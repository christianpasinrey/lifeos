import { ref } from 'vue'

/**
 * Native HTML5 drag-and-drop composable.
 * Zero dependencies.
 */
export function useDrag() {
    const dragging = ref(null)
    const dragOverId = ref(null)

    function onDragStart(e, payload) {
        dragging.value = payload
        e.dataTransfer.effectAllowed = 'move'
        e.dataTransfer.setData('text/plain', '')
        requestAnimationFrame(() => {
            e.target.classList.add('is-dragging')
        })
    }

    function onDragEnd(e) {
        e.target.classList.remove('is-dragging')
        dragging.value = null
        dragOverId.value = null
    }

    function onDragOver(e, id) {
        e.preventDefault()
        e.dataTransfer.dropEffect = 'move'
        dragOverId.value = id
    }

    function onDragLeave(e, id) {
        if (dragOverId.value === id) {
            dragOverId.value = null
        }
    }

    function onDrop(e, callback) {
        e.preventDefault()
        dragOverId.value = null
        if (dragging.value && callback) {
            callback(dragging.value)
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
