<template>
  <span
    :class="[
      'inline-flex items-center font-semibold select-none shrink-0',
      sizeClasses,
      variantClasses,
      rounded === 'full' ? 'rounded-full' : 'rounded-lg'
    ]"
  >
    <span v-if="dot" class="h-1.5 w-1.5 rounded-full mr-1.5 shrink-0" :class="dotColorClass"></span>
    <span v-if="icon" class="material-symbols-outlined text-xs mr-1 shrink-0">{{ icon }}</span>
    <slot></slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['primary', 'neutral', 'success', 'warning', 'danger', 'info', 'purple'].includes(v)
  },
  size: {
    type: String,
    default: 'sm',
    validator: (v) => ['xs', 'sm', 'md'].includes(v)
  },
  icon: { type: String, default: '' },
  dot: { type: Boolean, default: false },
  rounded: { type: String, default: 'full' }
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs': return 'px-2 py-0.5 text-[11px] leading-tight'
    case 'md': return 'px-3 py-1 text-xs'
    case 'sm':
    default:   return 'px-2.5 py-0.5 text-xs'
  }
})

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'bg-blue-50 text-blue-700 border border-blue-200/60'
    case 'success':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200/60'
    case 'warning':
      return 'bg-amber-50 text-amber-700 border border-amber-200/60'
    case 'danger':
      return 'bg-rose-50 text-rose-700 border border-rose-200/60'
    case 'info':
      return 'bg-sky-50 text-sky-700 border border-sky-200/60'
    case 'purple':
      return 'bg-purple-50 text-purple-700 border border-purple-200/60'
    case 'neutral':
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200/60'
  }
})

const dotColorClass = computed(() => {
  switch (props.variant) {
    case 'primary': return 'bg-blue-500'
    case 'success': return 'bg-emerald-500'
    case 'warning': return 'bg-amber-500'
    case 'danger': return 'bg-rose-500'
    case 'info': return 'bg-sky-500'
    case 'purple': return 'bg-purple-500'
    case 'neutral':
    default: return 'bg-slate-400'
  }
})
</script>
