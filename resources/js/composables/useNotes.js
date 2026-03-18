import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useNotes(folderId) {
    return useQuery({
        queryKey: computed(() => ['notes', { folder: unref(folderId) }]),
        queryFn: () => {
            const params = {}
            const f = unref(folderId)
            if (f) params.folder_id = f
            return api.get('/notes', { params }).then(r => r.data)
        },
    })
}

export function useNote(slug) {
    return useQuery({
        queryKey: computed(() => ['notes', unref(slug)]),
        queryFn: () => api.get(`/notes/${unref(slug)}`).then(r => r.data),
        enabled: computed(() => !!unref(slug)),
    })
}

export function useBookmarkedNotes() {
    return useQuery({
        queryKey: ['notes', 'bookmarked'],
        queryFn: () => api.get('/notes', { params: { bookmarked: 1 } }).then(r => r.data),
    })
}

export function useTrashNotes() {
    return useQuery({
        queryKey: ['notes', 'trash'],
        queryFn: () => api.get('/notes/trash').then(r => r.data),
    })
}

export function useCreateNote() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/notes', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['notes'] })
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}

export function useUpdateNote() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ slug, ...data }) => api.put(`/notes/${slug}`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['notes'] })
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}

export function useDeleteNote() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, slug }) => api.delete(`/notes/${slug || id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['notes'] })
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}

export function useRestoreNote() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, slug }) => api.post(`/notes/${slug || id}/restore`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['notes'] })
            qc.invalidateQueries({ queryKey: ['note-folders'] })
        },
    })
}

export function useDailyNote() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: () => api.post('/notes/daily').then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['notes'] })
        },
    })
}

export function useLinkEntity() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ noteId, noteable_type, noteable_id }) =>
            api.post(`/notes/${noteId}/link`, { noteable_type, noteable_id }).then(r => r.data),
        onSuccess: (_, { noteId }) => {
            qc.invalidateQueries({ queryKey: ['notes', noteId] })
        },
    })
}

export function useUnlinkEntity() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ noteId, noteableId }) =>
            api.delete(`/notes/${noteId}/link/${noteableId}`).then(r => r.data),
        onSuccess: (_, { noteId }) => {
            qc.invalidateQueries({ queryKey: ['notes', noteId] })
        },
    })
}
