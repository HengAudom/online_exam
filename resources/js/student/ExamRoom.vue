<template>
  <div class="h-screen w-screen overflow-hidden bg-slate-100 flex flex-col select-none relative font-sans" @contextmenu.prevent>

    <!-- ── Anti-Cheat Overlay (Tab / Window Focus Lost) ──────────── -->
    <div
      v-if="isBlurred && examStarted && !isSubmitted"
      class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex flex-col items-center justify-center text-white p-6 text-center animate-fade-in"
    >
      <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-400 mb-4 border border-amber-500/30">
        <span class="material-symbols-outlined text-4xl">visibility_off</span>
      </div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ t.examPaused }}</h2>
      <p class="text-slate-300 text-sm mt-2 max-w-md leading-relaxed">
        {{ t.examPausedDesc }}
      </p>

      <div class="mt-4 px-3 py-1.5 rounded-full bg-white/10 text-xs font-semibold text-amber-300 border border-white/10">
        {{ t.interruptionCount }}: {{ interruptions }}
      </div>

      <div class="mt-6">
        <Button
          variant="primary"
          size="lg"
          icon="play_arrow"
          @click="resumeFocus"
        >
          {{ t.returnToExam }}
        </Button>
      </div>
    </div>

    <!-- ── Screen 1: Exam Lobby (Pre-Check Screen) ────────────────── -->
    <div v-if="!examStarted && !loading && !loadError" class="flex-1 flex items-center justify-center p-4 sm:p-8 overflow-y-auto">
      <Card padding="lg" class="max-w-xl w-full shadow-soft-xl border-slate-200">
        <!-- Header -->
        <div class="text-center pb-6 border-b border-slate-100">
          <div class="mx-auto flex h-16 w-16 items-center justify-center mb-3">
            <img :src="settings.logoUrl || '/logo.png'" alt="Logo" class="w-full h-full object-contain drop-shadow-xs" />
          </div>
          <span class="text-xs font-bold uppercase tracking-wider text-blue-600">{{ t.preExamCheck }}</span>
          <h1 class="text-2xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ examData?.testName }}</h1>
          <p class="text-xs text-slate-500 mt-1">{{ examData?.skill }} · {{ examData?.group || 'All Groups' }}</p>
        </div>

        <!-- Instructions & Checklist -->
        <div class="py-6 space-y-4">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.importantConditions }}</h4>

          <div class="grid grid-cols-2 gap-3 text-xs font-semibold text-slate-700">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
              <span class="material-symbols-outlined text-blue-600 text-lg">format_list_numbered</span>
              <span>{{ examData?.questions?.length || 0 }} {{ t.questions }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
              <span class="material-symbols-outlined text-blue-600 text-lg">timer</span>
              <span>{{ examData?.durationMinutes }} {{ t.minutesDuration }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
              <span class="material-symbols-outlined text-emerald-600 text-lg">cloud_done</span>
              <span>{{ t.autosaveEnabled }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
              <span class="material-symbols-outlined text-amber-600 text-lg">security</span>
              <span>{{ t.singleAttemptRule }}</span>
            </div>
          </div>

          <!-- System Readiness -->
          <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 text-xs text-blue-900 space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-blue-800">
              <span class="material-symbols-outlined text-base">verified</span>
              <span>{{ t.systemCheckPassed }}</span>
            </div>
            <p class="text-blue-700 leading-relaxed">
              {{ t.timerNotice }}
            </p>
          </div>
        </div>

        <!-- Start CTA -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
          <Button variant="outline" icon="arrow_back" @click="backToDashboard">
            {{ t.cancel }}
          </Button>
          <Button variant="primary" size="lg" icon="play_arrow" @click="showStartConfirm = true">
            {{ t.startExamCTA }}
          </Button>
        </div>
      </Card>

      <!-- Start Confirmation Dialog -->
      <ConfirmDialog
        v-model="showStartConfirm"
        :title="t.readyToStartTitle"
        :message="t.readyToStartDesc"
        :confirm-text="t.startNow"
        :cancel-text="t.cancel"
        confirm-variant="primary"
        icon="timer"
        @confirm="confirmStartExam"
      />
    </div>

    <!-- ── Loading / Error / Submitting States ─────────────────────── -->
    <div v-else-if="loading" class="flex-1 flex flex-col items-center justify-center gap-4 text-blue-600">
      <span class="material-symbols-outlined animate-spin text-5xl">progress_activity</span>
      <p class="font-bold text-base text-slate-700">{{ t.loadingExam }}</p>
    </div>

    <div v-else-if="loadError" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
      <div
        :class="[
          'flex h-16 w-16 items-center justify-center rounded-2xl mb-4 border',
          isAlreadyCompleted
            ? 'bg-emerald-50 text-emerald-600 border-emerald-200'
            : 'bg-amber-50 text-amber-500 border-amber-100'
        ]"
      >
        <span class="material-symbols-outlined text-4xl">
          {{ isAlreadyCompleted ? 'verified' : 'schedule' }}
        </span>
      </div>
      <h3 class="text-lg font-bold text-slate-800">
        {{ isAlreadyCompleted ? (lang === 'kh' ? 'ការប្រឡងបានបញ្ចប់រួចរាល់' : 'Exam Already Completed') : t.unableToStart }}
      </h3>
      <p class="text-sm text-slate-500 mt-1 max-w-md leading-relaxed">{{ loadError }}</p>
      <div class="mt-6 flex items-center gap-3">
        <Button variant="primary" icon="dashboard" @click="backToDashboard">{{ t.backToDashboard }}</Button>
        <Button v-if="!isAlreadyCompleted" variant="outline" icon="refresh" @click="loadExam">{{ t.retry }}</Button>
      </div>
    </div>

    <div v-else-if="isSubmitting" class="flex-1 flex flex-col items-center justify-center p-6 text-center gap-4">
      <span class="material-symbols-outlined animate-spin text-5xl text-blue-600">progress_activity</span>
      <h2 class="text-xl font-extrabold text-slate-900">{{ t.submittingExamTitle }}</h2>
      <p class="text-sm text-slate-500 max-w-sm">{{ t.submittingNotice }}</p>
    </div>

    <div v-else-if="isSubmitted" class="flex-1 flex flex-col items-center justify-center p-6 text-center animate-fade-in">
      <div class="flex h-16 w-16 items-center justify-center rounded-2xl mb-4 border bg-emerald-50 text-emerald-600 border-emerald-200 shadow-xs">
        <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">verified</span>
      </div>
      <h3 class="text-xl font-bold text-slate-800">
        {{ t.examSubmittedTitle }}
      </h3>
      <p class="text-sm text-slate-500 mt-2 max-w-md leading-relaxed">
        {{ lang === 'kh' ? 'ការប្រឡងរបស់អ្នកត្រូវបានកត់ត្រា និងរក្សាទុកដោយជោគជ័យ។ អ្នកអាចត្រឡប់ទៅកាន់ផ្ទាំងដើមវិញ។' : 'Your assessment has been submitted and recorded securely. You can now return to your dashboard.' }}
      </p>

      <!-- Telegram Instant Result Status Banner -->
      <div v-if="telegramSent || examData?.studentTelegramLinked" class="mt-4 px-4 py-2.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 inline-flex items-center gap-2 max-w-md shadow-2xs">
        <span class="material-symbols-outlined text-base text-emerald-600">send</span>
        <span>{{ lang === 'kh' ? '📩 លទ្ធផល និងពិន្ទុរបស់អ្នក ត្រូវបានផ្ញើជូនទៅកាន់ Telegram របស់អ្នករួចរាល់ហើយ!' : 'Your score breakdown has been sent to your Telegram!' }}</span>
      </div>
      <div v-else-if="examData?.telegramConnectUrl" class="mt-4 px-4 py-2.5 rounded-2xl bg-blue-50 border border-blue-200 text-xs font-semibold text-blue-800 inline-flex items-center gap-2 max-w-md shadow-2xs">
        <span class="material-symbols-outlined text-base text-blue-600">notifications_active</span>
        <span>{{ lang === 'kh' ? 'ចង់ទទួលលទ្ធផលលើ Telegram?' : 'Want scores in Telegram?' }}</span>
        <a :href="examData.telegramConnectUrl" target="_blank" class="text-blue-700 underline font-bold hover:text-blue-900">
          {{ lang === 'kh' ? 'ភ្ជាប់ Telegram ឥឡូវនេះ' : 'Connect Telegram' }}
        </a>
      </div>

      <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <Button variant="primary" size="lg" icon="dashboard" @click="backToDashboard">
          {{ t.backToDashboard }}
        </Button>
        <Button
          v-if="submissionId"
          variant="outline"
          size="lg"
          icon="analytics"
          @click="router.push(`/student/results/${submissionId}`)"
        >
          {{ lang === 'kh' ? 'ពិនិត្យលទ្ធផល' : 'View Results' }}
        </Button>
      </div>
    </div>

    <!-- ── Screen 2: Focus Mode Exam Room ────────────────────────── -->
    <div v-else class="flex-1 flex flex-col h-full min-h-0 overflow-hidden">

      <!-- Exam Topbar Header -->
      <header class="bg-slate-900 text-white px-4 sm:px-8 py-3 shrink-0 flex items-center justify-between border-b border-slate-800 shadow-soft-xs z-30">
        <!-- Exam Title & Mobile Navigator Trigger -->
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            class="md:hidden p-1.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white"
            title="Question Navigator"
            @click="isMobileNavOpen = true"
          >
            <span class="material-symbols-outlined text-xl">list_alt</span>
          </button>

          <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wider text-blue-400 leading-none">
              Exam in Progress
            </p>
            <h1 class="text-sm sm:text-base font-extrabold text-white truncate mt-0.5">
              {{ examData.testName }}
            </h1>
          </div>
        </div>

        <!-- Center Autosave Indicator -->
        <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800/80 text-xs font-semibold text-slate-300 border border-slate-700/60">
          <span
            class="material-symbols-outlined text-sm"
            :class="autosaveState === 'saving' ? 'animate-spin text-amber-400' : 'text-emerald-400'"
            style="font-variation-settings: 'FILL' 1;"
          >
            {{ autosaveState === 'saving' ? 'sync' : 'check_circle' }}
          </span>
          <span>{{ autosaveState === 'saving' ? t.savingAnswer : t.autoSaved }}</span>
        </div>

        <!-- Timer & Controls -->
        <div class="flex items-center gap-3 shrink-0">
          <LangSwitcher variant="dark" />

          <!-- Dynamic Timer Badge -->
          <div
            :class="[
              'flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-sm font-extrabold transition-all duration-300',
              timerState === 'critical'
                ? 'bg-red-600 text-white animate-pulse shadow-soft-sm shadow-red-600/30'
                : timerState === 'warning'
                ? 'bg-amber-500 text-slate-950 font-extrabold'
                : 'bg-slate-800 text-white border border-slate-700'
            ]"
          >
            <span class="material-symbols-outlined text-base">timer</span>
            <span class="tracking-wide font-mono text-base">{{ formattedTime }}</span>
          </div>
        </div>
      </header>

      <!-- Exam Body Split Layout (Fixed height, independent scroll) -->
      <div class="flex-1 flex overflow-hidden min-h-0">

        <!-- ── Desktop Question Navigator Sidebar (Scrolls within itself) ── -->
        <aside class="hidden md:flex w-72 lg:w-80 shrink-0 bg-white border-r border-slate-200/90 flex-col justify-between shadow-soft-xs h-full min-h-0">
          <!-- Navigator Header -->
          <div class="p-3.5 border-b border-slate-100 space-y-2.5 shrink-0">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold uppercase tracking-wider text-slate-400">{{ t.navigator }}</span>
              <strong class="text-blue-700 font-extrabold">
                {{ answeredCount }} / {{ examData.questions.length }} {{ t.answered }}
              </strong>
            </div>

            <!-- Progress Bar -->
            <ProgressBar
              :value="answeredCount"
              :max="examData.questions.length"
              variant="primary"
              size="sm"
            />

            <!-- Filter Chips (All / Unanswered / Flagged) -->
            <div class="flex items-center gap-1 pt-0.5">
              <button
                v-for="filter in ['all', 'unanswered', 'flagged']"
                :key="filter"
                type="button"
                :class="[
                  'flex-1 py-1 text-[11px] font-bold rounded-lg transition-colors capitalize text-center cursor-pointer',
                  navFilter === filter
                    ? 'bg-blue-50 text-blue-700 border border-blue-200/60'
                    : 'text-slate-500 hover:bg-slate-100'
                ]"
                @click="navFilter = filter"
              >
                {{ filter === 'all' ? t.all : filter === 'unanswered' ? t.unanswered : t.flagged }}
              </button>
            </div>
          </div>

          <!-- Question Grid / List (Independent smooth scrolling) -->
          <div class="flex-1 overflow-y-auto p-2.5 grid grid-cols-5 gap-1.5 content-start min-h-0">
            <button
              v-for="(q, idx) in examData.questions"
              :key="q.id"
              v-show="shouldShowInNav(idx)"
              type="button"
              :class="[
                'h-9 rounded-xl font-extrabold text-xs flex items-center justify-center transition-all duration-150 cursor-pointer relative select-none',
                idx === currentIndex
                  ? 'bg-blue-600 text-white shadow-soft-sm shadow-blue-600/30 ring-2 ring-blue-600/20'
                  : selectedAnswers[idx]
                  ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100'
                  : flagged[idx]
                  ? 'bg-amber-50 text-amber-800 border border-amber-200/80 hover:bg-amber-100'
                  : 'bg-slate-50 text-slate-700 border border-slate-200/80 hover:bg-slate-100'
              ]"
              @click="goToQuestion(idx)"
            >
              <span>{{ getStudentQuestionNum(idx) }}</span>
              <span
                v-if="flagged[idx]"
                class="material-symbols-outlined text-[9px] absolute top-0.5 right-0.5 text-amber-500"
                style="font-variation-settings: 'FILL' 1;"
              >
                flag
              </span>
            </button>
          </div>

          <!-- Submit Action in Sidebar -->
          <div class="p-3 border-t border-slate-100 bg-slate-50/60 shrink-0">
            <Button
              variant="primary"
              full-width
              size="md"
              icon="send"
              @click="openSubmitConfirm"
            >
              {{ t.finishAndSubmit }}
            </Button>
          </div>
        </aside>

        <!-- ── Main Question View Area ───────────────────────────── -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 flex flex-col justify-between h-full min-h-0 bg-slate-50/50">
          
          <!-- When Question Has a Reading Passage: Split-Screen Side-by-Side View -->
          <div v-if="currentQuestion.passage" class="max-w-7xl w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
            
            <!-- Left Panel: Dedicated Reading Passage Card (Sticky & Scrollable) -->
            <div class="lg:col-span-6 xl:col-span-7 bg-white rounded-2xl border border-slate-200/90 shadow-soft-sm overflow-hidden flex flex-col max-h-[60vh] lg:max-h-[calc(100vh-160px)]">
              <!-- Passage Header -->
              <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                  <div class="h-8 w-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-soft-xs shrink-0">
                    <span class="material-symbols-outlined text-lg">menu_book</span>
                  </div>
                  <div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-blue-950">
                      {{ lang === 'kh' ? 'អត្ថបទអាន' : 'Reading Passage' }}
                    </h3>
                    <p class="text-[10px] text-blue-700 font-bold uppercase tracking-wider">
                      {{ lang === 'kh' ? 'អានអត្ថបទនេះដើម្បីឆ្លើយសំណួរ' : 'Read passage to answer questions' }}
                    </p>
                  </div>
                </div>

                <!-- Font Size Controls -->
                <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-blue-200/60 shadow-soft-xs">
                  <button
                    type="button"
                    class="h-6 w-6 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer select-none"
                    title="Smaller text"
                    @click="passageFontSize = passageFontSize === 'lg' ? 'base' : 'sm'"
                  >
                    A-
                  </button>
                  <button
                    type="button"
                    class="h-6 w-6 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer select-none"
                    title="Larger text"
                    @click="passageFontSize = passageFontSize === 'sm' ? 'base' : 'lg'"
                  >
                    A+
                  </button>
                </div>
              </div>

              <!-- Passage Content Body -->
              <div
                :class="[
                  'p-4 sm:p-6 overflow-y-auto leading-relaxed text-slate-800 font-normal selection:bg-blue-100 selection:text-blue-900',
                  passageFontSize === 'sm' ? 'text-xs' : passageFontSize === 'lg' ? 'text-base sm:text-lg leading-loose' : 'text-sm sm:text-base'
                ]"
              >
                <div class="whitespace-pre-line break-words leading-relaxed font-sans">{{ currentQuestion.passage }}</div>
              </div>
            </div>

            <!-- Right Panel: Question & Answers -->
            <div class="lg:col-span-6 xl:col-span-5 space-y-4">
              <!-- Question Card -->
              <Card padding="lg" class="shadow-soft-sm border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
                      {{ t.question }} {{ getStudentQuestionNum(currentIndex) }} {{ t.of }} {{ examData.questions.length }}
                    </span>
                    <span v-if="currentQuestion.isExample" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-extrabold border border-purple-200">
                      <span class="material-symbols-outlined text-[11px]">help_center</span>
                      {{ lang === 'kh' ? 'លំហាត់គំរូ' : 'Example' }}
                    </span>
                    <span v-else-if="currentQuestion.passage" class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-extrabold border border-blue-100">
                      Reading
                    </span>
                  </div>

                  <!-- Flag for review button -->
                  <button
                    type="button"
                    :class="[
                      'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer',
                      flagged[currentIndex]
                        ? 'bg-amber-100 text-amber-800 border border-amber-200'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    ]"
                    @click="toggleFlag"
                  >
                    <span
                      class="material-symbols-outlined text-sm"
                      :style="flagged[currentIndex] ? 'font-variation-settings: \'FILL\' 1;' : ''"
                    >
                      flag
                    </span>
                    <span>{{ flagged[currentIndex] ? t.flagged : t.flagForReview }}</span>
                  </button>
                </div>

                <!-- Question Statement -->
                <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-relaxed" v-html="renderMath(currentQuestion.text)"></h2>
              </Card>

              <!-- Example Question Notice Banner -->
              <div v-if="currentQuestion.isExample" class="p-2.5 rounded-xl bg-purple-50/90 border border-purple-200 text-purple-900 text-xs font-semibold flex items-center gap-2 animate-fade-in">
                <span class="material-symbols-outlined text-base text-purple-600 shrink-0">lock</span>
                <span>{{ lang === 'kh' ? 'នេះជាលំហាត់គំរូ ចម្លើយត្រូវបានកំណត់ជូនជាស្ថាពរ (មិនអាចកែប្រែបានទេ)' : 'This is an example exercise. The answer is fixed as a demonstration.' }}</span>
              </div>

              <!-- Option Cards (A, B, C, D) -->
              <div class="space-y-2.5">
                <div
                  v-for="(answer, aIdx) in currentQuestion.answers"
                  :key="answer.id"
                  :class="[
                    'p-3.5 sm:p-4 rounded-2xl border-2 transition-all duration-150 flex items-center gap-3 select-none',
                    currentQuestion.isExample
                      ? selectedAnswers[currentIndex] === answer.id
                        ? 'border-purple-600 bg-purple-50/80 shadow-soft-sm cursor-default ring-2 ring-purple-600/10'
                        : 'border-slate-200 bg-slate-50/60 opacity-60 cursor-not-allowed'
                      : selectedAnswers[currentIndex] === answer.id
                      ? 'border-blue-600 bg-blue-50/70 shadow-soft-sm ring-2 ring-blue-600/10 cursor-pointer'
                      : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/60 cursor-pointer'
                  ]"
                  @click="onAnswerSelect(answer.id)"
                >
                  <!-- Option Letter Badge -->
                  <span
                    :class="[
                      'h-7 w-7 rounded-xl flex items-center justify-center text-xs font-extrabold shrink-0 transition-colors',
                      currentQuestion.isExample && selectedAnswers[currentIndex] === answer.id
                        ? 'bg-purple-600 text-white shadow-soft-xs'
                        : selectedAnswers[currentIndex] === answer.id
                        ? 'bg-blue-600 text-white shadow-soft-xs'
                        : 'bg-slate-100 text-slate-600'
                    ]"
                  >
                    {{ ['A', 'B', 'C', 'D'][aIdx] }}
                  </span>

                  <!-- Option Text -->
                  <span class="flex-1 text-xs sm:text-sm font-semibold text-slate-800 leading-snug" v-html="renderMath(answer.text)"></span>

                  <!-- Selected Indicator -->
                  <template v-if="selectedAnswers[currentIndex] === answer.id">
                    <span
                      v-if="currentQuestion.isExample"
                      class="material-symbols-outlined text-purple-700 text-xl shrink-0"
                      style="font-variation-settings: 'FILL' 1;"
                    >
                      lock
                    </span>
                    <span
                      v-else
                      class="material-symbols-outlined text-blue-600 text-xl shrink-0"
                      style="font-variation-settings: 'FILL' 1;"
                    >
                      check_circle
                    </span>
                  </template>
                  <span
                    v-else
                    class="material-symbols-outlined text-slate-200 text-xl shrink-0"
                  >
                    radio_button_unchecked
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Standard View for Questions WITHOUT Reading Passage -->
          <div v-else class="max-w-3xl w-full mx-auto space-y-5">
            <!-- Question Card -->
            <Card padding="lg" class="shadow-soft-sm">
              <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
                    {{ t.question }} {{ getStudentQuestionNum(currentIndex) }} {{ t.of }} {{ examData.questions.length }}
                  </span>
                  <span v-if="currentQuestion.isExample" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-extrabold border border-purple-200">
                    <span class="material-symbols-outlined text-[11px]">help_center</span>
                    {{ lang === 'kh' ? 'លំហាត់គំរូ' : 'Example' }}
                  </span>
                </div>

                <!-- Flag for review button -->
                <button
                  type="button"
                  :class="[
                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer',
                    flagged[currentIndex]
                      ? 'bg-amber-100 text-amber-800 border border-amber-200'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                  ]"
                  @click="toggleFlag"
                >
                  <span
                    class="material-symbols-outlined text-sm"
                    :style="flagged[currentIndex] ? 'font-variation-settings: \'FILL\' 1;' : ''"
                  >
                    flag
                  </span>
                  <span>{{ flagged[currentIndex] ? t.flagged : t.flagForReview }}</span>
                </button>
              </div>

              <!-- Question Statement -->
              <h2 class="text-lg sm:text-xl font-bold text-slate-900 leading-relaxed" v-html="renderMath(currentQuestion.text)"></h2>
            </Card>

            <!-- Example Question Notice Banner -->
            <div v-if="currentQuestion.isExample" class="p-3 rounded-xl bg-purple-50/90 border border-purple-200 text-purple-900 text-xs font-semibold flex items-center gap-2 animate-fade-in">
              <span class="material-symbols-outlined text-base text-purple-600 shrink-0">lock</span>
              <span>{{ lang === 'kh' ? 'នេះជាលំហាត់គំរូ ចម្លើយត្រូវបានកំណត់ជូនជាស្ថាពរ (មិនអាចកែប្រែបានទេ)' : 'This is an example exercise. The answer is fixed as a demonstration.' }}</span>
            </div>

            <!-- Option Cards (A, B, C, D) -->
            <div class="space-y-3">
              <div
                v-for="(answer, aIdx) in currentQuestion.answers"
                :key="answer.id"
                :class="[
                  'p-4 sm:p-4.5 rounded-2xl border-2 transition-all duration-150 flex items-center gap-3.5 select-none',
                  currentQuestion.isExample
                    ? selectedAnswers[currentIndex] === answer.id
                      ? 'border-purple-600 bg-purple-50/80 shadow-soft-sm cursor-default ring-2 ring-purple-600/10'
                      : 'border-slate-200 bg-slate-50/60 opacity-60 cursor-not-allowed'
                    : selectedAnswers[currentIndex] === answer.id
                    ? 'border-blue-600 bg-blue-50/70 shadow-soft-sm ring-2 ring-blue-600/10 cursor-pointer'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/60 cursor-pointer'
                ]"
                @click="onAnswerSelect(answer.id)"
              >
                <!-- Option Letter Badge -->
                <span
                  :class="[
                    'h-8 w-8 rounded-xl flex items-center justify-center text-xs font-extrabold shrink-0 transition-colors',
                    currentQuestion.isExample && selectedAnswers[currentIndex] === answer.id
                      ? 'bg-purple-600 text-white shadow-soft-xs'
                      : selectedAnswers[currentIndex] === answer.id
                      ? 'bg-blue-600 text-white shadow-soft-xs'
                      : 'bg-slate-100 text-slate-600'
                  ]"
                >
                  {{ ['A', 'B', 'C', 'D'][aIdx] }}
                </span>

                <!-- Option Text -->
                <span class="flex-1 text-sm sm:text-base font-semibold text-slate-800 leading-snug" v-html="renderMath(answer.text)"></span>

                <!-- Selected Indicator -->
                <template v-if="selectedAnswers[currentIndex] === answer.id">
                  <span
                    v-if="currentQuestion.isExample"
                    class="material-symbols-outlined text-purple-700 text-2xl shrink-0"
                    style="font-variation-settings: 'FILL' 1;"
                  >
                    lock
                  </span>
                  <span
                    v-else
                    class="material-symbols-outlined text-blue-600 text-2xl shrink-0"
                    style="font-variation-settings: 'FILL' 1;"
                  >
                    check_circle
                  </span>
                </template>
                <span
                  v-else
                  class="material-symbols-outlined text-slate-200 text-2xl shrink-0"
                >
                  radio_button_unchecked
                </span>
              </div>
            </div>
          </div>

          <!-- Bottom Navigation Controls -->
          <div :class="currentQuestion.passage ? 'max-w-7xl' : 'max-w-3xl'" class="w-full mx-auto pt-4 flex items-center justify-between gap-3 shrink-0">
            <Button
              variant="outline"
              size="md"
              icon="arrow_back"
              :disabled="currentIndex === 0"
              @click="goPrevious"
            >
              {{ t.previous }}
            </Button>

            <!-- Mobile Submit Trigger -->
            <Button
              variant="primary"
              size="md"
              icon="send"
              class="md:hidden"
              @click="openSubmitConfirm"
            >
              {{ t.submit }}
            </Button>

            <Button
              variant="primary"
              size="md"
              trailing-icon="arrow_forward"
              :disabled="currentIndex === examData.questions.length - 1"
              @click="goNext"
            >
              {{ t.next }}
            </Button>
          </div>
        </main>
      </div>
    </div>

    <!-- ── Mobile Question Navigator Drawer ────────────────────────── -->
    <Drawer
      v-if="examData"
      v-model="isMobileNavOpen"
      :title="t.navigator"
      max-width="sm"
    >
      <div class="space-y-4">
        <div class="flex items-center justify-between text-xs">
          <span class="font-bold text-slate-600">{{ t.answered }}:</span>
          <strong class="text-blue-700 font-extrabold">{{ answeredCount }} / {{ examData.questions.length }}</strong>
        </div>

        <ProgressBar
          :value="answeredCount"
          :max="examData.questions.length"
          variant="primary"
          size="sm"
        />

        <div class="grid grid-cols-4 gap-2 pt-2">
          <button
            v-for="(q, idx) in examData.questions"
            :key="q.id"
            type="button"
            :class="[
              'h-11 rounded-xl font-bold text-xs flex flex-col items-center justify-center gap-0.5 cursor-pointer',
              idx === currentIndex
                ? 'bg-blue-600 text-white'
                : selectedAnswers[idx]
                ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                : flagged[idx]
                ? 'bg-amber-50 text-amber-800 border border-amber-200'
                : 'bg-slate-50 text-slate-700 border border-slate-200'
            ]"
            @click="goToQuestion(idx); isMobileNavOpen = false"
          >
            <span>{{ getStudentQuestionNum(idx) }}</span>
          </button>
        </div>

        <div class="pt-4 border-t border-slate-100">
          <Button
            variant="primary"
            full-width
            size="lg"
            icon="send"
            @click="openSubmitConfirm(); isMobileNavOpen = false"
          >
            {{ t.finishAndSubmit }}
          </Button>
        </div>
      </div>
    </Drawer>

    <!-- ── Safe Submit Confirmation Modal ──────────────────────────── -->
    <Modal
      v-model="showSubmitModal"
      max-width="md"
      :show-close="false"
      :close-on-backdrop="false"
    >
      <div class="text-center py-2 space-y-4">
        <div
          :class="[
            'mx-auto flex h-14 w-14 items-center justify-center rounded-2xl',
            unansweredCount > 0 ? 'bg-amber-50 text-amber-500 border border-amber-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200'
          ]"
        >
          <span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 1;">
            {{ unansweredCount > 0 ? 'warning' : 'task_alt' }}
          </span>
        </div>

        <div>
          <h3 class="text-xl font-extrabold text-slate-900">
            {{ unansweredCount > 0 ? (lang === 'kh' ? 'ត្រូវតែឆ្លើយគ្រប់សំណួរជាមុនសិន' : 'All Questions Must Be Answered') : t.confirmSubmitTitle }}
          </h3>
          <div class="text-sm text-slate-500 mt-2 leading-relaxed space-y-2">
            <template v-if="lang === 'kh'">
              <div v-if="unansweredCount > 0" class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs font-semibold leading-relaxed">
                អ្នកបានឆ្លើយ <strong>{{ answeredCount }}</strong> ក្នុងចំណោម <strong>{{ examData?.questions?.length }}</strong> សំណួរ។<br />
                <span class="text-rose-600 font-extrabold block mt-1">
                  ⚠️ នៅសល់ {{ unansweredCount }} សំណួរទៀតមិនទាន់បានឆ្លើយ! ប្រព័ន្ធតម្រូវឱ្យឆ្លើយគ្រប់សំណួរទាំងអស់ ទើបអាចប្រគល់វិញ្ញាសាបាន។
                </span>
              </div>
              <div v-else class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs font-semibold leading-relaxed">
                ✓ អបអរសាទរ! អ្នកបានឆ្លើយគ្រប់ <strong>{{ examData?.questions?.length }}</strong> សំណួររួចរាល់ហើយ។
              </div>
            </template>
            <template v-else>
              <div v-if="unansweredCount > 0" class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs font-semibold leading-relaxed">
                You have answered <strong>{{ answeredCount }}</strong> of <strong>{{ examData?.questions?.length }}</strong> questions.<br />
                <span class="text-rose-600 font-extrabold block mt-1">
                  ⚠️ {{ unansweredCount }} question(s) remain unanswered! You must answer all questions before submitting.
                </span>
              </div>
              <div v-else class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs font-semibold leading-relaxed">
                ✓ Great job! All <strong>{{ examData?.questions?.length }}</strong> questions have been answered.
              </div>
            </template>
          </div>
          <p v-if="unansweredCount === 0" class="text-xs text-slate-400 mt-2">{{ t.irreversibleNotice }}</p>
        </div>

        <div class="pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2.5">
          <Button
            v-if="unansweredCount > 0"
            variant="primary"
            full-width
            size="lg"
            icon="edit_note"
            @click="jumpToFirstUnanswered"
          >
            {{ lang === 'kh' ? 'ត្រឡប់ទៅឆ្លើយសំណួរដែលនៅសល់' : 'Answer Remaining Questions' }}
          </Button>
          <template v-else>
            <Button variant="outline" full-width @click="showSubmitModal = false">
              {{ t.continueExam }}
            </Button>
            <Button variant="primary" full-width :loading="isSubmitting" @click="submitExam(false)">
              {{ t.confirmSubmit }}
            </Button>
          </template>
        </div>
      </div>
    </Modal>

    <!-- ── Leave Exam Warning Modal (Back Navigation Protection) ──── -->
    <ConfirmDialog
      v-model="showLeaveConfirm"
      :title="lang === 'kh' ? 'តើអ្នកពិតជាចង់ចាកចេញមែនទេ?' : 'Are you sure you want to leave?'"
      :message="lang === 'kh' ? '⚠️ ការប្រឡងរបស់អ្នកកំពុងដំណើរការនៅឡើយ! ប្រសិនបើអ្នកចាកចេញ ពេលវេលានៅតែបន្តរាប់ថយក្រោយ ហើយចម្លើយរបស់អ្នកត្រូវបានរក្សាទុកស្វ័យប្រវត្តិ។' : '⚠️ Your exam is currently in progress! If you leave now, the timer will keep running and your progress has been auto-saved.'"
      :confirm-text="lang === 'kh' ? 'ចាកចេញពីបន្ទប់ប្រឡង' : 'Leave Exam'"
      :cancel-text="lang === 'kh' ? 'បន្តធ្វើការប្រឡង' : 'Stay & Continue'"
      confirm-variant="danger"
      icon="warning"
      @confirm="confirmLeaveExam"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import ProgressBar from '../components/ui/ProgressBar.vue'
