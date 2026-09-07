<template>
  <div class="w-full space-y-1.5">
    <div v-if="showLabel || $slots.label" class="flex items-center justify-between text-xs font-semibold">
      <slot name="label">
        <span class="text-slate-600">{{ label }}</span>
        <span class="text-slate-800 font-bold">{{ percentage }}%</span>
      </slot>
    </div>

    <!-- Progress Track -->
    <div
      :class="[
        'w-full rounded-full bg-slate-100 overflow-hidden',
        sizeClasses
      ]"
    >
      <div
        :class="[
          'h-full rounded-full transition-all duration-500 ease-out',
          variantClasses,
          animated ? 'animate-pulse' : ''
        ]"
        :style="{ width: `${clampedPercent}%` }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  label: { type: String, default: '' },
  showLabel: { type: Boolean, default: false },
  size: { type: String, default: 'md', validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v) },
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'success', 'warning', 'danger', 'info', 'purple', 'dynamic'].includes(v)
  },
  animated: { type: Boolean, default: false }
})

const percentage = computed(() => {
  if (props.max <= 0) return 0
  return Math.round((props.value / props.max) * 100)
})

const clampedPercent = computed(() => Math.min(100, Math.max(0, percentage.value)))

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs': return 'h-1'
    case 'sm': return 'h-1.5'
    case 'lg': return 'h-3'
    case 'md':
    default:   return 'h-2'
  }
})

const variantClasses = computed(() => {
  if (props.variant === 'dynamic') {
    if (clampedPercent.value >= 80) return 'bg-emerald-500'
    if (clampedPercent.value >= 50) return 'bg-amber-500'
    return 'bg-rose-500'
  }

  switch (props.variant) {
    case 'success': return 'bg-emerald-500'
    case 'warning': return 'bg-amber-500'
    case 'danger':  return 'bg-rose-500'
    case 'info':    return 'bg-sky-500'
    case 'purple':  return 'bg-purple-500'
    case 'primary':
    default:        return 'bg-blue-600'
  }
})
</script>
