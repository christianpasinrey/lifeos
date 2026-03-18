import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useNoteTags() {
    return useQuery({
        queryKey: ['note-tags'],
        queryFn: () => api.get('/note-tags').then(r => r.data),
    })
}

export function useDeleteTag() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id }) => api.delete(`/note-tags/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['note-tags'] })
        },
    })
}
