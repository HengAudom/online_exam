<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :title="title"
    :aria-label="ariaLabel || title"
    :class="[
      'inline-flex items-center justify-center transition-all duration-150 select-none cursor-pointer focus-ring disabled:opacity-40 disabled:cursor-not-allowed active:scale-95 shrink-0',
      sizeClasses,
      variantClasses,
      rounded === 'full' ? 'rounded-full' : 'rounded-xl'
    ]"
    @click="$emit('click', $event)"
  >
    <svg
      v-if="loading"
      class="animate-spin h-4 w-4 text-current"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>

    <span
      v-else
      class="material-symbols-outlined select-none"
      :class="iconSizeClass"
    >{{ icon }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  icon: { type: String, required: true },
  title: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
  type: { type: String, default: 'button' },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v)
  },
  variant: {
    type: String,
    default: 'ghost',
    validator: (v) => ['primary', 'secondary', 'outline', 'ghost', 'danger', 'subtle'].includes(v)
  },
  rounded: { type: String, default: 'xl' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false }
})

defineEmits(['click'])

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs': return 'h-7 w-7'
    case 'sm': return 'h-8 w-8'
    case 'lg': return 'h-11 w-11'
    case 'md':
    default:   return 'h-9 w-9'
  }
})

const iconSizeClass = computed(() => {
  switch (props.size) {
    case 'xs': return 'text-base'
    case 'sm': return 'text-lg'
    case 'lg': return 'text-2xl'
    case 'md':
    default:   return 'text-xl'
  }
})

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'primary':
      return 'bg-blue-600 text-white hover:bg-blue-700 shadow-soft-xs'
    case 'secondary':
      return 'bg-slate-100 text-slate-700 hover:bg-slate-200'
    case 'outline':
      return 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 hover:text-slate-900'
    case 'danger':
      return 'bg-red-50 text-red-600 hover:bg-red-100'
    case 'subtle':
      return 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-800'
    case 'ghost':
    default:
      return 'bg-transparent text-slate-500 hover:bg-slate-100 hover:text-slate-800'
  }
})
</script>
