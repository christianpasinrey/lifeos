<template>
    <div class="flex items-center gap-3">
        <!-- Habit name -->
        <div class="w-36 shrink-0 truncate">
            <span class="text-sm font-medium text-surface-200">{{ habit.name }}</span>
            <span v-if="habit.current_streak > 0" class="text-xs text-surface-400 ml-1">🔥{{ habit.current_streak }}</span>
        </div>

        <!-- Day cells -->
        <div class="flex gap-1.5">
            <HabitWeekCell
                v-for="day in habit.days"
                :key="day.date"
                :day="day"
                :color="habit.color"
                @toggle="$emit('toggle-date', habit.id, day.date)"
            />
        </div>
    </div>
</template>

<script setup>
import HabitWeekCell from './HabitWeekCell.vue'

defineProps({ habit: Object })
defineEmits(['toggle-date'])
</script>
