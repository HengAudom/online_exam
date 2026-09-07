<template>
  <div
    :class="[
      'bg-white rounded-2xl border transition-all duration-200',
      bordered ? 'border-slate-200/90' : 'border-transparent',
      hoverable ? 'hover:shadow-soft-md hover:border-slate-300/80 cursor-pointer' : '',
      shadow ? 'shadow-soft-sm' : '',
      paddingClasses
    ]"
    @click="$emit('click', $event)"
  >
    <!-- Header -->
    <div
      v-if="$slots.header || title"
      :class="[
        'flex items-center justify-between border-b border-slate-100 shrink-0',
        padding === 'none' ? 'px-4 sm:px-5 py-3' : 'pb-3 mb-3'
      ]"
    >
      <slot name="header">
        <div class="min-w-0">
          <h3 v-if="title" class="text-base font-bold text-slate-900 leading-normal pt-0.5">{{ title }}</h3>
          <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5 leading-normal">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
          <slot name="actions"></slot>
        </div>
      </slot>
    </div>

    <!-- Main Content -->
    <slot></slot>

    <!-- Footer -->
    <div
      v-if="$slots.footer"
      :class="[
        'border-t border-slate-100 flex items-center justify-between shrink-0',
        padding === 'none' ? 'px-5 sm:px-6 py-4' : 'pt-4 mt-4'
      ]"
    >
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  padding: { type: String, default: 'normal', validator: (v) => ['none', 'sm', 'normal', 'lg'].includes(v) },
  bordered: { type: Boolean, default: true },
  shadow: { type: Boolean, default: true },
  hoverable: { type: Boolean, default: false }
})

defineEmits(['click'])

const paddingClasses = computed(() => {
  switch (props.padding) {
    case 'none': return 'p-0'
    case 'sm': return 'p-4'
    case 'lg': return 'p-7 sm:p-8'
    case 'normal':
    default: return 'p-5 sm:p-6'
  }
})
</script>
