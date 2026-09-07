<template>
  <PublicLayout>
    <!-- Self-Registration Disabled State -->
    <Card v-if="settings.allowRegistration === false" padding="lg" class="shadow-soft-lg max-w-xl mx-auto text-center py-8 space-y-4">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 border border-amber-200">
        <span class="material-symbols-outlined text-4xl">person_off</span>
      </div>
      <div>
        <h2 class="text-xl font-extrabold text-slate-900">
          {{ lang === 'kh' ? 'ការចុះឈ្មោះត្រូវបានបិទ' : 'Self-Registration Closed' }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-sm mx-auto leading-relaxed">
          {{ lang === 'kh' ? 'ការចុះឈ្មោះបង្កើតគណនីដោយខ្លួនឯងត្រូវបានបិទជាបណ្ដោះអាសន្នដោយ Administrator។' : 'Candidate self-registration is currently disabled by administrator.' }}
        </p>
      </div>
      <div class="pt-2">
        <Button variant="primary" icon="login" size="md" @click="router.push('/login')">
          {{ lang === 'kh' ? 'ត្រឡប់ទៅទំព័រចូល' : 'Return to Sign In' }}
        </Button>
      </div>
    </Card>

    <Card v-else padding="lg" class="shadow-soft-lg max-w-xl mx-auto border border-slate-200/80">
      <!-- Wizard Step Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
            {{ t.step }} {{ currentStep }} {{ t.of }} 3: {{ currentStepTitle }}
          </span>
          <span class="text-xs font-semibold text-slate-400">
            {{ Math.round((currentStep / 3) * 100) }}%
          </span>
        </div>

        <!-- Step Indicator Track -->
        <div class="grid grid-cols-3 gap-1.5 h-1.5 rounded-full overflow-hidden bg-slate-100">
          <div
            v-for="step in 3"
            :key="step"
            :class="[
              'h-full rounded-full transition-all duration-300',
              step <= currentStep ? 'bg-blue-600' : 'bg-slate-200'
            ]"
          ></div>
        </div>
      </div>

      <!-- Error Alert -->
      <div
        v-if="errorMessage"
        class="mb-5 flex items-center gap-2.5 p-3.5 rounded-2xl bg-red-50 text-red-700 border border-red-200/80 text-xs font-semibold animate-fade-in"
      >
        <span class="material-symbols-outlined text-lg shrink-0">error</span>
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Step 1: Personal Info -->
      <div v-if="currentStep === 1" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input
            v-model="form.firstName"
            :label="t.firstName"
            required
            :placeholder="lang === 'kh' ? 'ឧទាហរណ៍៖ សុខា' : 'e.g. Sokha'"
            :error="errors.firstName?.[0]"
          />
          <Input
            v-model="form.lastName"
            :label="t.lastName"
            required
            :placeholder="lang === 'kh' ? 'ឧទាហរណ៍៖ ចាន់' : 'e.g. Chan'"
            :error="errors.lastName?.[0]"
          />
        </div>

        <Input
          v-model="form.phone"
          :label="t.phone"
          icon="call"
          required
          placeholder="012 345 678"
          :error="errors.phone?.[0]"
        />

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
            {{ t.gender }} <span class="text-red-500">*</span>
          </label>
          <CustomDropdown
            v-model="form.gender"
            :options="genderOptions"
            labelKey="label"
            valueKey="value"
            :placeholder="t.gender"
            :hasError="!!errors.gender"
          />
          <p v-if="errors.gender" class="text-xs text-red-500 font-semibold mt-1">{{ errors.gender[0] }}</p>
        </div>
      </div>

      <!-- Step 2: Academic & Enrollment Information -->
      <div v-if="currentStep === 2" class="space-y-4">
        <!-- Auto-Generated Student ID Badge Preview -->
        <div class="p-3.5 rounded-2xl bg-gradient-to-r from-blue-50/90 to-indigo-50/90 border border-blue-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
              <span class="material-symbols-outlined text-xl">badge</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600 block">{{ lang === 'kh' ? 'Student ID ស្វ័យប្រវត្តិ' : 'Auto-Generated Student ID' }}</span>
              <span class="text-sm font-black text-slate-900 font-mono tracking-wider">{{ form.studentCode }}</span>
            </div>
          </div>
          <button
            type="button"
            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-white rounded-xl transition-all"
            :title="lang === 'kh' ? 'បង្កើតកូដចៃដន្យថ្មី' : 'Randomize ID'"
            @click="randomizeStudentCode"
          >
            <span class="material-symbols-outlined text-lg">refresh</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.skill }} <span class="text-red-500">*</span>
            </label>
            <CustomDropdown
              v-model="form.skill"
              :options="skillOptions"
              labelKey="SkillName"
              valueKey="SkillName"
              :placeholder="t.skill"
              :hasError="!!errors.skill"
            />
            <p v-if="errors.skill" class="text-xs text-red-500 font-semibold mt-1">{{ errors.skill[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.group }} <span class="text-red-500">*</span>
            </label>
            <CustomDropdown
              v-model="form.group"
              :options="groupOptions"
              labelKey="GroupName"
              valueKey="GroupName"
              :placeholder="t.group"
              :hasError="!!errors.group"
            />
            <p v-if="errors.group" class="text-xs text-red-500 font-semibold mt-1">{{ errors.group[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.shift }} <span class="text-red-500">*</span>
            </label>
            <CustomDropdown
              v-model="form.shift"
              :options="shiftOptions"
              labelKey="label"
              valueKey="value"
              :placeholder="t.shift"
              :hasError="!!errors.shift"
            />
            <p v-if="errors.shift" class="text-xs text-red-500 font-semibold mt-1">{{ errors.shift[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.duration }} <span class="text-red-500">*</span>
            </label>
            <CustomDropdown
              v-model="form.durationMonths"
              :options="durationOptions"
              labelKey="DurationName"
              valueKey="DurationName"
              :placeholder="t.duration"
              :hasError="!!errors.durationMonths"
            />
            <p v-if="errors.durationMonths" class="text-xs text-red-500 font-semibold mt-1">{{ errors.durationMonths[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.intakeMonth }} <span class="text-red-500">*</span>
            </label>
            <CustomDropdown
              v-model="form.intakeMonth"
              :options="monthOptions"
              :placeholder="t.intakeMonth"
              :hasError="!!errors.intakeMonth"
            />
            <p v-if="errors.intakeMonth" class="text-xs text-red-500 font-semibold mt-1">{{ errors.intakeMonth[0] }}</p>
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.intakeYear }} <span class="text-red-500">*</span>
            </label>
            <CustomDropdown
              v-model="form.intakeYear"
              :options="yearOptions"
              :placeholder="t.intakeYear"
              :hasError="!!errors.intakeYear"
              @change="onYearChange"
            />
            <p v-if="errors.intakeYear" class="text-xs text-red-500 font-semibold mt-1">{{ errors.intakeYear[0] }}</p>
          </div>
        </div>
      </div>

      <!-- Step 3: Summary & Review -->
      <div v-if="currentStep === 3" class="space-y-4">
        <!-- Assigned Student ID Highlight -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-md flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl text-white">badge</span>
          </div>
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-100 block">
              {{ lang === 'kh' ? 'Student ID សម្រាប់ចូលប្រឡង' : 'Your Student ID' }}
            </span>
            <span class="text-xl font-black font-mono tracking-wider">{{ form.studentCode }}</span>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/60 pb-2">
            {{ t.personalInfo }}
          </h4>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div><span class="text-slate-400">{{ t.fullName }}:</span> <strong class="text-slate-800">{{ form.lastName }} {{ form.firstName }}</strong></div>
            <div><span class="text-slate-400">{{ t.phone }}:</span> <strong class="text-slate-800">{{ form.phone }}</strong></div>
            <div><span class="text-slate-400">{{ t.gender }}:</span> <strong class="text-slate-800">{{ form.gender }}</strong></div>
            <div><span class="text-slate-400">{{ t.shift }}:</span> <strong class="text-slate-800">{{ form.shift }}</strong></div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/60 pb-2">
            {{ t.enrollmentDetails }}
          </h4>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div><span class="text-slate-400">{{ t.skill }}:</span> <strong class="text-blue-700">{{ form.skill }}</strong></div>
            <div><span class="text-slate-400">{{ t.group }}:</span> <strong class="text-purple-700">{{ form.group }}</strong></div>
            <div><span class="text-slate-400">{{ t.duration }}:</span> <strong class="text-slate-800">{{ form.durationMonths }}</strong></div>
            <div><span class="text-slate-400">{{ t.intake }}:</span> <strong class="text-slate-800">{{ form.intakeMonth }} {{ form.intakeYear }}</strong></div>
          </div>
        </div>

        <div class="p-3 bg-amber-50/80 border border-amber-200/80 rounded-xl text-[11px] text-amber-800 flex items-start gap-2">
          <span class="material-symbols-outlined text-amber-600 text-base shrink-0 mt-0.5">info</span>
          <span>{{ lang === 'kh' ? 'ចំណាំ៖ ក្រោយពីចុះឈ្មោះជោគជ័យ អ្នកគ្រាន់តែប្រើ Student ID ខាងលើដើម្បីចូលប្រឡង (មិនចាំបាច់មានពាក្យសម្ងាត់ទេ)។' : 'Note: After registration, you will only need your Student ID to sign in (no password needed).' }}</span>
        </div>
      </div>

      <!-- Stepper Controls -->
      <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
        <Button
          v-if="currentStep > 1"
          variant="outline"
          icon="arrow_back"
          @click="prevStep"
        >
          {{ t.back }}
        </Button>
        <div v-else></div>

        <Button
          v-if="currentStep < 3"
          variant="primary"
          trailing-icon="arrow_forward"
          @click="nextStep"
        >
          {{ t.continue }}
        </Button>

        <Button
          v-else
          variant="primary"
          icon="how_to_reg"
          :loading="isSubmitting"
          :disabled="isSubmitting"
          @click="handleRegister"
        >
          {{ isSubmitting ? t.submittingBtn : t.createAccountBtn }}
        </Button>
      </div>

      <!-- Login Link -->
      <div class="mt-4 text-center">
        <p class="text-xs text-slate-500 font-medium">
          {{ t.alreadyHaveAccount }}
          <RouterLink
            to="/login"
            class="font-bold text-blue-600 hover:text-blue-700 hover:underline ml-1"
          >
            {{ t.signInLink }}
          </RouterLink>
        </p>
      </div>
    </Card>

    <!-- Success Modal -->
    <Modal v-model="showSuccess" max-width="md" :show-close="false" :close-on-backdrop="false">
      <div class="text-center py-4 space-y-4">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100">
          <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
        </div>
        <div>
          <h3 class="text-xl font-extrabold text-slate-900">{{ t.successTitle }}</h3>
          <p class="text-sm text-slate-500 mt-1 leading-relaxed">
            {{ t.successDesc }}
          </p>
          <div v-if="createdStudentCode" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200/80 shadow-sm text-center">
            <span class="text-xs text-blue-600 font-bold uppercase tracking-wider block mb-1">{{ lang === 'kh' ? 'Student ID របស់អ្នក' : 'Your Student ID' }}</span>
            <span class="text-2xl font-black text-blue-900 font-mono tracking-widest">{{ createdStudentCode }}</span>
            <span class="text-[11px] text-slate-400 block mt-1.5">{{ lang === 'kh' ? 'សូមរក្សាទុក Student ID នេះ ដើម្បីចូលប្រឡង' : 'Please save this ID to sign in to exams' }}</span>
          </div>
        </div>
        <div class="pt-2">
          <Button variant="primary" full-width size="lg" icon="login" @click="router.push('/login')">
            {{ t.goToLogin }}
          </Button>
        </div>
      </div>
    </Modal>
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'
import PublicLayout from '../layouts/PublicLayout.vue'
import Card from '../components/ui/Card.vue'
import Input from '../components/ui/Input.vue'
import Button from '../components/ui/Button.vue'
import Modal from '../components/ui/Modal.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { useSettings } from '../composables/useSettings'

const router = useRouter()
const { lang } = useLang()
const { settings, fetchSettings } = useSettings()

const currentStep = ref(1)
const isSubmitting = ref(false)
const errorMessage = ref('')
const showSuccess = ref(false)
const createdStudentCode = ref('')
const errors = reactive({})

const form = reactive({
  studentCode: '',
  firstName: '',
  lastName: '',
  phone: '',
  gender: 'Male',
  skill: '',
  group: '',
  shift: 'Morning',
  intakeMonth: 'មករា',
  intakeYear: '2026',
  durationMonths: ''
})

const skillOptions = ref([])
const groupOptions = ref([])
const durationOptions = ref([])

const generateRandomCode = (year = '2026') => {
  const rand = Math.floor(10000 + Math.random() * 90000)
  return `RTC-${year}-${rand}`
}

const randomizeStudentCode = () => {
  form.studentCode = generateRandomCode(form.intakeYear || '2026')
}

const onYearChange = () => {
  form.studentCode = generateRandomCode(form.intakeYear || '2026')
}

const genderOptions = computed(() => [
  { label: lang.value === 'kh' ? 'ប្រុស' : 'Male', value: 'Male' },
  { label: lang.value === 'kh' ? 'ស្រី' : 'Female', value: 'Female' },
  { label: lang.value === 'kh' ? 'ផ្សេងៗ' : 'Other', value: 'Other' }
])

const shiftOptions = computed(() => [
  { label: lang.value === 'kh' ? 'វេនព្រឹក (Morning)' : 'Morning', value: 'Morning' },
  { label: lang.value === 'kh' ? 'វេនរសៀល (Afternoon)' : 'Afternoon', value: 'Afternoon' },
  { label: lang.value === 'kh' ? 'វេនយប់ (Evening)' : 'Evening', value: 'Evening' }
])

const monthOptions = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ']
const yearOptions = ['2024', '2025', '2026', '2027', '2028', '2029', '2030']

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      step: 'ជំហានទី',
      of: 'នៃ',
      personalInfo: 'ព័ត៌មានផ្ទាល់ខ្លួន',
      enrollmentDetails: 'ព័ត៌មានសិក្សា និង ជំនាញ',
      review: 'ផ្ទៀងផ្ទាត់ និង បញ្ជាក់',
      firstName: 'នាមខ្លួន',
      lastName: 'គោត្តនាម',
      fullName: 'ឈ្មោះពេញ',
      phone: 'លេខទូរស័ព្ទ',
      gender: 'ភេទ',
      studentId: 'Student ID',
      skill: 'ជំនាញ',
      group: 'ក្រុមសិក្សា',
      shift: 'វេនសិក្សា',
      duration: 'រយៈពេលសិក្សា',
      intakeMonth: 'ខែចូលរៀន',
      intakeYear: 'ឆ្នាំចូលរៀន',
      intake: 'ចូលរៀន',
      back: 'ថយក្រោយ',
      continue: 'បន្តទៅមុខ',
      createAccountBtn: 'ចុះឈ្មោះប្រឡងឥឡូវនេះ',
      submittingBtn: 'កំពុងចុះឈ្មោះ...',
      alreadyHaveAccount: 'មានគណនីរួចហើយ?',
      signInLink: 'ចូលប្រឡងទីនេះ',
      successTitle: 'ចុះឈ្មោះជោគជ័យ!',
      successDesc: 'ការចុះឈ្មោះប្រឡងរបស់អ្នកត្រូវបានបញ្ចប់។ សូមរក្សាទុក Student ID ខាងក្រោមដើម្បីចូលប្រឡង។',
      goToLogin: 'ទៅកាន់ទំព័រចូលប្រឡង'
    }
  }
  return {
    step: 'Step',
    of: 'of',
    personalInfo: 'Personal Information',
    enrollmentDetails: 'Study & Enrollment',
    review: 'Review & Confirm',
    firstName: 'First Name',
    lastName: 'Last Name',
    fullName: 'Full Name',
    phone: 'Phone Number',
    gender: 'Gender',
    studentId: 'Student ID',
    skill: 'Skill Area',
    group: 'Study Group',
    shift: 'Study Shift',
    duration: 'Study Duration',
    intakeMonth: 'Intake Month',
    intakeYear: 'Intake Year',
    intake: 'Intake',
    back: 'Back',
    continue: 'Continue',
    createAccountBtn: 'Register for Exam Now',
    submittingBtn: 'Registering...',
    alreadyHaveAccount: 'Already have an account?',
    signInLink: 'Sign in here',
    successTitle: 'Registration Successful!',
    successDesc: 'Your candidate profile has been registered. Please keep your Candidate ID to log in.',
    goToLogin: 'Go to Exam Sign In'
  }
})

