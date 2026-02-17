<template>
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside class="calendar-panel-aside">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <BanknotesIcon class="w-4 h-4 text-amber-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-white">Nuevo registro</h3>
                        </div>
                        <button @click="$emit('close')" class="btn-close">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto px-5 py-4 glass-scroll">
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="form.type" :options="typeOptions" />
                            </div>

                            <div>
                                <label class="form-label">Monto</label>
                                <input
                                    v-model.number="form.amount"
                                    class="form-input"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    required
                                />
                            </div>

                            <div>
                                <label class="form-label">Descripción</label>
                                <input
                                    v-model="form.description"
                                    class="form-input"
                                    placeholder="Descripción del registro"
                                    required
                                    ref="descInput"
                                />
                            </div>

                            <div>
                                <label class="form-label">Fecha</label>
                                <input
                                    :value="date"
                                    class="form-input"
                                    type="date"
                                    disabled
                                />
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-white/[0.06] flex justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button @click="handleSubmit" class="btn-primary" :disabled="saving">
                            Crear registro
                        </button>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCreateTransaction } from '@/composables/useFinance'
import { useQueryClient } from '@tanstack/vue-query'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { BanknotesIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    date: { type: String, required: true },
})

const emit = defineEmits(['close', 'saved'])

const typeOptions = [
    { value: 'expense', label: 'Gasto' },
    { value: 'income', label: 'Ingreso' },
]

const form = ref({
    type: 'expense',
    amount: null,
    description: '',
})

const saving = ref(false)
const descInput = ref(null)
const queryClient = useQueryClient()
const createTransaction = useCreateTransaction()

onMounted(() => {
    descInput.value?.focus()
})

async function handleSubmit() {
    if (!form.value.description || !form.value.amount || saving.value) return
    saving.value = true
    try {
        await createTransaction.mutateAsync({
            type: form.value.type,
            amount: Math.abs(form.value.amount),
            description: form.value.description,
            date: props.date,
        })
        queryClient.invalidateQueries({ queryKey: ['calendar', 'source'] })
        emit('saved')
        emit('close')
    } finally {
        saving.value = false
    }
}
</script>
