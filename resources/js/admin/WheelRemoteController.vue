<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-khmer select-none">
    
    <!-- ── 1. COMPACT TOP STATUS HEADER ─────────────────────────────── -->
    <header class="h-14 shrink-0 border-b border-slate-800 bg-slate-900/90 backdrop-blur-md px-4 flex items-center justify-between sticky top-0 z-40">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 flex items-center justify-center shadow-md shadow-indigo-500/20">
          <span class="material-symbols-outlined text-white text-base">smartphone</span>
        </div>
        <div>
          <h1 class="text-sm font-black tracking-tight text-white flex items-center gap-1 leading-none">
            តេលេបញ្ជា <span class="text-[10px] text-indigo-400 font-bold bg-indigo-950 px-1 py-0.5 rounded border border-indigo-800">REMOTE</span>
          </h1>
          <p class="text-[10px] text-slate-400 leading-none mt-0.5">OnlineXam Lucky Wheel</p>
        </div>
      </div>

      <!-- Room PIN Pill & Connection Status -->
      <div v-if="isConnected" class="flex items-center gap-2">
        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-xs font-bold font-mono">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-slate-400">PIN:</span>
          <span class="text-emerald-300 font-black tracking-wider">{{ roomPin }}</span>
        </div>

        <button
          type="button"
          class="p-1 rounded-lg text-slate-400 hover:text-rose-400 transition"
          title="ផ្តាច់ការភ្ជាប់"
          @click="disconnectRoom"
        >
          <span class="material-symbols-outlined text-lg">logout</span>
        </button>
      </div>
    </header>

    <!-- ── 2. SCREEN A: ENTER ROOM PIN (If not connected) ───────────── -->
    <main v-if="!isConnected" class="flex-1 flex flex-col items-center justify-center p-5 max-w-sm mx-auto w-full">
      <div class="w-full bg-slate-900/90 border-2 border-indigo-900/50 rounded-3xl p-6 shadow-2xl text-center backdrop-blur-xl">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center mb-4">
          <span class="material-symbols-outlined text-3xl">cast_connected</span>
        </div>

        <h2 class="text-lg font-black text-white mb-1">ភ្ជាប់ជាមួយអេក្រង់កងវិល</h2>
        <p class="text-xs text-slate-400 mb-6">សូមបញ្ចូលលេខកូដ Room PIN ៤ ខ្ទង់ដែលបង្ហាញនៅលើអេក្រង់កុំព្យូទ័រ ឬទូរទស្សន៍</p>

        <!-- PIN Input Form -->
        <form @submit.prevent="connectWithPin" class="space-y-4">
          <div>
            <input
              v-model="inputPin"
              type="tel"
              maxlength="4"
              pattern="[0-9]*"
              placeholder="0 0 0 0"
              class="w-full text-center text-3xl font-mono font-black tracking-[0.5em] bg-slate-950 border-2 border-indigo-500/50 focus:border-emerald-400 rounded-2xl py-3 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 transition"
              :disabled="isConnecting"
              autofocus
            />
          </div>

          <div v-if="errorMessage" class="text-xs font-semibold text-rose-400 flex items-center justify-center gap-1">
            <span class="material-symbols-outlined text-sm">error</span>
            <span>{{ errorMessage }}</span>
          </div>

          <button
            type="submit"
            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-extrabold text-sm shadow-lg shadow-indigo-600/30 active:scale-[0.98] transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            :disabled="isConnecting || inputPin.length < 4"
          >
            <span class="material-symbols-outlined text-lg animate-spin" v-if="isConnecting">sync</span>
            <span class="material-symbols-outlined text-lg" v-else>link</span>
            <span>{{ isConnecting ? 'កំពុងភ្ជាប់...' : 'ភ្ជាប់តេលេបញ្ជា (Connect)' }}</span>
          </button>
        </form>
      </div>
    </main>

    <!-- ── 3. SCREEN B: ACTIVE REMOTE CONTROLLER ─────────────────────── -->
    <main v-else class="flex-1 flex flex-col p-4 max-w-md mx-auto w-full justify-between gap-3">
      
      <!-- ── SECTION: VIEW 1 - SETUP_VIEW (Waiting) ────────────────── -->
      <div v-if="gameState.view === 'SETUP_VIEW'" class="flex-1 flex flex-col items-center justify-center text-center p-4">
        <div class="w-16 h-16 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center mb-3 animate-pulse">
          <span class="material-symbols-outlined text-3xl">settings</span>
        </div>
        <h3 class="text-base font-black text-white mb-1">អេក្រង់ធំកំពុងស្ថិតក្នុងផ្ទាំងកំណត់</h3>
        <p class="text-xs text-slate-400 mb-6">លោកគ្រូអ្នកគ្រូអាចចុច "ចាប់ផ្តើមលេង" ពីទូរស័ព្ទ ឬនៅលើកុំព្យូទ័រផ្ទាល់</p>
        
        <button
          type="button"
          class="w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-500 text-white font-black text-base shadow-xl active:scale-95 transition flex items-center justify-center gap-2"
          @click="sendCommand('START_SESSION')"
        >
          <span class="material-symbols-outlined text-2xl">play_arrow</span>
          <span>ចាប់ផ្តើមលេង (Start Session)</span>
        </button>
      </div>

      <!-- ── SECTION: VIEW 2 - WHEEL_VIEW (Spin Controls) ──────────── -->
      <div v-else-if="gameState.view === 'WHEEL_VIEW'" class="flex-1 flex flex-col justify-between py-2">
        
        <!-- Live Progress Badge -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 flex items-center justify-between shadow-md">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-400 text-lg">tag</span>
            <span class="text-xs font-bold text-slate-300">ពាក្យទី {{ gameState.currentWordIndex + 1 }} / {{ gameState.wordsPerRound }}</span>
          </div>
          <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-black">
            <span class="material-symbols-outlined text-sm text-amber-400">star</span>
            <span>{{ gameState.currentScore }} ពិន្ទុ</span>
          </div>
        </div>

        <!-- Middle Card: Student Winner or Spinning Status -->
        <div class="my-auto py-4 text-center">
          
          <!-- When Spinning -->
          <div v-if="gameState.isSpinning" class="py-6">
            <span class="material-symbols-outlined text-6xl text-amber-400 animate-spin filter drop-shadow-[0_0_15px_rgba(245,158,11,0.6)]">
              sync
            </span>
            <div class="text-lg font-black text-amber-300 mt-3 animate-pulse">កងវិលកំពុងបង្វិល...</div>
            <p class="text-xs text-slate-400 mt-1">សូមរង់ចាំមើលថាតើសិស្សណាត្រូវឡើងពន្យល់</p>
          </div>

          <!-- When Explainer Chosen -->
          <div v-else-if="gameState.currentExplainer" class="bg-indigo-950/80 border-2 border-indigo-500/50 rounded-3xl p-5 shadow-2xl">
            <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
              <span class="material-symbols-outlined text-xs text-amber-400">campaign</span>
              <span>សិស្សឡើងពន្យល់ (EXPLAINER)</span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-white py-1 break-words">
              {{ gameState.currentExplainer }}
            </div>
            <p class="text-xs text-emerald-400 font-bold mt-1">សិស្សនេះត្រូវបានជ្រើសរើស!</p>
          </div>

          <!-- Waiting for Spin -->
          <div v-else class="py-6">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center mb-3">
              <span class="material-symbols-outlined text-3xl">casino</span>
            </div>
            <div class="text-base font-extrabold text-white">ត្រៀមខ្លួនបង្វិលកង</div>
            <p class="text-xs text-slate-400 mt-1">ចុចប៊ូតុងខាងក្រោមដើម្បីបង្វិលកងស្វែងរកសិស្ស</p>
          </div>

        </div>

        <!-- Giant Mobile Action Buttons -->
        <div class="space-y-3">
          
          <!-- When Winner is already chosen: Show Word & Re-spin -->
          <template v-if="gameState.currentExplainer && !gameState.isSpinning">
            <button
              type="button"
              class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-lg shadow-xl shadow-emerald-600/35 active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer"
              @click="sendCommand('START_GUESSING')"
            >
              <span class="material-symbols-outlined text-2xl">visibility</span>
              <span>បង្ហាញពាក្យ (Show Word / Start)</span>
            </button>

            <button
              type="button"
              class="w-full py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 font-bold text-sm active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer"
              @click="sendCommand('SPIN')"
            >
              <span class="material-symbols-outlined text-amber-400 text-lg">replay</span>
              <span>បង្វិលម្ដងទៀត (សិស្សអវត្តមាន)</span>
            </button>
          </template>

          <!-- Main SPIN Button -->
          <template v-else>
            <button
              type="button"
              class="w-full py-5 rounded-3xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xl tracking-wider uppercase shadow-[0_0_30px_rgba(245,158,11,0.4)] active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              :disabled="gameState.isSpinning"
              @click="sendCommand('SPIN')"
            >
              <span class="material-symbols-outlined text-2xl animate-spin" v-if="gameState.isSpinning">sync</span>
              <span class="material-symbols-outlined text-2xl" v-else>sync</span>
              <span>{{ gameState.isSpinning ? 'កំពុងបង្វិល...' : 'បង្វិលកង (SPIN)' }}</span>
            </button>
          </template>

        </div>

      </div>

      <!-- ── SECTION: VIEW 3 - GUESSING_VIEW (Clicker Controls) ────── -->
      <div v-else-if="gameState.view === 'GUESSING_VIEW'" class="flex-1 flex flex-col justify-between py-1 gap-3">
        
        <!-- Top Info: Timer, Explainer, Score -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 shadow-md space-y-2">
          
          <div class="flex items-center justify-between">
            <!-- Explainer Tag -->
            <div class="flex items-center gap-1.5 min-w-0 flex-1 pr-2">
              <span class="material-symbols-outlined text-indigo-400 text-base shrink-0">mic</span>
              <span class="text-xs font-bold text-white truncate">{{ gameState.currentExplainer }}</span>
            </div>

            <!-- Score Pill -->
            <div class="flex items-center gap-1 px-2 py-0.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-black shrink-0">
              <span class="material-symbols-outlined text-sm text-emerald-400">star</span>
              <span>{{ gameState.currentScore }} ពិន្ទុ</span>
            </div>
          </div>

          <!-- Timer Bar & Controls -->
          <div class="flex items-center gap-2 pt-1 border-t border-slate-800">
            <span class="material-symbols-outlined text-amber-400 text-base">schedule</span>
            <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden">
              <div
                class="h-full transition-all duration-1000 ease-linear"
                :class="gameState.timerSeconds <= 10 ? 'bg-rose-500 animate-pulse' : 'bg-gradient-to-r from-amber-500 to-rose-500'"
                :style="{ width: `${(gameState.timerSeconds / 60) * 100}%` }"
              ></div>
            </div>
            <span class="text-xs font-mono font-black w-8 text-right" :class="gameState.timerSeconds <= 10 ? 'text-rose-400' : 'text-amber-300'">
              {{ gameState.timerSeconds }}s
            </span>

            <button
              type="button"
              class="w-7 h-7 rounded-lg bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center"
              :title="gameState.isTimerPaused ? 'បន្តម៉ោង' : 'ផ្អាកម៉ោង'"
              @click="sendCommand('TOGGLE_TIMER')"
            >
              <span class="material-symbols-outlined text-sm">{{ gameState.isTimerPaused ? 'play_arrow' : 'pause' }}</span>
            </button>
          </div>

        </div>

        <!-- Secret Word Display on Phone (Teacher's Secret Card) -->
        <div class="bg-gradient-to-b from-indigo-950/80 to-slate-900 border-2 border-indigo-500/40 rounded-3xl p-4 text-center shadow-xl my-auto">
          <div class="flex items-center justify-between text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">
            <span>ពាក្យសម្ងាត់ (SECRET WORD)</span>
            <button
              type="button"
              class="text-indigo-300 hover:text-white flex items-center gap-1"
              @click="sendCommand('TOGGLE_WORD_VISIBILITY')"
            >
              <span class="material-symbols-outlined text-xs">
                {{ gameState.isWordVisible ? 'visibility_off' : 'visibility' }}
              </span>
              <span>{{ gameState.isWordVisible ? 'បិទលើ screen' : 'បង្ហាញលើ screen' }}</span>
            </button>
          </div>

          <div class="text-2xl sm:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-100 to-emerald-300 py-2 leading-relaxed break-words">
            {{ gameState.currentWord || '...' }}
          </div>
          <p class="text-[10px] text-slate-400">ពាក្យនេះកំពុងបង្ហាញនៅលើអេក្រង់ធំ</p>
        </div>

        <!-- TWO GIANT THUMB CLICKER BUTTONS (Correct & Wrong) -->
        <div class="space-y-2.5">
          
          <!-- Correct Button (+1) -->
          <button
            type="button"
            class="w-full py-4 sm:py-5 rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 text-white font-black text-xl shadow-xl shadow-emerald-600/35 active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer"
            @click="sendCommand('DECISION_CORRECT')"
          >
            <span class="material-symbols-outlined text-3xl">check_circle</span>
            <span>ត្រូវ (Correct +1)</span>
          </button>

          <!-- Wrong Button (Skip) -->
          <button
            type="button"
            class="w-full py-4 sm:py-5 rounded-2xl bg-gradient-to-r from-rose-600 via-rose-500 to-red-600 text-white font-black text-xl shadow-xl shadow-rose-600/35 active:scale-95 transition flex items-center justify-center gap-2 cursor-pointer"
            @click="sendCommand('DECISION_WRONG')"
          >
            <span class="material-symbols-outlined text-3xl">cancel</span>
            <span>ខុស (Wrong / Skip)</span>
          </button>

        </div>

      </div>

      <!-- ── SECTION: VIEW 4 - ROUND_SUMMARY_VIEW ───────────────────── -->
      <div v-else-if="gameState.view === 'ROUND_SUMMARY_VIEW'" class="flex-1 flex flex-col items-center justify-center text-center p-4 my-auto">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-amber-500/20 border-2 border-amber-500/40 text-amber-400 flex items-center justify-center text-4xl mb-4 shadow-xl shadow-amber-500/20">
          <span class="material-symbols-outlined text-4xl">trophy</span>
        </div>

        <h3 class="text-xl font-black text-white mb-1">ចប់ការប្រកួតជុំនេះ!</h3>
        <p class="text-xs text-slate-400 mb-4">លទ្ធផលពិន្ទុរួមរបស់ថ្នាក់រៀន</p>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 w-full mb-6">
          <div class="text-3xl font-black text-amber-400">{{ gameState.currentScore }} / {{ gameState.wordsPerRound }}</div>
          <div class="text-xs text-slate-400 mt-1">ពាក្យទាយត្រូវសរុប</div>
        </div>

        <button
          type="button"
          class="w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-500 text-white font-black text-base shadow-xl active:scale-95 transition flex items-center justify-center gap-2"
          @click="sendCommand('NEW_ROUND')"
        >
          <span class="material-symbols-outlined text-2xl">replay</span>
          <span>ចាប់ផ្តើមជុំថ្មី (New Round)</span>
        </button>
      </div>

      <!-- Footer Help -->
      <footer class="text-center text-[10px] text-slate-500 py-1">
        <span>តេលេបញ្ជាទូរស័ព្ទ • OnlineXam System</span>
      </footer>

    </main>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()

const roomPin = ref('')
const inputPin = ref('')
const isConnected = ref(false)
const isConnecting = ref(false)
const errorMessage = ref('')

const gameState = reactive({
  view: 'SETUP_VIEW',
  currentWord: '',
  currentExplainer: '',
  currentScore: 0,
  wordsPerRound: 5,
  currentWordIndex: 0,
  timerSeconds: 60,
  isTimerPaused: false,
  isSpinning: false,
  isWordVisible: true,
})

let pollInterval = null
let heartbeatInterval = null

function triggerHaptic() {
  if (typeof navigator !== 'undefined' && navigator.vibrate) {
    try {
      navigator.vibrate(35)
    } catch (e) {}
  }
}

async function connectToRoom(pin) {
  if (!pin || pin.length < 4) return
  isConnecting.value = true
  errorMessage.value = ''

  try {
    const res = await axios.get(`/api/lucky-wheel/remote/state?room=${pin}`)
    if (res.data.success) {
      roomPin.value = pin
      isConnected.value = true
      Object.assign(gameState, res.data.state)
      localStorage.setItem('wheel_remote_pin', pin)
      startPolling()
      triggerHaptic()
    } else {
      errorMessage.value = 'មិនមាន Room PIN នេះនៅលើប្រព័ន្ធឡើយ'
    }
  } catch (err) {
    errorMessage.value = 'មិនអាចភ្ជាប់បានទេ។ សូមពិនិត្យលេខកូដ Room PIN ឡើងវិញ។'
  } finally {
    isConnecting.value = false
  }
}

function connectWithPin() {
  if (inputPin.value.length === 4) {
    connectToRoom(inputPin.value)
  }
}

function disconnectRoom() {
  stopPolling()
  isConnected.value = false
  roomPin.value = ''
  localStorage.removeItem('wheel_remote_pin')
}

async function sendCommand(action, payload = {}) {
  if (!roomPin.value) return
  triggerHaptic()

  // Optimistic local feedback
  if (action === 'SPIN') {
    gameState.isSpinning = true
  }

  try {
    await axios.post('/api/lucky-wheel/remote/command', {
      room: roomPin.value,
      action,
      payload
    })
  } catch (err) {
    console.error('Failed to send remote command:', err)
  }
}

async function fetchState() {
  if (!roomPin.value || !isConnected.value) return
  try {
    const res = await axios.get(`/api/lucky-wheel/remote/state?room=${roomPin.value}`)
    if (res.data.success && res.data.state) {
      Object.assign(gameState, res.data.state)
    }
  } catch (err) {
    if (err.response && err.response.status === 404) {
      // Room was closed or expired
      disconnectRoom()
      errorMessage.value = 'បន្ទប់ល្បែងត្រូវបានបិទ ឬផុតកំណត់'
    }
  }
}

function startPolling() {
  stopPolling()
  // Poll state every 750ms for responsive updates
  pollInterval = setInterval(fetchState, 750)
  
  // Heartbeat ping every 6 seconds to let desktop know phone is active
  heartbeatInterval = setInterval(async () => {
    if (roomPin.value && isConnected.value) {
      try {
        await axios.post('/api/lucky-wheel/remote/ping', {
          room: roomPin.value,
          role: 'phone'
        })
      } catch (e) {}
    }
  }, 6000)
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
  if (heartbeatInterval) {
    clearInterval(heartbeatInterval)
    heartbeatInterval = null
  }
}

onMounted(() => {
  // Check if room PIN passed in URL (e.g. ?room=4821 or ?pin=4821)
  const queryRoom = route.query.room || route.query.pin
  const savedPin = localStorage.getItem('wheel_remote_pin')

  if (queryRoom && queryRoom.length === 4) {
    inputPin.value = queryRoom
    connectToRoom(queryRoom)
  } else if (savedPin && savedPin.length === 4) {
    inputPin.value = savedPin
    connectToRoom(savedPin)
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.font-khmer {
  font-family: 'Kantumruy Pro', 'Outfit', system-ui, sans-serif;
}
</style>
