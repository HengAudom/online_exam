import { ref, computed } from 'vue'
import axios from 'axios'

const currentUser = ref(null)
const userPermissions = ref([])
const isLoadingUser = ref(false)

export function usePermissions() {
  const fetchUser = async (force = false) => {
    if (currentUser.value && !force) return currentUser.value
    isLoadingUser.value = true
    try {
      const res = await axios.get('/api/profile')
      currentUser.value = res.data.user || null
      userPermissions.value = res.data.permissions || []
    } catch (e) {
      currentUser.value = null
      userPermissions.value = []
    } finally {
      isLoadingUser.value = false
    }
    return currentUser.value
  }

  const isSuperAdmin = computed(() => {
    const role = currentUser.value?.role
    return role === 'Super Admin' || role === 'SuperAdmin'
  })

  const isAdmin = computed(() => {
    const role = currentUser.value?.role
    return role === 'Admin' || isSuperAdmin.value
  })

  const isStudent = computed(() => {
    return currentUser.value?.role === 'Student'
  })

  const roleName = computed(() => {
    if (isSuperAdmin.value) return 'Super Admin'
    if (currentUser.value?.role === 'Admin') return 'Admin'
    return currentUser.value?.role || 'Student'
  })

  /**
   * Permission checker:
   * SuperAdmin has full unrestricted access.
   * Admin is dynamically evaluated against the saved Permission Matrix.
   * Student has student examinee permissions.
   */
  const can = (moduleName, action = 'view') => {
    if (isSuperAdmin.value) return true

    const role = currentUser.value?.role
    if (role === 'Admin') {
      const matrix = userPermissions.value || []
      if (!matrix.length) return true

      const target = (moduleName || '').toLowerCase().trim().replace(/&/g, 'and').replace(/\s+/g, ' ')
      const item = matrix.find(m => {
        const mod = (m.module || '').toLowerCase().trim().replace(/&/g, 'and').replace(/\s+/g, ' ')
        return mod === target
      })

      if (item && item[action] !== undefined) {
        return !!item[action]
      }
      return true
    }

    if (role === 'Student') {
      const mod = (moduleName || '').toLowerCase()
      return mod === 'exam' || mod === 'results'
    }

    return false
  }

  return {
    currentUser,
    userPermissions,
    isLoadingUser,
    isSuperAdmin,
    isAdmin,
    isStudent,
    roleName,
    fetchUser,
    can
  }
}
