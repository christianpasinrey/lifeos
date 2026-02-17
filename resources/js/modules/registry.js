import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const manifests = []

export function registerModule(manifest) {
    manifests.push(manifest)
}

export function useModuleRegistry() {
    const auth = useAuthStore()

    const active = computed(() =>
        manifests.filter(m => !m.module || auth.hasModule(m.module))
    )

    const navItems = computed(() =>
        active.value.flatMap(m => m.navItems ?? []).sort((a, b) => a.order - b.order)
    )

    const dashboardWidgets = computed(() =>
        active.value.flatMap(m => m.dashboardWidgets ?? []).sort((a, b) => a.order - b.order)
    )

    const sidebarWidgets = computed(() =>
        active.value.flatMap(m => m.sidebarWidgets ?? []).sort((a, b) => a.order - b.order)
    )

    function actionsForSlot(slot) {
        return computed(() =>
            active.value.flatMap(m => (m.actions ?? []).filter(a => a.slot === slot))
                .sort((a, b) => a.order - b.order)
        )
    }

    function isActive(moduleSlug) {
        return auth.hasModule(moduleSlug)
    }

    return { navItems, dashboardWidgets, sidebarWidgets, actionsForSlot, isActive }
}
