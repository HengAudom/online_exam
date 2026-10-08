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
        <a
          href="/admin/dashboard"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white text-xs sm:text-sm font-bold border border-slate-700/80 transition-all cursor-pointer shadow-sm hover:scale-[1.02] active:scale-[0.98] no-underline"
          title="ត្រឡប់ទៅផ្ទាំងគ្រប់គ្រង (Back to Dashboard)"
          @click="goBackToDashboard"
        >
          <span class="material-symbols-outlined text-base sm:text-lg text-blue-400">arrow_back</span>
          <span>ត្រឡប់ទៅផ្ទាំងគ្រប់គ្រង</span>
        </a>

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
    <main
      class="flex-1 flex flex-col items-center px-2.5 sm:px-6 lg:px-8 py-1 sm:py-2 w-full max-w-[96vw] 2xl:max-w-[1680px] mx-auto relative min-h-0 overflow-hidden z-10"
      :class="currentView === 'GUESSING_VIEW' ? 'justify-start pt-1 sm:pt-2' : 'justify-center'"
    >
      
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- VIEW 1: SETUP_VIEW (COMPACT & PRECISE)                          -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <section
        v-if="currentView === 'SETUP_VIEW'"
        class="w-full max-w-6xl bg-slate-900/90 border border-slate-800 rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 shadow-2xl backdrop-blur-xl my-auto transition-all"
      >
        <!-- Header -->
        <div class="text-center max-w-2xl mx-auto mb-2.5 sm:mb-3">
          <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider mb-1">
            <span class="material-symbols-outlined text-xs">tune</span> រៀបចំបញ្ជីឈ្មោះ និងពាក្យទាយ (Setup Session)
          </div>
          <h2 class="text-base sm:text-xl font-extrabold text-white mb-0.5">ចាប់ផ្តើមល្បែងកងវិលទាយពាក្យក្នុងថ្នាក់រៀន</h2>
          <p class="text-[11px] sm:text-xs text-slate-400">បញ្ចូលឈ្មោះសិស្ស ពាក្យត្រូវទាយ និង Link រូបភាព (ជួរទី១ សម្រាប់ពាក្យទី១) ឬទុកទំនេរដើម្បីឲ្យប្រព័ន្ធទាញ Auto</p>
        </div>

        <!-- 3-Column Grid: Students, Words, and Image Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3.5 mb-2.5 sm:mb-3.5">
          
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
              class="w-full h-24 sm:h-32 bg-slate-900 border border-slate-700/80 rounded-lg p-2 text-xs sm:text-sm text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono resize-none leading-relaxed transition"
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
                <span class="text-slate-400 truncate max-w-[170px] sm:max-w-[200px]" v-if="currentLoadedSkillLabel">
                  បានទាញ: <span class="text-emerald-400 font-bold">{{ currentLoadedSkillLabel }}</span>
                </span>
                <span v-else class="text-slate-500 truncate">* ជ្រើសជំនាញទាញសិស្ស</span>

                <button
                  type="button"
                  class="text-indigo-400 hover:text-indigo-300 underline cursor-pointer shrink-0 ml-auto font-medium"
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
              class="w-full h-24 sm:h-32 bg-slate-900 border border-slate-700/80 rounded-lg p-2 text-xs sm:text-sm text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 font-mono resize-none leading-relaxed transition"
              placeholder="ពាក្យ ១ ក្នុង ១ ជួរ..."
            ></textarea>
            <div class="mt-2 space-y-1.5">
              <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-slate-400 bg-slate-900/60 border border-slate-800/80 rounded-lg px-2.5 py-1.5">
                <span class="flex items-center gap-1 text-emerald-400 font-medium">
                  <span class="material-symbols-outlined text-xs">auto_awesome</span>
                  <span>ទាញស្វ័យប្រវត្តិ (Auto-fetch)</span>
                </span>
                <button
                  type="button"
                  class="text-emerald-400 hover:text-emerald-300 underline cursor-pointer shrink-0 font-medium"
                  @click="loadDemoWords"
                >
                  ពាក្យគំរូ
                </button>
              </div>
              <div class="text-[10px] sm:text-[11px] text-slate-500 px-0.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[11px] text-emerald-500/70">format_list_numbered</span>
                <span>១ ជួរ = ១ ពាក្យ (ជួរទី១ ត្រូវនឹង Link ទី១)</span>
              </div>
            </div>
          </div>

          <!-- Column C: Image Links Bank (1 Link per line matching Words Bank) -->
          <div class="flex flex-col bg-slate-950/70 rounded-xl p-2.5 sm:p-3 border border-slate-800/80">
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs sm:text-sm font-bold text-sky-300 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-sky-400">add_photo_alternate</span> បញ្ជី Link រូបភាព (Image Links)
              </label>
              <span
                class="text-[10px] sm:text-[11px] px-2 py-0.5 rounded-md border font-semibold transition"
                :class="mappedLinksCount > 0 ? 'bg-sky-950 text-sky-300 border-sky-800' : 'bg-slate-800/90 text-slate-400 border-slate-700'"
              >
                {{ mappedLinksCount > 0 ? `${mappedLinksCount}/${parsedWords.length} បានភ្ជាប់` : 'ជម្រើសបន្ថែម (Auto)' }}
              </span>
            </div>
            <textarea
              v-model="rawImageLinksText"
              rows="4"
              class="w-full h-24 sm:h-32 bg-slate-900 border border-slate-700/80 rounded-lg p-2 text-xs sm:text-sm text-slate-200 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 font-mono resize-none leading-relaxed transition"
              placeholder="Link រូបភាព ១ ក្នុង ១ ជួរ (ជួរទី១ សម្រាប់ពាក្យទី១)&#10;https://... (រូបពាក្យទី១)&#10;https://... (រូបពាក្យទី២)&#10;(ទុកជួរទំនេរ ប្រសិនបើចង់ឲ្យប្រព័ន្ធទាញស្វ័យប្រវត្តិ)"
            ></textarea>
            <div class="mt-2 space-y-1.5">
              <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-slate-400 bg-slate-900/60 border border-slate-800/80 rounded-lg px-2.5 py-1.5">
                <span class="flex items-center gap-1 text-sky-400 font-medium truncate" title="ជួរទី១ សម្រាប់ពាក្យទី១ (ទុកទំនេរ = Auto)">
                  <span class="material-symbols-outlined text-xs">link</span>
                  <span>ជួរទី១ សម្រាប់ពាក្យទី១</span>
                </span>
                <div class="flex items-center gap-2 shrink-0">
                  <button
                    v-if="rawImageLinksText.trim()"
                    type="button"
                    class="text-rose-400 hover:text-rose-300 underline cursor-pointer text-[10px] sm:text-[11px]"
                    @click="rawImageLinksText = ''"
                  >
                    សម្អាត
                  </button>
                  <button
                    type="button"
                    class="text-sky-400 hover:text-sky-300 underline cursor-pointer font-medium text-[10px] sm:text-[11px]"
                    @click="loadDemoImageLinks"
                  >
                    Link គំរូ
                  </button>
                </div>
              </div>
              <div class="text-[10px] sm:text-[11px] text-slate-500 px-0.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[11px] text-sky-500/70">auto_fix_high</span>
                <span>ជួរណាទុកទំនេរ ប្រព័ន្ធនឹងទាញពី Internet ស្វ័យប្រវត្តិ</span>
              </div>
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

            <!-- Top Needle Indicator (Rock-Solid Centered & Perpendicular) -->
            <div
              ref="wheelPointerRef"
              class="absolute -top-3 sm:-top-4 left-1/2 -translate-x-1/2 z-20 drop-shadow-[0_4px_10px_rgba(0,0,0,0.8)] pointer-events-none"
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

          <!-- Question Retained Notice (After Time's Up) -->
          <div
            v-if="currentWord"
            class="w-full bg-gradient-to-r from-amber-950/70 via-slate-900/95 to-amber-950/70 border border-amber-500/40 rounded-2xl p-2 sm:p-2.5 px-3.5 flex items-center justify-between text-xs text-amber-200 shadow-md backdrop-blur-md"
          >
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-sm">lock_clock</span>
              </span>
              <div class="truncate">
                <span class="font-bold text-amber-300">រក្សាសំណួរមុនដដែល</span>
                <span class="text-[11px] text-slate-400 ml-1.5 hidden sm:inline">(សិស្សថ្មីនឹងបន្តពន្យល់សំណួរនេះ)</span>
              </div>
            </div>
            <button
              type="button"
              class="text-[10px] sm:text-[11px] text-slate-400 hover:text-amber-300 underline cursor-pointer shrink-0 ml-2"
              title="ប្ដូរពាក្យសម្ងាត់ថ្មីចៃដន្យផ្សេងទៀត"
              @click="currentWord = ''"
            >
              ប្ដូរពាក្យថ្មី
            </button>
          </div>

          <!-- Explainer Card -->
          <div class="bg-gradient-to-br from-indigo-950/95 via-slate-900/98 to-slate-950 border-2 border-indigo-500/60 rounded-3xl p-5 sm:p-6 shadow-glow-indigo text-center transition-all duration-300 min-h-[190px] flex flex-col justify-center">
            
            <!-- Notice: Waiting to Spin -->
            <div v-if="!isSpinning && !currentExplainer" class="py-2">
              <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-2xl mb-2 animate-pulse">
                <span class="material-symbols-outlined text-2xl">touch_app</span>
              </div>
              <div class="text-base sm:text-lg font-black text-slate-200 mb-0.5">
                {{ currentWord ? 'បង្វិលកងជ្រើសរើសសិស្សថ្មី' : 'ចុច SPIN ដើម្បីបង្វិលកង' }}
              </div>
              <p class="text-xs text-slate-400">
                {{ currentWord ? 'ដើម្បីឡើងមកបន្តពន្យល់សំណួរមុន (រក្សាពាក្យដដែល)' : 'ប្រព័ន្ធនឹងចៃដន្យជ្រើសរើសសិស្សឡើងពន្យល់ពាក្យ' }}
              </p>
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
              @click="handleRespin"
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
        class="w-full max-w-6xl xl:max-w-7xl 2xl:max-w-[1550px] flex flex-col items-center justify-start mt-0 sm:mt-1 mb-auto px-2 sm:px-4 transition-all"
      >
        <!-- Top Meta Bar: Progress, Timer, Score -->
        <div class="w-full flex items-center justify-between bg-slate-900/90 border-2 border-slate-800 rounded-2xl px-4 sm:px-6 md:px-8 py-2 sm:py-2.5 mb-2 sm:mb-3 backdrop-blur-md shadow-xl">
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
              <span>ម៉ោង: 
                <span
                  :class="[
                    timerSeconds <= 5
                      ? 'text-rose-400 font-black scale-110 drop-shadow-[0_0_10px_rgba(244,63,94,0.9)] animate-pulse'
                      : (timerSeconds <= 10 ? 'text-rose-400 font-bold' : 'text-amber-300 font-bold')
                  ]"
                  class="font-mono text-xs sm:text-base inline-block transition-transform"
                >
                  {{ timerSeconds }}s
                </span>
              </span>
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
                :class="timerSeconds <= 5 ? 'bg-rose-500 animate-pulse' : (timerSeconds <= 10 ? 'bg-rose-500' : 'bg-gradient-to-r from-amber-500 to-rose-500')"
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
        <div class="w-full bg-gradient-to-b from-slate-900/95 via-slate-900/90 to-slate-950 border-2 border-indigo-500/40 rounded-3xl p-3.5 sm:p-5 md:p-7 lg:p-8 shadow-2xl shadow-indigo-950/60 text-center relative overflow-hidden backdrop-blur-xl flex flex-col justify-between min-h-[46vh] max-h-[76vh]">
          
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
                  {{ currentExplainer }}
                </div>
              </div>
            </div>
          </div>

          <!-- Secret Word & Auto-fetched Image Display -->
          <div class="py-1 sm:py-2 md:py-3 my-auto flex-1 flex flex-col lg:flex-row items-center justify-center gap-4 sm:gap-6 lg:gap-8 min-h-0 w-full px-2">
            
            <!-- ── AUTO-FETCHED IMAGE CARD & EDUCATIONAL CLUE ── -->
            <div class="relative shrink-0 flex flex-col items-center justify-center max-w-full">
              <!-- Image Card Container -->
              <div
                class="relative rounded-2xl sm:rounded-3xl overflow-hidden border-2 border-indigo-500/50 shadow-2xl shadow-indigo-950/80 bg-slate-950 flex items-center justify-center transition-all duration-300 group cursor-pointer"
                :class="[
                  'w-56 h-40 sm:w-72 sm:h-48 md:w-80 md:h-52 lg:w-96 lg:h-56 xl:w-[440px] xl:h-[260px] 2xl:w-[480px] 2xl:h-[280px]',
                  isWordVisible ? '' : 'blur-xl select-none brightness-75'
                ]"
                @click="isImageExpanded = true"
                title="ចុចដើម្បីពង្រីករូបភាពធំ (Click to Zoom)"
              >
                <!-- Blurred Background Ambient Glow Layer for stunning aspect blend -->
                <div
                  v-if="currentWordImage && !wordImageError"
                  class="absolute inset-0 bg-cover bg-center filter blur-xl opacity-30 scale-110 pointer-events-none transition-all duration-700"
                  :style="{ backgroundImage: 'url(' + currentWordImage + ')' }"
                ></div>

                <!-- Loading Shimmer -->
                <div
                  v-if="isWordImageLoading"
                  class="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center gap-2 text-indigo-400 z-10"
                >
                  <span class="material-symbols-outlined text-3xl sm:text-4xl animate-spin text-emerald-400">sync</span>
                  <span class="text-xs font-semibold text-slate-300">កំពុងទាញយករូបភាព...</span>
                </div>

                <!-- Displayed Image (OBJECT-CONTAIN: full device / connectors visible!) -->
                <img
                  v-if="currentWordImage && !wordImageError"
                  :src="currentWordImage"
                  :alt="currentWord"
                  class="w-full h-full object-contain p-2.5 sm:p-3 relative z-0 transition-transform duration-500 group-hover:scale-105"
                  @load="isWordImageLoading = false"
                  @error="handleImageError"
                />

                <!-- Fallback if error -->
                <div
                  v-else-if="!isWordImageLoading && (wordImageError || !currentWordImage)"
                  class="w-full h-full bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 flex flex-col items-center justify-center p-4 text-center relative z-0"
                >
                  <span class="material-symbols-outlined text-4xl text-indigo-400 mb-1">image_search</span>
                  <span class="text-xs text-slate-400">គ្មានរូបភាព</span>
                  <button
                    type="button"
                    class="mt-2 px-3 py-1 rounded-lg bg-indigo-600/80 hover:bg-indigo-500 text-xs text-white font-bold flex items-center gap-1 cursor-pointer transition shadow"
                    @click.stop="loadCurrentImage(currentWord, true)"
                  >
                    <span class="material-symbols-outlined text-xs">refresh</span>
                    <span>ទាញម្តងទៀត</span>
                  </button>
                </div>

                <!-- Floating Bottom Bar: Visual Badge & Switch Image Button -->
                <div class="absolute bottom-2 inset-x-2 flex items-center justify-between pointer-events-none z-10 px-1">
                  <div class="px-2.5 py-0.5 rounded-lg bg-slate-950/85 backdrop-blur-md border border-slate-700/60 text-[10px] font-bold text-slate-200 flex items-center gap-1 shadow-md">
                    <span class="material-symbols-outlined text-xs text-emerald-400">auto_awesome</span>
                    <span>រូបភាពជំនួយ (Visual)</span>
                  </div>

                  <button
                    type="button"
                    class="pointer-events-auto px-2.5 py-1 rounded-lg bg-indigo-600/90 hover:bg-indigo-500 border border-indigo-400/40 text-[10px] sm:text-[11px] font-bold text-white flex items-center gap-1 shadow-lg cursor-pointer transition hover:scale-105 active:scale-95"
                    @click.stop="cycleNextImage"
                    title="ប្តូរយករូបភាពមួយទៀត (Switch Image)"
                  >
                    <span class="material-symbols-outlined text-xs">sync</span>
                    <span>ប្តូររូបភាព</span>
                  </button>
                </div>

                <!-- Zoom Tooltip on hover -->
                <div class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-slate-950/70 border border-slate-700/60 text-slate-300 group-hover:text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow z-10">
                  <span class="material-symbols-outlined text-sm">zoom_in</span>
                </div>
              </div>
            </div>

            <!-- ── SECRET WORD TEXT ── -->
            <div class="flex-1 flex flex-col justify-center text-center lg:text-left min-w-0 max-w-2xl px-2">
              <div class="text-xs sm:text-sm uppercase tracking-widest text-slate-400 font-extrabold mb-1.5 sm:mb-2 flex items-center justify-center lg:justify-start gap-2">
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
                  class="font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-100 to-emerald-300 tracking-wide word-glow py-1 break-words transition-all duration-200"
                  :class="[
                    isWordVisible ? '' : 'blur-xl select-none',
                    secretWordTypographyClass
                  ]"
                >
                  {{ currentWord }}
                </h2>
              </div>

              <!-- ── EDUCATIONAL HINT / CLUE CARD (Beneath Secret Word) ── -->
              <div
                v-if="currentWordClue"
                class="mt-3 sm:mt-4 p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 via-slate-900/90 to-amber-950/20 border border-amber-500/35 shadow-xl shadow-amber-950/20 backdrop-blur-md transition-all duration-200 max-w-xl text-left"
                :class="[
                  isClueVisible ? 'opacity-100 ring-1 ring-amber-500/25' : 'opacity-70',
                  isWordVisible ? '' : 'blur-md select-none'
                ]"
              >
                <div class="flex items-start sm:items-center justify-between gap-3">
                  <div class="flex items-start sm:items-center gap-2.5 min-w-0 flex-1">
                    <span class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center justify-center shrink-0 shadow-sm shadow-amber-500/20">
                      <span class="material-symbols-outlined text-lg">lightbulb</span>
                    </span>
                    <div class="min-w-0 flex-1">
                      <div class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-amber-400/90 flex items-center gap-1.5">
                        <span>តម្រុយជំនួយ (EDUCATIONAL HINT)</span>
                      </div>
                      <div class="text-xs sm:text-sm md:text-base text-slate-100 font-medium leading-relaxed mt-0.5 break-words">
                        <span v-if="isClueVisible">{{ currentWordClue }}</span>
                        <span v-else class="text-slate-400 italic">តម្រុយត្រូវបានបិទបាំង (ចុចរូបភ្នែក ឬចុច [C] ដើម្បីបង្ហាញ)</span>
                      </div>
                    </div>
                  </div>

                  <!-- Visibility Toggle Button -->
                  <button
                    type="button"
                    class="shrink-0 p-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 text-slate-300 hover:text-white transition cursor-pointer flex items-center justify-center"
                    :title="isClueVisible ? 'បិទបាំងតម្រុយ [C]' : 'បង្ហាញតម្រុយ [C]'"
                    @click="isClueVisible = !isClueVisible"
                  >
                    <span class="material-symbols-outlined text-base sm:text-lg">{{ isClueVisible ? 'visibility_off' : 'visibility' }}</span>
                  </button>
                </div>
              </div>
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
            <div class="mt-3 flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-xs text-slate-400 font-semibold">
              <span class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">1</kbd> / <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">Enter</kbd> : <span class="text-emerald-400 font-bold">ត្រូវ</span>
              </span>
              <span class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">2</kbd> / <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">Space</kbd> : <span class="text-rose-400 font-bold">ខុស</span>
              </span>
              <span class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300 font-mono text-xs">C</kbd> : <span class="text-amber-300 font-bold">បិទ/បង្ហាញតម្រុយ</span>
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
                    <img
                      v-if="item.image"
                      :src="item.image"
                      alt=""
                      class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-cover border border-slate-700/80 shrink-0"
                    />
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

    <!-- ── 5. TIME'S UP ALERT MODAL ───────────────────────────────────── -->
    <div
      v-if="isTimeUpBannerVisible"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm transition-all animate-fade-in"
    >
      <div class="w-full max-w-sm bg-gradient-to-b from-slate-900 via-slate-900 to-rose-950/80 border-2 border-rose-500/80 rounded-3xl p-6 sm:p-7 text-center shadow-2xl shadow-rose-950/80">
        <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-rose-500/20 border-2 border-rose-500/40 text-rose-400 flex items-center justify-center shadow-lg shadow-rose-500/30">
          <span class="material-symbols-outlined text-4xl sm:text-5xl animate-pulse">timer_off</span>
        </div>
        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">អស់ពេលពន្យល់ហើយ!</h3>
        <p class="text-xs text-rose-300 font-extrabold uppercase tracking-widest mt-1">TIME'S UP</p>
        <div class="mt-4 py-2 px-3.5 rounded-xl bg-slate-800/90 border border-slate-700 text-slate-300 text-xs flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-amber-400 text-base animate-spin">sync</span>
          <span>ត្រឡប់ទៅបង្វិលកង... (រក្សាសំណួរមុនដដែល)</span>
        </div>
      </div>
    </div>

    <!-- ── 6. IMAGE LIGHTBOX ZOOM MODAL ─────────────────────────────── -->
    <div
      v-if="isImageExpanded && currentWordImage"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/90 backdrop-blur-md cursor-pointer animate-fade-in"
      @click="isImageExpanded = false"
    >
      <div class="relative max-w-4xl max-h-[90vh] bg-slate-900 border-2 border-indigo-500/60 rounded-3xl p-3 sm:p-4 shadow-2xl shadow-indigo-950 cursor-default" @click.stop>
        <button
          type="button"
          class="absolute top-5 right-5 w-9 h-9 rounded-full bg-slate-950/80 hover:bg-slate-800 text-white flex items-center justify-center border border-slate-700 cursor-pointer shadow-lg transition z-10"
          @click="isImageExpanded = false"
        >
          <span class="material-symbols-outlined text-xl">close</span>
        </button>
        <div class="rounded-2xl overflow-hidden max-h-[72vh] flex items-center justify-center bg-black/60 p-2">
          <img :src="currentWordImage" :alt="currentWord" class="max-w-full max-h-[72vh] object-contain block mx-auto rounded-xl" />
        </div>
        <div class="pt-3 pb-1 text-center w-full">
          <span class="text-white font-black text-xl sm:text-2xl">{{ currentWord }}</span>
          <div v-if="currentWordClue" class="text-xs sm:text-sm text-amber-300 font-semibold mt-1">
            💡 {{ currentWordClue }}
          </div>
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
const rawImageLinksText = ref('')
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

