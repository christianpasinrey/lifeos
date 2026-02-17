<template>
    <div class="habit-card group">
        <!-- Toggle / Value -->
        <button
            v-if="habit.type === 'boolean'"
            @click="$emit('toggle', habit, $event)"
            class="habit-checkbox"
            :class="habit.completed_today ? 'habit-checkbox-done' : 'habit-checkbox-pending'"
        >
            <CheckIcon v-if="habit.completed_today" class="w-4 h-4 text-white" />
        </button>
        <div v-else class="shrink-0 w-7 flex justify-center">
            <div
                class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold"
                :style="{ backgroundColor: habit.color + '20', color: habit.color }"
            >
                {{ habit.unit?.charAt(0)?.toUpperCase() || '#' }}
            </div>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span
                    class="text-sm font-medium transition-colors"
                    :class="habit.completed_today ? 'habit-name-done' : 'habit-name'"
                >
                    {{ habit.name }}
                </span>
                <span v-if="habit.current_streak > 0" class="habit-streak">
                    🔥 {{ habit.current_streak }}
                </span>
                <span v-if="hasStreakFreeze && habit.streak_freeze_count > 0" class="text-xs text-blue-400" title="Congelaciones disponibles">
                    🧊 {{ habit.streak_freeze_count }}
                </span>
                <SparklineChart
                    v-if="hasSparklines && sparkline"
                    :data="sparkline"
                    :type="habit.type"
                    :color="habit.color"
                />
            </div>

            <!-- Numeric input / progress -->
            <div v-if="habit.type === 'numeric'" class="mt-1.5 flex items-center gap-2">
                <input
                    v-if="editingValue"
                    ref="valueInput"
                    v-model.number="localValue"
                    type="number"
                    step="any"
                    min="0"
                    class="w-20 px-2 py-1 bg-white/[0.05] border border-white/[0.08] rounded text-sm text-surface-100 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    @keydown.enter="saveValue"
                    @blur="saveValue"
                />
                <button
                    v-else
                    @click="startEditing"
                    class="text-sm text-surface-400 hover:text-primary-400 transition-colors"
                >
                    {{ habit.today_value ?? 0 }} {{ habit.unit }}
                </button>
                <span v-if="habit.target_value" class="text-xs text-surface-500">
                    / {{ habit.target_value }} {{ habit.unit }}
                </span>
                <!-- Progress bar -->
                <div v-if="habit.progress !== null" class="flex-1 max-w-32">
                    <div class="progress-track">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="habit.progress >= 100 ? 'bg-accent-500' : 'bg-primary-500'"
                            :style="{ width: Math.min(100, habit.progress) + '%' }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes toggle -->
        <button
            v-if="hasNotes && habit.completed_today"
            @click="showNotes = !showNotes"
            class="btn-icon shrink-0"
            :class="{ 'text-primary-400': habit.today_notes || showNotes }"
            title="Nota"
        >
            <ChatBubbleLeftIcon class="w-4 h-4" />
        </button>

        <!-- Color dot -->
        <div
            class="w-2.5 h-2.5 rounded-full shrink-0"
            :style="{ backgroundColor: habit.color }"
        />

        <!-- Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
                @click="$emit('stats', habit)"
                class="btn-icon"
                title="Estadísticas"
            >
                <ChartBarIcon class="w-4 h-4" />
            </button>
            <button
                @click="$emit('edit', habit)"
                class="btn-icon"
                title="Editar"
            >
                <PencilSquareIcon class="w-4 h-4" />
            </button>
            <button
                @click="confirmDelete"
                class="btn-icon text-danger-400/60 hover:text-danger-400"
                title="Eliminar"
            >
                <TrashIcon class="w-4 h-4" />
            </button>
        </div>
    </div>

    <!-- Notes expandable -->
    <div v-if="hasNotes && showNotes && habit.completed_today" class="habit-card mt-1 !py-2">
        <input
            v-model="localNotes"
            type="text"
            class="flex-1 bg-transparent text-sm text-surface-200 placeholder-surface-500 focus:outline-none"
            placeholder="Agregar nota..."
            @keydown.enter="saveNotes"
            @blur="saveNotes"
        />
    </div>

    <ConfirmModal
        v-if="showDeleteConfirm"
        title="Eliminar hábito"
        :message="`¿Eliminar &quot;${habit.name}&quot;? Esta acción no se puede deshacer.`"
        confirm-text="Eliminar"
        :danger="true"
        @confirm="emit('delete', habit); showDeleteConfirm = false"
        @cancel="showDeleteConfirm = false"
    />
</template>

<script setup>
import { ref, nextTick, watch } from 'vue'
import { CheckIcon, ChartBarIcon, PencilSquareIcon, ChatBubbleLeftIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useHabitFeatures } from '@/composables/useHabitFeatures'
import SparklineChart from './SparklineChart.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const { hasNotes, hasSparklines, hasStreakFreeze } = useHabitFeatures()

const props = defineProps({
    habit: Object,
    sparkline: { type: Array, default: null },
})
const emit = defineEmits(['toggle', 'edit', 'stats', 'delete', 'update-value', 'save-notes'])

const showDeleteConfirm = ref(false)
const editingValue = ref(false)
const localValue = ref(0)
const valueInput = ref(null)
const showNotes = ref(false)
const localNotes = ref(props.habit?.today_notes ?? '')

watch(() => props.habit?.today_notes, (v) => { localNotes.value = v ?? '' })

function startEditing() {
    localValue.value = props.habit.today_value || 0
    editingValue.value = true
    nextTick(() => valueInput.value?.focus())
}

function saveValue() {
    editingValue.value = false
    if (localValue.value > 0) {
        emit('update-value', props.habit, localValue.value)
    }
}

function saveNotes() {
    if (localNotes.value !== (props.habit.today_notes ?? '')) {
        emit('save-notes', props.habit, localNotes.value)
    }
}

function confirmDelete() {
    showDeleteConfirm.value = true
}
</script>
