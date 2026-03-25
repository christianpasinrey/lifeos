<template>
    <div class="space-y-3">
        <div>
            <label class="form-label">Repetir</label>
            <CustomSelect v-model="freq" :options="freqOptions" />
        </div>

        <template v-if="freq !== 'none'">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Cada</label>
                    <div class="flex items-center gap-2">
                        <input
                            v-model.number="interval"
                            type="number"
                            min="1"
                            max="99"
                            class="form-input w-20"
                        />
                        <span class="text-sm text-surface-400">{{ intervalLabel }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="form-label">Termina</label>
                <CustomSelect v-model="endType" :options="endOptions" />
            </div>

            <div v-if="endType === 'count'">
                <label class="form-label">Repeticiones</label>
                <input
                    v-model.number="count"
                    type="number"
                    min="1"
                    max="999"
                    class="form-input w-28"
                />
            </div>

            <div v-if="endType === 'until'">
                <label class="form-label">Hasta</label>
                <input
                    v-model="until"
                    type="date"
                    class="form-input"
                />
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'

const props = defineProps({
    modelValue: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue'])

const freqOptions = [
    { value: 'none', label: 'No repetir' },
    { value: 'daily', label: 'Diaria' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'monthly', label: 'Mensual' },
    { value: 'yearly', label: 'Anual' },
]

const endOptions = [
    { value: 'never', label: 'Nunca' },
    { value: 'count', label: 'Después de N repeticiones' },
    { value: 'until', label: 'En una fecha' },
]

const freq = ref(props.modelValue?.freq ?? 'none')
const interval = ref(props.modelValue?.interval ?? 1)
const endType = ref(
    props.modelValue?.count ? 'count' :
    props.modelValue?.until ? 'until' : 'never'
)
const count = ref(props.modelValue?.count ?? 10)
const until = ref(props.modelValue?.until ?? '')

const intervalLabel = computed(() => {
    const labels = { daily: 'días', weekly: 'semanas', monthly: 'meses', yearly: 'años' }
    return labels[freq.value] ?? ''
})

function emitValue() {
    if (freq.value === 'none') {
        emit('update:modelValue', null)
        return
    }
    const rule = { freq: freq.value, interval: interval.value }
    if (endType.value === 'count') rule.count = count.value
    if (endType.value === 'until' && until.value) rule.until = until.value
    emit('update:modelValue', rule)
}

watch([freq, interval, endType, count, until], emitValue, { deep: true })
</script>
