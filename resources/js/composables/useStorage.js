import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useDriveFiles() {
    return useQuery({
        queryKey: ['storage', 'files'],
        queryFn: () => api.get('/storage/files').then(r => r.data),
    })
}

export function useDeleteDriveFile() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (mediaId) => api.delete(`/storage/files/${mediaId}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['storage', 'files'] })
            qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
        },
    })
}

export function useUploadTransactionMedia() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ transactionId, file }) => {
            const formData = new FormData()
            formData.append('file', file)

            return api.post(`/storage/transactions/${transactionId}/media`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            }).then(r => r.data)
        },
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
            qc.invalidateQueries({ queryKey: ['storage', 'files'] })
        },
    })
}

export function useDeleteTransactionMedia() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ transactionId, mediaId }) =>
            api.delete(`/storage/transactions/${transactionId}/media/${mediaId}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
            qc.invalidateQueries({ queryKey: ['storage', 'files'] })
        },
    })
}
