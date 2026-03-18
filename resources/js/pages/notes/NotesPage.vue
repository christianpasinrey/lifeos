<template>
    <div class="notes-page">
        <!-- Left sidebar -->
        <aside class="notes-sidebar notes-sidebar-left" :class="{ collapsed: leftCollapsed }">
            <div class="notes-sidebar-tabs">
                <button
                    v-for="tab in leftTabs"
                    :key="tab.id"
                    class="notes-tab-btn"
                    :class="{ active: activeLeftTab === tab.id }"
                    @click="activeLeftTab = tab.id"
                    :title="tab.label"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                </button>
                <button class="notes-tab-btn ml-auto" @click="leftCollapsed = !leftCollapsed" title="Colapsar">
                    <ChevronDoubleLeftIcon class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': leftCollapsed }" />
                </button>
            </div>
            <div v-if="!leftCollapsed" class="notes-sidebar-content glass-scroll">
                <FileExplorer v-if="activeLeftTab === 'explorer'" @select-note="navigateToNote" @create-note="handleCreateNote" />
                <SearchPanel v-else-if="activeLeftTab === 'search'" @select-note="navigateToNote" />
                <BookmarksPanel v-else-if="activeLeftTab === 'bookmarks'" @select-note="navigateToNote" />
            </div>
        </aside>

        <!-- Center -->
        <main class="notes-main">
            <!-- Toolbar -->
            <div class="notes-toolbar">
                <div class="flex items-center gap-1">
                    <button class="btn-icon" :disabled="!canGoBack" @click="goBack" title="Atrás">
                        <ArrowLeftIcon class="w-4 h-4" />
                    </button>
                    <button class="btn-icon" :disabled="!canGoForward" @click="goForward" title="Adelante">
                        <ArrowRightIcon class="w-4 h-4" />
                    </button>
                    <NotesBreadcrumb v-if="currentNote" :note="currentNote" />
                </div>
                <div class="flex items-center gap-1">
                    <button class="btn-icon" @click="openDailyNote" title="Nota diaria">
                        <CalendarDaysIcon class="w-4 h-4" />
                    </button>
                    <router-link :to="{ name: 'notes-graph' }" class="btn-icon" title="Graph view">
                        <ShareIcon class="w-4 h-4" />
                    </router-link>
                    <button class="btn-icon" @click="openQuickSwitcher" title="Buscar nota (Ctrl+K)">
                        <MagnifyingGlassIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <router-view
                :key="$route.fullPath"
                @note-loaded="onNoteLoaded"
            />
        </main>

        <!-- Right sidebar -->
        <aside class="notes-sidebar notes-sidebar-right" :class="{ collapsed: rightCollapsed }">
            <div class="notes-sidebar-tabs">
                <button class="notes-tab-btn" @click="rightCollapsed = !rightCollapsed" title="Colapsar">
                    <ChevronDoubleRightIcon class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': rightCollapsed }" />
                </button>
                <button
                    v-for="tab in rightTabs"
                    :key="tab.id"
                    class="notes-tab-btn"
                    :class="{ active: activeRightTab === tab.id }"
                    @click="activeRightTab = tab.id"
                    :title="tab.label"
                >
                    <component :is="tab.icon" class="w-4 h-4" />
                </button>
            </div>
            <div v-if="!rightCollapsed" class="notes-sidebar-content glass-scroll">
                <BacklinksPanel v-if="activeRightTab === 'backlinks'" :note="currentNote" @select-note="navigateToNote" />
                <OutlinePanel v-else-if="activeRightTab === 'outline'" :content="currentNote?.content" />
                <TagsPanel v-else-if="activeRightTab === 'tags'" @select-tag="filterByTag" />
            </div>
        </aside>

        <!-- Quick Switcher -->
        <QuickSwitcher v-if="showQuickSwitcher" @close="showQuickSwitcher = false" @select="navigateToNote" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, provide } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNoteNavigation } from '@/composables/useNoteNavigation'
import { useDailyNote } from '@/composables/useNotes'
import FileExplorer from '@/components/notes/sidebar/FileExplorer.vue'
import SearchPanel from '@/components/notes/sidebar/SearchPanel.vue'
import BookmarksPanel from '@/components/notes/sidebar/BookmarksPanel.vue'
import BacklinksPanel from '@/components/notes/sidebar/BacklinksPanel.vue'
import OutlinePanel from '@/components/notes/sidebar/OutlinePanel.vue'
import TagsPanel from '@/components/notes/sidebar/TagsPanel.vue'
import NotesBreadcrumb from '@/components/notes/NotesBreadcrumb.vue'
import QuickSwitcher from '@/components/notes/QuickSwitcher.vue'
import {
    FolderIcon, MagnifyingGlassIcon, StarIcon, LinkIcon,
    ListBulletIcon, TagIcon, ArrowLeftIcon, ArrowRightIcon,
    CalendarDaysIcon, ShareIcon, ChevronDoubleLeftIcon, ChevronDoubleRightIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const { push, goBack, goForward, canGoBack, canGoForward } = useNoteNavigation()
const dailyNote = useDailyNote()

const leftCollapsed = ref(localStorage.getItem('notes-left-collapsed') === 'true')
const rightCollapsed = ref(localStorage.getItem('notes-right-collapsed') === 'true')
const activeLeftTab = ref('explorer')
const activeRightTab = ref('backlinks')
const currentNote = ref(null)
const showQuickSwitcher = ref(false)

provide('currentNote', currentNote)

const leftTabs = [
    { id: 'explorer', label: 'Explorador', icon: FolderIcon },
    { id: 'search', label: 'Buscar', icon: MagnifyingGlassIcon },
    { id: 'bookmarks', label: 'Favoritos', icon: StarIcon },
]

const rightTabs = [
    { id: 'backlinks', label: 'Backlinks', icon: LinkIcon },
    { id: 'outline', label: 'Esquema', icon: ListBulletIcon },
    { id: 'tags', label: 'Tags', icon: TagIcon },
]

// Persist sidebar state
function watchCollapsed(key, ref) {
    return () => localStorage.setItem(key, ref.value)
}

function onNoteLoaded(note) {
    currentNote.value = note
    if (note?.slug) push(note.slug)
}

function navigateToNote(noteOrSlug) {
    const slug = typeof noteOrSlug === 'string' ? noteOrSlug : noteOrSlug.slug
    router.push({ name: 'note', params: { slug } })
}

function handleCreateNote(folderId) {
    // Will be handled by editor view
    router.push({ name: 'notes-home', query: { new: 1, folder: folderId || '' } })
}

async function openDailyNote() {
    const result = await dailyNote.mutateAsync()
    navigateToNote(result.data)
}

function openQuickSwitcher() {
    showQuickSwitcher.value = true
}

function filterByTag(tag) {
    activeLeftTab.value = 'search'
    // Search panel will handle tag: prefix
}

function onKeydown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault()
        showQuickSwitcher.value = !showQuickSwitcher.value
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>
