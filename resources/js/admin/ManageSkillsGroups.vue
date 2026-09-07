<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.subtitle }}
        </p>
      </div>
    </div>

    <!-- Mobile Category Tabs (Mobile only) -->
    <div class="lg:hidden">
      <Tabs
        v-model="activeMobileTab"
        :tabs="[
          { label: t.skills, value: 'skills', icon: 'category', count: skills?.length || 0 },
          { label: t.groups, value: 'groups', icon: 'groups', count: groups?.length || 0 },
          { label: t.durations, value: 'durations', icon: 'schedule', count: durations?.length || 0 }
        ]"
      />
    </div>

    <!-- 3-Column Desktop Grid / Active Tab on Mobile -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

      <!-- ── Panel 1: Skills ───────────────────────────────────────── -->
      <Card
        v-show="activeMobileTab === 'skills' || isDesktop"
        :title="t.skills"
        :subtitle="`${skills?.length || 0} ${t.skillAreas}`"
        padding="normal"
        class="shadow-soft-sm"
      >
        <template #actions>
          <span class="rounded-full bg-blue-50 text-blue-700 font-bold px-2.5 py-0.5 text-xs">
            {{ skills?.length || 0 }}
          </span>
        </template>

        <!-- Inline Add / Edit Input -->
        <div v-if="editingSkill ? can('Skills & Groups', 'edit') : can('Skills & Groups', 'create')" class="flex gap-2 mb-4">
          <Input
            v-model="newSkillName"
            :placeholder="editingSkill ? t.editSkillPlaceholder : t.newSkillPlaceholder"
            @keyup.enter="saveSkill"
          />
          <Button
            v-if="editingSkill"
            variant="outline"
            size="sm"
            @click="cancelEditSkill"
          >
            {{ t.cancel }}
          </Button>
          <Button
            variant="primary"
            size="sm"
            :icon="editingSkill ? 'save' : 'add'"
            @click="saveSkill"
          >
            {{ editingSkill ? t.update : t.add }}
          </Button>
        </div>

        <!-- Skills Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50/70">
              <tr>
                <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.skillName }}</th>
                <th v-if="can('Skills & Groups', 'edit') || can('Skills & Groups', 'delete')" class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.action }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="skill in skills" :key="skill.SkillId" class="hover:bg-slate-50/70 transition-colors">
                <td class="px-4 py-3 font-semibold text-slate-800">
                  <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                    {{ skill.SkillName }}
                  </div>
                </td>
                <td v-if="can('Skills & Groups', 'edit') || can('Skills & Groups', 'delete')" class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <IconButton
                      v-if="can('Skills & Groups', 'edit')"
                      icon="edit"
                      variant="ghost"
                      size="xs"
                      title="Edit Skill"
                      @click="editSkill(skill)"
                    />
                    <IconButton
                      v-if="can('Skills & Groups', 'delete')"
                      icon="delete"
                      variant="ghost"
                      size="xs"
                      title="Delete Skill"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                      @click="confirmDelete('skill', skill)"
                    />
                  </div>
                </td>
              </tr>
              <tr v-if="skills.length === 0">
                <td colspan="2" class="px-4 py-6 text-center text-xs text-slate-400">
                  {{ t.noSkills }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- ── Panel 2: Study Groups ─────────────────────────────────── -->
      <Card
        v-show="activeMobileTab === 'groups' || isDesktop"
        :title="t.groups"
        :subtitle="`${groups?.length || 0} ${t.groupCount}`"
        padding="normal"
        class="shadow-soft-sm"
      >
        <template #actions>
          <span class="rounded-full bg-purple-50 text-purple-700 font-bold px-2.5 py-0.5 text-xs">
            {{ groups?.length || 0 }}
          </span>
        </template>

        <!-- Inline Add / Edit Input -->
        <div v-if="editingGroup ? can('Skills & Groups', 'edit') : can('Skills & Groups', 'create')" class="flex gap-2 mb-4">
          <Input
            v-model="newGroupName"
            :placeholder="editingGroup ? t.editGroupPlaceholder : t.newGroupPlaceholder"
            @keyup.enter="saveGroup"
          />
          <Button
            v-if="editingGroup"
            variant="outline"
            size="sm"
            @click="cancelEditGroup"
          >
            {{ t.cancel }}
          </Button>
          <Button
            variant="primary"
            size="sm"
            :icon="editingGroup ? 'save' : 'add'"
            @click="saveGroup"
          >
            {{ editingGroup ? t.update : t.add }}
          </Button>
        </div>

        <!-- Groups Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50/70">
              <tr>
                <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.groupName }}</th>
                <th v-if="can('Skills & Groups', 'edit') || can('Skills & Groups', 'delete')" class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.action }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="group in groups" :key="group.GroupId" class="hover:bg-slate-50/70 transition-colors">
                <td class="px-4 py-3 font-semibold text-slate-800">
                  <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-purple-600"></span>
                    {{ group.GroupName }}
                  </div>
                </td>
                <td v-if="can('Skills & Groups', 'edit') || can('Skills & Groups', 'delete')" class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <IconButton
                      v-if="can('Skills & Groups', 'edit')"
                      icon="edit"
                      variant="ghost"
                      size="xs"
                      title="Edit Group"
                      @click="editGroup(group)"
                    />
                    <IconButton
                      v-if="can('Skills & Groups', 'delete')"
                      icon="delete"
                      variant="ghost"
                      size="xs"
                      title="Delete Group"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                      @click="confirmDelete('group', group)"
                    />
                  </div>
                </td>
              </tr>
              <tr v-if="groups.length === 0">
                <td colspan="2" class="px-4 py-6 text-center text-xs text-slate-400">
                  {{ t.noGroups }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <!-- ── Panel 3: Study Durations ──────────────────────────────── -->
      <Card
        v-show="activeMobileTab === 'durations' || isDesktop"
        :title="t.durations"
        :subtitle="`${durations?.length || 0} ${t.durationCount}`"
        padding="normal"
        class="shadow-soft-sm"
      >
        <template #actions>
          <span class="rounded-full bg-emerald-50 text-emerald-700 font-bold px-2.5 py-0.5 text-xs">
            {{ durations?.length || 0 }}
          </span>
        </template>

        <!-- Inline Add / Edit Input -->
        <div v-if="editingDuration ? can('Skills & Groups', 'edit') : can('Skills & Groups', 'create')" class="flex gap-2 mb-4">
          <Input
            v-model="newDurationName"
            :placeholder="editingDuration ? t.editDurationPlaceholder : t.newDurationPlaceholder"
            @keyup.enter="saveDuration"
          />
          <Button
            v-if="editingDuration"
            variant="outline"
            size="sm"
            @click="cancelEditDuration"
          >
            {{ t.cancel }}
          </Button>
          <Button
            variant="primary"
            size="sm"
            :icon="editingDuration ? 'save' : 'add'"
            @click="saveDuration"
          >
            {{ editingDuration ? t.update : t.add }}
          </Button>
        </div>

        <!-- Durations Table -->
        <div class="overflow-hidden rounded-xl border border-slate-200/80">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50/70">
              <tr>
                <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.durationName }}</th>
                <th v-if="can('Skills & Groups', 'edit') || can('Skills & Groups', 'delete')" class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.action }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="duration in durations" :key="duration.DurationId" class="hover:bg-slate-50/70 transition-colors">
                <td class="px-4 py-3 font-semibold text-slate-800">
                  <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                    {{ duration.DurationName }}
                  </div>
                </td>
                <td v-if="can('Skills & Groups', 'edit') || can('Skills & Groups', 'delete')" class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <IconButton
                      v-if="can('Skills & Groups', 'edit')"
                      icon="edit"
                      variant="ghost"
                      size="xs"
                      title="Edit Duration"
                      @click="editDuration(duration)"
                    />
                    <IconButton
                      v-if="can('Skills & Groups', 'delete')"
                      icon="delete"
                      variant="ghost"
                      size="xs"
                      title="Delete Duration"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                      @click="confirmDelete('duration', duration)"
                    />
                  </div>
                </td>
              </tr>
              <tr v-if="durations.length === 0">
                <td colspan="2" class="px-4 py-6 text-center text-xs text-slate-400">
                  {{ t.noDurations }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>

    <!-- ── Delete Confirm Dialog ───────────────────────────────────── -->
    <ConfirmDialog
      v-model="showDeleteModal"
      :title="t.deleteTitle"
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
import { computed, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import Input from '../components/ui/Input.vue'
import Tabs from '../components/ui/Tabs.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'
import { logActivity } from '../utils/activityLog'
import { useRealtimePoll, broadcastSync } from '../composables/useRealtimePoll'
import { fastCache } from '../stores/fastCache'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { can, fetchUser } = usePermissions()

const activeMobileTab = ref('skills')
const isDesktop = ref(window.innerWidth >= 1024)

const skills = ref([])
const groups = ref([])
const durations = ref([])

const newSkillName = ref('')
const editingSkill = ref(null)

const newGroupName = ref('')
const editingGroup = ref(null)

const newDurationName = ref('')
const editingDuration = ref(null)

const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const itemTypeToDelete = ref('')
const deleting = ref(false)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'ជំនាញ ក្រុម និង រយៈពេលសិក្សា',
      subtitle: 'រៀបចំជំនាញសិក្សា ក្រុមសិស្ស និងរយៈពេលវគ្គបណ្ដុះបណ្ដាល',
      skills: 'ជំនាញ',
      skillAreas: 'ជំនាញបច្ចុប្បន្ន',
      newSkillPlaceholder: 'ឈ្មោះជំនាញថ្មី...',
      editSkillPlaceholder: 'កែប្រែឈ្មោះជំនាញ...',
      groups: 'ក្រុមសិក្សា',
      groupCount: 'ក្រុមសរុប',
      newGroupPlaceholder: 'ឈ្មោះក្រុមថ្មី...',
      editGroupPlaceholder: 'កែប្រែឈ្មោះក្រុម...',
      durations: 'រយៈពេលសិក្សា',
      durationCount: 'ជម្រើសរយៈពេល',
      newDurationPlaceholder: 'រយៈពេលថ្មី (ឧ. ៣ ខែ)...',
      editDurationPlaceholder: 'កែប្រែរយៈពេល...',
      skillName: 'ឈ្មោះជំនាញ',
      groupName: 'ឈ្មោះក្រុម',
      durationName: 'រយៈពេល',
      action: 'សកម្មភាព',
      add: 'បន្ថែម',
      update: 'រក្សាទុក',
      cancel: 'បោះបង់',
      delete: 'លុប',
      deleteTitle: 'លុបទិន្នន័យ?',
      noSkills: 'មិនទាន់មានជំនាញនៅឡើយទេ',
      noGroups: 'មិនទាន់មានក្រុមនៅឡើយទេ',
      noDurations: 'មិនទាន់មានរយៈពេលសិក្សានៅឡើយទេ'
    }
  }
  return {
    title: 'Skills, Groups & Durations',
    subtitle: 'Configure academic departments, class cohorts, and course periods',
    skills: 'Skills',
    skillAreas: 'active skills',
    newSkillPlaceholder: 'New skill name...',
    editSkillPlaceholder: 'Edit skill name...',
    groups: 'Study Groups',
    groupCount: 'total cohorts',
    newGroupPlaceholder: 'New group name...',
    editGroupPlaceholder: 'Edit group name...',
    durations: 'Study Durations',
    durationCount: 'period options',
    newDurationPlaceholder: 'New duration (e.g. 3 Months)...',
    editDurationPlaceholder: 'Edit duration...',
    skillName: 'Skill Title',
    groupName: 'Group Name',
    durationName: 'Duration',
    action: 'Action',
    add: 'Add',
    update: 'Update',
    cancel: 'Cancel',
    delete: 'Delete',
    deleteTitle: 'Confirm Delete',
    noSkills: 'No skills found',
    noGroups: 'No groups found',
    noDurations: 'No duration options found'
  }
})

