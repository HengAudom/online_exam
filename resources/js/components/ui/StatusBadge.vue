<template>
  <Badge :variant="badgeConfig.variant" :icon="badgeConfig.icon" :dot="showDot" :size="size">
    {{ label || badgeConfig.label }}
  </Badge>
</template>

<script setup>
import { computed } from 'vue'
import Badge from './Badge.vue'

const props = defineProps({
  status: { type: String, required: true },
  label: { type: String, default: '' },
  size: { type: String, default: 'sm' },
  showDot: { type: Boolean, default: true }
})

const badgeConfig = computed(() => {
  const s = String(props.status).toLowerCase().trim()

  switch (s) {
    case 'published':
    case 'live':
    case 'active':
      return { variant: 'success', icon: 'check_circle', label: 'Published' }
    case 'draft':
      return { variant: 'warning', icon: 'edit_note', label: 'Draft' }
    case 'finished':
    case 'completed':
      return { variant: 'info', icon: 'task_alt', label: 'Finished' }
    case 'scheduled':
      return { variant: 'purple', icon: 'schedule', label: 'Scheduled' }
    case 'archived':
    case 'inactive':
      return { variant: 'neutral', icon: 'archive', label: 'Archived' }
    case 'taken':
    case 'passed':
      return { variant: 'success', icon: 'done_all', label: 'Taken' }
    case 'pending':
    case 'not_taken':
      return { variant: 'warning', icon: 'pending', label: 'Pending' }
    default:
      return { variant: 'neutral', icon: 'info', label: props.status }
  }
})
</script>
