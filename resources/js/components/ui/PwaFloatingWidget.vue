<template>
  <Teleport to="body">
    <div
      v-if="showWidget"
      ref="widgetRef"
      class="fixed z-[99999] select-none touch-none"
      :style="widgetComputedStyle"
      @mousedown="onMouseDown"
      @touchstart="onTouchStart"
    >
      <!-- Outer Capsule Pill (Deep Blue Unified Background) -->
      <div
        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#082373] hover:bg-[#061d61] border border-blue-400/30 shadow-md shadow-blue-950/60 backdrop-blur-sm transition-all duration-150"
      >
        <!-- ── Left Grip Dots (Smaller, Delicate Drag Handle) ────────── -->
        <div
          class="flex items-center pl-0.5 pr-0.5 text-blue-300 cursor-grab active:cursor-grabbing shrink-0"
          title="អូសប្តូរទីតាំង (Drag to move)"
        >
          <div class="grid grid-cols-2 gap-[3px]">
            <span class="w-[3.5px] h-[3.5px] rounded-full bg-[#60a5fa]"></span>
            <span class="w-[3.5px] h-[3.5px] rounded-full bg-[#60a5fa]"></span>
            <span class="w-[3.5px] h-[3.5px] rounded-full bg-[#60a5fa]"></span>
            <span class="w-[3.5px] h-[3.5px] rounded-full bg-[#60a5fa]"></span>
            <span class="w-[3.5px] h-[3.5px] rounded-full bg-[#60a5fa]"></span>
            <span class="w-[3.5px] h-[3.5px] rounded-full bg-[#60a5fa]"></span>
          </div>
        </div>

        <!-- ── Center Install Trigger (Phone + Text, Transparent Background) ── -->
        <button
          type="button"
          class="flex items-center gap-1.5 px-0.5 py-0.5 text-white font-extrabold text-[11px] sm:text-xs tracking-tight transition-transform active:scale-95 cursor-pointer"
          :title="label"
          @click.stop="handleInstallClick"
        >
          <!-- Phone outline with download arrow icon -->
          <svg
            class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0 text-white"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect
              x="5.5"
              y="2.5"
              width="13"
              height="19"
              rx="2.5"
              stroke="currentColor"
              stroke-width="2.3"
            />
            <path
              d="M12 7V14M12 14L9 11M12 14L15 11"
              stroke="currentColor"
              stroke-width="2.3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>

          <!-- Label -->
          <span class="whitespace-nowrap font-black">{{ label }}</span>
        </button>

        <!-- ── Right Close Button (Delicate Compact X) ──────────── -->
        <button
          type="button"
          class="w-4 h-4 rounded-full hover:bg-white/20 active:scale-90 flex items-center justify-center text-white/80 hover:text-white transition-colors cursor-pointer shrink-0 ml-0.5"
          title="បិទ (Close)"
          @click.stop="dismiss"
        >
          <svg
            class="w-2.5 h-2.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { usePwaInstall } from '../../composables/usePwaInstall'
import { useLang } from '../../utils/useLang'
import { useToast } from '../../composables/useToast'

const route = useRoute()
const { canInstall, installApp } = usePwaInstall()
const { lang } = useLang()
const { info: toastInfo } = useToast()

const widgetRef = ref(null)
const isDismissed = ref(false)
const isDragging = ref(false)
const isUserPositioned = ref(false)

const position = reactive({
  x: 0,
  y: 0
})

let startX = 0
let startY = 0
let initialPosX = 0
let initialPosY = 0
let hasMoved = false

const showWidget = computed(() => {
  // Hide in exam room or if dismissed or if already in standalone app
  const isExam = route?.path?.includes('/exam/')
  return Boolean(canInstall.value && !isDismissed.value && !isExam)
})

const label = computed(() => {
  if (lang.value === 'kh') {
    return 'ដំឡើង App'
  }
  return 'Install App'
})

const widgetComputedStyle = computed(() => {
  if (isUserPositioned.value) {
    return {
      left: `${position.x}px`,
      top: `${position.y}px`,
      cursor: isDragging.value ? 'grabbing' : 'default',
      transition: isDragging.value ? 'none' : 'transform 0.1s ease-out'
    }
  }
  // Default: Bottom-Left anchored
  return {
    bottom: '24px',
    left: '24px',
    cursor: isDragging.value ? 'grabbing' : 'default'
  }
})

