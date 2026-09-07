<template>
  <div :class="[
    isBuilderMode
      ? 'h-[calc(100vh-105px)] max-h-[calc(100vh-105px)] flex flex-col overflow-hidden gap-3'
      : 'space-y-6'
  ]">
    <!-- Header & Mode Switcher (Library Mode Only) -->
    <div v-if="!isBuilderMode" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between shrink-0">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
          <span class="material-symbols-outlined text-blue-600 text-3xl">quiz</span>
          {{ t.examLibraryTitle }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
          {{ t.librarySubtitle }}
        </p>
      </div>

      <div class="flex items-center gap-2.5 flex-wrap">
        <Button
          v-if="can('Exams', 'create')"
          variant="primary"
          icon="add_circle"
          size="sm"
          @click="openCreateBuilder"
        >
          {{ t.createExam }}
        </Button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- VIEW 1: EXAM LIBRARY (TEST BANK)                               -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="!isBuilderMode" class="space-y-6">
      <!-- Filter Toolbar -->
      <Card padding="sm" class="shadow-soft-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 flex-1 max-w-md">
            <CustomDropdown
              v-model="filterSkill"
              :options="[{ SkillName: t.allSkills, SkillId: '' }, ...skills]"
              labelKey="SkillName"
              valueKey="SkillName"
              :placeholder="t.allSkills"
            />
            <CustomDropdown
              v-model="filterGroup"
              :options="[{ GroupName: t.allGroups, GroupId: '' }, ...groups]"
              labelKey="GroupName"
              valueKey="GroupName"
              :placeholder="t.allGroups"
            />
          </div>

          <div class="flex items-center gap-2">
            <IconButton
              v-if="filterSkill || filterGroup || searchQuery || statusFilter !== 'all'"
              icon="restart_alt"
              variant="ghost"
              size="md"
              :title="t.reset"
              class="shrink-0"
              @click="resetFilters"
            />
            <div class="w-full sm:w-64">
              <SearchInput
                v-model="searchQuery"
                :placeholder="t.searchPlaceholder"
              />
            </div>
          </div>
        </div>

        <!-- Status Filter Tabs -->
        <div class="pt-3 mt-3 border-t border-slate-100">
          <Tabs
            v-model="statusFilter"
            :tabs="statusTabs"
            :pill="true"
          />
        </div>
      </Card>

      <!-- Exam Table Card -->
      <Card padding="none" class="shadow-soft-sm overflow-hidden">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50/70">
              <tr>
                <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.examName }}</th>
                <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.skillGroup }}</th>
                <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.duration }}</th>
                <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.questionsMarks }}</th>
                <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.status }}</th>
                <th class="px-4 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.actions }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="test in paginatedTests" :key="test.id" class="hover:bg-slate-50/80 transition-colors">
                <td class="px-4 py-3.5 font-bold text-slate-900 leading-snug">
                  <div>{{ test.name }}</div>
                  <div v-if="test.scheduledAt" class="text-xs text-slate-400 font-normal mt-0.5">
                    {{ formatDateTime(test.scheduledAt) }}
                  </div>
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                    {{ test.skill }}
                  </span>
                  <span v-if="test.group" class="ml-1 text-xs text-slate-400">· {{ test.group }}</span>
                </td>
                <td class="px-4 py-3.5 text-slate-600 font-medium whitespace-nowrap">
                  {{ test.durationMinutes }} {{ lang === 'kh' ? 'នាទី' : 'mins' }}
                </td>
                <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">
                  <span class="font-bold text-slate-800">{{ test.questionCount || 0 }}</span> {{ lang === 'kh' ? 'សំណួរ' : 'qs' }}
                  <span class="text-slate-300 mx-1">·</span>
                  <span class="font-bold text-blue-600">{{ test.totalMarks }}</span> {{ lang === 'kh' ? 'ពិន្ទុ' : 'pts' }}
                </td>
                <td class="px-4 py-3.5">
                  <StatusBadge
                    :status="test.status.toLowerCase()"
                    :label="test.status"
                  />
                </td>
                <td class="px-4 py-3.5 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <!-- Export Word -->
                    <IconButton
                      v-if="can('Exams', 'export')"
                      icon="article"
                      variant="ghost"
                      size="sm"
                      title="Export Word (.docx)"
                      class="text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                      :loading="exportingWordId === test.id"
                      @click="exportTestToWord(test)"
                    />
                    <!-- Export Text (.txt) -->
                    <IconButton
                      v-if="can('Exams', 'export')"
                      icon="text_snippet"
                      variant="ghost"
                      size="sm"
                      title="Export Text (.txt)"
                      class="text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50"
                      :loading="exportingTxtId === test.id"
                      @click="exportTestToTxt(test)"
                    />
                    <!-- Edit -->
                    <IconButton
                      v-if="can('Exams', 'edit')"
                      icon="edit"
                      variant="ghost"
                      size="sm"
                      :title="t.edit"
                      @click="editTest(test)"
                    />
                    <!-- Delete -->
                    <IconButton
                      v-if="can('Exams', 'delete')"
                      icon="delete"
                      variant="ghost"
                      size="sm"
                      :title="t.delete"
                      class="text-red-500 hover:text-red-700 hover:bg-red-50"
                      @click="confirmDeleteTest(test)"
                    />
                  </div>
                </td>
              </tr>

              <tr v-if="filteredTests.length === 0">
                <td colspan="6">
                  <EmptyState
                    icon="quiz"
                    :title="t.noExamsFound"
                    :description="t.noExamsDesc"
                    :action-label="can('Exams', 'create') ? t.createExam : ''"
                    @action="openCreateBuilder"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card List View -->
        <div class="md:hidden divide-y divide-slate-100">
          <div v-for="test in paginatedTests" :key="test.id" class="p-4 space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <h4 class="font-bold text-slate-900 text-sm leading-snug break-words">
                  {{ test.name }}
                </h4>
                <div v-if="test.scheduledAt" class="text-[11px] text-slate-400 mt-0.5">
                  {{ formatDateTime(test.scheduledAt) }}
                </div>
              </div>
              <StatusBadge
                :status="test.status.toLowerCase()"
                :label="test.status"
                class="shrink-0"
              />
            </div>

            <div class="flex items-center justify-between text-xs bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
              <span class="font-bold text-blue-700">{{ test.skill }}</span>
              <span v-if="test.group" class="text-slate-500 font-medium">{{ test.group }}</span>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-600 px-0.5">
              <div class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-slate-400">timer</span>
                <span>{{ test.durationMinutes }} {{ lang === 'kh' ? 'នាទី' : 'mins' }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="font-bold text-slate-800">{{ test.questionCount || 0 }}</span> {{ lang === 'kh' ? 'សំណួរ' : 'qs' }}
                <span class="text-slate-300">·</span>
                <span class="font-bold text-blue-600">{{ test.totalMarks }}</span> {{ lang === 'kh' ? 'ពិន្ទុ' : 'pts' }}
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 pt-2 border-t border-slate-100">
              <Button
                v-if="can('Exams', 'export')"
                variant="outline"
                size="xs"
                icon="article"
                class="w-full justify-center text-[11px]"
                :loading="exportingWordId === test.id"
                @click="exportTestToWord(test)"
              >
                Word
              </Button>
              <Button
                v-if="can('Exams', 'export')"
                variant="outline"
                size="xs"
                icon="text_snippet"
                class="w-full justify-center text-[11px]"
                :loading="exportingTxtId === test.id"
                @click="exportTestToTxt(test)"
              >
                Text (.txt)
              </Button>
              <Button
                v-if="can('Exams', 'edit')"
                variant="secondary"
                size="xs"
                icon="edit"
                class="w-full justify-center text-[11px]"
                @click="editTest(test)"
              >
                {{ t.edit }}
              </Button>
              <Button
                v-if="can('Exams', 'delete')"
                variant="danger"
                size="xs"
                icon="delete"
                class="w-full justify-center text-[11px]"
                @click="confirmDeleteTest(test)"
              >
                {{ t.delete }}
              </Button>
            </div>
          </div>

          <div v-if="filteredTests.length === 0" class="p-6">
            <EmptyState
              icon="quiz"
              :title="t.noExamsFound"
              :description="t.noExamsDesc"
              :action-label="can('Exams', 'create') ? t.createExam : ''"
              @action="openCreateBuilder"
            />
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredTests.length > pageSize" class="p-3 border-t border-slate-100">
          <Pagination
            v-model="currentPage"
            :total-items="filteredTests.length"
            :page-size="pageSize"
          />
        </div>
      </Card>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- VIEW 2: 3-ZONE EXAM BUILDER                                     -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-else class="flex-1 min-h-0 flex flex-col gap-3 overflow-hidden">
      <!-- Builder Top Actions Bar -->
      <div class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-2xl border border-slate-200/90 shadow-soft-sm px-4 py-2.5 sm:py-3 shrink-0">
        <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
          <Button
            variant="outline"
            size="sm"
            icon="arrow_back"
            class="shrink-0 whitespace-nowrap"
            @click="returnToLibrary"
          >
            <span class="hidden sm:inline">{{ t.backToLibrary }}</span>
          </Button>

          <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

          <div class="min-w-0 flex items-center gap-2.5">
            <span class="material-symbols-outlined text-blue-600 text-2xl hidden sm:inline-block">quiz</span>
            <div>
              <h2 class="text-base sm:text-lg font-extrabold text-slate-900 leading-tight truncate">
                {{ editingTestId ? t.editExamTitle : t.createExamTitle }}
              </h2>
              <p class="text-xs text-slate-500 truncate hidden sm:block mt-0.5">{{ t.builderSubtitle }}</p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 justify-end shrink-0">
          <Button
            variant="outline"
            size="sm"
            icon="edit_note"
            :loading="saving"
            class="whitespace-nowrap"
            @click="handleSaveTest('Draft')"
          >
            {{ t.saveDraft }}
          </Button>
          <Button
            variant="primary"
            size="sm"
            :icon="editingTestId ? 'save' : 'publish'"
            :loading="saving"
            class="whitespace-nowrap"
            @click="handleSaveTest('Published')"
          >
            {{ editingTestId ? t.updateAndPublish : t.publishExam }}
          </Button>
        </div>
      </div>

      <!-- Mobile Tab Switcher for Builder -->
      <div class="lg:hidden shrink-0">
        <Tabs
          v-model="activeBuilderTab"
          :tabs="builderTabs"
          :pill="true"
        />
      </div>

      <!-- 3-Zone Layout Grid (Exact viewport height fit, no outer scroll) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 flex-1 min-h-0 overflow-hidden">

        <!-- Zone 1: Question Navigator (3 cols on desktop) -->
        <Card
          :class="[
            'lg:col-span-3 shadow-soft-sm flex flex-col h-full min-h-0 overflow-hidden',
            activeBuilderTab === 'questions' ? 'flex' : 'hidden lg:flex'
          ]"
          padding="none"
        >
          <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.questionList }}</span>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
              {{ questions.length }} {{ lang === 'kh' ? 'សំណួរ' : 'Questions' }}
            </span>
          </div>

          <!-- Question Items (Scrollable) -->
          <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-slate-100 p-2 space-y-1">
            <button
              v-for="(q, qIdx) in questions"
              :key="qIdx"
              type="button"
              :class="[
                'w-full flex items-start justify-between p-2.5 rounded-xl text-left transition-all cursor-pointer select-none border',
                activeQuestionIdx === qIdx
                  ? 'bg-blue-50 border-blue-300 text-blue-900 font-bold shadow-soft-xs'
                  : 'bg-white border-transparent hover:bg-slate-50 text-slate-700'
              ]"
              @click="selectQuestion(qIdx)"
            >
              <div class="flex items-start gap-2.5 min-w-0 flex-1 pr-1">
                <span
                  :class="[
                    'h-5 w-5 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5',
                    q.isExample ? 'bg-purple-100 text-purple-700 ring-1 ring-purple-300' : (activeQuestionIdx === qIdx ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700')
                  ]"
                >
                  {{ getQuestionNum(qIdx) }}
                </span>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-1">
                    <span v-if="q.passage" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 text-[9px] font-bold shrink-0">
                      <span class="material-symbols-outlined text-[11px]">menu_book</span> Reading
                    </span>
                    <p class="text-xs truncate leading-tight flex-1" v-html="renderMath(q.text || (lang === 'kh' ? `(សំណួរទី ${getQuestionNum(qIdx)})` : `(Question ${getQuestionNum(qIdx)})`))"></p>
                  </div>
                  <p class="text-[10px] text-slate-400 mt-1 font-mono">
                    {{ q.answers.length }} {{ lang === 'kh' ? 'ជម្រើស' : 'options' }} · {{ q.points || 1 }} {{ lang === 'kh' ? 'ពិន្ទុ' : 'pts' }}
                  </p>
                </div>
              </div>

              <!-- Complete status icon -->
              <span
                v-if="q.correctIndex !== null && q.text.trim()"
                class="material-symbols-outlined text-base shrink-0 text-emerald-600"
                style="font-variation-settings: 'FILL' 1;"
              >
                check_circle
              </span>
              <span
                v-else
                class="material-symbols-outlined text-amber-500 text-base shrink-0"
              >
                error
              </span>
            </button>

            <div v-if="questions.length === 0" class="p-6 text-center text-xs text-slate-400">
              {{ t.noQuestionsYet }}
            </div>
          </div>

          <!-- Add Question & Bulk Import in Zone 1 Footer -->
          <div class="p-3 border-t border-slate-100 bg-slate-50/50 space-y-2 shrink-0">
            <Button
              variant="primary"
              full-width
              size="sm"
              icon="add"
              @click="addQuestionAndOpen"
            >
              {{ t.addQuestion }}
            </Button>

            <!-- Bulk Question Importer Button -->
            <Button
              variant="outline"
              full-width
              size="xs"
              icon="playlist_add"
              class="border-blue-200 text-blue-700 hover:bg-blue-50/80"
              @click="openBulkImportModal"
            >
              {{ lang === 'kh' ? 'នាំចូលសំណួរជាដុំ' : 'Bulk Import Questions' }}
            </Button>
          </div>
        </Card>

        <!-- Zone 2: Main Question Editor (5 cols on desktop) -->
        <Card
          :class="[
            'lg:col-span-5 xl:col-span-5 shadow-soft-sm flex flex-col h-full min-h-0 overflow-hidden',
            activeBuilderTab === 'editor' ? 'flex' : 'hidden lg:flex'
          ]"
          padding="sm"
        >
          <template v-if="currentEditingQuestion">
            <!-- Top Action Header Row (Single Row Always) -->
            <div class="flex items-center justify-between gap-1.5 sm:gap-2 pb-3 mb-3 border-b border-slate-100 shrink-0">
              <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <!-- Question Badge (សំណួរទី ១ / Question 1) -->
                <div class="px-2 sm:px-2.5 py-1 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 font-bold text-xs whitespace-nowrap shrink-0">
                  {{ lang === 'kh' ? `សំណួរទី ${getQuestionNum(activeQuestionIdx)}` : `Question ${getQuestionNum(activeQuestionIdx)}` }}
                </div>

                <!-- Checkbox (សំណួរគំរូ / Example) -->
                <label class="flex items-center gap-1 sm:gap-1.5 text-xs text-slate-700 font-medium cursor-pointer select-none whitespace-nowrap shrink-0" :title="lang === 'kh' ? 'សំណួរគំរូ (លំហាត់គំរូ)' : 'Example Question'">
                  <input
                    type="checkbox"
                    v-model="currentEditingQuestion.isExample"
                    class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                  />
                  <span class="text-xs">{{ lang === 'kh' ? 'សំណួរគំរូ' : 'Example' }}</span>
                </label>
              </div>

              <!-- Right: Points, Passage, Delete -->
              <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <!-- Points Input -->
                <div class="flex items-center gap-1 px-2 py-0.5 sm:py-1 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 whitespace-nowrap shrink-0">
                  <span class="text-[11px] sm:text-xs text-slate-500 font-medium">{{ lang === 'kh' ? 'ពិន្ទុ:' : 'Points:' }}</span>
                  <input
                    v-model.number="currentEditingQuestion.points"
                    type="number"
                    min="0"
                    class="w-6 bg-transparent text-xs font-bold text-slate-900 outline-none text-center"
                    @input="updateTotalMarks"
                  />
                </div>

                <!-- Reading Passage Button (Icon with tooltip) -->
                <button
                  type="button"
                  :class="[
                    'flex items-center justify-center p-1.5 sm:px-2 sm:py-1 rounded-xl border text-xs font-medium transition-all cursor-pointer select-none whitespace-nowrap shrink-0',
                    currentEditingQuestion.passage
                      ? 'border-blue-300 bg-blue-50 text-blue-700 font-bold shadow-soft-xs'
                      : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'
                  ]"
                  :title="lang === 'kh' ? 'អត្ថបទអាន (Reading Passage)' : 'Reading Passage'"
                  @click="togglePassageForCurrent"
                >
                  <span class="material-symbols-outlined text-[18px] text-blue-600">menu_book</span>
                </button>

                <!-- Delete Button -->
                <button
                  type="button"
                  class="p-1.5 rounded-xl border border-rose-100 bg-rose-50/60 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-all cursor-pointer flex items-center justify-center shrink-0"
                  title="Delete Question"
                  @click="removeQuestion(activeQuestionIdx)"
                >
                  <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
              </div>
            </div>

            <!-- Question Content (Scrollable) -->
            <div class="flex-1 min-h-0 overflow-y-auto space-y-3.5 pr-1">

              <!-- Reading Passage Card (Only shown when question has a passage) -->
              <div v-if="currentEditingQuestion.passage !== undefined && currentEditingQuestion.passage !== ''" class="p-3 rounded-xl border border-blue-200 bg-blue-50/60 space-y-2 animate-fade-in">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800 min-w-0">
                    <span class="material-symbols-outlined text-base text-blue-600 shrink-0">menu_book</span>
                    <span class="truncate">{{ lang === 'kh' ? 'អត្ថបទអាន (Reading Passage)' : 'Reading Passage' }}</span>
                  </div>
                  <div class="flex items-center gap-3 shrink-0">
                    <button
                      v-if="activeQuestionIdx < questions.length - 1"
                      type="button"
                      class="text-[11px] font-bold text-blue-600 hover:text-blue-700 hover:underline cursor-pointer whitespace-nowrap inline-flex items-center gap-1"
                      @click="applyPassageToNextQuestions"
                    >
                      <span class="material-symbols-outlined text-xs">playlist_add</span>
                      <span>{{ lang === 'kh' ? 'អនុវត្តទៅសំណួរបន្ទាប់' : 'Apply to Following' }}</span>
                    </button>
                    <button
                      type="button"
                      class="text-[11px] font-bold text-rose-600 hover:text-rose-700 hover:underline cursor-pointer inline-flex items-center gap-0.5 whitespace-nowrap"
                      @click="togglePassageForCurrent"
                    >
                      <span class="material-symbols-outlined text-xs">close</span>
                      <span>{{ lang === 'kh' ? 'លុបអត្ថបទ' : 'Remove' }}</span>
                    </button>
                  </div>
                </div>

                <div class="space-y-1">
                  <textarea
                    v-model="currentEditingQuestion.passage"
                    rows="3"
                    class="w-full px-3 py-2 text-xs font-medium rounded-xl border border-blue-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all text-slate-800 placeholder:text-slate-400 resize-y leading-relaxed"
                    :placeholder="lang === 'kh' ? 'វាយបញ្ចូលអត្ថបទអាននៅទីនេះ...' : 'Enter reading passage text here...'"
                  ></textarea>
                </div>
              </div>

              <!-- Question Content Label & Input -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    {{ lang === 'kh' ? 'ខ្លឹមសារសំណួរ' : t.questionText }} <span class="text-red-500">*</span>
                  </label>
                  <button
                    type="button"
                    class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer select-none"
                    @click="isEditingQuestionText = !isEditingQuestionText"
                  >
                    <span class="material-symbols-outlined text-sm">{{ isEditingQuestionText ? 'visibility' : 'edit' }}</span>
                    <span>{{ isEditingQuestionText ? (lang === 'kh' ? 'មើលរូបមន្ត' : 'Preview Math') : (lang === 'kh' ? 'កែសម្រួល' : 'Edit') }}</span>
                  </button>
                </div>

                <!-- Direct In-Place Rendered Math Box (when previewing) -->
                <div
                  v-if="!isEditingQuestionText"
                  class="w-full min-h-[72px] px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 bg-white hover:border-slate-300 transition-all text-slate-900 cursor-pointer leading-relaxed flex items-center"
                  @click="isEditingQuestionText = true"
                >
                  <div v-if="currentEditingQuestion.text" v-html="renderMath(currentEditingQuestion.text)" class="w-full"></div>
                  <span v-else class="text-slate-400 italic">{{ lang === 'kh' ? 'វាយបញ្ចូលខ្លឹមសារសំណួរនៅទីនេះ...' : t.enterQuestionContent }}</span>
                </div>

                <!-- Textarea (when editing) -->
                <textarea
                  v-else
                  v-model="currentEditingQuestion.text"
                  rows="3"
                  class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-blue-400 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all text-slate-900 placeholder:text-slate-400 resize-none leading-relaxed"
                  :placeholder="lang === 'kh' ? 'វាយបញ្ចូលខ្លឹមសារសំណួរនៅទីនេះ...' : t.enterQuestionContent"
                  @blur="isEditingQuestionText = false"
                ></textarea>
              </div>

              <!-- Multiple Choice Options -->
              <div class="space-y-2 pt-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    {{ lang === 'kh' ? `ជម្រើសចម្លើយ (${currentEditingQuestion.answers.length})` : t.answerOptions }} <span class="text-red-500">*</span>
                  </span>
                  <span class="text-[10px] text-slate-400 italic">
                    {{ lang === 'kh' ? 'ចុចលើអក្សរដើម្បីកំណត់ជាចម្លើយត្រឹមត្រូវ' : t.markCorrectAnswerNotice }}
                  </span>
                </div>

                <div class="space-y-2">
                  <div
                    v-for="(answer, aIdx) in currentEditingQuestion.answers"
                    :key="aIdx"
                    :class="[
                      'flex items-center gap-2.5 p-2 rounded-xl border transition-all',
                      currentEditingQuestion.correctIndex === aIdx
                        ? 'border-emerald-300 bg-emerald-50/50 ring-2 ring-emerald-500/20'
                        : 'border-slate-200 bg-white hover:border-slate-300'
                    ]"
                  >
                    <!-- Selector Button for Correct Answer -->
                    <button
                      type="button"
                      :class="[
                        'h-7 w-7 rounded-lg flex items-center justify-center shrink-0 font-bold text-xs transition-all cursor-pointer select-none',
                        currentEditingQuestion.correctIndex === aIdx
                          ? 'bg-emerald-600 text-white shadow-soft-xs'
                          : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                      ]"
                      :title="lang === 'kh' ? 'កំណត់ជាចម្លើយត្រឹមត្រូវ' : 'Mark as correct answer'"
                      @click="setCorrectAnswer(aIdx)"
                    >
                      <span v-if="currentEditingQuestion.correctIndex === aIdx" class="material-symbols-outlined text-sm">check</span>
                      <span v-else>{{ String.fromCharCode(65 + aIdx) }}</span>
                    </button>

                    <!-- Option Text with Math Rendering and Inline Edit -->
                    <div class="flex-1 min-h-[28px] flex items-center">
                      <input
                        v-if="editingOptionIdx === aIdx"
                        v-model="answer.text"
                        type="text"
                        :placeholder="`${t.option} ${String.fromCharCode(65 + aIdx)}`"
                        class="w-full bg-transparent text-xs font-medium text-slate-800 outline-none placeholder:text-slate-400"
                        @blur="editingOptionIdx = null"
                        @keyup.enter="editingOptionIdx = null"
                        autofocus
                      />
                      <div
                        v-else
                        class="w-full cursor-text py-0.5 text-xs font-medium text-slate-800"
                        @click="editingOptionIdx = aIdx"
                      >
                        <span v-if="answer.text" v-html="renderMath(answer.text)"></span>
                        <span v-else class="text-slate-400">{{ t.option }} {{ String.fromCharCode(65 + aIdx) }}</span>
                      </div>
                    </div>

                    <!-- Remove Option Button -->
                    <button
                      v-if="currentEditingQuestion.answers.length > 2"
                      type="button"
                      class="text-slate-300 hover:text-red-500 p-1 transition-colors cursor-pointer"
                      @click="removeAnswerOption(aIdx)"
                    >
                      <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                  </div>
                </div>

                <Button
                  v-if="currentEditingQuestion.answers.length < 6"
                  variant="ghost"
                  size="xs"
                  icon="add"
                  @click="addAnswerOption"
                >
                  {{ lang === 'kh' ? 'បន្ថែមជម្រើសចម្លើយ' : 'Add Option' }}
                </Button>
              </div>
            </div>

            <!-- Question Editor Navigation Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-1.5 sm:gap-2 shrink-0">
              <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
                <Button
                  variant="outline"
                  size="xs"
                  icon="arrow_back"
                  :disabled="activeQuestionIdx <= 0"
                  class="px-2 sm:px-2.5 whitespace-nowrap shrink-0"
                  @click="activeQuestionIdx--"
                >
                  <span class="hidden sm:inline">{{ t.previous }}</span>
                </Button>
                <Button
                  variant="outline"
                  size="xs"
                  icon="arrow_forward"
                  :disabled="activeQuestionIdx >= questions.length - 1"
                  class="px-2 sm:px-2.5 whitespace-nowrap shrink-0"
                  @click="activeQuestionIdx++"
                >
                  <span class="hidden sm:inline">{{ t.next }}</span>
                </Button>
              </div>

              <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <Button
                  variant="ghost"
                  size="xs"
                  icon="format_list_numbered"
                  class="lg:hidden px-2 whitespace-nowrap shrink-0"
                  :title="t.questionList"
                  @click="activeBuilderTab = 'questions'"
                >
                  <span class="hidden md:inline">{{ t.questionList }}</span>
                </Button>
                <Button
                  variant="primary"
                  size="xs"
                  icon="add"
                  class="whitespace-nowrap shrink-0"
                  @click="addQuestionAndOpen"
                >
                  <span class="whitespace-nowrap">{{ t.addQuestion }}</span>
                </Button>
              </div>
            </div>
          </template>

          <EmptyState
            v-else
            icon="quiz"
            :title="t.noQuestionSelected"
            :description="t.clickToAddOrSelect"
            :action-label="t.addQuestion"
            @action="addQuestionAndOpen"
          />
        </Card>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- Zone 3: Exam Settings & Summary (4 cols on desktop)            -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <Card
          :class="[
            'lg:col-span-4 xl:col-span-4 shadow-soft-sm flex flex-col h-full min-h-0 overflow-hidden',
            activeBuilderTab === 'settings' ? 'flex' : 'hidden lg:flex'
          ]"
          padding="sm"
        >
          <template #header>
            <div class="flex items-center gap-2 pb-1">
              <span class="material-symbols-outlined text-blue-600 text-lg">tune</span>
              <h3 class="font-bold text-slate-900 text-sm">{{ t.examSettings }}</h3>
            </div>
          </template>

          <div class="flex-1 min-h-0 overflow-y-auto space-y-3 pr-1">
            <!-- ឈ្មោះការប្រឡង * -->
            <Input
              v-model="testName"
              :label="t.examName"
              required
              placeholder="e.g. Mid-Term Evaluation"
            />

            <!-- ជំនាញ * & ក្រុម -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div class="space-y-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                  {{ t.skill }} <span class="text-red-500">*</span>
                </label>
                <CustomDropdown
                  v-model="selectedSkillId"
                  :options="skills"
                  labelKey="SkillName"
                  valueKey="SkillId"
                  :placeholder="t.skill"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                  {{ t.group }}
                </label>
                <CustomDropdown
                  v-model="selectedGroupId"
                  :options="[{ GroupName: t.allGroups, GroupId: '' }, ...groups]"
                  labelKey="GroupName"
                  valueKey="GroupId"
                  :placeholder="t.allGroups"
                />
              </div>
            </div>

            <!-- កាលបរិច្ឆេទចាប់ផ្តើម & កាលបរិច្ឆេទបញ្ចប់ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <Input
                v-model="scheduledAt"
                type="datetime-local"
                :label="t.scheduledDate"
              />
              <Input
                v-model="finishedAt"
                type="datetime-local"
                :label="t.finishedDate"
              />
            </div>

            <!-- រយៈពេល (នាទី) & ពិន្ទុសរុប -->
            <div class="grid grid-cols-2 gap-2.5">
              <Input
                v-model.number="durationMinutes"
                type="number"
                min="1"
                :label="t.durationMin"
              />
              <div class="space-y-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">
                  {{ t.totalMarks }}
                </label>
                <div class="px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-extrabold text-blue-700">
                  {{ totalMarks }} pts
                </div>
              </div>
            </div>

            <!-- Summary Box -->
            <div class="p-3 rounded-2xl bg-blue-50/70 border border-blue-100 space-y-1.5 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-slate-600">{{ t.totalQuestions }}:</span>
                <strong class="text-slate-900 font-bold text-xs">{{ questions.length }}</strong>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-600">{{ t.totalScore }}:</span>
                <strong class="text-blue-700 font-bold text-xs">{{ totalMarks }} pts</strong>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-600">{{ t.duration }}:</span>
                <strong class="text-slate-900 font-bold text-xs">{{ durationMinutes }} min</strong>
              </div>
            </div>
          </div>

          <!-- Action Buttons at bottom of Zone 3 (Pinned) -->
          <div class="pt-3 border-t border-slate-100 flex flex-col gap-2 shrink-0">
            <Button
              v-if="editingTestId ? can('Exams', 'edit') : can('Exams', 'create')"
              variant="primary"
              full-width
              size="md"
              icon="publish"
              :loading="saving"
              @click="handleSaveTest('Published')"
            >
              {{ editingTestId ? t.updateAndPublish : t.publishExam }}
            </Button>
            <Button
              v-if="editingTestId ? can('Exams', 'edit') : can('Exams', 'create')"
              variant="outline"
              full-width
              size="sm"
              icon="draft"
              :disabled="saving"
              @click="handleSaveTest('Draft')"
            >
              {{ t.saveDraft }}
            </Button>
          </div>
        </Card>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- Bulk Import Questions Modal                                    -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <Modal
      v-model="showBulkImportModal"
      :title="lang === 'kh' ? 'នាំចូលវិញ្ញាសាជាដុំ' : 'Bulk Question Importer'"
      max-width="2xl"
    >
      <div class="space-y-3.5">
        <!-- Mode Tabs: Upload Word File vs Import JSON -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-xl border border-slate-200/60">
          <button
            type="button"
            class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            :class="bulkImportTab === 'upload' ? 'bg-white text-blue-700 shadow-soft-xs' : 'text-slate-600 hover:text-slate-900'"
            @click="switchBulkImportTab('upload')"
          >
            <span class="material-symbols-outlined text-base">upload_file</span>
            <span>{{ lang === 'kh' ? 'ផ្ទុកឯកសារ Word / Docs' : 'Upload Word / Docs File' }}</span>
          </button>
          <button
            type="button"
            class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            :class="bulkImportTab === 'json' ? 'bg-white text-blue-700 shadow-soft-xs' : 'text-slate-600 hover:text-slate-900'"
            @click="switchBulkImportTab('json')"
          >
            <span class="material-symbols-outlined text-base">data_object</span>
            <span>{{ lang === 'kh' ? 'នាំចូល JSON' : 'Import JSON File' }}</span>
          </button>
        </div>

        <!-- 1. Word / Docs File Upload Dropzone (When tab is upload) -->
        <div v-if="bulkImportTab === 'upload'" class="space-y-1.5">
          <input
            ref="docFileInputRef"
            type="file"
            accept=".docx,.doc,.txt"
            class="hidden"
            @change="onDocFileSelected"
          />

          <div
            class="border-2 border-dashed rounded-2xl p-4 text-center transition-all cursor-pointer select-none"
            :class="[
              isDragging
                ? 'border-blue-500 bg-blue-50/80 scale-[0.99]'
                : uploadingDocFile
                ? 'border-slate-300 bg-slate-50'
                : uploadedDocName
                ? 'border-emerald-300 bg-emerald-50/50 hover:bg-emerald-50'
                : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100/80 hover:border-blue-300'
            ]"
            @click="triggerDocFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDocFileDrop"
          >
            <!-- Loading State -->
            <div v-if="uploadingDocFile" class="py-2 flex flex-col items-center justify-center gap-2">
              <span class="h-6 w-6 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
              <p class="text-xs font-bold text-blue-700">{{ lang === 'kh' ? 'កំពុងអានទិន្នន័យពីឯកសារ Word...' : 'Extracting text from Word file...' }}</p>
            </div>

            <!-- Uploaded File Info State -->
            <div v-else-if="uploadedDocName" class="flex items-center justify-between gap-3 px-2 py-1">
              <div class="flex items-center gap-3 min-w-0 text-left">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                  <span class="material-symbols-outlined text-2xl">description</span>
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-emerald-950 truncate">{{ uploadedDocName }}</p>
                  <p class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                    {{ parsedPreviewQuestions.length > 0 ? (lang === 'kh' ? `✓ រកឃើញ ${parsedPreviewQuestions.length} សំណួរដោយជោគជ័យ!` : `✓ Detected ${parsedPreviewQuestions.length} questions!`) : (lang === 'kh' ? 'ឯកសារត្រូវបានអាន' : 'File parsed') }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0" @click.stop>
                <button
                  type="button"
                  class="px-2.5 py-1 text-xs font-bold bg-white border border-emerald-300 text-emerald-800 rounded-lg hover:bg-emerald-100/80 cursor-pointer shadow-soft-xs"
                  @click="triggerDocFileInput"
                >
                  {{ lang === 'kh' ? 'ប្តូរឯកសារ' : 'Change File' }}
                </button>
                <button
                  type="button"
                  class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-white cursor-pointer transition-colors"
                  title="Clear file"
                  @click="clearDocFile"
                >
                  <span class="material-symbols-outlined text-base">close</span>
                </button>
              </div>
            </div>

            <!-- Default Empty Upload State -->
            <div v-else class="py-2 flex flex-col items-center justify-center gap-1">
              <div class="h-10 w-10 rounded-2xl bg-blue-100/80 text-blue-700 flex items-center justify-center mb-0.5">
                <span class="material-symbols-outlined text-2xl">upload_file</span>
              </div>
              <p class="text-xs font-bold text-slate-800">
                {{ lang === 'kh' ? 'ចុចទីនេះដើម្បី Upload File Word / Docs ឬ អូសទម្លាក់' : 'Click to Upload Word / Docs file or Drag & Drop' }}
              </p>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ lang === 'kh' ? 'គាំទ្រឯកសារ .docx, .doc, .txt (អានសំណួរ រូបមន្តគណិត និងចម្លើយស្វ័យប្រវត្តិ)' : 'Supports .docx, .doc, .txt files (Auto-extracts questions, formulas & choices)' }}
              </p>
            </div>
          </div>
        </div>

        <!-- 2. JSON File Upload Dropzone (When tab is json) -->
        <div v-else class="space-y-1.5">
          <input
            ref="jsonFileInputRef"
            type="file"
            accept=".json"
            class="hidden"
            @change="onJsonFileSelected"
          />

          <div
            class="border-2 border-dashed rounded-2xl p-4 text-center transition-all cursor-pointer select-none"
            :class="[
              isDraggingJson
                ? 'border-blue-500 bg-blue-50/80 scale-[0.99]'
                : uploadingJsonFile
                ? 'border-slate-300 bg-slate-50'
                : uploadedJsonName
                ? 'border-emerald-300 bg-emerald-50/50 hover:bg-emerald-50'
                : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100/80 hover:border-blue-300'
            ]"
            @click="triggerJsonFileInput"
            @dragover.prevent="isDraggingJson = true"
            @dragleave.prevent="isDraggingJson = false"
            @drop.prevent="onJsonFileDrop"
          >
            <!-- Loading State -->
            <div v-if="uploadingJsonFile" class="py-2 flex flex-col items-center justify-center gap-2">
              <span class="h-6 w-6 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
              <p class="text-xs font-bold text-blue-700">{{ lang === 'kh' ? 'កំពុងអានឯកសារ JSON...' : 'Reading JSON file...' }}</p>
            </div>

            <!-- Uploaded File Info State -->
            <div v-else-if="uploadedJsonName" class="flex items-center justify-between gap-3 px-2 py-1">
              <div class="flex items-center gap-3 min-w-0 text-left">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                  <span class="material-symbols-outlined text-2xl">data_object</span>
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-emerald-950 truncate">{{ uploadedJsonName }}</p>
                  <p class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                    {{ parsedPreviewQuestions.length > 0 ? (lang === 'kh' ? `✓ រកឃើញ ${parsedPreviewQuestions.length} សំណួរដោយជោគជ័យ!` : `✓ Detected ${parsedPreviewQuestions.length} questions!`) : (lang === 'kh' ? 'ឯកសារត្រូវបានអាន' : 'File parsed') }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0" @click.stop>
                <button
                  type="button"
                  class="px-2.5 py-1 text-xs font-bold bg-white border border-emerald-300 text-emerald-800 rounded-lg hover:bg-emerald-100/80 cursor-pointer shadow-soft-xs"
                  @click="triggerJsonFileInput"
                >
                  {{ lang === 'kh' ? 'ប្តូរឯកសារ' : 'Change File' }}
                </button>
                <button
                  type="button"
                  class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-white cursor-pointer transition-colors"
                  title="Clear file"
                  @click="clearJsonFile"
                >
                  <span class="material-symbols-outlined text-base">close</span>
                </button>
              </div>
            </div>

            <!-- Default Empty JSON State -->
            <div v-else class="py-2 flex flex-col items-center justify-center gap-1">
              <div class="h-10 w-10 rounded-2xl bg-purple-100/80 text-purple-700 flex items-center justify-center mb-0.5">
                <span class="material-symbols-outlined text-2xl">data_object</span>
              </div>
              <p class="text-xs font-bold text-slate-800">
                {{ lang === 'kh' ? 'ចុចទីនេះដើម្បី Upload File .JSON ឬ អូសទម្លាក់' : 'Click to Upload .JSON file or Drag & Drop' }}
              </p>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ lang === 'kh' ? 'គាំទ្រឯកសារ .json ស្តង់ដារ (questions array)' : 'Supports standard .json schema with questions array' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Collapsible Format Guide Banner -->
        <div class="rounded-xl border border-blue-100 bg-blue-50/60 overflow-hidden text-xs text-blue-900">
          <button
            type="button"
            class="w-full p-2.5 flex items-center justify-between font-bold hover:bg-blue-100/50 transition-colors cursor-pointer"
            @click="showFormatGuide = !showFormatGuide"
          >
            <div class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-sm text-blue-600">info</span>
              <span>{{ lang === 'kh' ? 'របៀបសរសេរទម្រង់សំណួរ (គាំទ្រលេខខ្មែរ ១, ២, ដាក់ពិន្ទុ (៤ពិន្ទុ) និង ក, ខ)' : 'Question Format Guide (Supports Khmer numerals, Points (4 pts), & choices)' }}</span>
            </div>
            <span class="material-symbols-outlined text-base text-blue-600 transition-transform" :class="showFormatGuide ? 'rotate-180' : ''">
              expand_more
            </span>
          </button>

          <div v-if="showFormatGuide" class="p-3 pt-0 space-y-2 border-t border-blue-100/80 mt-1">
            <pre class="bg-white/90 p-2.5 rounded-lg font-mono text-[11px] text-slate-700 border border-blue-200/60 overflow-x-auto leading-relaxed">
១. (៤ពិន្ទុ) តើ HTML តំណាងឱ្យអ្វី?
ក. HyperText Markup Language *
ខ. High Tech Machine Language
គ. Hyper Tool Multi Language
ឃ. Home Text Markup Language

២. (4 pts) តើ 1 + 1 + 1 ស្មើប៉ុន្មាន?
ក. ២ ខ. ១ គ. ៣ * ឃ. ៤ </pre>
            <p class="text-[11px] text-blue-700">
              {{ lang === 'kh' ? '💡 គាំទ្រ៖ លេខខ្មែរ (១., ២.), ដាក់ពិន្ទុតាមសំណួរ ((៤ពិន្ទុ) ឬ (4 pts)), អក្សរខ្មែរ (ក., ខ., គ., ឃ.), និងសញ្ញាសម្គាល់ចម្លើយត្រឹមត្រូវ (*, (ត្រឹមត្រូវ), (correct))' : '💡 Supports: Khmer numerals (១., ២.), Points per question ((4 pts) or (៤ពិន្ទុ)), Khmer letters (ក., ខ.), and correct answer marks (*, (ត្រឹមត្រូវ), (correct))' }}
            </p>
          </div>
        </div>

        <!-- Textarea input -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
              {{ lang === 'kh' ? 'ពិនិត្យ ឬបិទភ្ជាប់អត្ថបទសំណួរ៖' : 'Review or Paste Questions Content:' }}
            </label>
            <div class="flex items-center gap-2.5">
              <button
                type="button"
                class="text-xs text-blue-600 font-semibold hover:underline cursor-pointer"
                @click="bulkImportTab === 'json' ? insertSampleJson() : insertSampleBulkText()"
              >
                {{ lang === 'kh' ? 'បញ្ចូលឧទាហរណ៍គំរូ' : 'Insert Sample' }}
              </button>
              <span v-if="bulkInputText" class="text-slate-300">|</span>
              <button
                v-if="bulkInputText"
                type="button"
                class="text-xs text-slate-500 font-semibold hover:text-rose-600 hover:underline cursor-pointer"
                @click="clearBulkInput"
              >
                {{ lang === 'kh' ? 'សម្អាត' : 'Clear' }}
              </button>
            </div>
          </div>
          <textarea
            v-model="bulkInputText"
            :rows="bulkImportTab === 'upload' ? 4 : 7"
            class="w-full p-3 font-mono text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition-all text-slate-800 placeholder:text-slate-400 resize-none"
            :placeholder="lang === 'kh' ? 'អត្ថបទពី File Word នឹងបង្ហាញនៅទីនេះ ឬអាចចម្លងបិទភ្ជាប់ដោយផ្ទាល់...' : 'Extracted text from Word file will appear here, or you can paste directly...'"
            @input="runBulkParser"
          ></textarea>
        </div>

        <!-- Toggle: Auto-skip Example Questions (0.) -->
        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-blue-600">help_center</span>
            <div>
              <span class="font-bold text-slate-800">{{ lang === 'kh' ? 'រំលងសំណួរគំរូ (សំណួរ ០ / Example)' : 'Auto-Skip Example Questions (0.)' }}</span>
              <p class="text-[11px] text-slate-500">{{ lang === 'kh' ? 'រំលងសំណួរទី ០ នៃផ្នែកនីមួយៗដែលជាលំហាត់គំរូមានស្រាប់ចម្លើយលើវិញ្ញាសា' : 'Automatically skip question 0 in sections where answers are already filled as an example' }}</p>
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer shrink-0">
            <input type="checkbox" v-model="skipExampleQuestions" @change="runBulkParser" class="sr-only peer">
            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Live Parsed Preview Box -->
        <div v-if="parsedPreviewQuestions.length > 0" class="p-3 bg-emerald-50/80 rounded-xl border border-emerald-200 text-xs space-y-2">
          <div class="flex items-center justify-between font-bold text-emerald-900">
            <span class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
              <span>{{ lang === 'kh' ? `បានរកឃើញ ${parsedPreviewQuestions.length} សំណួរត្រឹមត្រូវ!` : `Detected ${parsedPreviewQuestions.length} valid questions!` }}</span>
            </span>
            <div class="flex items-center gap-1.5">
              <span v-if="skippedExamplesCount > 0 && skipExampleQuestions" class="text-[11px] font-bold bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                {{ lang === 'kh' ? `បានរំលង ${skippedExamplesCount} សំណួរគំរូ (០)` : `Skipped ${skippedExamplesCount} examples (0)` }}
              </span>
              <span class="text-[11px] font-mono bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">
                {{ parsedPreviewQuestions.reduce((acc, q) => acc + q.answers.length, 0) }} {{ lang === 'kh' ? 'ជម្រើសសរុប' : 'total options' }}
              </span>
            </div>
          </div>

          <!-- Scrollable Live Preview List of Parsed Questions with Math Rendering -->
          <div class="mt-2 max-h-52 overflow-y-auto space-y-2 pr-1 border-t border-emerald-200/60 pt-2">
            <div
              v-for="(pq, pqIdx) in parsedPreviewQuestions"
              :key="pqIdx"
              class="p-2.5 bg-white rounded-lg border border-emerald-100 shadow-soft-xs text-xs space-y-1.5"
            >
              <!-- Section Reading Passage Banner in Preview -->
              <div
                v-if="pq.passage && (pqIdx === 0 || parsedPreviewQuestions[pqIdx - 1]?.passage !== pq.passage)"
                class="p-2 rounded bg-blue-50 border border-blue-200/80 text-[11px] text-blue-950 mb-1 space-y-0.5"
              >
                <div class="flex items-center gap-1 font-bold text-blue-800">
                  <span class="material-symbols-outlined text-xs">menu_book</span>
                  <span>{{ lang === 'kh' ? 'អត្ថបទអាន (Reading Passage)' : 'Reading Passage' }}</span>
                </div>
                <p class="line-clamp-2 text-slate-700 italic font-normal leading-relaxed">{{ pq.passage }}</p>
              </div>

              <div class="flex items-start justify-between gap-2 font-medium text-slate-800">
                <div class="flex items-center gap-1.5 shrink-0">
                  <span class="font-bold text-emerald-800">{{ pqIdx + 1 }}. ({{ pq.points }}ពិន្ទុ)</span>
                  <span v-if="pq.passage" class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 text-[9px] font-bold">
                    <span class="material-symbols-outlined text-[10px]">menu_book</span> Reading
                  </span>
                </div>
                <span class="flex-1" v-html="renderMath(pq.text)"></span>
              </div>
              <div class="grid grid-cols-2 gap-1.5 pt-1 pl-2">
                <div
                  v-for="(ans, aIdx) in pq.answers"
                  :key="aIdx"
                  :class="[
                    'px-2 py-1 rounded text-[11px] flex items-center gap-1.5',
                    ans.correct ? 'bg-emerald-100/90 text-emerald-950 font-bold border border-emerald-300' : 'bg-slate-50 text-slate-700 border border-slate-100'
                  ]"
                >
                  <span class="text-slate-400 font-mono shrink-0">{{ ['ក', 'ខ', 'គ', 'ឃ', 'ង', 'ច'][aIdx] || String.fromCharCode(65 + aIdx) }}.</span>
                  <span class="truncate" v-html="renderMath(ans.text)"></span>
                  <span v-if="ans.correct" class="material-symbols-outlined text-xs text-emerald-700 ml-auto shrink-0">check_circle</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="bulkInputText.trim()" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 flex items-center gap-2">
          <span class="material-symbols-outlined text-amber-600 text-base">warning</span>
          <span>{{ bulkImportTab === 'json' ? (lang === 'kh' ? 'មិនទាន់រកឃើញទម្រង់ JSON ត្រឹមត្រូវទេ។ សូមពិនិត្យមើលទម្រង់គំរូខាងលើ។' : 'No valid JSON questions detected yet. Please check the sample format above.') : (lang === 'kh' ? 'មិនទាន់រកឃើញទម្រង់សំណួរត្រឹមត្រូវទេ។ សូមពិនិត្យមើលទម្រង់គំរូខាងលើ។' : 'No questions detected yet. Please check the sample format above.') }}</span>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-between w-full">
          <Button
            variant="outline"
            size="sm"
            @click="showBulkImportModal = false"
          >
            {{ t.cancel }}
          </Button>

          <Button
            variant="primary"
            size="sm"
            icon="playlist_add_check"
            :disabled="parsedPreviewQuestions.length === 0"
            @click="commitBulkQuestions"
          >
            {{ lang === 'kh' ? `នាំចូល ${parsedPreviewQuestions.length} សំណួរ` : `Import ${parsedPreviewQuestions.length} Questions` }}
          </Button>
        </div>
      </template>
    </Modal>

    <!-- ── Delete Confirm Dialog ───────────────────────────────────── -->
    <ConfirmDialog
      v-model="showDeleteDialog"
      :title="t.deleteExamTitle"
      :message="deleteConfirmMessage"
      :confirm-text="t.delete"
      :cancel-text="t.cancel"
      confirm-variant="danger"
      icon="delete"
      :loading="deleting"
      @confirm="performDeleteTest"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import Input from '../components/ui/Input.vue'
