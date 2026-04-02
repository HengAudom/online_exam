<template>
  <div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="flex items-center justify-between bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
      <div class="flex items-center gap-4">
        <button
          @click="router.push('/admin/results')"
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-600 hover:bg-[#00288e] hover:text-white transition-all shadow-sm group"
        >
          <span class="material-symbols-outlined transition-transform group-hover:-translate-x-1">arrow_back</span>
        </button>
        <div>
          <h2 class="font-manrope text-xl font-bold text-slate-900">Result Detail</h2>
          <p class="text-sm text-slate-500">Student: <span class="font-semibold text-[#00288e]">{{ result?.studentName }}</span></p>
        </div>
      </div>
      <div v-if="result" class="hidden md:flex items-center gap-3">
        <div class="text-right">
          <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Completed At</p>
          <p class="text-sm font-semibold text-slate-700">{{ formattedDate }}</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-4 text-[#00288e]">
      <span class="material-symbols-outlined animate-spin text-5xl">progress_activity</span>
      <p class="font-semibold text-lg">Loading result details…</p>
    </div>

    <div v-else-if="result" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Left Column: Summary & Breakdown -->
      <div class="lg:col-span-1 space-y-6">
        <!-- Stats Card -->
        <div class="rounded-3xl bg-[#00288e] p-8 text-white shadow-xl">
          <div class="flex items-center justify-center h-16 w-16 rounded-2xl bg-white/10 mb-6 mx-auto">
            <span class="material-symbols-outlined text-4xl text-green-300">verified</span>
          </div>
          <div class="text-center space-y-2">
            <h3 class="text-blue-200 uppercase tracking-widest text-xs font-bold">Total Score</h3>
            <p class="text-5xl font-manrope font-bold">{{ result.score }}<span class="text-2xl text-blue-300">/{{ result.totalMarks }}</span></p>
            <p class="text-sm text-blue-200 opacity-80">{{ result.testName }}</p>
          </div>
          
          <div class="mt-8 pt-8 border-t border-white/10 grid grid-cols-2 gap-4">
            <div class="text-center">
              <p class="text-2xl font-bold font-manrope text-white">{{ result.accuracy }}%</p>
              <p class="text-[10px] uppercase tracking-wider text-blue-300">Accuracy</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold font-manrope text-white">{{ result.elapsedMinutes }}m</p>
              <p class="text-[10px] uppercase tracking-wider text-blue-300">Duration</p>
            </div>
          </div>
        </div>

        <!-- Question Stats -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6 space-y-4">
          <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-3">Breakdown</h4>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-green-600">
              <span class="material-symbols-outlined text-xl">check_circle</span>
              <span class="text-sm font-medium">Correct</span>
            </div>
            <span class="font-bold text-slate-900">{{ result.totalCorrect }}</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-red-500">
              <span class="material-symbols-outlined text-xl">cancel</span>
              <span class="text-sm font-medium">Incorrect</span>
            </div>
            <span class="font-bold text-slate-900">{{ result.incorrect }}</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-500">
              <span class="material-symbols-outlined text-xl">remove_circle</span>
              <span class="text-sm font-medium">Skipped</span>
            </div>
            <span class="font-bold text-slate-900">{{ result.skipped }}</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Question Review -->
      <div class="lg:col-span-2">
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
            <h4 class="font-bold text-slate-900">Question-by-Question Review</h4>
          </div>
          <div class="divide-y divide-slate-100">
            <div
              v-for="(q, i) in result.questions"
              :key="q.id"
              class="px-6 py-6 transition-colors hover:bg-slate-50/50"
            >
              <div class="flex items-start gap-4">
                <div 
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                  :class="q.skipped ? 'bg-amber-100 text-amber-700' : q.isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ i + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-slate-800 font-medium mb-4 leading-relaxed">{{ q.text }}</p>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                      v-for="ans in q.answers"
                      :key="ans.id"
                      class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm border transition-all"
                      :class="{
                        'bg-green-50 border-green-200 text-green-800 font-semibold ring-1 ring-green-500': ans.isCorrect,
                        'bg-red-50 border-red-100 text-red-700 ring-1 ring-red-400': ans.id === q.selectedId && !ans.isCorrect,
                        'bg-slate-50 border-slate-100 text-slate-500': !ans.isCorrect && ans.id !== q.selectedId,
                      }"
                    >
                      <span class="material-symbols-outlined text-lg shrink-0"
                        v-if="ans.isCorrect || ans.id === q.selectedId">
                        {{ ans.isCorrect ? 'check_circle' : 'cancel' }}
                      </span>
                      <span class="material-symbols-outlined text-lg text-slate-300 shrink-0" v-else>circle</span>
                      <span class="flex-1">{{ ans.text }}</span>
                      <span v-if="ans.id === q.selectedId && !ans.isCorrect" class="text-[10px] bg-red-100 px-1.5 py-0.5 rounded uppercase font-bold tracking-tighter">Student's Choice</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
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

const formattedDate = computed(() => {
  if (!result.value?.completedAt) return ''
  return new Date(result.value.completedAt).toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
})

onMounted(async () => {
  try {
    const res = await axios.get(`/api/student/results/${submissionId}`)
    result.value = res.data
  } catch (e) {
    console.error(e)
    result.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
</style>
