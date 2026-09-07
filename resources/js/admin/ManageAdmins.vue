<template>
  <div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
            <span class="material-symbols-outlined text-xs mr-1">admin_panel_settings</span>
            Super Admin Access
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.desc }}
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <Button
          variant="primary"
          icon="person_add"
          size="sm"
          @click="openAddModal"
        >
          {{ t.addAdmin }}
        </Button>
      </div>
    </div>

    <!-- Stat KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <Card padding="normal" class="shadow-soft-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t.totalAdmins }}</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ adminsList.length }}</h3>
          </div>
          <div class="h-11 w-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
            <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
          </div>
        </div>
      </Card>

      <Card padding="normal" class="shadow-soft-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t.superAdminsCount }}</p>
            <h3 class="text-2xl font-black text-purple-700 mt-1">{{ superAdminsCount }}</h3>
          </div>
          <div class="h-11 w-11 rounded-2xl bg-purple-100/70 text-purple-700 flex items-center justify-center border border-purple-200">
            <span class="material-symbols-outlined text-2xl">security</span>
          </div>
        </div>
      </Card>

      <Card padding="normal" class="shadow-soft-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ t.standardAdminsCount }}</p>
            <h3 class="text-2xl font-black text-blue-700 mt-1">{{ standardAdminsCount }}</h3>
          </div>
          <div class="h-11 w-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
            <span class="material-symbols-outlined text-2xl">manage_accounts</span>
          </div>
        </div>
      </Card>
    </div>

    <!-- Filter & Search Toolbar -->
    <Card padding="sm" class="shadow-soft-sm">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:w-60">
          <CustomDropdown
            v-model="filterRole"
            :options="roleFilterOptions"
            labelKey="label"
            valueKey="value"
            :placeholder="t.allRoles"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <IconButton
            v-if="filterRole || searchQuery"
            icon="restart_alt"
            variant="ghost"
            size="md"
            :title="t.reset"
            class="shrink-0"
            @click="resetFilters"
          />

          <div class="w-full sm:w-72">
            <SearchInput
              v-model="searchQuery"
              :placeholder="t.searchPlaceholder"
            />
          </div>
        </div>
      </div>
    </Card>

    <!-- Administrators Table -->
    <Card padding="none" class="shadow-soft-sm overflow-hidden w-full">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 shrink-0">
              <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-normal">{{ t.adminsList }}</h3>
              <p class="text-[11px] text-slate-400 leading-normal">{{ filteredAdminsList.length }} {{ t.accountsCount }}</p>
            </div>
          </div>
          <Button
            variant="outline"
            size="xs"
            icon="add"
            @click="openAddModal"
          >
            {{ t.addAdmin }}
          </Button>
        </div>
      </template>

      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/70">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500 w-12">#</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.adminUser }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.role }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.phone }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.status }}</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.actions }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="(admin, index) in paginatedAdmins" :key="admin.id" class="hover:bg-purple-50/20 transition-colors">
              <td class="px-4 py-3 text-xs font-bold text-slate-400">
                {{ (currentPage - 1) * pageSize + index + 1 }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs shrink-0 border border-purple-100 shadow-sm">
                    {{ (admin.name || 'A').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-bold text-slate-900">{{ admin.name }}</div>
                    <div class="text-xs text-slate-400 font-mono mt-0.5">@{{ admin.username }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <Badge
                  :variant="admin.role === 'SuperAdmin' || admin.role === 'Super Admin' ? 'purple' : 'primary'"
                  size="sm"
                >
                  <span class="material-symbols-outlined text-[12px] mr-1">
                    {{ admin.role === 'SuperAdmin' || admin.role === 'Super Admin' ? 'security' : 'admin_panel_settings' }}
                  </span>
                  {{ admin.role }}
                </Badge>
              </td>
              <td class="px-4 py-3 text-xs font-medium text-slate-600">
                {{ admin.phone || '—' }}
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Active
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <IconButton
                    icon="edit"
                    variant="ghost"
                    size="sm"
                    :title="t.edit"
                    @click="editAdmin(admin)"
                  />
                  <IconButton
                    v-if="admin.id !== currentUser?.id"
                    icon="delete"
                    variant="ghost"
                    size="sm"
                    :title="t.delete"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    @click="confirmDeleteAdmin(admin)"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="filteredAdminsList.length === 0">
              <td colspan="6">
                <EmptyState
                  icon="admin_panel_settings"
                  :title="t.noAdminsFound"
                  :description="t.noAdminsDesc"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card List -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="admin in paginatedAdmins" :key="admin.id" class="p-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs shrink-0 border border-purple-100 shadow-sm">
                {{ (admin.name || 'A').charAt(0).toUpperCase() }}
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">{{ admin.name }}</h4>
                <p class="text-xs text-slate-400 font-mono">@{{ admin.username }}</p>
              </div>
            </div>
            <Badge
              :variant="admin.role === 'SuperAdmin' || admin.role === 'Super Admin' ? 'purple' : 'primary'"
              size="xs"
            >
              {{ admin.role }}
            </Badge>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
            <span>{{ t.phone }}: {{ admin.phone || '—' }}</span>
            <span class="text-emerald-600 font-semibold">• Active</span>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-50">
            <Button variant="secondary" size="xs" icon="edit" @click="editAdmin(admin)">
              {{ t.edit }}
            </Button>
            <Button
              v-if="admin.id !== currentUser?.id"
              variant="danger"
              size="xs"
              icon="delete"
              @click="confirmDeleteAdmin(admin)"
            >
              {{ t.delete }}
            </Button>
          </div>
        </div>

        <EmptyState
          v-if="filteredAdminsList.length === 0"
          icon="admin_panel_settings"
          :title="t.noAdminsFound"
          :description="t.noAdminsDesc"
        />
      </div>

      <!-- Pagination -->
      <Pagination
        v-if="filteredAdminsList.length > pageSize"
        v-model:currentPage="currentPage"
        :pageSize="pageSize"
        :totalItems="filteredAdminsList.length"
      />
    </Card>

    <!-- ── Add Administrator Modal ─────────────────────────────────── -->
    <Modal
      v-model="addingAdmin"
      :title="t.addAdminTitle"
      max-width="lg"
    >
      <div class="space-y-4">
        <!-- Role selector -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.role }}</label>
          <CustomDropdown
            v-model="addForm.role"
            :options="[
              { label: 'Admin (Standard)', value: 'Admin' },
              { label: 'Super Admin (Full Root)', value: 'SuperAdmin' }
            ]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="addForm.firstName" :label="t.firstName" required placeholder="First Name" />
          <Input v-model="addForm.lastName" :label="t.lastName" required placeholder="Last Name" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="addForm.username" :label="t.username" icon="alternate_email" required placeholder="admin_username" />
          <Input v-model="addForm.phone" :label="t.phone" icon="call" placeholder="+855 xxx xxx xxx" />
        </div>

        <PasswordInput v-model="addForm.password" :label="t.password" required placeholder="Min. 6 characters" />
      </div>

      <template #footer>
        <Button variant="outline" @click="addingAdmin = false">{{ t.cancel }}</Button>
        <Button variant="primary" :loading="savingAdd" @click="saveNewAdmin">{{ t.saveAdmin }}</Button>
      </template>
    </Modal>

    <!-- ── Edit Administrator Modal ────────────────────────────────── -->
    <Modal
      v-model="editingAdminModal"
      :title="t.editAdminTitle"
      max-width="lg"
    >
      <div class="space-y-4">
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.role }}</label>
          <CustomDropdown
            v-model="editForm.role"
            :options="[
              { label: 'Admin (Standard)', value: 'Admin' },
              { label: 'Super Admin (Full Root)', value: 'SuperAdmin' }
            ]"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="editForm.firstName" :label="t.firstName" required />
          <Input v-model="editForm.lastName" :label="t.lastName" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="editForm.username" :label="t.username" required />
          <Input v-model="editForm.phone" :label="t.phone" />
        </div>

        <!-- Optional Password Reset -->
        <div class="pt-2 border-t border-slate-100">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">{{ t.changePasswordOptional }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <PasswordInput v-model="editForm.newPassword" :label="t.newPassword" placeholder="Leave blank to keep" />
            <PasswordInput v-model="editForm.confirmPassword" :label="t.confirmPassword" placeholder="Confirm password" />
          </div>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="editingAdminModal = false">{{ t.cancel }}</Button>
        <Button variant="primary" :loading="savingEdit" @click="saveAdmin">{{ t.saveChanges }}</Button>
      </template>
    </Modal>

    <!-- ── Delete Confirm Dialog ───────────────────────────────────── -->
    <ConfirmDialog
      v-model="showDeleteDialog"
      :title="t.deleteConfirmTitle"
      :message="deleteConfirmMessage"
      :confirm-text="t.delete"
      :cancel-text="t.cancel"
      confirm-variant="danger"
      icon="delete"
      :loading="deleting"
      @confirm="performDelete"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import Badge from '../components/ui/Badge.vue'