import Modal from '../components/ui/Modal.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import Tabs from '../components/ui/Tabs.vue'
import Pagination from '../components/ui/Pagination.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { renderMath } from '../utils/mathRender'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'
import { logActivity } from '../utils/activityLog'
import { fastCache } from '../stores/fastCache'
import { useRealtimeSync, notifyRealtimeChange } from '../composables/useRealtimeSync'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { can } = usePermissions()

const pageSize = 8
const currentPage = ref(1)

const isBuilderMode = ref(false)
const activeBuilderTab = ref('questions')
const searchQuery = ref('')

const filterSkill = ref('')
const filterGroup = ref('')
const statusFilter = ref('all')

const cachedTests = fastCache.get('tests')
const tests = ref(Array.isArray(cachedTests) && cachedTests.length && cachedTests[0]?.name ? cachedTests : [])
const skills = ref(fastCache.get('skills') || [])
const groups = ref(fastCache.get('groups') || [])
const initialLoading = ref(!tests.value.length)

const editingTestId = ref(null)
const testName = ref('')
const selectedSkillId = ref('')
const selectedGroupId = ref('')
const durationMinutes = ref(45)
const totalMarks = ref(0)
const scheduledAt = ref('')
const finishedAt = ref('')
const questions = ref([])
const activeQuestionIdx = ref(0)
const isEditingQuestionText = ref(false)
const editingOptionIdx = ref(null)