const currentStepTitle = computed(() => {
  switch (currentStep.value) {
    case 1: return t.value.personalInfo
    case 2: return t.value.enrollmentDetails
    case 3: return t.value.review
    default: return ''
  }
})

const validateStep = (step) => {
  errorMessage.value = ''
  if (step === 1) {
    if (!form.firstName.trim()) {
      errors.firstName = [lang.value === 'kh' ? 'សូមបំពេញនាមខ្លួន (First Name)' : 'Please enter your first name.']
      errorMessage.value = errors.firstName[0]
      return false
    }
    if (!form.lastName.trim()) {
      errors.lastName = [lang.value === 'kh' ? 'សូមបំពេញគោត្តនាម (Last Name)' : 'Please enter your last name.']
      errorMessage.value = errors.lastName[0]
      return false
    }
    if (!form.phone.trim()) {
      errors.phone = [lang.value === 'kh' ? 'សូមបំពេញលេខទូរស័ព្ទ' : 'Please enter your phone number.']
      errorMessage.value = errors.phone[0]
      return false
    }
  }
  if (step === 2) {
    if (!form.skill) {
      errors.skill = [lang.value === 'kh' ? 'សូមជ្រើសរើសជំនាញ' : 'Please select a skill.']
      errorMessage.value = errors.skill[0]
      return false
    }
    if (!form.group) {
      errors.group = [lang.value === 'kh' ? 'សូមជ្រើសរើសក្រុមសិក្សា' : 'Please select a group.']
      errorMessage.value = errors.group[0]
      return false
    }
    if (!form.durationMonths) {
      errors.durationMonths = [lang.value === 'kh' ? 'សូមជ្រើសរើសរយៈពេលសិក្សា' : 'Please select study duration.']
      errorMessage.value = errors.durationMonths[0]
      return false
    }
  }
  return true
}

