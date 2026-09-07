<template>
  <div class="relative flex items-center w-full">
    <span class="material-symbols-outlined absolute left-3 text-slate-400 pointer-events-none select-none text-lg">
      search
    </span>

    <input
      :value="modelValue"
      type="text"
      inputmode="search"
      autocomplete="off"
      spellcheck="false"
      :placeholder="placeholder"
      :disabled="disabled"
      class="w-full rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-9 py-2 text-sm text-slate-900 font-medium transition-all duration-150 focus-ring hover:border-slate-300 focus:border-blue-600 focus:bg-white placeholder:text-slate-400 disabled:opacity-50 search-input-field"
      @input="onInput"
      @keyup.esc="clear"
    />

    <button
      v-if="modelValue"
      type="button"
      title="Clear search"
      class="absolute right-2.5 p-0.5 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors flex items-center justify-center cursor-pointer"
      @click="clear"
    >
      <span class="material-symbols-outlined text-base">close</span>
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Search...' },
  disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'clear'])

const onInput = (e) => {
  emit('update:modelValue', e.target.value)
}

const clear = () => {
  emit('update:modelValue', '')
  emit('clear')
}
</script>

<style scoped>
.search-input-field::-webkit-search-decoration,
.search-input-field::-webkit-search-cancel-button,
.search-input-field::-webkit-search-results-button,
.search-input-field::-webkit-search-results-decoration {
  -webkit-appearance: none;
  appearance: none;
  display: none !important;
}
</style>