watch(activeQuestionIdx, () => {
  isEditingQuestionText.value = false
  editingOptionIdx.value = null
})

const saving = ref(false)
const exportingWordId = ref(null)
const exportingTxtId = ref(null)

const showDeleteDialog = ref(false)
const testToDelete = ref(null)
const deleting = ref(false)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      examLibraryTitle: 'វិញ្ញាសា & ការប្រឡង',
      librarySubtitle: 'គ្រប់គ្រងបញ្ជីការប្រឡង ចេញផ្សាយ និងនាំចេញឯកសារ',
      createExamTitle: 'បង្កើតការប្រឡងថ្មី',
      editExamTitle: 'កែប្រែការប្រឡង',
      builderSubtitle: 'កំណត់សំណួរ ជម្រើសចម្លើយ ចម្លើយត្រឹមត្រូវ និងជំនាញ',
      createExam: 'បង្កើតវិញ្ញាសាថ្មី',
      backToLibrary: 'ត្រឡប់ទៅបញ្ជីវិញ្ញាសា',
      allSkills: 'ជំនាញទាំងអស់',
      allGroups: 'ក្រុមទាំងអស់',
      reset: 'កំណត់ឡើងវិញ',
      searchPlaceholder: 'ស្វែងរកការប្រឡង...',
      examName: 'ឈ្មោះការប្រឡង',
      skillGroup: 'ជំនាញ & ក្រុម',
      duration: 'រយៈពេល',
      durationMin: 'រយៈពេល (នាទី)',
      questionsMarks: 'សំណួរ & ពិន្ទុ',
      status: 'ស្ថានភាព',
      actions: 'សកម្មភាព',
      noExamsFound: 'រកមិនឃើញការប្រឡងទេ',
      noExamsDesc: 'មិនទាន់មានការប្រឡងត្រូវគ្នានឹងការស្វែងរករបស់អ្នកទេ។',
      questionList: 'បញ្ជីសំណួរ',
      addQuestion: 'បន្ថែមសំណួរ',
      importJSON: 'នាំចូលសំណួរ (JSON)',
      noQuestionsYet: 'មិនទាន់មានសំណួរទេ។ សូមចុច "បន្ថែមសំណួរ"',
      question: 'សំណួរ',
      of: 'នៃ',
      points: 'ពិន្ទុ',
      questionText: 'ខ្លឹមសារសំណួរ',
      enterQuestionContent: 'វាយបញ្ចូលខ្លឹមសារសំណួរនៅទីនេះ...',
      answerOptions: 'ជម្រើសចម្លើយ',
      markCorrectAnswerNotice: 'ចុចលើតួអក្សរដើម្បីកំណត់ជាចម្លើយត្រឹមត្រូវ',
      option: 'ជម្រើស',
      noQuestionSelected: 'មិនមានសំណួរត្រូវបានជ្រើសរើស',
      clickToAddOrSelect: 'ជ្រើសរើសសំណួរពីបញ្ជីខាងឆ្វេង ឬចុចបន្ថែមសំណួរថ្មី។',
      examSettings: 'ការកំណត់ការប្រឡង',
      skill: 'ជំនាញ',
      group: 'ក្រុម',
      scheduledDate: 'កាលបរិច្ឆេទចាប់ផ្តើម',
      finishedDate: 'កាលបរិច្ឆេទបញ្ចប់',
      totalMarks: 'ពិន្ទុសរុប',
      totalQuestions: 'ចំនួនសំណួរ',
      totalScore: 'ពិន្ទុសរុបគណនា',
      publishExam: 'ចេញការប្រឡង (Publish)',
      updateAndPublish: 'រក្សាទុកការកែសម្រួល',
      saveDraft: 'រក្សាទុកជាព្រាង (Draft)',
      edit: 'កែប្រែ',
      deleteExamTitle: 'លុបការប្រឡងនេះ?',
      delete: 'លុប',
      cancel: 'បោះបង់',
      previous: 'មុន',
      next: 'បន្ទាប់'
    }
  }
  return {
    examLibraryTitle: 'Exam Papers & Assessments',
    librarySubtitle: 'Manage tests, question banks, schedule exams, and export documents',
    createExamTitle: 'Create Exam',
    editExamTitle: 'Edit Exam',
    builderSubtitle: 'Configure questions, choices, answer keys, and skill groups',
    createExam: 'Create Exam',
    backToLibrary: 'Back to Exams',
    allSkills: 'All Skills',
    allGroups: 'All Groups',
    reset: 'Reset Filters',
    searchPlaceholder: 'Search exams...',
    examName: 'Exam Name',
    skillGroup: 'Skill & Group',
    duration: 'Duration',
    durationMin: 'Duration (Mins)',
    questionsMarks: 'Questions & Marks',
    status: 'Status',
    actions: 'Actions',
    noExamsFound: 'No exams found',
    noExamsDesc: 'No exams match your search criteria.',
    questionList: 'Questions List',
    addQuestion: 'Add Question',
    importJSON: 'Import JSON',
    noQuestionsYet: 'No questions added yet. Click "Add Question".',
    question: 'Question',
    of: 'of',
    points: 'Points',
    questionText: 'Question Content',
    enterQuestionContent: 'Enter question text here...',
    answerOptions: 'Answer Choices',
    markCorrectAnswerNotice: 'Click letter badge to mark as correct answer',
    option: 'Option',
    noQuestionSelected: 'No question selected',
    clickToAddOrSelect: 'Select a question on the left or click Add Question.',
    examSettings: 'Exam Settings',
    skill: 'Skill',
    group: 'Group',
    scheduledDate: 'Start Date & Time',
    finishedDate: 'End Date & Time',
    totalMarks: 'Total Marks',
    totalQuestions: 'Total Questions',
    totalScore: 'Calculated Score',
    publishExam: 'Publish Exam',
    updateAndPublish: 'Save Changes',
    saveDraft: 'Save as Draft',
    edit: 'Edit',
    deleteExamTitle: 'Delete Exam?',
    delete: 'Delete',
    cancel: 'Cancel',
    previous: 'Previous',
    next: 'Next'
  }
})

