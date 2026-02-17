<template>
    <CustomSelect
        :modelValue="modelValue"
        @update:modelValue="$emit('update:modelValue', $event)"
        :options="accountOptions"
        :placeholder="placeholder"
    />
</template>

<script setup>
import { computed } from 'vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { useFinanceAccounts } from '@/composables/useFinanceAccounts'

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    placeholder: { type: String, default: 'Seleccionar cuenta...' },
    includeAll: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const { data: accountsData } = useFinanceAccounts()

const accountOptions = computed(() => {
    const accounts = accountsData.value?.data ?? []
    const options = accounts.map(a => ({
        value: String(a.id),
        label: `${a.name} (${formatBalance(a.current_balance)})`,
    }))

    if (props.includeAll) {
        options.unshift({ value: '', label: 'Todas las cuentas' })
    }

    return options
})

function formatBalance(val) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(val ?? 0))
}
</script>