const deleteConfirmMessage = computed(() => {
  if (!itemToDelete.value) return ''
  const name = itemToDelete.value.SkillName || itemToDelete.value.GroupName || itemToDelete.value.DurationName || ''
  return lang.value === 'kh'
    ? `តើអ្នកពិតជាចង់លុប "${name}" មែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។`
    : `Are you sure you want to delete "${name}"? This cannot be undone.`
})

const loadData = async () => {
  try {
    const res = await axios.get('/api/admin/skills-groups')
    skills.value = res.data.skills || []
    groups.value = res.data.groups || []
    durations.value = res.data.durations || []

    fastCache.set('skills', skills.value)
    fastCache.set('groups', groups.value)
    fastCache.set('durations', durations.value)
  } catch (e) {
    console.error('Failed to load skills & groups', e)
  }
}

useRealtimePoll(loadData, { interval: 5000, listenEvents: ['skills_groups_updated'] })

// ── Skills CRUD ───────────────────────────────────────────────────────────────
const saveSkill = async () => {
  if (!newSkillName.value.trim()) return
  try {
    if (editingSkill.value) {
      await axios.put(`/api/admin/skills/${editingSkill.value.SkillId}`, {
        name: newSkillName.value.trim()
      })
      toastSuccess('Skill updated successfully!')
      editingSkill.value = null
    } else {
      await axios.post('/api/admin/skills', {
        name: newSkillName.value.trim()
      })
      toastSuccess('Skill added successfully!')
      logActivity(`New skill created: ${newSkillName.value.trim()}`, 'Skill area')
    }
    newSkillName.value = ''
    broadcastSync('skills_groups_updated')
    broadcastSync('students_updated')
    broadcastSync('tests_updated')
    await loadData()
  } catch (e) {
    toastError(e.response?.data?.message || 'Failed to save skill.')
  }
}

