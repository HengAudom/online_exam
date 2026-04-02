<template>
  <div class="min-h-screen bg-slate-100 px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-md rounded-[2rem] bg-white p-8 shadow-2xl ring-1 ring-slate-900/5">
      <div class="space-y-4 text-center">
        <div class="flex justify-center mb-2">
          <img :src="'/ico.svg'" alt="Logo" class="h-16 w-auto" />
        </div>
        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Reset Password</p>
        <h1 class="text-3xl font-semibold text-slate-900">Forgot your password?</h1>
        <p class="text-sm text-slate-500">Enter your email and we will send you an OTP code.</p>
      </div>

      <form @submit.prevent="handleSendLink" class="mt-8 space-y-6">
        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Email Address</span>
          <input
            v-model="email"
            type="email"
            required
            placeholder="Enter your email"
            class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
          />
        </label>

        <button
          type="submit"
          class="w-full rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Send Reset Link
        </button>

        <p class="text-center text-sm text-slate-500">
          Remembered your password?
          <RouterLink to="/login" class="font-semibold text-slate-900 hover:text-slate-700">Back to Login</RouterLink>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const email = ref('')

const handleSendLink = async () => {
  const userEmail = email.value.trim()
  if (!userEmail) {
    alert('Please enter your email.')
    return
  }

  try {
    const res = await axios.post('/api/password/forgot', { email: userEmail })
    alert(res.data.message || 'OTP sent successfully. Please check your email inbox.')
    router.push({ name: 'ResetPassword', query: { email: userEmail } })
  } catch (error) {
    alert(error.response?.data?.message || 'Email not found. Please check your input.')
  }
}
</script>
