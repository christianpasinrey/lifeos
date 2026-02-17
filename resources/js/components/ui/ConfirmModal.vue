<template>
    <Teleport to="body">
        <div class="modal-overlay" style="z-index: 9999;">
            <div class="modal-backdrop" @click="$emit('cancel')" />
            <div class="modal-content liquid-glass liquid-glass-panel" style="--glass-radius: 16px; max-width: 380px;">
                <h3 class="text-base font-semibold text-surface-100 mb-2">{{ title }}</h3>
                <p class="text-sm text-surface-400 mb-5">{{ message }}</p>
                <div class="flex justify-end gap-3">
                    <button @click="$emit('cancel')" class="btn-secondary text-sm">
                        {{ cancelText }}
                    </button>
                    <button
                        @click="$emit('confirm')"
                        class="text-sm px-4 py-2 rounded-xl font-medium transition"
                        :class="danger
                            ? 'bg-danger-500/20 text-danger-300 hover:bg-danger-500/30'
                            : 'btn-primary'"
                    >
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Confirmar' },
    message: { type: String, default: '¿Estás seguro?' },
    confirmText: { type: String, default: 'Confirmar' },
    cancelText: { type: String, default: 'Cancelar' },
    danger: { type: Boolean, default: false },
})

defineEmits(['confirm', 'cancel'])
</script>
