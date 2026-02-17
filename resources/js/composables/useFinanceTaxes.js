import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useFinanceTaxes() {
    return useQuery({
        queryKey: ['finance', 'taxes'],
        queryFn: () => api.get('/finance/taxes').then(r => r.data),
    })
}

export function useCreateTax() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (data) => api.post('/finance/taxes', data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'taxes'] }),
    })
}

export function useUpdateTax() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/taxes/${id}`, data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'taxes'] }),
    })
}

export function useDeleteTax() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (id) => api.delete(`/finance/taxes/${id}`).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'taxes'] }),
    })
}

export function useCreateTaxRate() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ taxId, data }) => api.post(`/finance/taxes/${taxId}/rates`, data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'taxes'] }),
    })
}

export function useUpdateTaxRate() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/taxes/rates/${id}`, data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'taxes'] }),
    })
}

export function useDeleteTaxRate() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (id) => api.delete(`/finance/taxes/rates/${id}`).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'taxes'] }),
    })
}
