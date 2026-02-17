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

        <!-- Bank connections -->
        <div class="liquid-glass liquid-glass-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">Conexiones bancarias</h3>
                    <p class="text-sm text-surface-500">Sincroniza tus cuentas ING automáticamente</p>
                </div>
                <button class="btn-add text-sm" @click="showBankForm = true">
                    <PlusIcon class="w-4 h-4" />
                    Conectar banco
                </button>
            </div>

            <div v-if="bankConnections.length === 0" class="text-center py-4 text-surface-500 text-sm">
                No hay conexiones bancarias configuradas
            </div>
            <div v-else class="space-y-3">
                <div
                    v-for="conn in bankConnections"
                    :key="conn.id"
                    class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4"
                >
                    <div>
                        <p class="text-sm font-medium text-white">ING</p>
                        <p class="text-xs text-surface-500">
                            {{ conn.status === 'active' ? 'Conectado' : conn.status === 'pending' ? 'Pendiente' : conn.status }}
                            <template v-if="conn.last_synced_at"> • Último sync: {{ conn.last_synced_at }}</template>
                            • {{ conn.accounts_count }} cuenta(s)
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="conn.status === 'pending'"
                            class="text-xs text-primary-300 hover:text-primary-200 transition"
                            @click="authorizeConnection(conn)"
                        >Autorizar</button>
                        <button
                            v-if="conn.status === 'active'"
                            class="text-xs text-primary-300 hover:text-primary-200 transition"
                            :disabled="syncBank.isPending.value"
                            @click="syncConnection(conn)"
                        >{{ syncBank.isPending.value ? 'Sincronizando...' : 'Sincronizar' }}</button>
                        <button
                            class="text-xs text-danger-400 hover:text-danger-500 transition"
                            @click="deleteConnection(conn)"
                        >Eliminar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank connection form modal -->
        <Teleport to="body">
            <div v-if="showBankForm" class="modal-overlay" @mousedown.self="showBankForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-lg">
                    <h2 class="section-title mb-5">Conectar ING</h2>
                    <form class="form-group" @submit.prevent="submitBankConnection">
                        <div>
                            <label class="form-label">Client ID</label>
                            <input v-model="bankForm.client_id" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Certificado TLS (PEM)</label>
                            <textarea v-model="bankForm.tls_certificate" rows="3" class="form-input font-mono text-xs" required placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Clave TLS (PEM)</label>
                            <textarea v-model="bankForm.tls_key" rows="3" class="form-input font-mono text-xs" required placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Certificado firma (PEM)</label>
                            <textarea v-model="bankForm.signing_certificate" rows="3" class="form-input font-mono text-xs" required placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Clave firma (PEM)</label>
                            <textarea v-model="bankForm.signing_key" rows="3" class="form-input font-mono text-xs" required placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                        </div>
                        <p v-if="bankError" class="form-error">{{ bankError }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="showBankForm = false">Cancelar</button>
                            <button class="btn-primary" type="submit" :disabled="createBank.isPending.value">Conectar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

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
import {
    useBankConnections,
    useCreateBankConnection,
    useDeleteBankConnection,
    useAuthorizeBankConnection,
    useSyncBankConnection,
} from '@/composables/useFinanceBanking'

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

// --- Banking ---
const { data: bankData } = useBankConnections()
const bankConnections = computed(() => bankData.value?.data ?? [])

const createBank = useCreateBankConnection()
const deleteBank = useDeleteBankConnection()
const authorizeBank = useAuthorizeBankConnection()
const syncBank = useSyncBankConnection()

const showBankForm = ref(false)
const bankError = ref('')
const bankForm = reactive({
    client_id: '',
    tls_certificate: '',
    tls_key: '',
    signing_certificate: '',
    signing_key: '',
})

async function submitBankConnection() {
    bankError.value = ''
    try {
        await createBank.mutateAsync({ ...bankForm })
        showBankForm.value = false
        bankForm.client_id = ''
        bankForm.tls_certificate = ''
        bankForm.tls_key = ''
        bankForm.signing_certificate = ''
        bankForm.signing_key = ''
    } catch (e) {
        bankError.value = e.response?.data?.message ?? 'Error al conectar'
    }
}

async function authorizeConnection(conn) {
    try {
        const result = await authorizeBank.mutateAsync(conn.id)
        const url = result.data?.authorization_url
        if (url) window.location.href = url
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al obtener URL de autorización')
    }
}

async function syncConnection(conn) {
    try {
        const result = await syncBank.mutateAsync(conn.id)
        alert(`Sincronizado: ${result.data?.accounts_synced ?? 0} cuentas, ${result.data?.transactions_synced ?? 0} transacciones`)
    } catch (e) {
        alert(e.response?.data?.error ?? e.response?.data?.message ?? 'Error al sincronizar')
    }
}

async function deleteConnection(conn) {
    if (!confirm('¿Eliminar esta conexión bancaria?')) return
    await deleteBank.mutateAsync(conn.id)
}

function formatCurrency(value, currency = 'EUR') {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency, minimumFractionDigits: 2 }).format(Number(value ?? 0))
}
</script>