import Modal from '../components/ui/Modal.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Drawer from '../components/ui/Drawer.vue'
import LangSwitcher from '../components/LangSwitcher.vue'
import { useLang } from '../utils/useLang'
import { renderMath } from '../utils/mathRender'
import { useToast } from '../composables/useToast'
import { useSettings } from '../composables/useSettings'
import { useRealtimeSync, notifyRealtimeChange } from '../composables/useRealtimeSync'

const route = useRoute()
const router = useRouter()
const { lang } = useLang()
const { settings } = useSettings()
const { error: toastError } = useToast()

const testId = route.params.testId

const examStarted = ref(false)
const showStartConfirm = ref(false)
const showSubmitModal = ref(false)
const isMobileNavOpen = ref(false)
const showLeaveConfirm = ref(false)
const allowLeave = ref(false)
const pendingRoute = ref(null)

const confirmLeaveExam = () => {
  allowLeave.value = true
  showLeaveConfirm.value = false
  if (pendingRoute.value) {
    router.push(pendingRoute.value)
  } else {
    router.push('/student')
  }
}

onBeforeRouteLeave((to, _from, next) => {
  if (allowLeave.value || !examStarted.value || isSubmitted.value) {
    return next()
  }
  pendingRoute.value = to
  showLeaveConfirm.value = true
  next(false)
})

