<template>
    <div v-if="badges?.length" class="flex flex-wrap gap-1.5">
        <span
            v-for="badge in badges"
            :key="badge.key"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-white/[0.06] border border-white/[0.08] text-surface-300"
            :title="`Conseguido con racha de ${badge.streak_value} días`"
        >
            {{ milestoneIcon(badge.key) }} {{ milestoneLabel(badge.key) }}
        </span>
    </div>
</template>

<script setup>
defineProps({
    badges: { type: Array, default: () => [] },
})

const milestones = {
    streak_7: { icon: '🔥', label: '7 días' },
    streak_21: { icon: '⭐', label: '21 días' },
    streak_30: { icon: '💪', label: '30 días' },
    streak_60: { icon: '🏆', label: '60 días' },
    streak_100: { icon: '💎', label: '100 días' },
    streak_365: { icon: '👑', label: '1 año' },
}

function milestoneIcon(key) {
    return milestones[key]?.icon ?? '🏅'
}

function milestoneLabel(key) {
    return milestones[key]?.label ?? key
}
</script>
