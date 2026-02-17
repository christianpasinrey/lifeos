import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

function invalidateStorage(qc) {
    qc.invalidateQueries({ queryKey: ['storage'] })
    qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
}

export function useDriveFiles(filters) {
    return useQuery({
        queryKey: computed(() => ['storage', 'files', JSON.stringify(unref(filters) ?? {})]),
        queryFn: () => {
            const raw = unref(filters)
            const params = {}
            if (raw?.search) params.search = raw.search
            if (raw?.category && raw.category !== 'all') params.category = raw.category
            return api.get('/storage/files', { params }).then(r => r.data)
        },
    })
}

export function useDriveStats() {
    return useQuery({
        queryKey: ['storage', 'stats'],
        queryFn: () => api.get('/storage/stats').then(r => r.data),
    })
}

export function useUploadDriveFile() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (file) => {
            const formData = new FormData()
            formData.append('file', file)

            return api.post('/storage/files', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            }).then(r => r.data)
        },
        onSuccess: () => invalidateStorage(qc),
    })
}

export function useDeleteDriveFile() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (mediaId) => api.delete(`/storage/files/${mediaId}`).then(r => r.data),
        onSuccess: () => invalidateStorage(qc),
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
        onSuccess: () => invalidateStorage(qc),
    })
}

export function useDeleteTransactionMedia() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ transactionId, mediaId }) =>
            api.delete(`/storage/transactions/${transactionId}/media/${mediaId}`).then(r => r.data),
        onSuccess: () => invalidateStorage(qc),
    })
}
