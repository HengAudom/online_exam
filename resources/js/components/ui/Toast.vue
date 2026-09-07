<template>
  <Teleport to="body">
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none px-4 sm:px-0">
      <TransitionGroup
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto flex items-center justify-between gap-3 rounded-2xl p-4 shadow-soft-lg border transition-all',
            toastBgClasses(toast.type)
          ]"
          role="alert"
        >
          <div class="flex items-center gap-3 min-w-0">
            <span
              class="material-symbols-outlined text-xl shrink-0"
              style="font-variation-settings: 'FILL' 1;"
              :class="toastIconColor(toast.type)"
            >
              {{ toastIcon(toast.type) }}
            </span>
            <p class="text-sm font-semibold leading-snug break-words text-slate-800">{{ toast.message }}</p>
          </div>

          <button
            type="button"
            title="Dismiss"
            class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100/80 transition-colors shrink-0 cursor-pointer"
            @click="remove(toast.id)"
          >
            <span class="material-symbols-outlined text-base">close</span>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '../../composables/useToast'

const { toasts, remove } = useToast()

const toastBgClasses = (type) => {
  switch (type) {
    case 'success': return 'bg-white border-emerald-200/80'
    case 'error': return 'bg-white border-red-200/80'
    case 'warning': return 'bg-white border-amber-200/80'
    case 'info':
    default: return 'bg-white border-blue-200/80'
  }
}

const toastIcon = (type) => {
  switch (type) {
    case 'success': return 'check_circle'
    case 'error': return 'cancel'
    case 'warning': return 'warning'
    case 'info':
    default: return 'info'
  }
}

const toastIconColor = (type) => {
  switch (type) {
    case 'success': return 'text-emerald-600'
    case 'error': return 'text-red-600'
    case 'warning': return 'text-amber-500'
    case 'info':
    default: return 'text-blue-600'
  }
}
</script>
