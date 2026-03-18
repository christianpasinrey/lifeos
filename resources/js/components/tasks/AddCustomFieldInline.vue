<template>
    <div>
        <button
            v-if="!expanded"
            @click="expanded = true"
            class="flex items-center gap-1.5 text-xs text-surface-500 hover:text-primary-400 transition-colors py-1"
        >
            <PlusIcon class="w-3.5 h-3.5" />
            Añadir campo
        </button>

        <div v-else class="space-y-2 p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
            <input v-model="form.name" class="form-input text-sm" placeholder="Nombre del campo" />

            <CustomSelect v-model="form.type" :options="typeOptions" />

            <!-- Options editor for select/multi_select -->
            <div v-if="['select', 'multi_select'].includes(form.type)" class="space-y-1.5">
                <div v-for="(opt, i) in form.choices" :key="i" class="flex items-center gap-2">
                    <input v-model="opt.label" class="form-input text-xs flex-1" placeholder="Opción..." />
                    <input v-model="opt.color" type="color" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent" />
                    <button @click="form.choices.splice(i, 1)" class="text-surface-600 hover:text-danger-400">
                        <XMarkIcon class="w-3.5 h-3.5" />
                    </button>
                </div>
                <button @click="addChoice" class="text-xs text-surface-500 hover:text-primary-400">+ Opción</button>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.required" class="form-checkbox" />
                <span class="text-xs text-surface-400">Obligatorio</span>
            </label>

            <div class="flex justify-end gap-2">
                <button @click="cancel" class="btn-secondary text-xs px-2 py-1">Cancelar</button>
                <button @click="create" class="btn-primary text-xs px-2 py-1" :disabled="!form.name || creating">Crear</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useCreateCustomField } from '@/composables/useCustomFields'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    boardId: { type: Number, required: true },
})

const emit = defineEmits(['created'])

const typeOptions = [
    { value: 'text', label: 'Texto' },
    { value: 'number', label: 'Número' },
    { value: 'date', label: 'Fecha' },
    { value: 'select', label: 'Selección' },
    { value: 'multi_select', label: 'Multi-selección' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'url', label: 'URL' },
]

const expanded = ref(false)
const creating = ref(false)
const createField = useCreateCustomField()

const defaultForm = () => ({
    name: '',
    type: 'text',
    required: false,
    choices: [],
})

const form = ref(defaultForm())

function addChoice() {
    form.value.choices.push({ label: '', color: '#6366f1' })
}

function cancel() {
    expanded.value = false
    form.value = defaultForm()
}

async function create() {
    creating.value = true
    try {
        const payload = {
            boardId: props.boardId,
            name: form.value.name,
            type: form.value.type,
            required: form.value.required,
        }

        if (['select', 'multi_select'].includes(form.value.type)) {
            payload.options = {
                choices: form.value.choices.map(c => ({
                    id: crypto.randomUUID(),
                    label: c.label,
                    color: c.color,
                })),
            }
        }

        await createField.mutateAsync(payload)
        emit('created')
        cancel()
    } finally {
        creating.value = false
    }
}
</script>
