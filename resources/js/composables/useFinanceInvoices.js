import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useFinanceInvoices(filters) {
    return useQuery({
        queryKey: computed(() => ['finance', 'invoices', JSON.stringify(unref(filters) || {})]),
        queryFn: () => {
            const raw = unref(filters) || {}
            const params = {}
            if (raw.type) params.type = raw.type
            if (raw.status) params.status = raw.status
            return api.get('/finance/invoices', { params }).then(r => r.data)
        },
    })
}

export function useInvoiceDetail(id) {
    return useQuery({
        queryKey: computed(() => ['finance', 'invoices', unref(id)]),
        queryFn: () => api.get(`/finance/invoices/${unref(id)}`).then(r => r.data),
        enabled: computed(() => !!unref(id)),
    })
}

function invalidate(qc) {
    qc.invalidateQueries({ queryKey: ['finance', 'invoices'] })
}

export function useCreateInvoice() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/finance/invoices', data).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useUpdateInvoice() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/invoices/${id}`, data).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useDeleteInvoice() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/finance/invoices/${id}`).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useLinkPayment() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ invoiceId, transactionId, amount }) =>
            api.post(`/finance/invoices/${invoiceId}/link-payment`, {
                transaction_id: transactionId,
                amount,
            }).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useUnlinkPayment() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ invoiceId, transactionId }) =>
            api.post(`/finance/invoices/${invoiceId}/unlink-payment/${transactionId}`).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useUpdateInvoiceStatus() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, status }) =>
            api.post(`/finance/invoices/${id}/status`, { status }).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}
