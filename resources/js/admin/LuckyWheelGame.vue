<template>
  <div
    ref="gameRootRef"
    class="lucky-wheel-app fixed inset-0 w-screen h-screen min-h-screen max-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between overflow-hidden select-none font-khmer z-40"
  >
    <!-- Background Ambient Glow Mesh -->
    <div class="absolute inset-0 bg-mesh pointer-events-none -z-0"></div>

    <!-- ── 1. COMPACT TOP TOOLBAR ───────────────────────────────────── -->
    <header class="h-12 sm:h-14 shrink-0 border-b border-slate-800/80 bg-slate-900/80 backdrop-blur-md px-3 sm:px-6 flex items-center justify-between z-30">
      
      <!-- Left: Back to Dashboard & App Title -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Back to Dashboard Option -->
        <button
          type="button"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white text-xs sm:text-sm font-bold border border-slate-700/80 transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-[0.98]"
          title="ត្រឡប់ទៅផ្ទាំងគ្រប់គ្រង (Back to Dashboard)"
          @click="goBackToDashboard"
        >
          <span class="material-symbols-outlined text-base sm:text-lg text-blue-400">arrow_back</span>
          <span>ត្រឡប់ទៅផ្ទាំងគ្រប់គ្រង</span>
        </button>

        <div class="h-5 w-px bg-slate-800 hidden sm:block"></div>

        <div class="flex items-center gap-2">
          <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 flex items-center justify-center shadow-md shadow-indigo-500/25">
            <span class="material-symbols-outlined text-white text-base animate-spin" style="animation-duration: 20s;">casino</span>
          </div>
          <div>
            <h1 class="text-xs sm:text-sm md:text-base font-black tracking-tight bg-gradient-to-r from-white via-indigo-200 to-emerald-300 bg-clip-text text-transparent flex items-center gap-1.5 leading-none">
              ទាយពាក្យ <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-indigo-400 bg-indigo-950/70 px-1 py-0.5 rounded border border-indigo-800/60">កងវិលសំណាង</span>
            </h1>
          </div>
        </div>
      </div>

      <!-- Right Controls: Progress, Sound, Setup, Fullscreen -->
      <div class="flex items-center gap-1.5 sm:gap-2.5">
        
        <!-- Word Progress Badge (In Wheel & Guessing Views) -->
        <div
          v-if="currentView === 'WHEEL_VIEW' || currentView === 'GUESSING_VIEW'"
          class="flex items-center gap-1.5 sm:gap-2 bg-slate-800/90 border border-slate-700/80 px-2 sm:px-2.5 py-1 rounded-lg text-xs font-semibold shadow-sm"
        >
          <span class="text-slate-400 text-[11px]">ពាក្យ:</span>
          <span class="text-emerald-400 font-black">{{ currentWordIndex + 1 }} / {{ wordsPerRound }}</span>
          <span class="text-slate-600">|</span>
          <span class="text-slate-400 text-[11px]">ពិន្ទុ:</span>
          <span class="text-amber-400 font-black">{{ currentScore }}</span>
        </div>

        <!-- Sound Toggle -->
        <button
          type="button"
          class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white transition flex items-center justify-center cursor-pointer"
          :title="isMuted ? 'បើកសំឡេង (Unmute)' : 'បិទសំឡេង (Mute)'"
          @click="toggleSound"
        >
          <span class="material-symbols-outlined text-sm sm:text-base">
            {{ isMuted ? 'volume_off' : 'volume_up' }}
          </span>
        </button>

        <!-- Mobile Remote Control Button -->
        <button
          type="button"
          class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 rounded-lg border transition cursor-pointer"
          :class="isRemoteConnected 
            ? 'bg-emerald-950/80 hover:bg-emerald-900/90 border-emerald-500/60 text-emerald-300 shadow-sm shadow-emerald-500/20' 
            : 'bg-slate-800 hover:bg-slate-700 border-slate-700 text-slate-300 hover:text-white'"
          title="តេលេបញ្ជាទូរស័ព្ទ (Mobile Remote Controller)"
          @click="openRemoteModal"
        >
          <span class="material-symbols-outlined text-sm sm:text-base" :class="isRemoteConnected ? 'text-emerald-400' : 'text-indigo-400'">smartphone</span>
          <span class="text-xs font-bold hidden md:inline">តេលេបញ្ជា</span>
          <span v-if="isRemoteConnected" class="inline-flex items-center gap-1 text-[10px] font-extrabold text-emerald-400 bg-emerald-500/20 px-1.5 py-0.2 rounded border border-emerald-500/40">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Online</span>
          </span>
          <span v-else-if="remotePin" class="text-[10px] font-mono text-indigo-300 bg-indigo-950/80 px-1.5 py-0.2 rounded border border-indigo-800/60">
            {{ remotePin }}
          </span>
        </button>

        <!-- Setup Quick Button -->
        <button
          v-if="currentView !== 'SETUP_VIEW'"
          type="button"
          class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white transition flex items-center justify-center cursor-pointer"
          title="ការកំណត់ (Setup Session)"
          @click="switchView('SETUP_VIEW')"
        >
          <span class="material-symbols-outlined text-sm sm:text-base">settings</span>
        </button>

        <!-- Fullscreen / Projector Mode Toggle -->
        <button
          type="button"
          class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-600/30 hover:bg-indigo-600/50 border border-indigo-500/40 text-indigo-300 hover:text-white transition flex items-center justify-center cursor-pointer"
          :title="isFullscreen ? 'ចាកចេញពីពេញអេក្រង់ (Exit Fullscreen)' : 'បញ្ចាំងពេញអេក្រង់ (Fullscreen Projector)'"
          @click="toggleFullscreen"
        >
          <span class="material-symbols-outlined text-sm sm:text-base">
            {{ isFullscreen ? 'fullscreen_exit' : 'fullscreen' }}
          </span>
        </button>
      </div>

    </header>

    <!-- ── 2. MAIN WORKSPACE CONTAINER (Zero Scrollbar Guaranteed) ───── -->
    <main class="flex-1 flex flex-col justify-center items-center px-2.5 sm:px-6 lg:px-8 py-1 sm:py-2 w-full max-w-[96vw] 2xl:max-w-[1680px] mx-auto relative min-h-0 overflow-hidden z-10">
      
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- VIEW 1: SETUP_VIEW (COMPACT & PRECISE)                          -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <section
        v-if="currentView === 'SETUP_VIEW'"
        class="w-full max-w-4xl bg-slate-900/90 border border-slate-800 rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-2xl backdrop-blur-xl my-auto transition-all"
      >
        <!-- Header -->
        <div class="text-center max-w-xl mx-auto mb-2.5 sm:mb-3">
          <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider mb-1">
            <span class="material-symbols-outlined text-xs">tune</span> រៀបចំបញ្ជីឈ្មោះ និងពាក្យទាយ (Setup Session)
          </div>
          <h2 class="text-base sm:text-xl font-extrabold text-white mb-0.5">ចាប់ផ្តើមល្បែងកងវិលទាយពាក្យក្នុងថ្នាក់រៀន</h2>
          <p class="text-[11px] sm:text-xs text-slate-400">បញ្ចូលឈ្មោះសិស្ស និងពាក្យត្រូវទាយ ឬទាញយកឈ្មោះសិស្សពីប្រព័ន្ធ OnlineXam ផ្ទាល់</p>
        </div>

        <!-- 2-Column Grid: Students & Words -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 sm:gap-3.5 mb-2.5 sm:mb-3.5">
          
          <!-- Column A: Students List -->
          <div class="flex flex-col bg-slate-950/70 rounded-xl p-2.5 sm:p-3 border border-slate-800/80">
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs sm:text-sm font-bold text-indigo-300 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-indigo-400">group</span> បញ្ជីឈ្មោះសិស្ស (Students)
              </label>
              <span class="text-[10px] sm:text-[11px] bg-indigo-950 text-indigo-300 px-2 py-0.5 rounded-md border border-indigo-800 font-semibold">
                {{ parsedStudents.length }} នាក់
              </span>
            </div>
            <textarea
              v-model="rawStudentsText"
              rows="4"
              class="w-full h-22 sm:h-28 bg-slate-900 border border-slate-700/80 rounded-lg p-2 text-xs sm:text-sm text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono resize-none leading-relaxed transition"
              placeholder="ឈ្មោះសិស្ស ១ នាក់ក្នុង ១ ជួរ..."
            ></textarea>
            <div class="mt-2 space-y-1.5">
              <!-- Skill Selection Row (Single Clean Dropdown - Never Overflows) -->
              <div class="flex items-center gap-1.5 bg-slate-900 border border-slate-700/80 rounded-lg p-1 shadow-inner overflow-hidden w-full">
                <span class="material-symbols-outlined text-xs sm:text-sm text-indigo-400 pl-1 shrink-0" title="ជ្រើសរើសជំនាញ">school</span>
                
                <!-- Single Unified Dropdown -->
                <select
                  v-model="selectedFilterId"
                  class="flex-1 min-w-0 bg-transparent text-[11px] sm:text-xs text-indigo-200 focus:outline-none cursor-pointer py-0.5 font-medium truncate"
                  :disabled="isLoadingOnlineStudents"
                  @change="onFilterChange"
                >
                  <option value="" class="bg-slate-900 text-slate-400">-- ជ្រើសរើសតាមជំនាញ (Select Skill) --</option>
                  <option
                    v-for="opt in filterOptions"
                    :key="opt.id"
                    :value="opt.id"
                    class="bg-slate-900 text-slate-200"
                  >
                    {{ opt.label }} ({{ opt.count }} នាក់)
                  </option>
                  <option value="__ALL__" class="bg-slate-900 text-slate-300">
                    សិស្សទាំងអស់ ({{ rawOnlineStudents.length }} នាក់)
                  </option>
                </select>

                <!-- Fetch / Apply Button -->
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] sm:text-xs font-bold flex items-center gap-1 transition cursor-pointer shrink-0 disabled:opacity-50"
                  :disabled="isLoadingOnlineStudents"
                  @click="handleDownloadClick"
                  title="ទាញយកសិស្សតាមជំនាញ"
                >
                  <span class="material-symbols-outlined text-xs animate-spin" v-if="isLoadingOnlineStudents">sync</span>
                  <span class="material-symbols-outlined text-xs" v-else>cloud_download</span>
                  <span>ទាញយក</span>
                </button>
              </div>

              <!-- Status line & demo button -->
              <div class="flex justify-between items-center text-[10px] sm:text-[11px] text-slate-400 px-0.5">
                <span class="text-slate-400 truncate max-w-[200px] sm:max-w-[260px]" v-if="currentLoadedSkillLabel">
                  បានទាញ: <span class="text-emerald-400 font-bold">{{ currentLoadedSkillLabel }}</span>
                </span>
                <span v-else class="text-slate-500 truncate">* សូមជ្រើសរើសជំនាញដើម្បីទាញឈ្មោះសិស្ស</span>

                <button
                  type="button"
                  class="text-indigo-400 hover:text-indigo-300 underline cursor-pointer shrink-0 ml-auto"
                  @click="loadDemoStudents"
                >
                  ឈ្មោះគំរូ
                </button>
              </div>
            </div>
          </div>

          <!-- Column B: Words Bank -->
          <div class="flex flex-col bg-slate-950/70 rounded-xl p-2.5 sm:p-3 border border-slate-800/80">
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs sm:text-sm font-bold text-emerald-300 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-emerald-400">spellcheck</span> បញ្ជីពាក្យត្រូវទាយ (Words Bank)
              </label>
              <span class="text-[10px] sm:text-[11px] bg-emerald-950 text-emerald-300 px-2 py-0.5 rounded-md border border-emerald-800 font-semibold">
                {{ parsedWords.length }} ពាក្យ
              </span>
            </div>
            <textarea
              v-model="rawWordsText"
              rows="4"
              class="w-full h-22 sm:h-28 bg-slate-900 border border-slate-700/80 rounded-lg p-2 text-xs sm:text-sm text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 font-mono resize-none leading-relaxed transition"
              placeholder="ពាក្យ ១ ក្នុង ១ ជួរ..."
            ></textarea>
            <div class="mt-1.5 flex justify-between items-center text-[10px] sm:text-[11px] text-slate-400">
              <span>* នឹងចៃដន្យពាក្យពេលលេង</span>
              <button
                type="button"
                class="text-emerald-400 hover:text-emerald-300 underline cursor-pointer"
                @click="loadDemoWords"
              >
                ពាក្យគំរូ
              </button>
            </div>
          </div>

        </div>

        <!-- Round Settings (Words per round) -->
        <div class="bg-gradient-to-r from-slate-950/90 via-indigo-950/40 to-slate-950 border border-indigo-900/50 rounded-xl p-2.5 sm:p-3 mb-3">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <div class="flex items-center gap-2 text-xs sm:text-sm font-bold text-indigo-300">
              <span class="material-symbols-outlined text-sm sm:text-base text-indigo-400">tune</span>
              <span>ការកំណត់ជុំលេង (Round Settings)</span>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
              <label class="text-xs sm:text-sm font-semibold text-slate-300 whitespace-nowrap">
                ចំនួនពាក្យក្នុងមួយជុំ:
              </label>
              <select
                v-model.number="wordsPerRound"
                class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1 text-white text-xs sm:text-sm focus:outline-none focus:border-indigo-500 cursor-pointer"
              >
                <option :value="3">3 ពាក្យ (3 Words)</option>
                <option :value="5">5 ពាក្យ (5 Words - គំរូទូទៅ)</option>
                <option :value="7">7 ពាក្យ (7 Words)</option>
                <option :value="10">10 ពាក្យ (10 Words)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Start Button -->
        <div class="flex items-center justify-center">
          <button
            type="button"
            class="w-full sm:w-auto px-8 py-2.5 sm:py-3 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-extrabold text-sm sm:text-base shadow-lg shadow-indigo-600/30 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
            @click="startSession"
          >
            <span class="material-symbols-outlined text-base sm:text-lg">play_arrow</span>
            <span>ចាប់ផ្តើមលេង (Start Session)</span>
          </button>
        </div>

      </section>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- VIEW 2: WHEEL_VIEW (GIANT WHEEL & PROMINENT SIDEBAR)            -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <section
        v-if="currentView === 'WHEEL_VIEW'"
        class="w-full max-w-7xl flex flex-col lg:flex-row items-center justify-center gap-5 lg:gap-8 xl:gap-12 my-auto px-2 transition-all"
      >
        <!-- LEFT COLUMN: GIANT CANVAS WHEEL -->
        <div class="relative flex flex-col items-center justify-center select-none shrink-0">
          
          <!-- Glowing Outer Halo -->
          <div class="absolute w-[280px] h-[280px] sm:w-[380px] sm:h-[380px] md:w-[440px] md:h-[440px] lg:w-[480px] lg:h-[480px] xl:w-[520px] xl:h-[520px] rounded-full bg-gradient-to-tr from-indigo-500/20 via-emerald-500/20 to-purple-500/20 blur-2xl -z-10 pointer-events-none"></div>

          <!-- Wheel Canvas Container -->
          <div class="relative p-2 rounded-full bg-gradient-to-b from-slate-700 via-slate-800 to-slate-950 border-2 sm:border-4 border-slate-700/80 shadow-[0_0_40px_-5px_rgba(0,0,0,0.8)]">
            <canvas
              ref="wheelCanvasRef"
              class="w-[280px] h-[280px] sm:w-[360px] sm:h-[360px] md:w-[420px] md:h-[420px] lg:w-[460px] lg:h-[460px] xl:w-[500px] xl:h-[500px] max-h-[66vh] max-w-[66vh] aspect-square rounded-full cursor-pointer touch-none"
              @click="handleWheelClick"
            ></canvas>

            <!-- Top Needle Indicator -->
            <div
              ref="wheelPointerRef"
              class="absolute -top-3 sm:-top-4 left-1/2 -translate-x-1/2 z-20 needle-bounce drop-shadow-[0_4px_10px_rgba(0,0,0,0.8)] transition-transform duration-75"
            >
              <svg width="36" height="48" viewBox="0 0 40 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 52L2 14C-0.5 9 3 2 9 2H31C37 2 40.5 9 38 14L20 52Z" fill="url(#pointerGradOnline)" stroke="#ffffff" stroke-width="2" />
                <circle cx="20" cy="14" r="6" fill="#ffffff" />
                <defs>
                  <linearGradient id="pointerGradOnline" x1="20" y1="2" x2="20" y2="52" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#EF4444" />
                    <stop offset="1" stop-color="#B91C1C" />
                  </linearGradient>
                </defs>
              </svg>
            </div>

            <!-- Center SPIN Button -->
            <button
              type="button"
              class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-b from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-xs sm:text-sm md:text-base tracking-wider uppercase shadow-[0_0_25px_rgba(245,158,11,0.6)] border-2 sm:border-4 border-white flex flex-col items-center justify-center gap-0.5 z-20 cursor-pointer active:scale-95 transition-transform"
              :class="{ 'scale-90 opacity-80': isSpinning }"
              title="ចុចដើម្បីបង្វិលកង (Press Space to Spin)"
              @click="triggerSpin"
            >
              <span class="material-symbols-outlined text-base sm:text-xl">sync</span>
              <span>SPIN</span>
            </button>
          </div>

          <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
            <kbd class="px-1.5 py-0.2 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-[10px]">Space</kbd> ដើម្បីបង្វិលកង
          </p>
        </div>

        <!-- RIGHT COLUMN: SIDEBAR WITH RESULTS & CONTROLS -->
        <div class="w-full lg:w-[380px] xl:w-[440px] 2xl:w-[480px] flex flex-col justify-center gap-3 shrink-0">
          
          <!-- Round Info Header -->
          <div class="w-full bg-slate-900/95 border-2 border-slate-800 rounded-2xl p-3 sm:p-4 shadow-xl backdrop-blur-md flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-lg">
                <span class="material-symbols-outlined">casino</span>
              </span>
              <div>
                <div class="text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-400 font-bold leading-none">វឌ្ឍនភាពជុំ (ROUND)</div>
                <div class="text-base sm:text-lg font-black text-emerald-400 leading-tight mt-0.5">
                  ពាក្យទី {{ currentWordIndex + 1 }} នៃ {{ wordsPerRound }}
                </div>
              </div>
            </div>

            <div class="text-right">
              <div class="text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-400 font-bold leading-none">ពិន្ទុជុំនេះ</div>
              <div class="text-base sm:text-lg font-black text-amber-300 leading-tight mt-0.5">
                {{ currentScore }} ពិន្ទុ
              </div>
            </div>
          </div>

          <!-- Explainer Card -->
          <div class="bg-gradient-to-br from-indigo-950/95 via-slate-900/98 to-slate-950 border-2 border-indigo-500/60 rounded-3xl p-5 sm:p-6 shadow-glow-indigo text-center transition-all duration-300 min-h-[190px] flex flex-col justify-center">
            
            <!-- Notice: Waiting to Spin -->
            <div v-if="!isSpinning && !currentExplainer" class="py-2">
              <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-2xl mb-2 animate-pulse">
                <span class="material-symbols-outlined text-2xl">touch_app</span>
              </div>
              <div class="text-base sm:text-lg font-black text-slate-200 mb-0.5">ចុច SPIN ដើម្បីបង្វិលកង</div>
              <p class="text-xs text-slate-400">ប្រព័ន្ធនឹងចៃដន្យជ្រើសរើសសិស្សឡើងពន្យល់ពាក្យ</p>
            </div>

            <!-- Notice: Currently Spinning (Clean Gold Icon without rotating background box) -->
            <div v-else-if="isSpinning" class="py-2">
              <div class="py-2 flex items-center justify-center mb-1">
                <span class="material-symbols-outlined text-5xl sm:text-6xl text-amber-400 animate-spin filter drop-shadow-[0_0_16px_rgba(245,158,11,0.65)]">
                  sync
                </span>
              </div>
              <div class="text-lg sm:text-xl font-black text-amber-300 mb-0.5">កំពុងបង្វិលកង...</div>
              <p class="text-xs text-slate-400">សូមរង់ចាំមើលថាតើសិស្សរូបណាជាអ្នកពន្យល់!</p>
            </div>

            <!-- Notice: Student Winner Chosen -->
            <div v-else class="py-1">
              <div class="text-xs uppercase tracking-widest text-indigo-400 font-extrabold flex items-center justify-center gap-1.5 mb-1">
                <span class="material-symbols-outlined text-amber-400 text-sm">campaign</span>
                <span>សិស្សឡើងពន្យល់ (EXPLAINER)</span>
              </div>
              <div class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-100 to-emerald-300 py-1 leading-tight break-words word-glow">
                {{ currentExplainer }}
              </div>
              <p class="text-xs sm:text-sm text-slate-300 font-semibold mt-1">សូមអញ្ជើញសិស្សនេះឡើងមកពន្យល់ពាក្យដល់មិត្តរួមថ្នាក់!</p>
            </div>

          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col gap-2.5">
            <!-- Primary Action: Show Word / Start Guessing -->
            <button
              type="button"
              class="w-full px-5 py-3 sm:py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-base sm:text-lg shadow-xl shadow-emerald-600/35 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer"
              :class="{ 'opacity-50 pointer-events-none': !currentExplainer || isSpinning }"
              @click="startGuessingCurrentWord"
            >
              <span class="material-symbols-outlined text-xl sm:text-2xl">visibility</span>
              <span>បង្ហាញពាក្យ (Show Word / Start)</span>
            </button>

            <!-- Secondary Action: Re-spin (Absent Student) -->
            <button
              type="button"
              class="w-full px-4 py-2.5 rounded-2xl bg-slate-800/95 hover:bg-slate-700 border-2 border-slate-700 text-slate-200 hover:text-white font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 shadow cursor-pointer"
              :disabled="isSpinning"
              @click="triggerSpin"
            >
              <span class="material-symbols-outlined text-amber-400 text-base">replay</span>
              <span>Re-spin (សិស្សអវត្តមាន)</span>
            </button>
          </div>

        </div>

      </section>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- VIEW 3: GUESSING_VIEW (PROJECTOR CARD - FULL COMMAND VIEW)      -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <section
        v-if="currentView === 'GUESSING_VIEW'"
        class="w-full max-w-6xl xl:max-w-7xl 2xl:max-w-[1550px] flex flex-col items-center justify-center my-auto px-2 sm:px-4 transition-all"
      >
        <!-- Top Meta Bar: Progress, Timer, Score -->
        <div class="w-full flex items-center justify-between bg-slate-900/90 border-2 border-slate-800 rounded-2xl px-5 sm:px-8 py-2.5 sm:py-3 mb-3 sm:mb-4 backdrop-blur-md shadow-xl">
          <!-- Word Progress -->
          <div class="flex items-center gap-2 sm:gap-3">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-sm sm:text-base font-bold">
              <span class="material-symbols-outlined text-base sm:text-xl">tag</span>
            </div>
            <div>
              <div class="text-[10px] sm:text-xs uppercase tracking-wider text-slate-400 font-bold leading-none">វឌ្ឍនភាពពាក្យ</div>
              <div class="text-xs sm:text-base md:text-lg font-black text-indigo-300 leading-tight mt-0.5">Word {{ currentWordIndex + 1 }} of {{ wordsPerRound }}</div>
            </div>
          </div>

          <!-- Timer Bar -->
          <div class="flex flex-col items-center justify-center">
            <div class="flex items-center gap-1.5 text-slate-400 text-xs sm:text-sm font-bold uppercase tracking-wider mb-1">
              <span class="material-symbols-outlined text-amber-400 text-sm sm:text-base">schedule</span>
              <span>ម៉ោង: <span :class="timerSeconds <= 10 ? 'text-rose-400 font-bold' : 'text-amber-300 font-bold'" class="font-mono text-xs sm:text-base">{{ timerSeconds }}s</span></span>
              <button
                type="button"
                class="text-slate-400 hover:text-white ml-1 cursor-pointer"
                :title="isTimerPaused ? 'បន្តម៉ោង (Resume)' : 'ផ្អាកម៉ោង (Pause)'"
                @click="toggleTimerPause"
              >
                <span class="material-symbols-outlined text-sm">{{ isTimerPaused ? 'play_arrow' : 'pause' }}</span>
              </button>
            </div>
            <div class="w-32 sm:w-64 md:w-80 h-2.5 bg-slate-800 rounded-full overflow-hidden border border-slate-700">
              <div
                class="h-full transition-all duration-1000 ease-linear"
                :class="timerSeconds <= 10 ? 'bg-rose-500 animate-pulse' : 'bg-gradient-to-r from-amber-500 to-rose-500'"
                :style="{ width: `${(timerSeconds / timerMaxSeconds) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- Live Score Badge -->
          <div class="flex items-center gap-2 sm:gap-3 text-right">
            <div>
              <div class="text-[10px] sm:text-xs uppercase tracking-wider text-slate-400 font-bold leading-none">ពិន្ទុបច្ចុប្បន្ន</div>
              <div class="text-xs sm:text-base md:text-lg font-black text-emerald-400 leading-tight mt-0.5">{{ currentScore }} ពិន្ទុ</div>
            </div>
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-sm sm:text-base font-bold">
              <span class="material-symbols-outlined text-base sm:text-xl text-emerald-400">star</span>
            </div>
          </div>
        </div>

        <!-- Giant Projector Display Card -->
        <div class="w-full bg-gradient-to-b from-slate-900/95 via-slate-900/90 to-slate-950 border-2 border-indigo-500/40 rounded-3xl p-5 sm:p-7 md:p-9 lg:p-11 shadow-2xl shadow-indigo-950/60 text-center relative overflow-hidden backdrop-blur-xl flex flex-col justify-between min-h-[48vh] max-h-[76vh]">
          
          <!-- Explainer Banner (Always 1 Single Line) -->
          <div class="max-w-3xl mx-auto mb-2 sm:mb-4 w-full">
            <div class="inline-flex items-center gap-2.5 sm:gap-3 bg-indigo-950/90 border-2 border-indigo-500/50 rounded-2xl py-2 px-4 sm:px-6 shadow-lg shadow-indigo-950/50 max-w-full">
              <span class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-500/30 text-indigo-300 flex items-center justify-center text-base sm:text-lg font-bold shrink-0">
                <span class="material-symbols-outlined text-lg sm:text-xl text-indigo-300">mic</span>
              </span>
              <div class="text-left min-w-0 flex-1">
                <div class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider text-indigo-400 leading-none">សិស្សឡើងពន្យល់ (EXPLAINER)</div>
                <div
                  class="font-black text-white leading-tight mt-0.5 whitespace-nowrap truncate text-sm sm:text-lg md:text-xl"
                  :title="currentExplainer"
                >
                  {{ currentExplainer || '--' }}
                </div>
              </div>
            </div>
          </div>

          <!-- Secret Word Display with Adaptive Typography -->
          <div class="py-3 sm:py-6 md:py-8 my-0.5 sm:my-1 flex-1 flex flex-col justify-center">
            <div class="text-xs sm:text-sm uppercase tracking-widest text-slate-400 font-extrabold mb-1.5 sm:mb-2.5 flex items-center justify-center gap-2">
              <span>ពាក្យសម្ងាត់ត្រូវទាយ (SECRET WORD)</span>
              <button
                type="button"
                class="text-slate-400 hover:text-white transition cursor-pointer"
                :title="isWordVisible ? 'បិទបាំងពាក្យ (Hide Word) [H]' : 'បង្ហាញពាក្យ (Peek Word) [H]'"
                @click="isWordVisible = !isWordVisible"
              >
                <span class="material-symbols-outlined text-base">{{ isWordVisible ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>

            <div>
              <h2
                class="font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-100 to-emerald-300 tracking-wide word-glow px-3 py-1 leading-tight break-words transition-all duration-200"
                :class="[
                  isWordVisible ? '' : 'blur-xl select-none',
                  currentWord.length > 20
                    ? 'text-3xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl'
                    : (currentWord.length > 12 ? 'text-4xl sm:text-6xl md:text-7xl lg:text-8xl xl:text-9xl' : 'text-5xl sm:text-7xl md:text-8xl lg:text-9xl xl:text-[9.5rem]')
                ]"
              >
                {{ currentWord || 'កុំព្យូទ័រ' }}
              </h2>
            </div>
          </div>

          <!-- Teacher Action Buttons -->
          <div class="max-w-2xl mx-auto pt-4 sm:pt-6 border-t border-slate-800/80 w-full">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
              
              <!-- Correct Button -->
              <button
                type="button"
                class="px-6 py-3.5 sm:py-4 rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-base sm:text-xl md:text-2xl shadow-xl shadow-emerald-600/35 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2.5 cursor-pointer"
                @click="handleTeacherDecision(true)"
              >
                <span class="material-symbols-outlined text-xl sm:text-2xl">check_circle</span>
                <span>ត្រូវ (Correct +1)</span>
              </button>

              <!-- Wrong Button -->
              <button
                type="button"
                class="px-6 py-3.5 sm:py-4 rounded-2xl bg-gradient-to-r from-rose-600 via-rose-500 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-black text-base sm:text-xl md:text-2xl shadow-xl shadow-rose-600/35 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2.5 cursor-pointer"
                @click="handleTeacherDecision(false)"
              >
                <span class="material-symbols-outlined text-xl sm:text-2xl">cancel</span>
                <span>ខុស (Wrong / Skip)</span>
              </button>
            </div>

            <!-- Hotkeys Hint -->
            <div class="mt-3 flex items-center justify-center gap-6 text-xs text-slate-400 font-semibold">
              <span class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">1</kbd> / <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">Enter</kbd> : <span class="text-emerald-400 font-bold">ត្រូវ</span>
              </span>
              <span class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">2</kbd> / <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">Space</kbd> : <span class="text-rose-400 font-bold">ខុស</span>
              </span>
            </div>

          </div>

        </div>

      </section>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- VIEW 4: ROUND_SUMMARY_VIEW (EXPANSIVE 2-COLUMN WIDESCREEN)      -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <section
        v-if="currentView === 'ROUND_SUMMARY_VIEW'"
        class="w-full max-w-5xl xl:max-w-6xl flex flex-col items-center justify-center my-auto px-2 transition-all"
      >
        <div class="w-full bg-gradient-to-b from-slate-900/95 via-slate-900/90 to-slate-950 border-2 border-indigo-500/40 rounded-3xl p-5 sm:p-7 md:p-8 shadow-2xl shadow-indigo-950/70 backdrop-blur-xl relative overflow-hidden">
          
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8 items-center">
            
            <!-- LEFT: TROPHY, FINAL SCORE, & ACTIONS -->
            <div class="lg:col-span-5 flex flex-col items-center text-center">
              
              <!-- Trophy Icon -->
              <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-gradient-to-tr from-amber-500 via-amber-400 to-yellow-300 text-slate-950 flex items-center justify-center text-3xl sm:text-4xl shadow-xl shadow-amber-500/35 mb-2 animate-bounce">
                <span class="material-symbols-outlined text-3xl sm:text-4xl">emoji_events</span>
              </div>

              <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-[11px] sm:text-xs font-bold uppercase tracking-wider mb-1">
                <span class="material-symbols-outlined text-xs">sports_score</span> បញ្ចប់ការទាយ ១ ជុំ (Round Completed)
              </div>

              <h2 class="text-xl sm:text-2xl lg:text-3xl font-black text-white mb-2.5">លទ្ធផលពិន្ទុសរុបក្នុងជុំនេះ</h2>

              <!-- Hero Score Box -->
              <div class="w-full bg-slate-950/85 border-2 border-amber-500/40 rounded-2xl p-3 sm:p-4 shadow-glow-amber mb-3">
                <div class="text-[10px] sm:text-[11px] uppercase tracking-widest text-slate-400 font-bold mb-0.5">ពិន្ទុសរុបទទួលបាន (FINAL SCORE)</div>
                <div class="text-5xl sm:text-6xl lg:text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-300 py-0.5 leading-tight word-glow">
                  {{ currentScore }} / {{ wordsPerRound }}
                </div>
                <div class="text-xs sm:text-sm font-extrabold mt-0.5" :class="scoreCommentClass">
                  {{ scoreCommentText }}
                </div>
              </div>

              <!-- Actions -->
              <div class="w-full flex flex-col sm:flex-row gap-2">
                <button
                  type="button"
                  class="flex-1 px-4 py-2.5 sm:py-3 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-black text-xs sm:text-sm shadow-lg shadow-indigo-600/30 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                  @click="playNextRound"
                >
                  <span class="material-symbols-outlined text-base">replay</span>
                  <span>លេងជុំបន្ទាប់ (Next Round)</span>
                </button>

                <button
                  type="button"
                  class="px-4 py-2.5 sm:py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 border-2 border-slate-700 text-slate-300 hover:text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-1.5 shadow cursor-pointer"
                  @click="switchView('SETUP_VIEW')"
                >
                  <span class="material-symbols-outlined text-base">tune</span>
                  <span>Setup</span>
                </button>
              </div>

            </div>

            <!-- RIGHT: DETAILED WORDS RECAP TABLE -->
            <div class="lg:col-span-7 flex flex-col bg-slate-950/70 border-2 border-slate-800/80 rounded-2xl p-3.5 sm:p-4 shadow-inner">
              
              <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                  <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-xs font-bold">
                    <span class="material-symbols-outlined text-sm">fact_check</span>
                  </span>
                  <span class="text-xs sm:text-sm uppercase tracking-wider text-slate-300 font-extrabold">ប្រវត្តិពាក្យក្នុងជុំនេះ (Words Recap)</span>
                </div>
                <span class="text-xs sm:text-sm font-black bg-emerald-950/80 text-emerald-300 border border-emerald-700/60 px-2.5 py-0.5 rounded-full">
                  {{ roundWordsHistory.filter(h => h.isCorrect).length }} ត្រូវ / {{ roundWordsHistory.filter(h => !h.isCorrect).length }} ខុស
                </span>
              </div>

              <!-- List of played words -->
              <div class="space-y-2 max-h-[250px] sm:max-h-[300px] overflow-y-auto pr-1">
                <div
                  v-for="(item, idx) in roundWordsHistory"
                  :key="idx"
                  class="flex items-center justify-between p-2 sm:p-2.5 rounded-xl border text-xs sm:text-sm font-semibold transition-all shadow-sm"
                  :class="item.isCorrect ? 'bg-emerald-950/40 border-emerald-700/60 text-emerald-200' : 'bg-rose-950/40 border-rose-700/60 text-rose-200'"
                >
                  <div class="flex items-center gap-2 sm:gap-2.5">
                    <span
                      class="w-6 h-6 rounded-lg flex items-center justify-center font-bold shrink-0"
                      :class="item.isCorrect ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'"
                    >
                      <span class="material-symbols-outlined text-xs sm:text-sm">{{ item.isCorrect ? 'check' : 'close' }}</span>
                    </span>
                    <span class="font-extrabold text-white text-xs sm:text-sm">{{ item.word }}</span>
                  </div>

                  <span class="text-[11px] sm:text-xs text-slate-300 font-semibold bg-slate-900/80 px-2 py-0.5 rounded-lg border border-slate-700/60 whitespace-nowrap ml-2">
                    ពន្យល់ដោយ: <strong class="text-indigo-300 font-bold">{{ item.explainer }}</strong>
                  </span>
                </div>
              </div>

            </div>

          </div>

        </div>
      </section>

    </main>

    <!-- ── 3. SUPER SLIM FOOTER ────────────────────────────────────── -->
    <footer class="w-full text-center py-1 text-[10px] sm:text-[11px] text-slate-500 border-t border-slate-900/80 bg-slate-950/70 shrink-0">
      <span>ទាយពាក្យ - កងវិលសំណាង (Lucky Wheel Guessing Game for Classrooms) • OnlineXam System</span>
    </footer>

    <!-- ── 4. MOBILE REMOTE CONTROLLER PAIRING MODAL ────────────────── -->
    <div
      v-if="showRemoteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/85 backdrop-blur-md transition-all"
      @click.self="showRemoteModal = false"
    >
      <div class="w-full max-w-lg bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 border-2 border-indigo-500/50 rounded-3xl p-5 sm:p-6 shadow-2xl shadow-indigo-950/80 relative text-left">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-xl">
              <span class="material-symbols-outlined">smartphone</span>
            </div>
            <div>
              <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
                <span>តេលេបញ្ជាតាមទូរស័ព្ទ</span>
                <span class="text-[10px] uppercase font-bold text-indigo-400 bg-indigo-950/80 px-1.5 py-0.5 rounded border border-indigo-800">Mobile Remote</span>
              </h3>
              <p class="text-[11px] sm:text-xs text-slate-400">ប្រើទូរស័ព្ទដៃដូចតេលេបញ្ជាដើម្បីបង្វិលកង និងដាក់ពិន្ទុ</p>
            </div>
          </div>

          <button
            type="button"
            class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center cursor-pointer transition"
            @click="showRemoteModal = false"
          >
            <span class="material-symbols-outlined text-lg">close</span>
          </button>
        </div>

        <!-- Connection Status Banner -->
        <div class="mt-4">
          <div
            v-if="isRemoteConnected"
            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl bg-emerald-950/60 border border-emerald-500/50 text-emerald-300 shadow-sm"
          >
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="material-symbols-outlined text-lg text-emerald-400">check_circle</span>
            <div class="text-xs font-bold">
              ទូរស័ព្ទបានភ្ជាប់ជោគជ័យ! <span class="text-emerald-200 font-normal">អ្នកអាចចាប់ផ្តើមបញ្ជាបានហើយ។</span>
            </div>
          </div>

          <div
            v-else
            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl bg-indigo-950/60 border border-indigo-500/40 text-indigo-300 shadow-sm"
          >
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
            <span class="material-symbols-outlined text-lg text-amber-400">phonelink_ring</span>
            <div class="text-xs font-bold">
              កំពុងរង់ចាំទូរស័ព្ទភ្ជាប់... <span class="text-slate-400 font-normal">សូមស្កេន QR Code ឬវាយលេខកូដខាងក្រោម</span>
            </div>
          </div>
        </div>

        <!-- Pairing Card: PIN + QR -->
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-center bg-slate-950/80 border border-slate-800 rounded-2xl p-4">
          
          <!-- Left: 4-digit PIN -->
          <div class="sm:col-span-7 flex flex-col items-center text-center">
            <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 mb-1">លេខកូដភ្ជាប់ (ROOM PIN)</span>
            
            <div class="flex items-center gap-2 my-1">
              <div
                v-for="(digit, idx) in remotePinDigits"
                :key="idx"
                class="w-11 h-13 sm:w-12 sm:h-14 rounded-xl bg-slate-900 border-2 border-indigo-500/60 flex items-center justify-center text-2xl sm:text-3xl font-black font-mono text-amber-300 shadow-md shadow-indigo-950"
              >
                {{ digit }}
              </div>
            </div>

            <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1">វាយលេខ ៤ ខ្ទង់នេះនៅលើទូរស័ព្ទរបស់អ្នក</p>

            <button
              type="button"
              class="mt-2.5 text-[11px] text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 cursor-pointer transition"
              @click="generateNewPin"
            >
              <span class="material-symbols-outlined text-xs">refresh</span>
              <span>បង្កើតលេខកូដថ្មី (New PIN)</span>
            </button>
          </div>

          <!-- Right: QR Code -->
          <div class="sm:col-span-5 flex flex-col items-center text-center border-t sm:border-t-0 sm:border-l border-slate-800 pt-3 sm:pt-0 sm:pl-3">
            <div class="p-2 bg-white rounded-xl shadow-md">
              <img
                v-if="qrCodeDataUrl"
                :src="qrCodeDataUrl"
                alt="QR Code for Mobile Remote"
                class="w-28 h-28 sm:w-32 sm:h-32 object-contain block"
              />
              <div v-else class="w-28 h-28 sm:w-32 sm:h-32 flex flex-col items-center justify-center text-slate-800 text-xs font-bold gap-1">
                <span class="material-symbols-outlined text-xl animate-spin text-indigo-600">sync</span>
                <span class="text-[10px]">បង្កើត QR...</span>
              </div>
            </div>
            <span class="text-[10px] text-slate-400 font-bold mt-1.5">ស្កេនដើម្បីបើកភ្លាមៗ</span>
          </div>

        </div>

        <!-- Direct Link & Copy -->
        <div class="mt-3 flex items-center gap-2 bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5">
          <span class="material-symbols-outlined text-xs sm:text-sm text-slate-500 shrink-0">link</span>
          <span class="text-[11px] text-slate-300 font-mono truncate flex-1">{{ remoteFullUrl }}</span>
          <button
            type="button"
            class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[10px] sm:text-xs flex items-center gap-1 cursor-pointer transition shrink-0"
            @click="copyRemoteLink"
          >
            <span class="material-symbols-outlined text-xs">{{ copiedLink ? 'check' : 'content_copy' }}</span>
            <span>{{ copiedLink ? 'បានចម្លង!' : 'ចម្លង Link' }}</span>
          </button>
        </div>

        <!-- 3 Simple Steps -->
        <div class="mt-3 p-2.5 rounded-xl bg-slate-950/40 border border-slate-800/80 text-[11px] text-slate-400 space-y-1">
          <div class="flex items-center gap-1.5 text-slate-300 font-bold">
            <span class="material-symbols-outlined text-xs text-emerald-400">tips_and_updates</span>
            <span>របៀបប្រើប្រាស់តេលេបញ្ជា៖</span>
          </div>
          <div class="pl-4 space-y-0.5 text-[10px] sm:text-[11px]">
            <div>• <strong>ជំហានទី ១:</strong> ស្កេន QR Code ឬបើក Link ខាងលើតាមទូរស័ព្ទ</div>
            <div>• <strong>ជំហានទី ២:</strong> ប្រព័ន្ធនឹងភ្ជាប់ដោយស្វ័យប្រវត្តិតាមរយៈលេខកូដ PIN</div>
            <div>• <strong>ជំហានទី ៣:</strong> គ្រូអាចដើរក្នុងថ្នាក់ និងចុចបង្វិលកង, បង្ហាញពាក្យ, និងកាត់សេចក្តី ត្រូវ/ខុស ដោយសេរី</div>
          </div>
        </div>

        <!-- Footer Action -->
        <div class="mt-4 flex justify-end">
          <button
            type="button"
            class="w-full sm:w-auto px-6 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-500 hover:from-indigo-500 hover:to-emerald-400 text-white font-bold text-xs sm:text-sm shadow-md cursor-pointer transition"
            @click="showRemoteModal = false"
          >
            យល់ព្រម & ចាប់ផ្តើម
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import confetti from 'canvas-confetti'
import QRCode from 'qrcode'

const router = useRouter()
const gameRootRef = ref(null)
const wheelCanvasRef = ref(null)
const wheelPointerRef = ref(null)

/* ── Default Demo Lists ── */
const DEFAULT_STUDENTS = [
  "សុខ ចាន់ដារ៉ា (Dara)",
  "កែវ ពិសិដ្ឋ (Piseth)",
  "មាស ធីតា (Thida)",
  "ចាន់ បុប្ផា (Bopha)",
  "សេង សុកន្ឋ (Sokun)",
  "រស់ សុជាតិ (Socheat)",
  "ឃឹម សៀក (Seak)",
  "ព្រំ សម្បត្តិ (Sambath)",
  "អ៊ុំ វិចិត្រ (Vichetr)",
  "គង់ វណ្ណៈ (Vannak)",
  "តាំង គឹមឡេង (Kimleng)",
  "លីម សុខហេង (Sokheng)"
]

const DEFAULT_WORDS = [
  "កុំព្យូទ័រ (Computer)",
  "អ៊ីនធឺណិត (Internet)",
  "ក្តារចុច (Keyboard)",
  "កណ្ដុរ (Mouse)",
  "ម៉ាស៊ីនបោះពុម្ព (Printer)",
  "ទូរស័ព្ទ (Phone)",
  "កម្មវិធីរុករក (Web Browser)",
  "កូដកម្មវិធី (Source Code)",
  "ទិន្នន័យ (Database)",
  "អេក្រង់ (Monitor)",
  "បណ្តាញសង្គម (Social Media)",
  "សន្តិសុខឌីជីថល (Cybersecurity)"
]

const WHEEL_COLORS = [
  '#4F46E5', '#059669', '#D97706', '#DC2626', '#7C3AED',
  '#0891B2', '#DB2777', '#2563EB', '#16A34A', '#CA8A04',
  '#9333EA', '#0D9488', '#E11D48', '#4338CA', '#047857'
]

/* ── Reactive Game State ── */
const currentView = ref('SETUP_VIEW')
const rawStudentsText = ref(DEFAULT_STUDENTS.join('\n'))
const rawWordsText = ref(DEFAULT_WORDS.join('\n'))
const wordsPerRound = ref(5)
const currentWordIndex = ref(0)
const currentScore = ref(0)
const currentExplainer = ref('')
const currentWord = ref('')
const isWordVisible = ref(true)
const roundWordsHistory = ref([])
const isSpinning = ref(false)
const isMuted = ref(false)
const isFullscreen = ref(false)
const isLoadingOnlineStudents = ref(false)
const rawOnlineStudents = ref([])
const selectedFilterId = ref('')
const currentLoadedSkillLabel = ref('')

/* ── Mobile Remote Controller State ── */
function generateRandomPin() {
  return String(Math.floor(1000 + Math.random() * 9000))
}

const initialPin = generateRandomPin()
const remoteRoom = ref(initialPin)
const remotePin = ref(initialPin)
const isRemoteConnected = ref(false)
const showRemoteModal = ref(false)
const copiedLink = ref(false)
const qrCodeDataUrl = ref('')
let remotePollInterval = null
let lastProcessedCmdId = ''

const remotePinDigits = computed(() => {
  const p = remotePin.value || initialPin
  return p.split('')
})

const remoteFullUrl = computed(() => {
  if (typeof window === 'undefined') return ''
  const origin = window.location.origin
  return `${origin}/wheel-remote?pin=${remotePin.value}`
})

const filterOptions = computed(() => {
  const list = []
  const skillMap = {}
  for (const s of rawOnlineStudents.value) {
    const sk = s.skill ? s.skill.trim() : 'គ្មានជំនាញ (Unassigned)'
    if (!skillMap[sk]) {
      skillMap[sk] = {
        name: sk,
        students: [],
        groupMap: {}
      }
    }
    skillMap[sk].students.push(s)
    if (s.group && s.group.trim()) {
      const grp = s.group.trim()
      skillMap[sk].groupMap[grp] = (skillMap[sk].groupMap[grp] || 0) + 1
    }
  }

  const sortedSkills = Object.keys(skillMap).sort((a, b) => a.localeCompare(b))
  for (const sk of sortedSkills) {
    const data = skillMap[sk]
    const groups = Object.keys(data.groupMap)

    if (groups.length <= 1) {
      list.push({
        id: `skill:${sk}`,
        label: sk,
        skill: sk,
        group: null,
        count: data.students.length
      })
    } else {
      list.push({
        id: `skill:${sk}`,
        label: `${sk} (ទាំងអស់)`,
        skill: sk,
        group: null,
        count: data.students.length
      })
      for (const grp of groups.sort()) {
        list.push({
          id: `group:${sk}|${grp}`,
          label: `↳ ${sk} - ${grp}`,
          skill: sk,
          group: grp,
          count: data.groupMap[grp]
        })
      }
    }
  }

  return list
})

/* Timer State */
const timerMaxSeconds = 60
const timerSeconds = ref(60)
const isTimerPaused = ref(false)
let timerInterval = null

/* ── Parsed Lists ── */
const parsedStudents = computed(() => {
  return rawStudentsText.value
    .split('\n')
    .map(s => s.trim())
    .filter(s => s.length > 0)
})

const parsedWords = computed(() => {
  return rawWordsText.value
    .split('\n')
    .map(w => w.trim())
    .filter(w => w.length > 0)
})

/* ── Sound Synthesizer (Pure Web Audio API) ── */
let audioCtx = null

function initAudio() {
  if (!audioCtx && typeof window !== 'undefined') {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext
    if (AudioContextClass) {
      audioCtx = new AudioContextClass()
    }
  }
  if (audioCtx && audioCtx.state === 'suspended') {
    audioCtx.resume()
  }
}

function playTickSound() {
  if (isMuted.value) return
  initAudio()
  if (!audioCtx) return
  try {
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'triangle'
    osc.frequency.setValueAtTime(450, audioCtx.currentTime)
    osc.frequency.exponentialRampToValueAtTime(120, audioCtx.currentTime + 0.04)
    gain.gain.setValueAtTime(0.18, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.04)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 0.04)
  } catch (e) {}
}