const editSkill = (skill) => {
  editingSkill.value = skill
  newSkillName.value = skill.SkillName
}

const cancelEditSkill = () => {
  editingSkill.value = null
  newSkillName.value = ''
}

// ── Groups CRUD ───────────────────────────────────────────────────────────────
const saveGroup = async () => {
  if (!newGroupName.value.trim()) return
  try {
    if (editingGroup.value) {
      await axios.put(`/api/admin/groups/${editingGroup.value.GroupId}`, {
        name: newGroupName.value.trim()
      })
      toastSuccess('Group updated successfully!')
      editingGroup.value = null
    } else {
      await axios.post('/api/admin/groups', {
        name: newGroupName.value.trim()
      })
      toastSuccess('Group added successfully!')
      logActivity(`New group created: ${newGroupName.value.trim()}`, 'Study cohort')
    }
    newGroupName.value = ''
    broadcastSync('skills_groups_updated')
    broadcastSync('students_updated')
    broadcastSync('tests_updated')
    await loadData()
  } catch (e) {
    toastError(e.response?.data?.message || 'Failed to save group.')
  }
}

const editGroup = (group) => {
  editingGroup.value = group
  newGroupName.value = group.GroupName
}

const cancelEditGroup = () => {
  editingGroup.value = null
  newGroupName.value = ''
}

