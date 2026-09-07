<template>
  <div class="relative w-full" ref="dropdownRef">
    <!-- Dropdown Button -->
    <button
      type="button"
      @click="toggle"
      :disabled="disabled"
      :class="[
        'w-full flex items-center justify-between gap-2 rounded-xl border bg-slate-50/70 px-3.5 py-2.5 text-sm font-medium transition-all duration-150 focus-ring cursor-pointer select-none text-left disabled:opacity-50 disabled:cursor-not-allowed',
        isOpen ? 'border-blue-600 bg-white ring-2 ring-blue-600/15' : 'hover:border-slate-300 hover:bg-white',
        hasError ? 'border-red-400 bg-red-50/30 text-red-900' : 'border-slate-200 text-slate-800'
      ]"
    >
      <span
        :class="[
          'truncate flex-1',
          selectedItem ? 'text-slate-900 font-semibold' : 'text-slate-400'
        ]"
      >
        {{ selectedLabel || placeholder }}
      </span>

      <span
        class="material-symbols-outlined text-slate-400 text-lg transition-transform duration-200 shrink-0 select-none"
        :class="{ 'rotate-180 text-blue-600': isOpen }"
      >
        expand_more
      </span>
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 translate-y-1 scale-98"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-1 scale-98"
    >
      <div
        v-if="isOpen"
        :class="[
          'absolute z-40 w-full min-w-[180px] bg-white rounded-2xl border border-slate-200/90 shadow-soft-xl overflow-hidden flex flex-col py-1.5',
          openUpward ? 'bottom-full mb-1.5' : 'top-full mt-1.5'
        ]"
        :style="{ maxHeight: `${menuMaxHeight}px` }"
      >
        <div class="overflow-y-auto divide-y divide-slate-50 flex-1 px-1">
          <button
            v-for="option in normalizedOptions"
            :key="option.value"
            type="button"
            :class="[
              'w-full flex items-center justify-between gap-2 px-3 py-2 text-sm rounded-xl text-left transition-colors select-none cursor-pointer',
              String(modelValue) === String(option.value)
                ? 'bg-blue-50 text-blue-700 font-bold'
                : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'
            ]"
            @click="selectOption(option)"
          >
            <div class="flex items-center gap-2 min-w-0 flex-1">
              <span class="truncate">{{ option.label }}</span>
              <span
                v-if="option.subLabel"
                class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 shrink-0"
              >
                {{ option.subLabel }}
              </span>
            </div>

            <span
              v-if="String(modelValue) === String(option.value)"
              class="material-symbols-outlined text-blue-600 text-base shrink-0 select-none"
              style="font-variation-settings: 'FILL' 1;"
            >
              check
            </span>
          </button>

          <div v-if="normalizedOptions.length === 0" class="px-4 py-6 text-center text-xs text-slate-400">
            No options available
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select...' },
  valueKey: { type: String, default: 'value' },
  labelKey: { type: String, default: 'label' },
  subLabelKey: { type: String, default: 'subLabel' },
  hasError: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const openUpward = ref(false)
const menuMaxHeight = ref(240)
const dropdownRef = ref(null)

const normalizedOptions = computed(() =>
  props.options.map(opt =>
    typeof opt !== 'object' || opt === null
      ? { label: String(opt ?? ''), value: opt, subLabel: null }
      : { label: opt[props.labelKey] ?? opt.label ?? String(opt[props.valueKey] ?? ''), value: opt[props.valueKey] !== undefined ? opt[props.valueKey] : opt.value, subLabel: opt[props.subLabelKey] || null }
  )
)

const selectedItem = computed(() =>
  normalizedOptions.value.find(opt => String(opt.value) === String(props.modelValue))
)

const selectedLabel = computed(() => selectedItem.value?.label ?? null)

const recalcPosition = () => {
  if (!dropdownRef.value) return
  const PADDING = 8, MAX_MENU = 240
  const rect = dropdownRef.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom - PADDING
  const spaceAbove = rect.top - PADDING

  if (spaceBelow < 160 && spaceAbove > spaceBelow) {
    openUpward.value = true
    menuMaxHeight.value = Math.min(MAX_MENU, Math.max(100, spaceAbove))
  } else {
    openUpward.value = false
    menuMaxHeight.value = Math.min(MAX_MENU, Math.max(100, spaceBelow))
  }
}

const toggle = () => {
  if (props.disabled) return
  if (!isOpen.value) recalcPosition()
  isOpen.value = !isOpen.value
}

const selectOption = (option) => {
  emit('update:modelValue', option.value)
  emit('change', option.value)
  isOpen.value = false
}

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('resize', recalcPosition)
  window.addEventListener('scroll', recalcPosition, true)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', recalcPosition)
  window.removeEventListener('scroll', recalcPosition, true)
})
</script>
