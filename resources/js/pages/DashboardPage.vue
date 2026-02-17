<template>
    <div class="p-8">
        <div class="mb-8">
            <h1 class="page-title">Buenos días, {{ auth.user?.name }} 👋</h1>
            <p class="page-subtitle">{{ formattedDate }}</p>
        </div>

        <div class="space-y-6">
            <template v-for="widget in dashboardWidgets" :key="widget.order">
                <component :is="widget.component" />
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useModuleRegistry } from '@/modules/registry'

const auth = useAuthStore()
const { dashboardWidgets } = useModuleRegistry()

const formattedDate = computed(() => {
    return new Date().toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
})
</script>
