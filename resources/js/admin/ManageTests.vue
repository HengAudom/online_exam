<template>
  <div class="space-y-6">

    <!-- Create Test Form -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h2 class="font-manrope text-lg font-bold text-slate-900">{{ editingTestId ? 'Edit Test' : 'Create New Test' }}</h2>
          <p class="text-sm text-slate-500">{{ editingTestId ? 'Modify this exam\'s details and questions.' : 'Build an exam with questions and correct answers.' }}</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            v-if="editingTestId || hasFormData"
            @click="cancelEdit"
            class="flex items-center gap-2 rounded-xl border-2 border-red-200 px-5 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50"
            :title="editingTestId ? 'Cancel editing and reset' : 'Clear all fields'"
          >
            <span class="material-symbols-outlined text-xl">{{ editingTestId ? 'close' : 'backspace' }}</span>
            {{ editingTestId ? 'Cancel Edit' : 'Clear Form' }}
          </button>
          <button
            @click="handleSaveTest('Draft')"
            class="flex items-center gap-2 rounded-xl border-2 border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            <span class="material-symbols-outlined text-xl">draft</span>
            {{ editingTestId ? 'Update Draft' : 'Save Draft' }}
          </button>
          <button
            @click="handleSaveTest('Published')"
            class="flex items-center gap-2 rounded-xl bg-[#00288e] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1e40af]"
          >
            <span class="material-symbols-outlined text-xl">publish</span>
            {{ editingTestId ? 'Update & Publish' : 'Confirm & Publish' }}
          </button>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-4">
        <label class="block space-y-1 md:col-span-1">
          <span class="label-text">Test Name</span>
          <input v-model="testName" type="text" class="field" placeholder="e.g. Front-End Fundamentals" />
        </label>
        <label class="block space-y-1">
          <span class="label-text">Skill</span>
          <select v-model="selectedSkillId" class="field">
            <option value="" disabled>Select skill…</option>
            <option v-for="s in skills" :key="s.SkillId" :value="s.SkillId">{{ s.SkillName }}</option>
          </select>
        </label>
        <label class="block space-y-1">
          <span class="label-text">Batch</span>
          <select v-model="selectedBatchId" class="field">
            <option value="">All Batches</option>
            <option v-for="b in batches" :key="b.BatchId" :value="b.BatchId">{{ b.BatchName }}</option>
          </select>
        </label>
        <label class="block space-y-1">
          <span class="label-text">Scheduled At</span>
          <input v-model="scheduledAt" type="datetime-local" class="field" />
        </label>
        <label class="block space-y-1">
          <span class="label-text">Finished At</span>
          <input v-model="finishedAt" type="datetime-local" class="field" />
        </label>
        <div class="grid grid-cols-2 gap-4">
          <label class="block space-y-1">
            <span class="label-text">Duration (min)</span>
            <input v-model.number="durationMinutes" type="number" min="5" class="field" />
          </label>
          <label class="block space-y-1">
            <span class="label-text">Total Marks</span>
            <input v-model.number="totalMarks" type="number" readonly class="field bg-slate-50 cursor-not-allowed" title="Calculated from question points" />
          </label>
        </div>
      </div>

      <!-- Toast feedback -->
      <div v-if="toast" class="mt-4 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold"
        :class="toast.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">
          {{ toast.type === 'success' ? 'check_circle' : 'error' }}
        </span>
        {{ toast.message }}
      </div>
    </div>

    <!-- Questions Builder -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h2 class="font-manrope text-lg font-bold text-slate-900">Questions
            <span class="ml-2 rounded-full bg-[#00288e]/10 px-2.5 py-0.5 text-sm text-[#00288e] font-semibold">{{ questions.length }}</span>
          </h2>
          <p class="text-sm text-slate-500">Add questions with four options and mark the correct answer.</p>
        </div>
        <button
          @click="addQuestion"
          class="flex items-center gap-2 rounded-xl border-2 border-[#00288e] px-5 py-2.5 text-sm font-semibold text-[#00288e] transition hover:bg-[#00288e] hover:text-white"
        >
          <span class="material-symbols-outlined text-xl">add_circle</span>
          Add Question
        </button>
      </div>

      <div class="space-y-6">
        <div
          v-for="(question, qIdx) in questions"
          :key="`q-${qIdx}`"
          class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
        >
          <div class="flex items-start gap-3 mb-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#00288e] text-white text-sm font-bold">{{ qIdx + 1 }}</span>
            <input
              v-model="question.text"
              type="text"
              class="field flex-1"
              placeholder="Enter question text…"
            />
            <div class="flex items-center gap-2 px-3 py-1 bg-white border border-slate-200 rounded-xl shadow-sm min-w-[100px]">
              <span class="text-[10px] font-bold text-slate-400 uppercase">Points</span>
              <input v-model.number="question.points" type="number" min="1" class="w-12 bg-transparent text-sm font-bold text-[#00288e] outline-none" @input="updateTotalMarks" />
            </div>
            <button
              @click="removeQuestion(qIdx)"
              class="shrink-0 flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition"
            >
              <span class="material-symbols-outlined text-lg">delete</span>
            </button>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <div
              v-for="(answer, aIdx) in question.answers"
              :key="`a-${qIdx}-${aIdx}`"
              class="flex items-center gap-3 rounded-xl border-2 bg-white px-4 py-3 transition"
              :class="question.correctIndex === aIdx ? 'border-green-500 bg-green-50' : 'border-slate-200'"
            >
              <button
                @click="setCorrect(qIdx, aIdx)"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 transition"
                :class="question.correctIndex === aIdx
                  ? 'border-green-500 bg-green-500 text-white'
                  : 'border-slate-300 text-slate-300 hover:border-green-400'"
              >
                <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">check</span>
              </button>
              <span class="text-xs font-bold text-slate-400 w-4">{{ ['A','B','C','D'][aIdx] }}</span>
              <input
                v-model="answer.text"
                type="text"
                class="flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-300"
                :placeholder="`Option ${['A','B','C','D'][aIdx]}`"
              />
            </div>
          </div>
          <p v-if="question.correctIndex === null" class="mt-2 text-xs text-amber-600">
            ⚠ Select the correct answer above.
          </p>
        </div>

        <div v-if="questions.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 py-12 text-center text-slate-400 text-sm">
          <span class="material-symbols-outlined block text-4xl mb-2">quiz</span>
          No questions yet. Click "Add Question" to begin.
        </div>
      </div>
    </div>

    <!-- Existing Tests Table -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2 flex-wrap">
        <span class="material-symbols-outlined text-[#00288e]">list_alt</span>
        <h2 class="font-manrope font-bold text-slate-900">Existing Tests</h2>
        <span class="ml-auto text-xs text-slate-400">{{ tests.length }} total</span>
        <!-- Import Button -->
        <button
          @click="showImportModal = true"
          class="flex items-center gap-2 rounded-xl border-2 border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
        >
          <span class="material-symbols-outlined text-lg">upload_file</span>
          Import Test
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead>
            <tr class="bg-slate-50">
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Test Name</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Skill</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Batch</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Duration</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Marks</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Questions</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="test in tests" :key="test.id" class="hover:bg-blue-50/30 transition-colors">
              <td class="px-4 py-3 font-medium text-slate-800">{{ test.name }}</td>
              <td class="px-4 py-3">
                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-[#00288e]">{{ test.skill }}</span>
              </td>
              <td class="px-4 py-3">
                <span v-if="test.batch" class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-700">{{ test.batch }}</span>
                <span v-else class="text-xs text-slate-400italic">All Batches</span>
              </td>
              <td class="px-4 py-3 text-slate-500">{{ test.durationMinutes }} min</td>
              <td class="px-4 py-3 text-slate-500">{{ test.totalMarks }}</td>
              <td class="px-4 py-3">
                <span :class="{
                  'bg-green-100 text-green-700': test.status === 'Published',
                  'bg-blue-100 text-blue-700': test.status === 'Finished',
                  'bg-amber-100 text-amber-700': test.status === 'Draft'
                }" class="rounded-full px-2.5 py-0.5 text-xs font-semibold">
                  {{ test.status }}
                </span>
                <div v-if="test.scheduledAt" class="text-[10px] text-slate-400 mt-1">
                  {{ new Date(test.scheduledAt).toLocaleString() }}
                </div>
              </td>
              <td class="px-4 py-3 text-slate-500">{{ test.questionCount }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button
                    @click="editTest(test)"
                    class="flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#00288e] hover:bg-[#00288e] hover:text-white transition"
                  >
                    <span class="material-symbols-outlined text-sm">edit</span>
                    Edit
                  </button>
                  <button
                    @click="exportTest(test)"
                    class="flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-600 hover:text-white transition"
                  >
                    <span class="material-symbols-outlined text-sm">download</span>
                    Export
                  </button>
                  <button
                    @click="deleteTest(test)"
                    class="flex items-center gap-1 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-600 hover:text-white transition"
                  >
                    <span class="material-symbols-outlined text-sm">delete</span>
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="tests.length === 0">
              <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-400">No tests created yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Import Modal ───────────────────────────────────────────── -->
    <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl p-6 space-y-5">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-manrope font-bold text-slate-900 text-lg">Import Test</h3>
            <p class="text-sm text-slate-400 mt-0.5">Upload a JSON file exported from this system.</p>
          </div>
          <button @click="closeImportModal" class="text-slate-400 hover:text-slate-700 transition">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <!-- File Drop Zone -->
        <label class="flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 cursor-pointer hover:border-[#00288e] hover:bg-blue-50/40 transition-all">
          <span class="material-symbols-outlined text-4xl text-slate-400">upload_file</span>
          <div class="text-center">
            <p class="text-sm font-semibold text-slate-600">Click to select JSON file</p>
            <p class="text-xs text-slate-400 mt-1">.json files only, max 2MB</p>
          </div>
          <input type="file" accept=".json,application/json" class="hidden" @change="onImportFileChange" />
          <span v-if="importFile" class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
            ✓ {{ importFile.name }}
          </span>
        </label>

        <!-- Preview -->
        <div v-if="importPreview" class="rounded-xl bg-[#00288e]/5 border border-[#00288e]/10 px-4 py-3 space-y-1">
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Preview</p>
          <p class="font-semibold text-slate-800">{{ importPreview.name }} <span class="text-slate-400 font-normal">(Copy)</span></p>
          <div class="flex gap-4 text-xs text-slate-500 mt-1">
            <span>⏱ {{ importPreview.durationMinutes }} min</span>
            <span>⭐ {{ importPreview.totalMarks }} marks</span>
            <span>❓ {{ importPreview.questions?.length }} questions</span>
          </div>
        </div>

        <!-- Skill Selector -->
        <label class="block space-y-1">
          <span class="label-text">Assign to Skill <span class="text-red-500">*</span></span>
          <select v-model="importSkillId" class="field">
            <option value="" disabled>Select skill…</option>
            <option v-for="s in skills" :key="s.SkillId" :value="s.SkillId">{{ s.SkillName }}</option>
          </select>
        </label>

        <!-- Batch Selector -->
        <label class="block space-y-1">
          <span class="label-text">Batch (optional)</span>
          <select v-model="importBatchId" class="field">
            <option value="">All Batches</option>
            <option v-for="b in batches" :key="b.BatchId" :value="b.BatchId">{{ b.BatchName }}</option>
          </select>
        </label>

        <!-- Error -->
        <div v-if="importError" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600">
          {{ importError }}
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-1">
          <button @click="closeImportModal" class="flex-1 rounded-xl border-2 border-slate-200 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
            Cancel
          </button>
          <button
            @click="confirmImport"
            :disabled="!importFile || !importSkillId || importLoading"
            class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="importLoading" class="material-symbols-outlined text-lg animate-spin">progress_activity</span>
            <span v-else class="material-symbols-outlined text-lg">upload</span>
            {{ importLoading ? 'Importing…' : 'Import as Draft' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const testName       = ref('')
const selectedSkillId = ref('')
const durationMinutes = ref(45)
const totalMarks      = ref(1)
const scheduledAt     = ref('')
const finishedAt      = ref('')
const editingTestId   = ref(null)
const skills          = ref([])
const batches         = ref([])
const selectedBatchId = ref('')
const tests           = ref([])
const toast           = ref(null)

const questions = ref([])

// ── Import state ────────────────────────────────────────────────────
const showImportModal = ref(false)
const importFile      = ref(null)
const importPreview   = ref(null)
const importSkillId   = ref('')
const importBatchId   = ref('')
const importError     = ref('')
const importLoading   = ref(false)

const hasFormData = computed(() => {
  return testName.value.trim() !== '' || 
         questions.value.length > 0 || 
         scheduledAt.value !== '' ||
         finishedAt.value !== ''
})

const addQuestion = () => {
  questions.value.push({
    text: '',
    points: 1,
    answers: [{ text: '' }, { text: '' }, { text: '' }, { text: '' }],
    correctIndex: null,
  })
  updateTotalMarks()
}

const updateTotalMarks = () => {
  totalMarks.value = questions.value.reduce((acc, q) => acc + (parseInt(q.points) || 0), 0)
}

const removeQuestion = (idx) => {
  questions.value.splice(idx, 1)
  updateTotalMarks()
}
const setCorrect = (qIdx, aIdx) => { questions.value[qIdx].correctIndex = aIdx }

const showToast = (message, type = 'success') => {
  toast.value = { message, type }
  setTimeout(() => { toast.value = null }, 4000)
}

const handleSaveTest = async (statusArg) => {
  if (!testName.value.trim()) return showToast('Enter a test name.', 'error')
  if (!selectedSkillId.value) return showToast('Select a skill.', 'error')
  if (questions.value.length === 0) return showToast('Add at least one question.', 'error')
  const invalid = questions.value.find(q => !q.text.trim() || q.correctIndex === null)
  if (invalid) return showToast('Fill all question texts and mark correct answers.', 'error')

  try {
    const payload = {
      name:            testName.value,
      skillId:         selectedSkillId.value,
      batchId:         selectedBatchId.value || null,
      durationMinutes: durationMinutes.value,
      totalMarks:      totalMarks.value || 0,
      scheduledAt:     scheduledAt.value || null,
      finishedAt:      finishedAt.value || null,
      status:          statusArg,
      questions: questions.value.map(q => ({
        text: q.text,
        points: q.points || 1,
        answers: q.answers.map((a, i) => ({ text: a.text, correct: i === q.correctIndex })),
      })),
    }

    if (editingTestId.value) {
      await axios.put(`/api/admin/tests/${editingTestId.value}`, payload)
      showToast('Test updated successfully!')
    } else {
      await axios.post('/api/admin/tests', payload)
      showToast(`Test ${statusArg === 'Published' ? 'published' : 'saved as draft'} successfully!`)
    }

    resetForm()
    await loadTests()
  } catch (e) {
    showToast(e.response?.data?.message || 'Failed to save test.', 'error')
  }
}

const resetForm = () => {
  editingTestId.value = null
  testName.value = ''
  selectedBatchId.value = ''
  scheduledAt.value = ''
  finishedAt.value = ''
  questions.value = []
  durationMinutes.value = 45
  totalMarks.value = 1
}

const editTest = async (test) => {
  try {
    const res = await axios.get(`/api/admin/tests/${test.id}`)
    const t = res.data.test
    editingTestId.value   = t.id
    testName.value        = t.name
    selectedSkillId.value = skills.value.find(s => s.SkillName === t.skill)?.SkillId || ''
    selectedBatchId.value = t.batchId || ''
    durationMinutes.value = t.durationMinutes
    totalMarks.value      = t.totalMarks
    scheduledAt.value     = t.scheduledAt ? t.scheduledAt.substring(0, 16) : ''
    finishedAt.value      = t.finishedAt ? t.finishedAt.substring(0, 16) : '' // format for datetime-local
    questions.value       = t.questions.map(q => ({
      text: q.text,
      points: q.points,
      answers: q.answers.map(a => ({ text: a.text })),
      correctIndex: q.answers.findIndex(a => a.correct)
    }))
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    showToast('Failed to load test details.', 'error')
  }
}

const cancelEdit = () => {
  resetForm()
}

const deleteTest = async (test) => {
  if (!confirm(`Delete "${test.name}"? This cannot be undone.`)) return
  try {
    await axios.delete(`/api/admin/tests/${test.id}`)
    tests.value = tests.value.filter(t => t.id !== test.id)
  } catch (e) { showToast('Failed to delete test.', 'error') }
}

const loadTests = async () => {
  const res = await axios.get('/api/admin/tests')
  tests.value = res.data.tests
}

// ── Export ───────────────────────────────────────────────────────────
const exportTest = async (test) => {
  try {
    const res = await axios.get(`/api/admin/tests/${test.id}/export`)
    const blob = new Blob([JSON.stringify(res.data, null, 2)], { type: 'application/json' })
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href     = url
    a.download = `${test.name.replace(/[^a-zA-Z0-9_-]/g, '_')}_export.json`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    showToast('Export failed.', 'error')
  }
}

// ── Import ───────────────────────────────────────────────────────────
const onImportFileChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  importFile.value = file
  importPreview.value = null
  importError.value = ''
  const reader = new FileReader()
  reader.onload = (ev) => {
    try {
      const data = JSON.parse(ev.target.result)
      if (!data.questions) throw new Error('No questions found.')
      importPreview.value = data
    } catch {
      importError.value = 'Invalid JSON file. Please use a file exported from this system.'
      importFile.value = null
    }
  }
  reader.readAsText(file)
}

const closeImportModal = () => {
  showImportModal.value = false
  importFile.value = null
  importPreview.value = null
  importSkillId.value = ''
  importBatchId.value = ''
  importError.value = ''
  importLoading.value = false
}

const confirmImport = async () => {
  if (!importFile.value || !importSkillId.value) return
  importLoading.value = true
  importError.value = ''
  try {
    const form = new FormData()
    form.append('file',    importFile.value)
    form.append('skillId', importSkillId.value)
    if (importBatchId.value) form.append('batchId', importBatchId.value)
    form.append('status', 'Draft')
    await axios.post('/api/admin/tests/import', form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    showToast('Test imported successfully as Draft!')
    closeImportModal()
    await loadTests()
  } catch (e) {
    importError.value = e.response?.data?.message || 'Import failed.'
  } finally {
    importLoading.value = false
  }
}

onMounted(async () => {
  try {
    const [testsRes, sbRes] = await Promise.all([
      axios.get('/api/admin/tests'),
      axios.get('/api/admin/skills-batches'),
    ])
    tests.value = testsRes.data.tests
    skills.value = sbRes.data.skills
    batches.value = sbRes.data.batches
    if (skills.value.length) selectedSkillId.value = skills.value[0].SkillId
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
