<template>
  <div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-2">

      <!-- ── Skills Panel ──────────────────────────────────────── -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">category</span>
          <div>
            <h2 class="font-manrope font-bold text-slate-900">Skills</h2>
            <p class="text-xs text-slate-400">{{ skills.length }} skill areas</p>
          </div>
        </div>

        <div class="flex gap-3 mb-4">
          <input
            v-model="newSkillName"
            type="text"
            :placeholder="editingSkill ? 'Edit skill name...' : 'New skill name…'"
            @keyup.enter="saveSkill"
            class="field flex-1"
          />
          <button v-if="editingSkill" @click="cancelEditSkill" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
          <button
            @click="saveSkill"
            class="flex items-center gap-1 rounded-xl bg-[#00288e] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1e40af]"
          >
            <span class="material-symbols-outlined text-lg">{{ editingSkill ? 'save' : 'add' }}</span>
            {{ editingSkill ? 'Update' : 'Add' }}
          </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Skill Name</th>
                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="skill in skills" :key="skill.SkillId" class="hover:bg-blue-50/20">
                <td class="px-4 py-3">
                  <span class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-[#00288e]"></span>
                    {{ skill.SkillName }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="editSkill(skill)"
                      class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-[#00288e] hover:text-white transition"
                    >
                      <span class="material-symbols-outlined text-sm align-middle">edit</span>
                    </button>
                    <button
                      @click="deleteSkill(skill)"
                      class="rounded-lg bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-500 hover:bg-red-500 hover:text-white transition"
                    >
                      <span class="material-symbols-outlined text-sm align-middle">delete</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="skills.length === 0">
                <td colspan="2" class="px-4 py-8 text-center text-sm text-slate-400">No skills found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Batches Panel ─────────────────────────────────────── -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-6">
          <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">groups</span>
          <div>
            <h2 class="font-manrope font-bold text-slate-900">Batches</h2>
            <p class="text-xs text-slate-400">{{ batches.length }} batch groups</p>
          </div>
        </div>

        <div class="grid gap-3 mb-4">
          <input v-model="newBatch.name" type="text" :placeholder="editingBatch ? 'Edit batch name...' : 'Batch name…'" class="field" />
          <div class="grid grid-cols-2 gap-3">
            <div>
              <span class="label-text">Start Date</span>
              <input v-model="newBatch.startDate" type="date" class="field" />
            </div>
            <div>
              <span class="label-text">End Date</span>
              <input v-model="newBatch.endDate" type="date" class="field" />
            </div>
          </div>
          <div class="flex gap-3">
            <button v-if="editingBatch" @click="cancelEditBatch" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
            <button
              @click="saveBatch"
              class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-[#00288e] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1e40af]"
            >
              <span class="material-symbols-outlined text-lg">{{ editingBatch ? 'save' : 'add' }}</span>
              {{ editingBatch ? 'Save Changes' : 'Add Batch' }}
            </button>
          </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Batch</th>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Start</th>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">End</th>
                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="batch in batches" :key="batch.BatchId" class="hover:bg-blue-50/20">
                <td class="px-4 py-3 font-medium text-slate-800">{{ batch.BatchName }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ batch.StartDate }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ batch.EndDate }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="editBatch(batch)"
                      class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-[#00288e] hover:text-white transition"
                    >
                      <span class="material-symbols-outlined text-sm align-middle">edit</span>
                    </button>
                    <button
                      @click="deleteBatch(batch)"
                      class="rounded-lg bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-500 hover:bg-red-500 hover:text-white transition"
                    >
                      <span class="material-symbols-outlined text-sm align-middle">delete</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="batches.length === 0">
                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">No batches yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div
      v-if="toast"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-3 rounded-2xl px-6 py-3 text-white text-sm font-semibold shadow-2xl z-50 transition-all"
      :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
    >
      <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">
        {{ toast.type === 'success' ? 'check_circle' : 'error' }}
      </span>
      {{ toast.message }}
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const skills  = ref([])
const batches = ref([])
const newSkillName = ref('')
const newBatch = ref({ name: '', startDate: '', endDate: '' })
const toast = ref(null)

const editingSkill = ref(null)
const editingBatch = ref(null)

const showToast = (message, type = 'success') => {
  toast.value = { message, type }
  setTimeout(() => { toast.value = null }, 3500)
}

const editSkill = (skill) => {
  editingSkill.value = skill
  newSkillName.value = skill.SkillName
}

const cancelEditSkill = () => {
  editingSkill.value = null
  newSkillName.value = ''
}

const saveSkill = async () => {
  const name = newSkillName.value.trim()
  if (!name) return showToast('Enter skill name.', 'error')
  try {
    if (editingSkill.value) {
      const res = await axios.put(`/api/admin/skills/${editingSkill.value.SkillId}`, { name })
      const s = skills.value.find(x => x.SkillId === editingSkill.value.SkillId)
      if (s) s.SkillName = res.data.skill.SkillName
      showToast('Skill updated.')
      cancelEditSkill()
    } else {
      const res = await axios.post('/api/admin/skills', { name })
      skills.value.unshift({ SkillId: res.data.skill.SkillId, SkillName: res.data.skill.SkillName })
      newSkillName.value = ''
      showToast('Skill added.')
    }
  } catch (e) { showToast(e.response?.data?.message || 'Failed to save skill.', 'error') }
}

const deleteSkill = async (skill) => {
  if (!confirm(`Delete skill "${skill.SkillName}"?`)) return
  if (editingSkill.value?.SkillId === skill.SkillId) cancelEditSkill()
  try {
    await axios.delete(`/api/admin/skills/${skill.SkillId}`)
    skills.value = skills.value.filter(s => s.SkillId !== skill.SkillId)
    showToast('Skill deleted.')
  } catch (e) { showToast('Failed to delete skill.', 'error') }
}

const editBatch = (batch) => {
  editingBatch.value = batch
  newBatch.value = { name: batch.BatchName, startDate: batch.StartDate, endDate: batch.EndDate }
}

const cancelEditBatch = () => {
  editingBatch.value = null
  newBatch.value = { name: '', startDate: '', endDate: '' }
}

const saveBatch = async () => {
  const { name, startDate, endDate } = newBatch.value
  if (!name.trim() || !startDate || !endDate) return showToast('Fill in all batch fields.', 'error')
  try {
    if (editingBatch.value) {
      const res = await axios.put(`/api/admin/batches/${editingBatch.value.BatchId}`, { name, startDate, endDate })
      const b = batches.value.find(x => x.BatchId === editingBatch.value.BatchId)
      if (b) {
         b.BatchName = res.data.batch.BatchName
         b.StartDate = res.data.batch.StartDate
         b.EndDate   = res.data.batch.EndDate
      }
      showToast('Batch updated.')
      cancelEditBatch()
    } else {
      const res = await axios.post('/api/admin/batches', { name, startDate, endDate })
      batches.value.unshift({
        BatchId: res.data.batch.BatchId,
        BatchName: res.data.batch.BatchName,
        StartDate: res.data.batch.StartDate,
        EndDate: res.data.batch.EndDate
      })
      newBatch.value = { name: '', startDate: '', endDate: '' }
      showToast('Batch added.')
    }
  } catch (e) { showToast(e.response?.data?.message || 'Failed to save batch.', 'error') }
}

const deleteBatch = async (batch) => {
  if (!confirm(`Delete batch "${batch.BatchName}"?`)) return
  try {
    await axios.delete(`/api/admin/batches/${batch.BatchId}`)
    batches.value = batches.value.filter(b => b.BatchId !== batch.BatchId)
    showToast('Batch deleted.')
  } catch (e) { showToast('Failed to delete batch.', 'error') }
}

onMounted(async () => {
  try {
    const res = await axios.get('/api/admin/skills-batches')
    skills.value  = res.data.skills
    batches.value = res.data.batches
  } catch (e) { console.error(e) }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
.field {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background-color: #f8fafc;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  outline: none;
  transition: border-color 150ms, background-color 150ms;
}
.field:focus {
  border-color: #00288e;
  background-color: #fff;
}
.label-text {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin-bottom: 0.25rem;
}
</style>
