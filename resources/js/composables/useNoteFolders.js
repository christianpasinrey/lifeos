import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useNoteFolders() {
    return useQuery({
        queryKey: ['note-folders'],
        queryFn: () => api.get('/note-folders').then(r => r.data),
    })
}

export function useCreateFolder() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/note-folders', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}

export function useUpdateFolder() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, ...data }) => api.put(`/note-folders/${id}`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}

export function useDeleteFolder() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id }) => api.delete(`/note-folders/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['note-folders'] })
            qc.invalidateQueries({ queryKey: ['notes'] })
        },
    })
}

export function useReorderFolders() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (items) => api.put('/note-folders/reorder', { items }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}
