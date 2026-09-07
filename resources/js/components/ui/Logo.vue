<template>
  <div class="flex items-center gap-3 select-none">
    <!-- Brand Logo SVG Icon (Official ico.svg) -->
    <div
      :class="[
        'flex items-center justify-center shrink-0 rounded-2xl p-1.5 transition-transform duration-200 group-hover:scale-105 shadow-soft-sm',
        variant === 'dark' ? 'bg-white/10 border border-white/15' : 'bg-blue-50/90 border border-blue-100',
        sizeClasses
      ]"
    >
      <img
        :src="icoSvg"
        alt="Education Icon"
        class="h-full w-full object-contain select-none pointer-events-none"
      />
    </div>

    <!-- Brand Typography -->
    <div v-if="!iconOnly" class="min-w-0">
      <div class="flex items-center gap-1.5">
        <span
          :class="[
            'font-black tracking-tight leading-none text-slate-900',
            textColorClass,
            fontSizeClass
          ]"
        >
          {{ brandTitle }}
        </span>
        <span
          v-if="badgeText"
          :class="[
            'text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded-md leading-none',
            badgeClass
          ]"
        >
          {{ badgeText }}
        </span>
      </div>
      <p
        :class="[
          'text-[10px] font-bold uppercase tracking-widest leading-none mt-1',
          subColorClass
        ]"
      >
        {{ brandSubtitle }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import icoSvg from '../../../../public/ico.svg'
import { useSettings } from '../../composables/useSettings'

const { settings } = useSettings()

const props = defineProps({
  variant: {
    type: String,
    default: 'admin', // 'admin' | 'superadmin' | 'student' | 'public' | 'dark'
  },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
  },
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  iconOnly: {
    type: Boolean,
    default: false
  },
  badge: {
    type: String,
    default: ''
  }
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'h-8 w-8 rounded-xl'
    case 'lg': return 'h-12 w-12 rounded-2xl'
    default: return 'h-10 w-10 rounded-2xl'
  }
})

const fontSizeClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'text-sm'
    case 'lg': return 'text-lg'
    default: return 'text-base'
  }
})

const textColorClass = computed(() => {
  if (props.variant === 'dark') return 'text-white'
  return 'text-slate-900'
})

const subColorClass = computed(() => {
  if (props.variant === 'dark') return 'text-slate-400'
  return 'text-slate-400'
})

const brandTitle = computed(() => {
  if (props.title) return props.title
  switch (props.variant) {
    case 'superadmin': return 'SuperPortal'
    case 'admin': return 'ExamAdmin'
    case 'student': return 'StudentPortal'
    case 'dark':
    case 'public': return settings.institutionName || 'OnlineExam'
    default: return settings.institutionName || 'OnlineExam'
  }
})

const brandSubtitle = computed(() => {
  if (props.subtitle) return props.subtitle
  switch (props.variant) {
    case 'superadmin': return 'Root System'
    case 'admin': return 'Operations'
    case 'student': return 'Evaluation & Exams'
    case 'dark':
    case 'public': return 'Evaluation Platform'
    default: return 'Evaluation Platform'
  }
})

const badgeText = computed(() => props.badge)

const badgeClass = computed(() => {
  switch (props.variant) {
    case 'superadmin': return 'bg-purple-100 text-purple-700'
    case 'student': return 'bg-emerald-100 text-emerald-700'
    default: return 'bg-blue-100 text-blue-700'
  }
})
</script>