import Input from '../components/ui/Input.vue'
import PasswordInput from '../components/ui/PasswordInput.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import Modal from '../components/ui/Modal.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import Pagination from '../components/ui/Pagination.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'
import { logActivity } from '../utils/activityLog'
import { useRealtimePoll, broadcastSync } from '../composables/useRealtimePoll'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { user: currentUser } = usePermissions()

const adminsList = ref([])
const initialLoading = ref(true)
const searchQuery = ref('')
const filterRole = ref('')
const currentPage = ref(1)
const pageSize = 10

const addingAdmin = ref(false)
const savingAdd = ref(false)

const editingAdminModal = ref(false)
const savingEdit = ref(false)
const editingAdminId = ref(null)

const showDeleteDialog = ref(false)
const adminToDelete = ref(null)
const deleting = ref(false)

const addForm = reactive({
  role: 'Admin',
  firstName: '',
  lastName: '',
  username: '',
  phone: '',
  password: ''
})

const editForm = reactive({
  role: 'Admin',
  firstName: '',
  lastName: '',
  username: '',
  phone: '',
  newPassword: '',
  confirmPassword: ''
})

const roleFilterOptions = computed(() => [
  { label: lang.value === 'kh' ? 'តួនាទីទាំងអស់' : 'All Roles', value: '' },
  { label: 'Super Admin', value: 'SuperAdmin' },
  { label: 'Admin', value: 'Admin' }
])