/* ── Word Image Auto-Fetch State & Clues ── */
const wordImageCache = ref({})
const wordImageCandidates = ref({})
const currentWordImage = ref('')
const isWordImageLoading = ref(false)
const wordImageError = ref(false)
const isImageExpanded = ref(false)
const isClueVisible = ref(true)

const currentWordClue = computed(() => {
  return findWordClue(currentWord.value)
})

const secretWordTypographyClass = computed(() => {
  const w = (currentWord.value || '').trim()
  const wordCount = w.split(/\s+/).filter(Boolean).length
  const len = w.length

  // Long text / 3+ words (e.g. "USB Flash Drive", "Audio Jack (3.5mm)", "Washing Machine") -> wraps into 2-3 lines
  if (len > 18 || wordCount >= 3) {
    return 'text-2xl sm:text-3xl md:text-4xl lg:text-4xl xl:text-5xl leading-tight'
  }
  // 2 words or medium-long (e.g. "VR Headset", "Smart TV", "Air Conditioner") -> wraps into 2 lines
  if (len > 10 || wordCount >= 2) {
    return 'text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl leading-snug'
  }
  // 1 medium word (e.g. "Computer", "Keyboard") -> 1 line
  if (len > 6) {
    return 'text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl leading-tight'
  }
  // Short 1-word (e.g. "CPU", "RAM", "SSD", "Cat", "Dog") -> 1 line
  return 'text-3xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl leading-none'
})

/* ── Fair & No-Repeat Rotation Pools ── */
const remainingWordsPool = ref([])
const calledStudentsPool = ref([])

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
const isTimeUpBannerVisible = ref(false)
let timerInterval = null
let timeUpTimeoutId = null

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