const handleBeforeUnload = (e) => {
  if (examStarted.value && !isSubmitted.value && !allowLeave.value) {
    e.preventDefault()
    e.returnValue = ''
    return ''
  }
}

const handlePopState = () => {
  if (examStarted.value && !isSubmitted.value && !allowLeave.value) {
    window.history.pushState(null, '', window.location.href)
    showLeaveConfirm.value = true
  }
}

const examData = ref(null)
const submissionId = ref(null)
const loading = ref(true)
const loadError = ref(null)
const isAlreadyCompleted = ref(false)
const completedSubmissionId = ref(null)
const isSubmitted = ref(false)
const isSubmitting = ref(false)
const telegramSent = ref(false)

const currentIndex = ref(0)
const selectedAnswers = ref([])
const flagged = ref([])
const isBlurred = ref(false)
const interruptions = ref(0)
const autosaveState = ref('saved') // 'saving' | 'saved'
const navFilter = ref('all') // 'all' | 'unanswered' | 'flagged'
const passageFontSize = ref('base') // 'sm' | 'base' | 'lg'

let timer = null
const remainingSeconds = ref(0)

const currentQuestion = computed(() =>
  examData.value?.questions?.[currentIndex.value] ?? { text: '', answers: [] }
)

const getStudentQuestionNum = (idx) => {
  if (!examData.value?.questions) return idx + 1
  const q = examData.value.questions[idx]
  if (q?.isExample) return 0
  let count = 0
  for (let i = idx; i >= 0; i--) {
    if (examData.value.questions[i]?.isExample) break
    count++
  }
  return count
}