const statusTabs = computed(() => [
  { label: lang.value === 'kh' ? 'ទាំងអស់' : 'All', value: 'all' },
  { label: lang.value === 'kh' ? 'បានចេញផ្សាយ' : 'Published', value: 'published' },
  { label: lang.value === 'kh' ? 'ព្រាង' : 'Draft', value: 'draft' }
])

const builderTabs = computed(() => [
  { label: t.value.questionList, value: 'questions' },
  { label: t.value.question, value: 'editor' },
  { label: t.value.examSettings, value: 'settings' }
])

const getQuestionNum = (idx) => {
  const q = questions.value[idx]
  if (!q) return idx + 1
  if (q.isExample) return 0
  let count = 0
  for (let i = idx; i >= 0; i--) {
    if (questions.value[i]?.isExample) break
    count++
  }
  return count
}

const togglePassageForCurrent = () => {
  if (!currentEditingQuestion.value) return
  if (currentEditingQuestion.value.passage) {
    currentEditingQuestion.value.passage = ''
  } else {
    const prevPassage = activeQuestionIdx.value > 0 ? questions.value[activeQuestionIdx.value - 1]?.passage : ''
    currentEditingQuestion.value.passage = prevPassage || 'Text: '
  }
}

const applyPassageToNextQuestions = () => {
  if (!currentEditingQuestion.value || !currentEditingQuestion.value.passage) return
  const p = currentEditingQuestion.value.passage
  for (let i = activeQuestionIdx.value + 1; i < questions.value.length; i++) {
    questions.value[i].passage = p
  }
  toastSuccess(lang.value === 'kh' ? 'បានអនុវត្តអត្ថបទអានទៅកាន់សំណួរបន្ទាប់ទាំងអស់' : 'Applied passage to all following questions')
}

