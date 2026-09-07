import { reactive, ref } from 'vue'
import axios from 'axios'
import { fastCache } from '../stores/fastCache'
import { useLang } from '../utils/useLang'

const defaultSettings = {
  institutionName: 'Online Examination System',
  academicYear: '2026-2027',
  timezone: 'Asia/Phnom_Penh',
  defaultLanguage: 'kh',
  sessionTimeoutMinutes: 60,
  allowRegistration: true,
  forceStrongPassword: true,
  antiCheatPause: true,
  autosaveIntervalSeconds: 3,
  autoSubmitOnTimeout: true,
  phpVersion: '',
  laravelVersion: '',
  databaseDriver: 'MySQL',
  serverTime: ''
}

const cached = fastCache.get('system_settings') || {}
const settings = reactive({ ...defaultSettings, ...cached })
const isLoaded = ref(false)
const isLoading = ref(false)

export function useSettings() {
  const { setLang } = useLang()

  const fetchSettings = async (force = false) => {
    if (isLoaded.value && !force) return settings
    isLoading.value = true
    try {
      const res = await axios.get('/api/public-settings')
      const data = res.data.settings || {}
      Object.assign(settings, data)
      fastCache.set('system_settings', { ...settings })
      isLoaded.value = true

      // If user hasn't set explicit language preference, sync with system default
      if (!localStorage.getItem('app_lang') && data.defaultLanguage) {
        setLang(data.defaultLanguage)
      }
    } catch (e) {
      // Fallback to cache or defaults
    } finally {
      isLoading.value = false
    }
    return settings
  }

  return {
    settings,
    isLoaded,
    isLoading,
    fetchSettings
  }
}
