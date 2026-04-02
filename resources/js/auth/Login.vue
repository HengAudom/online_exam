<template>
  <div class="min-h-screen bg-slate-100 px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-md rounded-[2rem] bg-white p-8 shadow-2xl ring-1 ring-slate-900/5">
      <div class="space-y-4 text-center">
        <div class="flex justify-center mb-2">
          <img :src="'/ico.svg'" alt="Logo" class="h-16 w-auto" />
        </div>
        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Welcome Back</p>
        <h1 class="text-3xl font-semibold text-slate-900">Login to your account</h1>
        <p class="text-sm text-slate-500">Enter your credentials to access the student portal.</p>
      </div>

      <form @submit.prevent="handleLogin" class="mt-8 space-y-6">
        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Username</span>
          <input
            v-model="form.username"
            type="text"
            required
            placeholder="Enter username"
            class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
          />
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">Password</span>
          <input
            v-model="form.password"
            type="password"
            required
            placeholder="Enter password"
            class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
          />
        </label>

        <button
          type="submit"
          class="w-full rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Login
        </button>

        <p class="text-center text-sm text-slate-500">
          Don't have an account?
          <RouterLink to="/register" class="font-semibold text-slate-900 hover:text-slate-700">Register</RouterLink>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const form = reactive({
  username: '',
  password: ''
})

const handleLogin = async () => {
  if (!form.username || !form.password) {
    alert('Please enter both username and password.')
    return
  }

  try {
    const response = await axios.post('/api/login', {
      username: form.username,
      password: form.password
    })

    if (response.data.role === 'Admin' || response.data.role === 'SuperAdmin') {
      router.push('/admin/dashboard')
    } else {
      router.push('/student')
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Invalid username or password. Please try again.')
  }
}
</script>
