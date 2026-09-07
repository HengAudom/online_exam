import { ref, computed } from 'vue'

// Shared singleton reactive state
const deferredPrompt = ref(null)
const isInstallable = ref(false)
const isStandalone = ref(false)
const isAppInstalled = ref(false)
const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false)
const swRegistration = ref(null)
let isInitialized = false

/**
 * Check if the application is currently running in standalone (installed) mode.
 */
function checkIsStandalone() {
  if (typeof window === 'undefined') return false
  if (window.__pwa_is_standalone) return true
  const isDisplayStandalone = window.matchMedia?.('(display-mode: standalone)')?.matches || false
  const isIosStandalone = window.navigator?.standalone === true
  const isAndroidWebApk = document.referrer?.includes('android-app://') || false
  const isPwaQuery = window.location.search.includes('source=pwa')
  return Boolean(isDisplayStandalone || isIosStandalone || isAndroidWebApk || isPwaQuery)
}

/**
 * Initialize PWA listeners and Service Worker registration.
 */
export function initPwa() {
  if (isInitialized || typeof window === 'undefined') return
  isInitialized = true

  // Initial standalone check
  isStandalone.value = checkIsStandalone()

  // Check if early capture in <head> already caught the prompt
  if (window.__pwa_deferred_prompt) {
    deferredPrompt.value = window.__pwa_deferred_prompt
    isInstallable.value = true
  }

  // Listen for custom event from head script
  window.addEventListener('pwa-prompt-ready', (e) => {
    deferredPrompt.value = e.detail || window.__pwa_deferred_prompt
    isInstallable.value = true
    isAppInstalled.value = false
  })

  window.addEventListener('pwa-installed', () => {
    deferredPrompt.value = null
    isInstallable.value = false
    isAppInstalled.value = true
    isStandalone.value = true
  })

  // Listen for display mode changes (e.g. user opens in app window)
  if (window.matchMedia) {
    const displayModeQuery = window.matchMedia('(display-mode: standalone)')
    try {
      displayModeQuery.addEventListener('change', (e) => {
        isStandalone.value = Boolean(e.matches)
        if (e.matches) {
          isInstallable.value = false
          deferredPrompt.value = null
        }
      })
    } catch {
      displayModeQuery.addListener?.((e) => {
        isStandalone.value = Boolean(e.matches)
      })
    }
  }

  // Network online/offline status
  window.addEventListener('online', () => {
    isOffline.value = false
  })
  window.addEventListener('offline', () => {
    isOffline.value = true
  })

  // Standard beforeinstallprompt listener
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt.value = e
    window.__pwa_deferred_prompt = e
    isInstallable.value = true
    isAppInstalled.value = false
  })

  // App was successfully installed
  window.addEventListener('appinstalled', () => {
    deferredPrompt.value = null
    window.__pwa_deferred_prompt = null
    isInstallable.value = false
    isAppInstalled.value = true
    isStandalone.value = true
  })

  // Register Service Worker immediately
  if ('serviceWorker' in navigator) {
    const registerSW = async () => {
      try {
        const registration = await navigator.serviceWorker.register('/sw.js', {
          scope: '/'
        })
        swRegistration.value = registration

        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing
          if (newWorker) {
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                newWorker.postMessage({ type: 'SKIP_WAITING' })
              }
            })
          }
        })
      } catch (err) {
        console.warn('[PWA] Service Worker registration failed:', err)
      }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      registerSW()
    } else {
      window.addEventListener('DOMContentLoaded', registerSW)
      window.addEventListener('load', registerSW)
    }

    let refreshing = false
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (!refreshing) {
        refreshing = true
        if (!window.location.pathname.includes('/exam/')) {
          window.location.reload()
        }
      }
    })
  }
}

/**
 * Composable hook for components to access PWA state and trigger installation.
 */
export function usePwaInstall() {
  if (!isInitialized) {
    initPwa()
  }

  // Can install: app is running in standard web browser (not installed / not standalone)
  const canInstall = computed(() => {
    return !isStandalone.value && !isAppInstalled.value
  })

  const hasNativePrompt = computed(() => {
    return Boolean(deferredPrompt.value || (typeof window !== 'undefined' && window.__pwa_deferred_prompt))
  })

  /**
   * Directly triggers the native browser installation dialog.
   */
  const installApp = async () => {
    const promptEvent = deferredPrompt.value || (typeof window !== 'undefined' ? window.__pwa_deferred_prompt : null)

    if (promptEvent) {
      try {
        await promptEvent.prompt()
        const choiceResult = await promptEvent.userChoice

        deferredPrompt.value = null
        if (typeof window !== 'undefined') {
          window.__pwa_deferred_prompt = null
        }
        isInstallable.value = false

        if (choiceResult.outcome === 'accepted') {
          isAppInstalled.value = true
          return { success: true, outcome: 'accepted' }
        } else {
          return { success: false, outcome: 'dismissed' }
        }
      } catch (error) {
        console.error('[PWA] Install prompt error:', error)
        return { success: false, error }
      }
    }

    return { success: false, reason: 'no-prompt' }
  }

  return {
    isInstallable,
    isStandalone,
    isAppInstalled,
    isOffline,
    canInstall,
    hasNativePrompt,
    installApp,
    checkIsStandalone
  }
}
