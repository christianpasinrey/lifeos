<template>
    <div>
        <!-- Text -->
        <input
            v-if="field.type === 'text'"
            v-model="localVal"
            class="form-input"
            :disabled="disabled"
            @blur="$emit('update', localVal)"
        />

        <!-- Number -->
        <input
            v-else-if="field.type === 'number'"
            v-model="localVal"
            type="number"
            class="form-input"
            :disabled="disabled"
            @blur="$emit('update', localVal)"
        />

        <!-- Date -->
        <input
            v-else-if="field.type === 'date'"
            v-model="localVal"
            type="date"
            class="form-input"
            :disabled="disabled"
            @change="$emit('update', localVal)"
        />

        <!-- Checkbox (toggle switch) -->
        <button
            v-else-if="field.type === 'checkbox'"
            :disabled="disabled"
            @click="$emit('update', value === 'true' ? 'false' : 'true')"
            class="relative w-10 h-6 rounded-full transition-colors duration-200"
            :class="value === 'true' ? 'bg-primary-500' : 'bg-surface-600'"
        >
            <span
                class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200"
                :class="value === 'true' ? 'translate-x-4' : ''"
            />
        </button>

        <!-- URL -->
        <div v-else-if="field.type === 'url'" class="flex items-center gap-2">
            <input
                v-model="localVal"
                class="form-input flex-1"
                placeholder="https://..."
                :disabled="disabled"
                @blur="$emit('update', localVal)"
            />
            <a
                v-if="value"
                :href="value"
                target="_blank"
                class="text-primary-400 hover:text-primary-300"
            >
                <ArrowTopRightOnSquareIcon class="w-4 h-4" />
            </a>
        </div>

        <!-- Select -->
        <CustomSelect
            v-else-if="field.type === 'select'"
            :modelValue="value"
            :options="selectOptions"
            @update:modelValue="$emit('update', $event)"
        />

        <!-- Multi-select -->
        <div v-else-if="field.type === 'multi_select'" class="flex flex-wrap gap-1.5">
            <button
                v-for="choice in field.options?.choices ?? []"
                :key="choice.id"
                @click="toggleMulti(choice.id)"
                class="px-2 py-0.5 rounded-full text-xs font-medium transition-all"
                :class="isSelected(choice.id)
                    ? 'text-white shadow-sm'
                    : 'text-surface-400 bg-white/5 hover:bg-white/10'"
                :style="isSelected(choice.id) ? { backgroundColor: choice.color || '#6366f1' } : {}"
            >
                {{ choice.label }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'

const props = defineProps({
    field: { type: Object, required: true },
    value: { type: [String, null], default: null },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update'])

const localVal = ref(props.value ?? '')

watch(() => props.value, (v) => {
    localVal.value = v ?? ''
})

const selectOptions = computed(() =>
    props.field.options?.choices?.map(c => ({ value: c.id, label: c.label })) ?? []
)

// Multi-select helpers
function parseMulti() {
    if (!props.value) return []
    try { return JSON.parse(props.value) } catch { return [] }
}

function isSelected(choiceId) {
    return parseMulti().includes(choiceId)
}

function toggleMulti(choiceId) {
    const current = parseMulti()
    const idx = current.indexOf(choiceId)
    if (idx >= 0) {
        current.splice(idx, 1)
    } else {
        current.push(choiceId)
    }
    emit('update', JSON.stringify(current))
}
</script>