function playWinnerFanfare() {
  if (isMuted.value) return
  initAudio()
  if (!audioCtx) return
  try {
    const notes = [261.63, 329.63, 392.00, 523.25]
    notes.forEach((freq, i) => {
      const osc = audioCtx.createOscillator()
      const gain = audioCtx.createGain()
      osc.type = 'sine'
      osc.frequency.setValueAtTime(freq, audioCtx.currentTime + i * 0.1)
      gain.gain.setValueAtTime(0.2, audioCtx.currentTime + i * 0.1)
      gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + i * 0.1 + 0.35)
      osc.connect(gain)
      gain.connect(audioCtx.destination)
      osc.start(audioCtx.currentTime + i * 0.1)
      osc.stop(audioCtx.currentTime + i * 0.1 + 0.35)
    })
  } catch (e) {}
}

function playCorrectSound() {
  if (isMuted.value) return
  initAudio()
  if (!audioCtx) return
  try {
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(523.25, audioCtx.currentTime)
    osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.25)
    gain.gain.setValueAtTime(0.25, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.25)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 0.25)
  } catch (e) {}
}

function playWrongSound() {
  if (isMuted.value) return
  initAudio()
  if (!audioCtx) return
  try {
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'sawtooth'
    osc.frequency.setValueAtTime(180, audioCtx.currentTime)
    osc.frequency.exponentialRampToValueAtTime(100, audioCtx.currentTime + 0.3)
    gain.gain.setValueAtTime(0.2, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 0.3)
  } catch (e) {}
}

