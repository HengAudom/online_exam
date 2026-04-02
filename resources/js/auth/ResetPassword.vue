<template>
  <div class="min-h-screen bg-slate-100 px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-md rounded-[2rem] bg-white p-8 shadow-2xl ring-1 ring-slate-900/5">
      <div class="space-y-4 text-center">
        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Reset Password</p>
        <h1 class="text-3xl font-semibold text-slate-900">Set a new password</h1>
        <p class="text-sm text-slate-500">Enter the OTP sent to your email and create a strong new password.</p>
      </div>

      <form @submit.prevent="handleSavePassword" class="mt-8 space-y-6">
        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">OTP Code</span>
          <input
            v-model="form.otp"
            type="text"
            required
            placeholder="Enter the 6-digit OTP code"
            class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
          />
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">New Password</span>
          <input
            v-model="form.password"
            type="password"
            required
            placeholder="Enter new password"
            class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
          />
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Confirm New Password</span>
          <input
            v-model="form.confirmPassword"
            type="password"
            required
            placeholder="Confirm new password"
            class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
          />
        </label>

        <button
          type="submit"
          class="w-full rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Save New Password
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const email = route.query.email || ''

const form = reactive({
  otp: '',
  password: '',
  confirmPassword: ''
})

const handleSavePassword = async () => {
  if (!email) {
    alert('No email provided to reset password.')
    return
  }

  if (form.password !== form.confirmPassword) {
    alert('Passwords do not match.')
    return
  }

  try {
    await axios.post('/api/password/reset', {
      email,
      otp: form.otp,
      password: form.password
    })
    alert('Password updated successfully. Please login with your new password.')
    router.push('/login')
  } catch (error) {
    alert(error.response?.data?.message || 'Unable to reset password. Please check your OTP and try again.')
  }
}
</script>
