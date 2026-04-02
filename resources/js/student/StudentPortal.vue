<template>
  <div class="min-h-screen bg-[#f0f4ff]">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

      <!-- Top Header -->
      <div class="rounded-2xl bg-[#00288e] px-8 py-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between shadow-lg relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/5 blur-3xl"></div>

        <div class="flex items-center gap-5 relative z-10">
          <!-- Profile Pic Upload — input must NOT be display:none on iOS -->
          <div class="relative group h-16 w-16 shrink-0 cursor-pointer" :title="t.changePhoto">
            <img
              v-if="student.profileImage"
              :src="student.profileImage"
              class="h-full w-full rounded-2xl object-cover border-2 border-white/20 shadow-md"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center rounded-2xl bg-white/10 border-2 border-dashed border-white/20"
            >
              <span class="material-symbols-outlined text-white text-3xl opacity-50">add_a_photo</span>
            </div>
            <!-- Camera icon overlay -->
            <div class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
              <span class="material-symbols-outlined text-white text-sm">photo_camera</span>
            </div>
            <!-- Invisible input covers the full area — works on iOS/Android -->
            <input
              type="file"
              accept="image/*"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              style="font-size: 0;"
              @change="onFileChange"
            />
          </div>

          <div>
            <p class="text-xs font-bold uppercase tracking-widest text-blue-300 opacity-80">{{ t.welcomeBack }}</p>
            <h1 class="font-manrope text-2xl font-bold text-white mt-1">{{ studentDisplayName }}</h1>
            <div class="flex items-center gap-2 mt-1">
              <span class="inline-block h-2 w-2 rounded-full bg-green-400"></span>
              <p class="text-sm text-blue-200">{{ student.skill }} · {{ student.batch }}</p>
            </div>
          </div>
        </div>

        <div class="flex flex-row flex-wrap gap-3 relative z-10 shrink-0">
          <!-- Language Toggle -->
          <button
            @click="toggleLang"
            class="flex items-center gap-1.5 rounded-xl border-2 border-white/30 bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/20"
            :title="lang === 'en' ? 'ប្តូរទៅភាសាខ្មែរ' : 'Switch to English'"
          >
            <span class="material-symbols-outlined text-base">language</span>
            {{ lang === 'en' ? 'ខ្មែរ' : 'EN' }}
          </button>

          <button
            @click="toggleEdit"
            class="flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20"
          >
            <span class="material-symbols-outlined text-lg">{{ editing ? 'close' : 'settings' }}</span>
            {{ editing ? t.closeSettings : t.profileSettings }}
          </button>
          <button
            @click="handleLogout"
            class="flex items-center gap-2 rounded-xl bg-red-500/80 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 shadow-lg shadow-red-500/20"
          >
            <span class="material-symbols-outlined text-lg">logout</span>
            {{ t.signOut }}
          </button>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[1fr_360px]">

        <!-- ── Left ─────────────────────────────── -->
        <div class="space-y-6">

          <div v-if="editing" class="space-y-6">
            <!-- Edit Profile -->
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
              <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">manage_accounts</span>
                <h2 class="font-manrope font-bold text-slate-900">{{ t.editProfile }}</h2>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1"><span class="label-text">{{ t.firstName }}</span>
                  <input v-model="profileForm.firstName" type="text" class="field" /></label>
                <label class="block space-y-1"><span class="label-text">{{ t.lastName }}</span>
                  <input v-model="profileForm.lastName" type="text" class="field" /></label>
                <label class="block space-y-1 sm:col-span-2"><span class="label-text">{{ t.email }}</span>
                  <input v-model="profileForm.email" type="email" class="field" /></label>
                <label class="block space-y-1"><span class="label-text">{{ t.phone }}</span>
                  <input v-model="profileForm.phone" type="tel" class="field" /></label>
                <label class="block space-y-1"><span class="label-text">{{ t.shift }}</span>
                  <select v-model="profileForm.shift" class="field">
                    <option value="Morning">{{ t.morning }}</option>
                    <option value="Afternoon">{{ t.afternoon }}</option>
                    <option value="Evening">{{ t.evening }}</option>
                  </select>
                </label>
              </div>
              <button @click="saveProfile" class="mt-4 flex items-center gap-2 rounded-xl bg-[#00288e] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1e40af] shadow-lg shadow-[#00288e]/20">
                <span class="material-symbols-outlined text-lg">save</span>
                {{ t.saveChanges }}
              </button>
            </div>

            <!-- Change Password -->
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
              <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">lock_reset</span>
                <h2 class="font-manrope font-bold text-slate-900">{{ t.accountSecurity }}</h2>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <label class="block space-y-1 sm:col-span-2">
                  <span class="label-text">{{ t.currentPassword }}</span>
                  <input v-model="passForm.currentPassword" type="password" class="field" placeholder="••••••••" />
                </label>
                <label class="block space-y-1">
                  <span class="label-text">{{ t.newPassword }}</span>
                  <input v-model="passForm.newPassword" type="password" class="field" placeholder="••••••••" />
                </label>
                <label class="block space-y-1">
                  <span class="label-text">{{ t.confirmNewPassword }}</span>
                  <input v-model="passForm.newPassword_confirmation" type="password" class="field" placeholder="••••••••" />
                </label>
              </div>
              <button @click="changePassword" class="mt-4 flex items-center gap-2 rounded-xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-black shadow-lg shadow-black/10">
                <span class="material-symbols-outlined text-lg">vpn_key</span>
                {{ t.updatePassword }}
              </button>
            </div>
          </div>

          <!-- Available Exams -->
          <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">quiz</span>
                <h2 class="font-manrope font-bold text-slate-900">{{ t.availableExams }}</h2>
              </div>
              <span class="rounded-full bg-[#00288e]/10 px-3 py-1 text-xs font-semibold text-[#00288e]">{{ availableTests.length }} {{ t.exams }}</span>
            </div>

            <div class="space-y-4">
              <div
                v-for="test in availableTests"
                :key="test.id"
                class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 hover:border-[#00288e]/30 hover:bg-blue-50/30 transition-all"
              >
                <div>
                  <p class="font-semibold text-slate-900">{{ test.name }}</p>
                  <div class="mt-1.5 flex flex-wrap gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-[#00288e]">category</span>{{ test.skill }}</span>
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">timer</span>{{ test.durationMinutes }} {{ t.min }}</span>
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">star</span>{{ test.totalMarks }} {{ t.marks }}</span>
                  </div>
                </div>
                <div v-if="test.status === 'Finished'" class="text-right flex flex-col items-end gap-1 px-4 py-2">
                  <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">{{ t.finished }}</span>
                  <span class="text-[10px] text-slate-400 mt-0.5">{{ t.examEnded }}</span>
                </div>
                <template v-else>
                  <div v-if="test.scheduledAt && new Date(test.scheduledAt) > new Date()" class="text-right flex flex-col items-end gap-1 px-4 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#00288e]/60">{{ t.scheduledFor }}</span>
                    <div class="flex items-center gap-2 rounded-xl bg-[#00288e]/5 px-4 py-2 border border-[#00288e]/10">
                      <span class="material-symbols-outlined text-lg text-[#00288e]">calendar_month</span>
                      <span class="text-sm font-bold text-[#00288e]">
                        {{ new Date(test.scheduledAt).toLocaleDateString(lang === 'en' ? 'en-US' : 'km-KH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                      </span>
                    </div>
                  </div>
                  <button
                    v-else
                    @click="startExam(test)"
                    class="shrink-0 flex items-center gap-2 rounded-xl bg-[#00288e] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1e40af]"
                  >
                    <span class="material-symbols-outlined text-lg">play_arrow</span>
                    {{ t.start }}
                  </button>
                </template>
              </div>

              <div v-if="availableTests.length === 0" class="rounded-xl border-2 border-dashed border-slate-200 py-10 text-center text-sm text-slate-400">
                <span class="material-symbols-outlined block text-4xl mb-2">event_busy</span>
                {{ t.noExams }}
              </div>
            </div>
          </div>

          <!-- My Results -->
          <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-5">
              <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">bar_chart</span>
              <h2 class="font-manrope font-bold text-slate-900">{{ t.myResults }}</h2>
            </div>
            <div class="space-y-3">
              <div
                v-for="result in myResults"
                :key="result.id"
                class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3"
              >
                <div>
                  <p class="text-sm font-semibold text-slate-800">{{ result.testName }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">{{ formatDate(result.completedAt) }}</p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-right">
                    <p class="font-manrope text-lg font-bold text-[#00288e]">{{ result.score }}<span class="text-sm text-slate-400">/{{ result.totalMarks }}</span></p>
                  </div>
                  <button
                    @click="viewResult(result)"
                    class="flex items-center gap-1 rounded-lg bg-[#00288e]/10 px-3 py-1.5 text-xs font-semibold text-[#00288e] hover:bg-[#00288e] hover:text-white transition"
                  >
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                    {{ t.view }}
                  </button>
                </div>
              </div>
              <div v-if="myResults.length === 0" class="rounded-xl border-2 border-dashed border-slate-200 py-6 text-center text-sm text-slate-400">
                {{ t.noResults }}
              </div>
            </div>
          </div>
        </div>

        <!-- ── Right: Profile Card ────────────────────────────────── -->
        <aside class="space-y-4">
          <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
              <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">account_circle</span>
              <span class="font-manrope font-bold text-slate-900">{{ t.profile }}</span>
              <span class="ml-auto rounded-full bg-[#00288e]/10 px-2.5 py-0.5 text-xs font-semibold text-[#00288e]">{{ t.studentBadge }}</span>
            </div>
            <div class="space-y-3">
              <div v-for="field in profileFields" :key="field.label" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                <span class="text-xs font-semibold text-slate-400 uppercase">{{ field.label }}</span>
                <span class="text-sm font-semibold text-slate-800 text-right max-w-[55%] truncate">{{ field.value }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
              <span class="material-symbols-outlined text-[#00288e]" style="font-variation-settings:'FILL' 1;">school</span>
              <span class="font-manrope font-bold text-slate-900">{{ t.enrollment }}</span>
            </div>
            <div class="space-y-3">
              <div class="rounded-xl bg-[#00288e]/5 px-4 py-3">
                <p class="text-xs text-slate-400 uppercase font-semibold">{{ t.skill }}</p>
                <p class="mt-1 font-semibold text-[#00288e]">{{ student.skill }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-400 uppercase font-semibold">{{ t.batch }}</p>
                <p class="mt-1 font-semibold text-slate-800">{{ student.batch }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-400 uppercase font-semibold">{{ t.studyShift }}</p>
                <p class="mt-1 font-semibold text-slate-800">{{ student.shift }}</p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// ── Language ──────────────────────────────────────────────────────────
const lang = ref(localStorage.getItem('student_lang') || 'en')
const toggleLang = () => {
  lang.value = lang.value === 'en' ? 'kh' : 'en'
  localStorage.setItem('student_lang', lang.value)
}

const translations = {
  en: {
    changePhoto:       'Click to change photo',
    welcomeBack:       'Welcome Back,',
    profileSettings:   'Profile Settings',
    closeSettings:     'Close Settings',
    signOut:           'Sign Out',
    editProfile:       'Edit Profile',
    firstName:         'First Name',
    lastName:          'Last Name',
    email:             'Email',
    phone:             'Phone',
    shift:             'Shift',
    morning:           'Morning',
    afternoon:         'Afternoon',
    evening:           'Evening',
    saveChanges:       'Save Changes',
    accountSecurity:   'Account Security',
    currentPassword:   'Current Password',
    newPassword:       'New Password',
    confirmNewPassword:'Confirm New Password',
    updatePassword:    'Update Password',
    availableExams:    'Available Exams',
    exams:             'exams',
    min:               'min',
    marks:             'marks',
    finished:          'Finished',
    examEnded:         'Exam duration ended',
    scheduledFor:      'Scheduled for',
    start:             'Start',
    noExams:           'No exams available for your skill and batch yet.',
    myResults:         'My Results',
    view:              'View',
    noResults:         'No completed exams yet.',
    profile:           'Profile',
    studentBadge:      'Student',
    studentId:         'Student ID',
    status:            'Status',
    active:            'Active',
    enrollment:        'Enrollment',
    skill:             'Skill',
    batch:             'Batch',
    studyShift:        'Study Shift',
  },
  kh: {
    changePhoto:       'ចុចដើម្បីប្តូររូបថត',
    welcomeBack:       'សូមស្វាគមន៍,',
    profileSettings:   'កំណត់ព័ត៌មាន',
    closeSettings:     'បិទការកំណត់',
    signOut:           'ចេញពីប្រព័ន្ធ',
    editProfile:       'កែតម្រូវព័ត៌មាន',
    firstName:         'នាមខ្លួន',
    lastName:          'នាមត្រកូល',
    email:             'អ៊ីមែល',
    phone:             'លេខទូរស័ព្ទ',
    shift:             'វេននៅពេល',
    morning:           'ព្រឹក',
    afternoon:         'ថ្ងៃ',
    evening:           'ល្ងាច',
    saveChanges:       'រក្សាទុកការផ្លាស់ប្តូរ',
    accountSecurity:   'សុវត្ថិភាពគណនី',
    currentPassword:   'លេខសម្ងាត់បច្ចុប្បន្ន',
    newPassword:       'លេខសម្ងាត់ថ្មី',
    confirmNewPassword:'បញ្ជាក់លេខសម្ងាត់ថ្មី',
    updatePassword:    'ផ្លាស់ប្តូរលេខសម្ងាត់',
    availableExams:    'ការប្រឡងទំនេរ',
    exams:             'ការប្រឡង',
    min:               'នាទី',
    marks:             'ពិន្ទុ',
    finished:          'បានបញ្ចប់',
    examEnded:         'ការប្រឡងបានបញ្ចប់',
    scheduledFor:      'កំណត់ពេលវេលា',
    start:             'ចាប់ផ្ដើម',
    noExams:           'មិនទាន់មានការប្រឡងសម្រាប់ជំនាញ និងថ្នាក់របស់អ្នក។',
    myResults:         'លទ្ធផលរបស់ខ្ញុំ',
    view:              'មើល',
    noResults:         'មិនទាន់មានការប្រឡងដែលបានបញ្ចប់។',
    profile:           'ព័ត៌មានផ្ទាល់ខ្លួន',
    studentBadge:      'សិស្ស',
    studentId:         'លេខសម្គាល់សិស្ស',
    status:            'ស្ថានភាព',
    active:            'សកម្ម',
    enrollment:        'ការចុះឈ្មោះ',
    skill:             'ជំនាញ',
    batch:             'ថ្នាក់',
    studyShift:        'វេនសិក្សា',
  },
}

const t = computed(() => translations[lang.value])

// ── Data ──────────────────────────────────────────────────────────────
const editing = ref(false)
const student = ref({ id: '', name: '', email: '', phone: '', skill: '', batch: '', shift: '', profileImage: null })
const availableTests = ref([])
const myResults = ref([])

const profileForm = reactive({ firstName: '', lastName: '', email: '', phone: '', shift: '' })
const passForm = reactive({ currentPassword: '', newPassword: '', newPassword_confirmation: '' })

const studentDisplayName = computed(() => student.value.name || 'Student')

const profileFields = computed(() => [
  { label: t.value.studentId, value: student.value.id || '—' },
  { label: t.value.email,     value: student.value.email || '—' },
  { label: t.value.phone,     value: student.value.phone || '—' },
  { label: t.value.status,    value: t.value.active },
])

const toggleEdit = () => {
  editing.value = !editing.value
  if (editing.value) {
    const [first, ...rest] = (student.value.name || '').split(' ')
    profileForm.firstName = first || ''
    profileForm.lastName  = rest.join(' ') || ''
    profileForm.email     = student.value.email
    profileForm.phone     = student.value.phone
    profileForm.shift     = student.value.shift
  }
}

const saveProfile = async () => {
  try {
    const res = await axios.post('/api/profile/update', profileForm)
    if (res.data.student) {
      student.value = { ...student.value, ...res.data.student }
      editing.value = false
    }
  } catch (e) { alert(e.response?.data?.message || 'Update failed.') }
}

const onFileChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  const formData = new FormData()
  formData.append('image', file)
  try {
    const res = await axios.post('/api/profile/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    student.value.profileImage = res.data.profileImage
  } catch (err) {
    alert(err.response?.data?.message || 'Upload failed')
  }
}

const changePassword = async () => {
  try {
    await axios.post('/api/profile/change-password', passForm)
    alert(lang.value === 'en' ? 'Password updated successfully!' : 'លេខសម្ងាត់ត្រូវបានផ្លាស់ប្តូរ!')
    passForm.currentPassword = ''
    passForm.newPassword = ''
    passForm.newPassword_confirmation = ''
    editing.value = false
  } catch (e) {
    alert(e.response?.data?.message || 'Failed to update password.')
  }
}

const startExam  = (test) => router.push({ name: 'Exam',        params: { testId: test.id } })
const viewResult = (r)    => router.push({ name: 'ExamResults', params: { submissionId: r.id } })

const handleLogout = async () => {
  try { await axios.post('/api/logout') } catch {}
  router.push('/login')
}

const formatDate = (d) => d
  ? new Date(d).toLocaleDateString(lang.value === 'en' ? 'en-US' : 'km-KH', { month: 'short', day: 'numeric', year: 'numeric' })
  : '—'

onMounted(async () => {
  try {
    const [profileRes, resultsRes] = await Promise.all([
      axios.get('/api/profile'),
      axios.get('/api/student/results').catch(() => ({ data: { results: [] } })),
    ])
    if (profileRes.data.student) {
      student.value = {
        ...profileRes.data.student,
        profileImage: profileRes.data.user.profileImage
      }
      availableTests.value = profileRes.data.tests || []
    } else {
      router.push('/login')
    }
    myResults.value = resultsRes.data.results || []
  } catch {
    router.push('/login')
  }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
.field {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background-color: #f8fafc;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  outline: none;
  transition: border-color 150ms, background-color 150ms;
}
.field:focus {
  border-color: #00288e;
  background-color: #fff;
}
.label-text {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin-bottom: 0.25rem;
}
</style>
