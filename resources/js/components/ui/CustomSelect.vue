<template>
    <div ref="wrapper" class="relative">
        <!-- Trigger -->
        <button
            type="button"
            class="form-input flex items-center justify-between gap-2 text-left cursor-pointer"
            @click="open = !open"
        >
            <span :class="modelValue ? 'text-surface-100' : 'text-surface-500'">
                {{ selectedLabel || placeholder }}
            </span>
            <ChevronUpDownIcon class="w-4 h-4 text-surface-400 shrink-0" />
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <ul
                v-if="open"
                class="absolute right-0 z-50 mt-1 min-w-full w-max py-1 rounded-lg border border-white/10
                       bg-slate-900/90 backdrop-blur-xl shadow-xl overflow-auto max-h-52"
            >
                <li
                    v-for="opt in options"
                    :key="opt.value"
                    class="px-3 py-2 text-sm cursor-pointer transition-colors"
                    :class="opt.value === modelValue
                        ? 'bg-primary-500/20 text-primary-300'
                        : 'text-surface-300 hover:bg-white/5'"
                    @click="select(opt.value)"
                >
                    {{ opt.label }}
                </li>
            </ul>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ChevronUpDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Seleccionar...' },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const wrapper = ref(null)

const selectedLabel = computed(() => {
    const found = props.options.find(o => o.value === props.modelValue)
    return found?.label ?? ''
})

function select(value) {
    emit('update:modelValue', value)
    open.value = false
}

function onClickOutside(e) {
    if (wrapper.value && !wrapper.value.contains(e.target)) {
        open.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>
