<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6 pb-2">
    <div>
      <div v-if="eyebrow" class="flex items-center gap-2 mb-1">
        <span class="text-xs font-bold uppercase tracking-wider text-blue-600">{{ eyebrow }}</span>
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
        {{ title }}
      </h1>
      <p v-if="description" class="text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
        {{ description }}
      </p>
    </div>

    <!-- Actions -->
    <div v-if="$slots.actions" class="flex items-center flex-wrap gap-2.5 shrink-0">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  eyebrow: { type: String, default: '' }
})
</script>