/* ── Full-Screen Confetti Celebration ── */
function triggerFullScreenCelebration() {
  if (typeof confetti !== 'function') return

  // 1. Massive Center Explosion with enlarged particles (scalar: 1.5)
  confetti({
    particleCount: 110,
    spread: 120,
    origin: { x: 0.5, y: 0.5 },
    startVelocity: 50,
    scalar: 1.5,
    ticks: 260,
    colors: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#3b82f6', '#8b5cf6', '#ffffff', '#eab308']
  })

  // 2. Left side cannon
  confetti({
    particleCount: 75,
    angle: 60,
    spread: 85,
    origin: { x: 0.02, y: 0.82 },
    startVelocity: 60,
    scalar: 1.4,
    ticks: 260,
    colors: ['#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#a855f7', '#fbbf24']
  })

  // 3. Right side cannon
  confetti({
    particleCount: 75,
    angle: 120,
    spread: 85,
    origin: { x: 0.98, y: 0.82 },
    startVelocity: 60,
    scalar: 1.4,
    ticks: 260,
    colors: ['#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#a855f7', '#fbbf24']
  })

  // 4. Delayed top shower raining down across the entire screen
  setTimeout(() => {
    confetti({
      particleCount: 90,
      spread: 170,
      origin: { x: 0.5, y: 0.15 },
      startVelocity: 35,
      scalar: 1.4,
      ticks: 300,
      colors: ['#fbbf24', '#34d399', '#818cf8', '#f472b6', '#38bdf8', '#ffffff']
    })
  }, 220)
}