const filteredAndStatusTests = computed(() => {
  let list = tests.value || []
  if (filterSkill.value) {
    list = list.filter(t => t?.skill === filterSkill.value)
  }
  if (filterGroup.value) {
    list = list.filter(t => (t?.group || t?.allGroups) === filterGroup.value)
  }
  if (statusFilter.value !== 'all') {
    list = list.filter(t => (t?.status || '').toLowerCase() === statusFilter.value)
  }
  return list
})

const filteredTests = computed(() => {
  let list = filteredAndStatusTests.value
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(t =>
      (t.name && t.name.toLowerCase().includes(q)) ||
      (t.skill && t.skill.toLowerCase().includes(q)) ||
      (t.group && t.group.toLowerCase().includes(q))
    )
  }
  return list
})

const paginatedTests = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredTests.value.slice(start, start + pageSize)
})

const currentEditingQuestion = computed(() => {
  return questions.value[activeQuestionIdx.value] || null
})

const calculatedTotalMarks = computed(() => {
  return questions.value.reduce((sum, q) => sum + (parseInt(q.points) || 1), 0)
})

const deleteConfirmMessage = computed(() => {
  if (!testToDelete.value) return ''
  return lang.value === 'kh'
    ? `តើអ្នកពិតជាចង់លុបការប្រឡង "${testToDelete.value.name}" មែនទេ? សំណួរ និងចម្លើយទាំងអស់នឹងត្រូវបានលុបជាអចិន្ត្រៃយ៍។`
    : `Are you sure you want to delete "${testToDelete.value.name}"? All associated questions and choices will be permanently deleted.`
})

