<template>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 bg-white border-t border-slate-100 select-none">
    <!-- Info Text -->
    <p class="text-xs font-medium text-slate-500">
      <template v-if="safeTotal > 0">
        {{ lang === 'kh' ? 'បង្ហាញ' : 'Showing' }}
        <span class="font-bold text-slate-800">{{ startItem }}</span>
        -
        <span class="font-bold text-slate-800">{{ endItem }}</span>
        {{ lang === 'kh' ? 'នៃ' : 'of' }}
        <span class="font-bold text-slate-800">{{ safeTotal }}</span>
      </template>
      <template v-else>
        {{ lang === 'kh' ? 'គ្មានទិន្នន័យ' : 'No items' }}
      </template>
    </p>

    <!-- Controls -->
    <div v-if="totalPages > 1" class="flex items-center gap-1.5 bg-slate-50 rounded-xl p-1 border border-slate-200/80 shadow-soft-xs">
      <!-- First Page -->
      <button
        type="button"
        :disabled="activePage <= 1"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="First page"
        @click="goToPage(1)"
      >
        <span class="material-symbols-outlined text-base">first_page</span>
      </button>

      <!-- Previous Page -->
      <button
        type="button"
        :disabled="activePage <= 1"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="Previous page"
        @click="goToPage(activePage - 1)"
      >
        <span class="material-symbols-outlined text-base">chevron_left</span>
      </button>

      <!-- Current / Total -->
      <span class="px-2.5 text-xs font-bold text-slate-700">
        {{ activePage }} / {{ totalPages }}
      </span>

      <!-- Next Page -->
      <button
        type="button"
        :disabled="activePage >= totalPages"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="Next page"
        @click="goToPage(activePage + 1)"
      >
        <span class="material-symbols-outlined text-base">chevron_right</span>
      </button>

      <!-- Last Page -->
      <button
        type="button"
        :disabled="activePage >= totalPages"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="Last page"
        @click="goToPage(totalPages)"
      >
        <span class="material-symbols-outlined text-base">last_page</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useLang } from '../../utils/useLang'

const props = defineProps({
  currentPage: { type: [Number, String], default: null },
  modelValue: { type: [Number, String], default: null },
  pageSize: { type: [Number, String], default: 10 },
  totalItems: { type: [Number, String], default: 0 }
})

const emit = defineEmits(['update:currentPage', 'update:modelValue'])

const { lang } = useLang()

const safeTotal = computed(() => {
  const n = parseInt(props.totalItems, 10)
  return isNaN(n) || n < 0 ? 0 : n
})

const safeSize = computed(() => {
  const n = parseInt(props.pageSize, 10)
  return isNaN(n) || n <= 0 ? 10 : n
})

const totalPages = computed(() => Math.max(1, Math.ceil(safeTotal.value / safeSize.value)))

const activePage = computed(() => {
  const raw = props.currentPage !== null && props.currentPage !== undefined ? props.currentPage : props.modelValue
  const n = parseInt(raw, 10)
  if (isNaN(n) || n < 1) return 1
  if (n > totalPages.value) return totalPages.value
  return n
})

const startItem = computed(() => {
  if (safeTotal.value === 0) return 0
  return (activePage.value - 1) * safeSize.value + 1
})

const endItem = computed(() => {
  if (safeTotal.value === 0) return 0
  return Math.min(activePage.value * safeSize.value, safeTotal.value)
})

const goToPage = (page) => {
  const target = Math.max(1, Math.min(totalPages.value, parseInt(page, 10) || 1))
  emit('update:currentPage', target)
  emit('update:modelValue', target)
}
</script>
