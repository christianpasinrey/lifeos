import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useProfile() {
    return useQuery({
        queryKey: ['profile'],
        queryFn: () => api.get('/profile').then(r => r.data),
    })
}

export function useUpdateProfile() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.put('/profile', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['profile'] })
        },
    })
}

export function useMcpTokenStatus() {
    return useQuery({
        queryKey: ['mcp-token-status'],
        queryFn: () => api.get('/profile/mcp-token/status').then(r => r.data),
    })
}

export function useGenerateMcpToken() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: () => api.post('/profile/mcp-token').then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['mcp-token-status'] })
        },
    })
}

export function useRevokeMcpToken() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: () => api.delete('/profile/mcp-token').then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['mcp-token-status'] })
        },
    })
}