const resetFilters = () => {
  filterSkill.value = ''
  filterGroup.value = ''
  statusFilter.value = 'all'
  searchQuery.value = ''
}

const formatDateTime = (dtStr) => {
  if (!dtStr) return ''
  try {
    const d = new Date(String(dtStr).replace(' ', 'T'))
    if (isNaN(d.getTime())) return String(dtStr)
    return d.toLocaleString(lang.value === 'kh' ? 'km-KH' : 'en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    })
  } catch (e) {
    return String(dtStr)
  }
}

const loadData = async (isBackground = false) => {
  try {
    const [testsRes, sgRes] = await Promise.all([
      axios.get('/api/admin/tests'),
      axios.get('/api/admin/skills-groups')
    ])
    tests.value = testsRes.data.tests || []
    skills.value = sgRes.data.skills || []
    groups.value = sgRes.data.groups || []

    fastCache.set('tests', tests.value)
    fastCache.set('skills', skills.value)
    fastCache.set('groups', groups.value)
  } catch (e) {
    if (!isBackground) {
      console.error('Failed to load tests or skills-groups:', e)
      if (!tests.value.length) {
        toastError(lang.value === 'kh' ? 'មិនអាចទាញយកទិន្នន័យបានទេ' : 'Failed to load data')
      }
    }
  } finally {
    initialLoading.value = false
  }
}

useRealtimeSync(() => {
  if (!isBuilderMode.value) {
    loadData(true)
  }
}, 4000)

const openCreateBuilder = async () => {
  editingTestId.value = null
  testName.value = ''
  if (!skills.value || skills.value.length === 0) {
    await loadData(true)
  }
  selectedSkillId.value = skills.value[0]?.SkillId || ''
  selectedGroupId.value = ''
  durationMinutes.value = 45
  totalMarks.value = 1
  scheduledAt.value = ''
  finishedAt.value = ''
  questions.value = []
  activeQuestionIdx.value = 0
  addQuestionAndOpen()
  isBuilderMode.value = true
}

const returnToLibrary = () => {
  isBuilderMode.value = false
  loadData(true)
}

const editTest = async (test) => {
  try {
    if (!skills.value || skills.value.length === 0) {
      await loadData(true)
    }
    const res = await axios.get(`/api/admin/tests/${test.id}`)
    const tData = res.data.test
    editingTestId.value = tData.id
    testName.value = tData.name
    selectedSkillId.value = tData.skillId
    selectedGroupId.value = tData.groupId || ''
    durationMinutes.value = tData.durationMinutes
    totalMarks.value = tData.totalMarks
    scheduledAt.value = tData.scheduledAt ? tData.scheduledAt.replace(' ', 'T').substring(0, 16) : ''
    finishedAt.value = tData.finishedAt ? tData.finishedAt.replace(' ', 'T').substring(0, 16) : ''

    questions.value = (tData.questions || []).map(q => {
      const correctIdx = q.answers.findIndex(a => a.correct)
      return {
        id: q.id,
        text: q.text,
        passage: q.passage || '',
        isExample: !!q.isExample,
        points: q.points || 1,
        correctIndex: correctIdx >= 0 ? correctIdx : 0,
        answers: q.answers.map(a => ({ id: a.id, text: a.text, correct: a.correct }))
      }
    })

    activeQuestionIdx.value = 0
    isBuilderMode.value = true
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចទាញយកសំណួរបានទេ' : 'Failed to load questions')
  }
}

const selectQuestion = (idx) => {
  activeQuestionIdx.value = idx
  activeBuilderTab.value = 'editor'
}

const addQuestionAndOpen = () => {
  questions.value.push({
    text: '',
    passage: '',
    isExample: false,
    points: 1,
    correctIndex: 0,
    answers: [
      { text: '', correct: true },
      { text: '', correct: false },
      { text: '', correct: false },
      { text: '', correct: false }
    ]
  })
  activeQuestionIdx.value = questions.value.length - 1
  updateTotalMarks()
}

const removeQuestion = (idx) => {
  questions.value.splice(idx, 1)
  if (activeQuestionIdx.value >= questions.value.length) {
    activeQuestionIdx.value = Math.max(0, questions.value.length - 1)
  }
  updateTotalMarks()
}

const setCorrectAnswer = (aIdx) => {
  if (!currentEditingQuestion.value) return
  currentEditingQuestion.value.correctIndex = aIdx
  currentEditingQuestion.value.answers.forEach((a, i) => {
    a.correct = (i === aIdx)
  })
}

const addAnswerOption = () => {
  if (!currentEditingQuestion.value) return
  currentEditingQuestion.value.answers.push({ text: '', correct: false })
}

const removeAnswerOption = (aIdx) => {
  if (!currentEditingQuestion.value || currentEditingQuestion.value.answers.length <= 2) return
  currentEditingQuestion.value.answers.splice(aIdx, 1)
  if (currentEditingQuestion.value.correctIndex >= currentEditingQuestion.value.answers.length) {
    setCorrectAnswer(0)
  }
}

const updateTotalMarks = () => {
  totalMarks.value = calculatedTotalMarks.value
}

const handleSaveTest = async (status = 'Published') => {
  if (!testName.value.trim()) {
    toastError(lang.value === 'kh' ? 'សូមបញ្ចូលឈ្មោះការប្រឡង' : 'Please enter exam name')
    return
  }
  if (!selectedSkillId.value) {
    toastError(lang.value === 'kh' ? 'សូមជ្រើសរើសជំនាញ' : 'Please select a skill')
    return
  }
  if (questions.value.length === 0) {
    toastError(lang.value === 'kh' ? 'សូមបន្ថែមសំណួរយ៉ាងហោចណាស់ ១' : 'Please add at least 1 question')
    return
  }

  // Validate questions
  for (let i = 0; i < questions.value.length; i++) {
    const q = questions.value[i]
    if (!q.text.trim()) {
      toastError(lang.value === 'kh' ? `សំណួរទី ${i + 1} មិនទាន់មានខ្លឹមសារនៅឡើយទេ` : `Question ${i + 1} text is empty`)
      activeQuestionIdx.value = i
      activeBuilderTab.value = 'editor'
      return
    }
    const filledAnswers = q.answers.filter(a => a.text.trim())
    if (filledAnswers.length < 2) {
      toastError(lang.value === 'kh' ? `សំណួរទី ${i + 1} ត្រូវមានចម្លើយយ៉ាងហោចណាស់ ២` : `Question ${i + 1} must have at least 2 non-empty choices`)
      activeQuestionIdx.value = i
      activeBuilderTab.value = 'editor'
      return
    }
    if (q.correctIndex === null || q.correctIndex >= q.answers.length) {
      toastError(lang.value === 'kh' ? `សូមជ្រើសរើសចម្លើយត្រឹមត្រូវសម្រាប់សំណួរទី ${i + 1}` : `Please select a correct answer for question ${i + 1}`)
      activeQuestionIdx.value = i
      activeBuilderTab.value = 'editor'
      return
    }
  }

  saving.value = true
  try {
    const payload = {
      name: testName.value,
      skillId: selectedSkillId.value,
      groupId: selectedGroupId.value || null,
      durationMinutes: durationMinutes.value,
      totalMarks: calculatedTotalMarks.value || 1,
      scheduledAt: scheduledAt.value ? scheduledAt.value.replace('T', ' ') + ':00' : null,
      finishedAt: finishedAt.value ? finishedAt.value.replace('T', ' ') + ':00' : null,
      status: status,
      questions: questions.value.map(q => ({
        id: q.id || null,
        text: q.text,
        passage: q.passage || null,
        isExample: !!q.isExample,
        points: parseInt(q.points) || 1,
        answers: q.answers.map((a, i) => ({
          id: a.id || null,
          text: a.text,
          correct: i === q.correctIndex
        }))
      }))
    }

    if (editingTestId.value) {
      await axios.put(`/api/admin/tests/${editingTestId.value}`, payload)
      toastSuccess(lang.value === 'kh' ? 'បានកែប្រែការប្រឡងជោគជ័យ' : 'Exam updated successfully')
      logActivity('UPDATE_EXAM', `Updated exam: ${testName.value}`)
    } else {
      await axios.post('/api/admin/tests', payload)
      toastSuccess(lang.value === 'kh' ? 'បានបង្កើតការប្រឡងជោគជ័យ' : 'Exam created successfully')
      logActivity('CREATE_EXAM', `Created exam: ${testName.value}`)
    }

    isBuilderMode.value = false
    notifyRealtimeChange('tests_updated')
    await loadData(true)
  } catch (e) {
    toastError(e.response?.data?.message || (lang.value === 'kh' ? 'មានបញ្ហាក្នុងការរក្សាទុក' : 'Failed to save exam'))
  } finally {
    saving.value = false
  }
}

const confirmDeleteTest = (test) => {
  testToDelete.value = test
  showDeleteDialog.value = true
}

const performDeleteTest = async () => {
  if (!testToDelete.value) return
  deleting.value = true
  try {
    await axios.delete(`/api/admin/tests/${testToDelete.value.id}`)
    toastSuccess(lang.value === 'kh' ? 'បានលុបការប្រឡងជោគជ័យ' : 'Exam deleted successfully')
    logActivity('DELETE_EXAM', `Deleted exam: ${testToDelete.value.name}`)
    showDeleteDialog.value = false
    testToDelete.value = null
    notifyRealtimeChange('tests_updated')
    await loadData(true)
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចលុបការប្រឡងបានទេ' : 'Failed to delete exam')
  } finally {
    deleting.value = false
  }
}

const exportTestToWord = async (test) => {
  exportingWordId.value = test.id
  try {
    const res = await axios.get(`/api/admin/tests/${test.id}/export-word`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Exam_${test.name.replace(/\s+/g, '_')}.docx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toastSuccess(lang.value === 'kh' ? 'ទាញយកឯកសារ Word (.docx) ជោគជ័យ' : 'Word document downloaded')
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចទាញយក Word បានទេ' : 'Failed to export Word')
  } finally {
    exportingWordId.value = null
  }
}

const exportTestToTxt = async (test) => {
  exportingTxtId.value = test.id
  try {
    const res = await axios.get(`/api/admin/tests/${test.id}/export-txt`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Exam_${test.name.replace(/\s+/g, '_')}.txt`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toastSuccess(lang.value === 'kh' ? 'ទាញយកឯកសារ Text (.txt) ជោគជ័យ' : 'Text file downloaded')
  } catch (e) {
    toastError(lang.value === 'kh' ? 'មិនអាចទាញយក Text (.txt) បានទេ' : 'Failed to export Text file')
  } finally {
    exportingTxtId.value = null
  }
}

// ══════════════════════════════════════════════════════════════════════
// Bulk Question Importer State & Functions
// ══════════════════════════════════════════════════════════════════════
const showBulkImportModal = ref(false)
const bulkInputText = ref('')
const bulkPointsPerQuestion = ref(1)
const parsedPreviewQuestions = ref([])
const skipExampleQuestions = ref(false)
const skippedExamplesCount = ref(0)
const docFileInputRef = ref(null)
const uploadingDocFile = ref(false)
const uploadedDocName = ref('')
const isDragging = ref(false)
const showFormatGuide = ref(false)

const jsonFileInputRef = ref(null)
const uploadingJsonFile = ref(false)
const uploadedJsonName = ref('')
const isDraggingJson = ref(false)

const bulkImportTab = ref('upload')

const openBulkImportModal = () => {
  bulkInputText.value = ''
  parsedPreviewQuestions.value = []
  bulkPointsPerQuestion.value = 1
  uploadedDocName.value = ''
  uploadedJsonName.value = ''
  uploadingDocFile.value = false
  uploadingJsonFile.value = false
  showFormatGuide.value = false
  skipExampleQuestions.value = false
  skippedExamplesCount.value = 0
  bulkImportTab.value = 'upload'
  showBulkImportModal.value = true
}

const switchBulkImportTab = (tab) => {
  bulkImportTab.value = tab
}

const triggerDocFileInput = () => {
  docFileInputRef.value?.click()
}

const clearDocFile = () => {
  uploadedDocName.value = ''
  bulkInputText.value = ''
  parsedPreviewQuestions.value = []
  if (docFileInputRef.value) docFileInputRef.value.value = ''
}

const triggerJsonFileInput = () => {
  jsonFileInputRef.value?.click()
}

const clearJsonFile = () => {
  uploadedJsonName.value = ''
  bulkInputText.value = ''
  parsedPreviewQuestions.value = []
  if (jsonFileInputRef.value) jsonFileInputRef.value.value = ''
}

const clearBulkInput = () => {
  bulkInputText.value = ''
  uploadedDocName.value = ''
  uploadedJsonName.value = ''
  parsedPreviewQuestions.value = []
  if (docFileInputRef.value) docFileInputRef.value.value = ''
  if (jsonFileInputRef.value) jsonFileInputRef.value.value = ''
}

const onDocFileSelected = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  await processDocFile(file)
}

const onDocFileDrop = async (event) => {
  isDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (!file) return
  await processDocFile(file)
}

const processDocFile = async (file) => {
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['docx', 'doc', 'txt'].includes(ext)) {
    toastError(lang.value === 'kh' ? 'សូមជ្រើសរើសឯកសារ Word (.docx, .doc) ឬ Text (.txt)' : 'Please select a Word (.docx, .doc) or Text (.txt) file')
    return
  }

  uploadingDocFile.value = true
  uploadedDocName.value = file.name

  try {
    let extractedText = ''

    if (ext === 'txt') {
      extractedText = await file.text()
    } else {
      // Use backend OMML equation parser endpoint
      const formData = new FormData()
      formData.append('file', file)
      const res = await axios.post('/api/admin/tests/parse-doc', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      if (res.data?.text) {
        extractedText = res.data.text
      }
    }

    if (extractedText.trim()) {
      bulkInputText.value = extractedText
      runBulkParser()
      toastSuccess(lang.value === 'kh' ? `បានអានឯកសារ '${file.name}' ដោយជោគជ័យ!` : `Successfully loaded '${file.name}'!`)
    } else {
      toastError(lang.value === 'kh' ? 'មិនអាចអានទិន្នន័យពីឯកសារ Word នេះបានទេ' : 'Could not extract text from this document')
    }
  } catch (err) {
    toastError(lang.value === 'kh' ? 'មានបញ្ហាក្នុងការអានឯកសារ' : 'Error reading document')
  } finally {
    uploadingDocFile.value = false
    if (docFileInputRef.value) docFileInputRef.value.value = ''
  }
}

const onJsonFileSelected = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return
  await processJsonFile(file)
}

const onJsonFileDrop = async (event) => {
  isDraggingJson.value = false
  const file = event.dataTransfer?.files?.[0]
  if (!file) return
  await processJsonFile(file)
}

const processJsonFile = async (file) => {
  const ext = file.name.split('.').pop().toLowerCase()
  if (ext !== 'json') {
    toastError(lang.value === 'kh' ? 'សូមជ្រើសរើសឯកសារ .json' : 'Please select a .json file')
    return
  }

  uploadingJsonFile.value = true
  uploadedJsonName.value = file.name

  try {
    const text = await file.text()
    bulkInputText.value = text
    runBulkParser()
    if (parsedPreviewQuestions.value.length > 0) {
      toastSuccess(lang.value === 'kh' ? `បានអាន ${parsedPreviewQuestions.value.length} សំណួរពី '${file.name}'!` : `Successfully loaded ${parsedPreviewQuestions.value.length} questions from '${file.name}'!`)
    } else {
      toastError(lang.value === 'kh' ? 'ឯកសារ JSON មិនមានទម្រង់សំណួរត្រឹមត្រូវទេ' : 'JSON file does not have valid questions format')
    }
  } catch (err) {
    toastError(lang.value === 'kh' ? 'មិនអាចអានឯកសារ JSON បានទេ' : 'Failed to parse JSON file')
  } finally {
    uploadingJsonFile.value = false
    if (jsonFileInputRef.value) jsonFileInputRef.value.value = ''
  }
}

const insertSampleBulkText = () => {
  bulkInputText.value = `១. (៤ពិន្ទុ) តើ HTML តំណាងឱ្យអ្វី?
ក. HyperText Markup Language *
ខ. High Tech Machine Language
គ. Hyper Tool Multi Language
ឃ. Home Text Markup Language

២. (៤ពិន្ទុ) ចម្លើយរបស់ប្រមាណវិធី √(2025) គឺ៖
ក. 45 *	ខ. 25	គ. 15	ឃ. 35

៣. (៤ពិន្ទុ) ដេរីវេទី១នៃអនុគមន៍ f(x)=e^4x គឺ៖
ក. -4e^3x	ខ. 3e^3x	គ. 3xe^3x	ឃ. 4e^4x *`
  runBulkParser()
}

const insertSampleJson = () => {
  const sample = {
    questions: [
      {
        text: "តើ HTML តំណាងឱ្យអ្វី?",
        points: 4,
        answers: [
          { text: "HyperText Markup Language", correct: true },
          { text: "High Tech Machine Language", correct: false },
          { text: "Hyper Tool Multi Language", correct: false },
          { text: "Home Text Markup Language", correct: false }
        ]
      },
      {
        text: "ចម្លើយរបស់ប្រមាណវិធី √(2025) គឺ៖",
        points: 4,
        answers: [
          { text: "45", correct: true },
          { text: "25", correct: false },
          { text: "15", correct: false },
          { text: "35", correct: false }
        ]
      },
      {
        text: "ដេរីវេទី១នៃអនុគមន៍ f(x)=e^4x គឺ៖",
        points: 4,
        answers: [
          { text: "-4e^3x", correct: false },
          { text: "3e^3x", correct: false },
          { text: "3xe^3x", correct: false },
          { text: "4e^4x", correct: true }
        ]
      }
    ]
  }
  bulkInputText.value = JSON.stringify(sample, null, 2)
  runBulkParser()
}

const runBulkParser = () => {
  const result = parseBulkQuestions(bulkInputText.value, skipExampleQuestions.value)
  parsedPreviewQuestions.value = result.questions
  skippedExamplesCount.value = result.skippedExamples
}

const normalizeJsonQuestions = (rawList, skipExamples = true) => {
  if (!Array.isArray(rawList)) return { questions: [], skippedExamples: 0 }
  let skipped = 0
  const normalized = []
  for (const q of rawList) {
    if (!q || (!q.text && !q.question)) continue
    const qText = q.text || q.question || ''
    const isEx = !!q.isExample || /^(?:(?:សំណួរទី|សំណួរ|Example|Ex|Sample)\s*(?:0|០)|(?:0|០)[\.\)\:\-៖]|Q(?:0|០)[\.\)\:\-៖])/i.test(qText.trim())
    if (skipExamples && isEx) {
      skipped++
      continue
    }

    const qPoints = parseInt(q.points) || parseInt(bulkPointsPerQuestion.value) || 1
    const rawAnswers = Array.isArray(q.answers) ? q.answers : (Array.isArray(q.options) ? q.options : [])

    let correctIdx = -1
    if (typeof q.correctIndex === 'number' && q.correctIndex >= 0 && q.correctIndex < rawAnswers.length) {
      correctIdx = q.correctIndex
    } else if (typeof q.correct_index === 'number' && q.correct_index >= 0 && q.correct_index < rawAnswers.length) {
      correctIdx = q.correct_index
    } else {
      correctIdx = rawAnswers.findIndex(a => (typeof a === 'object' && a !== null ? Boolean(a.correct) : false))
    }
    if (correctIdx < 0) correctIdx = 0

    const answers = rawAnswers.map((a, idx) => {
      if (typeof a === 'string') {
        return { text: a, correct: idx === correctIdx }
      }
      return {
        text: a.text || a.option || '',
        correct: idx === correctIdx || Boolean(a.correct)
      }
    })

    if (qText && answers.length >= 2) {
      normalized.push({
        text: qText,
        passage: q.passage || '',
        isExample: isEx,
        points: qPoints,
        correctIndex: correctIdx,
        answers: answers
      })
    }
  }
  return { questions: normalized, skippedExamples: skipped }
}

const parseBulkQuestions = (rawText, skipExamples = true) => {
  if (!rawText || !rawText.trim()) return { questions: [], skippedExamples: 0 }

  try {
    const json = JSON.parse(rawText.trim())
    if (Array.isArray(json)) {
      const res = normalizeJsonQuestions(json, skipExamples)
      if (res.questions.length > 0) return res
    } else if (json && Array.isArray(json.questions)) {
      const res = normalizeJsonQuestions(json.questions, skipExamples)
      if (res.questions.length > 0) return res
    }
  } catch (e) {}

  const khmerDigits = { '០': '0', '១': '1', '២': '2', '៣': '3', '៤': '4', '៥': '5', '៦': '6', '៧': '7', '៨': '8', '៩': '9' }
  const toArabic = (str) => String(str).replace(/[០-៩]/g, d => khmerDigits[d] || d)
  const cleanBoxes = (s) => s.replace(/[\u25a0-\u25ff\u2610-\u2612\u25aa\u25ab]/g, '').trim()

  const lines = rawText.split(/\r?\n/)
  const parsed = []
  let currentQ = null
  let currentPassage = ''
  let passageBuffer = []
  let inReadingSection = false
  let isSkippingCurrentExample = false
  let skippedCount = 0

  // Question start detection
  const isQStart = (line) => {
    const t = line.trim()
    if (/^\(?\s*[\d\u17E0-\u17E9]+\s*(?:ពិន្ទុ|points?|pts?)\s*\)?/i.test(t)) return true
    if (/^(?:(?:សំណួរទី|សំណួរ|Example|Ex|Sample)\s*[\d\u17E0-\u17E9]+[៖:\.\s\)\-]*|[\d\u17E0-\u17E9]+[\.\)\:\-៖]\s*|Q[\d\u17E0-\u17E9]+[\.\)\:\-៖]\s*)/i.test(t)) return true
    return false
  }

  // Check if a line is an example question
  const isExampleQuestion = (line) => {
    const t = line.trim()
    if (/^(?:(?:សំណួរទី|សំណួរ|Example|Ex|Sample)\s*(?:0|០)|(?:0|០)[\.\)\:\-៖]|Q(?:0|០)[\.\)\:\-៖]|\((?:0|០)\)|\[(?:0|០)\])/i.test(t)) return true
    if (/has\s+been\s+done\s+as\s+an\s+example/i.test(t)) return true
    return false
  }

  // Check if a line begins an answer option
  const isOptionLine = (line) => {
    const t = line.trim()
    return /^(?:[A-Da-d\u1780-\u1783][\.\:\-៖]|\([A-Da-d\u1780-\u1783]\)|\[[A-Da-d\u1780-\u1783]\])/.test(t)
  }

  const isSectionHeader = (line) => {
    const t = line.trim()
    if (/^={3,}|^_{3,}|^-{3,}/.test(t)) return true
    if (/^(?:[I|V|X]+\.|\bPart\s+[A-Za-z0-9]+|\bSection\s+[A-Za-z0-9]+|ផ្នែក\s*[ក-អ០-៩A-Za-z]+|វិញ្ញាសា|ព្រះរាជាណាចក្រ|ក្រសួង|វិទ្យាស្ថាន)/i.test(t)) return true
    return false
  }

  // Regex to split multiple options on the same line
  const optSplitRegex = /\t+|\s{2,}(?=[A-Da-d\u1780-\u1783][\.\:\-៖]|\([A-Da-d\u1780-\u1783]\)|\[[A-Da-d\u1780-\u1783]\])/

  const charToIdx = (char) => {
    if (!char) return 0
    const c = char.trim()
    const khmerConsonants = { 'ក': 0, 'ខ': 1, 'គ': 2, 'ឃ': 3, 'ង': 4, 'ច': 5 }
    if (khmerConsonants[c] !== undefined) return khmerConsonants[c]
    const khmerNum = { '១': 0, '២': 1, '៣': 2, '៤': 3, '៥': 4, '៦': 5 }
    if (khmerNum[c] !== undefined) return khmerNum[c]
    const upper = c.toUpperCase()
    if (upper >= 'A' && upper <= 'F') return upper.charCodeAt(0) - 65
    const num = parseInt(c) - 1
    if (!isNaN(num) && num >= 0) return num
    return 0
  }

  for (let i = 0; i < lines.length; i++) {
    let line = cleanBoxes(lines[i]).trim()
    if (!line) continue

    if (line.match(/reading/i) || line.match(/អត្ថបទអាន/i)) {
      inReadingSection = true
    }

    if (line.match(/^(?:vocabulary|grammar|ផ្នែកទី\s*[១|I]|Part\s+[A-B]:\s*(?:Vocabulary|Grammar))/i)) {
      inReadingSection = false
      currentPassage = ''
      passageBuffer = []
    }

    // A passage header
    if (line.match(/^(?:Text|Passage|Story|អត្ថបទអាន|Reading\s*\d*)\s*[:៖\-]/i) || line.match(/^Part\s+[A-Z0-9]+:\s*Reading/i)) {
      if (passageBuffer.length > 0 && currentQ && currentQ.answers.length > 0) {
        currentPassage = passageBuffer.join('\n\n').trim()
        passageBuffer = []
      }
      passageBuffer.push(line)
      continue
    }

    // Separate Answer Line
    const ansMatch = line.match(/^(?:Answer|Ans|Correct Answer|Correct|ចម្លើយ|ចម្លើយត្រឹមត្រូវ|ចម្លើយត្រូវ)\s*[\:\-\=៖]\s*([A-Fa-f1-6\u1780-\u1785\u17E1-\u17E6])/i)
    if (ansMatch && !isSkippingCurrentExample && currentQ && currentQ.answers.length > 0) {
      const targetIdx = charToIdx(ansMatch[1])
      if (targetIdx >= 0 && targetIdx < currentQ.answers.length) {
        currentQ.correctIndex = targetIdx
        currentQ.answers.forEach((a, idx) => { a.correct = (idx === targetIdx) })
      }
      continue
    }

    if (isQStart(line)) {
      if (passageBuffer.length > 0) {
        currentPassage = passageBuffer.join('\n\n').trim()
        passageBuffer = []
      }

      if (currentQ && currentQ.text && currentQ.answers.length >= 2) {
        parsed.push(currentQ)
        currentQ = null
      }

      // Check if this is an example question (Question 0)
      if (skipExamples && isExampleQuestion(line)) {
        isSkippingCurrentExample = true
        skippedCount++
        currentQ = null
        continue
      } else {
        isSkippingCurrentExample = false
      }

      let qPoints = bulkPointsPerQuestion.value || 1
      const pointMatch = line.match(/\(?\s*([\d\u17E0-\u17E9]+)\s*(?:ពិន្ទុ|points?|pts?|marks?)\s*\)?/i)
      if (pointMatch) {
        qPoints = parseInt(toArabic(pointMatch[1])) || 1
      }

      let cleanText = line
        .replace(/^(?:(?:សំណួរទី|សំណួរ|Example|Ex|Sample)\s*[\d\u17E0-\u17E9]+[៖:\.\s\)\-]*|[\d\u17E0-\u17E9]+[\.\)\:\-៖]\s*|Q[\d\u17E0-\u17E9]+[\.\)\:\-៖]\s*)/i, '')
        .replace(/\(?\s*[\d\u17E0-\u17E9]+\s*(?:ពិន្ទុ|points?|pts?|marks?)\s*\)?/gi, '')
        .trim()

      if (!cleanText) cleanText = line

      const isEx = isExampleQuestion(line)

      currentQ = {
        text: cleanText,
        passage: inReadingSection ? currentPassage : '',
        isExample: isEx,
        points: qPoints,
        correctIndex: 0,
        answers: []
      }
    } else if (isSkippingCurrentExample) {
      continue
    } else if (currentQ) {
      // Check if line contains one or multiple options
      if (isOptionLine(line)) {
        const rawTokens = line.split(optSplitRegex).map(t => t.trim()).filter(Boolean)
        for (const opt of rawTokens) {
          let optText = opt.replace(/^(?:[A-Da-d\u1780-\u1783][\.\:\-៖]|\([A-Da-d\u1780-\u1783]\)|\[[A-Da-d\u1780-\u1783]\])\s*/i, '').trim()
          optText = cleanBoxes(optText)

          let isCorrect = false
          if (/\*$/.test(optText) || /\s\*\s?/.test(optText) || /\(correct\)/i.test(optText) || /\[correct\]/i.test(optText) || /\[x\]/i.test(optText) || /\(ត្រឹមត្រូវ\)/.test(optText) || /\[ត្រឹមត្រូវ\]/.test(optText) || /\(ចម្លើយត្រូវ\)/.test(optText) || /\[ចម្លើយត្រូវ\]/.test(optText) || /\(ត្រូវ\)/.test(optText) || /\[ត្រូវ\]/.test(optText) || /\(ចម្លើយត្រឹមត្រូវ\)/.test(optText) || /\[ចម្លើយត្រឹមត្រូវ\]/.test(optText) || /[✓✔]/.test(optText)) {
            isCorrect = true
            optText = optText.replace(/\*|\(correct\)|\[correct\]|\(ត្រឹមត្រូវ\)|\[ត្រឹមត្រូវ\]|\(ចម្លើយត្រូវ\)|\[ចម្លើយត្រូវ\]|\(ត្រូវ\)|\[ត្រូវ\]|\(ចម្លើយត្រឹមត្រូវ\)|\[ចម្លើយត្រឹមត្រូវ\]|\[x\]|\[X\]|[✓✔]/gi, '').trim()
          }

          const newAns = { text: optText, correct: isCorrect }
          currentQ.answers.push(newAns)

          if (isCorrect) {
            currentQ.correctIndex = currentQ.answers.length - 1
            currentQ.answers.forEach((a, idx) => {
              a.correct = (idx === currentQ.correctIndex)
            })
          }
        }
      } else {
        if (currentQ.answers.length === 0) {
          currentQ.text += ' ' + line
        } else {
          if (isSectionHeader(line) || line.match(/Comprehension Questions/i) || line.match(/^(?:Text|Passage|Story|Part\s+[A-B]|II\.)/i)) {
            if (line.match(/reading/i) || line.match(/អត្ថបទ/i)) inReadingSection = true
            if (line.match(/^(?:vocabulary|grammar)/i)) inReadingSection = false
            if (inReadingSection && !line.match(/Comprehension Questions/i) && !isSectionHeader(line) && !line.match(/Instruction:/i)) {
              passageBuffer.push(line)
            }
          } else if (inReadingSection && !line.match(/Comprehension Questions/i) && !isSectionHeader(line) && !line.match(/Instruction:/i)) {
            passageBuffer.push(line)
          } else {
            currentQ.answers[currentQ.answers.length - 1].text += ' ' + line
          }
        }
      }
    } else {
      if (inReadingSection && !line.match(/Comprehension Questions/i) && !isSectionHeader(line) && !line.match(/Instruction:/i)) {
        passageBuffer.push(line)
      }
    }
  }

  if (currentQ && currentQ.text && currentQ.answers.length >= 2) {
    parsed.push(currentQ)
  }

  parsed.forEach(q => {
    if (q.correctIndex === null || q.correctIndex === undefined || q.correctIndex >= q.answers.length) {
      q.correctIndex = 0
      if (q.answers.length > 0) q.answers[0].correct = true
    }
  })

  return { questions: parsed, skippedExamples: skippedCount }
}

const commitBulkQuestions = () => {
  if (parsedPreviewQuestions.value.length === 0) return

  const toAdd = parsedPreviewQuestions.value.map(q => ({
    text: q.text,
    passage: q.passage || '',
    isExample: !!q.isExample,
    points: parseInt(q.points) || parseInt(bulkPointsPerQuestion.value) || 1,
    correctIndex: q.correctIndex || 0,
    answers: q.answers.map((a, i) => ({
      text: a.text,
      correct: i === q.correctIndex
    }))
  }))

  questions.value = toAdd

  activeQuestionIdx.value = 0
  isEditingQuestionText.value = false
  editingOptionIdx.value = null
  updateTotalMarks()
  bulkInputText.value = ''
  parsedPreviewQuestions.value = []
  showBulkImportModal.value = false
  toastSuccess(lang.value === 'kh' ? `បាននាំចូល ${toAdd.length} សំណួរដោយជោគជ័យ!` : `Successfully imported ${toAdd.length} questions!`)
}

onMounted(() => {
  loadData()
})
</script>
