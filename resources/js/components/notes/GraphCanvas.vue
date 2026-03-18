<template>
    <div ref="container" class="graph-canvas-container">
        <canvas ref="canvas" @wheel="onWheel" @mousedown="onMouseDown" @mousemove="onMouseMove" @mouseup="onMouseUp" @click="onClick" />
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    edges: { type: Array, default: () => [] },
})

const emit = defineEmits(['select-note'])

const container = ref(null)
const canvas = ref(null)

// Transform state
let transform = { x: 0, y: 0, scale: 1 }
let dragging = false
let dragStart = { x: 0, y: 0 }
let dragNode = null
let hoveredNode = null
let animFrame = null

// Simulation state
let simNodes = []
let simRunning = false
let simIterations = 0

const COLORS = ['#6366f1', '#8b5cf6', '#ec4899', '#14b8a6', '#06b6d4', '#0ea5e9', '#f59e0b', '#10b981', '#f97316', '#ef4444', '#84cc16', '#a855f7']

function colorForFolder(folderId) {
    if (!folderId) return '#64748b'
    return COLORS[folderId % COLORS.length]
}

function initSimulation() {
    const cx = canvas.value.width / 2
    const cy = canvas.value.height / 2

    simNodes = props.nodes.map((n, i) => ({
        ...n,
        x: cx + (Math.random() - 0.5) * 300,
        y: cy + (Math.random() - 0.5) * 300,
        vx: 0,
        vy: 0,
        radius: Math.min(4 + (getConnections(n.id) * 1.5), 14),
        color: colorForFolder(n.folder_id),
    }))

    simRunning = true
    simIterations = 0
    transform = { x: 0, y: 0, scale: 1 }
}

function getConnections(nodeId) {
    return props.edges.filter(e => e.source === nodeId || e.target === nodeId).length
}

function simulate() {
    if (!simRunning || simIterations > 300) {
        simRunning = false
        return
    }
    simIterations++

    const alpha = Math.max(0.01, 1 - simIterations / 300)
    const cx = canvas.value.width / 2
    const cy = canvas.value.height / 2

    // Repulsion between all nodes
    for (let i = 0; i < simNodes.length; i++) {
        for (let j = i + 1; j < simNodes.length; j++) {
            const a = simNodes[i], b = simNodes[j]
            let dx = b.x - a.x, dy = b.y - a.y
            let dist = Math.sqrt(dx * dx + dy * dy) || 1
            const force = Math.min(500 / (dist * dist), 5) * alpha

            const fx = (dx / dist) * force
            const fy = (dy / dist) * force
            a.vx -= fx; a.vy -= fy
            b.vx += fx; b.vy += fy
        }
    }

    // Attraction between linked nodes
    const nodeMap = new Map(simNodes.map(n => [n.id, n]))
    for (const edge of props.edges) {
        const a = nodeMap.get(edge.source), b = nodeMap.get(edge.target)
        if (!a || !b) continue
        let dx = b.x - a.x, dy = b.y - a.y
        let dist = Math.sqrt(dx * dx + dy * dy) || 1
        const force = (dist - 80) * 0.005 * alpha

        const fx = (dx / dist) * force
        const fy = (dy / dist) * force
        a.vx += fx; a.vy += fy
        b.vx -= fx; b.vy -= fy
    }

    // Central gravity
    for (const node of simNodes) {
        node.vx += (cx - node.x) * 0.001 * alpha
        node.vy += (cy - node.y) * 0.001 * alpha
    }

    // Apply velocity with damping
    for (const node of simNodes) {
        if (node === dragNode) continue
        node.vx *= 0.85
        node.vy *= 0.85
        node.x += node.vx
        node.y += node.vy
    }
}