/* ── Lucky Wheel Engine ── */
let wheelAnimationId = null
let wheelCurrentAngle = 0
let wheelSpinVelocity = 0
let wheelFriction = 0.985
let lastTickSegment = -1

function resizeAndDrawWheel() {
  const canvas = wheelCanvasRef.value
  if (!canvas) return
  const rect = canvas.getBoundingClientRect()
  const dpr = window.devicePixelRatio || 1
  canvas.width = rect.width * dpr
  canvas.height = rect.height * dpr
  drawWheel()
}

function drawWheel() {
  const canvas = wheelCanvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const width = canvas.width
  const height = canvas.height
  const centerX = width / 2
  const centerY = height / 2
  const radius = Math.min(centerX, centerY) - 8

  ctx.clearRect(0, 0, width, height)

  const items = parsedStudents.value
  const total = items.length
  if (total === 0) return

  const arc = (2 * Math.PI) / total

  ctx.save()
  ctx.translate(centerX, centerY)
  ctx.rotate(wheelCurrentAngle)

  for (let i = 0; i < total; i++) {
    const angle = i * arc

    // Draw Slice
    ctx.beginPath()
    ctx.fillStyle = WHEEL_COLORS[i % WHEEL_COLORS.length]
    ctx.moveTo(0, 0)
    ctx.arc(0, 0, radius, angle, angle + arc)
    ctx.lineTo(0, 0)
    ctx.fill()

    // Slice Separator Line
    ctx.beginPath()
    ctx.strokeStyle = '#ffffff'
    ctx.lineWidth = Math.max(2, width * 0.005)
    ctx.moveTo(0, 0)
    ctx.arc(0, 0, radius, angle, angle)
    ctx.stroke()

    // Draw Student Label
    ctx.save()
    ctx.rotate(angle + arc / 2)
    ctx.textAlign = 'right'
    ctx.fillStyle = '#ffffff'
    ctx.shadowColor = 'rgba(0, 0, 0, 0.7)'
    ctx.shadowBlur = 4

    let fontSize = Math.floor(radius * 0.075)
    if (total > 20) fontSize = Math.floor(radius * 0.05)
    if (total > 30) fontSize = Math.floor(radius * 0.04)
    ctx.font = `bold ${fontSize}px "Kantumruy Pro", "Outfit", sans-serif`

    let text = items[i]
    if (text.length > 18) text = text.substring(0, 16) + '...'

    ctx.fillText(text, radius - 20, fontSize * 0.35)
    ctx.restore()
  }

  // Outer Rim Shadow Ring
  ctx.beginPath()
  ctx.arc(0, 0, radius, 0, 2 * Math.PI)
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.35)'
  ctx.lineWidth = 4
  ctx.stroke()

  ctx.restore()
}

