import { useQuery } from '@tanstack/vue-query'
import { computed, ref, unref, watch } from 'vue'
import api from './useApi'

export function useNoteSearch() {
    const query = ref('')
    const debouncedQuery = ref('')
    let timer = null

    watch(query, (val) => {
        clearTimeout(timer)
        timer = setTimeout(() => {
            debouncedQuery.value = val
        }, 300)
    })

    const results = useQuery({
        queryKey: computed(() => ['notes-search', debouncedQuery.value]),
        queryFn: () => {
            const q = debouncedQuery.value
            if (!q || q.length < 1) return { data: [] }
            return api.get('/notes/search', { params: { q } }).then(r => r.data)
        },
        enabled: computed(() => debouncedQuery.value.length >= 1),
    })

    return {
        query,
        debouncedQuery,
        ...results,
    }
}
