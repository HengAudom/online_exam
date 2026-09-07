<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
            <span class="material-symbols-outlined text-xs mr-1">history</span>
            Super Admin Only
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.subtitle }}
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <Button
          variant="outline"
          icon="download"
          size="sm"
          @click="exportLogsJSON"
        >
          {{ t.exportJSON }}
        </Button>

        <Button
          v-if="can('Audit Logs', 'delete') || isSuperAdmin"
          variant="danger"
          icon="delete_sweep"
          size="sm"
          :loading="clearing"
          @click="showClearConfirm = true"
        >
          {{ t.clearAll }}
        </Button>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <Card padding="sm" class="shadow-soft-sm">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 flex-1 max-w-2xl">
          <CustomDropdown
            v-model="filterModule"
            :options="moduleOptions"
            labelKey="label"
            valueKey="value"
            :placeholder="t.allModules"
          />

          <CustomDropdown
            v-model="filterRole"
            :options="roleOptions"
            labelKey="label"
            valueKey="value"
            :placeholder="t.allRoles"
          />

          <SearchInput
            v-model="searchQuery"
            :placeholder="t.searchLogs"
          />
        </div>

        <div class="flex items-center gap-2">
          <IconButton
            v-if="filterModule || filterRole || searchQuery"
            icon="restart_alt"
            variant="ghost"
            size="md"
            :title="t.reset"
            class="shrink-0"
            @click="resetFilters"
          />
          <span class="text-xs font-bold text-slate-500 whitespace-nowrap bg-slate-100 px-2.5 py-1 rounded-lg">
            {{ filteredLogs.length }} {{ t.records }}
          </span>
        </div>
      </div>
    </Card>

    <!-- Logs Table Card -->
    <Card padding="none" class="shadow-soft-sm overflow-hidden">
      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/70">
            <tr>
              <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.dateTime }}</th>
              <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.userRole }}</th>
              <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.action }}</th>
              <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.module }}</th>
              <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.targetDetails }}</th>
              <th class="px-4 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.status }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="log in paginatedLogs" :key="log.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                <div class="flex items-center gap-1.5 font-mono font-medium">
                  <span class="material-symbols-outlined text-xs text-slate-400">schedule</span>
                  <span>{{ formatDate(log.date) }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 leading-snug">{{ log.user }}</div>
                <Badge :variant="log.role === 'Super Admin' || log.role === 'SuperAdmin' ? 'purple' : log.role === 'Admin' ? 'primary' : 'neutral'" size="xs" class="mt-0.5">
                  {{ log.role }}
                </Badge>
              </td>
              <td class="px-4 py-3 font-semibold text-slate-800">
                {{ log.action }}
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                  {{ log.module }}
                </span>
              </td>
              <td class="px-4 py-3 text-xs">
                <div class="font-bold text-slate-800">{{ log.target }}</div>
                <div v-if="log.details" class="text-slate-400 mt-0.5 truncate max-w-xs">{{ log.details }}</div>
              </td>
              <td class="px-4 py-3 text-right">
                <Badge
                  :variant="log.status === 'Failed' ? 'danger' : log.status === 'Logged Out' ? 'neutral' : 'success'"
                  size="xs"
                  dot
                >
                  {{ log.status || 'Success' }}
                </Badge>
              </td>
            </tr>
            <tr v-if="initialLoading && filteredLogs.length === 0">
              <td colspan="6" class="py-12 text-center">
                <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-semibold">
                  <span class="h-4 w-4 rounded-full border-2 border-purple-600 border-t-transparent animate-spin"></span>
                  <span>{{ lang === 'kh' ? 'កំពុងផ្ទុកទិន្នន័យ...' : 'Loading data...' }}</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="filteredLogs.length === 0">
              <td colspan="6">
                <EmptyState
                  icon="history_toggle_off"
                  :title="t.noLogsFound"
                  :description="t.noLogsDesc"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card List View -->
      <div class="md:hidden divide-y divide-slate-100 bg-white">
        <div v-for="log in paginatedLogs" :key="log.id" class="p-4 space-y-2.5">
          <!-- Top Row: User & Role + Status -->
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <div class="h-8 w-8 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs shrink-0 border border-purple-100">
                {{ (log.user || 'U').charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="font-bold text-slate-900 text-xs leading-tight">{{ log.user }}</p>
                <Badge
                  :variant="log.role === 'Super Admin' || log.role === 'SuperAdmin' ? 'purple' : log.role === 'Admin' ? 'primary' : 'neutral'"
                  size="xs"
                  class="mt-0.5"
                >
                  {{ log.role }}
                </Badge>
              </div>
            </div>

            <Badge
              :variant="log.status === 'Failed' ? 'danger' : log.status === 'Logged Out' ? 'neutral' : 'success'"
              size="xs"
              dot
            >
              {{ log.status || 'Success' }}
            </Badge>
          </div>

          <!-- Event / Action & Module -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700">
              {{ log.module || 'System' }}
            </span>
            <span class="text-xs font-semibold text-slate-800">
              {{ log.action }}
            </span>
          </div>

          <!-- Details -->
          <p v-if="log.details" class="text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100 break-words font-medium">
            {{ log.details }}
          </p>

          <!-- Footer: Date & IP -->
          <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono pt-1">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">schedule</span>
              {{ formatDate(log.date) }}
            </span>
            <span v-if="log.ip" class="text-slate-400 font-medium">IP: {{ log.ip }}</span>
          </div>
        </div>

        <div v-if="initialLoading && filteredLogs.length === 0" class="py-12 text-center">
          <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-semibold">
            <span class="h-4 w-4 rounded-full border-2 border-purple-600 border-t-transparent animate-spin"></span>
            <span>{{ lang === 'kh' ? 'កំពុងផ្ទុកទិន្នន័យ...' : 'Loading data...' }}</span>
          </div>
        </div>
        <div v-else-if="filteredLogs.length === 0" class="p-6">
          <EmptyState
            icon="history_toggle_off"
            :title="t.noLogsFound"
            :description="t.noLogsDesc"
          />
        </div>
      </div>

      <Pagination
        v-if="filteredLogs.length > pageSize"
        v-model:currentPage="currentPage"
        :pageSize="pageSize"
        :totalItems="filteredLogs.length"
      />
    </Card>

    <!-- ── Delete All Confirm Dialog ───────────────────────────────────── -->
    <ConfirmDialog
      v-model="showClearConfirm"
      :title="t.clearConfirmTitle"
      :message="t.clearConfirmMsg"
      :confirm-text="t.clearConfirmBtn"
      :cancel-text="t.cancel"
      confirm-variant="danger"
      icon="delete_sweep"
      :loading="clearing"
      @confirm="clearAllLogs"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import Badge from '../components/ui/Badge.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import Pagination from '../components/ui/Pagination.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'
import { fastCache } from '../stores/fastCache'
import { useRealtimePoll } from '../composables/useRealtimePoll'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { can, user } = usePermissions()

const isSuperAdmin = computed(() => {
  return user.value?.role === 'Super Admin' || user.value?.role === 'SuperAdmin'
})

const pageSize = 12
const currentPage = ref(1)

const logs = ref(fastCache.get('auditLogs') || [])
const initialLoading = ref(!logs.value.length)
const searchQuery = ref('')
const filterModule = ref('')
const filterRole = ref('')

const showClearConfirm = ref(false)
const clearing = ref(false)

const moduleOptions = computed(() => [
  { label: lang.value === 'kh' ? 'គ្រប់ម៉ូឌុលទាំងអស់' : 'All Modules', value: '' },
  { label: lang.value === 'kh' ? 'ការចូលប្រព័ន្ធ (Authentication / Login)' : 'Authentication (Logins)', value: 'Authentication' },
  { label: 'Exams', value: 'Exams' },
  { label: 'Students', value: 'Students' },
  { label: 'Admins', value: 'Admins' },
  { label: 'Academic', value: 'Academic' },
  { label: 'User Management', value: 'User Management' }
])

const roleOptions = computed(() => [
  { label: lang.value === 'kh' ? 'គ្រប់តួនាទីទាំងអស់' : 'All Roles', value: '' },
  { label: 'Super Admin', value: 'Super Admin' },
  { label: 'Admin', value: 'Admin' },
  { label: 'Student', value: 'Student' }
])

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'កំណត់ហេតុសកម្មភាពប្រព័ន្ធ (Audit Logs)',
      subtitle: 'តាមដានរាល់សកម្មភាព ការបង្កើត ការកែប្រែ និងការប្រឡងក្នុងប្រព័ន្ធ',
      exportJSON: 'នាំចេញ Logs (JSON)',
      clearAll: 'លុបទាំងអស់ (Delete All)',
      clearConfirmTitle: 'លុបកំណត់ហេតុទាំងអស់?',
      clearConfirmMsg: 'តើអ្នកប្រាកដជាចង់លុប និងជម្រះកំណត់ហេតុសកម្មភាពទាំងអស់ចេញពីប្រព័ន្ធមែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់ក្រោយវិញបានឡើយ។',
      clearConfirmBtn: 'លុបកំណត់ហេតុទាំងអស់',
      cancel: 'បោះបង់',
      clearedSuccess: 'បានលុបកំណត់ហេតុទាំងអស់ជោគជ័យ!',
      clearFailed: 'មិនអាចលុបកំណត់ហេតុបានទេ',
      allModules: 'ម៉ូឌុលទាំងអស់',
      allRoles: 'តួនាទីទាំងអស់',
      searchLogs: 'ស្វែងរកកំណត់ហេតុ...',
      reset: 'កំណត់ឡើងវិញ',
      records: 'កំណត់ត្រា',
      dateTime: 'កាលបរិច្ឆេទ & ម៉ោង',
      userRole: 'អ្នកប្រើ & តួនាទី',
      action: 'សកម្មភាព',
      module: 'ម៉ូឌុល',
      targetDetails: 'គោលដៅ & ព័ត៌មានលម្អិត',
      status: 'ស្ថានភាព',
      noLogsFound: 'រកមិនឃើញកំណត់ហេតុទេ',
      noLogsDesc: 'សូមព្យាយាមផ្លាស់ប្តូរពាក្យស្វែងរក ឬតម្រងម៉ូឌុល។'
    }
  }
  return {
    title: 'System Audit Logs',
    subtitle: 'System-wide event tracking, authentication events, and administrative activities',
    exportJSON: 'Export Logs (JSON)',
    clearAll: 'Delete All',
    clearConfirmTitle: 'Clear All Audit Logs?',
    clearConfirmMsg: 'Are you sure you want to delete and purge all system audit logs? This action cannot be undone.',
    clearConfirmBtn: 'Delete All Logs',
    cancel: 'Cancel',
    clearedSuccess: 'All audit logs cleared successfully!',
    clearFailed: 'Failed to clear audit logs.',
    allModules: 'All Modules',
    allRoles: 'All Roles',
    searchLogs: 'Search audit logs...',
    reset: 'Reset Filters',
    records: 'records',
    dateTime: 'Date & Time',
    userRole: 'User & Role',
    action: 'Action Event',
    module: 'Module',
    targetDetails: 'Target & Details',
    status: 'Status',
    noLogsFound: 'No audit records found',
    noLogsDesc: 'Try adjusting your search query or module filters.'
  }
})

