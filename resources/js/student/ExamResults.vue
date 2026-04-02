<template>
  <div class="min-h-screen bg-[#f0f4ff] flex flex-col items-center justify-center px-4 py-10">

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center gap-4 text-[#00288e]">
      <span class="material-symbols-outlined animate-spin text-5xl">progress_activity</span>
      <p class="font-semibold text-lg">Loading results…</p>
    </div>

    <div v-else-if="result" class="w-full max-w-3xl space-y-6">

      <!-- Hero Card -->
      <div class="rounded-3xl bg-[#00288e] px-8 py-10 text-white text-center shadow-xl">
        <span class="material-symbols-outlined text-6xl text-green-300" style="font-variation-settings:'FILL' 1;">check_circle</span>
        <h1 class="mt-4 font-manrope text-3xl font-bold">Exam Complete!</h1>
        <p class="mt-1 text-xl font-semibold text-green-200">{{ result.studentName }}</p>
        <p class="mt-2 text-blue-200 text-base">{{ result.testName }}</p>
        <p class="mt-1 text-sm text-blue-300">Completed {{ formattedDate }}</p>
      </div>

      <!-- Stats Row -->
      <div class="grid grid-cols-3 gap-4">
        <!-- Score -->
        <div class="rounded-2xl bg-white px-6 py-6 shadow-sm text-center">
          <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Score</p>
          <p class="mt-3 font-manrope text-4xl font-bold text-[#00288e]">
            {{ result.totalCorrect }}<span class="text-xl text-slate-400">/{{ result.totalQuestions }}</span>
          </p>
          <p class="mt-1 text-xs text-slate-500">out of {{ result.totalMarks }} marks</p>
        </div>

        <!-- Accuracy -->
        <div class="rounded-2xl bg-[#00288e] px-6 py-6 shadow-sm text-center">
          <p class="text-xs font-semibold uppercase tracking-widest text-blue-300">Accuracy</p>
          <p class="mt-3 font-manrope text-4xl font-bold text-white">{{ result.accuracy }}<span class="text-xl text-blue-300">%</span></p>
          <p class="mt-1 text-xs text-blue-300">correct rate</p>
        </div>

        <!-- Time -->
        <div class="rounded-2xl bg-white px-6 py-6 shadow-sm text-center">
          <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Time Taken</p>
          <p class="mt-3 font-manrope text-4xl font-bold text-[#00288e]">{{ result.elapsedMinutes }}<span class="text-xl text-slate-400">min</span></p>
          <p class="mt-1 text-xs text-slate-500">duration</p>
        </div>
      </div>

      <!-- Breakdown Row -->
      <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl bg-green-50 border border-green-200 px-6 py-5 text-center">
          <span class="material-symbols-outlined text-green-600 text-2xl" style="font-variation-settings:'FILL' 1;">check_circle</span>
          <p class="mt-2 font-manrope text-2xl font-bold text-green-700">{{ result.totalCorrect }}</p>
          <p class="text-xs text-green-600 mt-1">Correct</p>
        </div>
        <div class="rounded-2xl bg-red-50 border border-red-200 px-6 py-5 text-center">
          <span class="material-symbols-outlined text-red-500 text-2xl" style="font-variation-settings:'FILL' 1;">cancel</span>
          <p class="mt-2 font-manrope text-2xl font-bold text-red-600">{{ result.incorrect }}</p>
          <p class="text-xs text-red-500 mt-1">Incorrect</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-200 px-6 py-5 text-center">
          <span class="material-symbols-outlined text-amber-500 text-2xl" style="font-variation-settings:'FILL' 1;">remove_circle</span>
          <p class="mt-2 font-manrope text-2xl font-bold text-amber-600">{{ result.skipped }}</p>
          <p class="text-xs text-amber-500 mt-1">Skipped</p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4">
        <button
          @click="goToDashboard"
          class="flex-1 flex items-center justify-center gap-2 rounded-xl border-2 border-[#00288e] bg-white px-6 py-4 font-semibold text-[#00288e] transition hover:bg-[#00288e] hover:text-white"
        >
          <span class="material-symbols-outlined text-xl">home</span>
          Return to Dashboard
        </button>
        <button
          @click="viewReport"
          class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-[#00288e] px-6 py-4 font-semibold text-white transition hover:bg-[#1e40af]"
        >
          <span class="material-symbols-outlined text-xl">bar_chart</span>
          View Detailed Report
        </button>
      </div>

      <!-- Detailed Report (expandable) -->
      <div v-if="showReport" class="rounded-2xl bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#00288e]">format_list_bulleted</span>
          <p class="font-semibold text-slate-900">Question-by-Question Review</p>
        </div>
        <div class="divide-y divide-slate-100">
          <div
            v-for="(q, i) in result.questions"
            :key="q.id"
            class="px-6 py-5"
          >
            <div class="flex items-start gap-3">
              <span
                class="material-symbols-outlined text-xl mt-0.5 shrink-0"
                :class="q.skipped ? 'text-amber-500' : q.isCorrect ? 'text-green-600' : 'text-red-500'"
                style="font-variation-settings:'FILL' 1;"
              >
                {{ q.skipped ? 'remove_circle' : q.isCorrect ? 'check_circle' : 'cancel' }}
              </span>
              <div class="flex-1">
                <p class="text-sm font-medium text-slate-800">Q{{ i + 1 }}. {{ q.text }}</p>
                <div class="mt-2 space-y-1.5">
                  <div
                    v-for="ans in q.answers"
                    :key="ans.id"
                    class="flex items-center gap-2 text-xs rounded-lg px-3 py-1.5"
                    :class="{
                      'bg-green-100 text-green-800 font-semibold': ans.isCorrect,
                      'bg-red-100 text-red-700': ans.id === q.selectedId && !ans.isCorrect,
                      'text-slate-500': !ans.isCorrect && ans.id !== q.selectedId,
                    }"
                  >
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;"
                      v-if="ans.isCorrect || ans.id === q.selectedId">
                      {{ ans.isCorrect ? 'check_circle' : 'cancel' }}
                    </span>
                    {{ ans.text }}
                    <span v-if="ans.id === q.selectedId && !ans.isCorrect" class="text-red-400">(your answer)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error state -->
    <div v-else class="text-center text-slate-500">
      <span class="material-symbols-outlined text-5xl text-slate-300">error</span>
      <p class="mt-4">Could not load exam results.</p>
      <button @click="goToDashboard" class="mt-4 rounded-xl bg-[#00288e] px-6 py-3 text-white font-semibold">
        Back to Dashboard
      </button>
    </div>

    <!-- Bottom Toast -->
    <div
      v-if="result && !loading"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-3 rounded-2xl bg-[#00288e] px-6 py-3 text-white shadow-2xl text-sm z-50 animate-fade-in"
    >
      <span class="material-symbols-outlined text-green-300 text-xl" style="font-variation-settings:'FILL' 1;">celebration</span>
      <span>Great work! Your result has been saved.</span>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const submissionId = route.params.submissionId
const result = ref(null)
const loading = ref(true)
const showReport = ref(false)

const formattedDate = computed(() => {
  if (!result.value?.completedAt) return ''
  return new Date(result.value.completedAt).toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
})

const goToDashboard = () => router.push('/student')
const viewReport = () => { showReport.value = !showReport.value }

onMounted(async () => {
  try {
    const res = await axios.get(`/api/student/results/${submissionId}`)
    result.value = res.data
  } catch (e) {
    result.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
@keyframes fade-in { from { opacity: 0; transform: translateX(-50%) translateY(20px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }
.animate-fade-in { animation: fade-in 0.5s ease-out forwards; }
</style>