const formattedTime = computed(() => {
  const total = Math.max(0, Math.floor(Number(remainingSeconds.value) || 0))
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)
  const seconds = Math.floor(total % 60)

  if (hours > 0) {
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
  }
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
})

const timerState = computed(() => {
  if (remainingSeconds.value <= 300) return 'critical' // < 5 min
  if (remainingSeconds.value <= 600) return 'warning'  // 5-10 min
  return 'normal'
})

const answeredCount = computed(() => selectedAnswers.value.filter(Boolean).length)
const unansweredCount = computed(() => (examData.value?.questions?.length ?? 0) - answeredCount.value)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      preExamCheck: 'ការផ្ទៀងផ្ទាត់មុនពេលចាប់ផ្ដើម',
      importantConditions: 'លក្ខខណ្ឌសំខាន់ៗ',
      questions: 'សំណួរ',
      minutesDuration: 'នាទី',
      autosaveEnabled: 'រក្សាទុកចម្លើយស្វ័យប្រវត្តិ',
      singleAttemptRule: 'អនុញ្ញាតធ្វើតែ ១ លើកគត់',
      systemCheckPassed: 'ប្រព័ន្ធបានត្រៀមរួចរាល់',
      timerNotice: 'នៅពេលចុចចាប់ផ្ដើម នាឡិកានឹងរាប់ថយក្រោយភ្លាមៗ។ សូមរក្សាការផ្តោតអារម្មណ៍លើផ្ទាំងប្រឡង។',
      cancel: 'បោះបង់',
      startExamCTA: 'ចូលបន្ទប់ប្រឡង',
      readyToStartTitle: 'ត្រៀមខ្លួនរួចរាល់ហើយឬនៅ?',
      readyToStartDesc: 'នាឡិកាកំណត់ពេលវេលានឹងចាប់ផ្ដើមភ្លាមៗពេលអ្នកបញ្ជាក់។ សូមកុំចាកចេញពីផ្ទាំងប្រឡង។',
      startNow: 'ចាប់ផ្ដើមឥឡូវនេះ',
      loadingExam: 'កំពុងទាញយកទិន្នន័យវិញ្ញាសាប្រឡង...',
      unableToStart: 'មិនអាចចាប់ផ្ដើមការប្រឡងបានទេ',
      backToDashboard: 'ត្រឡប់ទៅផ្ទាំងដើម',
      retry: 'ព្យាយាមម្តងទៀត',
      submittingExamTitle: 'កំពុងប្រគល់វិញ្ញាសា...',
      submittingNotice: 'សូមរង់ចាំបន្តិច កំពុងផ្ទៀងផ្ទាត់ និងកត់ត្រាចម្លើយរបស់អ្នក។',
      examSubmittedTitle: 'បានប្រគល់វិញ្ញាសាជោគជ័យ!',
      redirectingToResults: 'កំពុងបញ្ជូនទៅកាន់ទំព័រពិនិត្យលទ្ធផល...',
      savingAnswer: 'កំពុងរក្សាទុក...',
      autoSaved: 'រក្សាទុករួចរាល់ ✓',
      navigator: 'បញ្ជីសំណួរ',
      answered: 'បានឆ្លើយ',
      all: 'ទាំងអស់',
      unanswered: 'មិនទាន់ឆ្លើយ',
      flagged: 'បានចំណាំ',
      question: 'សំណួរ',
      of: 'នៃ',
      flagForReview: 'ចំណាំសំណួរទុកផ្ទៀងផ្ទាត់',
      previous: 'ថយក្រោយ',
      next: 'បន្ទាប់',
      submit: 'ប្រគល់វិញ្ញាសា',
      finishAndSubmit: 'បញ្ចប់ & ប្រគល់វិញ្ញាសា',
      confirmSubmitTitle: 'ប្រគល់វិញ្ញាសាប្រឡង?',
      irreversibleNotice: 'នៅពេលប្រគល់រួច ចម្លើយរបស់អ្នកនឹងត្រូវកត់ត្រាជាស្ថាពរ ហើយមិនអាចកែប្រែបានទៀតទេ។',
      continueExam: 'បន្តធ្វើវិញ្ញាសា',
      confirmSubmit: 'បញ្ជាក់ការប្រគល់',
      examPaused: 'ការប្រឡងត្រូវបានផ្អាក',
      examPausedDesc: 'ប្រព័ន្ធបានកត់សម្គាល់ឃើញថាផ្ទាំងប្រឡងបានបាត់បង់ការផ្តោតអារម្មណ៍ (ប្តូរផ្ទាំង ឬបើកកម្មវិធីផ្សេង)។',
      interruptionCount: 'ចំនួនលើកនៃការប្តូរផ្ទាំង',
      returnToExam: 'ត្រឡប់ទៅកាន់ការប្រឡង'
    }
  }
  return {
    preExamCheck: 'Pre-Assessment Verification',
    importantConditions: 'Key Conditions',
    questions: 'Questions',
    minutesDuration: 'Minutes Duration',
    autosaveEnabled: 'Auto-Save Active',
    singleAttemptRule: 'Single Attempt Permitted',
    systemCheckPassed: 'System Check Verified',
    timerNotice: 'Once started, the countdown timer will begin immediately. Maintain window focus during the session.',
    cancel: 'Cancel',
    startExamCTA: 'Enter Examination',
    readyToStartTitle: 'Ready to begin assessment?',
    readyToStartDesc: 'Your session timer will begin as soon as you confirm. Please ensure you have a stable environment.',
    startNow: 'Start Now',
    loadingExam: 'Loading assessment environment...',
    unableToStart: 'Unable to start examination',
    backToDashboard: 'Back to Dashboard',
    retry: 'Retry',
    submittingExamTitle: 'Submitting Assessment...',
    submittingNotice: 'Please wait while your answers are verified and recorded.',
    examSubmittedTitle: 'Assessment Submitted Successfully!',
    redirectingToResults: 'Redirecting to your result review...',
    savingAnswer: 'Saving...',
    autoSaved: 'Saved just now ✓',
    navigator: 'Question Navigator',
    answered: 'answered',
    all: 'All',
    unanswered: 'Unanswered',
    flagged: 'Flagged',
    question: 'Question',
    of: 'of',
    flagForReview: 'Flag for Review',
    previous: 'Previous',
    next: 'Next',
    submit: 'Submit Exam',
    finishAndSubmit: 'Finish & Submit Exam',
    confirmSubmitTitle: 'Submit Examination?',
    irreversibleNotice: 'Once submitted, your answers are finalized and cannot be modified.',
    continueExam: 'Continue Exam',
    confirmSubmit: 'Submit Exam',
    examPaused: 'Examination Paused',
    examPausedDesc: 'We detected that the exam window lost focus or was switched.',
    interruptionCount: 'Tab Switch Incidents',
    returnToExam: 'Return to Exam'
  }
})

