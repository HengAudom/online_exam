<template>
  <div class="space-y-5 pb-8">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-2xl border border-slate-200/90 shadow-soft-sm p-4">
      <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
        <Button
          variant="outline"
          size="sm"
          icon="arrow_back"
          class="shrink-0 whitespace-nowrap"
          @click="router.push('/admin/results')"
        >
          <span class="hidden sm:inline">{{ lang === 'kh' ? 'ត្រឡប់ក្រោយ' : 'Back to Results' }}</span>
        </Button>

        <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

        <div class="min-w-0">
          <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-tight truncate">
            {{ lang === 'kh' ? 'លទ្ធផលប្រឡងលម្អិត' : 'Submission Details' }}
          </h2>
          <p class="text-xs text-slate-500 truncate mt-0.5">
            {{ lang === 'kh' ? 'បេក្ខជន៖' : 'Candidate:' }}
            <strong class="text-blue-700 font-bold">{{ result?.studentName }}</strong>
            <span v-if="result?.studentId" class="text-slate-400 font-mono text-xs ml-1">({{ result?.studentId }})</span>
          </p>
        </div>
      </div>

      <div v-if="result?.completedAt" class="flex items-center gap-1.5 text-xs text-slate-500 font-medium bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200/70 shrink-0">
        <span class="material-symbols-outlined text-sm text-slate-400">event_available</span>
        <span class="font-mono text-xs">{{ formattedDate }}</span>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Skeleton height="320px" customClass="rounded-2xl lg:col-span-1" />
      <Skeleton height="320px" customClass="rounded-2xl lg:col-span-2" />
    </div>

    <!-- Main Content -->
    <div v-else-if="result" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

      <!-- Left Column: Summary Hero Card & Breakdown (Fixed / Compact) -->
      <div class="lg:col-span-1 space-y-4">
        <!-- Score Card -->
        <Card padding="normal" class="shadow-soft-md text-center bg-gradient-to-br from-blue-600 to-blue-800 text-white border-none">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15 text-white mx-auto mb-2.5 backdrop-blur-xs">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">
              verified
            </span>
          </div>

          <p class="text-xs font-bold uppercase tracking-wider text-blue-100">
            {{ lang === 'kh' ? 'ពិន្ទុសរុប' : 'Total Score' }}
          </p>
          <h3 class="text-3xl sm:text-4xl font-extrabold mt-0.5 tracking-tight">
            {{ result.score }}<span class="text-xl text-blue-200">/{{ result.totalMarks }}</span>
          </h3>
          <p class="text-xs text-blue-100 mt-1 font-medium truncate">
            {{ result.testName }}
          </p>

          <div class="mt-4 pt-4 border-t border-white/15 grid grid-cols-2 gap-3 text-center">
            <div>
              <p class="text-xl font-extrabold text-white">{{ result.accuracy }}%</p>
              <p class="text-[10px] font-bold uppercase tracking-wider text-blue-200 mt-0.5">{{ lang === 'kh' ? 'អត្រាត្រឹមត្រូវ' : 'Accuracy' }}</p>
            </div>
            <div>
              <p class="text-xl font-extrabold text-white">{{ result.elapsedMinutes }}m</p>
              <p class="text-[10px] font-bold uppercase tracking-wider text-blue-200 mt-0.5">{{ lang === 'kh' ? 'រយៈពេល' : 'Duration' }}</p>
            </div>
          </div>
        </Card>

        <!-- Breakdown Card -->
        <Card :title="lang === 'kh' ? 'ស្ថិតិចម្លើយ' : 'Answer Breakdown'" padding="normal" class="shadow-soft-sm">
          <div class="space-y-2.5">
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-100">
              <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="material-symbols-outlined text-base text-emerald-600" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                <span>{{ lang === 'kh' ? 'ចម្លើយត្រឹមត្រូវ' : 'Correct Answers' }}</span>
              </div>
              <span class="text-sm font-extrabold text-emerald-700">{{ result.totalCorrect }}</span>
            </div>

            <div class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50 text-rose-800 border border-rose-100">
              <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="material-symbols-outlined text-base text-rose-600" style="font-variation-settings: 'FILL' 1;">cancel</span>
                <span>{{ lang === 'kh' ? 'ចម្លើយមិនត្រឹមត្រូវ' : 'Incorrect Answers' }}</span>
              </div>
              <span class="text-sm font-extrabold text-rose-700">{{ result.incorrect }}</span>
            </div>

            <!-- Tab Switches / Interruptions -->
            <div
              :class="[
                'flex items-center justify-between p-2.5 rounded-xl border transition-all',
                (result.interruptions || 0) > 0
                  ? 'bg-amber-50/90 text-amber-900 border-amber-200 shadow-soft-xs'
                  : 'bg-slate-50 text-slate-700 border-slate-200/80'
              ]"
            >
              <div class="flex items-center gap-2 text-xs font-semibold">
                <span
                  class="material-symbols-outlined text-base"
                  :class="(result.interruptions || 0) > 0 ? 'text-amber-600' : 'text-slate-400'"
                >
                  {{ (result.interruptions || 0) > 0 ? 'visibility_off' : 'verified_user' }}
                </span>
                <span>{{ lang === 'kh' ? 'ការប្តូរផ្ទាំង' : 'Tab Switches / Focus Lost' }}</span>
              </div>
              <span
                class="text-sm font-extrabold px-2 py-0.5 rounded-lg"
                :class="(result.interruptions || 0) > 0 ? 'bg-amber-100 text-amber-800 font-black' : 'text-slate-600'"
              >
                {{ result.interruptions || 0 }} {{ lang === 'kh' ? 'លើក' : 'times' }}
              </span>
            </div>
          </div>
        </Card>
      </div>

      <!-- Right Column: Question-by-Question Review -->
      <div class="lg:col-span-2 space-y-4">
        <Card
          :title="lang === 'kh' ? 'ការពិនិត្យចម្លើយតាមសំណួរ' : 'Question-by-Question Review'"
          :subtitle="`${result.questions?.length || 0} ${lang === 'kh' ? 'សំណួរ' : 'questions'}`"
          padding="none"
          class="shadow-soft-sm overflow-hidden"
        >
          <template #actions>
            <button
              type="button"
              :class="[
                'flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all cursor-pointer select-none shadow-soft-xs',
                hideAnswers
                  ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                  : 'bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100'
              ]"
              @click="toggleHideAnswers"
            >
              <span
                class="material-symbols-outlined text-base"
                :class="hideAnswers ? 'text-slate-500' : 'text-blue-600'"
              >
                {{ hideAnswers ? 'visibility' : 'visibility_off' }}
              </span>
              <span>{{ hideAnswers ? (lang === 'kh' ? 'បង្ហាញចម្លើយ' : 'Show Correct Answers') : (lang === 'kh' ? 'លាក់ចម្លើយ' : 'Hide Correct Answers') }}</span>
            </button>
          </template>

          <div class="divide-y divide-slate-100">
            <div
              v-for="(q, i) in result.questions"
              :key="q.id"
              class="p-4 sm:p-5 hover:bg-slate-50/50 transition-colors space-y-3"
            >
              <!-- Question Header -->
              <div class="flex items-start gap-3">
                <span
                  :class="[
                    'h-7 w-7 rounded-xl flex items-center justify-center font-bold text-xs shrink-0',
                    hideAnswers
                      ? 'bg-slate-100 text-slate-600'
                      : q.isCorrect ? 'bg-emerald-100 text-emerald-700' : q.skipped ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'
                  ]"
                >
                  {{ i + 1 }}
                </span>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2 mb-1.5">
                    <div class="flex items-center gap-1.5">
                      <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        {{ lang === 'kh' ? 'សំណួរទី' : 'Question' }} {{ i + 1 }}
                      </span>
                      <span v-if="q.passage" class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 text-[9px] font-bold">
                        <span class="material-symbols-outlined text-[10px]">menu_book</span> Reading
                      </span>
                    </div>
                    <Badge
                      v-if="!hideAnswers"
                      :variant="q.isCorrect ? 'success' : q.skipped ? 'warning' : 'danger'"
                      size="xs"
                    >
                      {{ q.isCorrect ? (lang === 'kh' ? 'ត្រឹមត្រូវ' : 'Correct') : q.skipped ? (lang === 'kh' ? 'មិនបានឆ្លើយ' : 'Skipped') : (lang === 'kh' ? 'មិនត្រឹមត្រូវ' : 'Incorrect') }}
                    </Badge>
                  </div>

                  <!-- Reading Passage Context in Review (if applicable) -->
                  <div v-if="q.passage && (i === 0 || result.questions[i - 1]?.passage !== q.passage)" class="p-3 my-2.5 rounded-xl bg-blue-50/80 border border-blue-200/70 text-xs text-slate-800 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-blue-800">
                      <span class="material-symbols-outlined text-sm">menu_book</span>
                      <span>{{ lang === 'kh' ? 'អត្ថបទអាន (Reading Passage)' : 'Reading Passage' }}</span>
                    </div>
                    <p class="whitespace-pre-line text-slate-700 italic leading-relaxed">{{ q.passage }}</p>
                  </div>

                  <p class="text-sm font-semibold text-slate-900 leading-relaxed" v-html="renderMath(q.text)"></p>

                  <!-- Answer Options Grid -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-3">
                    <div
                      v-for="ans in q.answers"
                      :key="ans.id"
                      :class="[
                        'flex items-center gap-2.5 p-3 rounded-xl text-xs border transition-all',
                        hideAnswers
                          ? 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
                          : ans.isCorrect
                          ? 'bg-emerald-50/90 border-emerald-300 text-emerald-950 font-bold ring-1 ring-emerald-400/60 shadow-soft-xs'
                          : ans.id === q.selectedId && !ans.isCorrect
                          ? 'bg-rose-50/90 border-rose-300 text-rose-950 font-bold ring-1 ring-rose-400/60 shadow-soft-xs'
                          : 'bg-white border-slate-200 text-slate-600'
                      ]"
                    >
                      <span
                        class="material-symbols-outlined text-base shrink-0 select-none"
                        style="font-variation-settings: 'FILL' 1;"
                        :class="!hideAnswers ? (ans.isCorrect ? 'text-emerald-600' : ans.id === q.selectedId ? 'text-rose-600' : 'text-slate-300') : 'text-slate-300'"
                      >
                        {{ !hideAnswers ? (ans.isCorrect ? 'check_circle' : ans.id === q.selectedId ? 'cancel' : 'radio_button_unchecked') : 'radio_button_unchecked' }}
                      </span>

                      <span class="flex-1 font-medium" v-html="renderMath(ans.text)"></span>

                      <span
                        v-if="!hideAnswers && ans.isCorrect && ans.id === q.selectedId"
                        class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-extrabold uppercase shrink-0"
                      >
                        {{ lang === 'kh' ? 'ចម្លើយសិស្ស (ត្រូវ)' : 'Student Choice (Correct)' }}
                      </span>
                      <span
                        v-else-if="!hideAnswers && ans.isCorrect"
                        class="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-extrabold uppercase shrink-0"
                      >
                        {{ lang === 'kh' ? 'ចម្លើយត្រូវ' : 'Correct Answer' }}
                      </span>
                      <span
                        v-else-if="!hideAnswers && ans.id === q.selectedId && !ans.isCorrect"
                        class="text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-extrabold uppercase shrink-0"
                      >
                        {{ lang === 'kh' ? 'ចម្លើយសិស្ស (ខុស)' : 'Student Choice (Wrong)' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Badge from '../components/ui/Badge.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import { useLang } from '../utils/useLang'
import { renderMath } from '../utils/mathRender'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const { lang } = useLang()
const { error: toastError } = useToast()

const hideAnswers = ref(true)
const toggleHideAnswers = () => {
  hideAnswers.value = !hideAnswers.value
}

const loading = ref(true)
const result = ref(null)

const formattedDate = computed(() => {
  if (!result.value?.completedAt) return ''
  return new Date(result.value.completedAt).toLocaleString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

const loadDetail = async () => {
  loading.value = true
  try {
    const submissionId = route.params.submissionId
    const res = await axios.get(`/api/admin/results/${submissionId}`)
    result.value = res.data.result || res.data || null
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចទាញយកទិន្នន័យលទ្ធផលបានទេ' : 'Failed to load result details')
    router.push('/admin/results')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDetail()
})
</script>
