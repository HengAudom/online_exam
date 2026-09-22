<template>
  <PublicLayout>
    <Card padding="none" class="p-5 sm:p-8 shadow-soft-lg border border-slate-200/80 rounded-2xl sm:rounded-3xl">
      <!-- Form Header -->
      <div class="mb-5 sm:mb-6 space-y-1 sm:space-y-1.5">
        <h2 class="text-lg sm:text-2xl font-extrabold text-slate-900 tracking-tight">
          {{ isAdminMode ? (lang === 'kh' ? 'ចូលផ្ទាំងគ្រប់គ្រង' : 'Admin Sign In') : (lang === 'kh' ? 'ចូលប្រឡង' : 'Candidate Sign In') }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
          {{ isAdminMode ? (lang === 'kh' ? 'សូមបញ្ចូលពាក្យសម្ងាត់របស់អ្នកគ្រប់គ្រងដើម្បីបន្ត' : 'Please enter admin password to continue') : (lang === 'kh' ? 'បញ្ចូលលេខសម្គាល់សិស្ស ដើម្បីចូលបន្ទប់ប្រឡង' : 'Enter your Student ID to access the examination portal') }}
        </p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="mb-4 sm:mb-6 p-3 sm:p-3.5 bg-red-50/90 border border-red-200/90 rounded-xl sm:rounded-2xl flex items-center gap-2.5 text-xs sm:text-sm text-red-600 font-medium animate-fade-in"
      >
        <span class="material-symbols-outlined text-lg text-red-500 shrink-0">error</span>
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="space-y-4 sm:space-y-5">
        <!-- Student ID / Username Input (Single Universal Input) -->
        <Input
          ref="usernameInputRef"
          v-model="form.username"
          :label="isAdminMode ? (lang === 'kh' ? 'ឈ្មោះគណនី' : 'Username') : (lang === 'kh' ? 'លេខសម្គាល់សិស្ស' : 'Student ID')"
          :icon="isAdminMode ? 'person' : 'badge'"
          required
          :placeholder="isAdminMode ? (lang === 'kh' ? 'បញ្ចូលឈ្មោះគណនី' : 'Enter admin username') : (lang === 'kh' ? 'ឧ. RTC-XXXX-XXXXX' : 'e.g. RTC-XXXX-XXXXX')"
          @input="onUsernameInput"
        />

        <!-- Password (Auto-revealed smoothly when username matches Admin / Super Admin) -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2 max-h-0 overflow-hidden"
          enter-to-class="opacity-100 translate-y-0 max-h-40 overflow-visible"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0 max-h-40 overflow-visible"
          leave-to-class="opacity-0 -translate-y-2 max-h-0 overflow-hidden"
        >
          <div v-if="isAdminMode" class="space-y-1.5">
            <PasswordInput
              ref="passwordInputRef"
              v-model="form.password"
              :label="lang === 'kh' ? 'ពាក្យសម្ងាត់' : 'Password'"
              :placeholder="lang === 'kh' ? 'បញ្ចូលពាក្យសម្ងាត់' : 'Enter admin password'"
              required
              @input="errorMessage = ''"
            >
              <template #labelRight>
                <RouterLink
                  to="/forgot-password"
                  class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                >
                  {{ t.forgotPassword }}
                </RouterLink>
              </template>
            </PasswordInput>
          </div>
        </Transition>

        <!-- Dynamic Guidance Note -->
        <div class="p-3 sm:p-3.5 rounded-xl sm:rounded-2xl bg-blue-50/60 border border-blue-100 text-xs text-slate-600 flex items-start gap-2.5 leading-relaxed">
          <span class="material-symbols-outlined text-blue-600 text-base shrink-0 mt-0.5">
            {{ isAdminMode ? 'lock' : 'info' }}
          </span>
          <span v-if="isAdminMode" class="leading-relaxed">
            {{ lang === 'kh' ? '🔑 គណនីអ្នកគ្រប់គ្រង៖ សូមបញ្ចូលពាក្យសម្ងាត់ដើម្បីផ្ទៀងផ្ទាត់ និងចូលគ្រប់គ្រងប្រព័ន្ធ។' : '🔑 Admin Account: Password is required to authenticate.' }}
          </span>
          <span v-else class="leading-relaxed">
            {{ lang === 'kh' ? '💡 សិស្សប្រឡង៖ គ្រាន់តែវាយលេខសម្គាល់សិស្ស (RTC-XXXX-XXXXX) រួចចុចចូលប្រឡងភ្លាម (មិនចាំបាច់មានពាក្យសម្ងាត់ទេ)។' : '💡 Students: Enter your assigned Student ID (RTC-XXXX-XXXXX) and click login (no password needed).' }}
          </span>
        </div>

        <!-- Remember Identifier Option -->
        <div class="flex items-center pt-0.5">
          <label class="flex items-center gap-2.5 cursor-pointer select-none group">
            <input
              type="checkbox"
              v-model="rememberUsername"
              class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition-colors cursor-pointer"
            />
            <span class="text-xs sm:text-sm font-medium text-slate-600 group-hover:text-slate-900 transition-colors">
              {{ isAdminMode ? (lang === 'kh' ? 'ចងចាំឈ្មោះគណនី' : 'Remember Username') : (lang === 'kh' ? 'ចងចាំលេខសម្គាល់សិស្ស' : 'Remember Student ID') }}
            </span>
          </label>
        </div>

        <!-- CAPTCHA Verification (Displayed after multiple failed attempts) -->
        <div
          v-if="requiresCaptcha"
          class="p-3.5 bg-slate-50 border border-slate-200/90 rounded-xl sm:rounded-2xl space-y-2.5 animate-fade-in"
        >
          <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
            <span class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-base text-amber-500">security</span>
              {{ lang === 'kh' ? 'ផ្ទៀងផ្ទាត់សុវត្ថិភាព (Security Check)' : 'Security Verification' }}
            </span>
            <button
              type="button"
              @click="fetchCaptcha"
              class="text-blue-600 hover:text-blue-700 flex items-center gap-1 text-[11px] font-medium transition-colors cursor-pointer"
              :disabled="isCaptchaLoading"
              :title="lang === 'kh' ? 'ប្តូរកូដ' : 'Reload code'"
            >
              <span class="material-symbols-outlined text-sm" :class="{ 'animate-spin': isCaptchaLoading }">refresh</span>
              {{ lang === 'kh' ? 'ប្តូរកូដ' : 'Reload' }}
            </button>
          </div>
          <div class="flex items-center gap-3">
            <div class="p-1 bg-white border border-slate-200 rounded-lg shadow-xs select-none flex items-center justify-center min-w-[130px] h-[44px] overflow-hidden">
              <img
                v-if="captchaImage"
                :src="captchaImage"
                alt="Security CAPTCHA"
                class="h-full w-auto object-contain rounded select-none pointer-events-none"
              />
              <span v-else class="text-xs font-mono text-slate-400">...</span>
            </div>
            <input
              type="text"
              v-model="captchaAnswer"
              autocomplete="off"
              spellcheck="false"
              maxlength="10"
              :placeholder="lang === 'kh' ? 'បញ្ចូលលេខ/អក្សរក្នុងរូប' : 'Enter code shown'"
              class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono font-bold tracking-widest uppercase"
              required
            />
          </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-1">
          <Button
            type="submit"
            variant="primary"
            full-width
            size="md"
            class="py-2.5 sm:py-3 text-sm sm:text-base font-bold shadow-xs"
            :loading="isSubmitting"
            :icon="isAdminMode ? 'admin_panel_settings' : 'login'"
          >
            {{ isSubmitting ? t.submittingBtn : (isAdminMode ? (lang === 'kh' ? 'ចូលគ្រប់គ្រង' : 'Sign In as Admin') : (lang === 'kh' ? 'ចូលប្រឡង' : 'Enter Exam Portal')) }}
          </Button>
        </div>

        <!-- Divider & Register Link (Only for Candidates) -->
        <div v-if="settings.allowRegistration !== false && !isAdminMode" class="pt-4 sm:pt-5 border-t border-slate-100 text-center">
          <p class="text-xs sm:text-sm text-slate-500 font-medium">
            {{ t.noAccount }}
            <RouterLink
              to="/register"
              class="font-bold text-blue-600 hover:text-blue-700 hover:underline ml-1"
            >
              {{ t.createAccount }}
            </RouterLink>
          </p>
        </div>
      </form>
    </Card>
  </PublicLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'
import PublicLayout from '../layouts/PublicLayout.vue'
import Card from '../components/ui/Card.vue'
import Input from '../components/ui/Input.vue'
import PasswordInput from '../components/ui/PasswordInput.vue'
import Button from '../components/ui/Button.vue'
import { useLang } from '../utils/useLang'
import { useSettings } from '../composables/useSettings'

const router = useRouter()
const { lang } = useLang()
const { settings, fetchSettings } = useSettings()

const form = reactive({
  username: '',
  password: ''
})

const rememberUsername = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const isAdminMode = ref(false)
const passwordInputRef = ref(null)

const requiresCaptcha = ref(false)
const captchaImage = ref('')
const captchaToken = ref('')
const captchaAnswer = ref('')
const isCaptchaLoading = ref(false)

const fetchCaptcha = async () => {
  try {
    isCaptchaLoading.value = true
    const res = await axios.get('/api/auth/captcha')
    captchaImage.value = res.data.image
    captchaToken.value = res.data.token
    captchaAnswer.value = ''
  } catch (e) {
    console.error('Failed to load CAPTCHA challenge', e)
  } finally {
    isCaptchaLoading.value = false
  }
}

let checkIdentifierTimer = null

const onUsernameInput = () => {
  errorMessage.value = ''
  const val = form.username.trim()

  if (checkIdentifierTimer) {
    clearTimeout(checkIdentifierTimer)
  }

  // 1. If empty or less than 3 characters, do NOT show password field (stay in Candidate mode)
  if (!val || val.length < 3) {
    isAdminMode.value = false
    return
  }

  // 2. If matches Student ID format (starts with RTC- or numeric), stay in Candidate mode
  const isStudentPattern = /^rtc-|^[0-9]+$/i.test(val)
  if (isStudentPattern) {
    isAdminMode.value = false
    return
  }

  // 3. For 3+ characters, check with server if it matches existing Admin data
  checkIdentifierTimer = setTimeout(async () => {
    try {
      const res = await axios.post('/api/check-identifier', { identifier: val })
      // Prevent race conditions if user kept typing
      if (form.username.trim() === val) {
        isAdminMode.value = !!res.data.requiresPassword
      }
    } catch (e) {
      // If error occurs, do not force change
    }
  }, 200)
}

onMounted(() => {
  document.title = 'OnlineXam - Online Examination System'
  fetchSettings(true)
  const saved = localStorage.getItem('saved_login_username')
  if (saved) {
    form.username = saved
    rememberUsername.value = true
    onUsernameInput()
  }
})

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      signIn: 'ចូលប្រព័ន្ធ',
      forgotPassword: 'ភ្លេចពាក្យសម្ងាត់?',
      submittingBtn: 'កំពុងផ្ទៀងផ្ទាត់...',
      noAccount: 'មិនទាន់មានគណនី?',
      createAccount: 'ចុះឈ្មោះបង្កើតគណនីថ្មី'
    }
  }
  return {
    signIn: 'Sign In',
    forgotPassword: 'Forgot password?',
    submittingBtn: 'Signing in...',
    noAccount: "Don't have an account?",
    createAccount: 'Create an account'
  }
})

