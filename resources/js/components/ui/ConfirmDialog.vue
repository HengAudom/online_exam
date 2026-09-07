<template>
  <Modal
    :modelValue="modelValue"
    max-width="md"
    :show-close="false"
    :close-on-backdrop="!loading"
    @update:modelValue="$emit('update:modelValue', $event)"
  >
    <div class="text-center py-2">
      <!-- Icon Container -->
      <div
        :class="[
          'mx-auto flex h-14 w-14 items-center justify-center rounded-2xl mb-4',
          iconBgClass
        ]"
      >
        <span class="material-symbols-outlined text-3xl select-none" style="font-variation-settings: 'FILL' 1;">
          {{ icon }}
        </span>
      </div>

      <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ title }}</h3>
      <p class="text-sm text-slate-500 mt-2 leading-relaxed whitespace-pre-line">{{ message }}</p>

      <div class="mt-6 flex flex-col-reverse sm:flex-row gap-2.5">
        <Button
          variant="outline"
          full-width
          :disabled="loading"
          @click="onCancel"
        >
          {{ cancelText }}
        </Button>
        <Button
          :variant="confirmVariant"
          full-width
          :loading="loading"
          @click="onConfirm"
        >
          {{ confirmText }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed } from 'vue'
import Modal from './Modal.vue'
import Button from './Button.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirm Action' },
  message: { type: String, default: 'Are you sure you want to proceed?' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  confirmVariant: { type: String, default: 'danger' },
  icon: { type: String, default: 'warning' },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const onConfirm = () => emit('confirm')
const onCancel = () => {
  emit('update:modelValue', false)
  emit('cancel')
}

const iconBgClass = computed(() => {
  switch (props.confirmVariant) {
    case 'danger': return 'bg-red-50 text-red-600 border border-red-100'
    case 'warning': return 'bg-amber-50 text-amber-600 border border-amber-100'
    case 'success': return 'bg-emerald-50 text-emerald-600 border border-emerald-100'
    case 'primary':
    default: return 'bg-blue-50 text-blue-600 border border-blue-100'
  }
})
</script>