const shouldShowInNav = (idx) => {
  if (navFilter.value === 'all') return true
  if (navFilter.value === 'unanswered') return !selectedAnswers.value[idx]
  if (navFilter.value === 'flagged') return flagged.value[idx]
  return true
}

const goToQuestion = (idx) => { currentIndex.value = idx }
const goNext = () => {
  if (currentIndex.value < examData.value.questions.length - 1) currentIndex.value++
}
const goPrevious = () => {
  if (currentIndex.value > 0) currentIndex.value--
}
const toggleFlag = () => {
  flagged.value[currentIndex.value] = !flagged.value[currentIndex.value]
}

const backToDashboard = () => router.push('/student')

const onAnswerSelect = async (answerId) => {
  // If this is an example question, prevent student from changing the answer
  if (currentQuestion.value?.isExample) return

  selectedAnswers.value[currentIndex.value] = answerId
  if (!submissionId.value) return

  autosaveState.value = 'saving'
  try {
    await axios.post('/api/exam/answer', {
      submissionId: submissionId.value,
      questionId: currentQuestion.value.id,
      selectedAnswerId: answerId
    })
    notifyRealtimeChange('exam_answer_saved')
    setTimeout(() => {
      autosaveState.value = 'saved'
    }, 400)
  } catch (e) {
    autosaveState.value = 'saved'
    if (e.response?.data?.isCompleted) {
      isSubmitted.value = true
      if (timer) clearInterval(timer)
    }
  }
}

