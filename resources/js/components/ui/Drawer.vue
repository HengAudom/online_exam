<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-xs"
        @click.self="close"
        @keydown.esc="close"
      >
        <div
          :class="[
            'fixed inset-y-0 max-w-full flex',
            side === 'left' ? 'left-0 pr-10' : 'right-0 pl-10'
          ]"
        >
          <Transition
            enter-active-class="transform transition ease-in-out duration-300"
            :enter-from-class="side === 'left' ? '-translate-x-full' : 'translate-x-full'"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-250"
            leave-to-class="translate-x-0"
            :leave-from-class="side === 'left' ? '-translate-x-full' : 'translate-x-full'"
          >
            <div
              v-if="modelValue"
              :class="[
                'w-screen bg-white shadow-soft-xl flex flex-col',
                side === 'left' ? 'border-r border-slate-200' : 'border-l border-slate-200',
                maxWidthClass
              ]"
            >
              <!-- Drawer Header -->
              <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 border-b border-slate-100 bg-slate-50/60 shrink-0">
                <slot name="header">
                  <div>
                    <h3 v-if="title" class="text-base font-bold text-slate-900 leading-tight">{{ title }}</h3>
                    <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">{{ subtitle }}</p>
                  </div>
                </slot>
                <button
                  type="button"
                  class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                  title="Close"
                  @click="close"
                >
                  <span class="material-symbols-outlined text-xl">close</span>
                </button>
              </div>

              <!-- Drawer Body -->
              <div class="flex-1 overflow-y-auto p-4 sm:p-6 min-h-0">
                <slot></slot>
              </div>

              <!-- Drawer Footer -->
              <div v-if="$slots.footer" class="px-4 sm:px-6 py-3 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-3 shrink-0">
                <slot name="footer"></slot>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  side: { type: String, default: 'right', validator: (v) => ['left', 'right'].includes(v) },
  maxWidth: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v) }
})

const emit = defineEmits(['update:modelValue', 'close'])

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

const maxWidthClass = computed(() => {
  switch (props.maxWidth) {
    case 'sm': return 'max-w-[280px] sm:max-w-xs'
    case 'lg': return 'max-w-lg'
    case 'xl': return 'max-w-xl'
    case 'md':
    default:   return 'max-w-md'
  }
})
</script>