const handleInstallClick = async () => {
  if (hasMoved) return
  await installApp()
}

const dismiss = () => {
  isDismissed.value = true
}

// ── Mouse Drag Handling ──────────────────────────────────────────────
const onMouseDown = (e) => {
  if (e.target.closest('button')) return

  const rect = widgetRef.value?.getBoundingClientRect()
  if (!isUserPositioned.value && rect) {
    position.x = rect.left
    position.y = rect.top
    isUserPositioned.value = true
  }

  isDragging.value = true
  hasMoved = false
  startX = e.clientX
  startY = e.clientY
  initialPosX = position.x
  initialPosY = position.y

  window.addEventListener('mousemove', onMouseMove, { passive: false })
  window.addEventListener('mouseup', onMouseUp)
}

const onMouseMove = (e) => {
  if (!isDragging.value) return
  e.preventDefault()

  const dx = e.clientX - startX
  const dy = e.clientY - startY

  if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
    hasMoved = true
  }

  const el = widgetRef.value
  const width = el?.offsetWidth || 230
  const height = el?.offsetHeight || 52
  const maxX = window.innerWidth - width - 8
  const maxY = window.innerHeight - height - 8

  position.x = Math.min(Math.max(8, initialPosX + dx), Math.max(8, maxX))
  position.y = Math.min(Math.max(8, initialPosY + dy), Math.max(8, maxY))
}

const onMouseUp = () => {
  isDragging.value = false
  cleanupListeners()
}

// ── Touch Drag Handling ──────────────────────────────────────────────
const onTouchStart = (e) => {
  const touch = e.touches[0]
  if (!touch) return

  if (e.target.closest('button')) {
    startX = touch.clientX
    startY = touch.clientY
    hasMoved = false
    return
  }

  const rect = widgetRef.value?.getBoundingClientRect()
  if (!isUserPositioned.value && rect) {
    position.x = rect.left
    position.y = rect.top
    isUserPositioned.value = true
  }

  isDragging.value = true
  hasMoved = false
  startX = touch.clientX
  startY = touch.clientY
  initialPosX = position.x
  initialPosY = position.y

  window.addEventListener('touchmove', onTouchMove, { passive: false })
  window.addEventListener('touchend', onTouchEnd)
  window.addEventListener('touchcancel', onTouchEnd)
}

const onTouchMove = (e) => {
  const touch = e.touches[0]
  if (!touch) return

  const dx = touch.clientX - startX
  const dy = touch.clientY - startY

  if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
    hasMoved = true
    isDragging.value = true
  }

  if (isDragging.value) {
    e.preventDefault()
    const el = widgetRef.value
    const width = el?.offsetWidth || 230
    const height = el?.offsetHeight || 52
    const maxX = window.innerWidth - width - 8
    const maxY = window.innerHeight - height - 8

    position.x = Math.min(Math.max(8, initialPosX + dx), Math.max(8, maxX))
    position.y = Math.min(Math.max(8, initialPosY + dy), Math.max(8, maxY))
  }
}

const onTouchEnd = () => {
  isDragging.value = false
  cleanupListeners()
}

const cleanupListeners = () => {
  window.removeEventListener('mousemove', onMouseMove)
  window.removeEventListener('mouseup', onMouseUp)
  window.removeEventListener('touchmove', onTouchMove)
  window.removeEventListener('touchend', onTouchEnd)
  window.removeEventListener('touchcancel', onTouchEnd)
}

const onResize = () => {
  if (isUserPositioned.value && widgetRef.value) {
    const el = widgetRef.value
    const width = el?.offsetWidth || 230
    const height = el?.offsetHeight || 52
    const maxX = window.innerWidth - width - 8
    const maxY = window.innerHeight - height - 8
    position.x = Math.min(Math.max(8, position.x), Math.max(8, maxX))
    position.y = Math.min(Math.max(8, position.y), Math.max(8, maxY))
  }
}

onMounted(() => {
  window.addEventListener('resize', onResize)
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', onResize)
    cleanupListeners()
  }
})
</script>