let targetEndTimestamp = null

const syncTimer = () => {
  if (!targetEndTimestamp) return
  const now = Date.now()
  const diffMs = targetEndTimestamp - now
  const secLeft = Math.max(0, Math.ceil(diffMs / 1000))
  remainingSeconds.value = secLeft

  let endAt = null
  if (examData.value?.finishedAt) {
    endAt = new Date(examData.value.finishedAt)
  } else if (examData.value?.scheduledAt) {
    const start = new Date(examData.value.scheduledAt)
    endAt = new Date(start.getTime() + (Number(examData.value.durationMinutes) || 1) * 60000)
  }

  if ((endAt && new Date() > endAt) || secLeft <= 0) {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
    if (examData.value?.autoSubmitOnTimeout !== false && !isSubmitted.value && !isSubmitting.value) {
      submitExam(true)
    }
  }
}

const startTimer = () => {
  if (timer) clearInterval(timer)
  targetEndTimestamp = Date.now() + (remainingSeconds.value * 1000)
  syncTimer()
  timer = setInterval(syncTimer, 500)
}

const confirmStartExam = () => {
  showStartConfirm.value = false
  examStarted.value = true
  if (typeof window !== 'undefined') {
    window.history.pushState(null, '', window.location.href)
  }
  startTimer()
}

