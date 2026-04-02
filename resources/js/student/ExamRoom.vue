<template>
  <div class="min-h-screen bg-[#f0f4ff] flex flex-col">

    <!-- ── Fixed Top Header ─────────────────────────────────────────── -->
    <header class="sticky top-0 z-30 flex items-center justify-between gap-4 bg-[#00288e] px-6 py-3 shadow-lg">
      <div class="flex items-center gap-3 min-w-0">
        <span class="material-symbols-outlined text-white text-2xl">quiz</span>
        <div class="min-w-0">
          <p class="text-xs text-blue-300 font-semibold uppercase tracking-widest">Exam Room</p>
          <h1 class="font-manrope text-lg font-bold text-white truncate">{{ examData?.testName || 'Loading…' }}</h1>
        </div>
      </div>

      <!-- Timer -->
      <div
        class="flex items-center gap-2 rounded-xl px-4 py-2 font-manrope text-xl font-bold transition-colors"
        :class="timerUrgent ? 'bg-red-600 text-white animate-pulse' : 'bg-white/10 text-white'"
      >
        <span class="material-symbols-outlined text-xl">timer</span>
        {{ formattedTime }}
      </div>
    </header>

    <!-- ── Body ─────────────────────────────────────────────────────── -->
    <div v-if="!examData && !loadError" class="flex flex-1 items-center justify-center">
      <span class="material-symbols-outlined animate-spin text-5xl text-[#00288e]">progress_activity</span>
    </div>

    <div v-else-if="loadError" class="flex flex-1 flex-col items-center justify-center gap-4 text-center px-6">
      <span class="material-symbols-outlined text-5xl text-amber-500 animate-pulse">schedule</span>
      <p class="text-slate-600 max-w-md">{{ loadError }}</p>
      <div class="flex gap-3">
        <button @click="backToDashboard" class="rounded-xl border-2 border-slate-200 px-6 py-3 text-slate-600 font-semibold shadow-sm transition hover:bg-slate-50">Back to Dashboard</button>
        <button @click="loadExam" class="rounded-xl bg-[#00288e] px-8 py-3 text-white font-semibold shadow-lg shadow-blue-900/20 transition hover:bg-[#1e40af] flex items-center gap-2">
           <span class="material-symbols-outlined text-lg">refresh</span>
           Refresh Now
        </button>
      </div>
      <p class="text-xs text-slate-400">Retrying automatically in few seconds...</p>
    </div>

    <div v-else-if="isSubmitted" class="flex flex-1 flex-col items-center justify-center gap-6 px-6 py-10">
      <span class="material-symbols-outlined text-6xl text-green-500" style="font-variation-settings:'FILL' 1;">check_circle</span>
      <p class="font-manrope text-2xl font-bold text-[#00288e]">Exam Submitted!</p>
      <p class="text-slate-500">Your answers have been recorded. Redirecting to results…</p>
      <div class="h-2 w-64 rounded-full bg-slate-200 overflow-hidden">
        <div class="h-full bg-[#00288e] rounded-full animate-pulse w-2/3"></div>
      </div>
    </div>

    <div v-else class="flex flex-1 overflow-hidden">

      <!-- ── Left Sidebar ──────────────────────────────────────────── -->
      <aside class="hidden md:flex w-72 shrink-0 flex-col bg-white border-r border-slate-200">
        <div class="px-5 py-4 border-b border-slate-100">
          <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Question Navigator</p>
          <p class="mt-1 text-sm text-slate-500">
            <span class="font-semibold text-[#00288e]">{{ answeredCount }}</span> / {{ examData.questions.length }} answered
          </p>
        </div>

        <!-- Progress bar -->
        <div class="px-5 py-3 border-b border-slate-100">
          <div class="h-1.5 rounded-full bg-slate-100">
            <div
              class="h-full rounded-full bg-[#00288e] transition-all duration-300"
              :style="{ width: progressPercent + '%' }"
            ></div>
          </div>
        </div>

        <!-- Question list -->
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-1">
          <button
            v-for="(q, idx) in examData.questions"
            :key="q.id"
            @click="goToQuestion(idx)"
            class="group w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-all"
            :class="{
              'bg-[#00288e] text-white shadow-md': idx === currentIndex,
              'bg-green-50 text-green-700': idx !== currentIndex && selectedAnswers[idx],
              'bg-amber-50 text-amber-700': flagged[idx],
              'text-slate-600 hover:bg-slate-50': idx !== currentIndex && !selectedAnswers[idx] && !flagged[idx],
            }"
          >
            <!-- Number bubble -->
            <span
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
              :class="{
                'bg-white/20 text-white': idx === currentIndex,
                'bg-green-600 text-white': idx !== currentIndex && selectedAnswers[idx],
                'bg-amber-400 text-white': flagged[idx] && idx !== currentIndex,
                'bg-slate-100 text-slate-500': idx !== currentIndex && !selectedAnswers[idx] && !flagged[idx],
              }"
            >{{ idx + 1 }}</span>

            <span class="flex-1 truncate text-left font-medium">Q{{ idx + 1 }}</span>

            <span
              class="material-symbols-outlined text-base shrink-0"
              :class="idx === currentIndex ? 'text-white/70' : 'text-current opacity-60'"
              style="font-variation-settings:'FILL' 1;"
            >
              {{ flagged[idx] ? 'flag' : selectedAnswers[idx] ? 'check_circle' : 'radio_button_unchecked' }}
            </span>
          </button>
        </nav>

        <!-- Submit button -->
        <div class="p-4 border-t border-slate-100">
          <button
            @click="confirmSubmit"
            class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#00288e] px-4 py-3 font-semibold text-white transition hover:bg-[#1e40af] shadow"
          >
            <span class="material-symbols-outlined text-xl">send</span>
            Submit Exam
          </button>
        </div>
      </aside>

      <!-- ── Main Content ───────────────────────────────────────────── -->
      <main class="flex-1 overflow-y-auto px-4 py-6 md:px-8">
        <div class="mx-auto max-w-2xl space-y-6">

          <!-- Question Card -->
          <div class="rounded-2xl bg-white shadow-sm p-6">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-bold uppercase tracking-widest text-[#00288e]">
                Question {{ currentIndex + 1 }} of {{ examData.questions.length }}
              </span>
              <button
                @click="toggleFlag"
                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                :class="flagged[currentIndex] ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-600'"
              >
                <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">flag</span>
                {{ flagged[currentIndex] ? 'Flagged' : 'Flag for Review' }}
              </button>
            </div>

            <h2 class="mt-4 font-manrope text-xl font-bold text-slate-900 leading-relaxed">
              {{ currentQuestion.text }}
            </h2>
          </div>

          <!-- Answer Options -->
          <div class="space-y-3">
            <label
              v-for="(answer, aIdx) in currentQuestion.answers"
              :key="answer.id"
              class="flex cursor-pointer items-center gap-4 rounded-2xl border-2 px-5 py-4 transition-all"
              :class="{
                'border-[#00288e] bg-[#00288e]/5 shadow-md': selectedAnswers[currentIndex] === answer.id,
                'border-slate-200 bg-white hover:border-[#00288e]/40 hover:bg-blue-50/30': selectedAnswers[currentIndex] !== answer.id,
              }"
            >
              <input
                type="radio"
                :name="`q-${currentIndex}`"
                :value="answer.id"
                v-model="selectedAnswers[currentIndex]"
                @change="onAnswerSelect(answer.id)"
                class="sr-only"
              />
              <!-- Option circle -->
              <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold transition-all"
                :class="{
                  'border-[#00288e] bg-[#00288e] text-white': selectedAnswers[currentIndex] === answer.id,
                  'border-slate-300 text-slate-400': selectedAnswers[currentIndex] !== answer.id,
                }"
              >{{ ['A','B','C','D'][aIdx] }}</span>
              <span
                class="flex-1 font-medium"
                :class="selectedAnswers[currentIndex] === answer.id ? 'text-[#00288e]' : 'text-slate-700'"
              >{{ answer.text }}</span>
              <span
                v-if="selectedAnswers[currentIndex] === answer.id"
                class="material-symbols-outlined text-xl text-[#00288e] shrink-0"
                style="font-variation-settings:'FILL' 1;"
              >check_circle</span>
            </label>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex items-center justify-between gap-3">
            <button
              @click="goPrevious"
              :disabled="currentIndex === 0"
              class="flex items-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-[#00288e] hover:text-[#00288e] disabled:cursor-not-allowed disabled:opacity-40"
            >
              <span class="material-symbols-outlined text-xl">arrow_back</span>
              Previous
            </button>

            <!-- Mobile Submit -->
            <button
              @click="confirmSubmit"
              class="flex md:hidden items-center gap-2 rounded-xl bg-[#00288e] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1e40af]"
            >
              <span class="material-symbols-outlined text-xl">send</span>
              Submit
            </button>

            <button
              @click="goNext"
              :disabled="currentIndex === examData.questions.length - 1"
              class="flex items-center gap-2 rounded-xl bg-[#00288e] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1e40af] disabled:cursor-not-allowed disabled:opacity-40"
            >
              Next
              <span class="material-symbols-outlined text-xl">arrow_forward</span>
            </button>
          </div>
        </div>
      </main>
    </div>

    <!-- ── Confirm Submit Modal ──────────────────────────────────────── -->
    <div
      v-if="showConfirm"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
    >
      <div class="mx-4 w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
        <div class="text-center">
          <span class="material-symbols-outlined text-5xl text-amber-500" style="font-variation-settings:'FILL' 1;">warning</span>
          <h2 class="mt-4 font-manrope text-xl font-bold text-slate-900">Submit Exam?</h2>
          <p class="mt-2 text-sm text-slate-500">
            You've answered <strong>{{ answeredCount }}</strong> of <strong>{{ examData?.questions.length }}</strong> questions.
            <span v-if="unansweredCount > 0" class="text-amber-600 font-semibold"> {{ unansweredCount }} unanswered.</span>
          </p>
        </div>
        <div class="mt-8 flex gap-3">
          <button
            @click="showConfirm = false"
            class="flex-1 rounded-xl border-2 border-slate-200 py-3 font-semibold text-slate-700 transition hover:border-slate-300"
          >Cancel</button>
          <button
            @click="submitExam"
            class="flex-1 rounded-xl bg-[#00288e] py-3 font-semibold text-white transition hover:bg-[#1e40af]"
          >Submit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const testId = route.params.testId

