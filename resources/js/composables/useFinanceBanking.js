import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useInstitutions() {
    return useQuery({
        queryKey: ['finance', 'banking', 'institutions'],
        queryFn: () => api.get('/finance/banking/institutions').then(r => r.data),
        staleTime: 24 * 60 * 60 * 1000, // 24h cache
    })
}

export function useBankConnections() {
    return useQuery({
        queryKey: ['finance', 'banking', 'connections'],
        queryFn: () => api.get('/finance/banking/connections').then(r => r.data),
    })
}

export function useBankConnectionDetail(id) {
    return useQuery({
        queryKey: ['finance', 'banking', 'connections', id],
        queryFn: () => api.get(`/finance/banking/connections/${id}`).then(r => r.data),
        enabled: !!id,
    })
}

export function useCreateBankConnection() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/finance/banking/connections', data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'banking'] }),
    })
}

export function useDeleteBankConnection() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/finance/banking/connections/${id}`).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'banking'] }),
    })
}

export function useSyncBankConnection() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.post(`/finance/banking/connections/${id}/sync`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance', 'banking'] })
            qc.invalidateQueries({ queryKey: ['finance', 'accounts'] })
            qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
        },
    })
}

export function useReconnectBankConnection() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.post(`/finance/banking/connections/${id}/reconnect`).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['finance', 'banking'] }),
    })
}