function extractUrlFromLine(line) {
  if (!line) return ''
  const trimmed = line.trim()
  const match = trimmed.match(/https?:\/\/[^\s"'<>]+/i)
  return match ? match[0] : ''
}

// Maps each word to its corresponding line image link (Line 1 Link -> Line 1 Word)
const wordCustomLinkMap = computed(() => {
  const map = {}
  if (!rawWordsText.value) return map

  const wordsLines = rawWordsText.value.split('\n')
  const linksLines = (rawImageLinksText.value || '').split('\n')

  // Step 1: Strict line-by-line mapping (Line i of Words -> Line i of Links)
  for (let i = 0; i < wordsLines.length; i++) {
    const rawWord = (wordsLines[i] || '').trim()
    const linkLine = (linksLines[i] || '').trim()
    const url = extractUrlFromLine(linkLine)
    if (rawWord && url) {
      map[rawWord] = url
    }
  }

  // Step 2: Fallback for non-empty items if blank lines differ
  const nonBlankWords = wordsLines.map(w => w.trim()).filter(Boolean)
  for (let k = 0; k < nonBlankWords.length; k++) {
    const w = nonBlankWords[k]
    if (!map[w]) {
      const linkLine = (linksLines[k] || '').trim()
      const url = extractUrlFromLine(linkLine)
      if (url) {
        map[w] = url
      }
    }
  }

  return map
})

const mappedLinksCount = computed(() => {
  return Object.keys(wordCustomLinkMap.value).length
})

const parsedImageLinks = computed(() => {
  return (rawImageLinksText.value || '')
    .split('\n')
    .map(extractUrlFromLine)
    .filter(u => u.length > 0)
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

function playCountdownBeep(secondsLeft) {
  if (isMuted.value) return
  initAudio()
  if (!audioCtx) return
  try {
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()

    // Gradual pitch elevation for urgency:
    // 5s: 650Hz, 4s: 720Hz, 3s: 800Hz, 2s: 890Hz, 1s: 990Hz
    const pitchMap = { 5: 650, 4: 720, 3: 800, 2: 890, 1: 990 }
    const freq = pitchMap[secondsLeft] || 800

    osc.type = 'sine'
    osc.frequency.setValueAtTime(freq, audioCtx.currentTime)
    osc.frequency.exponentialRampToValueAtTime(freq * 1.05, audioCtx.currentTime + 0.04)

    // Snappy digital countdown pip
    gain.gain.setValueAtTime(0.28, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.12)

    osc.connect(gain)
    gain.connect(audioCtx.destination)

    osc.start(audioCtx.currentTime)
    osc.stop(audioCtx.currentTime + 0.12)
  } catch (e) {}
}

function playTimeUpSound() {
  if (isMuted.value) return
  initAudio()
  if (!audioCtx) return
  try {
    const playBuzz = (delay) => {
      const osc = audioCtx.createOscillator()
      const gain = audioCtx.createGain()
      osc.type = 'sawtooth'
      osc.frequency.setValueAtTime(160, audioCtx.currentTime + delay)
      osc.frequency.setValueAtTime(130, audioCtx.currentTime + delay + 0.15)
      gain.gain.setValueAtTime(0.25, audioCtx.currentTime + delay)
      gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + delay + 0.25)
      osc.connect(gain)
      gain.connect(audioCtx.destination)
      osc.start(audioCtx.currentTime + delay)
      osc.stop(audioCtx.currentTime + delay + 0.25)
    }
    playBuzz(0)
    playBuzz(0.3)
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
let lastTickSegment = -1
let spinStartTime = 0
let spinDuration = 5000
let spinStartAngle = 0
let spinTotalDelta = 0
let spinTargetWinnerIndex = 0

function easeOutQuart(x) {
  return 1 - Math.pow(1 - x, 4)
}

function resizeAndDrawWheel() {
  const canvas = wheelCanvasRef.value
  if (!canvas) return
  const rect = canvas.getBoundingClientRect()
  const dpr = window.devicePixelRatio || 1
  canvas.width = rect.width * dpr
  canvas.height = rect.height * dpr

  // On initial load, align wheel so slice 0 is perfectly centered under the 12 o'clock pointer (never on a boundary line)
  if (wheelCurrentAngle === 0) {
    const total = parsedStudents.value.length
    if (total > 0) {
      const arc = (2 * Math.PI) / total
      wheelCurrentAngle = 1.5 * Math.PI - arc / 2
    }
  }

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
  const total = parsedStudents.value.length
  if (isSpinning.value || total === 0) return

  if (wheelAnimationId) {
    cancelAnimationFrame(wheelAnimationId)
    wheelAnimationId = null
  }

  isSpinning.value = true
  currentExplainer.value = ''
  initAudio()
  syncRemoteState()

  // Find students who haven't been called yet for a fair, non-repeating cycle
  let uncalledIndices = []
  parsedStudents.value.forEach((student, idx) => {
    if (!calledStudentsPool.value.includes(student)) {
      uncalledIndices.push(idx)
    }
  })

  // If every student in the class has had a turn, reset the pool to start a fresh cycle
  if (uncalledIndices.length === 0) {
    calledStudentsPool.value = []
    uncalledIndices = parsedStudents.value.map((_, idx) => idx)
  }

  // Avoid immediate duplicate on re-spin if multiple students available
  if (uncalledIndices.length > 1 && currentExplainer.value) {
    const prevIdx = parsedStudents.value.findIndex(s => s === currentExplainer.value)
    if (prevIdx !== -1) {
      uncalledIndices = uncalledIndices.filter(idx => idx !== prevIdx)
    }
  }

  const targetIndex = uncalledIndices[Math.floor(Math.random() * uncalledIndices.length)]
  spinTargetWinnerIndex = targetIndex

  const arc = (2 * Math.PI) / total
  const pointerAngle = 1.5 * Math.PI
  const twoPi = 2 * Math.PI

  // Target safely inside the middle of the slice with mild jitter (+/- 25% of half-arc, NEVER near boundaries)
  const sliceCenter = targetIndex * arc + arc / 2
  const jitter = (Math.random() - 0.5) * 0.5 * (arc / 2)
  const desiredModulo = (pointerAngle - (sliceCenter + jitter)) % twoPi
  const normDesired = desiredModulo < 0 ? desiredModulo + twoPi : desiredModulo

  const curModulo = wheelCurrentAngle % twoPi
  const normCur = curModulo < 0 ? curModulo + twoPi : curModulo

  let delta = normDesired - normCur
  if (delta <= 0) {
    delta += twoPi
  }

  const extraSpins = Math.floor(5 + Math.random() * 3) // 5 to 7 full revolutions
  spinStartAngle = wheelCurrentAngle
  spinTotalDelta = delta + extraSpins * twoPi
  spinDuration = 4800 + Math.random() * 500 // 4.8s to 5.3s dramatic natural deceleration
  spinStartTime = performance.now()
  lastTickSegment = getCurrentWinnerIndex()

  wheelAnimationId = requestAnimationFrame(animateSpinLoop)
}

function animateSpinLoop(now) {
  const elapsed = (now || performance.now()) - spinStartTime
  const progress = Math.min(elapsed / spinDuration, 1)
  const eased = easeOutQuart(progress)

  wheelCurrentAngle = spinStartAngle + spinTotalDelta * eased

  // Audio tick per segment
  const curIdx = getCurrentWinnerIndex()
  if (curIdx !== lastTickSegment) {
    lastTickSegment = curIdx
    playTickSound()
  }

  drawWheel()

  if (progress < 1) {
    wheelAnimationId = requestAnimationFrame(animateSpinLoop)
  } else {
    // Wheel completed spinning! Lock final angle:
    wheelCurrentAngle = spinStartAngle + spinTotalDelta
    wheelAnimationId = null
    isSpinning.value = false
    drawWheel()
    onSpinComplete(spinTargetWinnerIndex)
  }
}

function onSpinComplete(forcedIndex) {
  const winnerIndex = typeof forcedIndex === 'number' ? forcedIndex : getCurrentWinnerIndex()
  const winnerName = parsedStudents.value[winnerIndex] || 'សិស្សគ្មានឈ្មោះ'
  currentExplainer.value = winnerName

  // Record student as called so they don't repeat in this round/cycle
  if (!calledStudentsPool.value.includes(winnerName)) {
    calledStudentsPool.value.push(winnerName)
  }

  playWinnerFanfare()
  triggerFullScreenCelebration()
  syncRemoteState()
}

function handleRespin() {
  if (isSpinning.value) return
  // If re-spinning because student is absent, remove them from called pool so they can play later
  if (currentExplainer.value) {
    calledStudentsPool.value = calledStudentsPool.value.filter(s => s !== currentExplainer.value)
    currentExplainer.value = ''
  }
  triggerSpin()
}

function handleWheelClick() {
  if (!isSpinning.value) {
    triggerSpin()
  }
}

/* ── View Transitions ── */
function switchView(viewName) {
  if (timeUpTimeoutId) {
    clearTimeout(timeUpTimeoutId)
    timeUpTimeoutId = null
  }
  isTimeUpBannerVisible.value = false

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

/* ── Curated Word Image Dictionary & Auto-Fetch Logic ── */
const BUILTIN_WORD_IMAGES = {
  // VR & Audio Connectors
  'vr headset': 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg/960px-Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg',
  'vr': 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg/960px-Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg',
  'virtual reality': 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg/960px-Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg',
  'វ៉ែនតា vr': 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg/960px-Sony-PlayStation-4-PSVR-Headset-Mk1-FL.jpg',

  'audio jack': 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Phone-connectors-labeled.jpg/960px-Phone-connectors-labeled.jpg',
  'audio jack (3.5mm)': 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Phone-connectors-labeled.jpg/960px-Phone-connectors-labeled.jpg',
  '3.5mm': 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Phone-connectors-labeled.jpg/960px-Phone-connectors-labeled.jpg',
  'jack': 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Phone-connectors-labeled.jpg/960px-Phone-connectors-labeled.jpg',
  'ក្បាលដោតកាស': 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Phone-connectors-labeled.jpg/960px-Phone-connectors-labeled.jpg',
  'រន្ធដោតកាស': 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Phone-connectors-labeled.jpg/960px-Phone-connectors-labeled.jpg',

  // IT & Tech Terms
  'phone': 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
  'smartphone': 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
  'mobile': 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
  'telephone': 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
  'ទូរស័ព្ទ': 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
  'ទូរស័ព្ទឆ្លាតវៃ': 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',

  // Hardware, Peripherals & Media (Direct Verified Online Internet URLs)
  'usb flash drive': 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',
  'មេម៉ូរី flash drive': 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',
  'flash drive': 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',
  'usb drive': 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',

  'ខ្សែ type-c': 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
  'ខ្សែសាក type-c': 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
  'type-c': 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
  'type c': 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
  'usb-c': 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',
  'usb c': 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/USB-C_plug%2C_focus_stacked.jpg/960px-USB-C_plug%2C_focus_stacked.jpg',

  'រន្ធ usb': 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/USB_port_on_london_bus.jpg/960px-USB_port_on_london_bus.jpg',
  'usb port': 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/USB_port_on_london_bus.jpg/960px-USB_port_on_london_bus.jpg',
  'usb ports': 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/USB_port_on_london_bus.jpg/960px-USB_port_on_london_bus.jpg',
  'usb': 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg/960px-SanDisk-Cruzer-USB-4GB-ThumbDrive.jpg',

  'solid state drive': 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png/960px-Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png',
  'ssd': 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/28/Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png/960px-Samsung_870_QVO_8TB_SATA_2%2C5_Zoll_Internes_Solid_State_Drive_%28SSD%29_%28MZ-77Q8T0BW%29_20211008_SSD023_corr.png',

  'hard disk': 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
  'hard drive': 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
  'hhd': 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
  'hdd': 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
  'ឌីសរឹង': 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',
  'ហាដឌីស': 'https://images.unsplash.com/photo-1531492746076-161ca9bcad58?auto=format&fit=crop&w=800&q=80',

  'ram': 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',
  'រ៉េម': 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',
  'រ៉ាម': 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=800&q=80',

  'compact disc': 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
  'ស៊ីឌី': 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
  'cd': 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
  'dvd': 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',
  'ឌីវីឌី': 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/DVD-R_bottom-side.jpg/960px-DVD-R_bottom-side.jpg',

  'matboard': 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
  'motherboard': 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
  'mainboard': 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',
  'ម៉េដបត': 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80',

  'processor': 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
  'cpu': 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
  'ស៊ីភីយូ': 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',

  'ups': 'https://upload.wikimedia.org/wikipedia/commons/f/f4/UPSFrontView.jpg',
  'អាគុយជំនួយភ្លើង': 'https://upload.wikimedia.org/wikipedia/commons/f/f4/UPSFrontView.jpg',

  'windows': 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Windows_logo_-_2021.svg/960px-Windows_logo_-_2021.svg.png',
  'វីនដូ': 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Windows_logo_-_2021.svg/960px-Windows_logo_-_2021.svg.png',

  'wi-fi': 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',
  'wifi': 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',
  'វ៉ាយហ្វាយ': 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',
  'router': 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/D-Link_DI-524.jpg/960px-D-Link_DI-524.jpg',

  'ម៉ាស៊ីនហ្គេម': 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
  'game console': 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
  'ps5': 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',

  'ទូរទស្សន៍ឆ្លាតវៃ': 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80',
  'smart tv': 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=800&q=80',

  'ថេបប្លេត': 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',
  'tablet': 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',
  'ipad': 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',

  'កុំព្យូទ័រលើតុ': 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=800&q=80',
  'desktop': 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=800&q=80',

  'កុំព្យូទ័រយួរដៃ': 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
  'laptop': 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',

  'camera': 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
  'កាមេរ៉ា': 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',

  'source code': 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
  'code': 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
  'coding': 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
  'programming': 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
  'developer': 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',
  'កូដកម្មវិធី': 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80',

  'computer': 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
  'laptop': 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
  'pc': 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
  'កុំព្យូទ័រ': 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',

  'keyboard': 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
  'ក្តារចុច': 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
  'ក្ដារចុច': 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',

  'mouse': 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
  'computer mouse': 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
  'កណ្ដុរ': 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',
  'កណ្តុរ': 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?auto=format&fit=crop&w=800&q=80',

  'printer': 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនបោះពុម្ព': 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80',

  'internet': 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
  'network': 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
  'អ៊ីនធឺណិត': 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',

  'database': 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',
  'server': 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',
  'ទិន្នន័យ': 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=800&q=80',

  'monitor': 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
  'screen': 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
  'display': 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
  'អេក្រង់': 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',

  'web browser': 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
  'browser': 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
  'website': 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',
  'កម្មវិធីរុករក': 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?auto=format&fit=crop&w=800&q=80',

  'social media': 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',
  'social': 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',
  'បណ្តាញសង្គម': 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',
  'បណ្ដាញសង្គម': 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=800&q=80',

  'cybersecurity': 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=800&q=80',
  'security': 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=800&q=80',
  'សន្តិសុខឌីជីថល': 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=800&q=80',

  // General Classroom Terms
  'robot': 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',
  'មនុស្សយន្ត': 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',
  'រ៉ូបូត': 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',

  'ai': 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',
  'artificial intelligence': 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',
  'បញ្ញាសិប្បនិម្មិត': 'https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&w=800&q=80',

  'headphones': 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
  'កាស': 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',

  'book': 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=800&q=80',
  'សៀវភៅ': 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=800&q=80',

  'school': 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80',
  'សាលារៀន': 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80',

  'teacher': 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80',
  'គ្រូបង្រៀន': 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80',

  'student': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80',
  'សិស្ស': 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=800&q=80',

  'car': 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
  'ឡាន': 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
  'រថយន្ត': 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',

  'airplane': 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',
  'យន្តហោះ': 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80',

  'clock': 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80',
  'watch': 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80',
  'នាឡិកា': 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80',

  // Expanded Everyday Objects, Tech, Appliances & Animals
  'drone': 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?auto=format&fit=crop&w=800&q=80',
  'uav': 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?auto=format&fit=crop&w=800&q=80',
  'ដ្រូន': 'https://images.unsplash.com/photo-1527977966376-1c8408f9f108?auto=format&fit=crop&w=800&q=80',

  'microphone': 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=800&q=80',
  'mic': 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=800&q=80',
  'មីក្រូហ្វូន': 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=800&q=80',
  'មេក្រូ': 'https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=800&q=80',

  'speaker': 'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
  'speakers': 'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
  'បំពងសំឡេង': 'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
  'ធុងបាស': 'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',

  'electric fan': 'https://images.unsplash.com/photo-1585338107529-13afc5f02586?auto=format&fit=crop&w=800&q=80',
  'fan': 'https://images.unsplash.com/photo-1585338107529-13afc5f02586?auto=format&fit=crop&w=800&q=80',
  'កង្ហារ': 'https://images.unsplash.com/photo-1585338107529-13afc5f02586?auto=format&fit=crop&w=800&q=80',

  'air conditioner': 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=800&q=80',
  'ac': 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនត្រជាក់': 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=800&q=80',

  'refrigerator': 'https://images.unsplash.com/photo-1571175443880-49e1d25b2bc5?auto=format&fit=crop&w=800&q=80',
  'fridge': 'https://images.unsplash.com/photo-1571175443880-49e1d25b2bc5?auto=format&fit=crop&w=800&q=80',
  'ទូទឹកកក': 'https://images.unsplash.com/photo-1571175443880-49e1d25b2bc5?auto=format&fit=crop&w=800&q=80',

  'washing machine': 'https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនបោកខោអាវ': 'https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនបោកគក់': 'https://images.unsplash.com/photo-1626806787461-102c1bfaaea1?auto=format&fit=crop&w=800&q=80',

  'smartwatch': 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
  'smart watch': 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
  'នាឡិកាឆ្លាតវៃ': 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',

  'bicycle': 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=800&q=80',
  'bike': 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=800&q=80',
  'កង់': 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=800&q=80',

  'motorcycle': 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80',
  'motorbike': 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80',
  'ម៉ូតូ': 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80',

  'bus': 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
  'ឡានក្រុង': 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
  'រថយន្តក្រុង': 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',

  'train': 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=800&q=80',
  'រថភ្លើង': 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=800&q=80',

  'helicopter': 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=800&q=80',
  'ឧទ្ធម្ភាគចក្រ': 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=800&q=80',

  'boat': 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
  'ship': 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
  'ទូក': 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',
  'កប៉ាល់': 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80',

  'telescope': 'https://images.unsplash.com/photo-1563089145-599997674d42?auto=format&fit=crop&w=800&q=80',
  'កែវយឹត': 'https://images.unsplash.com/photo-1563089145-599997674d42?auto=format&fit=crop&w=800&q=80',

  'satellite': 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?auto=format&fit=crop&w=800&q=80',
  'ផ្កាយរណប': 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?auto=format&fit=crop&w=800&q=80',

  'solar panel': 'https://images.unsplash.com/photo-1509391365360-2e959784a276?auto=format&fit=crop&w=800&q=80',
  'ផ្ទាំងសូឡា': 'https://images.unsplash.com/photo-1509391365360-2e959784a276?auto=format&fit=crop&w=800&q=80',

  'battery': 'https://images.unsplash.com/photo-1619725002198-6a689b72f41d?auto=format&fit=crop&w=800&q=80',
  'ថ្ម': 'https://images.unsplash.com/photo-1619725002198-6a689b72f41d?auto=format&fit=crop&w=800&q=80',
  'អាគុយ': 'https://images.unsplash.com/photo-1619725002198-6a689b72f41d?auto=format&fit=crop&w=800&q=80',

  'calculator': 'https://images.unsplash.com/photo-1587145820266-a5951ee6f620?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនគិតលេខ': 'https://images.unsplash.com/photo-1587145820266-a5951ee6f620?auto=format&fit=crop&w=800&q=80',

  'projector': 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនបញ្ចាំង': 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?auto=format&fit=crop&w=800&q=80',

  'scanner': 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80',
  'ម៉ាស៊ីនស្កេន': 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=800&q=80',

  'apple': 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=800&q=80',
  'ផ្លែប៉ោម': 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=800&q=80',

  'banana': 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=800&q=80',
  'ផ្លែចេក': 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=800&q=80',

  'dog': 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=800&q=80',
  'ឆ្កែ': 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=800&q=80',

  'cat': 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=800&q=80',
  'ឆ្មា': 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=800&q=80',

  'tiger': 'https://images.unsplash.com/photo-1561731216-c3a4d99437d5?auto=format&fit=crop&w=800&q=80',
  'ខ្លា': 'https://images.unsplash.com/photo-1561731216-c3a4d99437d5?auto=format&fit=crop&w=800&q=80',

  'elephant': 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?auto=format&fit=crop&w=800&q=80',
  'ដំរី': 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?auto=format&fit=crop&w=800&q=80',

  'pen': 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=800&q=80',
  'ប៊ិច': 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=800&q=80',

  'pencil': 'https://images.unsplash.com/photo-1585336261026-70e285a7bb91?auto=format&fit=crop&w=800&q=80',
  'ខ្មៅដៃ': 'https://images.unsplash.com/photo-1585336261026-70e285a7bb91?auto=format&fit=crop&w=800&q=80',

  'chair': 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80',
  'កៅអី': 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80',

  'desk': 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=800&q=80',
  'table': 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=800&q=80',
  'តុ': 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=800&q=80'
}

/* ── Educational Khmer Clues & Definitions (ជួយសិស្សយល់ច្បាស់ពីរូបភាព & ពាក្យ) ── */
const WORD_CLUES = {
  'audio jack': 'រន្ធដោត ឬក្បាលដោតកាសទំហំ 3.5mm សម្រាប់បញ្ជូនសញ្ញាសំឡេងទៅកាន់កាស ឬធុងបាស (Audio Jack / 3.5mm)',
  'audio jack (3.5mm)': 'រន្ធដោត ឬក្បាលដោតកាសទំហំ 3.5mm សម្រាប់បញ្ជូនសញ្ញាសំឡេងទៅកាន់កាស ឬធុងបាស (Audio Jack / 3.5mm)',
  '3.5mm': 'រន្ធដោត ឬក្បាលដោតកាសទំហំ 3.5mm សម្រាប់បញ្ជូនសញ្ញាសំឡេង (Audio Jack / Headphone Jack)',
  'jack': 'រន្ធដោត ឬក្បាលដោតកាសទំហំ 3.5mm សម្រាប់បញ្ជូនសញ្ញាសំឡេង (Audio Jack)',
  'ក្បាលដោតកាស': 'ក្បាលដោតកាសទំហំ 3.5mm សម្រាប់បញ្ជូនសញ្ញាសំឡេង (Audio Jack / 3.5mm)',
  'រន្ធដោតកាស': 'រន្ធដោតកាសទំហំ 3.5mm សម្រាប់ដោតខ្សែបញ្ជូនសំឡេង (Audio Jack / 3.5mm Port)',

  'vr headset': 'វ៉ែនតាឆ្លាតវៃពាក់លើក្បាលដើម្បីមើល និងចូលរួមក្នុងពិភពនិម្មិត 3D ដូចពិតៗ (Virtual Reality Headset)',
  'vr': 'បច្ចេកវិទ្យាពិភពនិម្មិត 3D មើលតាមរយៈវ៉ែនតាពាក់លើភ្នែក (Virtual Reality)',
  'virtual reality': 'បច្ចេកវិទ្យាពិភពនិម្មិត 3D មើលតាមរយៈវ៉ែនតាពាក់លើភ្នែក (Virtual Reality)',
  'វ៉ែនតា vr': 'វ៉ែនតាឆ្លាតវៃពាក់លើក្បាលដើម្បីមើល និងចូលរួមក្នុងពិភពនិម្មិត 3D ដូចពិតៗ (VR Headset)',

  'ssd': 'ឧបករណ៍ផ្ទុកទិន្នន័យជំនាន់ថ្មីល្បឿនលឿន (Solid State Drive) ដើរលឿនជាង Hard Disk',
  'solid state drive': 'ឧបករណ៍ផ្ទុកទិន្នន័យជំនាន់ថ្មីល្បឿនលឿន (Solid State Drive) ដើរលឿនជាង Hard Disk',
  'hhd': 'ឧបករណ៍ផ្ទុកទិន្នន័យចានដែកវិល (Hard Disk Drive) សម្រាប់រក្សាទុកឯកសារធំៗ',
  'hdd': 'ឧបករណ៍ផ្ទុកទិន្នន័យចានដែកវិល (Hard Disk Drive) សម្រាប់រក្សាទុកឯកសារធំៗ',
  'hard disk': 'ឧបករណ៍ផ្ទុកទិន្នន័យចានដែកវិល (Hard Disk Drive) សម្រាប់រក្សាទុកឯកសារធំៗ',
  'hard drive': 'ឧបករណ៍ផ្ទុកទិន្នន័យចានដែកវិល (Hard Disk Drive) សម្រាប់រក្សាទុកឯកសារធំៗ',
  'ឌីសរឹង': 'ឧបករណ៍ផ្ទុកទិន្នន័យចានដែកវិល (Hard Disk Drive) សម្រាប់រក្សាទុកឯកសារធំៗ',
  'ហាដឌីស': 'ឧបករណ៍ផ្ទុកទិន្នន័យចានដែកវិល (Hard Disk Drive) សម្រាប់រក្សាទុកឯកសារធំៗ',
  'ram': 'អង្គចងចាំបណ្តោះអាសន្នរបស់កុំព្យូទ័រ (បើបិទភ្លើងទិន្នន័យនឹងបាត់)',
  'រ៉េម': 'អង្គចងចាំបណ្តោះអាសន្នរបស់កុំព្យូទ័រ (បើបិទភ្លើងទិន្នន័យនឹងបាត់)',
  'រ៉ាម': 'អង្គចងចាំបណ្តោះអាសន្នរបស់កុំព្យូទ័រ (បើបិទភ្លើងទិន្នន័យនឹងបាត់)',
  'cpu': 'ខួរក្បាលកណ្តាលរបស់កុំព្យូទ័រ ទទួលបន្ទុកគិត និងដំណើរការទិន្នន័យ (Processor)',
  'processor': 'ខួរក្បាលកណ្តាលរបស់កុំព្យូទ័រ ទទួលបន្ទុកគិត និងដំណើរការទិន្នន័យ (Processor)',
  'ស៊ីភីយូ': 'ខួរក្បាលកណ្តាលរបស់កុំព្យូទ័រ ទទួលបន្ទុកគិត និងដំណើរការទិន្នន័យ (Processor)',
  'matboard': 'បន្ទះសៀគ្វីមេធំជាងគេ ភ្ជាប់គ្រប់គ្រឿងបន្លាស់កុំព្យូទ័រទាំងអស់ (Motherboard)',
  'motherboard': 'បន្ទះសៀគ្វីមេធំជាងគេ ភ្ជាប់គ្រប់គ្រឿងបន្លាស់កុំព្យូទ័រទាំងអស់ (Motherboard)',
  'mainboard': 'បន្ទះសៀគ្វីមេធំជាងគេ ភ្ជាប់គ្រប់គ្រឿងបន្លាស់កុំព្យូទ័រទាំងអស់ (Motherboard)',
  'ម៉េដបត': 'បន្ទះសៀគ្វីមេធំជាងគេ ភ្ជាប់គ្រប់គ្រឿងបន្លាស់កុំព្យូទ័រទាំងអស់ (Motherboard)',
  'រន្ធ usb': 'រន្ធដោតតភ្ជាប់ជាសកលសម្រាប់ Flash Drive, Mouse, ក្តារចុច... (USB Port)',
  'usb port': 'រន្ធដោតតភ្ជាប់ជាសកលសម្រាប់ Flash Drive, Mouse, ក្តារចុច... (USB Port)',
  'usb': 'រន្ធដោតតភ្ជាប់ ឬឧបករណ៍ផ្ទុកទិន្នន័យចល័ត (Universal Serial Bus)',
  'usb flash drive': 'ឧបករណ៍ផ្ទុកទិន្នន័យចល័តតូចមួយ ដោតតាមរន្ធ USB យកតាមខ្លួនបាន',
  'flash drive': 'ឧបករណ៍ផ្ទុកទិន្នន័យចល័តតូចមួយ ដោតតាមរន្ធ USB យកតាមខ្លួនបាន',
  'ខ្សែ type-c': 'ខ្សែតភ្ជាប់ជំនាន់ថ្មីក្បាលរាងទ្រវែង អាចដោតផ្កាប់ឬផ្ងារបាន និងសាកថ្មលឿន',
  'type-c': 'ខ្សែតភ្ជាប់ជំនាន់ថ្មីក្បាលរាងទ្រវែង អាចដោតផ្កាប់ឬផ្ងារបាន និងសាកថ្មលឿន',
  'ខ្សែសាក type-c': 'ខ្សែតភ្ជាប់ជំនាន់ថ្មីក្បាលរាងទ្រវែង អាចដោតផ្កាប់ឬផ្ងារបាន និងសាកថ្មលឿន',
  'ups': 'ឧបករណ៍រក្សាថាមពលអគ្គិសនីបម្រុង កុំឱ្យកុំព្យូទ័ររលត់ភ្លាមៗពេលដាច់ភ្លើង',
  'អាគុយជំនួយភ្លើង': 'ឧបករណ៍រក្សាថាមពលអគ្គិសនីបម្រុង កុំឱ្យកុំព្យូទ័ររលត់ភ្លាមៗពេលដាច់ភ្លើង',
  'wi-fi': 'បច្ចេកវិទ្យាតភ្ជាប់បណ្តាញអ៊ីនធឺណិតឥតខ្សែ (Wireless Network)',
  'wifi': 'បច្ចេកវិទ្យាតភ្ជាប់បណ្តាញអ៊ីនធឺណិតឥតខ្សែ (Wireless Network)',
  'វ៉ាយហ្វាយ': 'បច្ចេកវិទ្យាតភ្ជាប់បណ្តាញអ៊ីនធឺណិតឥតខ្សែ (Wireless Network)',
  'windows': 'ប្រព័ន្ធប្រតិបត្តិការកុំព្យូទ័រពេញនិយមបំផុតរបស់ក្រុមហ៊ុន Microsoft',
  'វីនដូ': 'ប្រព័ន្ធប្រតិបត្តិការកុំព្យូទ័រពេញនិយមបំផុតរបស់ក្រុមហ៊ុន Microsoft',
  'ស៊ីឌី': 'បន្ទះថាសមូលស្តើងប្រើពន្លឺឡាស៊ែរអាន/កត់ត្រាទិន្នន័យ (Compact Disc)',
  'cd': 'បន្ទះថាសមូលស្តើងប្រើពន្លឺឡាស៊ែរអាន/កត់ត្រាទិន្នន័យ (Compact Disc)',
  'dvd': 'បន្ទះថាសមូលស្តើងផ្ទុកទិន្នន័យ ឬខ្សែភាពយន្តច្បាស់ៗបានច្រើនជាង CD',
  'ឌីវីឌី': 'បន្ទះថាសមូលស្តើងផ្ទុកទិន្នន័យ ឬខ្សែភាពយន្តច្បាស់ៗបានច្រើនជាង CD',
  'កុំព្យូទ័រលើតុ': 'កុំព្យូទ័រធំសម្រាប់ប្រើប្រាស់លើតុ ត្រូវការដោតភ្លើងជាប់ជានិច្ច (Desktop PC)',
  'desktop': 'កុំព្យូទ័រធំសម្រាប់ប្រើប្រាស់លើតុ ត្រូវការដោតភ្លើងជាប់ជានិច្ច (Desktop PC)',
  'កុំព្យូទ័រយួរដៃ': 'កុំព្យូទ័រខ្នាតតូចមានថ្ម និងអេក្រង់ភ្ជាប់ជាមួយ អាចបត់ដាក់កាតាបបាន (Laptop)',
  'laptop': 'កុំព្យូទ័រខ្នាតតូចមានថ្ម និងអេក្រង់ភ្ជាប់ជាមួយ អាចបត់ដាក់កាតាបបាន (Laptop)',
  'ទូរស័ព្ទឆ្លាតវៃ': 'ទូរស័ព្ទដៃទំនើបមាន Touch Screen អាចលេង Facebook, YouTube និងដំឡើង App បាន',
  'smartphone': 'ទូរស័ព្ទដៃទំនើបមាន Touch Screen អាចលេង Facebook, YouTube និងដំឡើង App បាន',
  'ទូរស័ព្ទ': 'ឧបករណ៍ទាក់ទងគ្នាពីចម្ងាយ អាចខលផ្ញើសារ និងលេងអ៊ីនធឺណិតបាន',
  'phone': 'ឧបករណ៍ទាក់ទងគ្នាពីចម្ងាយ អាចខលផ្ញើសារ និងលេងអ៊ីនធឺណិតបាន',
  'ថេបប្លេត': 'ឧបករណ៍ឆ្លាតវៃអេក្រង់ធំជាងទូរស័ព្ទ ប៉ុន្តែតូចជាង Laptop ប្រើ Touch Screen',
  'tablet': 'ឧបករណ៍ឆ្លាតវៃអេក្រង់ធំជាងទូរស័ព្ទ ប៉ុន្តែតូចជាង Laptop ប្រើ Touch Screen',
  'ipad': 'ថេបប្លេតរបស់ក្រុមហ៊ុន Apple មានអេក្រង់ថាច់ស្គ្រីនធំទូលាយ',
  'កាមេរ៉ា': 'ឧបករណ៍សម្រាប់ថតរូបភាព និងថតវីដេអូទុកជាអនុស្សាវរីយ៍',
  'camera': 'ឧបករណ៍សម្រាប់ថតរូបភាព និងថតវីដេអូទុកជាអនុស្សាវរីយ៍',
  'ម៉ាស៊ីនហ្គេម': 'ឧបករណ៍អេឡិចត្រូនិកផលិតឡើងសម្រាប់តភ្ជាប់ទូរទស្សន៍លេងហ្គេម (Game Console)',
  'game console': 'ឧបករណ៍អេឡិចត្រូនិកផលិតឡើងសម្រាប់តភ្ជាប់ទូរទស្សន៍លេងហ្គេម (Game Console)',
  'ទូរទស្សន៍ឆ្លាតវៃ': 'ទូរទស្សន៍ដែលអាចភ្ជាប់ Wi-Fi មើល YouTube, Netflix និងប្រើអ៊ីនធឺណិតបាន (Smart TV)',
  'smart tv': 'ទូរទស្សន៍ដែលអាចភ្ជាប់ Wi-Fi មើល YouTube, Netflix និងប្រើអ៊ីនធឺណិតបាន (Smart TV)',
  'កុំព្យូទ័រ': 'ម៉ាស៊ីនអេឡិចត្រូនិកគិតលេខ និងដំណើរការទិន្នន័យ (Computer)',
  'computer': 'ម៉ាស៊ីនអេឡិចត្រូនិកគិតលេខ និងដំណើរការទិន្នន័យ (Computer)',
  'ក្តារចុច': 'ឧបករណ៍សម្រាប់វាយបញ្ចូលអក្សរ លេខ និងបញ្ជាទៅកាន់កុំព្យូទ័រ (Keyboard)',
  'ក្ដារចុច': 'ឧបករណ៍សម្រាប់វាយបញ្ចូលអក្សរ លេខ និងបញ្ជាទៅកាន់កុំព្យូទ័រ (Keyboard)',
  'keyboard': 'ឧបករណ៍សម្រាប់វាយបញ្ចូលអក្សរ លេខ និងបញ្ជាទៅកាន់កុំព្យូទ័រ (Keyboard)',
  'កណ្ដុរ': 'ឧបករណ៍ចង្អុលបង្ហាញ និងចុច Click បញ្ជាលើអេក្រង់កុំព្យូទ័រ (Mouse)',
  'កណ្តុរ': 'ឧបករណ៍ចង្អុលបង្ហាញ និងចុច Click បញ្ជាលើអេក្រង់កុំព្យូទ័រ (Mouse)',
  'mouse': 'ឧបករណ៍ចង្អុលបង្ហាញ និងចុច Click បញ្ជាលើអេក្រង់កុំព្យូទ័រ (Mouse)',
  'ម៉ាស៊ីនបោះពុម្ព': 'ឧបករណ៍សម្រាប់ព្រីនឯកសារ និងរូបភាពចេញពីកុំព្យូទ័រមកលើក្រដាស (Printer)',
  'printer': 'ឧបករណ៍សម្រាប់ព្រីនឯកសារ និងរូបភាពចេញពីកុំព្យូទ័រមកលើក្រដាស (Printer)',
  'អ៊ីនធឺណិត': 'បណ្តាញតភ្ជាប់សកលលោកអនុញ្ញាតឱ្យកុំព្យូទ័រចែករំលែកព័ត៌មានគ្នា',
  'internet': 'បណ្តាញតភ្ជាប់សកលលោកអនុញ្ញាតឱ្យកុំព្យូទ័រចែករំលែកព័ត៌មានគ្នា',
  'កូដកម្មវិធី': 'សំណុំបញ្ជាសរសេរដោយអ្នកបង្កើតកម្មវិធីដើម្បីបញ្ជាកុំព្យូទ័រ (Source Code)',
  'source code': 'សំណុំបញ្ជាសរសេរដោយអ្នកបង្កើតកម្មវិធីដើម្បីបញ្ជាកុំព្យូទ័រ (Source Code)',
  'ទិន្នន័យ': 'ព័ត៌មានដែលផ្ទុកក្នុងកុំព្យូទ័រ ឬប្រព័ន្ធមូលដ្ឋានទិន្នន័យ (Database)',
  'database': 'ប្រព័ន្ធរក្សាទុក និងរៀបចំទិន្នន័យយ៉ាងមានសណ្ដាប់ធ្នាប់',
  'អេក្រង់': 'ផ្ទាំងសម្រាប់បង្ហាញរូបភាព អក្សរ និងវីដេអូឱ្យអ្នកប្រើប្រាស់មើលឃើញ (Monitor)',
  'monitor': 'ផ្ទាំងសម្រាប់បង្ហាញរូបភាព អក្សរ និងវីដេអូឱ្យអ្នកប្រើប្រាស់មើលឃើញ (Monitor)',
  'បណ្តាញសង្គម': 'កម្មវិធីសម្រាប់មនុស្សទំនាក់ទំនង ចែករំលែកព័ត៌មាន និងរូបភាព (Social Media)',
  'បណ្ដាញសង្គម': 'កម្មវិធីសម្រាប់មនុស្សទំនាក់ទំនង ចែករំលែកព័ត៌មាន និងរូបភាព (Social Media)',
  'social media': 'កម្មវិធីសម្រាប់មនុស្សទំនាក់ទំនង ចែករំលែកព័ត៌មាន និងរូបភាព (Social Media)',
  'សន្តិសុខឌីជីថល': 'វិធានការការពារកុំព្យូទ័រ បណ្តាញ និងទិន្នន័យពីការលួចចូល (Cybersecurity)',
  'cybersecurity': 'វិធានការការពារកុំព្យូទ័រ បណ្តាញ និងទិន្នន័យពីការលួចចូល (Cybersecurity)',
  'មនុស្សយន្ត': 'ម៉ាស៊ីនស្វ័យប្រវត្តិដែលអាចបំពេញការងារជំនួសមនុស្ស (Robot)',
  'robot': 'ម៉ាស៊ីនស្វ័យប្រវត្តិដែលអាចបំពេញការងារជំនួសមនុស្ស (Robot)',
  'ai': 'បញ្ញាសិប្បនិម្មិត កុំព្យូទ័រឆ្លាតវៃអាចគិត និងរៀនសូត្រដូចមនុស្ស (Artificial Intelligence)',
  'កាស': 'ឧបករណ៍ពាក់ត្រចៀកសម្រាប់ស្តាប់សំឡេង ឬតន្ត្រីផ្ទាល់ខ្លួន (Headphones)',
  'headphones': 'ឧបករណ៍ពាក់ត្រចៀកសម្រាប់ស្តាប់សំឡេង ឬតន្ត្រីផ្ទាល់ខ្លួន (Headphones)',
  'សៀវភៅ': 'សំណុំទំព័រក្រដាសចងក្រងសម្រាប់អាន និងកត់ត្រាចំណេះដឹង (Book)',
  'book': 'សំណុំទំព័រក្រដាសចងក្រងសម្រាប់អាន និងកត់ត្រាចំណេះដឹង (Book)',
  'សាលារៀន': 'កន្លែងផ្តល់ការអប់រំ និងបណ្តុះបណ្តាលដល់សិស្សានុសិស្ស (School)',
  'school': 'កន្លែងផ្តល់ការអប់រំ និងបណ្តុះបណ្តាលដល់សិស្សានុសិស្ស (School)',
  'គ្រូបង្រៀន': 'អ្នកផ្ទេរចំណេះដឹង និងណែនាំសិស្សានុសិស្សនៅក្នុងថ្នាក់រៀន (Teacher)',
  'teacher': 'អ្នកផ្ទេរចំណេះដឹង និងណែនាំសិស្សានុសិស្សនៅក្នុងថ្នាក់រៀន (Teacher)',
  'សិស្ស': 'អ្នកកំពុងសិក្សា និងក្រេបជញ្ជក់យកចំណេះដឹង (Student)',
  'student': 'អ្នកកំពុងសិក្សា និងក្រេបជញ្ជក់យកចំណេះដឹង (Student)',

  // Everyday & Technology Clues
  'drone': 'យន្តហោះបញ្ជាគ្មានមនុស្សបើក មានស្លាបចក្រច្រើន និងបំពាក់កាមេរ៉ាថតពីលើអាកាស (Drone / UAV)',
  'uav': 'យន្តហោះបញ្ជាគ្មានមនុស្សបើក មានស្លាបចក្រច្រើន និងបំពាក់កាមេរ៉ាថតពីលើអាកាស (Drone / UAV)',
  'ដ្រូន': 'យន្តហោះបញ្ជាគ្មានមនុស្សបើក មានស្លាបចក្រច្រើន និងបំពាក់កាមេរ៉ាថតពីលើអាកាស (Drone / UAV)',

  'microphone': 'ឧបករណ៍បំពង ឬថតចាប់សំឡេងសម្រាប់និយាយ និងច្រៀង (Microphone)',
  'mic': 'ឧបករណ៍បំពង ឬថតចាប់សំឡេងសម្រាប់និយាយ និងច្រៀង (Microphone)',
  'មីក្រូហ្វូន': 'ឧបករណ៍បំពង ឬថតចាប់សំឡេងសម្រាប់និយាយ និងច្រៀង (Microphone)',
  'មេក្រូ': 'ឧបករណ៍បំពង ឬថតចាប់សំឡេងសម្រាប់និយាយ និងច្រៀង (Microphone)',

  'speaker': 'ឧបករណ៍បន្លឺសំឡេង ឬបំពងសំឡេងតន្ត្រីឱ្យឮលាន់ឮខ្លាំង (Loudspeaker)',
  'speakers': 'ឧបករណ៍បន្លឺសំឡេង ឬបំពងសំឡេងតន្ត្រីឱ្យឮលាន់ឮខ្លាំង (Loudspeaker)',
  'បំពងសំឡេង': 'ឧបករណ៍បន្លឺសំឡេង ឬបំពងសំឡេងតន្ត្រីឱ្យឮលាន់ឮខ្លាំង (Loudspeaker)',
  'ធុងបាស': 'ឧបករណ៍បន្លឺសំឡេង ឬបំពងសំឡេងតន្ត្រីឱ្យឮលាន់ឮខ្លាំង (Loudspeaker)',

  'electric fan': 'ឧបករណ៍ប្រើអគ្គិសនីវិលស្លាបបង្កើតកម្លាំងខ្យល់ត្រជាក់ (Fan)',
  'fan': 'ឧបករណ៍ប្រើអគ្គិសនីវិលស្លាបបង្កើតកម្លាំងខ្យល់ត្រជាក់ (Fan)',
  'កង្ហារ': 'ឧបករណ៍ប្រើអគ្គិសនីវិលស្លាបបង្កើតកម្លាំងខ្យល់ត្រជាក់ (Fan)',

  'air conditioner': 'ម៉ាស៊ីនបញ្ចេញខ្យល់ត្រជាក់បន្សុទ្ធខ្យល់ក្នុងបន្ទប់ឱ្យត្រជាក់ស្រួល (Air Conditioner)',
  'ac': 'ម៉ាស៊ីនបញ្ចេញខ្យល់ត្រជាក់បន្សុទ្ធខ្យល់ក្នុងបន្ទប់ឱ្យត្រជាក់ស្រួល (Air Conditioner)',
  'ម៉ាស៊ីនត្រជាក់': 'ម៉ាស៊ីនបញ្ចេញខ្យល់ត្រជាក់បន្សុទ្ធខ្យល់ក្នុងបន្ទប់ឱ្យត្រជាក់ស្រួល (Air Conditioner)',

  'refrigerator': 'ទូសម្រាប់រក្សាទុកម្ហូបអាហារ បន្លែ ផ្លែឈើឱ្យនៅស្រស់មិនខូច (Refrigerator)',
  'fridge': 'ទូសម្រាប់រក្សាទុកម្ហូបអាហារ បន្លែ ផ្លែឈើឱ្យនៅស្រស់មិនខូច (Refrigerator)',
  'ទូទឹកកក': 'ទូសម្រាប់រក្សាទុកម្ហូបអាហារ បន្លែ ផ្លែឈើឱ្យនៅស្រស់មិនខូច (Refrigerator)',

  'washing machine': 'ម៉ាស៊ីនស្វ័យប្រវត្តសម្រាប់បោកគក់ និងសម្អាតសម្លៀកបំពាក់ (Washing Machine)',
  'ម៉ាស៊ីនបោកខោអាវ': 'ម៉ាស៊ីនស្វ័យប្រវត្តសម្រាប់បោកគក់ និងសម្អាតសម្លៀកបំពាក់ (Washing Machine)',
  'ម៉ាស៊ីនបោកគក់': 'ម៉ាស៊ីនស្វ័យប្រវត្តសម្រាប់បោកគក់ និងសម្អាតសម្លៀកបំពាក់ (Washing Machine)',

  'smartwatch': 'នាឡិកាដៃឆ្លាតវៃអាចវាស់ចង្វាក់បេះដូង រាប់ជំហាន និងភ្ជាប់ជាមួយទូរស័ព្ទ (Smartwatch)',
  'smart watch': 'នាឡិកាដៃឆ្លាតវៃអាចវាស់ចង្វាក់បេះដូង រាប់ជំហាន និងភ្ជាប់ជាមួយទូរស័ព្ទ (Smartwatch)',
  'នាឡិកាឆ្លាតវៃ': 'នាឡិកាដៃឆ្លាតវៃអាចវាស់ចង្វាក់បេះដូង រាប់ជំហាន និងភ្ជាប់ជាមួយទូរស័ព្ទ (Smartwatch)',

  'bicycle': 'យានជំនិះកង់ពីរ ជិះដោយការធាក់ដោយកម្លាំងជើង (Bicycle)',
  'bike': 'យានជំនិះកង់ពីរ ជិះដោយការធាក់ដោយកម្លាំងជើង (Bicycle)',
  'កង់': 'យានជំនិះកង់ពីរ ជិះដោយការធាក់ដោយកម្លាំងជើង (Bicycle)',

  'motorcycle': 'យានជំនិះកង់ពីរដំណើរការដោយម៉ាស៊ីនសាំង ឬអគ្គិសនី (Motorcycle)',
  'motorbike': 'យានជំនិះកង់ពីរដំណើរការដោយម៉ាស៊ីនសាំង ឬអគ្គិសនី (Motorcycle)',
  'ម៉ូតូ': 'យានជំនិះកង់ពីរដំណើរការដោយម៉ាស៊ីនសាំង ឬអគ្គិសនី (Motorcycle)',

  'bus': 'រថយន្តធំសម្រាប់ដឹកអ្នកដំណើរជាសាធារណៈបានច្រើននាក់ (Bus)',
  'ឡានក្រុង': 'រថយន្តធំសម្រាប់ដឹកអ្នកដំណើរជាសាធារណៈបានច្រើននាក់ (Bus)',
  'រថយន្តក្រុង': 'រថយន្តធំសម្រាប់ដឹកអ្នកដំណើរជាសាធារណៈបានច្រើននាក់ (Bus)',

  'train': 'យានជំនិះមានក្បាលម៉ាស៊ីនទាញរទេះរត់លើផ្លូវដែក (Train)',
  'រថភ្លើង': 'យានជំនិះមានក្បាលម៉ាស៊ីនទាញរទេះរត់លើផ្លូវដែក (Train)',

  'helicopter': 'យានជំនិះហោះហើរលើអាកាសមានស្លាបចក្រវិលធំនៅពីលើ (Helicopter)',
  'ឧទ្ធម្ភាគចក្រ': 'យានជំនិះហោះហើរលើអាកាសមានស្លាបចក្រវិលធំនៅពីលើ (Helicopter)',

  'telescope': 'ឧបករណ៍អុបទិកសម្រាប់ឆ្លុះមើលវត្ថុឆ្ងាយៗ ដូចជាព្រះចន្ទ និងផ្កាយលើមេឃ (Telescope)',
  'កែវយឹត': 'ឧបករណ៍អុបទិកសម្រាប់ឆ្លុះមើលវត្ថុឆ្ងាយៗ ដូចជាព្រះចន្ទ និងផ្កាយលើមេឃ (Telescope)',

  'satellite': 'ឧបករណ៍បាញ់បង្ហោះទៅក្នុងលំហអាកាសសម្រាប់ផ្សាយសញ្ញា និងទូរគមនាគមន៍ (Satellite)',
  'ផ្កាយរណប': 'ឧបករណ៍បាញ់បង្ហោះទៅក្នុងលំហអាកាសសម្រាប់ផ្សាយសញ្ញា និងទូរគមនាគមន៍ (Satellite)',

  'solar panel': 'ផ្ទាំងស្រូបយកពន្លឺព្រះអាទិត្យបំលែងជាថាមពលអគ្គិសនី (Solar Panel)',
  'ផ្ទាំងសូឡា': 'ផ្ទាំងស្រូបយកពន្លឺព្រះអាទិត្យបំលែងជាថាមពលអគ្គិសនី (Solar Panel)',

  'battery': 'ឧបករណ៍ស្តុកទុកថាមពលគីមីដើម្បីផ្គត់ផ្គង់ចរន្តអគ្គិសនី (Battery)',
  'ថ្ម': 'ឧបករណ៍ស្តុកទុកថាមពលគីមីដើម្បីផ្គត់ផ្គង់ចរន្តអគ្គិសនី (Battery)',
  'អាគុយ': 'ឧបករណ៍ស្តុកទុកថាមពលគីមីដើម្បីផ្គត់ផ្គង់ចរន្តអគ្គិសនី (Battery)',

  'calculator': 'ឧបករណ៍អេឡិចត្រូនិកខ្នាតតូចសម្រាប់គណនាលេខ និងរូបមន្តគណិតវិទ្យា (Calculator)',
  'ម៉ាស៊ីនគិតលេខ': 'ឧបករណ៍អេឡិចត្រូនិកខ្នាតតូចសម្រាប់គណនាលេខ និងរូបមន្តគណិតវិទ្យា (Calculator)',

  'projector': 'ឧបករណ៍បញ្ចាំងពន្លឺ និងរូបភាពពីកុំព្យូទ័រឡើងទៅលើផ្ទាំងសំពត់ស (Projector)',
  'ម៉ាស៊ីនបញ្ចាំង': 'ឧបករណ៍បញ្ចាំងពន្លឺ និងរូបភាពពីកុំព្យូទ័រឡើងទៅលើផ្ទាំងសំពត់ស (Projector)',

  'scanner': 'ឧបករណ៍ផ្តិតយកឯកសារ ឬរូបភាពលើក្រដាសបញ្ចូលទៅក្នុងកុំព្យូទ័រ (Scanner)',
  'ម៉ាស៊ីនស្កេន': 'ឧបករណ៍ផ្តិតយកឯកសារ ឬរូបភាពលើក្រដាសបញ្ចូលទៅក្នុងកុំព្យូទ័រ (Scanner)',

  'apple': 'ផ្លែឈើស្រួយផ្អែម មានពណ៌ក្រហម ឬបៃតង (Apple)',
  'ផ្លែប៉ោម': 'ផ្លែឈើស្រួយផ្អែម មានពណ៌ក្រហម ឬបៃតង (Apple)',

  'banana': 'ផ្លែឈើវែងកោង សំបកពណ៌លឿង សាច់ទន់ផ្អែមឆ្ងាញ់ (Banana)',
  'ផ្លែចេក': 'ផ្លែឈើវែងកោង សំបកពណ៌លឿង សាច់ទន់ផ្អែមឆ្ងាញ់ (Banana)',

  'dog': 'សត្វចិញ្ចឹមស្មោះត្រង់ ជួយយាមផ្ទះ និងស្រឡាញ់ម្ចាស់ (Dog)',
  'ឆ្កែ': 'សត្វចិញ្ចឹមស្មោះត្រង់ ជួយយាមផ្ទះ និងស្រឡាញ់ម្ចាស់ (Dog)',

  'cat': 'សត្វចិញ្ចឹមគួរឱ្យស្រឡាញ់ ចូលចិត្តចាប់កណ្តុរ (Cat)',
  'ឆ្មា': 'សត្វចិញ្ចឹមគួរឱ្យស្រឡាញ់ ចូលចិត្តចាប់កណ្តុរ (Cat)',

  'tiger': 'សត្វព្រៃកាចសាហាវ មានឆ្នូតពណ៌ខ្មៅលើរោមពណ៌លឿងទុំ (Tiger)',
  'ខ្លា': 'សត្វព្រៃកាចសាហាវ មានឆ្នូតពណ៌ខ្មៅលើរោមពណ៌លឿងទុំ (Tiger)',

  'elephant': 'សត្វលើគោកធំជាងគេ មានប្រមោយវែង និងភ្លុកសស្អាត (Elephant)',
  'ដំរី': 'សត្វលើគោកធំជាងគេ មានប្រមោយវែង និងភ្លុកសស្អាត (Elephant)',

  'pen': 'ឧបករណ៍សម្រាប់សរសេរអក្សរដោយប្រើទឹកថ្នាំ (Pen)',
  'ប៊ិច': 'ឧបករណ៍សម្រាប់សរសេរអក្សរដោយប្រើទឹកថ្នាំ (Pen)',

  'pencil': 'ឧបករណ៍សម្រាប់សរសេរ ឬគូររូប មានបណ្តូលធ្វើពីក្រាហ្វិត (Pencil)',
  'ខ្មៅដៃ': 'ឧបករណ៍សម្រាប់សរសេរ ឬគូររូប មានបណ្តូលធ្វើពីក្រាហ្វិត (Pencil)',

  'chair': 'គ្រឿងសង្ហារិមសម្រាប់មនុស្សអង្គុយសម្រាក ឬធ្វើការងារ (Chair)',
  'កៅអី': 'គ្រឿងសង្ហារិមសម្រាប់មនុស្សអង្គុយសម្រាក ឬធ្វើការងារ (Chair)',

  'desk': 'គ្រឿងសង្ហារិមរាបស្មើសម្រាប់ដាក់កុំព្យូទ័រ សៀវភៅ ឬធ្វើការងារ (Desk / Table)',
  'table': 'គ្រឿងសង្ហារិមរាបស្មើសម្រាប់ដាក់កុំព្យូទ័រ សៀវភៅ ឬធ្វើការងារ (Desk / Table)',
  'តុ': 'គ្រឿងសង្ហារិមរាបស្មើសម្រាប់ដាក់កុំព្យូទ័រ សៀវភៅ ឬធ្វើការងារ (Desk / Table)'
}

/* ── Khmer-to-English Mapping for Fast Cross-Language Lookup ── */
const KHMER_TO_ENG = {
  'ដ្រូន': 'drone',
  'កង្ហារ': 'electric fan',
  'ម៉ាស៊ីនត្រជាក់': 'air conditioner',
  'ទូទឹកកក': 'refrigerator',
  'ម៉ាស៊ីនបោកខោអាវ': 'washing machine',
  'ម៉ាស៊ីនបោកគក់': 'washing machine',
  'ម៉ាស៊ីនគិតលេខ': 'calculator',
  'មីក្រូហ្វូន': 'microphone',
  'មេក្រូ': 'microphone',
  'បំពងសំឡេង': 'loudspeaker',
  'ធុងបាស': 'speaker',
  'ម៉ាស៊ីនបញ្ចាំង': 'projector',
  'ម៉ាស៊ីនស្កេន': 'scanner',
  'នាឡិកាឆ្លាតវៃ': 'smartwatch',
  'កង់': 'bicycle',
  'ម៉ូតូ': 'motorcycle',
  'ឡាន': 'car',
  'រថយន្ត': 'car',
  'ឡានក្រុង': 'bus',
  'រថយន្តក្រុង': 'bus',
  'យន្តហោះ': 'airplane',
  'ឧទ្ធម្ភាគចក្រ': 'helicopter',
  'រថភ្លើង': 'train',
  'ទូក': 'boat',
  'កប៉ាល់': 'ship',
  'កែវយឹត': 'telescope',
  'ផ្កាយរណប': 'satellite',
  'ផ្ទាំងសូឡា': 'solar panel',
  'ថ្ម': 'battery',
  'អាគុយ': 'battery',
  'ផ្លែប៉ោម': 'apple fruit',
  'ផ្លែចេក': 'banana fruit',
  'ឆ្កែ': 'dog',
  'ឆ្មា': 'cat',
  'ខ្លា': 'tiger',
  'ដំរី': 'elephant',
  'ប៊ិច': 'pen',
  'ខ្មៅដៃ': 'pencil',
  'តុ': 'desk',
  'កៅអី': 'chair',
  'សៀវភៅ': 'book',
}

function findWordClue(rawWord) {
  if (!rawWord) return ''
  
  // 1. If user typed custom clue via separator: "Word | តម្រុយ..."
  if (rawWord.includes('|')) {
    const parts = rawWord.split('|')
    const possibleClue = parts[1].trim()
    if (possibleClue && !/^https?:\/\//i.test(possibleClue)) {
      return possibleClue
    }
  }

  // 2. If parenthesized hint provided: "SSD (ឧបករណ៍ផ្ទុកទិន្នន័យ)"
  const parenMatch = rawWord.match(/\(([^)]+)\)|\[([^\]]+)\]/)
  if (parenMatch) {
    const inside = (parenMatch[1] || parenMatch[2] || '').trim()
    if (inside.length > 8 && /[\u1780-\u17FF]/.test(inside)) {
      return inside
    }
  }

  // 3. Match against curated WORD_CLUES
  const lower = rawWord.toLowerCase()
  const sortedKeys = Object.keys(WORD_CLUES).sort((a, b) => b.length - a.length)
  for (const key of sortedKeys) {
    if (/^[a-z0-9\s\-]+$/i.test(key)) {
      const escaped = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      const regex = new RegExp(`(^|[^a-z0-9])${escaped}([^a-z0-9]|$)`, 'i')
      if (regex.test(lower)) {
        return WORD_CLUES[key]
      }
    } else if (lower.includes(key.toLowerCase())) {
      return WORD_CLUES[key]
    }
  }

  // 4. Try Khmer translated key
  for (const [km, en] of Object.entries(KHMER_TO_ENG)) {
    if (lower.includes(km.toLowerCase()) && WORD_CLUES[en]) {
      return WORD_CLUES[en]
    }
  }

  return ''
}

function getSafeImageUrl(url) {
  if (!url) return ''
  if (url.startsWith('/')) return url
  if (url.includes('images.unsplash.com')) return url
  if (url.includes('pollinations.ai')) return url
  // All external domains (Wikimedia, Wikipedia, etc.) routed via same-origin proxy to eliminate COEP/CORB blocks
  return `/api/lucky-wheel/proxy-image?url=${encodeURIComponent(url)}`
}

function findBuiltinImage(rawWord) {
  if (!rawWord) return ''
  const lower = rawWord.toLowerCase()
  const sortedKeys = Object.keys(BUILTIN_WORD_IMAGES).sort((a, b) => b.length - a.length)
  for (const key of sortedKeys) {
    if (/^[a-z0-9\s\-]+$/i.test(key)) {
      const regex = new RegExp(`(^|[^a-z0-9])${key}([^a-z0-9]|$)`, 'i')
      if (regex.test(lower)) {
        return getSafeImageUrl(BUILTIN_WORD_IMAGES[key])
      }
    } else if (lower.includes(key.toLowerCase())) {
      return getSafeImageUrl(BUILTIN_WORD_IMAGES[key])
    }
  }

  // Try Khmer translated term
  for (const [km, en] of Object.entries(KHMER_TO_ENG)) {
    if (lower.includes(km.toLowerCase()) && BUILTIN_WORD_IMAGES[en]) {
      return getSafeImageUrl(BUILTIN_WORD_IMAGES[en])
    }
  }

  return ''
}

function sanitizeImageUrl(url) {
  if (!url) return ''
  // Strip tracking parameters (?utm_source=...) which Brave Shields and adblockers block
  if (url.includes('?') && (url.includes('wikimedia.org') || url.includes('wikipedia.org') || url.includes('utm_'))) {
    return url.split('?')[0]
  }
  return url
}

function extractSearchKeyword(rawWord) {
  if (!rawWord) return { keyword: '', directUrl: '' }

  // 1. If explicit URL provided: "Word | https://..."
  if (rawWord.includes('|')) {
    const parts = rawWord.split('|')
    const possibleUrl = parts[1].trim()
    if (/^https?:\/\//i.test(possibleUrl)) {
      return { keyword: parts[0].trim(), directUrl: possibleUrl }
    }
  }

  // 2. Extract content inside parentheses: (Computer) or [Computer]
  const parenMatch = rawWord.match(/\(([^)]+)\)|\[([^\]]+)\]/)
  if (parenMatch) {
    const inside = (parenMatch[1] || parenMatch[2] || '').trim()
    if (inside.length > 0) {
      return { keyword: inside, directUrl: '' }
    }
  }

  // 3. Extract English words if any
  const englishMatch = rawWord.match(/[a-zA-Z\s]{2,}/)
  if (englishMatch && englishMatch[0].trim().length > 1) {
    return { keyword: englishMatch[0].trim(), directUrl: '' }
  }

  // 4. Check Khmer translation dictionary
  const lower = rawWord.toLowerCase()
  for (const [km, en] of Object.entries(KHMER_TO_ENG)) {
    if (lower.includes(km.toLowerCase())) {
      return { keyword: en, directUrl: '' }
    }
  }

  // 5. Fallback to clean Khmer/raw word
  const clean = rawWord.replace(/[^\p{L}\p{N}\s]/gu, '').trim()
  return { keyword: clean || rawWord.trim(), directUrl: '' }
}

async function fetchImageForWord(rawWord, cycleIndex = 0) {
  if (!rawWord) return ''

  // 0. Custom image link provided in Setup Bank (HIGHEST PRIORITY)
  if (wordCustomLinkMap.value && (wordCustomLinkMap.value[rawWord] || wordCustomLinkMap.value[rawWord.trim()])) {
    const customLink = wordCustomLinkMap.value[rawWord] || wordCustomLinkMap.value[rawWord.trim()]
    const customUrl = getSafeImageUrl(customLink)
    if (!cycleIndex) {
      wordImageCache.value[rawWord] = customUrl
      wordImageCandidates.value[rawWord] = [customUrl]
      return customUrl
    }
  }

  if (!cycleIndex && wordImageCache.value[rawWord]) {
    return wordImageCache.value[rawWord]
  }

  // 1. Instant check in Curated Builtin Dictionary (0ms, 100% relevant, perfectly unblocked)
  const builtin = findBuiltinImage(rawWord)
  if (builtin && !cycleIndex) {
    wordImageCache.value[rawWord] = builtin
    return builtin
  }

  const { keyword, directUrl } = extractSearchKeyword(rawWord)
  if (directUrl) {
    const safeDirect = getSafeImageUrl(directUrl)
    wordImageCache.value[rawWord] = safeDirect
    wordImageCandidates.value[rawWord] = [safeDirect]
    return safeDirect
  }
  if (!keyword) return ''

  const kwBuiltin = findBuiltinImage(keyword)
  if (kwBuiltin && !cycleIndex) {
    wordImageCache.value[rawWord] = kwBuiltin
    return kwBuiltin
  }

  // 2. Try our Laravel backend API endpoint (Uses User-Agent, caches candidates in DB, proxies external images)
  try {
    const res = await axios.get(`/api/lucky-wheel/word-image?word=${encodeURIComponent(rawWord)}&cycle=${cycleIndex}`, { timeout: 7000 })
    if (res.data && res.data.success && res.data.url) {
      if (res.data.images && Array.isArray(res.data.images) && res.data.images.length > 0) {
        wordImageCandidates.value[rawWord] = res.data.images
      }
      const safeUrl = getSafeImageUrl(sanitizeImageUrl(res.data.url))
      wordImageCache.value[rawWord] = safeUrl
      return safeUrl
    }
  } catch (e) {
    // Backend API failed or timed out, fallback to client-side strategies
  }

  // 3. Client-side Khmer Wikipedia (km.wikipedia.org) if Khmer script detected
  if (/[\u1780-\u17FF]/.test(rawWord)) {
    const kmClean = rawWord.replace(/[^\u1780-\u17FF\s]/g, '').trim()
    if (kmClean) {
      try {
        const kmWikiUrl = `https://km.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=${encodeURIComponent(kmClean)}&gsrlimit=3&prop=pageimages&pithumbsize=640&piprop=thumbnail&format=json&origin=*`
        const kmRes = await axios.get(kmWikiUrl, { timeout: 3500 })
        if (kmRes.data?.query?.pages) {
          const kmPages = Object.values(kmRes.data.query.pages)
          for (const p of kmPages) {
            if (p.thumbnail?.source && !p.thumbnail.source.includes('Disambig') && !p.thumbnail.source.includes('.svg')) {
              const safeUrl = getSafeImageUrl(sanitizeImageUrl(p.thumbnail.source))
              wordImageCache.value[rawWord] = safeUrl
              return safeUrl
            }
          }
        }
      } catch (e) {}
    }
  }

  // 4a. Client-side English Wikipedia Direct Article Title (origin=*)
  try {
    const directTitleUrl = `https://en.wikipedia.org/w/api.php?action=query&titles=${encodeURIComponent(keyword)}&prop=pageimages&pithumbsize=640&piprop=thumbnail&format=json&origin=*`
    const dtRes = await axios.get(directTitleUrl, { timeout: 3000 })
    if (dtRes.data?.query?.pages) {
      const dtPages = Object.values(dtRes.data.query.pages)
      for (const p of dtPages) {
        if (p.thumbnail?.source && !p.thumbnail.source.includes('Disambig') && !p.thumbnail.source.includes('.svg')) {
          const safeUrl = getSafeImageUrl(sanitizeImageUrl(p.thumbnail.source))
          wordImageCache.value[rawWord] = safeUrl
          return safeUrl
        }
      }
    }
  } catch (e) {}

  // 4b. Client-side English Wikipedia Generator Search with origin=*
  try {
    const wikiUrl = `https://en.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=${encodeURIComponent(keyword)}&gsrlimit=3&prop=pageimages&pithumbsize=640&piprop=thumbnail&format=json&origin=*`
    const res = await axios.get(wikiUrl, { timeout: 3000 })
    if (res.data?.query?.pages) {
      const pages = Object.values(res.data.query.pages)
      for (const p of pages) {
        if (p.thumbnail?.source && !p.thumbnail.source.includes('Disambig') && !p.thumbnail.source.includes('.svg')) {
          const safeUrl = getSafeImageUrl(sanitizeImageUrl(p.thumbnail.source))
          wordImageCache.value[rawWord] = safeUrl
          return safeUrl
        }
      }
    }
  } catch (e) {}

  // 5. Client-side Wikimedia Commons image search with origin=*
  try {
    const commonsUrl = `https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=${encodeURIComponent(keyword + ' photo')}&gsrlimit=3&prop=imageinfo&iiprop=url&iiurlwidth=640&format=json&origin=*`
    const cRes = await axios.get(commonsUrl, { timeout: 3000 })
    if (cRes.data?.query?.pages) {
      const cPages = Object.values(cRes.data.query.pages)
      for (const cp of cPages) {
        if (cp.imageinfo?.[0]?.thumburl) {
          const safeUrl = getSafeImageUrl(sanitizeImageUrl(cp.imageinfo[0].thumburl))
          wordImageCache.value[rawWord] = safeUrl
          return safeUrl
        }
      }
    }
  } catch (e) {}

  // 6. Client-side Openverse Public API
  try {
    const ovUrl = `https://api.openverse.org/v1/images/?q=${encodeURIComponent(keyword)}&page_size=3`
    const ovRes = await axios.get(ovUrl, { timeout: 3000 })
    if (ovRes.data?.results) {
      for (const item of ovRes.data.results) {
        if (item.url) {
          const safeUrl = getSafeImageUrl(item.url)
          wordImageCache.value[rawWord] = safeUrl
          return safeUrl
        }
      }
    }
  } catch (e) {}

  return ''
}

function handleImageError() {
  const current = currentWordImage.value || ''
  const word = currentWord.value

  // 1. If currently a raw external URL not yet proxied, try routing via our safe proxy
  if (current && !current.startsWith('/api/lucky-wheel/proxy-image')) {
    const proxied = `/api/lucky-wheel/proxy-image?url=${encodeURIComponent(current)}`
    currentWordImage.value = proxied
    wordImageCache.value[word] = proxied
    return
  }

  // 2. If alternative candidates exist for this word, cycle to the next candidate automatically
  const candidates = wordImageCandidates.value[word] || []
  if (candidates.length > 1) {
    const currIdx = candidates.indexOf(current)
    const nextIdx = (currIdx + 1) % candidates.length
    const alt = candidates[nextIdx]
    if (alt && alt !== current) {
      currentWordImage.value = alt
      wordImageCache.value[word] = alt
      return
    }
  }

  // 3. Try builtin dictionary if different from current
  const builtin = findBuiltinImage(word)
  if (builtin && current !== builtin) {
    currentWordImage.value = builtin
    wordImageCache.value[word] = builtin
    return
  }

  wordImageError.value = true
}

async function loadCurrentImage(word, forceRefresh = false) {
  if (!word) {
    currentWordImage.value = ''
    return
  }
  wordImageError.value = false

  // 0. If custom image link exists in Setup Bank and not force-refreshing, use it directly with 0ms delay!
  if (!forceRefresh && wordCustomLinkMap.value && (wordCustomLinkMap.value[word] || wordCustomLinkMap.value[word.trim()])) {
    const custom = getSafeImageUrl(wordCustomLinkMap.value[word] || wordCustomLinkMap.value[word.trim()])
    currentWordImage.value = custom
    wordImageCache.value[word] = custom
    wordImageCandidates.value[word] = [custom]
    isWordImageLoading.value = false
    syncRemoteState()
    return
  }

  if (forceRefresh) {
    delete wordImageCache.value[word]
    delete wordImageCandidates.value[word]
  } else if (wordImageCache.value[word]) {
    currentWordImage.value = wordImageCache.value[word]
    isWordImageLoading.value = false
    syncRemoteState()
    return
  }
  isWordImageLoading.value = true
  try {
    const img = await fetchImageForWord(word, 0)
    currentWordImage.value = img
    syncRemoteState()
  } catch (e) {
    wordImageError.value = true
  } finally {
    isWordImageLoading.value = false
  }
}

let cycleImageIndex = 0

async function cycleNextImage() {
  if (!currentWord.value) return
  const word = currentWord.value
  isWordImageLoading.value = true
  wordImageError.value = false
  cycleImageIndex++

  // 1. If we already have multiple candidates loaded, cycle locally with ZERO delay!
  const candidates = wordImageCandidates.value[word] || []
  if (candidates.length > 1) {
    const nextUrl = candidates[cycleImageIndex % candidates.length]
    currentWordImage.value = nextUrl
    wordImageCache.value[word] = nextUrl
    isWordImageLoading.value = false
    syncRemoteState()
    return
  }

  // 2. Request next image from Laravel backend via live internet search cycle
  try {
    const res = await axios.get(`/api/lucky-wheel/word-image?word=${encodeURIComponent(word)}&cycle=${cycleImageIndex}&refresh=1`, { timeout: 6000 })
    if (res.data?.success && res.data.url) {
      const safeUrl = getSafeImageUrl(sanitizeImageUrl(res.data.url))
      const customUrl = wordCustomLinkMap.value?.[word] ? getSafeImageUrl(wordCustomLinkMap.value[word]) : null
      const list = [
        ...(customUrl ? [customUrl] : []),
        ...(res.data.images && Array.isArray(res.data.images) ? res.data.images : [safeUrl])
      ]
      wordImageCandidates.value[word] = Array.from(new Set(list))
      currentWordImage.value = safeUrl
      wordImageCache.value[word] = safeUrl
      syncRemoteState()
      return
    }
  } catch (err) {
    console.warn('Backend cycle failed, trying client fallback:', err)
  }

  // 3. Fallback: Client-side Wikimedia Commons search with offset
  try {
    const { keyword } = extractSearchKeyword(word)
    const kw = keyword || word
    const commonsUrl = `https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=${encodeURIComponent(kw + ' photo')}&gsroffset=${cycleImageIndex}&gsrlimit=3&prop=imageinfo&iiprop=url&iiurlwidth=800&format=json&origin=*`
    const cRes = await axios.get(commonsUrl, { timeout: 3500 })
    const pages = Object.values(cRes.data?.query?.pages || {})
    for (const p of pages) {
      if (p.imageinfo?.[0]?.thumburl) {
        const safeUrl = getSafeImageUrl(sanitizeImageUrl(p.imageinfo[0].thumburl))
        if (safeUrl !== currentWordImage.value) {
          currentWordImage.value = safeUrl
          wordImageCache.value[word] = safeUrl
          syncRemoteState()
          return
        }
      }
    }
  } catch (err) {}

  isWordImageLoading.value = false
}

async function prefetchImages() {
  for (const w of parsedWords.value) {
    if (!wordImageCache.value[w]) {
      await fetchImageForWord(w).catch(() => {})
      await new Promise(r => setTimeout(r, 200))
    }
  }
}

function shuffleArray(arr) {
  const copy = [...arr]
  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[copy[i], copy[j]] = [copy[j], copy[i]]
  }
  return copy
}

function resetWordsPool() {
  remainingWordsPool.value = shuffleArray(parsedWords.value)
}

function resetStudentsPool() {
  calledStudentsPool.value = []
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
  currentWord.value = ''
  currentWordImage.value = ''

  // Initialize fresh non-repeating pools
  resetWordsPool()
  resetStudentsPool()

  // Pre-seed cache with any custom image links provided in Setup Bank
  if (wordCustomLinkMap.value) {
    for (const [w, link] of Object.entries(wordCustomLinkMap.value)) {
      if (link) {
        const safe = getSafeImageUrl(link)
        wordImageCache.value[w] = safe
        wordImageCandidates.value[w] = [safe]
      }
    }
  }

  // Prefetch images in background for instant display during gameplay
  prefetchImages()

  switchView('WHEEL_VIEW')
}

function startGuessingCurrentWord() {
  if (!currentExplainer.value) return

  // Keep previous question/word if continuing from time-up or re-spin (រក្សាសំណួរមុនដដែល)
  if (!currentWord.value) {
    if (remainingWordsPool.value.length === 0) {
      resetWordsPool()
    }
    // Pull unique word without replacement from shuffled pool
    const nextWord = remainingWordsPool.value.pop()
    currentWord.value = nextWord || (parsedWords.value[0] || 'កុំព្យូទ័រ')
  }
  isWordVisible.value = true

  // Load auto-fetched image for this word immediately
  loadCurrentImage(currentWord.value)

  switchView('GUESSING_VIEW')
}

/* ── Timer Controls ── */
function startTimer() {
  stopTimer()
  if (timeUpTimeoutId) {
    clearTimeout(timeUpTimeoutId)
    timeUpTimeoutId = null
  }
  isTimeUpBannerVisible.value = false
  timerSeconds.value = timerMaxSeconds
  isTimerPaused.value = false
  syncRemoteState()

  timerInterval = setInterval(() => {
    if (!isTimerPaused.value) {
      timerSeconds.value--

      // Sound countdown alert when 5, 4, 3, 2, 1 seconds remain
      if (timerSeconds.value <= 5 && timerSeconds.value > 0) {
        playCountdownBeep(timerSeconds.value)
      }

      // Sync state: every second when <= 5, otherwise every 4 seconds
      if (timerSeconds.value <= 5 || timerSeconds.value % 4 === 0) {
        syncRemoteState()
      }

      if (timerSeconds.value <= 0) {
        stopTimer()
        playTimeUpSound()
        isTimeUpBannerVisible.value = true
        syncRemoteState()

        timeUpTimeoutId = setTimeout(() => {
          isTimeUpBannerVisible.value = false
          currentExplainer.value = '' // Clear explainer so teacher spins wheel for new student
          // IMPORTANT: currentWord.value is PRESERVED so the new student gets the same question/word!
          switchView('WHEEL_VIEW')
        }, 1300)
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
  if (currentView.value !== 'GUESSING_VIEW') return
  stopTimer()

  roundWordsHistory.value.push({
    word: currentWord.value,
    image: currentWordImage.value,
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
    currentWord.value = ''
    currentWordImage.value = ''
    currentExplainer.value = ''
    switchView('ROUND_SUMMARY_VIEW')
    if (currentScore.value === wordsPerRound.value) {
      triggerFullScreenCelebration()
    }
  } else {
    // Switch directly to WHEEL_VIEW without any delay or flashing of empty card
    currentExplainer.value = ''
    currentWord.value = ''
    currentWordImage.value = ''
    switchView('WHEEL_VIEW')
  }
}

function playNextRound() {
  currentWordIndex.value = 0
  currentScore.value = 0
  roundWordsHistory.value = []
  currentExplainer.value = ''
  currentWord.value = ''
  currentWordImage.value = ''
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
  if (showRemoteModal.value) {
    showRemoteModal.value = false
  }
  isRemoteConnected.value = true

  switch (action) {
    case 'SPIN':
      if (currentView.value === 'WHEEL_VIEW') {
        if (!isSpinning.value) {
          if (currentExplainer.value) {
            handleRespin()
          } else {
            triggerSpin()
          }
        }
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

    case 'CYCLE_IMAGE':
      cycleNextImage()
      break

    case 'TOGGLE_CLUE':
      isClueVisible.value = !isClueVisible.value
      syncRemoteState()
      break
  }
}

function syncRemoteState() {
  if (!remotePin.value) return
  const state = {
    view: currentView.value,
    currentWord: currentWord.value,
    currentWordImage: currentWordImage.value,
    currentWordClue: currentWordClue.value,
    isClueVisible: isClueVisible.value,
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

const DEMO_IMAGE_LINKS = [
  'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&auto=format&fit=crop', // 1. Computer
  'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800&auto=format&fit=crop', // 2. Internet
  'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=800&auto=format&fit=crop', // 3. Keyboard
  'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=800&auto=format&fit=crop', // 4. Mouse
  'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?w=800&auto=format&fit=crop', // 5. Printer
  'https://images.unsplash.com/photo-1511707171634-5f897ff025a5?w=800&auto=format&fit=crop', // 6. Phone
  'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&auto=format&fit=crop', // 7. Web Browser
  'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&auto=format&fit=crop', // 8. Source Code
  'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop', // 9. Database
  'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=800&auto=format&fit=crop', // 10. Monitor
  'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=800&auto=format&fit=crop', // 11. Social Media
  'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=800&auto=format&fit=crop'  // 12. Cybersecurity
]

function loadDemoImageLinks() {
  rawImageLinksText.value = DEMO_IMAGE_LINKS.join('\n')
}

function goBackToDashboard(e) {
  if (e) {
    if (typeof e.preventDefault === 'function') e.preventDefault()
    if (typeof e.stopPropagation === 'function') e.stopPropagation()
  }

  stopTimer()
  if (wheelAnimationId) {
    cancelAnimationFrame(wheelAnimationId)
    wheelAnimationId = null
  }
  if (remotePollInterval) {
    clearInterval(remotePollInterval)
    remotePollInterval = null
  }

  try {
    if (document.fullscreenElement && typeof document.exitFullscreen === 'function') {
      document.exitFullscreen().catch(() => {})
    }
  } catch (err) {}

  try {
    if (router && typeof router.push === 'function') {
      router.push('/admin/dashboard').catch(() => {
        window.location.href = '/admin/dashboard'
      })
    } else {
      window.location.href = '/admin/dashboard'
    }
  } catch (err) {
    window.location.href = '/admin/dashboard'
  }

  setTimeout(() => {
    if (typeof window !== 'undefined' && window.location.pathname.includes('/lucky-wheel')) {
      window.location.href = '/admin/dashboard'
    }
  }, 120)
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
    } else if (e.key.toLowerCase() === 'c') {
      e.preventDefault()
      isClueVisible.value = !isClueVisible.value
      syncRemoteState()
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
  if (timeUpTimeoutId) clearTimeout(timeUpTimeoutId)
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
