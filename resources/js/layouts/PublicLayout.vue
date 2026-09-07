<template>
  <div
    class="min-h-dvh w-full bg-slate-50 flex flex-col lg:flex-row select-none"
    @contextmenu.prevent
  >
    <!-- Left Brand Panel (Desktop) -->
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 bg-slate-900 text-white p-12 flex-col justify-between relative overflow-hidden shrink-0 min-h-screen">
      <!-- Background subtle gradient glow -->
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Brand Header -->
      <div class="relative z-10">
        <Logo variant="dark" size="lg" />
      </div>

      <!-- Feature Highlights -->
      <div class="relative z-10 space-y-8 my-auto max-w-md">
        <div class="space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs font-semibold text-blue-300 border border-white/10 backdrop-blur-xs">
            <span class="material-symbols-outlined text-sm">verified</span>
            Modern EdTech System
          </div>
          <h1 class="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight">
            {{ lang === 'kh' ? 'ប្រព័ន្ធគ្រប់គ្រងការប្រឡងអនឡាញ' : 'Next-Generation Online Examination System' }}
          </h1>
          <p class="text-slate-400 text-sm leading-relaxed">
            {{ lang === 'kh' ? 'ប្រព័ន្ធរៀបចំការប្រឡង វាយតម្លៃលទ្ធផលសិស្ស និងគ្រប់គ្រងទិន្នន័យដោយសុវត្ថិភាព និងប្រសិទ្ធភាពខ្ពស់។' : 'A robust, calm, and distraction-free workspace for assessments, student management, and real-time score analytics.' }}
          </p>
        </div>

        <div class="grid gap-4">
          <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/5 border border-white/5 backdrop-blur-xs">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/20 text-blue-400 shrink-0">
              <span class="material-symbols-outlined text-xl">quiz</span>
            </div>
            <div>
              <h4 class="text-sm font-bold text-white">{{ lang === 'kh' ? 'ការប្រឡងឆ្លាតវៃ' : 'Smart Assessments' }}</h4>
              <p class="text-xs text-slate-400 mt-0.5">{{ lang === 'kh' ? 'រក្សាទុកចម្លើយស្វ័យប្រវត្តិ និងកំណត់ពេលវេលាជាក់ស្ដែង' : 'Auto-saving answers, precise timers, and focus protection' }}</p>
            </div>
          </div>

          <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/5 border border-white/5 backdrop-blur-xs">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 shrink-0">
              <span class="material-symbols-outlined text-xl">bar_chart</span>
            </div>
            <div>
              <h4 class="text-sm font-bold text-white">{{ lang === 'kh' ? 'លទ្ធផលភ្លាមៗ' : 'Instant Analytics' }}</h4>
              <p class="text-xs text-slate-400 mt-0.5">{{ lang === 'kh' ? 'វិភាគពិន្ទុ អត្រាត្រឹមត្រូវ និងការពិនិត្យឡើងវិញលម្អិត' : 'Instant scoring, question review, and comprehensive metrics' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Info -->
      <div class="relative z-10 flex items-center justify-between text-xs text-slate-500">
        <p>© 2026 Online Exam System</p>
        <div class="flex items-center gap-1">
          <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
          <span>System Operational</span>
        </div>
      </div>
    </div>

    <!-- Right Form Container (Allows smooth scroll when rotated to landscape) -->
    <div class="flex-1 flex flex-col justify-between min-h-dvh p-4 sm:p-6 lg:p-12 overflow-y-auto">
      <!-- Mobile / Top Bar Header with Language Switcher -->
      <div class="flex items-center justify-between mb-3 sm:mb-4 shrink-0">
        <!-- Mobile Logo -->
        <Logo variant="public" size="sm" class="flex lg:hidden" />
        <div class="hidden lg:block"></div>

        <!-- Language Switcher -->
        <LangSwitcher variant="light" />
      </div>

      <!-- Form Center Area -->
      <div class="w-full max-w-md mx-auto my-auto py-2 sm:py-4 shrink-0">
        <slot></slot>
      </div>

      <!-- Bottom Small Copyright -->
      <div class="text-center text-[11px] sm:text-xs text-slate-400 py-2 shrink-0 z-10">
        <p>Protected by end-to-end encryption and session timeout monitoring.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import LangSwitcher from '../components/LangSwitcher.vue'
import Logo from '../components/ui/Logo.vue'
import { useLang } from '../utils/useLang'

const { lang } = useLang()

const blockInspect = (e) => {
  // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, Ctrl+U, Ctrl+S
  if (
    e.keyCode === 123 ||
    (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) ||
    (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 83))
  ) {
    e.preventDefault()
    e.stopPropagation()
    return false
  }
}

onMounted(() => {
  window.addEventListener('keydown', blockInspect)
  document.addEventListener('contextmenu', (e) => e.preventDefault())
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', blockInspect)
  document.removeEventListener('contextmenu', (e) => e.preventDefault())
})
</script>