const jumpToFirstUnanswered = () => {
  showSubmitModal.value = false
  const firstUnansweredIdx = examData.value?.questions?.findIndex((q, idx) => !selectedAnswers.value[idx])
  if (firstUnansweredIdx !== undefined && firstUnansweredIdx !== -1) {
    currentIndex.value = firstUnansweredIdx
  }
}

const openSubmitConfirm = () => {
  if (unansweredCount.value > 0) {
    const firstUnansweredIdx = examData.value?.questions?.findIndex((q, idx) => !selectedAnswers.value[idx])
    if (firstUnansweredIdx !== undefined && firstUnansweredIdx !== -1) {
      currentIndex.value = firstUnansweredIdx
    }
  }
  showSubmitModal.value = true
}

const submitExam = async (forced = false) => {
  if (!forced && unansweredCount.value > 0) {
    showSubmitModal.value = true
    return
  }

  showSubmitModal.value = false
  if (!submissionId.value) {
    toastError(lang.value === 'kh' ? 'មិនមានទិន្នន័យការប្រឡងទេ សូមចូលម្តងទៀត' : 'Exam session missing. Please reload.')
    return
  }

  clearInterval(timer)
  isSubmitting.value = true

  const maxRetries = 3
  let lastError = null

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      const res = await axios.post(`/api/exam/${submissionId.value}/complete`, {
        interruptions: interruptions.value,
        forcedTimeout: forced
      })
      if (res.data?.submissionId) {
        submissionId.value = res.data.submissionId
      }
      if (res.data?.telegramSent) {
        telegramSent.value = true
      }
      isSubmitting.value = false
      isSubmitted.value = true
      notifyRealtimeChange('exam_submitted')
      return
    } catch (e) {
      lastError = e
      const status = e.response?.status
      if (status === 422) {
        // Backend validation error for unanswered questions
        toastError(e.response?.data?.message || (lang.value === 'kh' ? 'ត្រូវតែឆ្លើយគ្រប់សំណួរជាមុនសិន' : 'All questions must be answered.'))
        isSubmitting.value = false
        showSubmitModal.value = true
        return
      }
      if (status === 401 || status === 404) break
      if (attempt < maxRetries) {
        await new Promise(resolve => setTimeout(resolve, attempt * 1000))
      }
    }
  }

  isSubmitting.value = false
  const status = lastError?.response?.status
  if (status === 401) {
    toastError('Session expired. Please log in again.')
    router.push('/login')
  } else {
    toastError('Failed to submit exam. Please check your connection and retry.')
  }
}

