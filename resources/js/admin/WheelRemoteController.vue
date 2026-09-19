<template>
  <div class="remote-root">
    
    <!-- ── 1. COMPACT STATUS HEADER ───────────────────────────────────── -->
    <header class="remote-header">
      <div class="remote-brand">
        <div class="remote-icon-badge">
          <!-- Smartphone SVG -->
          <svg class="svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
            <line x1="12" y1="18" x2="12.01" y2="18"></line>
          </svg>
        </div>
        <div class="brand-text">
          <div class="brand-title">
            តេលេបញ្ជា <span class="badge-tag">REMOTE</span>
          </div>
          <div class="brand-sub">OnlineXam Lucky Wheel</div>
        </div>
      </div>

      <!-- Room PIN Pill & Connection Status -->
      <div v-if="isConnected" class="status-pill-group">
        <div class="pin-pill">
          <span class="live-pulse"></span>
          <span class="pin-lbl">PIN:</span>
          <span class="pin-val">{{ roomPin }}</span>
        </div>

        <button
          type="button"
          class="btn-icon-disconnect"
          title="ផ្តាច់ការភ្ជាប់"
          @click="disconnectRoom"
        >
          <!-- Logout SVG -->
          <svg class="svg-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </button>
      </div>
    </header>

    <!-- ── 2. SCREEN A: ENTER ROOM PIN (If not connected) ─────────────── -->
    <main v-if="!isConnected" class="remote-screen-center">
      <div class="remote-card">
        <div class="radar-badge">
          <!-- Cast / Radio SVG -->
          <svg class="svg-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 16.1A5 5 0 0 1 5.9 20M2 12.05A9 9 0 0 1 9.95 20M2 8A13 13 0 0 1 14 20"></path>
            <line x1="2" y1="20" x2="2.01" y2="20"></line>
          </svg>
        </div>

        <h2 class="card-title">ភ្ជាប់ជាមួយអេក្រង់កងវិល</h2>
        <p class="card-desc">សូមបញ្ចូលលេខកូដ Room PIN ៤ ខ្ទង់ដែលបង្ហាញនៅលើអេក្រង់កុំព្យូទ័រ ឬទូរទស្សន៍</p>

        <!-- PIN Input Form -->
        <form @submit.prevent="connectWithPin" class="pin-form">
          <div class="input-wrapper">
            <input
              v-model="inputPin"
              type="tel"
              maxlength="4"
              pattern="[0-9]*"
              placeholder="0 0 0 0"
              class="pin-text-input"
              :disabled="isConnecting"
              autofocus
            />
          </div>

          <div v-if="errorMessage" class="error-banner">
            <!-- Alert SVG -->
            <svg class="svg-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ errorMessage }}</span>
          </div>

          <button
            type="submit"
            class="btn-primary-glow"
            :disabled="isConnecting || inputPin.length < 4"
          >
            <!-- Sync / Spinner SVG -->
            <svg v-if="isConnecting" class="svg-icon-sm spin-animate" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
            </svg>
            <!-- Link SVG -->
            <svg v-else class="svg-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
              <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
            </svg>
            <span>{{ isConnecting ? 'កំពុងភ្ជាប់...' : 'ភ្ជាប់តេលេបញ្ជា (Connect)' }}</span>
          </button>
        </form>
      </div>
    </main>

    <!-- ── 3. SCREEN B: ACTIVE REMOTE CONTROLLER ───────────────────────── -->
    <main v-else class="remote-screen-active">
      
      <!-- ── SECTION: VIEW 1 - SETUP_VIEW (Waiting) ──────────────────── -->
      <div v-if="gameState.view === 'SETUP_VIEW'" class="view-panel view-center">
        <div class="view-icon-circle pulse-slow">
          <!-- Settings Cog SVG -->
          <svg class="svg-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
        </div>
        <h3 class="panel-heading">អេក្រង់ធំកំពុងស្ថិតក្នុងផ្ទាំងកំណត់</h3>
        <p class="panel-desc">លោកគ្រូអ្នកគ្រូអាចចុច "ចាប់ផ្តើមលេង" ពីទូរស័ព្ទ ឬនៅលើកុំព្យូទ័រផ្ទាល់</p>
        
        <button
          type="button"
          class="btn-start-session"
          @click="sendCommand('START_SESSION')"
        >
          <!-- Play SVG -->
          <svg class="svg-icon-md" viewBox="0 0 24 24" fill="currentColor">
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
          <span>ចាប់ផ្តើមលេង (Start Session)</span>
        </button>
      </div>

      <!-- ── SECTION: VIEW 2 - WHEEL_VIEW (Spin Controls) ────────────── -->
      <div v-else-if="gameState.view === 'WHEEL_VIEW'" class="view-panel view-column">
        
        <!-- Live Progress Badge -->
        <div class="stats-row">
          <div class="stat-tag">
            <span class="stat-dot"></span>
            <span>ពាក្យទី {{ gameState.currentWordIndex + 1 }} / {{ gameState.wordsPerRound }}</span>
          </div>
          <div class="score-pill">
            <!-- Star SVG -->
            <svg class="svg-icon-xs text-amber" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            <span>{{ gameState.currentScore }} ពិន្ទុ</span>
          </div>
        </div>

        <!-- Middle Card: Student Winner or Spinning Status -->
        <div class="wheel-stage-card">
          
          <!-- When Spinning -->
          <div v-if="gameState.isSpinning" class="spinning-box">
            <div class="spin-wheel-graphic spin-fast">
              <svg viewBox="0 0 100 100" class="wheel-svg">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#f59e0b" stroke-width="6" stroke-dasharray="10 6" />
                <circle cx="50" cy="50" r="30" fill="none" stroke="#6366f1" stroke-width="4" stroke-dasharray="8 4" />
                <circle cx="50" cy="50" r="10" fill="#f59e0b" />
              </svg>
            </div>
            <div class="spinning-text">កងវិលកំពុងបង្វិល...</div>
            <p class="spinning-sub">សូមរង់ចាំមើលថាតើសិស្សណាត្រូវឡើងពន្យល់</p>
          </div>

          <!-- When Explainer Chosen -->
          <div v-else-if="gameState.currentExplainer" class="winner-box">
            <div class="winner-label">
              <!-- Megaphone SVG -->
              <svg class="svg-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 11l18-5v12L3 13v-2z"></path>
                <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>
              </svg>
              <span>សិស្សឡើងពន្យល់ (EXPLAINER)</span>
            </div>
            <div class="winner-name">
              {{ gameState.currentExplainer }}
            </div>
            <p class="winner-sub">សិស្សនេះត្រូវបានជ្រើសរើស!</p>
          </div>

          <!-- Waiting for Spin -->
          <div v-else class="ready-box">
            <div class="ready-icon-box">
              <!-- Dices / Casino SVG -->
              <svg class="svg-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                <circle cx="8" cy="8" r="1.5" fill="currentColor"></circle>
                <circle cx="16" cy="16" r="1.5" fill="currentColor"></circle>
                <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                <circle cx="16" cy="8" r="1.5" fill="currentColor"></circle>
                <circle cx="8" cy="16" r="1.5" fill="currentColor"></circle>
              </svg>
            </div>
            <div class="ready-text">ត្រៀមខ្លួនបង្វិលកង</div>
            <p class="ready-sub">ចុចប៊ូតុងខាងក្រោមដើម្បីបង្វិលកងស្វែងរកសិស្ស</p>
          </div>

        </div>

        <!-- Giant Mobile Action Buttons -->
        <div class="action-dock">
          
          <!-- When Winner is already chosen: Show Word & Re-spin -->
          <template v-if="gameState.currentExplainer && !gameState.isSpinning">
            <button
              type="button"
              class="btn-show-word"
              @click="sendCommand('START_GUESSING')"
            >
              <!-- Eye SVG -->
              <svg class="svg-icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <span>បង្ហាញពាក្យ (Show Word / Start)</span>
            </button>

            <button
              type="button"
              class="btn-respin"
              @click="sendCommand('SPIN')"
            >
              <!-- Refresh SVG -->
              <svg class="svg-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
              </svg>
              <span>បង្វិលម្ដងទៀត (សិស្សអវត្តមាន)</span>
            </button>
          </template>

          <!-- Giant Arcade SPIN Button -->
          <template v-else>
            <button
              type="button"
              class="btn-giant-spin"
              :class="{ 'is-spinning': gameState.isSpinning }"
              :disabled="gameState.isSpinning"
              @click="sendCommand('SPIN')"
            >
              <div class="spin-bevel">
                <!-- Rotate SVG -->
                <svg class="svg-icon-lg" :class="{ 'spin-fast': gameState.isSpinning }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
                <span class="spin-label">{{ gameState.isSpinning ? 'កំពុងបង្វិល...' : 'បង្វិលកង (SPIN)' }}</span>
              </div>
            </button>
          </template>

        </div>

      </div>

      <!-- ── SECTION: VIEW 3 - GUESSING_VIEW (Clicker Controls) ──────── -->
      <div v-else-if="gameState.view === 'GUESSING_VIEW'" class="view-panel view-column">
        
        <!-- Top Info: Timer, Explainer, Score -->
        <div class="guessing-header-box">
          <div class="explainer-row">
            <div class="explainer-tag">
              <!-- Mic SVG -->
              <svg class="svg-icon-xs text-indigo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                <line x1="12" y1="19" x2="12" y2="23"></line>
                <line x1="8" y1="23" x2="16" y2="23"></line>
              </svg>
              <span class="explainer-name-short">{{ gameState.currentExplainer }}</span>
            </div>

            <div class="score-pill-emerald">
              <!-- Star SVG -->
              <svg class="svg-icon-xs text-emerald" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
              <span>{{ gameState.currentScore }} ពិន្ទុ</span>
            </div>
          </div>

          <!-- Timer Bar & Controls -->
          <div class="timer-row">
            <!-- Clock SVG -->
            <svg class="svg-icon-xs text-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <div class="timer-track">
              <div
                class="timer-bar-fill"
                :class="{ 'timer-critical': gameState.timerSeconds <= 10 }"
                :style="{ width: `${(gameState.timerSeconds / 60) * 100}%` }"
              ></div>
            </div>
            <span class="timer-num" :class="{ 'text-rose': gameState.timerSeconds <= 10 }">
              {{ gameState.timerSeconds }}s
            </span>

            <button
              type="button"
              class="btn-timer-toggle"
              :title="gameState.isTimerPaused ? 'បន្តម៉ោង' : 'ផ្អាកម៉ោង'"
              @click="sendCommand('TOGGLE_TIMER')"
            >
              <!-- Play / Pause SVG -->
              <svg v-if="gameState.isTimerPaused" class="svg-icon-xs" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
              </svg>
              <svg v-else class="svg-icon-xs" viewBox="0 0 24 24" fill="currentColor">
                <rect x="6" y="4" width="4" height="16"></rect>
                <rect x="14" y="4" width="4" height="16"></rect>
              </svg>
            </button>
          </div>
        </div>

        <!-- Secret Word Display on Phone (Teacher's Secret Card) -->
        <div class="secret-word-card">
          <div class="secret-head">
            <span class="secret-title">ពាក្យសម្ងាត់ (SECRET WORD)</span>
            <button
              type="button"
              class="btn-secret-toggle"
              @click="sendCommand('TOGGLE_WORD_VISIBILITY')"
            >
              <!-- Eye / Eye off SVG -->
              <svg v-if="gameState.isWordVisible" class="svg-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
              <svg v-else class="svg-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <span>{{ gameState.isWordVisible ? 'បិទលើ screen' : 'បង្ហាញលើ screen' }}</span>
            </button>
          </div>

          <div class="secret-word-text">
            {{ gameState.currentWord || '...' }}
          </div>
          <p class="secret-sub">ពាក្យនេះកំពុងបង្ហាញនៅលើអេក្រង់ធំ</p>
        </div>

        <!-- TWO GIANT THUMB CLICKER BUTTONS (Correct & Wrong) -->
        <div class="clicker-duo">
          
          <!-- Correct Button (+1) -->
          <button
            type="button"
            class="btn-clicker btn-correct"
            @click="sendCommand('DECISION_CORRECT')"
          >
            <div class="clicker-inner">
              <!-- Check Circle SVG -->
              <svg class="svg-icon-xl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              <span class="clicker-text">ត្រូវ (Correct +1)</span>
            </div>
          </button>

          <!-- Wrong Button (Skip) -->
          <button
            type="button"
            class="btn-clicker btn-wrong"
            @click="sendCommand('DECISION_WRONG')"
          >
            <div class="clicker-inner">
              <!-- X Circle SVG -->
              <svg class="svg-icon-xl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              <span class="clicker-text">ខុស (Wrong / Skip)</span>
            </div>
          </button>

        </div>

      </div>

      <!-- ── SECTION: VIEW 4 - ROUND_SUMMARY_VIEW ────────────────────── -->
      <div v-else-if="gameState.view === 'ROUND_SUMMARY_VIEW'" class="view-panel view-center">
        <div class="trophy-circle">
          <!-- Trophy SVG -->
          <svg class="svg-icon-xl text-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
            <path d="M4 22h16"></path>
            <path d="M10 14.66V17c0 .55-.45 1-1 1H7"></path>
            <path d="M14 14.66V17c0 .55.45 1 1 1h2"></path>
            <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
          </svg>
        </div>

        <h3 class="panel-heading">ចប់ការប្រកួតជុំនេះ!</h3>
        <p class="panel-desc">លទ្ធផលពិន្ទុរួមរបស់ថ្នាក់រៀន</p>

        <div class="summary-score-card">
          <div class="big-score">{{ gameState.currentScore }} / {{ gameState.wordsPerRound }}</div>
          <div class="score-caption">ពាក្យទាយត្រូវសរុប</div>
        </div>

        <button
          type="button"
          class="btn-new-round"
          @click="sendCommand('NEW_ROUND')"
        >
          <!-- Replay SVG -->
          <svg class="svg-icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"></polyline>
            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
          </svg>
          <span>ចាប់ផ្តើមជុំថ្មី (New Round)</span>
        </button>
      </div>

      <!-- Footer Help -->
      <footer class="remote-footer">
        <span>តេលេបញ្ជាទូរស័ព្ទ • OnlineXam Remote</span>
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
      disconnectRoom()
      errorMessage.value = 'បន្ទប់ល្បែងត្រូវបានបិទ ឬផុតកំណត់'
    }
  }
}