const superAdminsCount = computed(() => {
  return adminsList.value.filter(a => a.role === 'SuperAdmin' || a.role === 'Super Admin').length
})

const standardAdminsCount = computed(() => {
  return adminsList.value.filter(a => a.role === 'Admin').length
})

const filteredAdminsList = computed(() => {
  return adminsList.value.filter(a => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      (a.name && a.name.toLowerCase().includes(q)) ||
      (a.username && a.username.toLowerCase().includes(q)) ||
      (a.phone && a.phone.toLowerCase().includes(q))

    let matchesRole = true
    if (filterRole.value) {
      if (filterRole.value === 'SuperAdmin') {
        matchesRole = a.role === 'SuperAdmin' || a.role === 'Super Admin'
      } else {
        matchesRole = a.role === filterRole.value
      }
    }

    return matchesSearch && matchesRole
  })
})

const paginatedAdmins = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredAdminsList.value.slice(start, start + pageSize)
})

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'គ្រប់គ្រងអ្នកគ្រប់គ្រង',
      desc: 'គ្រប់គ្រងគណនី Admin និង Super Admin ព្រមទាំងការកំណត់សិទ្ធិប្រព័ន្ធ',
      addAdmin: 'បន្ថែមអ្នកគ្រប់គ្រង',
      addAdminTitle: 'បន្ថែមអ្នកគ្រប់គ្រងថ្មី',
      editAdminTitle: 'កែប្រែព័ត៌មានអ្នកគ្រប់គ្រង',
      totalAdmins: 'អ្នកគ្រប់គ្រងសរុប',
      superAdminsCount: 'Super Admin (Root)',
      standardAdminsCount: 'Admin (ស្តង់ដារ)',
      adminsList: 'បញ្ជីអ្នកគ្រប់គ្រង',
      accountsCount: 'គណនីគ្រប់គ្រង',
      adminUser: 'ឈ្មោះអ្នកគ្រប់គ្រង',
      role: 'តួនាទី',
      allRoles: 'តួនាទីទាំងអស់',
      phone: 'លេខទូរស័ព្ទ',
      status: 'ស្ថានភាព',
      actions: 'សកម្មភាព',
      edit: 'កែប្រែ',
      delete: 'លុប',
      searchPlaceholder: 'ស្វែងរកតាមឈ្មោះ ឬ Username...',
      reset: 'កំណត់ឡើងវិញ',
      noAdminsFound: 'រកមិនឃើញទិន្នន័យអ្នកគ្រប់គ្រងទេ',
      noAdminsDesc: 'សូមព្យាយាមផ្លាស់ប្តូរពាក្យស្វែងរក ឬតម្រង។',
      firstName: 'នាមខ្លួន',
      lastName: 'គោត្តនាម',
      username: 'ឈ្មោះគណនី (Username)',
      password: 'ពាក្យសម្ងាត់',
      changePasswordOptional: 'ផ្លាស់ប្តូរពាក្យសម្ងាត់ (ស្រេចចិត្ត)',
      newPassword: 'ពាក្យសម្ងាត់ថ្មី',
      confirmPassword: 'បញ្ជាក់ពាក្យសម្ងាត់',
      cancel: 'បោះបង់',
      saveAdmin: 'រក្សាទុក',
      saveChanges: 'រក្សាទុកការកែប្រែ',
      deleteConfirmTitle: 'លុបអ្នកគ្រប់គ្រង?',
    }
  }
  return {
    title: 'Administrators Management',
    desc: 'Manage administrator accounts, Super Admin credentials, and staff access',
    addAdmin: 'Add Administrator',
    addAdminTitle: 'Add New Administrator',
    editAdminTitle: 'Edit Administrator Profile',
    totalAdmins: 'Total Admins',
    superAdminsCount: 'Super Admins',
    standardAdminsCount: 'Standard Admins',
    adminsList: 'Administrators Directory',
    accountsCount: 'admin accounts',
    adminUser: 'Administrator',
    role: 'Role',
    allRoles: 'All Roles',
    phone: 'Phone',
    status: 'Status',
    actions: 'Actions',
    edit: 'Edit',
    delete: 'Delete',
    searchPlaceholder: 'Search by name or username...',
    reset: 'Reset Filters',
    noAdminsFound: 'No administrators found',
    noAdminsDesc: 'Try adjusting your search terms or filter selection.',
    firstName: 'First Name',
    lastName: 'Last Name',
    username: 'Username',
    password: 'Password',
    changePasswordOptional: 'Change Password (Optional)',
    newPassword: 'New Password',
    confirmPassword: 'Confirm Password',
    cancel: 'Cancel',
    saveAdmin: 'Save Admin',
    saveChanges: 'Save Changes',
    deleteConfirmTitle: 'Delete Administrator?',
  }
})

