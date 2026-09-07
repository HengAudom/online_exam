<template>
  <div class="w-full space-y-1.5">
    <!-- Label -->
    <div v-if="label || $slots.labelRight" class="flex items-center justify-between">
      <label v-if="label" :for="id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
        {{ label }}
        <span v-if="required" class="text-red-500 ml-0.5">*</span>
      </label>
      <slot name="labelRight"></slot>
    </div>

    <!-- Input Wrapper -->
    <div class="relative flex items-center">
      <!-- Leading Icon -->
      <span
        v-if="icon"
        class="material-symbols-outlined absolute left-3.5 text-slate-400 pointer-events-none select-none text-lg"
      >{{ icon }}</span>

      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :class="[
          'w-full rounded-xl border bg-slate-50/70 text-slate-900 text-sm font-medium transition-all duration-150 focus-ring disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed placeholder:text-slate-400/80',
          sizeClasses,
          icon ? 'pl-10' : 'pl-3.5',
          trailingIcon || $slots.trailing ? 'pr-10' : 'pr-3.5',
          error
            ? 'border-red-400 bg-red-50/30 text-red-900 focus:border-red-500 focus:ring-red-500/20'
            : 'border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:bg-white'
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
        @change="$emit('change', $event)"
        @keyup="$emit('keyup', $event)"
        @keydown="$emit('keydown', $event)"
      />

      <!-- Trailing Icon or Slot -->
      <div v-if="trailingIcon || $slots.trailing" class="absolute right-3 flex items-center text-slate-400">
        <span v-if="trailingIcon" class="material-symbols-outlined text-lg pointer-events-none select-none">{{ trailingIcon }}</span>
        <slot name="trailing"></slot>
      </div>
    </div>

    <!-- Helper Text or Error -->
    <p v-if="error" class="text-xs font-semibold text-red-500 flex items-center gap-1 mt-1">
      <span class="material-symbols-outlined text-xs">error</span>
      {{ error }}
    </p>
    <p v-else-if="hint" class="text-xs text-slate-400 mt-1">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  icon: { type: String, default: '' },
  trailingIcon: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false }
})

defineEmits(['update:modelValue', 'focus', 'blur', 'change', 'keyup', 'keydown'])

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'py-1.5 text-xs'
    case 'lg': return 'py-3 text-base'
    case 'md':
    default:   return 'py-2.5 text-sm'
  }
})
</script>
