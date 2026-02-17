<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    public function index(Request $request)
    {
        $invoices = $this->service->getInvoices($request->user(), [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
        ]);

        return response()->json(['data' => $invoices]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:issued,received',
            'number' => 'nullable|string|max:50',
            'counterpart_name' => 'required|string|max:255',
            'counterpart_tax_id' => 'nullable|string|max:50',
            'counterpart_address' => 'nullable|string',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.concept' => 'required|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.sort_order' => 'nullable|integer',
            'lines.*.taxes' => 'nullable|array',
            'lines.*.taxes.*.tax_id' => 'required|exists:finance_taxes,id',
            'lines.*.taxes.*.tax_rate_id' => 'required|exists:finance_tax_rates,id',
        ]);

        $lines = $validated['lines'];
        unset($validated['lines']);

        $invoice = $this->service->createInvoice($request->user(), $validated, $lines);

        return response()->json(['data' => $invoice], 201);
    }

    public function show(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        return response()->json([
            'data' => $invoice->load('lines.taxes', 'transactions'),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'number' => 'sometimes|string|max:50',
            'counterpart_name' => 'sometimes|string|max:255',
            'counterpart_tax_id' => 'nullable|string|max:50',
            'counterpart_address' => 'nullable|string',
            'issue_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'lines' => 'nullable|array|min:1',
            'lines.*.concept' => 'required_with:lines|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0.0001',
            'lines.*.unit_price' => 'required_with:lines|numeric',
            'lines.*.sort_order' => 'nullable|integer',
            'lines.*.taxes' => 'nullable|array',
            'lines.*.taxes.*.tax_id' => 'required|exists:finance_taxes,id',
            'lines.*.taxes.*.tax_rate_id' => 'required|exists:finance_tax_rates,id',
        ]);

        $lines = array_key_exists('lines', $validated) ? $validated['lines'] : null;
        unset($validated['lines']);

        $updated = $this->service->updateInvoice($invoice, $validated, $lines);

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $this->service->deleteInvoice($invoice);

        return response()->json(['message' => 'Factura eliminada']);
    }

    public function linkPayment(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $transaction = Transaction::where('id', $validated['transaction_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->service->linkPayment($invoice, $transaction, $validated['amount']);

        return response()->json([
            'data' => $invoice->fresh(['lines.taxes', 'transactions']),
        ]);
    }

    public function unlinkPayment(Request $request, Invoice $invoice, Transaction $transaction)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $this->service->unlinkPayment($invoice, $transaction);

        return response()->json([
            'data' => $invoice->fresh(['lines.taxes', 'transactions']),
        ]);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
        ]);

        $updated = $this->service->updateStatus($invoice, $validated['status']);

        return response()->json(['data' => $updated]);
    }
}
