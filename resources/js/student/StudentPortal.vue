<template>
  <StudentLayout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

      <!-- ── Candidate Welcome & Exam Shift Card ───────────────────────────── -->
      <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 text-white p-5 sm:p-8 shadow-soft-lg relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-6">
          <div class="flex items-start sm:items-center gap-3.5 sm:gap-5 min-w-0">
            <!-- Profile Avatar with upload -->
            <div class="relative group h-16 w-16 sm:h-20 sm:w-20 shrink-0 cursor-pointer rounded-2xl bg-white/10 border-2 border-white/20 overflow-hidden flex items-center justify-center shadow-soft-sm">
              <img v-if="student.profileImage" :src="student.profileImage" class="h-full w-full object-cover" />
              <span v-else class="material-symbols-outlined text-white/60 text-3xl">person</span>

              <div class="absolute inset-0 bg-slate-900/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none text-white">
                <span class="material-symbols-outlined text-sm">photo_camera</span>
              </div>
              <input
                type="file"
                accept="image/*"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                title="Change Photo"
                @change="onFileChange"
              />
            </div>

            <div class="min-w-0 flex-1 space-y-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-blue-300 whitespace-nowrap">
                  {{ t.candidateBadge }}
                </span>
                <span v-if="student.studentCode || student.studentId" class="px-2.5 py-0.5 rounded-lg bg-blue-500/25 text-blue-200 text-xs font-mono font-bold border border-blue-400/30 whitespace-nowrap inline-flex items-center shrink-0">
                  ID: {{ student.studentCode || student.studentId }}
                </span>
              </div>
              <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight mt-0.5 truncate capitalize">
                {{ studentDisplayName }}
              </h1>

              <!-- Exam Session & Shift Details -->
              <div class="flex items-center gap-2 pt-0.5 flex-wrap">
                <!-- Shift Badge -->
                <div v-if="student.shift || student.sessionName" class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-blue-500/20 text-blue-200 border border-blue-400/30 whitespace-nowrap">
                  <span class="material-symbols-outlined text-xs">schedule</span>
                  <span>{{ formatShift(student.shift) || student.sessionName }}</span>
                </div>

                <!-- Group Badge -->
                <div v-if="student.group" class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30 whitespace-nowrap">
                  <span class="material-symbols-outlined text-xs">groups</span>
                  <span>{{ student.group }}</span>
                </div>

                <!-- Skill Badge -->
                <div v-if="student.skill" class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30 whitespace-nowrap">
                  <span class="material-symbols-outlined text-xs">school</span>
                  <span>{{ student.skill }}</span>
                </div>

                <div v-if="student.examDate" class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-slate-200 border border-white/10 font-mono whitespace-nowrap">
                  <span class="material-symbols-outlined text-xs">event</span>
                  <span>{{ student.examDate }}</span>
                  <span v-if="student.startTime">({{ formatTime(student.startTime) }} - {{ formatTime(student.endTime) }})</span>
                </div>

                <!-- Telegram Notification Badge -->
                <a
                  v-if="student.telegramConnected"
                  href="https://t.me/onlinexam_bot"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 whitespace-nowrap hover:bg-emerald-500/30 transition-colors"
                  :title="lang === 'kh' ? 'Telegram ត្រូវបានភ្ជាប់រួចរាល់' : 'Telegram Connected'"
                >
                  <span class="material-symbols-outlined text-xs">send</span>
                  <span>{{ student.telegramUsername ? student.telegramUsername : (lang === 'kh' ? 'Telegram: បានភ្ជាប់' : 'Telegram: Connected') }}</span>
                </a>
                <a
                  v-else
                  :href="student.telegramConnectUrl || 'https://t.me/onlinexam_bot'"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30 whitespace-nowrap hover:bg-amber-500/30 transition-colors cursor-pointer"
                  :title="lang === 'kh' ? 'ចុចដើម្បីភ្ជាប់ Telegram ទទួលលទ្ធផលប្រឡង' : 'Click to connect Telegram for exam results'"
                  @click="onConnectTelegramClick"
                >
                  <span class="material-symbols-outlined text-xs">notifications_active</span>
                  <span>{{ lang === 'kh' ? 'ភ្ជាប់ Telegram' : 'Connect Telegram' }}</span>
                </a>
              </div>
            </div>
          </div>

          <!-- Settings Trigger -->
          <div class="flex items-center sm:justify-end gap-2 shrink-0 pt-2 sm:pt-0 border-t border-white/10 sm:border-t-0">
            <Button
              variant="outline"
              size="sm"
              :icon="editing ? 'close' : 'manage_accounts'"
              class="bg-white/10 text-white border-white/20 hover:bg-white/20 w-full sm:w-auto justify-center font-bold text-xs"
              @click="toggleEdit"
            >
              {{ editing ? t.closeSettings : t.accountSettings }}
            </Button>
          </div>
        </div>
      </div>

      <!-- ── Candidate Information Settings ───────────────────── -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform opacity-0 -translate-y-2"
        enter-to-class="transform opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform opacity-100 translate-y-0"
        leave-to-class="transform opacity-0 -translate-y-2"
      >
        <div v-if="editing" class="p-6 rounded-3xl bg-white border border-slate-200/90 shadow-soft-md space-y-5">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-lg">person_edit</span>
              </div>
              <div>
                <h3 class="font-extrabold text-slate-900 text-base leading-tight">
                  {{ t.editProfile }}
                </h3>
                <p class="text-xs text-slate-500">
                  {{ lang === 'kh' ? 'ពិនិត្យ និងកែប្រែព័ត៌មានផ្ទាល់ខ្លួនរបស់បេក្ខជន' : 'Review and update your candidate information' }}
                </p>
              </div>
            </div>
            <button
              type="button"
              class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"
              @click="editing = false"
            >
              <span class="material-symbols-outlined text-lg">close</span>
            </button>
          </div>

          <form @submit.prevent="saveProfile" class="space-y-4">
            <!-- ── Part 1: Names ────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                v-model="profileForm.firstName"
                :label="t.firstName"
                :placeholder="t.firstName"
                required
              />
              <Input
                v-model="profileForm.lastName"
                :label="t.lastName"
                :placeholder="t.lastName"
                required
              />
            </div>

            <!-- ── Part 2: Phone & Candidate ID ────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                v-model="profileForm.phone"
                :label="t.phone"
                icon="call"
                placeholder="012 345 678"
                required
              />
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  {{ lang === 'kh' ? 'អត្តលេខបេក្ខជន' : 'Candidate ID' }}
                </label>
                <div class="h-10 px-3.5 rounded-xl bg-slate-100/80 border border-slate-200 text-slate-700 font-mono text-sm font-bold flex items-center select-none">
                  {{ student.studentCode || student.studentId }}
                </div>
              </div>
            </div>

            <!-- ── Part 3: Skill & Study Group (ជំនាញ និងក្រុមដែលគាត់រៀន) ── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  {{ lang === 'kh' ? 'ជំនាញដែលកំពុងរៀន' : 'Enrolled Skill / Major' }}
                </label>
                <div class="h-10 px-3.5 rounded-xl bg-blue-50/60 border border-blue-200/80 text-blue-950 text-xs font-bold flex items-center gap-2 select-none">
                  <span class="material-symbols-outlined text-blue-600 text-base">school</span>
                  <span class="truncate">{{ student.skill || (lang === 'kh' ? 'ទូទៅ' : 'General') }}</span>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  {{ lang === 'kh' ? 'ក្រុមសិក្សា និងវេន' : 'Study Group & Shift' }}
                </label>
                <div class="h-10 px-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200/80 text-emerald-950 text-xs font-bold flex items-center gap-2 select-none">
                  <span class="material-symbols-outlined text-emerald-600 text-base">groups</span>
                  <span class="truncate">{{ student.group || (lang === 'kh' ? 'ក្រុមទូទៅ' : 'General Group') }}</span>
                  <span v-if="student.shift" class="ml-auto text-[11px] font-semibold text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded-md">
                    {{ student.shift }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
              <Button
                variant="outline"
                size="md"
                type="button"
                @click="editing = false"
              >
                {{ lang === 'kh' ? 'បោះបង់' : 'Cancel' }}
              </Button>
              <Button
                variant="primary"
                size="md"
                type="submit"
                icon="save"
                :loading="savingProfile"
              >
                {{ t.saveProfile }}
              </Button>
            </div>
          </form>

          <!-- ── Telegram Notification Settings (Clean, Unified Single Card) ───────────────────── -->
          <div class="p-5 rounded-2xl border transition-all mt-4" :class="student.telegramConnected ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50/80'">
            <div class="flex items-center justify-between flex-wrap gap-3">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl flex items-center justify-center shrink-0" :class="student.telegramConnected ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600'">
                  <span class="material-symbols-outlined text-xl">{{ student.telegramConnected ? 'verified' : 'send' }}</span>
                </div>
                <div>
                  <h4 class="font-extrabold text-slate-900 text-sm leading-tight">
                    {{ lang === 'kh' ? 'ការជូនដំណឹងតាម Telegram (Telegram Exam Alerts)' : 'Telegram Exam Result Notifications' }}
                  </h4>
                  <p class="text-xs text-slate-500 mt-0.5">
                    {{ lang === 'kh' ? 'ទទួលលទ្ធផលប្រឡង និងពិន្ទុរបស់អ្នកដោយស្វ័យប្រវត្តិតាម Telegram ពេលប្រឡងចប់' : 'Receive instant score notifications in your Telegram when you finish an exam.' }}
                  </p>
                </div>
              </div>

              <!-- Status Badge -->
              <span
                v-if="student.telegramConnected"
                class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-300 inline-flex items-center gap-1.5"
              >
                <span class="material-symbols-outlined text-sm">verified</span>
                {{ lang === 'kh' ? 'បានភ្ជាប់រួចរាល់' : 'Connected' }} {{ student.telegramUsername ? `(${student.telegramUsername})` : '' }}
              </span>
              <span
                v-else
                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-300 inline-flex items-center gap-1.5"
              >
                <span class="material-symbols-outlined text-sm">link_off</span>
                {{ lang === 'kh' ? 'មិនទាន់ភ្ជាប់' : 'Not Connected' }}
              </span>
            </div>

            <div class="flex items-center justify-between pt-3 mt-3 border-t flex-wrap gap-3" :class="student.telegramConnected ? 'border-emerald-200/60' : 'border-slate-200/60'">
              <div class="text-xs text-slate-600 max-w-lg leading-relaxed">
                {{ student.telegramConnected
                  ? (lang === 'kh' ? 'គណនីរបស់អ្នកបានភ្ជាប់ជាមួយ OnlineExam Bot រួចរាល់។ ពេលប្រឡងចប់ ពិន្ទុនឹងផ្ញើមកទីនេះដោយស្វ័យប្រវត្តិ។' : 'Your account is linked. Score alerts will be sent to your Telegram automatically.')
                  : (lang === 'kh' ? 'ចុចប៊ូតុងខាងស្ដាំដើម្បីបើក Telegram Bot ឬបញ្ចូល Chat ID របស់អ្នកខាងក្រោមដើម្បីភ្ជាប់ដោយផ្ទាល់។' : 'Click to open Telegram Bot or enter your Chat ID below to connect directly.')
                }}
              </div>

              <div class="flex items-center gap-2">
                <a
                  v-if="!student.telegramConnected"
                  :href="student.telegramConnectUrl || 'https://t.me/onlinexam_bot'"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition-colors cursor-pointer"
                  @click="onConnectTelegramClick"
                >
                  <span class="material-symbols-outlined text-sm">open_in_new</span>
                  <span>{{ lang === 'kh' ? 'ភ្ជាប់ជាមួយ Telegram' : 'Connect Telegram' }}</span>
                </a>
                <button
                  v-else
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-100/60 border border-rose-200 transition-colors cursor-pointer"
                  @click="unlinkTelegram"
                >
                  <span class="material-symbols-outlined text-sm">link_off</span>
                  <span>{{ lang === 'kh' ? 'ផ្តាច់ការភ្ជាប់' : 'Unlink' }}</span>
                </button>
              </div>
            </div>

            <!-- Manual Telegram Chat ID entry fallback -->
            <div v-if="!student.telegramConnected" class="pt-3 border-t border-slate-200/60">
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                {{ lang === 'kh' ? 'ឬបញ្ចូល Telegram Chat ID ដោយផ្ទាល់ (ប្រសិនបើមិនទាន់ភ្ជាប់)៖' : 'Or enter your Telegram Chat ID directly:' }}
              </label>
              <div class="flex items-center gap-2 max-w-md">
                <input
                  v-model="manualChatId"
                  type="text"
                  :placeholder="lang === 'kh' ? 'បញ្ចូល Telegram Chat ID របស់អ្នក...' : 'Enter your Telegram Chat ID...'"
                  class="flex-1 px-3 py-1.5 rounded-xl text-xs border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                  @keyup.enter="linkTelegramManually"
                />
                <button
                  type="button"
                  :disabled="linkingTelegram || !manualChatId"
                  class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50 transition-colors shrink-0"
                  @click="linkTelegramManually"
                >
                  <span class="material-symbols-outlined text-xs">link</span>
                  <span>{{ linkingTelegram ? '...' : (lang === 'kh' ? 'ភ្ជាប់' : 'Link') }}</span>
                </button>
              </div>
              <p class="text-[10px] text-slate-400 mt-1">
                {{ lang === 'kh' ? '💡 ដើម្បីដឹង Chat ID របស់អ្នក៖ ចូលទៅកាន់ Telegram Bot (@onlinexam_bot) រួចផ្ញើសារ /myid ឬ /chatid' : '💡 Tip: To find your Chat ID, send /myid or /chatid to @onlinexam_bot.' }}
              </p>
            </div>
          </div>
        </div>
      </transition>

      <!-- ── Featured Next Urgent Exam Banner ──────────────────────── -->
      <div v-if="nextUrgentExam && !editing" class="p-6 rounded-3xl bg-blue-600 text-white shadow-soft-md flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2 max-w-xl">
          <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-white/20 text-xs font-extrabold tracking-wide uppercase">
            <span class="h-2 w-2 rounded-full" :class="getExamTimingStatus(nextUrgentExam).isOpen ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
            {{ t.nextExamPrompt }}
          </div>
          <h2 class="text-2xl font-extrabold tracking-tight text-white leading-snug">
            {{ nextUrgentExam.name }}
          </h2>
          <div class="flex items-center gap-4 text-xs text-blue-100 flex-wrap font-medium">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">schedule</span>{{ nextUrgentExam.durationMinutes }} {{ t.minutes }}</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">star</span>{{ nextUrgentExam.totalMarks }} {{ t.marks }}</span>
            <span v-if="nextUrgentExam.sessionName" class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">calendar_clock</span>{{ nextUrgentExam.sessionName }}</span>
            <span v-if="getExamTimingStatus(nextUrgentExam).isUpcoming" class="flex items-center gap-1 text-amber-200 font-bold bg-amber-500/20 px-2.5 py-0.5 rounded-full border border-amber-300/30">
              <span class="material-symbols-outlined text-sm">alarm</span>
              {{ lang === 'kh' ? 'បើកនៅ៖ ' : 'Opens at: ' }}{{ getExamTimingStatus(nextUrgentExam).timeText }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <Button
            variant="secondary"
            size="lg"
            :icon="getExamTimingStatus(nextUrgentExam).isOpen ? 'play_arrow' : 'schedule'"
            :disabled="!getExamTimingStatus(nextUrgentExam).isOpen"
            class="bg-white text-blue-700 hover:bg-blue-50 font-extrabold shadow-soft-md disabled:opacity-60"
            @click="startExam(nextUrgentExam.id)"
          >
            {{ getExamTimingStatus(nextUrgentExam).isOpen ? t.startExamNow : getExamTimingStatus(nextUrgentExam).btnText }}
          </Button>
        </div>
      </div>

      <!-- ── Main Grid: Available Exams & Exam History ─────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Available Exams (7 cols) -->
        <Card
          :title="t.availableExams"
          :subtitle="t.availableSubtitle"
          class="lg:col-span-7 shadow-soft-sm"
          padding="normal"
        >
          <template #actions>
            <span class="rounded-full bg-blue-50 text-blue-700 font-bold px-2.5 py-0.5 text-xs">
              {{ availableTests.length }}
            </span>
          </template>

          <div v-if="availableTests.length > 0" class="space-y-3">
            <div
              v-for="exam in availableTests"
              :key="exam.id"
              class="p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-blue-300 hover:shadow-soft-xs transition-all flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
              <div class="space-y-1.5">
                <h3 class="font-bold text-slate-900 text-sm sm:text-base leading-snug">
                  {{ exam.name }}
                </h3>
                <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                  <span class="inline-flex items-center gap-1 font-medium">
                    <span class="material-symbols-outlined text-sm text-slate-400">schedule</span>
                    {{ exam.durationMinutes }} {{ t.minutes }}
                  </span>
                  <span class="inline-flex items-center gap-1 font-medium">
                    <span class="material-symbols-outlined text-sm text-slate-400">star</span>
                    {{ exam.totalMarks }} {{ t.marks }}
                  </span>
                  <span v-if="exam.sessionName" class="inline-flex items-center gap-1 font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                    <span class="material-symbols-outlined text-xs">calendar_clock</span>
                    {{ exam.sessionName }}
                  </span>
                  <span
                    v-if="getExamTimingStatus(exam).isUpcoming"
                    class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-md"
                  >
                    <span class="material-symbols-outlined text-xs">alarm</span>
                    {{ lang === 'kh' ? 'បើកនៅ៖ ' : 'Starts: ' }}{{ getExamTimingStatus(exam).timeText }}
                  </span>
                </div>
              </div>

              <Button
                :variant="getExamTimingStatus(exam).isOpen ? 'primary' : 'outline'"
                size="sm"
                :icon="getExamTimingStatus(exam).isOpen ? 'arrow_forward' : 'schedule'"
                :disabled="!getExamTimingStatus(exam).isOpen"
                class="shrink-0"
                @click="startExam(exam.id)"
              >
                {{ getExamTimingStatus(exam).btnText }}
              </Button>
            </div>
          </div>

          <EmptyState
            v-else
            icon="event_available"
            :title="t.noExamsAvailable"
            :description="t.noExamsDesc"
          />
        </Card>

        <!-- Exam History (5 cols) -->
        <Card
          :title="t.examHistory"
          :subtitle="t.historySubtitle"
          class="lg:col-span-5 shadow-soft-sm"
          padding="normal"
        >
          <div v-if="examResults.length > 0" class="space-y-3">
            <div
              v-for="res in examResults"
              :key="res.id || res.submissionId"
              class="p-3.5 rounded-2xl border border-slate-100 bg-slate-50/50 hover:bg-slate-100/80 transition-all flex items-center justify-between gap-3 cursor-pointer group"
              @click="router.push(`/student/results/${res.id || res.submissionId}`)"
            >
              <div class="min-w-0">
                <h4 class="font-bold text-slate-900 text-xs sm:text-sm truncate group-hover:text-blue-600 transition-colors">
                  {{ res.testName }}
                </h4>
                <p class="text-[11px] text-slate-400 mt-0.5 font-mono">
                  {{ formatDate(res.completedAt) }}
                </p>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                  {{ lang === 'kh' ? 'បានប្រគល់រួច' : 'Submitted' }}
                </span>
                <span class="material-symbols-outlined text-slate-400 group-hover:text-blue-600 group-hover:translate-x-0.5 transition-all text-base">
                  arrow_forward
                </span>
              </div>
            </div>
          </div>

          <EmptyState
            v-else
            icon="history_edu"
            :title="t.noHistoryYet"
            :description="t.noHistoryDesc"
          />
        </Card>

      </div>

    </div>
  </StudentLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import StudentLayout from '../layouts/StudentLayout.vue'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import Input from '../components/ui/Input.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { useSettings } from '../composables/useSettings'
import { useRealtimeSync, notifyRealtimeChange } from '../composables/useRealtimeSync'

const router = useRouter()
const { lang } = useLang()
const { success: toastSuccess, error: toastError, info: toastInfo } = useToast()
const { settings, fetchSettings } = useSettings()

const student = reactive({
  name: '',
  firstName: '',
  lastName: '',
  studentId: '',
  studentCode: '',
  phone: '',
  skill: '',
  group: '',
  shift: '',
  sessionId: null,
  sessionName: '',
  examDate: null,
  startTime: null,
  endTime: null,
  profileImage: '',
  telegramChatId: null,
  telegramUsername: null,
  telegramConnected: false,
  telegramConnectUrl: ''
})

const profileForm = reactive({
  firstName: '',
  lastName: '',
  phone: ''
})

const availableTests = ref([])
const examResults = ref([])
const editing = ref(false)
const savingProfile = ref(false)
const manualChatId = ref('')
const linkingTelegram = ref(false)

const studentDisplayName = computed(() => {
  return student.name || 'Candidate'
})

const nextUrgentExam = computed(() => {
  return availableTests.value.length > 0 ? availableTests.value[0] : null
})

const formatShift = (shift) => {
  if (!shift) return ''
  const s = String(shift).trim().toLowerCase()
  if (s.includes('morning') || s.includes('ព្រឹក')) {
    return lang.value === 'kh' ? 'វេនព្រឹក (Morning)' : 'Morning'
  }
  if (s.includes('afternoon') || s.includes('រសៀល')) {
    return lang.value === 'kh' ? 'វេនរសៀល (Afternoon)' : 'Afternoon'
  }
  if (s.includes('evening') || s.includes('យប់')) {
    return lang.value === 'kh' ? 'វេនយប់ (Evening)' : 'Evening'
  }
  return shift
}

const formatTime = (timeStr) => {
  if (!timeStr) return ''
  return timeStr.substring(0, 5)
}

const formatDateTime = (iso) => {
  if (!iso) return ''
  const d = new Date(String(iso).replace(' ', 'T'))
  if (isNaN(d.getTime())) return ''
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${day}/${month}/${year} ${hours}:${minutes}`
}

const getExamTimingStatus = (exam) => {
  if (!exam) return { isOpen: true, isUpcoming: false, isExpired: false, label: 'Open', btnText: 'Start' }
  const now = new Date()

  let isUpcoming = exam.isUpcoming === true
  let isExpired = exam.isFinished === true

  let startDate = null
  if (exam.scheduledAt) {
    const s = new Date(String(exam.scheduledAt).replace(' ', 'T'))
    if (!isNaN(s.getTime())) {
      startDate = s
      if (now < s) {
        isUpcoming = true
      }
    }
  }

  let endDate = null
  if (exam.finishedAt) {
    const e = new Date(String(exam.finishedAt).replace(' ', 'T'))
    if (!isNaN(e.getTime())) endDate = e
  } else if (startDate) {
    endDate = new Date(startDate.getTime() + (exam.durationMinutes || 45) * 60000)
  }

  if (endDate && now > endDate) {
    isExpired = true
  }

  if (isUpcoming) {
    return {
      isOpen: false,
      isUpcoming: true,
      isExpired: false,
      label: lang.value === 'kh' ? 'មិនទាន់ដល់ម៉ោង' : 'Upcoming',
      btnText: lang.value === 'kh' ? 'មិនទាន់ដល់ម៉ោង' : 'Upcoming',
      timeText: formatDateTime(exam.scheduledAt)
    }
  }

  if (isExpired) {
    return {
      isOpen: false,
      isUpcoming: false,
      isExpired: true,
      label: lang.value === 'kh' ? 'បានផុតកំណត់' : 'Expired',
      btnText: lang.value === 'kh' ? 'បានផុតកំណត់' : 'Expired',
      timeText: formatDateTime(endDate)
    }
  }

  return {
    isOpen: true,
    isUpcoming: false,
    isExpired: false,
    label: lang.value === 'kh' ? 'កំពុងបើកដំណើរការ' : 'Open',
    btnText: lang.value === 'kh' ? 'ចូលប្រឡង' : 'Take Exam'
  }
}

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      welcomeBack: 'សូមស្វាគមន៍មកកាន់ប្រព័ន្ធប្រឡងអាហារូបករណ៍',
      candidateBadge: 'បេក្ខជនអាហារូបករណ៍',
      generalSession: 'វេនទូទៅ',
      accountSettings: 'កែសម្រួលព័ត៌មាន',
      closeSettings: 'បិទ',
      editProfile: 'កែសម្រួលព័ត៌មានផ្ទាល់ខ្លួន',
      firstName: 'នាមខ្លួន',
      lastName: 'គោត្តនាម',
      phone: 'លេខទូរស័ព្ទ',
      saveProfile: 'រក្សាទុកព័ត៌មាន',
      nextExamPrompt: 'វិញ្ញាសាប្រឡងបន្ទាប់របស់អ្នក',
      startExamNow: 'ចាប់ផ្ដើមធ្វើវិញ្ញាសាឥឡូវនេះ',
      minutes: 'នាទី',
      marks: 'ពិន្ទុ',
      availableExams: 'វិញ្ញាសាប្រឡងដែលមាន',
      availableSubtitle: 'វិញ្ញាសាប្រឡងដែលបានចេញផ្សាយសម្រាប់វេនប្រឡងរបស់អ្នក',
      noExamsAvailable: 'មិនទាន់មានវិញ្ញាសាប្រឡងថ្មីទេ',
      noExamsDesc: 'នៅពេលគណៈកម្មការចេញវិញ្ញាសាប្រឡង វានឹងបង្ហាញនៅទីនេះ។',
      startExam: 'ចូលប្រឡង',
      examHistory: 'ប្រវត្តិការប្រឡង',
      historySubtitle: 'បញ្ជីវិញ្ញាសាដែលបានប្រគល់រួចរាល់',
      noHistoryYet: 'មិនទាន់មានប្រវត្តិប្រឡងទេ',
      noHistoryDesc: 'នៅពេលអ្នកបញ្ចប់ការប្រឡង កំណត់ត្រានឹងបង្ហាញនៅទីនេះ។'
    }
  }
  return {
    welcomeBack: 'Scholarship Entrance Assessment Portal',
    candidateBadge: 'Scholarship Candidate',
    generalSession: 'General Shift',
    accountSettings: 'Edit Profile',
    closeSettings: 'Close',
    editProfile: 'Edit Candidate Profile',
    firstName: 'First Name',
    lastName: 'Last Name',
    phone: 'Phone Number',
    saveProfile: 'Save Profile',
    nextExamPrompt: 'Next Scheduled Exam',
    startExamNow: 'Start Exam Now',
    minutes: 'min',
    marks: 'marks',
    availableExams: 'Available Exams',
    availableSubtitle: 'Exams published for your assigned exam shift',
    noExamsAvailable: 'No exams currently open',
    noExamsDesc: 'When examination papers are released for your session, they will appear here.',
    startExam: 'Take Exam',
    examHistory: 'Exam History',
    historySubtitle: 'Archive of submitted examination papers',
    noHistoryYet: 'No previous exam submissions',
    noHistoryDesc: 'Completed exams and submissions will be archived here.'
  }
})

const toggleEdit = () => {
  editing.value = !editing.value
  if (editing.value) {
    profileForm.firstName = student.firstName || ''
    profileForm.lastName = student.lastName || ''
    profileForm.phone = student.phone || ''

    if (!profileForm.firstName && !profileForm.lastName && student.name) {
      const parts = student.name.trim().split(' ')
      profileForm.firstName = parts[0] || ''
      profileForm.lastName = parts.slice(1).join(' ') || ''
    }
  }
}

const onFileChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  const formData = new FormData()
  formData.append('image', file)
  try {
    const res = await axios.post('/api/profile/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    student.profileImage = res.data.profileImage
    toastSuccess(lang.value === 'kh' ? 'បានផ្លាស់ប្តូររូបថតជោគជ័យ' : 'Profile photo updated!')
  } catch (err) {
    toastError(err.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចផ្ទុករូបភាពបានទេ' : 'Upload failed'))
  }
}

const saveProfile = async () => {
  savingProfile.value = true
  try {
    await axios.post('/api/profile/update', profileForm)
    Object.assign(student, profileForm)
    student.name = `${profileForm.firstName} ${profileForm.lastName}`.trim()
    toastSuccess(lang.value === 'kh' ? 'បានកែប្រែព័ត៌មានជោគជ័យ' : 'Profile updated successfully!')
    editing.value = false
  } catch (e) {
    toastError(e.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចកែប្រែបានទេ' : 'Failed to update profile.'))
  } finally {
    savingProfile.value = false
  }
}

const unlinkTelegram = async () => {
  if (!confirm(lang.value === 'kh' ? 'តើអ្នកពិតជាចង់ផ្តាច់ការភ្ជាប់ Telegram មែនទេ?' : 'Are you sure you want to unlink Telegram?')) return
  try {
    await axios.post('/api/student/telegram/unlink')
    student.telegramConnected = false
    student.telegramChatId = null
    student.telegramUsername = null
    toastSuccess(lang.value === 'kh' ? 'បានផ្តាច់ការភ្ជាប់គណនី Telegram រួចរាល់' : 'Telegram unlinked successfully')
    notifyRealtimeChange('student_updated')
  } catch (e) {
    toastError(e.response?.data?.message || (lang.value === 'kh' ? 'មានបញ្ហាក្នុងការផ្តាច់' : 'Failed to unlink Telegram'))
  }
}

const linkTelegramManually = async () => {
  if (!manualChatId.value.trim()) {
    toastError(lang.value === 'kh' ? 'សូមបញ្ចូល Telegram Chat ID របស់អ្នក' : 'Please enter your Telegram Chat ID')
    return
  }
  linkingTelegram.value = true
  try {
    const res = await axios.post('/api/student/telegram/manual-link', { chatId: manualChatId.value.trim() })
    student.telegramConnected = true
    student.telegramChatId = res.data.telegramChatId || manualChatId.value.trim()
    toastSuccess(lang.value === 'kh' ? 'បានភ្ជាប់ Telegram ដោយជោគជ័យ!' : 'Telegram linked successfully!')
    manualChatId.value = ''
    notifyRealtimeChange('student_updated')
    loadStudentData(true)
  } catch (e) {
    toastError(e.response?.data?.message || (lang.value === 'kh' ? 'មិនអាចភ្ជាប់ Telegram បានទេ' : 'Failed to link Telegram'))
  } finally {
    linkingTelegram.value = false
  }
}

const startExam = (testId) => {
  router.push({ name: 'Exam', params: { testId } })
}

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(lang.value === 'kh' ? 'km-KH' : 'en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

let pollingActive = false
let isFetchingStudentData = false
let initialLoadDone = false

const loadStudentData = async (forcePollTelegram = false) => {
  if (isFetchingStudentData) return
  isFetchingStudentData = true
  try {
    // Non-blocking Telegram queue check in background
    if ((!student.telegramConnected || forcePollTelegram) && !pollingActive) {
      pollingActive = true
      axios.get('/api/telegram/poll-once').catch(() => {}).finally(() => {
        pollingActive = false
      })
    }

    // Parallel fetch profile & results for instant load
    const [profileRes, resultsRes] = await Promise.all([
      axios.get('/api/profile'),
      axios.get('/api/student/results').catch(() => ({ data: { results: [] } }))
    ])

    const s = profileRes.data.student || {}
    const wasConnected = student.telegramConnected

    student.name = s.name || profileRes.data.user?.name || ''
    student.firstName = s.firstName || ''
    student.lastName = s.lastName || ''
    student.studentId = s.studentId || ''
    student.studentCode = s.studentCode || s.studentId || ''
    student.phone = s.phone || ''
    student.skill = s.skill || ''
    student.group = s.group || ''
    student.shift = s.shift || ''
    student.sessionId = s.sessionId || null
    student.sessionName = s.sessionName || ''
    student.examDate = s.examDate || null
    student.startTime = s.startTime || null
    student.endTime = s.endTime || null
    student.profileImage = profileRes.data.user?.profileImage || ''
    student.telegramChatId = s.telegramChatId || null
    student.telegramUsername = s.telegramUsername || null
    student.telegramConnected = Boolean(s.telegramConnected)
    student.telegramConnectUrl = s.telegramConnectUrl || ('https://t.me/onlinexam_bot?start=link_' + encodeURIComponent(student.studentCode || student.studentId))

    if (initialLoadDone) {
      if (!wasConnected && student.telegramConnected) {
        toastSuccess(lang.value === 'kh' ? '🎉 ការភ្ជាប់ Telegram បានជោគជ័យ!' : '🎉 Telegram connected successfully!')
      } else if (wasConnected && !student.telegramConnected) {
        toastInfo(lang.value === 'kh' ? '✂️ គណនី Telegram ត្រូវបានផ្តាច់ការភ្ជាប់រួចរាល់' : 'Telegram unlinked successfully')
      }
    }
    initialLoadDone = true

    if (!student.firstName && !student.lastName && student.name) {
      const parts = student.name.trim().split(' ')
      student.firstName = parts[0] || ''
      student.lastName = parts.slice(1).join(' ') || ''
    }

    if (!editing.value) {
      profileForm.firstName = student.firstName
      profileForm.lastName = student.lastName
      profileForm.phone = student.phone
    }

    availableTests.value = profileRes.data.tests || []
    examResults.value = resultsRes.data.results || []
  } catch (e) {
    console.error('Failed to load student data', e)
  } finally {
    isFetchingStudentData = false
  }
}

const onConnectTelegramClick = () => {
  loadStudentData(true)
}

const onFocusCheck = () => {
  loadStudentData(true)
}

// Real-time synchronization:
// - Updates every 3 seconds while tab is active
// - Updates immediately on window focus, tab visibility change, or cross-tab broadcast
useRealtimeSync(async () => {
  if (document.visibilityState === 'visible') {
    await loadStudentData(false)
  }
}, 3000)

onMounted(() => {
  fetchSettings()
  loadStudentData(true)
  window.addEventListener('focus', onFocusCheck)
  document.addEventListener('visibilitychange', onFocusCheck)
})

onUnmounted(() => {
  window.removeEventListener('focus', onFocusCheck)
  document.removeEventListener('visibilitychange', onFocusCheck)
})
</script>