function getCurrentWinnerIndex() {
  const total = parsedStudents.value.length
  if (total === 0) return 0
  const arc = (2 * Math.PI) / total
  const pointerAngle = 1.5 * Math.PI
  let relativeAngle = (pointerAngle - wheelCurrentAngle) % (2 * Math.PI)
  if (relativeAngle < 0) relativeAngle += 2 * Math.PI
  return Math.floor(relativeAngle / arc) % total
}

function triggerSpin() {
  if (isSpinning.value || parsedStudents.value.length === 0) return
  isSpinning.value = true
  initAudio()
  syncRemoteState()

  const randomSpins = 5 + Math.random() * 3
  wheelSpinVelocity = randomSpins * 0.08 + Math.random() * 0.04
  wheelFriction = 0.984 + Math.random() * 0.004

  animateSpinLoop()
}

function animateSpinLoop() {
  wheelCurrentAngle += wheelSpinVelocity
  wheelSpinVelocity *= wheelFriction

  // Needle twitch & Audio tick
  const curIdx = getCurrentWinnerIndex()
  if (curIdx !== lastTickSegment) {
    lastTickSegment = curIdx
    playTickSound()
    if (wheelPointerRef.value) {
      wheelPointerRef.value.style.transform = 'translateX(-50%) rotate(-12deg)'
      setTimeout(() => {
        if (wheelPointerRef.value) {
          wheelPointerRef.value.style.transform = 'translateX(-50%) rotate(0deg)'
        }
      }, 45)
    }
  }

  drawWheel()

  if (wheelSpinVelocity < 0.002) {
    wheelSpinVelocity = 0
    isSpinning.value = false
    onSpinComplete()
    return
  }

  wheelAnimationId = requestAnimationFrame(animateSpinLoop)
}