function render() {
    const ctx = canvas.value?.getContext('2d')
    if (!ctx) return

    const w = canvas.value.width
    const h = canvas.value.height

    ctx.clearRect(0, 0, w, h)
    ctx.save()
    ctx.translate(transform.x, transform.y)
    ctx.scale(transform.scale, transform.scale)

    const nodeMap = new Map(simNodes.map(n => [n.id, n]))

    // Draw edges
    ctx.lineWidth = 0.5
    for (const edge of props.edges) {
        const a = nodeMap.get(edge.source), b = nodeMap.get(edge.target)
        if (!a || !b) continue
        ctx.strokeStyle = hoveredNode && (hoveredNode.id === a.id || hoveredNode.id === b.id)
            ? 'rgba(99, 102, 241, 0.6)'
            : 'rgba(255, 255, 255, 0.08)'
        ctx.lineWidth = hoveredNode && (hoveredNode.id === a.id || hoveredNode.id === b.id) ? 1.5 : 0.5
        ctx.beginPath()
        ctx.moveTo(a.x, a.y)
        ctx.lineTo(b.x, b.y)
        ctx.stroke()
    }

    // Draw nodes
    for (const node of simNodes) {
        const isHovered = hoveredNode === node
        ctx.beginPath()
        ctx.arc(node.x, node.y, node.radius * (isHovered ? 1.3 : 1), 0, Math.PI * 2)
        ctx.fillStyle = isHovered ? '#fff' : node.color
        ctx.globalAlpha = isHovered ? 1 : 0.8
        ctx.fill()
        ctx.globalAlpha = 1

        // Label
        if (transform.scale > 0.4 || isHovered) {
            ctx.font = `${isHovered ? 11 : 9}px system-ui`
            ctx.fillStyle = isHovered ? '#fff' : 'rgba(255,255,255,0.5)'
            ctx.textAlign = 'center'
            ctx.fillText(node.title, node.x, node.y + node.radius + 12)
        }
    }

    ctx.restore()

    simulate()
    animFrame = requestAnimationFrame(render)
}

function resizeCanvas() {
    if (!canvas.value || !container.value) return
    canvas.value.width = container.value.clientWidth
    canvas.value.height = container.value.clientHeight
}

function nodeAt(mx, my) {
    const x = (mx - transform.x) / transform.scale
    const y = (my - transform.y) / transform.scale
    for (let i = simNodes.length - 1; i >= 0; i--) {
        const n = simNodes[i]
        const dx = x - n.x, dy = y - n.y
        if (dx * dx + dy * dy < (n.radius + 4) ** 2) return n
    }
    return null
}

function onWheel(e) {
    e.preventDefault()
    const factor = e.deltaY > 0 ? 0.9 : 1.1
    transform.scale *= factor
    transform.scale = Math.max(0.1, Math.min(5, transform.scale))
}

function onMouseDown(e) {
    const rect = canvas.value.getBoundingClientRect()
    const mx = e.clientX - rect.left, my = e.clientY - rect.top
    dragNode = nodeAt(mx, my)
    dragging = true
    dragStart = { x: e.clientX, y: e.clientY }
}

function onMouseMove(e) {
    const rect = canvas.value.getBoundingClientRect()
    const mx = e.clientX - rect.left, my = e.clientY - rect.top

    if (dragging && dragNode) {
        dragNode.x = (mx - transform.x) / transform.scale
        dragNode.y = (my - transform.y) / transform.scale
    } else if (dragging) {
        transform.x += e.clientX - dragStart.x
        transform.y += e.clientY - dragStart.y
        dragStart = { x: e.clientX, y: e.clientY }
    } else {
        hoveredNode = nodeAt(mx, my)
        canvas.value.style.cursor = hoveredNode ? 'pointer' : 'grab'
    }
}

function onMouseUp() {
    dragging = false
    dragNode = null
}

function onClick(e) {
    const rect = canvas.value.getBoundingClientRect()
    const node = nodeAt(e.clientX - rect.left, e.clientY - rect.top)
    if (node) emit('select-note', node)
}

watch(() => [props.nodes, props.edges], () => {
    if (props.nodes.length) {
        nextTick(() => {
            resizeCanvas()
            initSimulation()
        })
    }
}, { deep: true })

let resizeObs = null
onMounted(() => {
    resizeCanvas()
    if (props.nodes.length) initSimulation()
    animFrame = requestAnimationFrame(render)

    resizeObs = new ResizeObserver(resizeCanvas)
    resizeObs.observe(container.value)
})

onUnmounted(() => {
    cancelAnimationFrame(animFrame)
    resizeObs?.disconnect()
})
</script>
