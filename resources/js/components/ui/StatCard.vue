<template>
  <div
    :class="[
      'rounded-2xl border transition-all duration-200 p-5 sm:p-6 flex flex-col justify-between relative overflow-hidden',
      accent
        ? 'bg-blue-600 border-blue-500 text-white shadow-soft-md shadow-blue-500/20'
        : 'bg-white border-slate-200/90 shadow-soft-sm hover:border-slate-300'
    ]"
  >
    <!-- Background subtle decoration for accent -->
    <div v-if="accent" class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-white/10 pointer-events-none"></div>

    <div class="flex items-start justify-between gap-4 mb-3">
      <!-- Icon Container -->
      <div
        :class="[
          'flex h-11 w-11 items-center justify-center rounded-xl shrink-0',
          accent ? 'bg-white/20 text-white' : iconBgClass
        ]"
      >
        <span class="material-symbols-outlined text-2xl select-none" style="font-variation-settings: 'FILL' 1;">
          {{ icon }}
        </span>
      </div>

      <!-- Optional Trend / Badge -->
      <div
        v-if="trend"
        :class="[
          'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold',
          trendUp ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'
        ]"
      >
        <span class="material-symbols-outlined text-xs">{{ trendUp ? 'trending_up' : 'trending_down' }}</span>
        {{ trend }}
      </div>
    </div>

    <!-- Value & Label -->
    <div>
      <p
        :class="[
          'text-xs font-bold uppercase tracking-wider mb-1',
          accent ? 'text-blue-100' : 'text-slate-500'
        ]"
      >
        {{ label }}
      </p>
      <div class="flex items-baseline gap-2">
        <h4
          :class="[
            'text-2xl sm:text-3xl font-extrabold tracking-tight',
            accent ? 'text-white' : 'text-slate-900'
          ]"
        >
          {{ value }}
        </h4>
        <span v-if="suffix" :class="accent ? 'text-blue-100 text-sm font-semibold' : 'text-slate-400 text-sm font-semibold'">
          {{ suffix }}
        </span>
      </div>
      <p v-if="hint" :class="accent ? 'text-blue-200 text-xs mt-1' : 'text-slate-400 text-xs mt-1'">
        {{ hint }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: String, required: true },
  suffix: { type: String, default: '' },
  hint: { type: String, default: '' },
  trend: { type: String, default: '' },
  trendUp: { type: Boolean, default: true },
  accent: { type: Boolean, default: false },
  color: {
    type: String,
    default: 'blue',
    validator: (v) => ['blue', 'indigo', 'purple', 'emerald', 'amber', 'rose'].includes(v)
  }
})

const iconBgClass = computed(() => {
  switch (props.color) {
    case 'indigo': return 'bg-indigo-50 text-indigo-600'
    case 'purple': return 'bg-purple-50 text-purple-600'
    case 'emerald': return 'bg-emerald-50 text-emerald-600'
    case 'amber': return 'bg-amber-50 text-amber-600'
    case 'rose': return 'bg-rose-50 text-rose-600'
    case 'blue':
    default: return 'bg-blue-50 text-blue-600'
  }
})
</script>
