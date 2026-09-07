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

const identifierCache = new Map()
let checkDebounceTimer = null
let currentRequestId = 0

const onUsernameInput = () => {
  errorMessage.value = ''
  const val = form.username.trim()
  if (!val) {
    isAdminMode.value = false
    return
  }

  // Instant response from in-memory cache
  const lowerVal = val.toLowerCase()
  if (identifierCache.has(lowerVal)) {
    isAdminMode.value = identifierCache.get(lowerVal)
  }

  const reqId = ++currentRequestId

  if (checkDebounceTimer) clearTimeout(checkDebounceTimer)
  checkDebounceTimer = setTimeout(async () => {
    try {
      const res = await axios.post('/api/check-identifier', { identifier: val })
      // Guard against race conditions from out-of-order async responses
      if (reqId !== currentRequestId) return

      // Set admin mode directly based on Database check & cache
      const requiresPwd = Boolean(res.data?.requiresPassword)
      identifierCache.set(lowerVal, requiresPwd)
      isAdminMode.value = requiresPwd
    } catch (e) {
      // Ignore background check errors
    }
  }, 40)
}

onMounted(() => {
  fetchSettings()
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

  if (isAdminMode.value && !form.password) {
    errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលពាក្យសម្ងាត់' : 'Please enter your password.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const res = await axios.post('/api/login', {
      identifier: identifier,
      username: identifier,
      password: isAdminMode.value ? form.password : '',
      lang: lang.value
    })

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

    if (err.response?.status === 422 || err.response?.status === 404) {
      if (rawMsg.includes('Student ID') || rawMsg.includes('not found') || rawMsg.includes('រកមិនឃើញ')) {
        errorMessage.value = lang.value === 'kh'
          ? 'រកមិនឃើញ Student ID នេះឡើយ'
          : 'Student ID not found.'
      } else if (rawMsg.includes('Invalid password') || rawMsg.includes('ពាក្យសម្ងាត់')) {
        errorMessage.value = lang.value === 'kh'
          ? 'ពាក្យសម្ងាត់មិនត្រឹមត្រូវ'
          : 'Invalid password.'
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
          : (lang.value === 'kh' ? 'រកមិនឃើញ Student ID នេះឡើយ' : 'Student ID not found.')
      }
    } else {
      errorMessage.value = lang.value === 'kh'
        ? 'មានបញ្ហាតភ្ជាប់មូលដ្ឋានទិន្នន័យ សូមព្យាយាមម្តងទៀត'
        : 'Database connection error. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
