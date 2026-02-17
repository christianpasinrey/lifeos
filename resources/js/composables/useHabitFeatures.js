import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useHabitFeatures() {
    const auth = useAuthStore()

    const feature = (key) => computed(() => auth.hasFeature('habits', key))

    return {
        hasWeeklyView: feature('weekly_view'),
        hasRoutines: feature('routines'),
        hasSparklines: feature('sparklines'),
        hasNotes: feature('notes'),
        hasBadges: feature('badges'),
        hasStreakFreeze: feature('streak_freeze'),
        hasCelebrations: feature('celebrations'),
        hasCharts: feature('charts'),
        hasAiAnalyzer: feature('ai_analyzer'),
        hasTemplates: feature('templates'),
        hasCalendarView: feature('calendar_view'),
        hasVacationMode: feature('vacation_mode'),
        hasReminders: feature('reminders'),
    }
}