const examData = ref(null)
const submissionId = ref(null)
const loading = ref(true)
const loadError = ref(null)
const isSubmitted = ref(false)
const showConfirm = ref(false)

const currentIndex = ref(0)
const selectedAnswers = ref([])   // array of answerId or null
const flagged = ref([])           // boolean array

let timer = null
const remainingSeconds = ref(0)

// ── Computed ──────────────────────────────────────────────────────────────────
const currentQuestion = computed(() =>
  examData.value?.questions?.[currentIndex.value] ?? { text: '', answers: [] }
)

const formattedTime = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60).toString().padStart(2, '0')
  const s = (remainingSeconds.value % 60).toString().padStart(2, '0')
  return `${m}:${s}`
})

const timerUrgent = computed(() => remainingSeconds.value <= 300) // 5 min

const answeredCount = computed(() => selectedAnswers.value.filter(Boolean).length)
const unansweredCount = computed(() =>
  (examData.value?.questions?.length ?? 0) - answeredCount.value
)
const progressPercent = computed(() =>
  examData.value?.questions?.length
    ? Math.round((answeredCount.value / examData.value.questions.length) * 100)
    : 0
)

// ── Navigation ────────────────────────────────────────────────────────────────
const goNext = () => {
  if (currentIndex.value < examData.value.questions.length - 1) currentIndex.value++
}
const goPrevious = () => {
  if (currentIndex.value > 0) currentIndex.value--
}
const goToQuestion = (idx) => { currentIndex.value = idx }
const toggleFlag = () => { flagged.value[currentIndex.value] = !flagged.value[currentIndex.value] }
const backToDashboard = () => router.push('/student')

