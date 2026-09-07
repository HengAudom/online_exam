<template>
  <div class="w-full space-y-1.5">
    <div v-if="label || $slots.labelRight" class="flex items-center justify-between">
      <label v-if="label" :for="id" class="block text-xs font-bold uppercase tracking-wider text-slate-600">
        {{ label }}
        <span v-if="required" class="text-red-500 ml-0.5">*</span>
      </label>
      <slot name="labelRight"></slot>
    </div>

    <div class="relative flex items-center">
      <span class="material-symbols-outlined absolute left-3.5 text-slate-400 pointer-events-none select-none text-lg">
        lock
      </span>

      <input
        :id="id"
        :type="showPassword ? 'text' : 'password'"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="[
          'w-full rounded-xl border bg-slate-50/70 text-slate-900 text-sm font-medium transition-all duration-150 focus-ring pl-10 pr-11 py-2.5 disabled:bg-slate-100 disabled:text-slate-400 placeholder:text-slate-400/80',
          error
            ? 'border-red-400 bg-red-50/30 text-red-900 focus:border-red-500 focus:ring-red-500/20'
            : 'border-slate-200 hover:border-slate-300 focus:border-blue-600 focus:bg-white'
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
      />

      <button
        type="button"
        tabindex="-1"
        :title="showPassword ? 'Hide password' : 'Show password'"
        class="absolute right-2.5 p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer flex items-center justify-center"
        @click="showPassword = !showPassword"
      >
        <span class="material-symbols-outlined text-lg">
          {{ showPassword ? 'visibility_off' : 'visibility' }}
        </span>
      </button>
    </div>

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
import { ref } from 'vue'

defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  id: { type: String, default: () => `pwd-${Math.random().toString(36).substr(2, 9)}` },
  placeholder: { type: String, default: '••••••••' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false }
})

defineEmits(['update:modelValue', 'focus', 'blur'])

const showPassword = ref(false)
</script>
