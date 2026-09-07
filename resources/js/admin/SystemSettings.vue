<template>
  <div class="space-y-6 w-full">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
            <span class="material-symbols-outlined text-xs mr-1">tune</span>
            Super Admin Only
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.subtitle }}
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <Button
          variant="primary"
          icon="save"
          size="md"
          :loading="saving"
          @click="saveSettings"
        >
          {{ t.saveSettings }}
        </Button>
      </div>
    </div>

    <!-- Skeletons if loading -->
    <div v-if="loading" class="space-y-6">
      <Skeleton height="180px" customClass="rounded-2xl" />
      <Skeleton height="180px" customClass="rounded-2xl" />
      <Skeleton height="180px" customClass="rounded-2xl" />
    </div>

    <!-- Loaded Content -->
    <div v-else class="space-y-6">

      <!-- ── Section 1: General Platform Configuration ─────────────── -->
      <Card :title="t.generalTitle" :subtitle="t.generalSubtitle" padding="normal" class="shadow-soft-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Input
            v-model="form.institutionName"
            :label="t.institutionName"
            required
            placeholder="e.g. Online Examination Platform"
          />

          <Input
            v-model="form.academicYear"
            :label="t.academicYear"
            required
            placeholder="e.g. 2026-2027"
          />

          <Input
            v-model="form.timezone"
            :label="t.timezone"
            disabled
            placeholder="Asia/Phnom_Penh"
          />

          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
              {{ t.defaultLanguage }}
            </label>
            <CustomDropdown
              v-model="form.defaultLanguage"
              :options="[
                { label: 'ភាសាខ្មែរ (Khmer - Default)', value: 'kh' },
                { label: 'English', value: 'en' }
              ]"
              labelKey="label"
              valueKey="value"
            />
          </div>
        </div>
      </Card>

      <!-- ── Section 2: Authentication & Security ─────────────────── -->
      <Card :title="t.authTitle" :subtitle="t.authSubtitle" padding="normal" class="shadow-soft-sm">
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Input
              v-model.number="form.sessionTimeoutMinutes"
              type="number"
              min="5"
              max="240"
              :label="t.sessionTimeout"
              icon="timer"
            />
          </div>

          <div class="pt-3 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100/60 transition-colors select-none">
              <input
                type="checkbox"
                v-model="form.allowRegistration"
                class="mt-0.5 h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer shrink-0"
              />
              <div class="text-xs min-w-0">
                <span class="font-bold text-slate-900 block">{{ t.allowSelfReg }}</span>
                <span class="text-slate-500 mt-0.5 block leading-relaxed">{{ t.allowSelfRegDesc }}</span>
              </div>
            </label>

            <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100/60 transition-colors select-none">
              <input
                type="checkbox"
                v-model="form.forceStrongPassword"
                class="mt-0.5 h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer shrink-0"
              />
              <div class="text-xs min-w-0">
                <span class="font-bold text-slate-900 block">{{ t.forceStrongPass }}</span>
                <span class="text-slate-500 mt-0.5 block leading-relaxed">{{ t.forceStrongPassDesc }}</span>
              </div>
            </label>
          </div>
        </div>
      </Card>

      <!-- ── Section 3: Assessment & Anti-Cheat Rules ─────────────── -->
      <Card :title="t.examRulesTitle" :subtitle="t.examRulesSubtitle" padding="normal" class="shadow-soft-sm">
        <div class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Input
              v-model.number="form.autosaveIntervalSeconds"
              type="number"
              min="1"
              max="30"
              :label="t.autosaveInterval"
              icon="sync"
            />
          </div>

          <div class="pt-3 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100/60 transition-colors select-none">
              <input
                type="checkbox"
                v-model="form.antiCheatPause"
                class="mt-0.5 h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer shrink-0"
              />
              <div class="text-xs min-w-0">
                <span class="font-bold text-slate-900 block">{{ t.antiCheatBlur }}</span>
                <span class="text-slate-500 mt-0.5 block leading-relaxed">{{ t.antiCheatBlurDesc }}</span>
              </div>
            </label>

            <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 cursor-pointer hover:bg-slate-100/60 transition-colors select-none">
              <input
                type="checkbox"
                v-model="form.autoSubmitOnTimeout"
                class="mt-0.5 h-4 w-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer shrink-0"
              />
              <div class="text-xs min-w-0">
                <span class="font-bold text-slate-900 block">{{ t.autoSubmitTimeout }}</span>
                <span class="text-slate-500 mt-0.5 block leading-relaxed">{{ t.autoSubmitTimeoutDesc }}</span>
              </div>
            </label>
          </div>
        </div>
      </Card>

      <!-- ── Section 4: System Health & Diagnostics ───────────────── -->
      <Card :title="t.diagnosticsTitle" :subtitle="t.diagnosticsSubtitle" padding="normal" class="shadow-soft-sm">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
            <span class="text-slate-400 font-bold uppercase block text-[11px]">{{ t.phpVersion }}</span>
            <strong class="text-slate-800 text-sm font-mono mt-1 block">{{ form.phpVersion || '8.2+' }}</strong>
          </div>
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
            <span class="text-slate-400 font-bold uppercase block text-[11px]">{{ t.laravelVersion }}</span>
            <strong class="text-slate-800 text-sm font-mono mt-1 block">{{ form.laravelVersion || '12.x' }}</strong>
          </div>
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
            <span class="text-slate-400 font-bold uppercase block text-[11px]">{{ t.dbEngine }}</span>
            <strong class="text-slate-800 text-sm capitalize mt-1 block">{{ form.databaseDriver || 'MySQL' }}</strong>
          </div>
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
            <span class="text-slate-400 font-bold uppercase block text-[11px]">{{ t.systemStatus }}</span>
            <strong class="text-emerald-700 text-sm font-bold flex items-center gap-1.5 mt-1">
              <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
              Operational
            </strong>
          </div>
        </div>
      </Card>

      <!-- Bottom Save Action Bar -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Button
          variant="primary"
          icon="save"
          size="md"
          :loading="saving"
          @click="saveSettings"
        >
          {{ t.saveSettings }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Input from '../components/ui/Input.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { useSettings } from '../composables/useSettings'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { fetchSettings } = useSettings()

const loading = ref(true)
const saving = ref(false)

const form = reactive({
  institutionName: '',
  academicYear: '',
  timezone: '',
  defaultLanguage: 'kh',
  sessionTimeoutMinutes: 60,
  allowRegistration: true,
  forceStrongPassword: true,
  antiCheatPause: true,
  autosaveIntervalSeconds: 3,
  autoSubmitOnTimeout: true,
  phpVersion: '',
  laravelVersion: '',
  databaseDriver: ''
})

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'ការកំណត់ប្រព័ន្ធទាំងមូល (System Settings)',
      subtitle: 'គ្រប់គ្រងគោលការណ៍សុវត្ថិភាព វគ្គប្រឡង និងការកំណត់ទូទៅរបស់ប្រព័ន្ធ',
      saveSettings: 'រក្សាទុកការកំណត់',
      generalTitle: 'ព័ត៌មានទូទៅរបស់ស្ថាប័ន',
      generalSubtitle: 'ឈ្មោះប្រព័ន្ធ និងឆ្នាំសិក្សាគោល',
      institutionName: 'ឈ្មោះស្ថាប័ន / ប្រព័ន្ធ',
      academicYear: 'ឆ្នាំសិក្សា',
      timezone: 'ល្វែងម៉ោង (Timezone)',
      defaultLanguage: 'ភាសាលំនាំដើម',
      authTitle: 'សុវត្ថិភាព & ការចូលប្រើប្រាស់',
      authSubtitle: 'កំណត់ពេលវេលាកំណត់ Session និងការចុះឈ្មោះ',
      sessionTimeout: 'កំណត់ពេលវេលាអសកម្ម Session (នាទី)',
      allowSelfReg: 'អនុញ្ញាតឲ្យសិស្សចុះឈ្មោះបង្កើតគណនីដោយខ្លួនឯង',
      allowSelfRegDesc: 'បើកទំព័រ Register សម្រាប់សិស្សថ្មីចុះឈ្មោះ',
      forceStrongPass: 'តម្រូវឲ្យពាក្យសម្ងាត់មានសុវត្ថិភាពខ្ពស់',
      forceStrongPassDesc: 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច ៦ ខ្ទង់',
      examRulesTitle: 'គោលការណ៍ប្រឡង & Anti-Cheat',
      examRulesSubtitle: 'ការរក្សាទុកស្វ័យប្រវត្តិ និងការតាមដានការផ្តោតអារម្មណ៍',
      autosaveInterval: 'គម្លាតពេលរក្សាទុកចម្លើយ (វិនាទី)',
      antiCheatBlur: 'ផ្អាកការប្រឡងនៅពេលសិស្សប្តូរផ្ទាំងកម្មវិធី (Blur Pause)',
      antiCheatBlurDesc: 'បង្ហាញផ្ទាំង Exam Paused នៅពេលបង្អួចបាត់បង់ការផ្តោតអារម្មណ៍',
      autoSubmitTimeout: 'បញ្ជូនការប្រឡងស្វ័យប្រវត្តិនៅពេលអស់ម៉ោង',
      autoSubmitTimeoutDesc: 'រក្សាទុក និងបញ្ចប់ការប្រឡងភ្លាមៗនៅពេលនាឡិការាប់ដល់ ០០:០០',
      diagnosticsTitle: 'ស្ថានភាពបច្ចេកទេសប្រព័ន្ធ',
      diagnosticsSubtitle: 'ព័ត៌មានជំនួយ និង Server Environment',
      phpVersion: 'PHP Version',
      laravelVersion: 'Laravel Version',
      dbEngine: 'Database Engine',
      systemStatus: 'ស្ថានភាពប្រព័ន្ធ'
    }
  }
  return {
    title: 'System Settings',
    subtitle: 'Manage global security rules, assessment behavior, and platform configuration',
    saveSettings: 'Save Settings',
    generalTitle: 'General Institution Configuration',
    generalSubtitle: 'System branding and academic period settings',
    institutionName: 'Institution / Platform Name',
    academicYear: 'Academic Year',
    timezone: 'Timezone',
    defaultLanguage: 'Default Language',
    authTitle: 'Authentication & Session Security',
    authSubtitle: 'Session timeout rules and self-enrollment toggles',
    sessionTimeout: 'Inactivity Session Timeout (Minutes)',
    allowSelfReg: 'Enable Student Self-Registration',
    allowSelfRegDesc: 'Permits new examinees to register from the public portal',
    forceStrongPass: 'Enforce Strong Passwords',
    forceStrongPassDesc: 'Requires minimum 6 characters for all accounts',
    examRulesTitle: 'Assessment & Anti-Cheat Controls',
    examRulesSubtitle: 'Autosave intervals and window focus disruption tracking',
    autosaveInterval: 'Autosave Cadence (Seconds)',
    antiCheatBlur: 'Pause assessment on window blur / tab switch',
    antiCheatBlurDesc: 'Shows Exam Paused overlay when exam window loses active focus',
    autoSubmitTimeout: 'Auto-submit when timer reaches zero',
    autoSubmitTimeoutDesc: 'Finalizes and records current answers immediately upon countdown expiration',
    diagnosticsTitle: 'System Health & Diagnostics',
    diagnosticsSubtitle: 'Server runtime environment and version information',
    phpVersion: 'PHP Runtime',
    laravelVersion: 'Laravel Version',
    dbEngine: 'Database Engine',
    systemStatus: 'System Status'
  }
})

const loadSettings = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/admin/system-settings')
    Object.assign(form, res.data.settings)
  } catch (e) {
    toastError('Failed to load system settings.')
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  try {
    const res = await axios.post('/api/admin/system-settings', form)
    toastSuccess(lang.value === 'kh' ? 'រក្សាទុកការកំណត់ប្រព័ន្ធបានជោគជ័យ!' : 'System settings updated successfully!')
    await fetchSettings(true)
    if (res.data.settings) {
      Object.assign(form, res.data.settings)
    }
  } catch (e) {
    const msg = e.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចរក្សាទុកការកំណត់បានទេ' : 'Failed to save system settings.')
    toastError(msg)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>
