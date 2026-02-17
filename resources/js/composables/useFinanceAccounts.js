import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useFinanceAccounts(activeOnly = true) {
    return useQuery({
        queryKey: ['finance', 'accounts', activeOnly],
        queryFn: () => api
            .get('/finance/accounts', { params: { active_only: activeOnly } })
            .then(r => r.data),
    })
}

function invalidateAccountData(qc) {
    qc.invalidateQueries({ queryKey: ['finance', 'accounts'] })
    qc.invalidateQueries({ queryKey: ['finance', 'summary'] })
}

export function useCreateAccount() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (data) => api.post('/finance/accounts', data).then(r => r.data),
        onSuccess: () => invalidateAccountData(qc),
    })
}

export function useUpdateAccount() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/accounts/${id}`, data).then(r => r.data),
        onSuccess: () => invalidateAccountData(qc),
    })
}

export function useDeleteAccount() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (id) => api.delete(`/finance/accounts/${id}`).then(r => r.data),
        onSuccess: () => invalidateAccountData(qc),
    })
}

export function useRecalculateAccount() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (id) => api.post(`/finance/accounts/${id}/recalculate`).then(r => r.data),
        onSuccess: () => invalidateAccountData(qc),
    })
}

export function useCreateTransfer() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (data) => api.post('/finance/transfers', data).then(r => r.data),
        onSuccess: () => {
            invalidateAccountData(qc)
            qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
        },
    })
}
