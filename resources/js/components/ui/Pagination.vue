<template>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 bg-white border-t border-slate-100 select-none">
    <!-- Info Text -->
    <p class="text-xs font-medium text-slate-500">
      <template v-if="totalItems > 0">
        {{ lang === 'kh' ? 'បង្ហាញ' : 'Showing' }}
        <span class="font-bold text-slate-800">{{ startItem }}</span>
        -
        <span class="font-bold text-slate-800">{{ endItem }}</span>
        {{ lang === 'kh' ? 'នៃ' : 'of' }}
        <span class="font-bold text-slate-800">{{ totalItems }}</span>
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
        :disabled="currentPage === 1"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="First page"
        @click="$emit('update:currentPage', 1)"
      >
        <span class="material-symbols-outlined text-base">first_page</span>
      </button>

      <!-- Previous Page -->
      <button
        type="button"
        :disabled="currentPage === 1"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="Previous page"
        @click="$emit('update:currentPage', currentPage - 1)"
      >
        <span class="material-symbols-outlined text-base">chevron_left</span>
      </button>

      <!-- Current / Total -->
      <span class="px-2.5 text-xs font-bold text-slate-700">
        {{ currentPage }} / {{ totalPages }}
      </span>

      <!-- Next Page -->
      <button
        type="button"
        :disabled="currentPage === totalPages"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="Next page"
        @click="$emit('update:currentPage', currentPage + 1)"
      >
        <span class="material-symbols-outlined text-base">chevron_right</span>
      </button>

      <!-- Last Page -->
      <button
        type="button"
        :disabled="currentPage === totalPages"
        class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-600 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:cursor-not-allowed transition font-semibold"
        title="Last page"
        @click="$emit('update:currentPage', totalPages)"
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
  currentPage: { type: Number, required: true },
  pageSize: { type: Number, default: 10 },
  totalItems: { type: Number, required: true }
})

defineEmits(['update:currentPage'])

const { lang } = useLang()

const totalPages = computed(() => Math.max(1, Math.ceil(props.totalItems / props.pageSize)))
const startItem = computed(() => (props.currentPage - 1) * props.pageSize + 1)
const endItem = computed(() => Math.min(props.currentPage * props.pageSize, props.totalItems))
</script>