// ── Anti-Cheat Measures ───────────────────────────────────────────────────────
const blockKeys = (e) => {
  if (e.key === 'PrintScreen' || e.code === 'PrintScreen') { e.preventDefault(); return false; }
  if (e.ctrlKey && (e.key === 'c' || e.key === 'C')) { e.preventDefault(); return false; }
  if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) { e.preventDefault(); return false; }
  if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) { e.preventDefault(); return false; }
}

const recordFocusLoss = async () => {
  if (examData.value?.antiCheatPause === false) return
  if (!examStarted.value || isSubmitted.value || !submissionId.value) return
  if (isBlurred.value) return

  isBlurred.value = true
  interruptions.value++

  try {
    await axios.post('/api/exam/interruption', {
      submissionId: submissionId.value,
      interruptions: interruptions.value
    })
    notifyRealtimeChange('exam_interruption')
  } catch (e) {}
}

const onVisibilityChange = () => {
  if (document.hidden) {
    recordFocusLoss()
  } else {
    syncTimer()
  }
}

const onWindowBlur = () => {
  recordFocusLoss()
}

const resumeFocus = () => {
  isBlurred.value = false
  syncTimer()
}

const loadExam = async () => {
  loading.value = true
  loadError.value = null
  isAlreadyCompleted.value = false
  completedSubmissionId.value = null

  try {
    const res = await axios.get(`/api/exam/${testId}/start`)
    examData.value = res.data
    submissionId.value = res.data.submissionId
    interruptions.value = Number(res.data.interruptions || 0)
    
    // Restore previously saved answers or pre-fill example question answers
    if (res.data.savedAnswers && typeof res.data.savedAnswers === 'object') {
      selectedAnswers.value = res.data.questions.map(q => res.data.savedAnswers[q.id] || (q.isExample ? q.defaultAnswerId : null))
    } else {
      selectedAnswers.value = res.data.questions.map(q => q.isExample ? q.defaultAnswerId : null)
    }

    flagged.value = Array(res.data.questions.length).fill(false)
    const durationMin = Math.max(1, Number(res.data.durationMinutes) || 1)
    const maxSec = durationMin * 60
    const rawSec = res.data.remainingSeconds !== undefined ? Number(res.data.remainingSeconds) : maxSec
    remainingSeconds.value = Math.max(0, Math.min(maxSec, Math.floor(rawSec || 0)))

    // If the exam was already started previously, skip lobby and resume timer immediately
    if (res.data.isStarted || (res.data.savedAnswers && Object.keys(res.data.savedAnswers).length > 0)) {
      examStarted.value = true
      startTimer()
    }
  } catch (e) {
    loadError.value = e.response?.data?.message || 'Failed to load exam. Please try again.'
    if (e.response?.data?.alreadyCompleted) {
      isAlreadyCompleted.value = true
      completedSubmissionId.value = e.response.data.submissionId
    }
    if (loadError.value.toLowerCase().includes('scheduled')) {
      setTimeout(loadExam, 10000)
    }
  } finally {
    loading.value = false
  }
}

useRealtimeSync(async () => {
  if (submissionId.value && !isSubmitted.value) {
    try {
      const res = await axios.get(`/api/exam/${submissionId.value}/status`)
      if (res.data.isCompleted) {
        isSubmitted.value = true
        if (timer) clearInterval(timer)
      }
    } catch (e) {}
  }
}, 2000)

onMounted(() => {
  loadExam()
  window.addEventListener('keydown', blockKeys)
  document.addEventListener('visibilitychange', onVisibilityChange)
  window.addEventListener('focus', syncTimer)
  window.addEventListener('blur', onWindowBlur)
  window.addEventListener('beforeunload', handleBeforeUnload)
  window.addEventListener('popstate', handlePopState)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
  window.removeEventListener('keydown', blockKeys)
  document.removeEventListener('visibilitychange', onVisibilityChange)
  window.removeEventListener('focus', syncTimer)
  window.removeEventListener('blur', onWindowBlur)
  window.removeEventListener('beforeunload', handleBeforeUnload)
  window.removeEventListener('popstate', handlePopState)
})
</script>