const nextStep = () => {
  if (validateStep(currentStep.value)) {
    currentStep.value++
    errorMessage.value = ''
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    errorMessage.value = ''
  }
}

const loadMetadata = async () => {
  try {
    const res = await axios.get('/api/skills-groups').catch(() => axios.get('/api/admin/skills-groups'))
    skillOptions.value = res.data.skills || []
    groupOptions.value = res.data.groups || []
    durationOptions.value = res.data.durations || []

    if (skillOptions.value.length && !form.skill) form.skill = skillOptions.value[0].SkillName
    if (groupOptions.value.length && !form.group) form.group = groupOptions.value[0].GroupName
    if (durationOptions.value.length && !form.durationMonths) form.durationMonths = durationOptions.value[0].DurationName
  } catch (e) {
    console.error('Failed to load skills/groups metadata', e)
  }

  if (!durationOptions.value.length) {
    durationOptions.value = [
      { DurationId: 1, DurationName: '1 ខែ (1 Month)', DurationMonths: 1 },
      { DurationId: 2, DurationName: '2 ខែ (2 Months)', DurationMonths: 2 },
      { DurationId: 3, DurationName: '3 ខែ (3 Months)', DurationMonths: 3 },
      { DurationId: 4, DurationName: '4 ខែ (4 Months)', DurationMonths: 4 },
      { DurationId: 5, DurationName: '5 ខែ (5 Months)', DurationMonths: 5 },
      { DurationId: 6, DurationName: '6 ខែ (6 Months)', DurationMonths: 6 },
      { DurationId: 7, DurationName: '1 ឆ្នាំ (1 Year)', DurationMonths: 12 }
    ]
    if (!form.durationMonths) form.durationMonths = durationOptions.value[0].DurationName
  }
}

