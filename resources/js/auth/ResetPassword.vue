<template>
  <PublicLayout>
    <Card padding="lg" class="shadow-soft-lg max-w-md mx-auto">
      <div class="mb-6 space-y-1">
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
          {{ lang === 'kh' ? 'កំណត់ពាក្យសម្ងាត់ថ្មី' : 'Set New Password' }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500">
          {{ lang === 'kh' ? 'បញ្ចូលលេខកូដ OTP ៦ ខ្ទង់ដែលបានផ្ញើទៅ ' : 'Enter the 6-digit OTP code sent to ' }}
          <strong class="text-blue-700 font-bold">{{ email || (lang === 'kh' ? 'គណនីរបស់អ្នក' : 'your account') }}</strong>
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

      <!-- Demo OTP Info Helper (if email server offline) -->
      <div
        v-if="hintOtp"
        class="mb-4 p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs flex items-center justify-between gap-2"
      >
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-base text-blue-600">vpn_key</span>
          <span>{{ lang === 'kh' ? 'លេខកូដ OTP របស់អ្នក៖' : 'Your OTP Code:' }} <strong class="font-mono text-sm font-extrabold tracking-wider text-blue-900">{{ hintOtp }}</strong></span>
        </div>
        <button
          type="button"
          class="text-[11px] font-bold text-blue-700 underline hover:text-blue-900 cursor-pointer"
          @click="form.otp = hintOtp"
        >
          {{ lang === 'kh' ? 'ប្រើកូដនេះ' : 'Use Code' }}
        </button>
      </div>

      <form @submit.prevent="handleSavePassword" class="space-y-4">
        <div>
          <Input
            v-model="form.otp"
            :label="lang === 'kh' ? 'លេខកូដ OTP (៦ ខ្ទង់)' : 'OTP Code (6 Digits)'"
            icon="pin"
            required
            maxlength="6"
            placeholder="e.g. 123456"
            @input="errorMessage = ''"
          />
          <div class="flex justify-end mt-1.5">
            <button
              type="button"
              :disabled="resendCooldown > 0 || resending"
              :class="[
                'text-xs font-bold transition-colors cursor-pointer',
                resendCooldown > 0 || resending ? 'text-slate-400 cursor-not-allowed' : 'text-blue-600 hover:text-blue-700 hover:underline'
              ]"
              @click="handleResendOtp"
            >
              {{ resending ? (lang === 'kh' ? 'កំពុងផ្ញើ...' : 'Sending...') : resendCooldown > 0 ? `${lang === 'kh' ? 'ផ្ញើម្តងទៀតក្នុង' : 'Resend in'} ${resendCooldown}s` : (lang === 'kh' ? 'ផ្ញើលេខកូដ OTP ម្តងទៀត' : 'Resend OTP Code') }}
            </button>
          </div>
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
            icon="save"
          >
            {{ loading ? (lang === 'kh' ? 'កំពុងរក្សាទុក...' : 'Updating...') : (lang === 'kh' ? 'រក្សាទុកពាក្យសម្ងាត់ថ្មី' : 'Save New Password') }}
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
    </Card>
  </PublicLayout>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import axios from 'axios'
import PublicLayout from '../layouts/PublicLayout.vue'
import Card from '../components/ui/Card.vue'
import Input from '../components/ui/Input.vue'
import PasswordInput from '../components/ui/PasswordInput.vue'
import Button from '../components/ui/Button.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()

const email = ref(route.query.email || '')
const hintOtp = ref(route.query.hintOtp || '')
const loading = ref(false)
const resending = ref(false)
const resendCooldown = ref(0)
let timer = null

const errorMessage = ref('')

const form = reactive({
  otp: route.query.hintOtp || '',
  password: '',
  confirmPassword: ''
})

const startCooldown = (seconds = 60) => {
  resendCooldown.value = seconds
  if (timer) clearInterval(timer)
  timer = setInterval(() => {
    if (resendCooldown.value > 0) {
      resendCooldown.value--
    } else {
      clearInterval(timer)
    }
  }, 1000)
}

const handleResendOtp = async () => {
  if (!email.value || resendCooldown.value > 0) return
  resending.value = true
  errorMessage.value = ''
  try {
    const res = await axios.post('/api/password/forgot', { email: email.value })
    if (res.data.otp) {
      hintOtp.value = res.data.otp
      form.otp = res.data.otp
    }
    toastSuccess(res.data.message || (lang.value === 'kh' ? 'លេខកូដ OTP ថ្មីត្រូវបានផ្ញើជូន' : 'New OTP sent.'))
    startCooldown(60)
  } catch (error) {
    toastError(error.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចផ្ញើលេខកូដបានទេ' : 'Failed to resend OTP.'))
  } finally {
    resending.value = false
  }
}

const handleSavePassword = async () => {
  if (!email.value) {
    errorMessage.value = lang.value === 'kh' ? 'រកមិនឃើញឈ្មោះគណនី ឬ អ៊ីមែលសម្រាប់កំណត់ឡើងវិញ' : 'No account provided.'
    return
  }

  if (!form.otp || form.otp.length < 4) {
    errorMessage.value = lang.value === 'kh' ? 'សូមបញ្ចូលលេខកូដ OTP ឲ្យបានត្រឹមត្រូវ' : 'Please enter valid OTP code.'
    return
  }

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
      email: email.value,
      otp: form.otp.trim(),
      password: form.password
    })
    toastSuccess(res.data.message || (lang.value === 'kh' ? 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ' : 'Password reset successfully. Please log in.'))
    router.push('/login')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចកំណត់ពាក្យសម្ងាត់បានទេ សូមពិនិត្យលេខកូដ OTP' : 'Failed to reset password. Please check your OTP.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  startCooldown(30)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>
