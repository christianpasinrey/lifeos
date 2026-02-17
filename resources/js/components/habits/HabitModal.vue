<template>
    <Teleport to="body">
        <div class="modal-overlay">
            <!-- Backdrop -->
            <div class="modal-backdrop" @click="$emit('close')" />

            <!-- Modal -->
            <div class="modal-content liquid-glass liquid-glass-panel" style="--glass-radius: 20px;">
                <h2 class="section-title mb-5">
                    {{ habit ? 'Editar hábito' : 'Nuevo hábito' }}
                </h2>

                <!-- Template button -->
                <div v-if="hasTemplates && !habit" class="mb-4">
                    <button
                        type="button"
                        @click="showTemplates = true"
                        class="w-full py-2 px-3 rounded-lg border border-dashed border-white/[0.15] text-sm text-surface-300 hover:border-white/[0.25] hover:bg-white/[0.03] transition-all"
                    >
                        O elegir plantilla...
                    </button>
                </div>

                <HabitTemplateGallery
                    v-if="showTemplates"
                    @close="showTemplates = false"
                    @applied="emit('saved')"
                />

                <form @submit.prevent="handleSubmit" class="form-group">
                    <!-- Name -->
                    <div>
                        <label class="form-label">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="form-input"
                            placeholder="Ej: Meditar, Leer, Ejercicio..."
                        />
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="form-label">Tipo</label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="form.type = 'boolean'"
                                class="flex-1 py-2 px-3 rounded-lg text-sm font-medium border transition-colors"
                                :class="form.type === 'boolean'
                                    ? 'bg-primary-500/10 border-primary-500/40 text-primary-400'
                                    : 'border-white/[0.08] text-surface-400 hover:border-white/[0.15]'"
                            >
                                ✓ Sí/No
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'numeric'"
                                class="flex-1 py-2 px-3 rounded-lg text-sm font-medium border transition-colors"
                                :class="form.type === 'numeric'
                                    ? 'bg-primary-500/10 border-primary-500/40 text-primary-400'
                                    : 'border-white/[0.08] text-surface-400 hover:border-white/[0.15]'"
                            >
                                # Numérico
                            </button>
                        </div>
                    </div>

                    <!-- Numeric fields -->
                    <div v-if="form.type === 'numeric'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Unidad</label>
                            <input
                                v-model="form.unit"
                                type="text"
                                class="form-input-sm"
                                placeholder="pasos, litros, min..."
                            />
                        </div>
                        <div>
                            <label class="form-label">Objetivo</label>
                            <input
                                v-model.number="form.target_value"
                                type="number"
                                step="any"
                                min="0"
                                class="form-input-sm"
                                placeholder="10000"
                            />
                        </div>
                    </div>

                    <!-- Frequency -->
                    <div>
                        <label class="form-label">Frecuencia</label>
                        <div class="flex gap-2">
                            <button
                                v-for="freq in frequencies"
                                :key="freq.value"
                                type="button"
                                @click="form.frequency = freq.value"
                                class="flex-1 py-2 px-2 rounded-lg text-xs font-medium border transition-colors"
                                :class="form.frequency === freq.value
                                    ? 'bg-primary-500/10 border-primary-500/40 text-primary-400'
                                    : 'border-white/[0.08] text-surface-400 hover:border-white/[0.15]'"
                            >
                                {{ freq.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Routine selector -->
                    <div v-if="hasRoutines">
                        <label class="form-label">Rutina</label>
                        <div class="flex gap-2">
                            <button
                                v-for="r in routines"
                                :key="r.value"
                                type="button"
                                @click="form.routine = r.value"
                                class="flex-1 py-2 px-2 rounded-lg text-xs font-medium border transition-colors"
                                :class="form.routine === r.value
                                    ? 'bg-primary-500/10 border-primary-500/40 text-primary-400'
                                    : 'border-white/[0.08] text-surface-400 hover:border-white/[0.15]'"
                            >
                                {{ r.icon }} {{ r.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Day selector -->
                    <div v-if="form.frequency !== 'daily'">
                        <label class="form-label">Días</label>
                        <div class="flex gap-1.5">
                            <button
                                v-for="day in days"
                                :key="day.value"
                                type="button"
                                @click="toggleDay(day.value)"
                                class="w-10 h-10 rounded-lg text-xs font-medium border transition-all"
                                :class="form.target_days.includes(day.value)
                                    ? 'bg-primary-500/20 border-primary-500/40 text-primary-300'
                                    : 'border-white/[0.08] text-surface-500 hover:border-white/[0.15]'"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Color picker -->
                    <div>
                        <label class="form-label">Color</label>
                        <div class="flex gap-2">
                            <button
                                v-for="color in colors"
                                :key="color"
                                type="button"
                                @click="form.color = color"
                                class="w-8 h-8 rounded-full transition-all"
                                :style="{ backgroundColor: color }"
                                :class="form.color === color ? 'ring-2 ring-white/80 ring-offset-2 ring-offset-transparent scale-110' : 'hover:scale-105'"
                            />
                        </div>
                    </div>

                    <!-- Reminder -->
                    <div v-if="hasReminders">
                        <label class="form-label">Recordatorio</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.reminder_time"
                                type="time"
                                class="form-input-sm flex-1"
                            />
                            <span class="text-xs text-surface-500">Notificaciones próximamente</span>
                        </div>
                    </div>

                    <!-- Vacation mode -->
                    <div v-if="hasVacationMode && habit">
                        <label class="form-label">Modo vacaciones</label>
                        <div class="space-y-2">
                            <div v-for="v in vacations" :key="v.id" class="flex items-center gap-2 text-sm text-surface-300">
                                <span>{{ v.starts_at }} → {{ v.ends_at }}</span>
                                <span v-if="v.reason" class="text-surface-500">— {{ v.reason }}</span>
                                <button type="button" @click="removeVacation(v.id)" class="text-danger-400 text-xs hover:underline">Eliminar</button>
                            </div>
                            <div class="flex gap-2">
                                <input v-model="vacationForm.starts_at" type="date" class="form-input-sm flex-1" />
                                <input v-model="vacationForm.ends_at" type="date" class="form-input-sm flex-1" />
                                <button type="button" @click="addVacation" class="btn-secondary text-xs px-2" :disabled="!vacationForm.starts_at || !vacationForm.ends_at">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="flex-1 btn-secondary"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 btn-primary"
                        >
                            {{ saving ? 'Guardando...' : (habit ? 'Guardar' : 'Crear') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useCreateHabit, useUpdateHabit, useHabitVacations, useCreateVacation, useDeleteVacation } from '@/composables/useHabits'
import { useHabitFeatures } from '@/composables/useHabitFeatures'
import HabitTemplateGallery from './HabitTemplateGallery.vue'

const props = defineProps({ habit: Object })
const emit = defineEmits(['close', 'saved'])

const { mutateAsync: createHabit } = useCreateHabit()
const { mutateAsync: updateHabit } = useUpdateHabit()
const { hasRoutines, hasVacationMode, hasTemplates, hasReminders } = useHabitFeatures()

const saving = ref(false)
const showTemplates = ref(false)

// Vacation mode
const habitId = computed(() => props.habit?.id)
const { data: vacationsData } = useHabitVacations(habitId)
const vacations = computed(() => vacationsData.value ?? [])
const { mutateAsync: createVacation } = useCreateVacation()
const { mutateAsync: deleteVacation } = useDeleteVacation()
const vacationForm = reactive({ starts_at: '', ends_at: '' })

async function addVacation() {
    if (!vacationForm.starts_at || !vacationForm.ends_at) return
    await createVacation({ habitId: props.habit.id, data: { ...vacationForm } })
    vacationForm.starts_at = ''
    vacationForm.ends_at = ''
}

async function removeVacation(vacationId) {
    await deleteVacation({ habitId: props.habit.id, vacationId })
}

const form = reactive({
    name: props.habit?.name ?? '',
    type: props.habit?.type ?? 'boolean',
    unit: props.habit?.unit ?? '',
    target_value: props.habit?.target_value ?? null,
    frequency: props.habit?.frequency ?? 'daily',
    target_days: props.habit?.target_days ?? [],
    routine: props.habit?.routine ?? 'anytime',
    reminder_time: props.habit?.reminder_time ?? null,
    color: props.habit?.color ?? '#6366f1',
})

const frequencies = [
    { value: 'daily', label: 'Diario' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'custom', label: 'Personalizado' },
]

const days = [
    { value: 'mon', label: 'Lun' },
    { value: 'tue', label: 'Mar' },
    { value: 'wed', label: 'Mié' },
    { value: 'thu', label: 'Jue' },
    { value: 'fri', label: 'Vie' },
    { value: 'sat', label: 'Sáb' },
    { value: 'sun', label: 'Dom' },
]

const routines = [
    { value: 'morning', label: 'Mañana', icon: '🌅' },
    { value: 'afternoon', label: 'Tarde', icon: '☀️' },
    { value: 'evening', label: 'Noche', icon: '🌙' },
    { value: 'anytime', label: 'Cualquiera', icon: '⏰' },
]

const colors = [
    '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
    '#f97316', '#eab308', '#22c55e', '#10b981',
    '#06b6d4', '#3b82f6',
]

function toggleDay(day) {
    const idx = form.target_days.indexOf(day)
    if (idx >= 0) {
        form.target_days.splice(idx, 1)
    } else {
        form.target_days.push(day)
    }
}

async function handleSubmit() {
    saving.value = true
    try {
        if (props.habit) {
            await updateHabit({ id: props.habit.id, data: form })
        } else {
            await createHabit(form)
        }
        emit('saved')
    } catch (e) {
        console.error('Error saving habit:', e)
    } finally {
        saving.value = false
    }
}
</script>
