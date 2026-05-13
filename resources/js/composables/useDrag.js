import { ref } from 'vue'
import { animate } from 'animejs'

/**
 * Native HTML5 drag-and-drop with anime.js polish.
 *
 * Animation phases:
 * - dragstart  → pickup lift  (scale + shadow + tilt for columns)
 * - dragend    → release      (spring-back when no drop happened)
 * - drop       → impact flash (target glow pulse + dropped card flash by data id)
 */
export function useDrag() {
    const dragging = ref(null)
    const dragOverId = ref(null)

    function onDragStart(e, payload) {
        dragging.value = payload
        e.dataTransfer.effectAllowed = 'move'
        e.dataTransfer.setData('text/plain', '')

        const target = e.currentTarget ?? e.target
        target.classList.add('is-dragging')

        // Pickup: lift, slight tilt (more for columns), elevate shadow.
        animate(target, {
            scale: payload.type === 'column' ? 1.02 : 1.04,
            rotate: payload.type === 'column' ? -1.5 : -0.8,
            duration: 220,
            ease: 'outQuart',
        })
    }

    function onDragEnd(e) {
        const target = e.currentTarget ?? e.target

        // Spring back. Works for both successful drops and aborts —
        // a successful drop unmounts the source on re-render anyway.
        animate(target, {
            scale: 1,
            rotate: 0,
            duration: 320,
            ease: 'outElastic(1, 0.6)',
        })

        setTimeout(() => target.classList.remove('is-dragging'), 60)
        dragging.value = null
        dragOverId.value = null
    }

    function onDragOver(e, id) {
        e.preventDefault()
        e.dataTransfer.dropEffect = 'move'
        if (dragOverId.value !== id) {
            dragOverId.value = id
        }
    }

    function onDragLeave(e, id) {
        if (dragOverId.value === id) {
            dragOverId.value = null
        }
    }

    function onDrop(e, callback) {
        e.preventDefault()
        const payload = dragging.value
        const targetEl = e.currentTarget ?? e.target
        dragOverId.value = null

        if (payload && callback) {
            callback(payload)

            // Impact pulse on the column / task that received the drop.
            animate(targetEl, {
                scale: [{ to: 1.015, duration: 110, ease: 'outQuad' }, { to: 1, duration: 220, ease: 'outElastic(1, 0.7)' }],
            })

            // After Vue re-renders, flash the dropped item by data-id.
            if (payload.type === 'task') {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const card = document.querySelector(`[data-task-id="${payload.id}"]`)
                        if (card) flashIn(card)
                    })
                })
            } else if (payload.type === 'column') {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const col = document.querySelector(`[data-column-id="${payload.id}"]`)
                        if (col) flashIn(col)
                    })
                })
            }
        }

        dragging.value = null
    }

    return {
        dragging,
        dragOverId,
        onDragStart,
        onDragEnd,
        onDragOver,
        onDragLeave,
        onDrop,
    }
}

/**
 * Subtle "I just landed here" entrance — scale from 0.92 with a primary-tinted
 * shadow that decays back to baseline.
 */
function flashIn(el) {
    el.style.willChange = 'transform, box-shadow'
    animate(el, {
        scale: [{ from: 0.92, to: 1.03, duration: 220, ease: 'outQuart' }, { to: 1, duration: 280, ease: 'outElastic(1, 0.6)' }],
        boxShadow: [
            { from: '0 0 0 0 rgba(99, 102, 241, 0)', to: '0 8px 28px 2px rgba(99, 102, 241, 0.45)', duration: 220 },
            { to: '0 0 0 0 rgba(99, 102, 241, 0)', duration: 480, ease: 'outQuad' },
        ],
        onComplete: () => { el.style.willChange = '' },
    })
}
