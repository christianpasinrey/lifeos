import { ref } from 'vue'
import gsap from 'gsap'

const pendingBadge = ref(null)

export function useCelebration() {
    function celebrateHabit(element) {
        if (!element) return

        // Scale pulse
        gsap.fromTo(element, { scale: 1 }, {
            scale: 1.15,
            duration: 0.15,
            yoyo: true,
            repeat: 1,
            ease: 'power2.out',
        })

        // Particle burst
        const rect = element.getBoundingClientRect()
        const centerX = rect.left + rect.width / 2
        const centerY = rect.top + rect.height / 2
        const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#22c55e', '#eab308', '#06b6d4', '#f97316', '#f43f5e']

        for (let i = 0; i < 8; i++) {
            const particle = document.createElement('div')
            particle.style.cssText = `
                position: fixed; z-index: 9999; pointer-events: none;
                width: 6px; height: 6px; border-radius: 50%;
                background: ${colors[i]}; left: ${centerX}px; top: ${centerY}px;
            `
            document.body.appendChild(particle)

            const angle = (i / 8) * Math.PI * 2
            const distance = 30 + Math.random() * 20

            gsap.to(particle, {
                x: Math.cos(angle) * distance,
                y: Math.sin(angle) * distance,
                opacity: 0,
                duration: 0.6,
                ease: 'power2.out',
                onComplete: () => particle.remove(),
            })
        }
    }

    function celebrateBadge(badge) {
        pendingBadge.value = badge
        setTimeout(() => {
            pendingBadge.value = null
        }, 3000)
    }

    return { celebrateHabit, celebrateBadge, pendingBadge }
}
