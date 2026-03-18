<template>
    <div class="properties-panel">
        <button class="flex items-center gap-1.5 text-xs text-surface-500 hover:text-surface-300 transition-colors mb-2" @click="expanded = !expanded">
            <ChevronRightIcon class="w-3 h-3 transition-transform" :class="{ 'rotate-90': expanded }" />
            Propiedades
        </button>

        <div v-if="expanded" class="space-y-2 pb-3 border-b border-white/[0.04] mb-3">
            <!-- Aliases -->
            <div>
                <label class="form-label">Aliases</label>
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="(alias, i) in localProps.aliases ?? []"
                        :key="i"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/5 text-xs text-surface-300"
                    >
                        {{ alias }}
                        <button @click="removeAlias(i)" class="text-surface-600 hover:text-danger-400">&times;</button>
                    </span>
                    <input
                        v-model="newAlias"
                        class="bg-transparent border-none outline-none text-xs text-surface-300 w-20"
                        placeholder="+ alias"
                        @keydown.enter.prevent="addAlias"
                    />
                </div>
            </div>

            <!-- Template toggle -->
            <div class="flex items-center justify-between">
                <label class="form-label mb-0">Template</label>
                <button
                    @click="toggleTemplate"
                    class="relative w-8 h-5 rounded-full transition-colors"
                    :class="localProps.template ? 'bg-primary-500' : 'bg-surface-600'"
                >
                    <span
                        class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform"
                        :class="localProps.template ? 'translate-x-3' : ''"
                    />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    properties: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update'])

const expanded = ref(false)
const localProps = ref({ ...props.properties })
const newAlias = ref('')

watch(() => props.properties, (p) => {
    localProps.value = { ...p }
}, { deep: true })

function addAlias() {
    if (!newAlias.value.trim()) return
    const aliases = localProps.value.aliases ?? []
    aliases.push(newAlias.value.trim())
    localProps.value = { ...localProps.value, aliases }
    newAlias.value = ''
    emit('update', localProps.value)
}

function removeAlias(index) {
    const aliases = [...(localProps.value.aliases ?? [])]
    aliases.splice(index, 1)
    localProps.value = { ...localProps.value, aliases }
    emit('update', localProps.value)
}

function toggleTemplate() {
    localProps.value = { ...localProps.value, template: !localProps.value.template }
    emit('update', localProps.value)
}
</script>
