<template>
  <div class="space-y-6">

    <!-- Header + Search -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h2 class="font-manrope text-lg font-bold text-slate-900">Exam Results</h2>
          <p class="text-sm text-slate-500">All submission results across all students and tests.</p>
        </div>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search student or test…"
            class="w-64 rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-sm outline-none transition focus:border-[#00288e] focus:bg-white"
          />
        </div>
      </div>

      <!-- Summary stats -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl bg-[#00288e]/5 px-4 py-3 text-center">
          <p class="text-2xl font-bold text-[#00288e] font-manrope">{{ results.length }}</p>
          <p class="text-xs text-slate-500 mt-0.5">Total Submissions</p>
        </div>
        <div class="rounded-xl bg-green-50 px-4 py-3 text-center">
          <p class="text-2xl font-bold text-green-700 font-manrope">{{ avgAccuracy }}%</p>
          <p class="text-xs text-slate-500 mt-0.5">Average Accuracy</p>
        </div>
        <div class="rounded-xl bg-amber-50 px-4 py-3 text-center">
          <p class="text-2xl font-bold text-amber-700 font-manrope">{{ uniqueStudents }}</p>
          <p class="text-xs text-slate-500 mt-0.5">Unique Students</p>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead>
            <tr class="bg-slate-50">
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Student</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Test</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Score</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Accuracy</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Date</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="r in filteredResults" :key="r.id" class="hover:bg-blue-50/20 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#00288e]/10 text-xs font-bold text-[#00288e]">
                    {{ r.studentName?.charAt(0).toUpperCase() }}
                  </div>
                  <span class="font-medium text-slate-800">{{ r.studentName }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ r.testName }}</td>
              <td class="px-4 py-3">
                <span class="font-semibold text-slate-800">{{ r.score }}</span>
                <span class="text-slate-400"> / {{ r.totalMarks }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1.5 rounded-full bg-slate-100 max-w-20">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="r.accuracy >= 80 ? 'bg-green-500' : r.accuracy >= 50 ? 'bg-amber-400' : 'bg-red-400'"
                      :style="{ width: r.accuracy + '%' }"
                    ></div>
                  </div>
                  <span
                    class="text-xs font-semibold"
                    :class="r.accuracy >= 80 ? 'text-green-600' : r.accuracy >= 50 ? 'text-amber-600' : 'text-red-500'"
                  >{{ r.accuracy }}%</span>
                </div>
              </td>
              <td class="px-4 py-3 text-xs text-slate-400">{{ formatDate(r.completedAt) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="viewResult(r.id)"
                    class="flex items-center justify-center h-8 w-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                    title="View Detailed Results"
                  >
                    <span class="material-symbols-outlined text-lg">visibility</span>
                  </button>
                  <button
                    @click="deleteResult(r.id)"
                    class="flex items-center justify-center h-8 w-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                    title="Delete Result"
                  >
                    <span class="material-symbols-outlined text-lg">delete</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredResults.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                <span class="material-symbols-outlined block text-4xl mb-2">bar_chart</span>
                No results found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const results     = ref([])
const searchQuery = ref('')

const filteredResults = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return results.value
  return results.value.filter(r =>
    r.studentName?.toLowerCase().includes(q) ||
    r.testName?.toLowerCase().includes(q)
  )
})

const avgAccuracy = computed(() => {
  if (!results.value.length) return 0
  const sum = results.value.reduce((acc, r) => acc + (r.accuracy ?? 0), 0)
  return Math.round(sum / results.value.length)
})

const uniqueStudents = computed(() =>
  new Set(results.value.map(r => r.studentName)).size
)

const formatDate = (dateStr) => {
  if (!dateStr) return '–'
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const viewResult = (id) => {
  router.push(`/admin/results/${id}`)
}

const deleteResult = async (id) => {
  if (!confirm('Are you sure you want to delete this result?')) return
  try {
    await axios.delete(`/api/admin/results/${id}`)
    results.value = results.value.filter(r => r.id !== id)
  } catch (e) { console.error(e) }
}

onMounted(async () => {
  try {
    const res = await axios.get('/api/admin/results')
    results.value = res.data.results
  } catch (e) { console.error(e) }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
</style>