// ── Durations CRUD ────────────────────────────────────────────────────────────
const saveDuration = async () => {
  if (!newDurationName.value.trim()) return
  try {
    if (editingDuration.value) {
      await axios.put(`/api/admin/durations/${editingDuration.value.DurationId}`, {
        name: newDurationName.value.trim()
      })
      toastSuccess('Duration updated successfully!')
      editingDuration.value = null
    } else {
      await axios.post('/api/admin/durations', {
        name: newDurationName.value.trim()
      })
      toastSuccess('Duration added successfully!')
    }
    newDurationName.value = ''
    broadcastSync('skills_groups_updated')
    broadcastSync('students_updated')
    await loadData()
  } catch (e) {
    toastError(e.response?.data?.message || 'Failed to save duration.')
  }
}

const editDuration = (duration) => {
  editingDuration.value = duration
  newDurationName.value = duration.DurationName
}

const cancelEditDuration = () => {
  editingDuration.value = null
  newDurationName.value = ''
}

// ── Delete Confirmation ───────────────────────────────────────────────────────
const confirmDelete = (type, item) => {
  itemTypeToDelete.value = type
  itemToDelete.value = item
  showDeleteModal.value = true
}

const performDelete = async () => {
  if (!itemToDelete.value) return
  deleting.value = true
  try {
    if (itemTypeToDelete.value === 'skill') {
      await axios.delete(`/api/admin/skills/${itemToDelete.value.SkillId}`)
      toastSuccess('Skill deleted successfully.')
    } else if (itemTypeToDelete.value === 'group') {
      await axios.delete(`/api/admin/groups/${itemToDelete.value.GroupId}`)
      toastSuccess('Group deleted successfully.')
    } else if (itemTypeToDelete.value === 'duration') {
      await axios.delete(`/api/admin/durations/${itemToDelete.value.DurationId}`)
      toastSuccess('Duration deleted successfully.')
    }
    showDeleteModal.value = false
    broadcastSync('skills_groups_updated')
    broadcastSync('students_updated')
    broadcastSync('tests_updated')
    await loadData()
  } catch (e) {
    toastError(e.response?.data?.message || 'Failed to delete item.')
  } finally {
    deleting.value = false
  }
}

const handleResize = () => {
  isDesktop.value = window.innerWidth >= 1024
}

onMounted(() => {
  fetchUser()
  loadData()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>
