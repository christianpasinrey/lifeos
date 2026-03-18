import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

// Boards
export function useBoards() {
    return useQuery({
        queryKey: ['boards'],
        queryFn: () => api.get('/boards').then(r => r.data),
    })
}

export function useBoard(id) {
    return useQuery({
        queryKey: computed(() => ['boards', unref(id)]),
        queryFn: () => api.get(`/boards/${unref(id)}`).then(r => r.data),
        enabled: computed(() => !!unref(id)),
    })
}

export function useCreateBoard() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/boards', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['boards'] })
        },
    })
}

export function useUpdateBoard() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/boards/${id}`, data).then(r => r.data),
        onSuccess: (_, { id }) => {
            qc.invalidateQueries({ queryKey: ['boards'] })
            qc.invalidateQueries({ queryKey: ['boards', id] })
        },
    })
}

export function useDeleteBoard() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/boards/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['boards'] })
        },
    })
}

// Columns
export function useCreateColumn() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ boardId, ...data }) => api.post(`/boards/${boardId}/columns`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useUpdateColumn() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId, ...data }) => api.put(`/columns/${id}`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useDeleteColumn() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId }) => api.delete(`/columns/${id}`).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useReorderColumns() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ boardId, order }) => api.put(`/boards/${boardId}/columns/reorder`, { order }).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

// Tasks
export function useCreateTask() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ columnId, boardId, ...data }) => api.post(`/columns/${columnId}/tasks`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useUpdateTask() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId, ...data }) => api.put(`/tasks/${id}`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useDeleteTask() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId }) => api.delete(`/tasks/${id}`).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useMoveTask() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId, column_id, sort_order }) =>
            api.put(`/tasks/${id}/move`, { column_id, sort_order }).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

// Single Task & Attachments
export function useTask(id) {
    return useQuery({
        queryKey: computed(() => ['tasks', unref(id)]),
        queryFn: () => api.get(`/tasks/${unref(id)}`).then(r => r.data),
        enabled: computed(() => !!unref(id)),
    })
}

export function useUploadAttachment() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ taskId, boardId, file }) => {
            const formData = new FormData()
            formData.append('file', file)
            return api.post(`/tasks/${taskId}/attachments`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            }).then(r => r.data)
        },
        onSuccess: (_, { taskId, boardId }) => {
            qc.invalidateQueries({ queryKey: ['tasks', taskId] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useDeleteAttachment() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ taskId, boardId, attachmentId }) =>
            api.delete(`/tasks/${taskId}/attachments/${attachmentId}`).then(r => r.data),
        onSuccess: (_, { taskId, boardId }) => {
            qc.invalidateQueries({ queryKey: ['tasks', taskId] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

// AI Generation
export function useSuggestTasks() {
    return useMutation({
        mutationFn: (prompt) => api.post('/tasks/ai/suggest', { prompt }).then(r => r.data),
    })
}

export function useConfirmGeneratedTasks() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ columnId, tasks, boardId }) =>
            api.post('/tasks/ai/confirm', { column_id: columnId, tasks }).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}
