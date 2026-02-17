import { useQueries, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import { useModuleRegistry } from '@/modules/registry'
import api from './useApi'

export function useCalendarSources(start, end) {
    const { calendarSlots } = useModuleRegistry()
    const queryClient = useQueryClient()

    const queries = useQueries({
        queries: computed(() =>
            calendarSlots.value.map(slot => ({
                queryKey: ['calendar', 'source', slot.source, unref(start), unref(end)],
                queryFn: () => api.get(`/calendar/sources/${slot.source}/events`, {
                    params: { start: unref(start), end: unref(end) },
                }).then(r => r.data.data),
                enabled: !!unref(start) && !!unref(end),
            }))
        ),
    })

    const allEvents = computed(() => queries.value.flatMap(q => q.data ?? []))
    const isLoading = computed(() => queries.value.some(q => q.isLoading))

    const sourceStates = computed(() =>
        calendarSlots.value.map((slot, i) => ({
            ...slot,
            events: queries.value[i]?.data ?? [],
            isLoading: queries.value[i]?.isLoading ?? true,
        }))
    )

    function invalidateAll() {
        queryClient.invalidateQueries({ queryKey: ['calendar', 'source'] })
    }

    return { allEvents, isLoading, sourceStates, calendarSlots, invalidateAll }
}
