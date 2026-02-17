import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useFinanceBudgets(activeOnly = true) {
    return useQuery({
        queryKey: ['finance', 'budgets', activeOnly],
        queryFn: () => api.get('/finance/budgets', { params: { active_only: activeOnly } }).then(r => r.data),
    })
}

export function useFinanceBudgetProgress() {
    return useQuery({
        queryKey: ['finance', 'budgets', 'progress'],
        queryFn: () => api.get('/finance/budgets/progress').then(r => r.data),
    })
}

export function useCreateBudget() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/finance/budgets', data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'budgets'] }),
    })
}

export function useUpdateBudget() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/budgets/${id}`, data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'budgets'] }),
    })
}

export function useDeleteBudget() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/finance/budgets/${id}`).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'budgets'] }),
    })
}
