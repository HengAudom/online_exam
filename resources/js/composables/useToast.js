import { ref } from 'vue'

const toasts = ref([])
let nextId = 1

export function useToast() {
  const show = (message, type = 'success', duration = 4000) => {
    const id = nextId++
    const toast = { id, message, type }
    toasts.value.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }
    return id
  }

  const success = (message, duration = 4000) => show(message, 'success', duration)
  const error = (message, duration = 5000) => show(message, 'error', duration)
  const warning = (message, duration = 4500) => show(message, 'warning', duration)
  const info = (message, duration = 4000) => show(message, 'info', duration)

  const remove = (id) => {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) {
      toasts.value.splice(idx, 1)
    }
  }

  const clearAll = () => {
    toasts.value = []
  }

  return {
    toasts,
    show,
    success,
    error,
    warning,
    info,
    remove,
    clearAll
  }
}
