<template>
  <div class="min-h-[70vh] flex flex-col items-center justify-center p-6 text-center">
    <Card padding="lg" class="max-w-md w-full shadow-soft-md border-slate-200">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-4 border border-amber-100">
        <span class="material-symbols-outlined text-4xl">lock</span>
      </div>

      <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 mb-2">
        <span class="material-symbols-outlined text-xs">admin_panel_settings</span>
        {{ lang === 'kh' ? 'សិទ្ធិត្រូវបានកំណត់' : 'Access Restricted' }}
      </span>

      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">
        {{ lang === 'kh' ? 'មិនមានសិទ្ធិចូលមើលទំព័រនេះទេ' : 'Permission Required' }}
      </h1>

      <p class="text-sm text-slate-500 mt-2 leading-relaxed">
        {{ lang === 'kh'
          ? 'ទំព័រនេះទាមទារសិទ្ធិជា Super Admin។ សូមទាក់ទង Super Admin ប្រសិនបើអ្នកត្រូវការចូលប្រើប្រាស់មុខងារនេះ។'
          : 'You do not have sufficient permissions to access this page. Please contact a Super Administrator if you need access.'
        }}
      </p>

      <div class="pt-6 mt-6 border-t border-slate-100 flex flex-col gap-2.5">
        <Button
          variant="primary"
          full-width
          size="lg"
          icon="dashboard"
          @click="router.push('/admin/dashboard')"
        >
          {{ lang === 'kh' ? 'ត្រឡប់ទៅផ្ផ្ទាំងគ្រប់គ្រង' : 'Go to Dashboard' }}
        </Button>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import { useLang } from '../utils/useLang'

const router = useRouter()
const { lang } = useLang()
</script>
