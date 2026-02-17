<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-surface-400">Etiquetas para organizar ingresos y gastos</p>
            <button class="btn-add" @click="openForm()">
                <PlusIcon class="w-4 h-4" />
                Nueva categoría
            </button>
        </div>

        <div v-if="isLoading" class="text-center py-10 text-surface-500">Cargando...</div>

        <div v-else-if="categories.length === 0" class="text-center py-10 text-surface-500">
            No hay categorías configuradas
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="cat in categories"
                :key="cat.id"
                class="liquid-glass liquid-glass-card p-5 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <span class="w-2 h-8 rounded-full" :style="{ background: cat.color }" />
                    <div>
                        <p class="text-sm font-medium text-white">{{ cat.name }}</p>
                        <p class="text-xs text-surface-500 uppercase tracking-wide">{{ typeLabel(cat.type) }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="text-xs font-medium text-surface-400 hover:text-white transition" @click="openForm(cat)">Editar</button>
                    <button class="text-xs font-medium text-danger-400 hover:text-danger-500 transition" @click="remove(cat)">Eliminar</button>
                </div>
            </div>
        </div>

        <!-- Form modal -->
        <Teleport to="body">
            <div v-if="showForm" class="modal-overlay" @mousedown.self="showForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-md">
                    <h2 class="section-title mb-5">
                        {{ editing ? 'Editar categoría' : 'Nueva categoría' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submit">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input v-model="form.name" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Tipo</label>
                            <CustomSelect v-model="form.type" :options="typeOptions" />
                        </div>
                        <div>
                            <label class="form-label">Color</label>
                            <div class="flex items-center gap-3">
                                <input v-model="form.color" type="color" class="w-10 h-10 rounded-lg border border-white/10 bg-transparent cursor-pointer" />
                                <span class="text-sm text-surface-400">{{ form.color }}</span>
                            </div>
                        </div>
                        <p v-if="error" class="form-error">{{ error }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="showForm = false">Cancelar</button>
                            <button class="btn-primary" type="submit" :disabled="mutationPending">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import {
    useFinanceCategories,
    useCreateCategory,
    useUpdateCategory,
    useDeleteCategory,
} from '@/composables/useFinance'

const { data: categoriesData, isLoading } = useFinanceCategories()
const categories = computed(() => categoriesData.value?.data ?? [])

const createCategory = useCreateCategory()
const updateCategory = useUpdateCategory()
const deleteCategoryMutation = useDeleteCategory()

const showForm = ref(false)
const editing = ref(null)
const error = ref('')

const form = reactive({ name: '', type: 'both', color: '#6366f1' })

const typeOptions = [
    { value: 'both', label: 'Ingresos y gastos' },
    { value: 'income', label: 'Solo ingresos' },
    { value: 'expense', label: 'Solo gastos' },
]

function typeLabel(type) {
    if (type === 'income') return 'Ingresos'
    if (type === 'expense') return 'Gastos'
    return 'Ambos'
}

const mutationPending = computed(() =>
    createCategory.isPending.value || updateCategory.isPending.value
)

function openForm(cat = null) {
    error.value = ''
    if (cat) {
        editing.value = cat
        form.name = cat.name
        form.type = cat.type
        form.color = cat.color
    } else {
        editing.value = null
        form.name = ''
        form.type = 'both'
        form.color = '#6366f1'
    }
    showForm.value = true
}

async function submit() {
    error.value = ''
    try {
        if (editing.value) {
            await updateCategory.mutateAsync({ id: editing.value.id, data: { ...form } })
        } else {
            await createCategory.mutateAsync({ ...form })
        }
        showForm.value = false
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function remove(cat) {
    if (!confirm('¿Eliminar esta categoría? Las transacciones quedarán sin etiqueta.')) return
    await deleteCategoryMutation.mutateAsync(cat.id)
}
</script>