const filteredLogs = computed(() => {
  return logs.value.filter(item => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      (item.user && item.user.toLowerCase().includes(q)) ||
      (item.action && item.action.toLowerCase().includes(q)) ||
      (item.target && item.target.toLowerCase().includes(q))

    const matchesModule = !filterModule.value || item.module === filterModule.value
    const matchesRole = !filterRole.value || item.role === filterRole.value

    return matchesSearch && matchesModule && matchesRole
  })
})

const paginatedLogs = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredLogs.value.slice(start, start + pageSize)
})

const resetFilters = () => {
  filterModule.value = ''
  filterRole.value = ''
  searchQuery.value = ''
  currentPage.value = 1
}

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleString('en-US', {
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const exportLogsJSON = () => {
  const data = JSON.stringify(filteredLogs.value, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `audit-logs-${new Date().toISOString().substring(0, 10)}.json`
  a.click()
  URL.revokeObjectURL(url)
  toastSuccess(`Exported ${filteredLogs.value.length} log records successfully!`)
}

const clearAllLogs = async () => {
  clearing.value = true
  try {
    await axios.delete('/api/admin/audit-logs')
    logs.value = []
    fastCache.set('auditLogs', [])
    try {
      localStorage.removeItem('activity_logs')
    } catch (e) {}
    toastSuccess(t.value.clearedSuccess)
    showClearConfirm.value = false
    await loadLogs()
  } catch (e) {
    toastError(e.response?.data?.message || t.value.clearFailed)
  } finally {
    clearing.value = false
  }
}

const loadLogs = async (isBackground = false) => {
  try {
    const res = await axios.get('/api/admin/audit-logs')
    logs.value = res.data.logs || []
    fastCache.set('auditLogs', logs.value)
  } catch (e) {
    if (!isBackground) {
      toastError('Failed to load audit logs.')
    }
  } finally {
    initialLoading.value = false
  }
}

useRealtimePoll(loadLogs, { interval: 5000 })
</script>
