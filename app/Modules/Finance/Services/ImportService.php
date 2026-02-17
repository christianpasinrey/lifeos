<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\Finance\Models\Transaction;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportService
{
    /**
     * Import transactions from an uploaded file (XLS, XLSX, CSV).
     * Returns ['imported' => int, 'skipped' => int, 'errors' => array]
     */
    public function importFile(User $user, string $filePath, ?int $accountId = null, string $format = 'auto'): array
    {
        $rows = $this->parseFile($filePath);

        if (empty($rows)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['El archivo está vacío o no se pudo leer.']];
        }

        // Detect format from headers
        $headers = array_map(fn ($h) => mb_strtolower(trim($h)), array_keys($rows[0]));

        if ($format === 'auto') {
            $format = $this->detectFormat($headers);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $parsed = $this->parseRow($row, $format);

                if (!$parsed) {
                    $skipped++;
                    continue;
                }

                Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $accountId,
                    'type' => $parsed['type'],
                    'amount' => $parsed['amount'],
                    'description' => $parsed['description'],
                    'notes' => $parsed['notes'] ?? null,
                    'date' => $parsed['date'],
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
                if (count($errors) > 20) break;
            }
        }

        // Recalculate account balance
        if ($accountId && $imported > 0) {
            $account = \App\Modules\Finance\Models\Account::find($accountId);
            $account?->recalculateBalance();
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Preview first N rows from a file without importing.
     */
    public function preview(string $filePath, int $limit = 10): array
    {
        $rows = $this->parseFile($filePath);

        if (empty($rows)) {
            return ['headers' => [], 'rows' => [], 'format' => 'unknown', 'total' => 0];
        }

        $headers = array_keys($rows[0]);
        $headersLower = array_map(fn ($h) => mb_strtolower(trim($h)), $headers);
        $format = $this->detectFormat($headersLower);

        return [
            'headers' => $headers,
            'rows' => array_slice($rows, 0, $limit),
            'format' => $format,
            'total' => count($rows),
        ];
    }

    /**
     * Parse a spreadsheet/CSV file into an array of associative rows.
     */
    private function parseFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        if (count($data) < 2) {
            return [];
        }

        // Find the header row (skip logo/empty rows from ING)
        $headerRowIndex = null;
        foreach ($data as $index => $row) {
            $nonEmpty = array_filter($row, fn ($cell) => $cell !== null && trim((string)$cell) !== '');
            if (count($nonEmpty) >= 3) {
                // Check if this looks like a header row
                $joined = mb_strtolower(implode(' ', $nonEmpty));
                if (str_contains($joined, 'fecha') || str_contains($joined, 'date') ||
                    str_contains($joined, 'importe') || str_contains($joined, 'amount') ||
                    str_contains($joined, 'descripci') || str_contains($joined, 'concepto') ||
                    str_contains($joined, 'f. valor')) {
                    $headerRowIndex = $index;
                    break;
                }
            }
        }

        if ($headerRowIndex === null) {
            // Fallback: use first non-empty row as header
            foreach ($data as $index => $row) {
                $nonEmpty = array_filter($row, fn ($cell) => $cell !== null && trim((string)$cell) !== '');
                if (count($nonEmpty) >= 3) {
                    $headerRowIndex = $index;
                    break;
                }
            }
        }

        if ($headerRowIndex === null) {
            return [];
        }

        $headers = array_map(fn ($h) => trim((string)($h ?? '')), $data[$headerRowIndex]);

        $rows = [];
        foreach ($data as $index => $row) {
            if ($index <= $headerRowIndex) continue;

            // Skip empty rows
            $nonEmpty = array_filter($row, fn ($cell) => $cell !== null && trim((string)$cell) !== '');
            if (count($nonEmpty) < 2) continue;

            $assoc = [];
            foreach ($headers as $colKey => $headerName) {
                if ($headerName === '') continue;
                $assoc[$headerName] = $row[$colKey] ?? null;
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * Detect the bank format from headers.
     */
    private function detectFormat(array $headers): string
    {
        $joined = implode(' ', $headers);

        // ING Spain: "f. valor", "categoría", "subcategoría", "descripción", "comentario", "importe (€)", "saldo (€)"
        if (str_contains($joined, 'f. valor') && str_contains($joined, 'importe')) {
            return 'ing_es';
        }

        // ING Netherlands
        if (str_contains($joined, 'datum') && str_contains($joined, 'bedrag')) {
            return 'ing_nl';
        }

        // Generic: needs at least date + amount
        if ((str_contains($joined, 'fecha') || str_contains($joined, 'date')) &&
            (str_contains($joined, 'importe') || str_contains($joined, 'amount') || str_contains($joined, 'monto'))) {
            return 'generic_es';
        }

        return 'generic';
    }

    /**
     * Parse a single row based on detected format.
     */
    private function parseRow(array $row, string $format): ?array
    {
        return match ($format) {
            'ing_es' => $this->parseIngEs($row),
            'generic_es' => $this->parseGenericEs($row),
            default => $this->parseGeneric($row),
        };
    }

    /**
     * Parse ING Spain format.
     * Columns: F. VALOR | CATEGORÍA | SUBCATEGORÍA | DESCRIPCIÓN | COMENTARIO | IMPORTE (€) | SALDO (€)
     */
    private function parseIngEs(array $row): ?array
    {
        $date = $this->findColumn($row, ['f. valor', 'fecha valor', 'fecha']);
        $description = $this->findColumn($row, ['descripción', 'descripcion']);
        $amount = $this->findColumn($row, ['importe (€)', 'importe']);
        $category = $this->findColumn($row, ['categoría', 'categoria']);
        $comment = $this->findColumn($row, ['comentario']);

        if (!$date || $amount === null) return null;

        $parsedDate = $this->parseDate($date);
        $parsedAmount = $this->parseAmount($amount);

        if (!$parsedDate || $parsedAmount === null) return null;

        $notes = trim(implode(' ', array_filter([$category, $comment])));

        return [
            'date' => $parsedDate,
            'description' => $description ?: 'Sin descripción',
            'amount' => abs($parsedAmount),
            'type' => $parsedAmount >= 0 ? 'income' : 'expense',
            'notes' => $notes ?: null,
        ];
    }

    /**
     * Parse generic Spanish format.
     */
    private function parseGenericEs(array $row): ?array
    {
        $date = $this->findColumn($row, ['fecha', 'date', 'f. valor', 'fecha valor', 'fecha operación']);
        $description = $this->findColumn($row, ['descripción', 'descripcion', 'concepto', 'detalle']);
        $amount = $this->findColumn($row, ['importe', 'importe (€)', 'amount', 'monto', 'cantidad']);
        $notes = $this->findColumn($row, ['notas', 'comentario', 'observaciones', 'notes']);

        if (!$date || $amount === null) return null;

        $parsedDate = $this->parseDate($date);
        $parsedAmount = $this->parseAmount($amount);

        if (!$parsedDate || $parsedAmount === null) return null;

        return [
            'date' => $parsedDate,
            'description' => $description ?: 'Sin descripción',
            'amount' => abs($parsedAmount),
            'type' => $parsedAmount >= 0 ? 'income' : 'expense',
            'notes' => $notes ?: null,
        ];
    }

    /**
     * Parse generic format (English or any).
     */
    private function parseGeneric(array $row): ?array
    {
        $date = $this->findColumn($row, ['date', 'fecha', 'f. valor', 'transaction date', 'booking date']);
        $description = $this->findColumn($row, ['description', 'descripción', 'concept', 'memo', 'details', 'narrative']);
        $amount = $this->findColumn($row, ['amount', 'importe', 'value', 'sum']);
        $notes = $this->findColumn($row, ['notes', 'notas', 'reference', 'comment']);

        // Some formats have separate debit/credit columns
        if ($amount === null || $amount === '') {
            $credit = $this->findColumn($row, ['credit', 'ingreso', 'haber', 'in']);
            $debit = $this->findColumn($row, ['debit', 'gasto', 'debe', 'out']);

            if ($credit && $this->parseAmount($credit) > 0) {
                $amount = $credit;
            } elseif ($debit) {
                $amount = '-' . ltrim((string)$debit, '-');
            }
        }

        if (!$date || $amount === null) return null;

        $parsedDate = $this->parseDate($date);
        $parsedAmount = $this->parseAmount($amount);

        if (!$parsedDate || $parsedAmount === null) return null;

        return [
            'date' => $parsedDate,
            'description' => $description ?: 'Imported transaction',
            'amount' => abs($parsedAmount),
            'type' => $parsedAmount >= 0 ? 'income' : 'expense',
            'notes' => $notes ?: null,
        ];
    }

    /**
     * Find a column value by trying multiple header name variants (case-insensitive).
     */
    private function findColumn(array $row, array $variants): ?string
    {
        foreach ($row as $key => $value) {
            $keyLower = mb_strtolower(trim($key));
            foreach ($variants as $variant) {
                if ($keyLower === $variant || str_contains($keyLower, $variant)) {
                    return $value !== null ? trim((string)$value) : null;
                }
            }
        }
        return null;
    }

    /**
     * Parse various date formats.
     */
    private function parseDate($value): ?string
    {
        if (!$value) return null;

        $value = trim((string)$value);

        // Excel serial date number
        if (is_numeric($value) && (int)$value > 30000 && (int)$value < 60000) {
            try {
                $date = ExcelDate::excelToDateTimeObject((int)$value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        // DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // YYYY-MM-DD
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $value)) {
            return $value;
        }

        // YYYY/MM/DD
        if (preg_match('#^(\d{4})/(\d{2})/(\d{2})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        // Try PHP parsing as last resort
        try {
            $dt = new \DateTime($value);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse amount with European (1.234,56) or US (1,234.56) format.
     */
    private function parseAmount($value): ?float
    {
        if ($value === null || trim((string)$value) === '') return null;

        $value = trim((string)$value);

        // Remove currency symbols
        $value = preg_replace('/[€$£\s]/', '', $value);

        if ($value === '') return null;

        // European format: 1.234,56 → detect by comma as last separator
        if (preg_match('/,\d{1,2}$/', $value) && !str_contains($value, '.') ||
            (str_contains($value, '.') && str_contains($value, ',') && strrpos($value, ',') > strrpos($value, '.'))) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        // US format with thousands: 1,533.52 (comma before dot)
        elseif (str_contains($value, ',') && str_contains($value, '.') && strrpos($value, '.') > strrpos($value, ',')) {
            $value = str_replace(',', '', $value);
        }
        // Only commas, no dots
        elseif (str_contains($value, ',') && !str_contains($value, '.')) {
            // Could be european without dots: "1234,56"
            if (preg_match('/,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        }

        return is_numeric($value) ? (float)$value : null;
    }
}
