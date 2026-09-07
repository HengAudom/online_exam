<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center font-semibold transition-all duration-150 select-none cursor-pointer focus-ring disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]',
      sizeClasses,
      variantClasses,
      fullWidth ? 'w-full' : '',
      roundedClasses
    ]"
    @click="$emit('click', $event)"
  >
    <!-- Loading spinner -->
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-4 w-4 text-current"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>

    <!-- Leading Icon -->
    <span
      v-if="icon && !loading"
      class="material-symbols-outlined shrink-0 mr-1.5"
      :class="iconSizeClass"
    >{{ icon }}</span>

    <!-- Slot / Text Content -->
    <slot></slot>

    <!-- Trailing Icon -->
    <span
      v-if="trailingIcon"
      class="material-symbols-outlined shrink-0 ml-1.5"
      :class="iconSizeClass"
    >{{ trailingIcon }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: { type: String, default: 'button' },
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'outline', 'ghost', 'danger', 'success', 'warning'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v)
  },
  icon: { type: String, default: '' },
  trailingIcon: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  fullWidth: { type: Boolean, default: false },
  rounded: { type: String, default: 'xl' } // 'md', 'lg', 'xl', 'full'
})

defineEmits(['click'])

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs': return 'px-2.5 py-1 text-xs gap-1'
    case 'sm': return 'px-3.5 py-1.5 text-xs gap-1.5'
    case 'lg': return 'px-6 py-3 text-base gap-2.5 shadow-soft-sm'
    case 'md':
    default:   return 'px-4 py-2.5 text-sm gap-2 shadow-soft-xs'
  }
})

const iconSizeClass = computed(() => {
  switch (props.size) {
    case 'xs': return 'text-sm'
    case 'sm': return 'text-base'
    case 'lg': return 'text-xl'
    case 'md':
    default:   return 'text-lg'
  }
})

const roundedClasses = computed(() => {
  switch (props.rounded) {
    case 'md': return 'rounded-md'
    case 'lg': return 'rounded-lg'
    case 'full': return 'rounded-full'
    case 'xl':
    default: return 'rounded-xl'
  }
})

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'secondary':
      return 'bg-slate-100 text-slate-700 hover:bg-slate-200/80 border border-slate-200/60'
    case 'outline':
      return 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200 hover:border-slate-300'
    case 'ghost':
      return 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900'
    case 'danger':
      return 'bg-red-600 text-white hover:bg-red-700 shadow-soft-xs'
    case 'success':
      return 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-soft-xs'
    case 'warning':
      return 'bg-amber-500 text-white hover:bg-amber-600 shadow-soft-xs'
    case 'primary':
    default:
      return 'bg-blue-600 text-white hover:bg-blue-700 shadow-soft-sm'
  }
})
</script>
