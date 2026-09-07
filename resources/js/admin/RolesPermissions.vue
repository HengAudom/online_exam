<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
            <span class="material-symbols-outlined text-xs mr-1">security</span>
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
          variant="primary"
          icon="save"
          :loading="saving"
          @click="savePermissions"
        >
          {{ t.saveMatrix }}
        </Button>
      </div>
    </div>

    <!-- Role Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <Card
        v-for="role in roles"
        :key="role.name"
        padding="normal"
        class="shadow-soft-sm relative overflow-hidden"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-3">
            <div
              :class="[
                'flex h-10 w-10 items-center justify-center rounded-xl font-bold text-sm shrink-0',
                role.name === 'Super Admin'
                  ? 'bg-purple-100 text-purple-700 border border-purple-200'
                  : role.name === 'Admin'
                  ? 'bg-blue-100 text-blue-700 border border-blue-200'
                  : 'bg-emerald-100 text-emerald-700 border border-emerald-200'
              ]"
            >
              <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">
                {{ role.name === 'Super Admin' ? 'admin_panel_settings' : role.name === 'Admin' ? 'manage_accounts' : 'school' }}
              </span>
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">{{ role.name }}</h3>
              <p class="text-xs text-slate-500">{{ role.usersCount }} {{ t.assignedUsers }}</p>
            </div>
          </div>
          <Badge :variant="role.name === 'Super Admin' ? 'purple' : role.name === 'Admin' ? 'primary' : 'success'" size="xs">
            {{ role.name === 'Super Admin' ? 'Root' : 'Standard' }}
          </Badge>
        </div>

        <p class="text-xs text-slate-600 mt-3 line-clamp-2 leading-relaxed">
          {{ role.description }}
        </p>
      </Card>
    </div>

    <!-- Permission Matrix Card -->
    <Card
      :title="t.matrixTitle"
      :subtitle="t.matrixSubtitle"
      padding="none"
      class="shadow-soft-sm overflow-hidden"
    >
      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/70">
            <tr>
              <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.module }}</th>
              <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.view }}</th>
              <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.create }}</th>
              <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.edit }}</th>
              <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.delete }}</th>
              <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.export }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="item in permissions" :key="item.module" class="hover:bg-slate-50/70 transition-colors">
              <td class="px-6 py-4 font-bold text-slate-900 flex items-center gap-2.5">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                <span>{{ item.module }}</span>
              </td>

              <!-- View Checkbox -->
              <td class="px-6 py-4 text-center">
                <input
                  type="checkbox"
                  v-model="item.view"
                  class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
                />
              </td>

              <!-- Create Checkbox -->
              <td class="px-6 py-4 text-center">
                <input
                  type="checkbox"
                  v-model="item.create"
                  class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
                />
              </td>

              <!-- Edit Checkbox -->
              <td class="px-6 py-4 text-center">
                <input
                  type="checkbox"
                  v-model="item.edit"
                  class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
                />
              </td>

              <!-- Delete Checkbox -->
              <td class="px-6 py-4 text-center">
                <input
                  type="checkbox"
                  v-model="item.delete"
                  class="h-4 w-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer"
                />
              </td>

              <!-- Export Checkbox -->
              <td class="px-6 py-4 text-center">
                <input
                  type="checkbox"
                  v-model="item.export"
                  class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 cursor-pointer"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card List View -->
      <div class="md:hidden divide-y divide-slate-100">
        <div
          v-for="item in permissions"
          :key="item.module"
          class="p-4 space-y-3"
        >
          <!-- Module Header -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
              <h4 class="font-bold text-slate-900 text-sm">{{ item.module }}</h4>
            </div>
            <!-- Quick Toggle All for this module -->
            <button
              type="button"
              class="text-[11px] font-bold text-blue-600 hover:text-blue-700 active:scale-95 transition-transform"
              @click="toggleAllForModule(item)"
            >
              {{ isAllSelected(item) ? (lang === 'kh' ? 'ដោះទាំងអស់' : 'Deselect All') : (lang === 'kh' ? 'ជ្រើសទាំងអស់' : 'Select All') }}
            </button>
          </div>

          <!-- 5 Permission Toggles in a 2-col / 3-col Grid -->
          <div class="grid grid-cols-2 gap-2">
            <!-- View -->
            <label
              :class="[
                'flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all select-none',
                item.view
                  ? 'bg-blue-50/80 border-blue-200 text-blue-900 shadow-soft-xs'
                  : 'bg-slate-50/60 border-slate-200/80 text-slate-500 hover:bg-slate-100/60'
              ]"
            >
              <input
                type="checkbox"
                v-model="item.view"
                class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
              />
              <span class="truncate">{{ t.viewShort }}</span>
            </label>

            <!-- Create -->
            <label
              :class="[
                'flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all select-none',
                item.create
                  ? 'bg-blue-50/80 border-blue-200 text-blue-900 shadow-soft-xs'
                  : 'bg-slate-50/60 border-slate-200/80 text-slate-500 hover:bg-slate-100/60'
              ]"
            >
              <input
                type="checkbox"
                v-model="item.create"
                class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
              />
              <span class="truncate">{{ t.createShort }}</span>
            </label>

            <!-- Edit -->
            <label
              :class="[
                'flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all select-none',
                item.edit
                  ? 'bg-blue-50/80 border-blue-200 text-blue-900 shadow-soft-xs'
                  : 'bg-slate-50/60 border-slate-200/80 text-slate-500 hover:bg-slate-100/60'
              ]"
            >
              <input
                type="checkbox"
                v-model="item.edit"
                class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer"
              />
              <span class="truncate">{{ t.editShort }}</span>
            </label>

            <!-- Delete -->
            <label
              :class="[
                'flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all select-none',
                item.delete
                  ? 'bg-rose-50/80 border-rose-200 text-rose-900 shadow-soft-xs'
                  : 'bg-slate-50/60 border-slate-200/80 text-slate-500 hover:bg-slate-100/60'
              ]"
            >
              <input
                type="checkbox"
                v-model="item.delete"
                class="h-4 w-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 cursor-pointer"
              />
              <span class="truncate">{{ t.deleteShort }}</span>
            </label>

            <!-- Export -->
            <label
              :class="[
                'col-span-2 flex items-center gap-2 p-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all select-none',
                item.export
                  ? 'bg-indigo-50/80 border-indigo-200 text-indigo-900 shadow-soft-xs'
                  : 'bg-slate-50/60 border-slate-200/80 text-slate-500 hover:bg-slate-100/60'
              ]"
            >
              <input
                type="checkbox"
                v-model="item.export"
                class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 cursor-pointer"
              />
              <span class="truncate">{{ t.exportShort }}</span>
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <p class="text-xs text-slate-500">
            {{ t.footerNotice }}
          </p>
          <Button
            variant="primary"
            size="sm"
            icon="save"
            :loading="saving"
            class="whitespace-nowrap shrink-0 self-end sm:self-auto"
            @click="savePermissions"
          >
            {{ t.saveMatrix }}
          </Button>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Badge from '../components/ui/Badge.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { fetchUser } = usePermissions()