const deleteConfirmMessage = computed(() => {
  if (!adminToDelete.value) return ''
  return lang.value === 'kh'
    ? `តើអ្នកពិតជាចង់លុបគណនី "${adminToDelete.value.name}" (@${adminToDelete.value.username}) មែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។`
    : `Are you sure you want to delete "${adminToDelete.value.name}" (@${adminToDelete.value.username})? This action cannot be undone.`
})

const resetFilters = () => {
  filterRole.value = ''
  searchQuery.value = ''
  currentPage.value = 1
}

const openAddModal = () => {
  addForm.role = 'Admin'
  addForm.firstName = ''
  addForm.lastName = ''
  addForm.username = ''
  addForm.phone = ''
  addForm.password = ''
  addingAdmin.value = true
}

const saveNewAdmin = async () => {
  if (!addForm.firstName.trim() || !addForm.lastName.trim() || !addForm.username.trim() || !addForm.password.trim()) {
    toastError(lang.value === 'kh' ? 'សូមបំពេញព័ត៌មានចាំបាច់ទាំងអស់ (ឈ្មោះ, Username, Password)' : 'Please fill all required fields.')
    return
  }

  savingAdd.value = true
  try {
    const res = await axios.post('/api/admin/students', {
      role: addForm.role,
      firstName: addForm.firstName.trim(),
      lastName: addForm.lastName.trim(),
      username: addForm.username.trim(),
      phone: addForm.phone.trim(),
      password: addForm.password
    })

    toastSuccess(res.data.message || 'Administrator created successfully!')
    logActivity(`New ${addForm.role} created: ${addForm.firstName} ${addForm.lastName}`, `Username: @${addForm.username}`)
    addingAdmin.value = false
    broadcastSync('students_updated')
    await loadData()
  } catch (err) {
    toastError(err.response?.data?.message || 'Failed to add administrator.')
  } finally {
    savingAdd.value = false
  }
}