// ── Answer ────────────────────────────────────────────────────────────────────
const onAnswerSelect = async (answerId) => {
  if (!submissionId.value) return
  try {
    await axios.post('/api/exam/answer', {
      submissionId: submissionId.value,
      questionId: currentQuestion.value.id,
      selectedAnswerId: answerId,
    })
  } catch (e) { /* silently fail – answer stored locally */ }
}

// ── Submit ────────────────────────────────────────────────────────────────────
const confirmSubmit = () => { showConfirm.value = true }

const submitExam = async () => {
  showConfirm.value = false
  clearInterval(timer)
  isSubmitted.value = true

  try {
    const res = await axios.post(`/api/exam/${submissionId.value}/complete`)
    setTimeout(() => {
      router.push({ name: 'ExamResults', params: { submissionId: res.data.submissionId } })
    }, 1500)
  } catch (e) {
    isSubmitted.value = false
    alert('Failed to submit exam. Please try again.')
  }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
const loadExam = async () => {
  loading.value = true
  loadError.value = null
  try {
    const res = await axios.get(`/api/exam/${testId}/start`)
    examData.value = res.data
    submissionId.value = res.data.submissionId
    selectedAnswers.value = Array(res.data.questions.length).fill(null)
    flagged.value = Array(res.data.questions.length).fill(false)
    remainingSeconds.value = res.data.durationMinutes * 60

    if (timer) clearInterval(timer)
    timer = setInterval(() => {
      // 1. Personal Timer
      if (remainingSeconds.value > 0) {
        remainingSeconds.value--
      } else {
        clearInterval(timer)
        submitExam()
        return
      }

      // 2. GLOBAL Exam Window Check (End of exam for everyone)
      let endAt = null
      if (examData.value.finishedAt) {
        endAt = new Date(examData.value.finishedAt)
      } else if (examData.value.scheduledAt) {
        const start = new Date(examData.value.scheduledAt)
        endAt = new Date(start.getTime() + examData.value.durationMinutes * 60000)
      }

      if (endAt && new Date() > endAt) {
        clearInterval(timer)
        submitExam()
      }
    }, 1000)
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Failed to load exam. Please try again.'
    // If it's a scheduling error, retry in 10 seconds
    if (loadError.value.toLowerCase().includes('scheduled')) {
      setTimeout(loadExam, 10000)
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadExam)

onBeforeUnmount(() => clearInterval(timer))
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
</style>