const handleRegister = async () => {
  if (isSubmitting.value) return
  errorMessage.value = ''
  Object.keys(errors).forEach(key => delete errors[key])

  if (!validateStep(1)) {
    currentStep.value = 1
    return
  }
  if (!validateStep(2)) {
    currentStep.value = 2
    return
  }

  isSubmitting.value = true

  try {
    const res = await axios.post('/api/register', {
      studentCode: form.studentCode,
      firstName: form.firstName.trim(),
      lastName: form.lastName.trim(),
      phone: form.phone.trim(),
      gender: form.gender,
      skill: form.skill,
      group: form.group,
      shift: form.shift,
      intakeMonth: form.intakeMonth,
      intakeYear: form.intakeYear,
      durationMonths: form.durationMonths
    })

    createdStudentCode.value = res.data.studentCode || form.studentCode
    showSuccess.value = true
  } catch (err) {
    if (err.response?.data?.studentCode) {
      createdStudentCode.value = err.response.data.studentCode
      showSuccess.value = true
      return
    }

    if (err.response?.data?.errors) {
      Object.assign(errors, err.response.data.errors)
      const errorKeys = Object.keys(err.response.data.errors)
      if (errorKeys.length > 0) {
        const firstKey = errorKeys[0]
        if (['firstName', 'lastName', 'phone', 'gender'].includes(firstKey)) {
          currentStep.value = 1
        } else {
          currentStep.value = 2
        }

        const firstMsg = err.response.data.errors[firstKey]?.[0] || ''
        errorMessage.value = firstMsg || (lang.value === 'kh' ? 'សូមកែសម្រួលព័ត៌មានដែលមិនត្រឹមត្រូវខាងក្រោម' : 'Please fix the errors below.')
      }
    } else {
      const rawMsg = err.response?.data?.message || err.response?.data?.error || ''
      errorMessage.value = rawMsg || (lang.value === 'kh' ? 'ការចុះឈ្មោះមិនបានជោគជ័យ សូមព្យាយាមម្តងទៀត' : 'Registration failed. Please try again.')
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  randomizeStudentCode()
  fetchSettings()
  loadMetadata()
})
</script>
