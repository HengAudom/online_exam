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
        <p>© 2026 OnlineXam</p>
        <!-- Developer Info Section (2 Rows) -->
        <div class="flex flex-col items-end justify-center text-right">
          <!-- Row 1: Social Contact Icons -->
          <div class="flex items-center gap-3 mb-1">
            <!-- Facebook Link -->
            <a 
              href="https://www.facebook.com/may.dom.bon.1502" 
              target="_blank" 
              rel="noopener noreferrer" 
              title="Facebook: Heng Audom"
              class="text-slate-400 hover:text-blue-500 transition-colors duration-200"
            >
              <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>

            <!-- Telegram Link -->
            <a 
              href="https://t.me/DomAi1" 
              target="_blank" 
              rel="noopener noreferrer" 
              title="Telegram: @DomAi1"
              class="text-slate-400 hover:text-sky-400 transition-colors duration-200"
            >
              <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.325-.635.325l.19-2.71 4.935-4.46c.214-.19-.047-.295-.332-.105L8.46 14.15l-2.628-.823c-.573-.18-.584-.573.12-.85L16.2 8.41c.475-.18.89.112.694.811z"/>
              </svg>
            </a>
          </div>

          <!-- Row 2: Developer Name -->
          <div class="text-[11px] font-medium tracking-wide text-slate-400">
            Dev by <span class="text-slate-200 font-semibold hover:text-white transition-colors">Heng Audom (ហេង ឧត្តម)</span>
          </div>
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
