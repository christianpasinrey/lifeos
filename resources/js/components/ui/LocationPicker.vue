<template>
    <div class="space-y-2">
        <div class="relative">
            <input
                ref="searchInput"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                @keydown.enter.prevent="geocode"
                class="form-input pr-9"
                placeholder="Buscar dirección..."
            />
            <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-surface-400 hover:text-surface-200 transition-colors"
                @click="geocode"
                :disabled="geocoding"
            >
                <MagnifyingGlassIcon v-if="!geocoding" class="w-4 h-4" />
                <div v-else class="w-4 h-4 border-2 border-surface-400 border-t-transparent rounded-full animate-spin" />
            </button>
        </div>

        <div ref="mapContainer" class="w-full h-48 rounded-xl overflow-hidden border border-white/[0.08]" />

        <p v-if="geocodeError" class="text-xs text-red-400">{{ geocodeError }}</p>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Fix Leaflet default marker icons for Vite
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
})

const props = defineProps({
    modelValue: { type: String, default: '' },
    latitude: { type: [Number, null], default: null },
    longitude: { type: [Number, null], default: null },
})

const emit = defineEmits(['update:modelValue', 'update:latitude', 'update:longitude'])

const mapContainer = ref(null)
const searchInput = ref(null)
const geocoding = ref(false)
const geocodeError = ref('')

let map = null
let marker = null
let debounceTimer = null

const defaultCenter = [40.4168, -3.7038] // Madrid
const defaultZoom = 13

onMounted(() => {
    nextTick(() => {
        const center = props.latitude && props.longitude
            ? [props.latitude, props.longitude]
            : defaultCenter

        map = L.map(mapContainer.value, {
            center,
            zoom: props.latitude ? 15 : defaultZoom,
            zoomControl: true,
            attributionControl: false,
        })

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map)

        if (props.latitude && props.longitude) {
            placeMarker(props.latitude, props.longitude)
        }

        map.on('click', (e) => {
            placeMarker(e.latlng.lat, e.latlng.lng)
            emit('update:latitude', e.latlng.lat)
            emit('update:longitude', e.latlng.lng)
            reverseGeocode(e.latlng.lat, e.latlng.lng)
        })

        // Invalidate map size after modal transition
        setTimeout(() => map?.invalidateSize(), 300)
    })
})

onBeforeUnmount(() => {
    if (debounceTimer) clearTimeout(debounceTimer)
    if (map) {
        map.remove()
        map = null
    }
})

watch(() => [props.latitude, props.longitude], ([lat, lng]) => {
    if (lat && lng && map) {
        placeMarker(lat, lng)
        map.setView([lat, lng], 15)
    }
})

function placeMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng])
    } else if (map) {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map)
        marker.on('dragend', () => {
            const pos = marker.getLatLng()
            emit('update:latitude', pos.lat)
            emit('update:longitude', pos.lng)
            reverseGeocode(pos.lat, pos.lng)
        })
    }
}

async function geocode() {
    const query = props.modelValue?.trim()
    if (!query || geocoding.value) return

    geocoding.value = true
    geocodeError.value = ''

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`
        const res = await fetch(url, {
            headers: { 'Accept-Language': 'es' },
        })
        const data = await res.json()

        if (data.length > 0) {
            const lat = parseFloat(data[0].lat)
            const lng = parseFloat(data[0].lon)
            emit('update:latitude', lat)
            emit('update:longitude', lng)
            emit('update:modelValue', data[0].display_name)
            placeMarker(lat, lng)
            map?.setView([lat, lng], 16)
        } else {
            geocodeError.value = 'No se encontró la dirección'
        }
    } catch {
        geocodeError.value = 'Error al buscar la dirección'
    } finally {
        geocoding.value = false
    }
}

function reverseGeocode(lat, lng) {
    if (debounceTimer) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(async () => {
        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
            const res = await fetch(url, {
                headers: { 'Accept-Language': 'es' },
            })
            const data = await res.json()
            if (data.display_name) {
                emit('update:modelValue', data.display_name)
            }
        } catch {
            // silent fail for reverse geocode
        }
    }, 1000)
}
</script>