function onSpinComplete() {
  const winnerIndex = getCurrentWinnerIndex()
  const winnerName = parsedStudents.value[winnerIndex] || 'សិស្សគ្មានឈ្មោះ'
  currentExplainer.value = winnerName

  playWinnerFanfare()
  triggerFullScreenCelebration()
  syncRemoteState()
}

function handleWheelClick() {
  if (!isSpinning.value) {
    triggerSpin()
  }
}

/* ── View Transitions ── */
function switchView(viewName) {
  currentView.value = viewName
  if (viewName === 'WHEEL_VIEW') {
    nextTick(() => {
      resizeAndDrawWheel()
    })
  } else if (viewName === 'GUESSING_VIEW') {
    startTimer()
  } else {
    stopTimer()
  }
  syncRemoteState()
}

function startSession() {
  if (parsedStudents.value.length < 2) {
    alert('សូមបញ្ចូលឈ្មោះសិស្សយ៉ាងតិច ២ នាក់ឡើងទៅ!')
    return
  }
  if (parsedWords.value.length === 0) {
    alert('សូមបញ្ចូលបញ្ជីពាក្យត្រូវទាយ!')
    return
  }

  currentWordIndex.value = 0
  currentScore.value = 0
  roundWordsHistory.value = []
  currentExplainer.value = ''

  switchView('WHEEL_VIEW')
}

