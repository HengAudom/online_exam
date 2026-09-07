<template>
  <div class="flex flex-col items-center justify-center text-center p-8 sm:p-12 select-none">
    <!-- Icon or Illustration -->
    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100/90 text-slate-400 mb-4">
      <span class="material-symbols-outlined text-3xl select-none" style="font-variation-settings: 'FILL' 0;">
        {{ icon }}
      </span>
    </div>

    <!-- Title & Description -->
    <h3 class="text-base font-bold text-slate-800">{{ title }}</h3>
    <p v-if="description" class="text-sm text-slate-500 mt-1 max-w-sm leading-relaxed">
      {{ description }}
    </p>

    <!-- Action Slot or Button -->
    <div v-if="$slots.action || actionLabel" class="mt-6">
      <slot name="action">
        <Button
          v-if="actionLabel"
          variant="primary"
          :icon="actionIcon"
          @click="$emit('action')"
        >
          {{ actionLabel }}
        </Button>
      </slot>
    </div>
  </div>
</template>

<script setup>
import Button from './Button.vue'

defineProps({
  icon: { type: String, default: 'inbox' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  actionLabel: { type: String, default: '' },
  actionIcon: { type: String, default: 'add' }
})

defineEmits(['action'])
</script>
