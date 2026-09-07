<template>
  <div
    v-if="canInstall && !isDismissed"
    :class="[
      'inline-flex items-center gap-1.5 sm:gap-2.5 px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-full bg-[#002f9f] hover:bg-[#002787] text-white shadow-md border border-blue-400/25 transition-all duration-200 select-none group',
      customClass
    ]"
  >
    <!-- Left Grip Dots (2x3 Grid in Sky Blue) -->
    <div class="flex items-center px-0.5 text-blue-300/90 shrink-0">
      <div class="grid grid-cols-2 gap-0.5 sm:gap-1">
        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-300"></span>
        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-300"></span>
        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-300"></span>
        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-300"></span>
        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-300"></span>
        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-blue-300"></span>
      </div>
    </div>

    <!-- Middle Clickable Install Trigger (Phone + Arrow & Label) -->
    <button
      type="button"
      class="flex items-center gap-1.5 sm:gap-2 font-extrabold text-white cursor-pointer active:scale-95 transition-transform"
      :title="label"
      @click="handleInstallClick"
    >
      <!-- Phone with Download Arrow Icon -->
      <svg
        class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 text-white"
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
          stroke-width="2.2"
        />
        <path
          d="M12 7V14M12 14L9 11M12 14L15 11"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>

      <!-- Label ("ដំឡើង App" / "Install App") -->
      <span class="text-xs sm:text-sm font-extrabold tracking-tight whitespace-nowrap">
        {{ label }}
      </span>
    </button>

    <!-- Right Close / Dismiss Circular Button -->
    <button
      type="button"
      class="h-5 w-5 sm:h-6 sm:w-6 rounded-full bg-[#001e66]/80 hover:bg-[#00174d] active:scale-90 flex items-center justify-center text-white/90 hover:text-white transition-all cursor-pointer shrink-0 ml-0.5"
      title="Close"
      @click.stop="dismiss"
    >
      <span class="material-symbols-outlined text-xs sm:text-sm font-black">close</span>
    </button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePwaInstall } from '../../composables/usePwaInstall'
import { useLang } from '../../utils/useLang'

defineProps({
  variant: {
    type: String,
    default: 'header'
  },
  size: {
    type: String,
    default: 'md'
  },
  customClass: {
    type: String,
    default: ''
  }
})

const { canInstall, installApp } = usePwaInstall()
const { lang } = useLang()

const isDismissed = ref(false)

const label = computed(() => {
  if (lang.value === 'kh') {
    return 'ដំឡើង App'
  }
  return 'Install App'
})

const handleInstallClick = async () => {
  await installApp()
}

const dismiss = () => {
  isDismissed.value = true
}
</script>
