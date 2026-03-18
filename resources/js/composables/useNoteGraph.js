import { useQuery } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useNoteGraph(scope, noteId, depth) {
    return useQuery({
        queryKey: computed(() => ['notes-graph', unref(scope), unref(noteId), unref(depth)]),
        queryFn: () => {
            const params = { scope: unref(scope) || 'global' }
            const nId = unref(noteId)
            if (nId) params.note_id = nId
            const d = unref(depth)
            if (d) params.depth = d
            return api.get('/notes-graph', { params }).then(r => r.data)
        },
    })
}
