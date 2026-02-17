import { markRaw } from 'vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

function parseTransactionsJson(text) {
    const match = text.match(/TRANSACTIONS_JSON_START\s*([\s\S]*?)\s*TRANSACTIONS_JSON_END/)
    if (match) {
        try { return JSON.parse(match[1].trim()) } catch { return null }
    }
    const jsonMatch = text.match(/\[\s*\{[\s\S]*?"type"\s*:[\s\S]*?\}\s*\]/)
    if (jsonMatch) {
        try { return JSON.parse(jsonMatch[0]) } catch { return null }
    }
    return null
}

const extractPrompt = [
    'Analiza este archivo y extrae TODAS las transacciones financieras individuales (ingresos y gastos).',
    'IMPORTANTE: Si hay cuotas mensuales, plazos o pagos recurrentes, EXPANDE cada uno como una transacción individual con su fecha concreta.',
    'Por ejemplo, "23 cuotas de 125€" = 23 transacciones separadas, cada una con su fecha de vencimiento.',
    'Para cada transacción indica: tipo (income/expense), importe, descripción breve, fecha exacta, y categoría sugerida.',
    'Primero muestra un resumen legible de lo que vas a crear y cuántas transacciones serán en total.',
    'Después incluye OBLIGATORIAMENTE un bloque JSON con TODAS las transacciones individuales expandidas.',
    'Formato (sin bloques de código markdown):',
    'TRANSACTIONS_JSON_START',
    '[{"type":"expense","amount":125.98,"description":"Cuota 1/23 Aplazame","date":"2025-11-04","category_name":"Financiación","category_type":"expense","category_color":"#f59e0b"}]',
    'TRANSACTIONS_JSON_END',
].join('\n')

registerModule({
    module: 'finance',
    navItems: [
        { to: '/finance', label: 'Finanzas', icon: markRaw(BanknotesIcon), order: 30 },
    ],
    actions: [
        {
            slot: 'drive-file-actions',
            label: 'Extraer transacciones',
            icon: markRaw(BanknotesIcon),
            prompt: extractPrompt,
            confirmAction: {
                label: 'Confirmar y crear transacciones',
                async handler({ aiResponse, reAnalyze }) {
                    let transactions = parseTransactionsJson(aiResponse)

                    if (!transactions || !Array.isArray(transactions) || transactions.length === 0) {
                        const followUp = [
                            'El usuario CONFIRMA. Genera AHORA el JSON con TODAS las transacciones que propusiste.',
                            'Tu propuesta anterior fue:',
                            '---',
                            aiResponse,
                            '---',
                            'REGLAS ESTRICTAS:',
                            '- Genera UNA entrada JSON por cada transacción individual.',
                            '- Si propusiste cuotas/plazos recurrentes, EXPANDE cada cuota como una entrada separada con su fecha concreta.',
                            '- Por ejemplo "23 cuotas" = 23 objetos JSON, cada uno con fecha incrementada mensualmente.',
                            '- Devuelve SOLO el bloque JSON, sin texto ni explicaciones.',
                            'TRANSACTIONS_JSON_START',
                            '[{"type":"expense","amount":125.98,"description":"Cuota 1/23","date":"2025-11-04","category_name":"Financiación","category_type":"expense","category_color":"#f59e0b"}]',
                            'TRANSACTIONS_JSON_END',
                        ].join('\n')

                        const result = await reAnalyze(followUp)
                        transactions = parseTransactionsJson(result.text)
                    }

                    if (!transactions || !Array.isArray(transactions) || transactions.length === 0) {
                        return { error: 'No se pudieron extraer las transacciones. Intenta con "Extraer transacciones" de nuevo.' }
                    }

                    const { default: api } = await import('@/composables/useApi')
                    const catResponse = await api.get('/finance/categories')
                    const existingCategories = catResponse.data?.data ?? []
                    const catMap = {}
                    existingCategories.forEach(c => { catMap[c.name.toLowerCase()] = c.id })

                    let created = 0
                    let newCats = 0

                    for (const tx of transactions) {
                        let categoryId = null
                        if (tx.category_name) {
                            const key = tx.category_name.toLowerCase()
                            if (catMap[key]) {
                                categoryId = catMap[key]
                            } else {
                                const newCat = await api.post('/finance/categories', {
                                    name: tx.category_name,
                                    color: tx.category_color || '#6366f1',
                                    type: tx.category_type || 'both',
                                })
                                categoryId = newCat.data?.data?.id
                                if (categoryId) {
                                    catMap[key] = categoryId
                                    newCats++
                                }
                            }
                        }

                        await api.post('/finance/transactions', {
                            type: tx.type || 'expense',
                            amount: Math.abs(Number(tx.amount)),
                            description: tx.description,
                            notes: tx.notes || null,
                            category_id: categoryId,
                            date: tx.date || null,
                        })
                        created++
                    }

                    return {
                        message: `Se crearon ${created} transacciones` + (newCats > 0 ? ` y ${newCats} categorías nuevas.` : '.'),
                    }
                },
            },
            order: 30,
        },
    ],
})