function startPolling() {
  stopPolling()
  // Poll state every 750ms for responsive clicker updates
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
/* ── BULLETPROOF SELF-CONTAINED REMOTE STYLING (ZERO DEPENDENCY) ─────── */
.remote-root {
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  background: radial-gradient(circle at 50% 0%, #151d30 0%, #080b12 55%, #04060a 100%) !important;
  color: #f8fafc !important;
  font-family: 'Kantumruy Pro', 'Outfit', system-ui, -apple-system, sans-serif !important;
  display: flex !important;
  flex-direction: column !important;
  user-select: none !important;
  -webkit-user-select: none !important;
  touch-action: manipulation;
  box-sizing: border-box !important;
}

.remote-root *,
.remote-root *::before,
.remote-root *::after {
  box-sizing: border-box !important;
}

/* ── SVG ICONS ───────────────────────────────────────────────────────── */
.svg-icon {
  width: 18px;
  height: 18px;
  display: block;
}
.svg-icon-xs {
  width: 14px;
  height: 14px;
  display: block;
}
.svg-icon-sm {
  width: 18px;
  height: 18px;
  display: block;
}
.svg-icon-md {
  width: 22px;
  height: 22px;
  display: block;
}
.svg-icon-lg {
  width: 28px;
  height: 28px;
  display: block;
}
.svg-icon-xl {
  width: 38px;
  height: 38px;
  display: block;
}

/* ── HEADER ──────────────────────────────────────────────────────────── */
.remote-header {
  height: 56px;
  flex-shrink: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(13, 18, 30, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 50;
}

.remote-brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.remote-icon-badge {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: linear-gradient(135deg, #4f46e5, #06b6d4);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.brand-text {
  line-height: 1.1;
}

.brand-title {
  font-size: 13px;
  font-weight: 800;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 6px;
}

.badge-tag {
  font-size: 9px;
  font-weight: 800;
  color: #818cf8;
  background: rgba(49, 46, 129, 0.6);
  padding: 1px 5px;
  border-radius: 4px;
  border: 1px solid rgba(99, 102, 241, 0.4);
  letter-spacing: 0.5px;
}

.brand-sub {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 2px;
}

/* Status Pill */
.status-pill-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pin-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.12);
  font-family: monospace;
  font-size: 12px;
  font-weight: 700;
}

.live-pulse {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #34d399;
  box-shadow: 0 0 8px #34d399;
  animation: pulseAnim 1.5s infinite;
}

.pin-lbl {
  color: #94a3b8;
}

.pin-val {
  color: #6ee7b7;
  font-weight: 900;
  letter-spacing: 1px;
}

.btn-icon-disconnect {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: none;
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-icon-disconnect:hover,
.btn-icon-disconnect:active {
  color: #fb7185;
  background: rgba(244, 63, 94, 0.15);
}

/* ── SCREEN A: ENTER PIN ─────────────────────────────────────────────── */
.remote-screen-center {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  max-width: 400px;
  margin: 0 auto;
  width: 100%;
}

.remote-card {
  width: 100%;
  background: rgba(15, 23, 42, 0.85);
  border: 1.5px solid rgba(99, 102, 241, 0.35);
  border-radius: 28px;
  padding: 28px 22px;
  text-align: center;
  box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

.radar-badge {
  width: 64px;
  height: 64px;
  margin: 0 auto 16px auto;
  border-radius: 20px;
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.3);
  color: #818cf8;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 25px rgba(99, 102, 241, 0.2);
}

.card-title {
  font-size: 18px;
  font-weight: 900;
  color: #ffffff;
  margin: 0 0 6px 0;
  line-height: 1.3;
}

.card-desc {
  font-size: 12px;
  color: #94a3b8;
  margin: 0 0 24px 0;
  line-height: 1.5;
}

.pin-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.input-wrapper {
  position: relative;
}

.pin-text-input {
  width: 100% !important;
  text-align: center !important;
  font-size: 32px !important;
  font-family: monospace, ui-monospace, sans-serif !important;
  font-weight: 900 !important;
  letter-spacing: 0.4em !important;
  background: #060911 !important;
  border: 2px solid rgba(99, 102, 241, 0.4) !important;
  border-radius: 18px !important;
  padding: 14px 10px !important;
  color: #ffffff !important;
  outline: none !important;
  box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.6) !important;
  transition: all 0.2s ease !important;
}

.pin-text-input:focus {
  border-color: #34d399 !important;
  box-shadow: 0 0 20px rgba(52, 211, 153, 0.3), inset 0 2px 6px rgba(0, 0, 0, 0.6) !important;
}

.error-banner {
  font-size: 12px;
  font-weight: 600;
  color: #fb7185;
  background: rgba(244, 63, 94, 0.1);
  border: 1px solid rgba(244, 63, 94, 0.25);
  border-radius: 10px;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.btn-primary-glow {
  width: 100%;
  padding: 14px 20px;
  border-radius: 18px;
  border: none;
  background: linear-gradient(135deg, #4f46e5 0%, #10b981 100%);
  color: #ffffff;
  font-size: 14px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  box-shadow: 0 8px 25px rgba(79, 70, 229, 0.35);
  transition: transform 0.15s, box-shadow 0.15s;
}

.btn-primary-glow:active {
  transform: scale(0.98);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
}

.btn-primary-glow:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

/* ── SCREEN B: ACTIVE REMOTE CONTROLLER ─────────────────────────────── */
.remote-screen-active {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 14px 16px;
  max-width: 440px;
  margin: 0 auto;
  width: 100%;
  justify-content: space-between;
  gap: 12px;
}

.view-panel {
  flex: 1;
  display: flex;
}

.view-column {
  flex-direction: column;
  justify-content: space-between;
}

.view-center {
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 20px 10px;
}

.view-icon-circle {
  width: 68px;
  height: 68px;
  border-radius: 22px;
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.3);
  color: #818cf8;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}

.panel-heading {
  font-size: 17px;
  font-weight: 900;
  color: #ffffff;
  margin: 0 0 6px 0;
}

.panel-desc {
  font-size: 12px;
  color: #94a3b8;
  margin: 0 0 24px 0;
  line-height: 1.5;
}

.btn-start-session {
  width: 100%;
  padding: 16px;
  border-radius: 20px;
  border: none;
  background: linear-gradient(135deg, #4f46e5 0%, #10b981 100%);
  color: #ffffff;
  font-size: 15px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 10px 30px rgba(79, 70, 229, 0.35);
  cursor: pointer;
  transition: transform 0.15s;
}
.btn-start-session:active {
  transform: scale(0.97);
}

/* ── VIEW 2: WHEEL VIEW ─────────────────────────────────────────────── */
.stats-row {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-tag {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 800;
  color: #cbd5e1;
}

.stat-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #818cf8;
}

.score-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 10px;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #fcd34d;
  font-size: 12px;
  font-weight: 900;
}

.text-amber {
  color: #fbbf24;
}
.text-indigo {
  color: #818cf8;
}
.text-emerald {
  color: #34d399;
}
.text-rose {
  color: #fb7185;
}

.wheel-stage-card {
  margin: auto 0;
  padding: 16px 0;
  text-align: center;
}

.spinning-box {
  padding: 10px 0;
}

.spin-wheel-graphic {
  width: 90px;
  height: 90px;
  margin: 0 auto 12px auto;
}

.wheel-svg {
  width: 100%;
  height: 100%;
}

.spinning-text {
  font-size: 18px;
  font-weight: 900;
  color: #fcd34d;
  margin-top: 6px;
  animation: pulseAnim 1.2s infinite;
}

.spinning-sub {
  font-size: 12px;
  color: #94a3b8;
  margin: 4px 0 0 0;
}

/* Winner picked */
.winner-box {
  background: rgba(30, 27, 75, 0.7);
  border: 2px solid rgba(99, 102, 241, 0.45);
  border-radius: 24px;
  padding: 20px 16px;
  box-shadow: 0 15px 40px -10px rgba(0, 0, 0, 0.6);
}

.winner-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(99, 102, 241, 0.25);
  color: #a5b4fc;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.winner-name {
  font-size: 22px;
  font-weight: 900;
  color: #ffffff;
  padding: 4px 0;
  line-height: 1.3;
  word-break: break-word;
}

.winner-sub {
  font-size: 11px;
  font-weight: 700;
  color: #34d399;
  margin: 4px 0 0 0;
}

/* Ready to spin */
.ready-box {
  padding: 10px 0;
}

.ready-icon-box {
  width: 64px;
  height: 64px;
  margin: 0 auto 12px auto;
  border-radius: 20px;
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.25);
  color: #fbbf24;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ready-text {
  font-size: 17px;
  font-weight: 900;
  color: #ffffff;
}

.ready-sub {
  font-size: 12px;
  color: #94a3b8;
  margin: 4px 0 0 0;
}

/* Action dock */
.action-dock {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-show-word {
  width: 100%;
  padding: 16px;
  border-radius: 20px;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
  color: #ffffff;
  font-size: 16px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 10px 30px rgba(5, 150, 105, 0.4);
  cursor: pointer;
  transition: transform 0.15s;
}
.btn-show-word:active {
  transform: scale(0.97);
}

.btn-respin {
  width: 100%;
  padding: 12px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(30, 41, 59, 0.7);
  color: #cbd5e1;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: transform 0.15s;
}
.btn-respin:active {
  transform: scale(0.97);
}

/* Giant Arcade Spin Button */
.btn-giant-spin {
  width: 100%;
  padding: 6px;
  border-radius: 28px;
  border: none;
  background: linear-gradient(180deg, #f59e0b, #d97706);
  box-shadow: 0 12px 35px rgba(245, 158, 11, 0.45), 0 4px 0 #92400e;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;
}

.btn-giant-spin:active:not(:disabled) {
  transform: translateY(4px);
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3), 0 0 0 #92400e;
}

.btn-giant-spin:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spin-bevel {
  padding: 16px 20px;
  border-radius: 24px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #0b0f19;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: 1px;
}

/* ── VIEW 3: GUESSING VIEW (CLICKER CONTROLS) ────────────────────────── */
.guessing-header-box {
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.explainer-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.explainer-tag {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
}

.explainer-name-short {
  font-size: 13px;
  font-weight: 800;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.score-pill-emerald {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 8px;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #6ee7b7;
  font-size: 12px;
  font-weight: 900;
  flex-shrink: 0;
}

.timer-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 8px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.timer-track {
  flex: 1;
  height: 8px;
  border-radius: 999px;
  background: rgba(30, 41, 59, 0.8);
  overflow: hidden;
}

.timer-bar-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #f59e0b, #ef4444);
  transition: width 1s linear;
}

.timer-critical {
  background: #f43f5e !important;
  animation: pulseAnim 0.8s infinite;
}

.timer-num {
  width: 38px;
  text-align: right;
  font-family: monospace;
  font-size: 13px;
  font-weight: 900;
  color: #fcd34d;
}

.btn-timer-toggle {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

/* Secret Word Card */
.secret-word-card {
  margin: auto 0;
  background: linear-gradient(180deg, rgba(30, 27, 75, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
  border: 1.5px solid rgba(99, 102, 241, 0.35);
  border-radius: 24px;
  padding: 16px 14px;
  text-align: center;
  box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.6);
}

.secret-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}

.secret-title {
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.8px;
  color: #94a3b8;
}

.btn-secret-toggle {
  border: none;
  background: transparent;
  color: #a5b4fc;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
}

.secret-word-text {
  font-size: 26px;
  font-weight: 900;
  color: #ffffff;
  padding: 6px 0;
  line-height: 1.3;
  word-break: break-word;
  text-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
}

.secret-sub {
  font-size: 10px;
  color: #64748b;
  margin: 0;
}

/* Two Giant Clicker Buttons */
.clicker-duo {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-clicker {
  width: 100%;
  padding: 4px;
  border-radius: 24px;
  border: none;
  cursor: pointer;
  transition: transform 0.12s, box-shadow 0.12s;
}

.clicker-inner {
  padding: 18px 20px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  color: #ffffff;
}

.clicker-text {
  font-size: 19px;
  font-weight: 900;
  letter-spacing: 0.5px;
}

/* Correct (+1) */
.btn-correct {
  background: linear-gradient(180deg, #10b981, #047857);
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4), 0 4px 0 #064e3b;
}
.btn-correct .clicker-inner {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
.btn-correct:active {
  transform: translateY(4px);
  box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3), 0 0 0 #064e3b;
}

/* Wrong (Skip) */
.btn-wrong {
  background: linear-gradient(180deg, #f43f5e, #be123c);
  box-shadow: 0 8px 25px rgba(244, 63, 94, 0.4), 0 4px 0 #881337;
}
.btn-wrong .clicker-inner {
  background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
}
.btn-wrong:active {
  transform: translateY(4px);
  box-shadow: 0 2px 10px rgba(244, 63, 94, 0.3), 0 0 0 #881337;
}

/* ── VIEW 4: ROUND SUMMARY ───────────────────────────────────────────── */
.trophy-circle {
  width: 76px;
  height: 76px;
  border-radius: 24px;
  background: rgba(245, 158, 11, 0.15);
  border: 1.5px solid rgba(245, 158, 11, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  box-shadow: 0 0 30px rgba(245, 158, 11, 0.25);
}

.summary-score-card {
  width: 100%;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 18px;
  margin-bottom: 24px;
}

.big-score {
  font-size: 34px;
  font-weight: 900;
  color: #fbbf24;
}

.score-caption {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

.btn-new-round {
  width: 100%;
  padding: 16px;
  border-radius: 20px;
  border: none;
  background: linear-gradient(135deg, #4f46e5 0%, #10b981 100%);
  color: #ffffff;
  font-size: 15px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 10px 30px rgba(79, 70, 229, 0.35);
  cursor: pointer;
  transition: transform 0.15s;
}
.btn-new-round:active {
  transform: scale(0.97);
}

/* ── FOOTER ──────────────────────────────────────────────────────────── */
.remote-footer {
  text-align: center;
  font-size: 10px;
  color: #475569;
  padding: 4px 0 8px 0;
}

/* ── ANIMATIONS ──────────────────────────────────────────────────────── */
@keyframes pulseAnim {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.04); }
}

@keyframes spinClockwise {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.spin-fast {
  animation: spinClockwise 1s linear infinite;
}

.spin-animate {
  animation: spinClockwise 1s linear infinite;
}

.pulse-slow {
  animation: pulseAnim 2s infinite ease-in-out;
}
</style>
