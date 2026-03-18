import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const history = ref([])
const currentIndex = ref(-1)

export function useNoteNavigation() {
    const router = useRouter()

    function push(slug) {
        // If we're not at the end of history, truncate forward entries
        if (currentIndex.value < history.value.length - 1) {
            history.value = history.value.slice(0, currentIndex.value + 1)
        }
        history.value.push(slug)
        currentIndex.value = history.value.length - 1
    }

    function goBack() {
        if (!canGoBack.value) return
        currentIndex.value--
        const slug = history.value[currentIndex.value]
        router.push({ name: 'note', params: { slug } })
    }

    function goForward() {
        if (!canGoForward.value) return
        currentIndex.value++
        const slug = history.value[currentIndex.value]
        router.push({ name: 'note', params: { slug } })
    }

    const canGoBack = computed(() => currentIndex.value > 0)
    const canGoForward = computed(() => currentIndex.value < history.value.length - 1)

    return { push, goBack, goForward, canGoBack, canGoForward, history, currentIndex }
}
