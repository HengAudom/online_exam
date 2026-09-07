<template>
  <div ref="containerRef" class="overflow-x-auto pb-1 scrollbar-none scroll-smooth w-full">
    <div
      :class="[
        'flex items-center gap-1 sm:gap-1.5 p-1 w-full min-w-full',
        pill ? 'bg-slate-100/90 rounded-2xl border border-slate-200/60' : 'border-b border-slate-200 min-w-max'
      ]"
    >
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        :class="[
          'inline-flex items-center justify-center gap-1 sm:gap-2 font-semibold transition-all duration-150 cursor-pointer select-none focus-ring whitespace-nowrap',
          pill
            ? [
                'flex-1 min-w-0 rounded-xl px-2 sm:px-4 py-2 text-xs sm:text-sm',
                modelValue === tab.value
                  ? 'bg-white text-blue-600 shadow-soft-xs font-bold'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'
              ]
            : [
                'px-3 sm:px-4 py-2.5 text-xs sm:text-sm -mb-px border-b-2',
                modelValue === tab.value
                  ? 'border-blue-600 text-blue-600 font-bold'
                  : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
              ]
        ]"
        @click="selectTab(tab.value, $event)"
      >
        <span
          v-if="tab.icon"
          class="material-symbols-outlined text-base sm:text-lg shrink-0"
          :style="modelValue === tab.value ? 'font-variation-settings: \'FILL\' 1;' : ''"
        >
          {{ tab.icon }}
        </span>

        <span class="truncate">{{ tab.label }}</span>

        <!-- Counter Badge -->
        <span
          v-if="tab.count !== undefined"
          :class="[
            'rounded-full px-1.5 sm:px-2 py-0.2 text-[10px] sm:text-xs font-bold shrink-0',
            modelValue === tab.value
              ? 'bg-blue-50 text-blue-700'
              : 'bg-slate-200/70 text-slate-600'
          ]"
        >
          {{ tab.count }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], required: true },
  tabs: {
    type: Array,
    required: true // array of { label, value, icon, count }
  },
  pill: { type: Boolean, default: true }
})

const emit = defineEmits(['update:modelValue'])
const containerRef = ref(null)

const selectTab = (val, event) => {
  emit('update:modelValue', val)
  if (event?.currentTarget) {
    event.currentTarget.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
      inline: 'center'
    })
  }
}
</script>
