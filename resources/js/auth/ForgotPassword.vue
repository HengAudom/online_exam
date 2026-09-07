<template>
  <PublicLayout>
    <Card padding="lg" class="shadow-soft-lg max-w-md mx-auto">
      <div class="mb-6 space-y-1">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
            {{ lang === 'kh' ? `ជំហានទី ${currentStep} / ២` : `Step ${currentStep} of 2` }}
          </span>
          <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
          <span class="text-xs font-medium text-slate-500">
            {{ currentStep === 1 ? (lang === 'kh' ? 'ផ្ទៀងផ្ទាត់គណនី' : 'Account Verification') : (lang === 'kh' ? 'កំណត់ពាក្យសម្ងាត់ថ្មី' : 'Set New Password') }}
          </span>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
          {{ currentStep === 1 ? (lang === 'kh' ? 'ភ្លេចពាក្យសម្ងាត់?' : 'Forgot Password?') : (lang === 'kh' ? 'កំណត់ពាក្យសម្ងាត់ថ្មី' : 'Set New Password') }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500">
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

      <!-- Step 2: Set New Password -->
      <form v-else @submit.prevent="handleResetPassword" class="space-y-4">
        <!-- Verified User Chip -->
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-semibold flex items-center justify-between gap-2 animate-fade-in">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0">check_circle</span>
            <span>{{ lang === 'kh' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ៖' : 'Verified:' }} <strong class="text-emerald-950 font-extrabold">{{ verifiedDisplayName || form.username }}</strong></span>
          </div>
          <button
            type="button"
            @click="currentStep = 1; errorMessage = ''"
            class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 underline cursor-pointer"
          >
            {{ lang === 'kh' ? 'កែប្រែ' : 'Change' }}
          </button>
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
    </Card>
  </PublicLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
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

const form = reactive({
  username: '',
  phone: '',
  password: '',
  confirmPassword: ''
})

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
      phone: phone
    })

    verifiedDisplayName.value = res.data.displayName || username
    currentStep.value = 2
    toastSuccess(res.data.message || (lang.value === 'kh' ? 'ការផ្ទៀងផ្ទាត់ជោគជ័យ' : 'Identity verified.'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'ការផ្ទៀងផ្ទាត់មិនត្រឹមត្រូវ សូមពិនិត្យឈ្មោះគណនី និងលេខទូរស័ព្ទឡើងវិញ' : 'Verification failed. Please check your username and phone number.')
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
      password: form.password
    })

    toastSuccess(res.data.message || (lang.value === 'kh' ? 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ' : 'Password reset successfully.'))
    router.push('/login')
  } catch (error) {
    errorMessage.value = error.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចផ្លាស់ប្តូរពាក្យសម្ងាត់បានទេ សូមព្យាយាមម្តងទៀត' : 'Failed to reset password.')
  } finally {
    loading.value = false
  }
}
</script>