const editAdmin = (admin) => {
  editingAdminId.value = admin.id
  editForm.role = admin.role || 'Admin'
  editForm.firstName = admin.firstName || admin.first_name || admin.name?.split(' ')[0] || ''
  editForm.lastName = admin.lastName || admin.last_name || admin.name?.split(' ').slice(1).join(' ') || ''
  editForm.username = admin.username || ''
  editForm.phone = admin.phone || ''
  editForm.newPassword = ''
  editForm.confirmPassword = ''
  editingAdminModal.value = true
}

const saveAdmin = async () => {
  if (!editForm.firstName.trim() || !editForm.lastName.trim() || !editForm.username.trim()) {
    toastError(lang.value === 'kh' ? 'សូមបំពេញព័ត៌មានចាំបាច់' : 'Please fill all required fields.')
    return
  }

  if (editForm.newPassword && editForm.newPassword !== editForm.confirmPassword) {
    toastError(lang.value === 'kh' ? 'ពាក្យសម្ងាត់មិនត្រូវគ្នាទេ' : 'Passwords do not match.')
    return
  }

  savingEdit.value = true
  try {
    const payload = {
      role: editForm.role,
      firstName: editForm.firstName.trim(),
      lastName: editForm.lastName.trim(),
      username: editForm.username.trim(),
      phone: editForm.phone.trim(),
      newPassword: editForm.newPassword || undefined
    }

    const res = await axios.put(`/api/admin/students/${editingAdminId.value}`, payload)

    toastSuccess(res.data.message || 'Administrator updated successfully!')
    editingAdminModal.value = false
    broadcastSync('students_updated')
    await loadData()
  } catch (err) {
    toastError(err.response?.data?.message || 'Failed to update administrator.')
  } finally {
    savingEdit.value = false
  }
}

const confirmDeleteAdmin = (admin) => {
  if (admin.id === currentUser.value?.id) {
    toastError(lang.value === 'kh' ? 'អ្នកមិនអាចលុបគណនីផ្ទាល់ខ្លួនរបស់អ្នកបានទេ' : 'You cannot delete your own account.')
    return
  }
  adminToDelete.value = admin
  showDeleteDialog.value = true
}

const performDelete = async () => {
  if (!adminToDelete.value) return
  deleting.value = true
  try {
    await axios.delete(`/api/admin/students/${adminToDelete.value.id}`)
    toastSuccess('Administrator deleted successfully.')
    showDeleteDialog.value = false
    broadcastSync('students_updated')
    await loadData()
  } catch (err) {
    toastError(err.response?.data?.message || 'Failed to delete administrator.')
  } finally {
    deleting.value = false
  }
}

const loadData = async (isBackground = false) => {
  try {
    const res = await axios.get('/api/admin/students')
    adminsList.value = res.data.admins || []
  } catch (err) {
    if (!isBackground) {
      console.error('Failed to load administrators', err)
    }
  } finally {
    initialLoading.value = false
  }
}

onMounted(() => {
  loadData()
})

useRealtimePoll(loadData, { interval: 4000, listenEvents: ['students_updated'] })
</script>
