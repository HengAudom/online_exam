<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans">
    <!-- Topbar Header -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-8 py-3 shadow-soft-xs">
      <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
        <!-- Brand & Student Badge -->
        <div>
          <Logo variant="student" badge="Online" />
        </div>

        <!-- Right: Actions & User Menu -->
        <div class="flex items-center gap-3">
          <LangSwitcher variant="light" />

          <!-- User Profile & Sign Out Button -->
          <button
            type="button"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-red-50 hover:border-red-200 text-slate-700 hover:text-red-600 transition-colors text-xs font-bold cursor-pointer"
            title="Sign Out"
            @click="showLogoutModal = true"
          >
            <span class="material-symbols-outlined text-base">logout</span>
            <span class="hidden sm:inline">{{ lang === 'kh' ? 'ចាកចេញ' : 'Sign Out' }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 pb-16 lg:pb-8">
      <slot></slot>
    </main>

    <!-- Logout Confirmation -->
    <ConfirmDialog
      v-model="showLogoutModal"
      :title="lang === 'kh' ? 'តើអ្នកចង់ចាកចេញពីប្រព័ន្ធ?' : 'Sign out of student portal?'"
      :message="lang === 'kh' ? 'អ្នកនឹងត្រូវចូលប្រព័ន្ធម្តងទៀតដើម្បីចូលធ្វើការប្រឡង។' : 'You will need to sign in again to access exams and results.'"
      :confirm-text="lang === 'kh' ? 'ចាកចេញ' : 'Sign Out'"
      :cancel-text="lang === 'kh' ? 'បោះបង់' : 'Cancel'"
      confirm-variant="danger"
      icon="logout"
      :loading="loggingOut"
      @confirm="confirmLogout"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { logout as apiLogout } from '../utils/auth'
import { useLang } from '../utils/useLang'
import LangSwitcher from '../components/LangSwitcher.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Logo from '../components/ui/Logo.vue'

const { lang } = useLang()

const showLogoutModal = ref(false)
const loggingOut = ref(false)

const confirmLogout = async () => {
  loggingOut.value = true
  await apiLogout()
}
</script>
