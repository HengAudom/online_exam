<template>
  <div class="min-h-screen bg-slate-100 px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-md rounded-[2rem] bg-white p-8 shadow-2xl ring-1 ring-slate-900/5">
      <div class="space-y-4 text-center">
        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Verify Email</p>
        <h1 class="text-3xl font-semibold text-slate-900">Almost there!</h1>
        <p class="text-sm text-slate-500">
          We sent a verification link to the account you just registered. Verify your email and then log in.
        </p>
      </div>

      <div class="mt-8 space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-left">
          <p class="text-sm font-medium text-slate-700">Account</p>
          <p class="mt-2 text-base font-semibold text-slate-900">{{ registeredUser || 'your email' }}</p>
          <p class="mt-3 text-sm text-slate-500">If you signed up with Google, please confirm the email address that appears in the OAuth flow.</p>
        </div>

        <button
          @click="handleResend"
          type="button"
          class="w-full rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Resend Verification Link
        </button>

        <button
          @click="handleVerified"
          type="button"
          class="w-full rounded-3xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
        >
          I Have Verified My Email
        </button>

        <p class="text-center text-sm text-slate-500">
          Already verified?
          <RouterLink to="/login" class="font-semibold text-slate-900 hover:text-slate-700">Go to Login</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

const router = useRouter()
const registeredUser = ref(localStorage.getItem('registeredUser') || '')

const handleResend = () => {
  console.log('Resend verification link for', registeredUser.value)
  alert('Verification link resent. Check your email inbox (or Gmail account).')
}

const handleVerified = () => {
  localStorage.setItem('verified', 'true')
  localStorage.removeItem('pendingVerification')
  alert('Email verified successfully. You can now log in.')
  router.push('/login')
}
</script>
