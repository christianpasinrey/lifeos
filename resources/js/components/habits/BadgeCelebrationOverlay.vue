<template>
    <Teleport to="body">
        <Transition name="badge-celebration">
            <div
                v-if="badge"
                class="fixed inset-0 z-[9999] flex items-center justify-center pointer-events-none"
            >
                <div ref="overlayRef" class="text-center pointer-events-auto">
                    <div class="text-6xl mb-3" ref="iconRef">{{ badge.icon }}</div>
                    <div class="text-lg font-bold text-white mb-1">{{ badge.label }}</div>
                    <div class="text-sm text-surface-300">Nueva insignia desbloqueada</div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import gsap from 'gsap'

const props = defineProps({
    badge: { type: Object, default: null },
})

const overlayRef = ref(null)
const iconRef = ref(null)

watch(() => props.badge, async (badge) => {
    if (!badge) return
    await nextTick()
    if (overlayRef.value) {
        gsap.fromTo(overlayRef.value,
            { scale: 0, opacity: 0 },
            { scale: 1, opacity: 1, duration: 0.4, ease: 'back.out(1.7)' }
        )
    }
    if (iconRef.value) {
        gsap.fromTo(iconRef.value,
            { rotation: -15 },
            { rotation: 15, duration: 0.3, yoyo: true, repeat: 3, ease: 'power1.inOut' }
        )
    }
})
</script>

<style scoped>
.badge-celebration-enter-active { transition: opacity 0.3s; }
.badge-celebration-leave-active { transition: opacity 0.5s; }
.badge-celebration-enter-from,
.badge-celebration-leave-to { opacity: 0; }
</style>
