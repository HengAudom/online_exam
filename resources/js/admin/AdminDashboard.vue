<template>
  <div class="space-y-6">
    <!-- Stat Cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-2xl p-5 shadow-sm flex items-start gap-4"
        :class="card.accent ? 'bg-[#00288e] text-white' : 'bg-white border border-slate-200'"
      >
        <div
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
          :class="card.accent ? 'bg-white/20' : 'bg-[#00288e]/10'"
        >
          <span
            class="material-symbols-outlined text-2xl"
            :class="card.accent ? 'text-white' : 'text-[#00288e]'"
            style="font-variation-settings:'FILL' 1;"
          >{{ card.icon }}</span>
        </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest" :class="card.accent ? 'text-blue-200' : 'text-slate-400'">{{ card.label }}</p>
          <p class="mt-1 font-manrope text-3xl font-bold" :class="card.accent ? 'text-white' : 'text-[#00288e]'">{{ card.value }}</p>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
        <span class="material-symbols-outlined text-[#00288e]">notifications</span>
        <p class="font-semibold text-slate-900">Recent Activity</p>
      </div>
      <div class="divide-y divide-slate-100">
        <div
          v-for="(item, i) in dashboard.latestActivity"
          :key="i"
          class="flex items-start gap-4 px-6 py-4"
        >
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#00288e]/10">
            <span class="material-symbols-outlined text-[#00288e] text-sm" style="font-variation-settings:'FILL' 1;">check_circle</span>
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-800">{{ item.title }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ item.description }}</p>
          </div>
        </div>
        <div v-if="!dashboard.latestActivity?.length" class="px-6 py-8 text-center text-sm text-slate-400">
          No recent activity yet.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import axios from 'axios'

const dashboard = reactive({
  activeStudents: 0,
  publishedTests: 0,
  completedExams: 0,
  avgScore: 0,
  latestActivity: [],
})

const statCards = computed(() => [
  { label: 'Active Students',  value: dashboard.activeStudents, icon: 'group',      accent: true },
  { label: 'Published Tests',  value: dashboard.publishedTests, icon: 'quiz',       accent: false },
  { label: 'Completed Exams',  value: dashboard.completedExams, icon: 'task_alt',   accent: false },
  { label: 'Avg Score',        value: dashboard.avgScore + '%', icon: 'bar_chart',  accent: false },
])

onMounted(async () => {
  try {
    const res = await axios.get('/api/admin/dashboard')
    Object.assign(dashboard, res.data)
  } catch (e) {
    console.error('Failed to load dashboard', e)
  }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
</style>