function startGuessingCurrentWord() {
  if (!currentExplainer.value) return

  // Pick random word from bank
  const pool = parsedWords.value
  const randomWord = pool[Math.floor(Math.random() * pool.length)]
  currentWord.value = randomWord
  isWordVisible.value = true

  switchView('GUESSING_VIEW')
}

/* ── Timer Controls ── */
function startTimer() {
  stopTimer()
  timerSeconds.value = timerMaxSeconds
  isTimerPaused.value = false
  syncRemoteState()

  timerInterval = setInterval(() => {
    if (!isTimerPaused.value) {
      timerSeconds.value--
      if (timerSeconds.value % 4 === 0) {
        syncRemoteState()
      }
      if (timerSeconds.value <= 0) {
        stopTimer()
        playWrongSound()
        syncRemoteState()
      }
    }
  }, 1000)
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

function toggleTimerPause() {
  isTimerPaused.value = !isTimerPaused.value
  syncRemoteState()
}

/* ── Teacher Actions ── */
function handleTeacherDecision(isCorrect) {
  stopTimer()

  roundWordsHistory.value.push({
    word: currentWord.value,
    explainer: currentExplainer.value,
    isCorrect: isCorrect
  })

  if (isCorrect) {
    playCorrectSound()
    currentScore.value += 1
  } else {
    playWrongSound()
  }

  currentWordIndex.value++

  if (currentWordIndex.value >= wordsPerRound.value) {
    setTimeout(() => {
      switchView('ROUND_SUMMARY_VIEW')
      if (currentScore.value === wordsPerRound.value) {
        triggerFullScreenCelebration()
      }
    }, 300)
  } else {
    currentExplainer.value = ''
    setTimeout(() => {
      switchView('WHEEL_VIEW')
    }, 300)
  }
  syncRemoteState()
}

function playNextRound() {
  currentWordIndex.value = 0
  currentScore.value = 0
  roundWordsHistory.value = []
  currentExplainer.value = ''
  switchView('WHEEL_VIEW')
}

/* ── Mobile Remote Controller Remote Polling & Execution ── */
async function refreshQrCode() {
  if (!remoteFullUrl.value) return
  try {
    qrCodeDataUrl.value = await QRCode.toDataURL(remoteFullUrl.value, {
      width: 240,
      margin: 1,
      errorCorrectionLevel: 'M',
      color: {
        dark: '#0f172a',
        light: '#ffffff'
      }
    })
  } catch (err) {
    qrCodeDataUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(remoteFullUrl.value)}`
  }
}

function openRemoteModal() {
  showRemoteModal.value = true
  refreshQrCode()
}

async function generateNewPin() {
  const freshPin = generateRandomPin()
  remotePin.value = freshPin
  remoteRoom.value = freshPin
  await refreshQrCode()
  await initRemoteRoom(true)
}

async function initRemoteRoom(forceWithCurrentPin = false) {
  if (!remotePin.value) {
    const freshPin = generateRandomPin()
    remotePin.value = freshPin
    remoteRoom.value = freshPin
  }

  await refreshQrCode()

  try {
    const res = await axios.post('/api/lucky-wheel/remote/room', {
      room: remotePin.value
    })
    if (res.data?.success && res.data.room) {
      remoteRoom.value = res.data.room
      remotePin.value = res.data.room
      await refreshQrCode()
    }
  } catch (err) {
    console.warn('initRemoteRoom notice (fallback to current PIN):', err)
  } finally {
    await refreshQrCode()
    syncRemoteState()
    startRemotePolling()
  }
}

function startRemotePolling() {
  if (remotePollInterval) clearInterval(remotePollInterval)
  remotePollInterval = setInterval(async () => {
    if (!remotePin.value) return
    try {
      const res = await axios.get('/api/lucky-wheel/remote/poll', {
        params: {
          room: remotePin.value,
          last_cmd_id: lastProcessedCmdId || undefined
        }
      })

      if (res.data?.success) {
        isRemoteConnected.value = !!res.data.phoneActive

        if (res.data.hasNewCommand && res.data.command) {
          const cmd = res.data.command
          lastProcessedCmdId = cmd.id
          handleIncomingRemoteCommand(cmd.action, cmd.payload)
        }
      }
    } catch (e) {
      // silent poll catch
    }
  }, 600)
}

function handleIncomingRemoteCommand(action, payload) {
  switch (action) {
    case 'SPIN':
      if (currentView.value === 'WHEEL_VIEW') {
        if (!isSpinning.value) triggerSpin()
      } else if (currentView.value === 'SETUP_VIEW') {
        startSession()
        nextTick(() => {
          if (!isSpinning.value) triggerSpin()
        })
      }
      break

    case 'START_GUESSING':
      if (currentView.value === 'WHEEL_VIEW' && currentExplainer.value && !isSpinning.value) {
        startGuessingCurrentWord()
      }
      break

    case 'DECISION_CORRECT':
      if (currentView.value === 'GUESSING_VIEW') {
        handleTeacherDecision(true)
      }
      break

    case 'DECISION_WRONG':
      if (currentView.value === 'GUESSING_VIEW') {
        handleTeacherDecision(false)
      }
      break

    case 'TOGGLE_TIMER':
      if (currentView.value === 'GUESSING_VIEW') {
        toggleTimerPause()
      }
      break

    case 'TOGGLE_WORD_VISIBILITY':
      if (currentView.value === 'GUESSING_VIEW') {
        isWordVisible.value = !isWordVisible.value
        syncRemoteState()
      }
      break

    case 'START_SESSION':
      if (currentView.value === 'SETUP_VIEW') {
        startSession()
      }
      break

    case 'NEW_ROUND':
      if (currentView.value === 'ROUND_SUMMARY_VIEW') {
        playNextRound()
      }
      break
  }
}

function syncRemoteState() {
  if (!remotePin.value) return
  const state = {
    view: currentView.value,
    currentWord: currentWord.value,
    currentExplainer: currentExplainer.value,
    currentScore: currentScore.value,
    wordsPerRound: wordsPerRound.value,
    currentWordIndex: currentWordIndex.value,
    timerSeconds: timerSeconds.value,
    timerMaxSeconds: timerMaxSeconds,
    isTimerPaused: isTimerPaused.value,
    isSpinning: isSpinning.value,
    isWordVisible: isWordVisible.value,
  }

  axios.post('/api/lucky-wheel/remote/sync', {
    room: remotePin.value,
    state: state
  }).catch(() => {})
}

function copyRemoteLink() {
  if (typeof navigator !== 'undefined' && navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(remoteFullUrl.value)
    copiedLink.value = true
    setTimeout(() => {
      copiedLink.value = false
    }, 2500)
  }
}

/* ── Score Compliment ── */
const scoreCommentText = computed(() => {
  const ratio = currentScore.value / (wordsPerRound.value || 1)
  if (ratio === 1) return '🏆 អស្ចារ្យឥតខ្ចោះ! ឆ្លើយត្រូវ ១០០% ទាំងអស់!'
  if (ratio >= 0.7) return '🌟 ពូកែណាស់! ឆ្លើយត្រូវស្ទើរតែទាំងអស់!'
  if (ratio >= 0.5) return '👍 ល្អគួរសម! ខិតខំប្រឹងប្រែងបន្តទៀត!'
  return '💪 ព្យាយាមម្តងទៀតនៅជុំក្រោយណា៎!'
})

const scoreCommentClass = computed(() => {
  const ratio = currentScore.value / (wordsPerRound.value || 1)
  if (ratio === 1) return 'text-amber-300'
  if (ratio >= 0.7) return 'text-emerald-400'
  if (ratio >= 0.5) return 'text-indigo-400'
  return 'text-slate-400'
})

/* ── OnlineXam Integrations (Filter by Skill & Group) ── */
async function fetchOnlineStudentsSilently() {
  if (rawOnlineStudents.value.length > 0) return
  isLoadingOnlineStudents.value = true
  try {
    const res = await axios.get('/api/admin/students')
    rawOnlineStudents.value = res.data.students || []
  } catch (err) {
    console.error('Failed to load students from OnlineXam API:', err)
  } finally {
    isLoadingOnlineStudents.value = false
  }
}

async function handleDownloadClick() {
  if (rawOnlineStudents.value.length === 0) {
    await fetchOnlineStudentsSilently()
  }

  if (!selectedFilterId.value) {
    alert('សូមជ្រើសរើសជំនាញជាមុនសិន! (Please select a skill first)')
    return
  }

  applySelectedFilter()
}

function onFilterChange() {
  applySelectedFilter()
}

function applySelectedFilter() {
  if (!rawOnlineStudents.value.length) return
  if (!selectedFilterId.value) return

  let filtered = rawOnlineStudents.value
  let label = ''

  if (selectedFilterId.value === '__ALL__') {
    filtered = rawOnlineStudents.value
    label = 'សិស្សទាំងអស់'
  } else if (selectedFilterId.value.startsWith('group:')) {
    const parts = selectedFilterId.value.replace('group:', '').split('|')
    const skillName = parts[0]
    const groupName = parts[1]
    filtered = filtered.filter(s => {
      const sk = s.skill ? s.skill.trim() : 'គ្មានជំនាញ (Unassigned)'
      return sk === skillName && s.group && s.group.trim() === groupName
    })
    label = `${skillName} - ${groupName}`
  } else if (selectedFilterId.value.startsWith('skill:')) {
    const skillName = selectedFilterId.value.replace('skill:', '')
    filtered = filtered.filter(s => {
      const sk = s.skill ? s.skill.trim() : 'គ្មានជំនាញ (Unassigned)'
      return sk === skillName
    })
    label = skillName
  }

  if (filtered.length === 0) {
    alert('មិនមានសិស្សនៅក្នុងជំនាញ/ក្រុមដែលបានជ្រើសរើសទេ។')
    return
  }

  const studentNames = filtered.map(s => {
    return s.studentCode ? `${s.name} (${s.studentCode})` : s.name
  })

  rawStudentsText.value = studentNames.join('\n')
  currentLoadedSkillLabel.value = `${label} (${filtered.length} នាក់)`
}

function loadDemoStudents() {
  rawStudentsText.value = DEFAULT_STUDENTS.join('\n')
  selectedFilterId.value = ''
  currentLoadedSkillLabel.value = 'ឈ្មោះគំរូ (Demo)'
}

function loadDemoWords() {
  rawWordsText.value = DEFAULT_WORDS.join('\n')
}

function goBackToDashboard() {
  stopTimer()
  if (wheelAnimationId) {
    cancelAnimationFrame(wheelAnimationId)
    wheelAnimationId = null
  }
  if (document.fullscreenElement && document.exitFullscreen) {
    document.exitFullscreen().catch(() => {})
  }
  router.push('/admin/dashboard').catch(() => {})
}

function toggleSound() {
  isMuted.value = !isMuted.value
}

function toggleFullscreen() {
  const el = gameRootRef.value || document.documentElement
  if (!document.fullscreenElement) {
    if (el.requestFullscreen) {
      el.requestFullscreen().catch(() => {})
    }
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {})
    }
    isFullscreen.value = false
  }
}

/* ── Global Key Listener ── */
function onGlobalKeyDown(e) {
  // Ignore if user is typing in textarea or input
  if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return

  if (currentView.value === 'WHEEL_VIEW') {
    if (e.code === 'Space') {
      e.preventDefault()
      triggerSpin()
    }
  } else if (currentView.value === 'GUESSING_VIEW') {
    if (e.key === '1' || e.key === 'Enter') {
      e.preventDefault()
      handleTeacherDecision(true)
    } else if (e.key === '2' || e.code === 'Space') {
      e.preventDefault()
      handleTeacherDecision(false)
    } else if (e.key.toLowerCase() === 'h') {
      e.preventDefault()
      isWordVisible.value = !isWordVisible.value
    }
  }
}

function onWindowResize() {
  if (currentView.value === 'WHEEL_VIEW') {
    resizeAndDrawWheel()
  }
}

onMounted(() => {
  fetchOnlineStudentsSilently()
  initRemoteRoom()
  window.addEventListener('keydown', onGlobalKeyDown)
  window.addEventListener('resize', onWindowResize)
  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = !!document.fullscreenElement
    nextTick(() => {
      if (currentView.value === 'WHEEL_VIEW') resizeAndDrawWheel()
    })
  })
})

onUnmounted(() => {
  stopTimer()
  if (wheelAnimationId) cancelAnimationFrame(wheelAnimationId)
  if (remotePollInterval) clearInterval(remotePollInterval)
  window.removeEventListener('keydown', onGlobalKeyDown)
  window.removeEventListener('resize', onWindowResize)
})
</script>

<style scoped>
.font-khmer {
  font-family: 'Kantumruy Pro', 'Outfit', system-ui, sans-serif;
}

.bg-mesh {
  background-image: 
    radial-gradient(at 10% 20%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
    radial-gradient(at 90% 80%, rgba(16, 185, 129, 0.12) 0px, transparent 50%),
    radial-gradient(at 50% 50%, rgba(244, 63, 94, 0.08) 0px, transparent 55%);
}

.word-glow {
  text-shadow: 0 0 25px rgba(99, 102, 241, 0.45);
}

.shadow-glow-indigo {
  box-shadow: 0 0 35px -5px rgba(99, 102, 241, 0.35);
}

.shadow-glow-amber {
  box-shadow: 0 0 35px -5px rgba(245, 158, 11, 0.35);
}

.needle-bounce {
  transform-origin: top center;
}
</style>
