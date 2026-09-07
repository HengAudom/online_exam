<template>
  <button
    type="button"
    @click="toggleLang"
    :class="[
      'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition-all duration-150 cursor-pointer select-none focus-ring shadow-soft-xs active:scale-95 shrink-0',
      variant === 'dark'
        ? 'bg-slate-800 text-white hover:bg-slate-700 border border-slate-700'
        : variant === 'light'
        ? 'bg-white text-slate-800 hover:bg-slate-50 border border-slate-200'
        : 'bg-white/15 text-white hover:bg-white/25 border border-white/20 backdrop-blur-xs'
    ]"
    :title="lang === 'kh' ? 'ប្ដូរទៅជាភាសាអង់គ្លេស / Switch to English' : 'Switch to Khmer / ប្ដូរទៅជាភាសាខ្មែរ'"
  >
    <!-- Khmer Flag -->
    <template v-if="lang === 'kh'">
      <svg class="w-4 h-3 rounded-[2px] overflow-hidden shrink-0 border border-white/20" viewBox="0 0 900 600">
        <rect width="900" height="600" fill="#002B7F"/>
        <rect y="150" width="900" height="300" fill="#CE1126"/>
        <path fill="#ffffff" d="M450,200 L415,270 L485,270 Z M395,270 L505,270 L505,330 L395,330 Z M370,330 L525,330 L525,370 L375,370 Z"/>
      </svg>
      <span>ភាសាខ្មែរ</span>
    </template>

    <!-- UK Flag -->
    <template v-else>
      <svg class="w-4 h-3 rounded-[2px] overflow-hidden shrink-0 border border-white/20" viewBox="0 0 600 400">
        <rect width="600" height="400" fill="#00247D"/>
        <path d="M0,0 L600,400 M600,0 L0,400" stroke="#ffffff" stroke-width="60"/>
        <path d="M0,0 L600,400 M600,0 L0,400" stroke="#CF142B" stroke-width="35"/>
        <path d="M300,0 V400 M0,200 H600" stroke="#ffffff" stroke-width="100"/>
        <path d="M300,0 V400 M0,200 H600" stroke="#CF142B" stroke-width="60"/>
      </svg>
      <span>English</span>
    </template>
  </button>
</template>

<script setup>
import { useLang } from '../utils/useLang'

defineProps({
  variant: {
    type: String,
    default: 'light', // 'light' | 'dark' | 'glass'
    validator: (v) => ['light', 'dark', 'glass'].includes(v)
  }
})

const { lang, toggleLang } = useLang()
</script>
