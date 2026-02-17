<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-surface-400">Gestiona tus cuentas bancarias, efectivo y otros</p>
            <button class="btn-add" @click="openForm()">
                <PlusIcon class="w-4 h-4" />
                Nueva cuenta
            </button>
        </div>

        <div v-if="isLoading" class="text-center py-10 text-surface-500">Cargando...</div>

        <div v-else-if="accounts.length === 0" class="text-center py-10 text-surface-500">
            No tienes cuentas configuradas
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="account in accounts"
                :key="account.id"
                class="liquid-glass liquid-glass-card p-6 space-y-4"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-10 rounded-full" :style="{ background: account.color }" />
                        <div>
                            <p class="text-lg font-semibold text-white">{{ account.name }}</p>
                            <p class="text-xs text-surface-500 uppercase tracking-wide">{{ typeLabel(account.type) }} • {{ account.currency }}</p>
                        </div>
                    </div>
                    <span
                        v-if="!account.is_active"
                        class="text-xs px-2 py-0.5 rounded bg-surface-700 text-surface-400"
                    >Inactiva</span>
                </div>

                <div>
                    <p class="text-sm text-surface-400">Saldo actual</p>
                    <p
                        class="text-2xl font-semibold"
                        :class="account.current_balance >= 0 ? 'text-white' : 'text-danger-400'"
                    >
                        {{ formatCurrency(account.current_balance, account.currency) }}
                    </p>
                    <p v-if="account.initial_balance != 0" class="text-xs text-surface-500 mt-1">
                        Saldo inicial: {{ formatCurrency(account.initial_balance, account.currency) }}
                    </p>
                </div>

                <div class="flex gap-2 pt-2 border-t border-white/5">
                    <button class="text-xs font-medium text-surface-400 hover:text-white transition" @click="openForm(account)">Editar</button>
                    <button class="text-xs font-medium text-primary-300 hover:text-primary-200 transition" @click="recalculate(account)">Recalcular</button>
                    <button class="text-xs font-medium text-danger-400 hover:text-danger-500 transition" @click="remove(account)">Eliminar</button>
                </div>
            </div>
        </div>

        <!-- Transfer section -->
        <div class="liquid-glass liquid-glass-card p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Transferencia entre cuentas</h3>
            <form class="flex flex-col gap-4 sm:flex-row sm:items-end" @submit.prevent="submitTransfer">
                <div class="flex-1">
                    <label class="form-label">Desde</label>
                    <AccountSelector v-model="transferForm.from_account_id" />
                </div>
                <div class="flex-1">
                    <label class="form-label">Hacia</label>
                    <AccountSelector v-model="transferForm.to_account_id" />
                </div>
                <div class="w-40">
                    <label class="form-label">Importe</label>
                    <input v-model="transferForm.amount" type="number" step="0.01" min="0.01" class="form-input" required />
                </div>
                <button class="btn-primary whitespace-nowrap" type="submit" :disabled="createTransfer.isPending.value">
                    Transferir
                </button>
            </form>
        </div>

        <!-- Account form modal -->
        <Teleport to="body">
            <div v-if="showForm" class="modal-overlay" @mousedown.self="showForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-md">
                    <h2 class="section-title mb-5">
                        {{ editing ? 'Editar cuenta' : 'Nueva cuenta' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submitAccount">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input v-model="form.name" type="text" class="form-input" required />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="form.type" :options="typeOptions" />
                            </div>
                            <div>
                                <label class="form-label">Moneda</label>
                                <input v-model="form.currency" type="text" class="form-input" maxlength="3" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Saldo inicial</label>
                                <input v-model="form.initial_balance" type="number" step="0.01" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Color</label>
                                <div class="flex items-center gap-3">
                                    <input v-model="form.color" type="color" class="w-10 h-10 rounded-lg border border-white/10 bg-transparent cursor-pointer" />
                                    <span class="text-sm text-surface-400">{{ form.color }}</span>
                                </div>
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
import AccountSelector from './components/AccountSelector.vue'
import {
    useFinanceAccounts,
    useCreateAccount,
    useUpdateAccount,
    useDeleteAccount,
    useRecalculateAccount,
    useCreateTransfer,
} from '@/composables/useFinanceAccounts'

const { data: accountsData, isLoading } = useFinanceAccounts(false)
const accounts = computed(() => accountsData.value?.data ?? [])

const createAccount = useCreateAccount()
const updateAccount = useUpdateAccount()
const deleteAccount = useDeleteAccount()
const recalculateAccount = useRecalculateAccount()
const createTransfer = useCreateTransfer()

const showForm = ref(false)
const editing = ref(null)
const error = ref('')

const form = reactive({
    name: '',
    type: 'bank',
    currency: 'EUR',
    initial_balance: 0,
    color: '#6366f1',
})

const transferForm = reactive({
    from_account_id: '',
    to_account_id: '',
    amount: '',
})

const typeOptions = [
    { value: 'bank', label: 'Banco' },
    { value: 'cash', label: 'Efectivo' },
    { value: 'credit_card', label: 'Tarjeta de crédito' },
    { value: 'savings', label: 'Ahorro' },
    { value: 'investment', label: 'Inversión' },
    { value: 'other', label: 'Otro' },
]

const typeLabels = { bank: 'Banco', cash: 'Efectivo', credit_card: 'Tarjeta', savings: 'Ahorro', investment: 'Inversión', other: 'Otro' }
function typeLabel(type) { return typeLabels[type] || type }

const mutationPending = computed(() =>
    createAccount.isPending.value || updateAccount.isPending.value
)

function openForm(account = null) {
    error.value = ''
    if (account) {
        editing.value = account
        form.name = account.name
        form.type = account.type
        form.currency = account.currency
        form.initial_balance = account.initial_balance
        form.color = account.color
    } else {
        editing.value = null
        form.name = ''
        form.type = 'bank'
        form.currency = 'EUR'
        form.initial_balance = 0
        form.color = '#6366f1'
    }
    showForm.value = true
}

async function submitAccount() {
    error.value = ''
    try {
        if (editing.value) {
            await updateAccount.mutateAsync({ id: editing.value.id, data: { ...form } })
        } else {
            await createAccount.mutateAsync({ ...form })
        }
        showForm.value = false
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function remove(account) {
    if (!confirm(`¿Eliminar la cuenta "${account.name}"? Las transacciones se desvincularan.`)) return
    await deleteAccount.mutateAsync(account.id)
}

async function recalculate(account) {
    await recalculateAccount.mutateAsync(account.id)
}

async function submitTransfer() {
    try {
        await createTransfer.mutateAsync({
            from_account_id: Number(transferForm.from_account_id),
            to_account_id: Number(transferForm.to_account_id),
            amount: Number(transferForm.amount),
        })
        transferForm.from_account_id = ''
        transferForm.to_account_id = ''
        transferForm.amount = ''
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error en la transferencia')
    }
}

function formatCurrency(value, currency = 'EUR') {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency, minimumFractionDigits: 2 }).format(Number(value ?? 0))
}
</script>
