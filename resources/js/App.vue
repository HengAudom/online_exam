<template>
  <router-view />
  <Toast />
  <PwaFloatingWidget />
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { setupAutoLogout } from './utils/autoLogout'
import Toast from './components/ui/Toast.vue'
import PwaFloatingWidget from './components/ui/PwaFloatingWidget.vue'

const router = useRouter()
const route = useRoute()

onMounted(() => {
  const result = setupAutoLogout(router)
  const cleanup = result.cleanup
  const resetTimer = result.resetTimer

  // Watch for route changes to ensure the inactivity timer is active
  watch(() => route.path, () => {
    resetTimer()
  })

  onUnmounted(() => {
    if (cleanup) cleanup()
  })
})
</script>
