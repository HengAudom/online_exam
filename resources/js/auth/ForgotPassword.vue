<template>
  <PublicLayout>
    <Card padding="lg" class="shadow-soft-lg max-w-md mx-auto">
      <div class="mb-6 space-y-1">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
            {{ lang === 'kh' ? `ជំហានទី ${currentStep} / ៣` : `Step ${currentStep} of 3` }}
          </span>
          <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
          <span class="text-xs font-medium text-slate-500">
            {{ currentStep === 1 
                ? (lang === 'kh' ? 'ផ្ទៀងផ្ទាត់គណនី' : 'Account Verification') 
                : (currentStep === 2 
                    ? (lang === 'kh' ? 'ផ្ទៀងផ្ទាត់កូដ OTP' : 'Verify OTP Code') 
                    : (lang === 'kh' ? 'កំណត់ពាក្យសម្ងាត់ថ្មី' : 'Set New Password')) }}
          </span>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
          {{ currentStep === 1 
              ? (lang === 'kh' ? 'ភ្លេចពាក្យសម្ងាត់?' : 'Forgot Password?') 
              : (currentStep === 2 
                  ? (lang === 'kh' ? 'ផ្ទៀងផ្ទាត់កូដ OTP' : 'Verify OTP Code') 
                  : (lang === 'kh' ? 'កំណត់ពាក្យសម្ងាត់ថ្មី' : 'Set New Password')) }}
        </h2>
        <p v-if="currentStep !== 2" class="text-xs sm:text-sm text-slate-500">
          {{ currentStep === 1 
            ? (lang === 'kh' ? 'បញ្ចូលឈ្មោះគណនី និងលេខទូរស័ព្ទដែលបានចុះឈ្មោះ ដើម្បីផ្ទៀងផ្ទាត់' : 'Enter your username and registered phone number to verify your account.') 
            : (lang === 'kh' ? 'បញ្ចូលពាក្យសម្ងាត់ថ្មីសម្រាប់គណនីរបស់អ្នក' : 'Create a new secure password for your account.') }}
        </p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="mb-5 flex items-center gap-2.5 p-3.5 rounded-2xl bg-red-50 text-red-700 border border-red-200/80 text-xs font-semibold animate-fade-in"
      >
        <span class="material-symbols-outlined text-lg shrink-0">error</span>
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Step 1: Verify Username & Phone -->
      <form v-if="currentStep === 1" @submit.prevent="handleVerifyIdentity" class="space-y-4">
        <Input
          v-model="form.username"
          type="text"
          required
          icon="person"
          :label="lang === 'kh' ? 'ឈ្មោះគណនី' : 'Username'"
          :placeholder="lang === 'kh' ? 'បញ្ចូលឈ្មោះគណនីរបស់អ្នក...' : 'Enter your username...'"
          @input="errorMessage = ''"
        />

        <Input
          v-model="form.phone"
          type="text"
          required
          icon="call"
          :label="lang === 'kh' ? 'លេខទូរស័ព្ទដែលបានចុះឈ្មោះ' : 'Registered Phone Number'"
          :placeholder="lang === 'kh' ? 'ឧ. 012 345 678' : 'e.g. 012 345 678'"
          @input="errorMessage = ''"
        />

        <div class="pt-2">
          <Button
            type="submit"
            variant="primary"
            full-width
            size="lg"
            :loading="loading"
            icon="arrow_forward"
          >
            {{ loading ? (lang === 'kh' ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'Verifying...') : (lang === 'kh' ? 'ផ្ទៀងផ្ទាត់គណនី' : 'Verify Account') }}
          </Button>
        </div>

        <div class="pt-4 border-t border-slate-100 text-center">
          <RouterLink
            to="/login"
            class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline inline-flex items-center gap-1"
          >
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            {{ lang === 'kh' ? 'ត្រឡប់ទៅចូលប្រព័ន្ធ' : 'Back to Sign In' }}
          </RouterLink>
        </div>
      </form>

      <!-- Step 2: Verify Telegram OTP (6-box style from Downloads/OTP) -->
      <form v-else-if="currentStep === 2" @submit.prevent="handleVerifyOtp" class="space-y-6">
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-bold text-slate-700">
              {{ lang === 'kh' ? 'លេខកូដ OTP (៦ ខ្ទង់)' : 'Verification Code (6 digits)' }}
              <span class="text-red-500">*</span>
            </label>
            <button
              type="button"
              @click="handleResendOtp"
              :disabled="resendCountdown > 0 || resendLoading"
              class="text-[11px] font-bold text-blue-600 hover:text-blue-700 disabled:text-slate-400 cursor-pointer disabled:cursor-not-allowed inline-flex items-center gap-1 transition-colors"
            >
              <span class="material-symbols-outlined text-xs" :class="{ 'animate-spin': resendLoading }">sync</span>
              <span v-if="resendCountdown > 0">
                {{ lang === 'kh' ? `ផ្ញើម្ដងទៀត (${resendCountdown}s)` : `Resend in ${resendCountdown}s` }}
              </span>
              <span v-else>
                {{ lang === 'kh' ? 'ផ្ញើកូដឡើងវិញ' : 'Resend OTP' }}
              </span>
            </button>
          </div>

          <!-- 6-box OTP Container -->
          <div class="otp-container" :class="{ 'is-complete': form.otp.length === 6 }">
            <input
              v-for="(digit, idx) in otpDigits"
              :key="idx"
              :ref="el => otpBoxes[idx] = el"
              type="text"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="1"
              :value="digit"
              class="otp-box"
              :class="{
                'is-filled': digit !== '',
                'is-complete': form.otp.length === 6
              }"
              @input="onOtpInput(idx, $event)"
              @keydown="onOtpKeydown(idx, $event)"
              @paste="onOtpPaste(idx, $event)"
              @focus="$event.target.select()"
            />
          </div>
        </div>

        <div class="pt-2">
          <Button
            type="submit"
            variant="primary"
            full-width
            size="lg"
            :loading="loading"
            :disabled="form.otp.length !== 6"
            icon="verified_user"
          >
            {{ loading ? (lang === 'kh' ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'Verifying OTP...') : (lang === 'kh' ? 'ផ្ទៀងផ្ទាត់កូដ OTP' : 'Verify OTP Code') }}
          </Button>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
          <button
            type="button"
            @click="currentStep = 1; errorMessage = ''"
            class="font-bold text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 cursor-pointer transition-colors"
          >
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            {{ lang === 'kh' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
          </button>

          <RouterLink
            to="/login"
            class="font-bold text-blue-600 hover:text-blue-700 hover:underline inline-flex items-center gap-1"
          >
            {{ lang === 'kh' ? 'ត្រឡប់ទៅចូលប្រព័ន្ធ' : 'Back to Sign In' }}
          </RouterLink>
        </div>
      </form>

      <!-- Step 3: Set New Password -->
      <form v-else-if="currentStep === 3" @submit.prevent="handleResetPassword" class="space-y-4">
        <!-- Verified User & OTP Success Chip -->
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-semibold flex items-center justify-between gap-2 animate-fade-in">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0">verified</span>
            <span>{{ lang === 'kh' ? 'OTP ត្រឹមត្រូវ៖' : 'OTP Verified:' }} <strong class="text-emerald-950 font-extrabold">{{ verifiedDisplayName || form.username }}</strong></span>
          </div>
          <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
            {{ lang === 'kh' ? 'ជោគជ័យ' : 'Verified' }}
          </span>
        </div>

        <PasswordInput
          v-model="form.password"
          :label="lang === 'kh' ? 'ពាក្យសម្ងាត់ថ្មី' : 'New Password'"
          required
          :placeholder="lang === 'kh' ? 'បញ្ចូលពាក្យសម្ងាត់ថ្មី (យ៉ាងតិច ៦ ខ្ទង់)' : 'Enter new password (min 6 chars)'"
          @input="errorMessage = ''"
        />

        <PasswordInput
          v-model="form.confirmPassword"
          :label="lang === 'kh' ? 'បញ្ជាក់ពាក្យសម្ងាត់ថ្មី' : 'Confirm New Password'"
          required
          :placeholder="lang === 'kh' ? 'បញ្ជាក់ពាក្យសម្ងាត់ថ្មីម្តងទៀត' : 'Confirm new password'"
          @input="errorMessage = ''"
        />

        <div class="pt-2">
          <Button
            type="submit"
            variant="primary"
            full-width
            size="lg"
            :loading="loading"
            icon="lock_reset"
          >
            {{ loading ? (lang === 'kh' ? 'កំពុងផ្លាស់ប្តូរ...' : 'Updating...') : (lang === 'kh' ? 'ផ្លាស់ប្តូរពាក្យសម្ងាត់' : 'Reset Password') }}
          </Button>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
          <button
            type="button"
            @click="currentStep = 2; errorMessage = ''"
            class="font-bold text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 cursor-pointer transition-colors"
          >
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            {{ lang === 'kh' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
          </button>

          <RouterLink
            to="/login"
            class="font-bold text-blue-600 hover:text-blue-700 hover:underline inline-flex items-center gap-1"
          >
            {{ lang === 'kh' ? 'ត្រឡប់ទៅចូលប្រព័ន្ធ' : 'Back to Sign In' }}
          </RouterLink>
        </div>
      </form>
    </Card>
  </PublicLayout>
</template>

<script setup>
import { reactive, ref, onBeforeUnmount, nextTick } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'
import PublicLayout from '../layouts/PublicLayout.vue'
import Card from '../components/ui/Card.vue'
import Input from '../components/ui/Input.vue'
import PasswordInput from '../components/ui/PasswordInput.vue'
import Button from '../components/ui/Button.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'

const router = useRouter()
const { lang } = useLang()
const { success: toastSuccess } = useToast()

const currentStep = ref(1)
const verifiedDisplayName = ref('')
const loading = ref(false)
const errorMessage = ref('')
const resendCountdown = ref(0)
const resendLoading = ref(false)
let timer = null

const otpDigits = ref(['', '', '', '', '', ''])
const otpBoxes = ref([])

const form = reactive({
  username: '',
  phone: '',
  otp: '',
  password: '',
  confirmPassword: ''
})

const focusFirstOtpBox = () => {
  nextTick(() => {
    otpBoxes.value[0]?.focus()
  })
}

const startCountdown = () => {
  resendCountdown.value = 60
  if (timer) clearInterval(timer)
  timer = setInterval(() => {
    if (resendCountdown.value > 0) {
      resendCountdown.value--
    } else {
      clearInterval(timer)
      timer = null
    }
  }, 1000)
}

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

const onOtpInput = (index, event) => {
  const raw = event.target.value || ''
  const digit = raw.replace(/[^0-9]/g, '').slice(-1)
  otpDigits.value[index] = digit
  event.target.value = digit
  errorMessage.value = ''

  form.otp = otpDigits.value.join('')

  if (digit && index < 5) {
    otpBoxes.value[index + 1]?.focus()
  }
}

const onOtpKeydown = (index, event) => {
  if (event.key === 'Backspace') {
    if (!otpDigits.value[index] && index > 0) {
      otpDigits.value[index - 1] = ''
      if (otpBoxes.value[index - 1]) {
        otpBoxes.value[index - 1].value = ''
        otpBoxes.value[index - 1].focus()
      }
    } else {
      otpDigits.value[index] = ''
      event.target.value = ''
    }
    form.otp = otpDigits.value.join('')
    errorMessage.value = ''
  } else if (event.key === 'ArrowLeft' && index > 0) {
    otpBoxes.value[index - 1]?.focus()
  } else if (event.key === 'ArrowRight' && index < 5) {
    otpBoxes.value[index + 1]?.focus()
  }
}

const onOtpPaste = (index, event) => {
  event.preventDefault()
  const pasted = (event.clipboardData?.getData('text') || '').replace(/[^0-9]/g, '').trim()
  if (!pasted) return

  for (let j = 0; j < pasted.length && (index + j) < 6; j++) {
    otpDigits.value[index + j] = pasted[j]
    if (otpBoxes.value[index + j]) {
      otpBoxes.value[index + j].value = pasted[j]
    }
  }

  form.otp = otpDigits.value.join('')
  const targetIndex = Math.min(index + pasted.length, 5)
  otpBoxes.value[targetIndex]?.focus()
  errorMessage.value = ''
}

const handleVerifyIdentity = async () => {
  const username = form.username.trim()
  const phone = form.phone.trim()

  if (!username) {
    errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលឈ្មោះគណនីរបស់អ្នក' : 'Please enter your username.'
    return
  }

  if (!phone) {
    errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលលេខទូរស័ព្ទដែលបានចុះឈ្មោះ' : 'Please enter your registered phone number.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const res = await axios.post('/api/password/verify-identity', {
      username: username,
      phone: phone,
      lang: lang.value
    })

    if (res.data.username) {
      form.username = res.data.username
    }
    verifiedDisplayName.value = res.data.displayName || username
    form.otp = ''
    otpDigits.value = ['', '', '', '', '', '']
    currentStep.value = 2
    startCountdown()
    focusFirstOtpBox()
    toastSuccess(res.data.message || (lang.value === 'kh' ? 'លេខកូដ OTP ត្រូវបានផ្ញើទៅ Telegram រួចរាល់' : 'OTP dispatched to Telegram.'))
  } catch (error) {
    if (error.response?.status === 429) {
      errorMessage.value = lang.value === 'kh'
        ? 'អ្នកបានព្យាយាមច្រើនដងពេកហើយ! សូមរង់ចាំ ១ នាទី រួចសាកល្បងម្ដងទៀត (Too Many Attempts. Please wait a minute).'
        : 'Too many attempts. Please wait a minute and try again.'
    } else {
      errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'ការផ្ទៀងផ្ទាត់មិនត្រឹមត្រូវ សូមពិនិត្យឈ្មោះគណនី និងលេខទូរស័ព្ទឡើងវិញ' : 'Verification failed. Please check your username and phone number.')
    }
  } finally {
    loading.value = false
  }
}

const handleResendOtp = async () => {
  if (resendCountdown.value > 0 || resendLoading.value) return
  resendLoading.value = true
  errorMessage.value = ''

  try {
    const res = await axios.post('/api/password/verify-identity', {
      username: form.username.trim(),
      phone: form.phone.trim(),
      lang: lang.value
    })

    form.otp = ''
    otpDigits.value = ['', '', '', '', '', '']
    startCountdown()
    focusFirstOtpBox()
    toastSuccess(res.data.message || (lang.value === 'kh' ? 'លេខកូដ OTP ថ្មីត្រូវបានផ្ញើទៅកាន់ Telegram' : 'New OTP dispatched to Telegram.'))
  } catch (error) {
    if (error.response?.status === 429) {
      errorMessage.value = lang.value === 'kh'
        ? 'អ្នកបានព្យាយាមច្រើនដងពេកហើយ! សូមរង់ចាំ ១ នាទី (Too Many Attempts).'
        : 'Too many attempts. Please wait a minute.'
    } else {
      errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចផ្ញើកូដឡើងវិញបានទេ' : 'Failed to resend OTP.')
    }
  } finally {
    resendLoading.value = false
  }
}

const handleVerifyOtp = async () => {
  if (!form.otp || form.otp.trim().length !== 6) {
    errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលលេខកូដ OTP ៦ ខ្ទង់ដែលបានផ្ញើទៅ Telegram' : 'Please enter the 6-digit OTP code sent to Telegram.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const res = await axios.post('/api/password/verify-otp', {
      username: form.username.trim(),
      phone: form.phone.trim(),
      otp: form.otp.trim(),
      lang: lang.value
    })

    currentStep.value = 3
    toastSuccess(res.data.message || (lang.value === 'kh' ? 'លេខកូដ OTP ត្រឹមត្រូវ' : 'OTP verified successfully.'))
  } catch (error) {
    if (error.response?.status === 429) {
      errorMessage.value = lang.value === 'kh'
        ? 'អ្នកបានព្យាយាមច្រើនដងពេកហើយ! សូមរង់ចាំ ១ នាទី (Too Many Attempts).'
        : 'Too many attempts. Please wait a minute.'
    } else {
      errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ សូមពិនិត្យមើលសារក្នុង Telegram ឡើងវិញ' : 'Incorrect OTP code. Please check your Telegram.')
    }
  } finally {
    loading.value = false
  }
}

const handleResetPassword = async () => {
  if (form.password.length < 6) {
    errorMessage.value = lang.value === 'kh' ? 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច ៦ ខ្ទង់' : 'Password must be at least 6 characters.'
    return
  }

  if (form.password !== form.confirmPassword) {
    errorMessage.value = lang.value === 'kh' ? 'ពាក្យសម្ងាត់ទាំងពីរមិនត្រូវគ្នាទេ' : 'Passwords do not match.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const res = await axios.post('/api/password/reset', {
      username: form.username.trim(),
      phone: form.phone.trim(),
      otp: form.otp.trim(),
      password: form.password,
      lang: lang.value
    })

    toastSuccess(res.data.message || (lang.value === 'kh' ? 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ' : 'Password reset successfully.'))
    router.push('/login')
  } catch (error) {
    if (error.response?.status === 429) {
      errorMessage.value = lang.value === 'kh'
        ? 'អ្នកបានព្យាយាមច្រើនដងពេកហើយ! សូមរង់ចាំ ១ នាទី រួចសាកល្បងម្ដងទៀត (Too Many Attempts. Please wait a minute).'
        : 'Too many attempts. Please wait a minute and try again.'
    } else {
      errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចផ្លាស់ប្តូរពាក្យសម្ងាត់បានទេ' : 'Failed to reset password.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.otp-container {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin: 14px 0 10px;
}

@media (min-width: 400px) {
  .otp-container {
    gap: 12px;
  }
}

.otp-box {
  width: 46px;
  height: 58px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  color: #0f172a;
  background: #f8fafc;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  outline: none;
}

@media (min-width: 400px) {
  .otp-box {
    width: 50px;
    height: 60px;
  }
}

.otp-box:focus {
  border-color: #2563eb;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
  transform: translateY(-2px);
}

.otp-box.is-filled {
  border-color: #3b82f6;
  background: #ffffff;
}

.otp-container.is-complete .otp-box {
  border-color: #10b981;
  color: #059669;
  background: #f0fdf4;
}
</style>
