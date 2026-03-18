import { ref, watch } from 'vue'

export function useInlineEdit(initialValue, onSave) {
    const editing = ref(false)
    const localValue = ref(initialValue)
    const saving = ref(false)
    const error = ref(null)

    watch(() => initialValue, (v) => {
        if (!editing.value) localValue.value = v
    })

    function startEdit() {
        editing.value = true
        error.value = null
    }

    async function save() {
        if (localValue.value === initialValue) {
            editing.value = false
            return
        }
        saving.value = true
        error.value = null
        try {
            await onSave(localValue.value)
            editing.value = false
        } catch (e) {
            error.value = e.message || 'Error al guardar'
            localValue.value = initialValue
        } finally {
            saving.value = false
        }
    }

    function cancel() {
        localValue.value = initialValue
        editing.value = false
        error.value = null
    }

    return { editing, localValue, saving, error, startEdit, save, cancel }
}