const handleLogin = async () => {
  const identifier = form.username.trim()
  if (!identifier) {
    errorMessage.value = isAdminMode.value
      ? (lang.value === 'kh' ? 'សូមបញ្ចូលឈ្មោះគណនី' : 'Please enter admin username.')
      : (lang.value === 'kh' ? 'សូមបញ្ចូល Student ID' : 'Please enter Student ID.')
    return
  }

  // If not yet switched to admin mode, but input is 3+ chars and matches admin data, prompt for password
  if (!isAdminMode.value && identifier.length >= 3 && !/^rtc-|^[0-9]+$/i.test(identifier)) {
    try {
      const checkRes = await axios.post('/api/check-identifier', { identifier })
      if (checkRes.data?.requiresPassword) {
        isAdminMode.value = true
        errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលពាក្យសម្ងាត់' : 'Please enter your password.'
        nextTick(() => {
          passwordInputRef.value?.focus()
        })
        return
      }
    } catch (e) {
      // Fall through to standard login flow
    }
  }

  if (isAdminMode.value && !form.password) {
    errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលពាក្យសម្ងាត់' : 'Please enter your password.'
    return
  }

  if (requiresCaptcha.value && !captchaAnswer.value.trim()) {
    errorMessage.value = lang.value === 'kh'
      ? 'សូមបញ្ចូលចម្លើយសុវត្ថិភាព (CAPTCHA)'
      : 'Please complete the CAPTCHA verification.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const payload = {
      identifier: identifier,
      username: identifier,
      password: isAdminMode.value ? form.password : '',
      lang: lang.value
    }

    if (requiresCaptcha.value) {
      payload.captcha_token = captchaToken.value
      payload.captcha_answer = captchaAnswer.value.trim()
    }

    const res = await axios.post('/api/login', payload)

    // Handle Remember Identifier persistence
    if (rememberUsername.value) {
      localStorage.setItem('saved_login_username', identifier)
    } else {
      localStorage.removeItem('saved_login_username')
    }

    localStorage.setItem('isAuthenticated', 'true')

    const role = res.data.role || res.data.user?.role || (res.data.loginType === 'student' ? 'Student' : 'Admin')
    if (role) {
      localStorage.setItem('userRole', role)
    }

    if (res.data.redirect) {
      router.push(res.data.redirect)
    } else if (['Admin', 'SuperAdmin', 'Super Admin'].includes(role)) {
      router.push('/admin/dashboard')
    } else {
      router.push('/student')
    }
  } catch (err) {
    const rawMsg = err.response?.data?.message || ''

    if (err.response?.data?.requiresCaptcha) {
      requiresCaptcha.value = true
      fetchCaptcha()
    }

    if (rawMsg && (rawMsg.includes('Password is required') || rawMsg.includes('ពាក្យសម្ងាត់') || rawMsg.includes('Admin'))) {
      isAdminMode.value = true
      errorMessage.value = lang.value === 'kh'
        ? 'សូមបញ្ចូលពាក្យសម្ងាត់សម្រាប់គណនី Admin'
        : 'Password is required for Admin login.'
      nextTick(() => {
        passwordInputRef.value?.focus?.()
      })
      return
    }

    if (!err.response) {
      // Network error, offline, or request aborted
      errorMessage.value = lang.value === 'kh'
        ? 'មិនអាចតភ្ជាប់ទៅកាន់ Server បានទេ សូមពិនិត្យមើលអ៊ីនធឺណិតរបស់អ្នក'
        : 'Unable to connect to the server. Please check your internet connection.'
      return
    }

    const status = err.response.status

    if (status === 429) {
      errorMessage.value = rawMsg || (lang.value === 'kh'
        ? 'អ្នកបានព្យាយាមចូលច្រើនដងពេក សូមរង់ចាំមួយភ្លែតរួចព្យាយាមម្តងទៀត'
        : 'Too many login attempts. Please wait a moment and try again.')
      return
    }

    if (status === 403) {
      errorMessage.value = lang.value === 'kh'
        ? 'គណនីនេះត្រូវបានផ្អាកជាបណ្ដោះអាសន្ន'
        : (rawMsg || 'Account is suspended.')
      return
    }

    if (status === 419) {
      errorMessage.value = lang.value === 'kh'
        ? 'Session ផុតកំណត់ សូម Refresh ទំព័ររួចព្យាយាមម្តងទៀត'
        : 'Session expired. Please refresh the page and try again.'
      return
    }

    if (status === 422 || status === 404) {
      if (rawMsg.includes('CAPTCHA') || rawMsg.includes('សុវត្ថិភាព')) {
        errorMessage.value = rawMsg
      } else if (rawMsg.includes('suspended') || rawMsg.includes('ផ្អាក')) {
        errorMessage.value = lang.value === 'kh'
          ? 'គណនីនេះត្រូវបានផ្អាកជាបណ្ដោះអាសន្ន'
          : 'Account is suspended.'
      } else if (rawMsg.includes('inactive') || rawMsg.includes('មិនអាច')) {
        errorMessage.value = lang.value === 'kh'
          ? 'គណនីសិស្សនេះមិនអាច Login បានទេ'
          : 'This student account is inactive.'
      } else {
        errorMessage.value = isAdminMode.value
          ? (lang.value === 'kh' ? 'ឈ្មោះគណនី ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ' : 'Invalid username or password.')
          : (lang.value === 'kh' ? 'លេខកូដសិស្ស ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ' : 'Invalid student ID or credentials.')
      }
    } else {
      // 500 or other unexpected server errors
      if (rawMsg && !rawMsg.toLowerCase().includes('database connection error')) {
        errorMessage.value = rawMsg
      } else {
        errorMessage.value = lang.value === 'kh'
          ? 'មានបញ្ហាតភ្ជាប់មូលដ្ឋានទិន្នន័យ សូមព្យាយាមម្តងទៀត'
          : 'Database connection error. Please try again.'
      }
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
