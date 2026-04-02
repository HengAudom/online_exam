<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-4xl overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-900/5">
      <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
        <div class="hidden bg-slate-900 p-10 text-white lg:block">
          <div class="space-y-6">
            <div class="flex justify-center">
              <img :src="'/ico.svg'" alt="Logo" class="h-20 w-auto brightness-0 invert opacity-90" />
            </div>
            <div>
              <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Student Registration</p>
              <h2 class="mt-4 text-3xl font-semibold tracking-tight">Join the Exam Portal</h2>
            </div>
            <p class="text-slate-300">
              Create your student account to access exams, view results, and manage your assigned tests.
            </p>
            <div class="grid gap-4 rounded-3xl bg-slate-800/80 p-6">
              <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Secure</p>
                <p class="mt-2 text-sm text-slate-300">Your password is validated and the form is ready to connect to your backend authentication API.</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Smart</p>
                <p class="mt-2 text-sm text-slate-300">Student registration maps to Users and tblStudent fields in your database schema.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="px-8 py-10 sm:px-12 sm:py-12">
          <div class="mx-auto max-w-xl">
            <h3 class="text-2xl font-semibold text-slate-900">Register Account</h3>
            <p class="mt-2 text-sm text-slate-500">Fill in your details to create a student account.</p>

            <form @submit.prevent="handleRegister" class="mt-8 space-y-6">
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">First Name</span>
                  <input
                    v-model="form.firstName"
                    type="text"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  />
                </label>
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Last Name</span>
                  <input
                    v-model="form.lastName"
                    type="text"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  />
                </label>
              </div>

              <label class="block">
                <span class="mb-2 block text-sm font-medium text-slate-700">Username</span>
                <input
                  v-model="form.username"
                  type="text"
                  required
                  class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                />
              </label>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Email</span>
                  <input
                    v-model="form.email"
                    type="email"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  />
                </label>
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Phone</span>
                  <input
                    v-model="form.phone"
                    type="tel"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  />
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Password</span>
                  <input
                    v-model="form.password"
                    type="password"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  />
                </label>
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Confirm Password</span>
                  <input
                    v-model="form.confirmPassword"
                    type="password"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  />
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                  <p class="mb-2 text-sm font-medium text-slate-700">Gender</p>
                  <div class="flex flex-wrap gap-3">
                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-slate-300">
                      <input type="radio" value="Female" v-model="form.gender" class="h-4 w-4 text-slate-900" />
                      Female
                    </label>
                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-slate-300">
                      <input type="radio" value="Male" v-model="form.gender" class="h-4 w-4 text-slate-900" />
                      Male
                    </label>
                    <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:border-slate-300">
                      <input type="radio" value="Other" v-model="form.gender" class="h-4 w-4 text-slate-900" />
                      Other
                    </label>
                  </div>
                </div>

                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Study Shift</span>
                  <select
                    v-model="form.shift"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  >
                    <option disabled value="">Select shift</option>
                    <option>Morning</option>
                    <option>Afternoon</option>
                    <option>Evening</option>
                  </select>
                </label>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Select Skill</span>
                  <select
                    v-model="form.skill"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  >
                    <option disabled value="">Select skill</option>
                    <option v-for="skill in skillOptions" :key="skill" :value="skill">{{ skill }}</option>
                  </select>
                </label>

                <label class="block">
                  <span class="mb-2 block text-sm font-medium text-slate-700">Select Batch</span>
                  <select
                    v-model="form.batch"
                    required
                    class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white"
                  >
                    <option disabled value="">Select batch</option>
                    <option v-for="batch in batchOptions" :key="batch" :value="batch">{{ batch }}</option>
                  </select>
                </label>
              </div>

              <button
                type="submit"
                class="w-full rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
              >
                Register
              </button>

              <p class="text-center text-sm text-slate-500">
                Already have an account?
                <RouterLink to="/login" class="font-semibold text-slate-900 hover:text-slate-700">Login</RouterLink>
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const skillOptions = ref(['Graphics Design', 'Computer Service', 'Web Development', 'Data Science'])
const batchOptions = ref(['Batch 1', 'Batch 2', 'Batch 3', 'Batch 4'])

const form = reactive({
  firstName: '',
  lastName: '',
  username: '',
  email: '',
  phone: '',
  password: '',
  confirmPassword: '',
  gender: '',
  shift: '',
  skill: '',
  batch: ''
})

const handleRegister = async () => {
  if (form.password !== form.confirmPassword) {
    alert('Passwords do not match.')
    return
  }

  try {
    await axios.post('/api/register', {
      firstName: form.firstName,
      lastName: form.lastName,
      username: form.username,
      email: form.email,
      phone: form.phone,
      password: form.password,
      gender: form.gender,
      shift: form.shift,
      skill: form.skill,
      batch: form.batch
    })

    alert('Registration successful. You can now log in.')
    router.push('/login')
  } catch (error) {
    alert(error.response?.data?.message || 'Registration failed. Please check your input.')
  }
}
</script>
