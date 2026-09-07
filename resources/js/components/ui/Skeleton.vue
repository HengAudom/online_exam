<template>
  <div
    :class="[
      'skeleton-shimmer rounded-xl select-none',
      shape === 'circle' ? 'rounded-full' : '',
      customClass
    ]"
    :style="{ width: computedWidth, height: computedHeight }"
  ></div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  width: { type: [String, Number], default: '100%' },
  height: { type: [String, Number], default: '1rem' },
  shape: { type: String, default: 'rect', validator: (v) => ['rect', 'circle'].includes(v) },
  customClass: { type: String, default: '' }
})

const computedWidth = computed(() => (typeof props.width === 'number' ? `${props.width}px` : props.width))
const computedHeight = computed(() => (typeof props.height === 'number' ? `${props.height}px` : props.height))
</script>
