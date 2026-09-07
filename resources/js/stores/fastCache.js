import { reactive } from 'vue'
import axios from 'axios'

// In-memory and sessionStorage backed lightning-fast cache
const memoryCache = reactive({
  students: null,
  admins: null,
  skills: null,
  groups: null,
  durations: null,
  tests: null,
  results: null,
  auditLogs: null,
  settings: null,
  dashboard: null,
  profile: null
})

// Load any persisted cache on startup
if (typeof window !== 'undefined') {
  try {
    const raw = sessionStorage.getItem('admin_fast_cache')
    if (raw) {
      const parsed = JSON.parse(raw)
      Object.assign(memoryCache, parsed)
    }
  } catch (e) {}
}

const persistCache = () => {
  if (typeof window !== 'undefined') {
    try {
      sessionStorage.setItem('admin_fast_cache', JSON.stringify(memoryCache))
    } catch (e) {}
  }
}

/**
 * Fast SWR fetcher that returns cached data immediately (0ms) and updates in background.
 */
export const fastCache = {
  get(key) {
    return memoryCache[key]
  },

  has(key) {
    const val = memoryCache[key]
    if (val === null || val === undefined) return false
    if (Array.isArray(val)) return val.length > 0
    return true
  },

  set(key, val) {
    memoryCache[key] = val
    persistCache()
  },

  /**
   * Pre-warm all admin datasets simultaneously in the background.
   */
  async prewarmAdminData() {
    try {
      Promise.allSettled([
        axios.get('/api/admin/students').then(res => {
          memoryCache.students = res.data.students || []
          memoryCache.admins = res.data.admins || []
          persistCache()
        }),
        axios.get('/api/admin/tests').then(res => {
          memoryCache.tests = res.data.tests || []
          persistCache()
        }),
        axios.get('/api/admin/skills-groups').then(res => {
          memoryCache.skills = res.data.skills || []
          memoryCache.groups = res.data.groups || []
          memoryCache.durations = res.data.durations || []
          persistCache()
        }),
        axios.get('/api/admin/results').then(res => {
          memoryCache.results = res.data.results || []
          persistCache()
        }),
        axios.get('/api/admin/audit-logs').then(res => {
          memoryCache.auditLogs = res.data.logs || []
          persistCache()
        })
      ])
    } catch (e) {}
  },

  clear() {
    Object.keys(memoryCache).forEach(k => {
      memoryCache[k] = null
    })
    if (typeof window !== 'undefined') {
      sessionStorage.removeItem('admin_fast_cache')
    }
  }
}
