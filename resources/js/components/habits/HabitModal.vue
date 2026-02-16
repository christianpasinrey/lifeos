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
import { ref, reactive } from 'vue'
import { useCreateHabit, useUpdateHabit } from '@/composables/useHabits'

const props = defineProps({ habit: Object })
const emit = defineEmits(['close', 'saved'])

const { mutateAsync: createHabit } = useCreateHabit()
const { mutateAsync: updateHabit } = useUpdateHabit()

const saving = ref(false)

const form = reactive({
    name: props.habit?.name ?? '',
    type: props.habit?.type ?? 'boolean',
    unit: props.habit?.unit ?? '',
    target_value: props.habit?.target_value ?? null,
    frequency: props.habit?.frequency ?? 'daily',
    target_days: props.habit?.target_days ?? [],
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