const roles = ref([])
const permissions = ref([])
const saving = ref(false)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'តួនាទី & សិទ្ធិប្រើប្រាស់',
      subtitle: 'កំណត់សមត្ថភាព និងសិទ្ធិប្រើប្រាស់តាមម៉ូឌុលសម្រាប់គណនីអ្នកគ្រប់គ្រងនីមួយៗ',
      saveMatrix: 'រក្សាទុកតារាងសិទ្ធិ',
      assignedUsers: 'គណនីប្រើប្រាស់',
      matrixTitle: 'តារាងកំណត់សិទ្ធិប្រើប្រាស់',
      matrixSubtitle: 'កំណត់សិទ្ធិ មើល, បង្កើត, កែសម្រួល, លុប និង នាំចេញទិន្នន័យ សម្រាប់អ្នកគ្រប់គ្រង',
      module: 'ម៉ូឌុល',
      view: 'មើល',
      create: 'បង្កើត',
      edit: 'កែសម្រួល',
      delete: 'លុប',
      export: 'នាំចេញ',
      viewShort: 'មើល',
      createShort: 'បង្កើត',
      editShort: 'កែសម្រួល',
      deleteShort: 'លុប',
      exportShort: 'នាំចេញ',
      footerNotice: 'ការផ្លាស់ប្តូរសិទ្ធិនឹងអនុវត្តភ្លាមៗចំពោះគណនី Admin ទាំងអស់ក្នុងប្រព័ន្ធ។'
    }
  }
  return {
    title: 'Roles & Permissions',
    subtitle: 'Configure operational capabilities and access matrices for admin roles',
    saveMatrix: 'Save Permissions',
    assignedUsers: 'assigned accounts',
    matrixTitle: 'Admin Permission Matrix',
    matrixSubtitle: 'Define View, Create, Edit, Delete, and Export capabilities per module',
    module: 'Module',
    view: 'View',
    create: 'Create',
    edit: 'Edit',
    delete: 'Delete',
    export: 'Export',
    viewShort: 'View',
    createShort: 'Create',
    editShort: 'Edit',
    deleteShort: 'Delete',
    exportShort: 'Export',
    footerNotice: 'Permission modifications are enforced system-wide across all active sessions.'
  }
})

const isAllSelected = (item) => {
  return item.view && item.create && item.edit && item.delete && item.export
}

const toggleAllForModule = (item) => {
  const target = !isAllSelected(item)
  item.view = target
  item.create = target
  item.edit = target
  item.delete = target
  item.export = target
}

const loadData = async () => {
  try {
    const res = await axios.get('/api/admin/roles-permissions')
    roles.value = res.data.roles || []
    permissions.value = res.data.permissions || res.data.matrix || []
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចទាញយកតារាងសិទ្ធិបានទេ' : 'Failed to load roles and permissions.')
  }
}

const savePermissions = async () => {
  saving.value = true
  try {
    await axios.post('/api/admin/roles-permissions', {
      permissions: permissions.value,
      matrix: permissions.value
    })
    toastSuccess(lang.value === 'kh' ? 'បានរក្សាទុកតារាងសិទ្ធិជោគជ័យ!' : 'Roles and permissions saved successfully!')
    await fetchUser(true)
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចរក្សាទុកតារាងសិទ្ធិបានទេ' : 'Failed to update permissions matrix.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
