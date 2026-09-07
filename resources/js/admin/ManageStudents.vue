<template>
  <div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
          {{ t.title }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ t.desc }}
        </p>
      </div>

      <div class="flex items-center gap-2.5 flex-wrap">
        <Button
          v-if="can('Students', 'create')"
          variant="primary"
          icon="person_add"
          size="sm"
          @click="openAddModal"
        >
          {{ t.addStudent }}
        </Button>
      </div>
    </div>

    <!-- Filter Toolbar Card -->
    <Card padding="sm" class="shadow-soft-sm">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <!-- Filter Controls -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 flex-1 max-w-4xl">
          <CustomDropdown
            v-model="filterSkill"
            :options="[{ SkillName: t.allSkills, SkillId: '' }, ...skillsList]"
            labelKey="SkillName"
            valueKey="SkillName"
            :placeholder="t.allSkills"
          />

          <CustomDropdown
            v-model="filterGroup"
            :options="[{ GroupName: t.allGroups, GroupId: '' }, ...groupsList]"
            labelKey="GroupName"
            valueKey="GroupName"
            :placeholder="t.allGroups"
          />

          <CustomDropdown
            v-model="filterExam"
            :options="[{ TestName: t.allExams, TestId: '' }, ...examsList]"
            labelKey="TestName"
            valueKey="TestName"
            :placeholder="t.allExams"
          />

          <CustomDropdown
            v-model="filterExamStatus"
            :options="examStatusOptions"
            labelKey="label"
            valueKey="value"
            :placeholder="t.examStatus"
          />
        </div>

        <!-- Search & Reset -->
        <div class="flex items-center gap-2">
          <IconButton
            v-if="filterSkill || filterGroup || filterExam || filterExamStatus || searchQuery"
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
    </Card>

    <!-- ── Students Table / Cards (Dedicated Students View) ───────────── -->
    <Card padding="none" class="shadow-soft-sm overflow-hidden w-full">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 shrink-0">
              <span class="material-symbols-outlined text-lg">group</span>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-normal">{{ t.studentsList }}</h3>
              <p class="text-[11px] text-slate-400 leading-normal">{{ filteredStudentsList.length }} {{ t.enrolledCount }}</p>
            </div>
          </div>
          <Button
            v-if="can('Students', 'create')"
            variant="outline"
            size="xs"
            icon="add"
            @click="openAddModal"
          >
            {{ t.addStudent }}
          </Button>
        </div>
      </template>

      <!-- Desktop Table with all student columns -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/70">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500 w-12">#</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.student }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.genderShift }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.skillGroup }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.intakeDuration }}</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.examStatus }}</th>
              <th v-if="can('Students', 'edit') || can('Students', 'delete')" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">{{ t.actions }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="(student, index) in paginatedStudents" :key="student.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="px-4 py-3 text-xs font-bold text-slate-400">
                {{ (currentStudentPage - 1) * pageSize + index + 1 }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div v-if="student.photo || student.profileImage" class="h-10 w-10 rounded-xl overflow-hidden shrink-0 border border-blue-100 shadow-sm">
                    <img :src="student.photo || student.profileImage" class="w-full h-full object-cover" />
                  </div>
                  <div v-else class="h-10 w-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-100">
                    {{ (student.name || 'S').charAt(0).toUpperCase() }}
                  </div>
                  <div class="min-w-0">
                    <div class="font-bold text-slate-900 truncate">{{ student.name }}</div>
                    <div class="mt-0.5 space-y-0.5">
                      <div>
                        <span class="font-bold font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded text-[11px] border border-blue-100 inline-block">
                          {{ student.studentCode || ('RTC-' + student.id) }}
                        </span>
                      </div>
                      <div v-if="student.phone" class="text-xs text-slate-400">
                        {{ student.phone }}
                      </div>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-slate-800 text-xs">
                  {{ student.gender === 'Female' ? (lang === 'kh' ? 'ស្រី' : 'Female') : student.gender === 'Other' ? (lang === 'kh' ? 'ផ្សេងៗ' : 'Other') : (lang === 'kh' ? 'ប្រុស' : 'Male') }}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">
                  {{ student.shift === 'Morning' ? (lang === 'kh' ? 'វេនព្រឹក' : 'Morning') : student.shift === 'Afternoon' ? (lang === 'kh' ? 'វេនរសៀល' : 'Afternoon') : student.shift === 'Evening' ? (lang === 'kh' ? 'វេនយប់' : 'Evening') : (student.shift || 'Morning') }}
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                  {{ student.skill }}
                </span>
                <div class="text-[11px] font-medium text-slate-500 mt-1">
                  {{ student.group }}
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="text-xs font-semibold text-slate-700">
                  {{ student.enrolledMonth ? student.enrolledMonth : '' }} {{ student.enrolledYear ? student.enrolledYear : '' }}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">
                  {{ student.durationMonths || '—' }}
                </div>
              </td>
              <td class="px-4 py-3">
                <StatusBadge
                  :status="getStudentExamStatusType(student)"
                  :label="getStudentExamStatusLabel(student)"
                />
                <div
                  v-if="getStudentExamNamesDisplay(student)"
                  class="text-[11px] font-medium text-slate-500 truncate max-w-[200px] mt-0.5"
                  :title="getStudentExamNamesDisplay(student)"
                >
                  {{ getStudentExamNamesDisplay(student) }}
                </div>
              </td>
              <td v-if="can('Students', 'edit') || can('Students', 'delete')" class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <IconButton
                    v-if="can('Students', 'edit')"
                    icon="edit"
                    variant="ghost"
                    size="sm"
                    title="Edit Student"
                    @click="editStudent(student)"
                  />
                  <IconButton
                    v-if="can('Students', 'delete')"
                    icon="delete"
                    variant="ghost"
                    size="sm"
                    title="Delete Student"
                    class="text-red-500 hover:text-red-700 hover:bg-red-50"
                    @click="confirmDeleteStudent(student)"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="initialLoading && filteredStudentsList.length === 0">
              <td colspan="7" class="py-12 text-center">
                <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-semibold">
                  <span class="h-4 w-4 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
                  <span>{{ lang === 'kh' ? 'កំពុងផ្ទុកទិន្នន័យ...' : 'Loading data...' }}</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="filteredStudentsList.length === 0">
              <td colspan="7">
                <EmptyState
                  icon="person_search"
                  :title="t.noStudentsFound"
                  :description="t.noStudentsDesc"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Card List -->
      <div class="md:hidden divide-y divide-slate-100">
        <div v-for="student in paginatedStudents" :key="student.id" class="p-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div v-if="student.photo || student.profileImage" class="h-10 w-10 rounded-xl overflow-hidden shrink-0 border border-blue-100 shadow-sm">
                <img :src="student.photo || student.profileImage" class="w-full h-full object-cover" />
              </div>
              <div v-else class="h-10 w-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-100">
                {{ (student.name || 'S').charAt(0).toUpperCase() }}
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">{{ student.name }}</h4>
                <div class="mt-1 space-y-0.5">
                  <div>
                    <span class="text-xs font-bold font-mono text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 inline-block">
                      {{ student.studentCode || ('RTC-' + student.id) }}
                    </span>
                  </div>
                  <div v-if="student.phone" class="text-xs text-slate-400 font-medium">
                    {{ student.phone }}
                  </div>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0">
              <StatusBadge
                :status="getStudentExamStatusType(student)"
                :label="getStudentExamStatusLabel(student)"
              />
              <div
                v-if="getStudentExamNamesDisplay(student)"
                class="text-[11px] font-medium text-slate-500 truncate max-w-[150px] mt-0.5"
                :title="getStudentExamNamesDisplay(student)"
              >
                {{ getStudentExamNamesDisplay(student) }}
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 text-xs text-slate-600">
            <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold">{{ student.skill }}</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">{{ student.group }}</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">{{ student.shift }}</span>
            <span v-if="student.enrolledMonth" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">{{ student.enrolledMonth }} {{ student.enrolledYear || '' }}</span>
            <span v-if="student.durationMonths" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">{{ student.durationMonths }}</span>
          </div>

          <div v-if="can('Students', 'edit') || can('Students', 'delete')" class="flex items-center justify-end gap-2 pt-2 border-t border-slate-50">
            <Button v-if="can('Students', 'edit')" variant="secondary" size="xs" icon="edit" @click="editStudent(student)">
              {{ t.edit }}
            </Button>
            <Button v-if="can('Students', 'delete')" variant="danger" size="xs" icon="delete" @click="confirmDeleteStudent(student)">
              {{ t.delete }}
            </Button>
          </div>
        </div>

        <div v-if="initialLoading && filteredStudentsList.length === 0" class="py-12 text-center">
          <div class="inline-flex items-center gap-2 text-slate-400 text-xs font-semibold">
            <span class="h-4 w-4 rounded-full border-2 border-blue-600 border-t-transparent animate-spin"></span>
            <span>{{ lang === 'kh' ? 'កំពុងផ្ទុកទិន្នន័យ...' : 'Loading data...' }}</span>
          </div>
        </div>

        <EmptyState
          v-else-if="filteredStudentsList.length === 0"
          icon="person_search"
          :title="t.noStudentsFound"
          :description="t.noStudentsDesc"
        />
      </div>

      <!-- Student Pagination -->
      <Pagination
        v-if="filteredStudentsList.length > pageSize"
        v-model:currentPage="currentStudentPage"
        :pageSize="pageSize"
        :totalItems="filteredStudentsList.length"
      />
    </Card>

    <!-- ── Add Student Modal (No Role Selector) ──────────────────────── -->
    <Modal
      v-model="addingStudent"
      :title="t.addUserTitle"
      max-width="lg"
    >
      <div class="space-y-4">
        <!-- Photo Upload Preview -->
        <div class="flex items-center gap-4 p-3 rounded-2xl bg-slate-50 border border-slate-100">
          <div class="relative group cursor-pointer" @click="triggerAddPhotoInput">
            <div class="w-16 h-16 rounded-full border-2 border-dashed border-blue-300 group-hover:border-blue-500 bg-white flex items-center justify-center overflow-hidden transition-all shadow-sm">
              <img
                v-if="addForm.photoPreview"
                :src="addForm.photoPreview"
                alt="Profile Preview"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-center">
                <span class="material-symbols-outlined text-xl text-blue-500">add_a_photo</span>
              </div>
            </div>
            <div class="absolute bottom-0 right-0 p-1 bg-blue-600 text-white rounded-full shadow flex items-center justify-center">
              <span class="material-symbols-outlined text-[10px]">photo_camera</span>
            </div>
          </div>
          <input
            ref="addPhotoInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onAddPhotoSelected"
          />
          <div>
            <span class="text-xs font-bold text-slate-700 block">{{ lang === 'kh' ? 'រូបថតប្រវត្តិរូប' : 'Profile Photo' }}</span>
            <button
              type="button"
              class="text-xs text-blue-600 hover:text-blue-700 hover:underline font-medium"
              @click="triggerAddPhotoInput"
            >
              {{ addForm.photoPreview ? (lang === 'kh' ? 'ប្តូររូបថត' : 'Change Photo') : (lang === 'kh' ? 'ជ្រើសរើសរូបភាព' : 'Upload Photo') }}
            </button>
          </div>
        </div>

        <!-- Student ID Badge (Auto-generated Random) -->
        <div class="p-3 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
              <span class="material-symbols-outlined text-base">badge</span>
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600 block">Student ID</span>
              <span class="text-sm font-black text-blue-950 font-mono">{{ addForm.studentCode }}</span>
            </div>
          </div>
          <button
            type="button"
            class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-white rounded-xl transition-all"
            :title="lang === 'kh' ? 'បង្កើតកូដចៃដន្យថ្មី' : 'Generate Random ID'"
            @click="randomizeAddStudentCode"
          >
            <span class="material-symbols-outlined text-lg">refresh</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="addForm.firstName" :label="t.firstName" required placeholder="First Name" />
          <Input v-model="addForm.lastName" :label="t.lastName" required placeholder="Last Name" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="addForm.phone" :label="t.phone" icon="call" required placeholder="+855 xxx xxx xxx" />
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.gender }}</label>
            <CustomDropdown v-model="addForm.gender" :options="genderOptions" labelKey="label" valueKey="value" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.shift }}</label>
            <CustomDropdown v-model="addForm.shift" :options="shiftOptions" labelKey="label" valueKey="value" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.skill }}</label>
            <CustomDropdown v-model="addForm.skillId" :options="skillsList" labelKey="SkillName" valueKey="SkillId" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.group }}</label>
            <CustomDropdown v-model="addForm.groupId" :options="groupsList" labelKey="GroupName" valueKey="GroupId" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.studyDurationLabel }}</label>
            <CustomDropdown v-model="addForm.durationMonths" :options="durationOptions" labelKey="label" valueKey="value" :placeholder="t.studyDurationLabel" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.intakeMonthLabel }}</label>
            <CustomDropdown v-model="addForm.intakeMonth" :options="monthOptions" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.intakeYearLabel }}</label>
            <CustomDropdown v-model="addForm.intakeYear" :options="yearOptions" @change="onAddYearChange" />
          </div>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="addingStudent = false">{{ t.cancel }}</Button>
        <Button variant="primary" :loading="savingAdd" @click="saveNewUser">{{ t.saveUser }}</Button>
      </template>
    </Modal>

    <!-- ── Edit Student Modal (No Role Selector) ─────────────────────── -->
    <Modal
      v-model="editingStudentModal"
      :title="t.editUserTitle"
      max-width="lg"
    >
      <div class="space-y-4">
        <!-- Edit Photo Upload Preview -->
        <div class="flex items-center gap-4 p-3 rounded-2xl bg-slate-50 border border-slate-100">
          <div class="relative group cursor-pointer" @click="triggerEditPhotoInput">
            <div class="w-16 h-16 rounded-full border-2 border-dashed border-blue-300 group-hover:border-blue-500 bg-white flex items-center justify-center overflow-hidden transition-all shadow-sm">
              <img
                v-if="editForm.photoPreview"
                :src="editForm.photoPreview"
                alt="Profile Preview"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-center">
                <span class="material-symbols-outlined text-xl text-blue-500">photo</span>
              </div>
            </div>
            <div class="absolute bottom-0 right-0 p-1 bg-blue-600 text-white rounded-full shadow flex items-center justify-center">
              <span class="material-symbols-outlined text-[10px]">photo_camera</span>
            </div>
          </div>
          <input
            ref="editPhotoInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onEditPhotoSelected"
          />
          <div>
            <span class="text-xs font-bold text-slate-700 block">{{ lang === 'kh' ? 'រូបថតប្រវត្តិរូប' : 'Profile Photo' }}</span>
            <button
              type="button"
              class="text-xs text-blue-600 hover:text-blue-700 hover:underline font-medium"
              @click="triggerEditPhotoInput"
            >
              {{ editForm.photoPreview ? (lang === 'kh' ? 'ប្តូររូបថត' : 'Change Photo') : (lang === 'kh' ? 'ជ្រើសរើសរូបភាព' : 'Upload Photo') }}
            </button>
          </div>
        </div>

        <!-- Student ID -->
        <div class="grid grid-cols-1 gap-4">
          <Input v-model="editForm.studentCode" label="Student ID" icon="badge" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="editForm.firstName" :label="t.firstName" required />
          <Input v-model="editForm.lastName" :label="t.lastName" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input v-model="editForm.phone" :label="t.phone" required />
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.gender }}</label>
            <CustomDropdown v-model="editForm.gender" :options="genderOptions" labelKey="label" valueKey="value" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.shift }}</label>
            <CustomDropdown v-model="editForm.shift" :options="shiftOptions" labelKey="label" valueKey="value" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.skill }}</label>
            <CustomDropdown v-model="editForm.skill" :options="skillsList" labelKey="SkillName" valueKey="SkillName" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.group }}</label>
            <CustomDropdown v-model="editForm.group" :options="groupsList" labelKey="GroupName" valueKey="GroupName" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.studyDurationLabel }}</label>
            <CustomDropdown v-model="editForm.durationMonths" :options="durationOptions" labelKey="label" valueKey="value" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.intakeMonthLabel }}</label>
            <CustomDropdown v-model="editForm.intakeMonth" :options="monthOptions" />
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">{{ t.intakeYearLabel }}</label>
            <CustomDropdown v-model="editForm.intakeYear" :options="yearOptions" />
          </div>
        </div>

        <!-- Optional Password Reset -->
        <div class="pt-2 border-t border-slate-100">
          <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">{{ t.changePasswordOptional }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <PasswordInput v-model="editForm.newPassword" :label="t.newPassword" placeholder="Leave blank to keep" />
            <PasswordInput v-model="editForm.confirmPassword" :label="t.confirmPassword" placeholder="Confirm password" />
          </div>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="editingStudentModal = false">{{ t.cancel }}</Button>
        <Button variant="primary" :loading="savingEdit" @click="saveStudent">{{ t.saveChanges }}</Button>
      </template>
    </Modal>

    <!-- ── Delete Confirm Dialog ───────────────────────────────────── -->
    <ConfirmDialog
      v-model="showDeleteDialog"
      :title="t.deleteConfirmTitle"
      :message="deleteConfirmMessage"
      :confirm-text="t.delete"
      :cancel-text="t.cancel"
      confirm-variant="danger"
      icon="delete"
      :loading="deleting"
      @confirm="performDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import Button from '../components/ui/Button.vue'
import IconButton from '../components/ui/IconButton.vue'
import Input from '../components/ui/Input.vue'
import PasswordInput from '../components/ui/PasswordInput.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import Badge from '../components/ui/Badge.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'
import Modal from '../components/ui/Modal.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Tabs from '../components/ui/Tabs.vue'
import Pagination from '../components/ui/Pagination.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import CustomDropdown from '../components/CustomDropdown.vue'
import { useLang } from '../utils/useLang'
import { useToast } from '../composables/useToast'
import { usePermissions } from '../composables/usePermissions'
import { logActivity } from '../utils/activityLog'

const { lang } = useLang()
const { success: toastSuccess, error: toastError } = useToast()
const { can, isSuperAdmin, fetchUser } = usePermissions()

const pageSize = 10
const currentStudentPage = ref(1)

const searchQuery = ref('')
const filterSkill = ref('')
const filterGroup = ref('')
const filterExam = ref('')
import { fastCache } from '../stores/fastCache'

const filterExamStatus = ref('')
const activeFilter = ref('all') // 'all', 'students', 'admins'

const studentsList = ref(fastCache.get('students') || [])
const adminsList = ref(fastCache.get('admins') || [])
const skillsList = ref(fastCache.get('skills') || [])
const groupsList = ref(fastCache.get('groups') || [])
const examsList = ref(fastCache.get('exams') || [])
const durationsList = ref(fastCache.get('durations') || [])
const initialLoading = ref(!studentsList.value.length && !adminsList.value.length)

const addingStudent = ref(false)
const savingAdd = ref(false)
const editingStudentModal = ref(false)
const savingEdit = ref(false)
const editingStudentId = ref(null)

const showDeleteDialog = ref(false)
const userToDelete = ref(null)
const deleting = ref(false)
const addPhotoInputRef = ref(null)
const editPhotoInputRef = ref(null)

const generateRandomCode = (year = '2026') => {
  const rand = Math.floor(10000 + Math.random() * 90000)
  return `RTC-${year}-${rand}`
}

const randomizeAddStudentCode = () => {
  addForm.studentCode = generateRandomCode(addForm.intakeYear || '2026')
}

const onAddYearChange = () => {
  addForm.studentCode = generateRandomCode(addForm.intakeYear || '2026')
}

const triggerAddPhotoInput = () => {
  addPhotoInputRef.value?.click()
}

const triggerEditPhotoInput = () => {
  editPhotoInputRef.value?.click()
}

const onAddPhotoSelected = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => {
    addForm.photoPreview = ev.target.result
    addForm.photo = ev.target.result
  }
  reader.readAsDataURL(file)
}

const onEditPhotoSelected = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => {
    editForm.photoPreview = ev.target.result
    editForm.photo = ev.target.result
  }
  reader.readAsDataURL(file)
}

const addForm = reactive({
  role: 'Student',
  studentCode: '',
  firstName: '',
  lastName: '',
  username: '',
  phone: '',
  password: '',
  photo: null,
  photoPreview: '',
  gender: 'Male',
  shift: 'Morning',
  skillId: '',
  groupId: '',
  intakeMonth: 'មករា',
  intakeYear: '2026',
  durationMonths: ''
})

const editForm = reactive({
  role: 'Student',
  studentCode: '',
  firstName: '',
  lastName: '',
  username: '',
  phone: '',
  photo: null,
  photoPreview: '',
  gender: 'Male',
  shift: 'Morning',
  skill: '',
  group: '',
  intakeMonth: 'មករា',
  intakeYear: '2026',
  durationMonths: '',
  newPassword: '',
  confirmPassword: ''
})

const genderOptions = computed(() => [
  { label: lang.value === 'kh' ? 'ប្រុស' : 'Male', value: 'Male' },
  { label: lang.value === 'kh' ? 'ស្រី' : 'Female', value: 'Female' },
  { label: lang.value === 'kh' ? 'ផ្សេងៗ' : 'Other', value: 'Other' }
])

const shiftOptions = computed(() => [
  { label: lang.value === 'kh' ? 'វេនព្រឹក (Morning)' : 'Morning', value: 'Morning' },
  { label: lang.value === 'kh' ? 'វេនរសៀល (Afternoon)' : 'Afternoon', value: 'Afternoon' },
  { label: lang.value === 'kh' ? 'វេនយប់ (Evening)' : 'Evening', value: 'Evening' }
])

const durationOptions = computed(() => {
  if (durationsList.value && durationsList.value.length > 0) {
    return durationsList.value.map(d => ({
      label: d.DurationName,
      value: d.DurationName
    }))
  }
  return [
    { label: '1 ខែ (1 Month)', value: '1 ខែ (1 Month)' },
    { label: '2 ខែ (2 Months)', value: '2 ខែ (2 Months)' },
    { label: '3 ខែ (3 Months)', value: '3 ខែ (3 Months)' },
    { label: '4 ខែ (4 Months)', value: '4 ខែ (4 Months)' },
    { label: '5 ខែ (5 Months)', value: '5 ខែ (5 Months)' },
    { label: '6 ខែ (6 Months)', value: '6 ខែ (6 Months)' },
    { label: '1 ឆ្នាំ (1 Year)', value: '1 ឆ្នាំ (1 Year)' }
  ]
})

const monthOptions = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ']
const yearOptions = ['2024', '2025', '2026', '2027', '2028', '2029', '2030']

const examStatusOptions = computed(() => [
  { label: lang.value === 'kh' ? 'ស្ថានភាពប្រឡងទាំងអស់' : 'All Exam Statuses', value: '' },
  { label: lang.value === 'kh' ? 'បានប្រឡងរួច' : 'Taken Exam', value: 'taken' },
  { label: lang.value === 'kh' ? 'មិនទាន់ប្រឡង' : 'Not Taken', value: 'not_taken' }
])

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      title: 'គ្រប់គ្រងសិស្ស',
      desc: 'បញ្ជីឈ្មោះសិស្ស ព័ត៌មានសិក្សា និងការតាមដានការប្រឡង',
      addStudent: 'បន្ថែមសិស្ស',
      allSkills: 'ជំនាញទាំងអស់',
      allGroups: 'ក្រុមទាំងអស់',
      allExams: 'វិញ្ញាសាទាំងអស់',
      examStatus: 'ស្ថានភាពប្រឡង',
      reset: 'កំណត់ឡើងវិញ',
      searchPlaceholder: 'ស្វែងរកតាមឈ្មោះ, ID, ឬលេខទូរស័ព្ទ...',
      studentsList: 'បញ្ជីសិស្ស',
      enrolledCount: 'សិស្សបានចុះឈ្មោះ',
      student: 'សិស្ស',
      genderShift: 'ភេទ & វេន',
      skillGroup: 'ជំនាញ & ក្រុម',
      intakeDuration: 'ចូលរៀន & រយៈពេល',
      actions: 'សកម្មភាព',
      takenExam: 'បានប្រឡង',
      notTakenExam: 'មិនទាន់ប្រឡង',
      edit: 'កែប្រែ',
      delete: 'លុប',
      noStudentsFound: 'រកមិនឃើញទិន្នន័យសិស្សទេ',
      noStudentsDesc: 'សូមព្យាយាមផ្លាស់ប្តូរពាក្យស្វែងរក ឬតម្រង។',
      addUserTitle: 'បន្ថែមសិស្សថ្មី',
      editUserTitle: 'កែប្រែព័ត៌មានសិស្ស',
      firstName: 'នាមខ្លួន',
      lastName: 'គោត្តនាម',
      phone: 'លេខទូរស័ព្ទ',
      gender: 'ភេទ',
      shift: 'វេនសិក្សា',
      skill: 'ជំនាញ',
      group: 'ក្រុម',
      intakeMonthLabel: 'ខែចូលរៀន',
      intakeYearLabel: 'ឆ្នាំចូលរៀន',
      studyDurationLabel: 'រយៈពេលសិក្សា',
      changePasswordOptional: 'ផ្លាស់ប្តូរពាក្យសម្ងាត់ (ស្រេចចិត្ត)',
      newPassword: 'ពាក្យសម្ងាត់ថ្មី',
      confirmPassword: 'បញ្ជាក់ពាក្យសម្ងាត់',
      cancel: 'បោះបង់',
      saveUser: 'រក្សាទុកសិស្ស',
      saveChanges: 'រក្សាទុកការកែប្រែ',
      deleteConfirmTitle: 'លុបសិស្ស?',
    }
  }
  return {
    title: 'Students Management',
    desc: 'Manage enrolled candidates, study tracks, and cohorts',
    addStudent: 'Add Student',
    allSkills: 'All Skills',
    allGroups: 'All Groups',
    allExams: 'All Exams',
    examStatus: 'Exam Status',
    reset: 'Reset Filters',
    searchPlaceholder: 'Search by name, ID, or phone...',
    studentsList: 'Students Directory',
    enrolledCount: 'enrolled students',
    student: 'Student',
    genderShift: 'Gender & Shift',
    skillGroup: 'Skill & Group',
    intakeDuration: 'Intake & Duration',
    actions: 'Actions',
    takenExam: 'Taken',
    notTakenExam: 'Not Taken',
    edit: 'Edit',
    delete: 'Delete',
    noStudentsFound: 'No students found',
    noStudentsDesc: 'Try adjusting your search terms or filter selection.',
    addUserTitle: 'Add New Student',
    editUserTitle: 'Edit Student Profile',
    firstName: 'First Name',
    lastName: 'Last Name',
    phone: 'Phone Number',
    gender: 'Gender',
    shift: 'Shift',
    skill: 'Skill Area',
    group: 'Study Group',
    intakeMonthLabel: 'Intake Month',
    intakeYearLabel: 'Intake Year',
    studyDurationLabel: 'Study Duration',
    changePasswordOptional: 'Change Password (Optional)',
    newPassword: 'New Password',
    confirmPassword: 'Confirm Password',
    cancel: 'Cancel',
    saveUser: 'Save Student',
    saveChanges: 'Save Changes',
    deleteConfirmTitle: 'Delete Student?',
  }
})

const filteredStudentsList = computed(() => {
  return studentsList.value.filter(s => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      (s.name && s.name.toLowerCase().includes(q)) ||
      (s.username && s.username.toLowerCase().includes(q)) ||
      (s.studentCode && s.studentCode.toLowerCase().includes(q)) ||
      (s.phone && s.phone.toLowerCase().includes(q))

    const matchesSkill = !filterSkill.value || s.skill === filterSkill.value
    const matchesGroup = !filterGroup.value || s.group === filterGroup.value

    let matchesStatus = true
    if (filterExam.value) {
      const hasThisExam = Array.isArray(s.takenExamNames) && s.takenExamNames.includes(filterExam.value)
      if (filterExamStatus.value === 'taken') {
        matchesStatus = hasThisExam
      } else if (filterExamStatus.value === 'not_taken') {
        matchesStatus = !hasThisExam
      }
    } else {
      if (filterExamStatus.value === 'taken') matchesStatus = s.hasTakenExam
      if (filterExamStatus.value === 'not_taken') matchesStatus = !s.hasTakenExam
    }

    return matchesSearch && matchesSkill && matchesGroup && matchesStatus
  })
})

const paginatedStudents = computed(() => {
  const start = (currentStudentPage.value - 1) * pageSize
  return filteredStudentsList.value.slice(start, start + pageSize)
})

const getStudentExamStatusLabel = (student) => {
  if (filterExam.value) {
    const hasThisExam = Array.isArray(student.takenExamNames) && student.takenExamNames.includes(filterExam.value)
    return hasThisExam ? `${t.value.takenExam} (1)` : t.value.notTakenExam
  }
  return student.hasTakenExam ? `${t.value.takenExam} (${student.examCount})` : t.value.notTakenExam
}

const getStudentExamStatusType = (student) => {
  if (filterExam.value) {
    const hasThisExam = Array.isArray(student.takenExamNames) && student.takenExamNames.includes(filterExam.value)
    return hasThisExam ? 'taken' : 'pending'
  }
  return student.hasTakenExam ? 'taken' : 'pending'
}

const getStudentExamNamesDisplay = (student) => {
  if (!student.takenExamNames || !student.takenExamNames.length) return ''
  if (filterExam.value) {
    const hasThisExam = Array.isArray(student.takenExamNames) && student.takenExamNames.includes(filterExam.value)
    return hasThisExam ? filterExam.value : ''
  }
  return student.takenExamNames.join(', ')
}

const deleteConfirmMessage = computed(() => {
  if (!userToDelete.value) return ''
  return lang.value === 'kh'
    ? `តើអ្នកពិតជាចង់លុបទិន្នន័យសិស្ស "${userToDelete.value.name}" (${userToDelete.value.studentCode || userToDelete.value.username}) មែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។`
    : `Are you sure you want to delete student "${userToDelete.value.name}" (${userToDelete.value.studentCode || userToDelete.value.username})? This action cannot be undone.`
})

const resetFilters = () => {
  filterSkill.value = ''
  filterGroup.value = ''
  filterExam.value = ''
  filterExamStatus.value = ''
  searchQuery.value = ''
  currentStudentPage.value = 1
}

const openAddModal = () => {
  addForm.role = 'Student'
  addForm.firstName = ''
  addForm.lastName = ''
  addForm.username = ''
  addForm.phone = ''
  addForm.password = ''
  addForm.photo = null
  addForm.photoPreview = ''
  addForm.gender = 'Male'
  addForm.shift = 'Morning'
  addForm.intakeMonth = 'មករា'
  addForm.intakeYear = '2026'
  addForm.studentCode = generateRandomCode('2026')
  if (skillsList.value.length) addForm.skillId = skillsList.value[0].SkillId
  if (groupsList.value.length) addForm.groupId = groupsList.value[0].GroupId
  if (durationsList.value.length) {
    addForm.durationMonths = durationsList.value[0].DurationName
  } else {
    addForm.durationMonths = '1 ខែ (1 Month)'
  }
  addingStudent.value = true
}

const saveNewUser = async () => {
  if (addForm.role === 'Student') {
    if (!addForm.firstName.trim() || !addForm.lastName.trim() || !addForm.phone.trim()) {
      toastError(lang.value === 'kh' ? 'សូមបំពេញព័ត៌មានចាំបាច់ (នាមខ្លួន គោត្តនាម លេខទូរស័ព្ទ)' : 'Please fill all required fields (First Name, Last Name, Phone).')
      return
    }
  } else {
    if (!addForm.firstName.trim() || !addForm.lastName.trim() || !addForm.username.trim() || !addForm.password.trim() || !addForm.phone.trim()) {
      toastError(lang.value === 'kh' ? 'សូមបំពេញព័ត៌មានចាំបាច់ទាំងអស់' : 'Please fill all required fields.')
      return
    }
  }

  savingAdd.value = true
  try {
    const res = await axios.post('/api/admin/students', {
      role: addForm.role,
      studentCode: addForm.studentCode,
      firstName: addForm.firstName.trim(),
      lastName: addForm.lastName.trim(),
      username: addForm.role === 'Student' ? (addForm.studentCode || addForm.username) : addForm.username.trim(),
      phone: addForm.phone.trim(),
      password: addForm.password || undefined,
      photo: addForm.photo,
      gender: addForm.gender,
      shift: addForm.shift,
      skillId: addForm.skillId,
      groupId: addForm.groupId,
      intakeMonth: addForm.intakeMonth,
      intakeYear: addForm.intakeYear,
      durationMonths: addForm.durationMonths
    })

    toastSuccess(res.data.message || 'User added successfully!')
    logActivity(`New ${addForm.role} added: ${addForm.firstName} ${addForm.lastName}`, `Role: ${addForm.role}`)
    addingStudent.value = false
    broadcastSync('students_updated')
    await loadData()
  } catch (err) {
    toastError(err.response?.data?.message || 'Failed to add user.')
  } finally {
    savingAdd.value = false
  }
}

const editStudent = (student) => {
  editingStudentId.value = student.id
  editForm.role = student.role || 'Student'
  editForm.studentCode = student.studentCode || student.username || ''
  editForm.firstName = student.firstName || student.first_name || student.name?.split(' ')[0] || ''
  editForm.lastName = student.lastName || student.last_name || student.name?.split(' ').slice(1).join(' ') || ''
  editForm.username = student.username || ''
  editForm.phone = student.phone || ''
  editForm.gender = student.gender || 'Male'
  editForm.shift = student.shift || 'Morning'
  editForm.skill = student.skill || ''
  editForm.group = student.group || ''
  editForm.intakeMonth = student.enrolledMonth || student.intakeMonth || 'មករា'
  editForm.intakeYear = student.enrolledYear || student.intakeYear || '2026'
  editForm.durationMonths = student.durationMonths || (durationsList.value.length ? durationsList.value[0].DurationName : '1 ខែ (1 Month)')
  editForm.photo = null
  editForm.photoPreview = student.photo || student.profileImage || ''
  editForm.newPassword = ''
  editForm.confirmPassword = ''
  editingStudentModal.value = true
}

const saveStudent = async () => {
  if (editForm.role === 'Student') {
    if (!editForm.firstName.trim() || !editForm.lastName.trim() || !editForm.phone.trim()) {
      toastError(lang.value === 'kh' ? 'សូមបំពេញព័ត៌មានចាំបាច់' : 'Please fill all required fields.')
      return
    }
  } else {
    if (!editForm.firstName.trim() || !editForm.lastName.trim() || !editForm.username.trim()) {
      toastError(lang.value === 'kh' ? 'សូមបំពេញព័ត៌មានចាំបាច់' : 'Please fill all required fields.')
      return
    }
  }

  if (editForm.newPassword && editForm.newPassword !== editForm.confirmPassword) {
    toastError(lang.value === 'kh' ? 'ពាក្យសម្ងាត់មិនត្រូវគ្នាទេ' : 'Passwords do not match.')
    return
  }

  savingEdit.value = true
  try {
    const payload = {
      role: editForm.role,
      firstName: editForm.firstName.trim(),
      lastName: editForm.lastName.trim(),
      username: editForm.username.trim() || editForm.studentCode,
      phone: editForm.phone.trim(),
      photo: editForm.photo || undefined,
      newPassword: editForm.newPassword || undefined
    }

    if (editForm.role === 'Student') {
      payload.studentCode = editForm.studentCode
      payload.gender = editForm.gender
      payload.shift = editForm.shift
      payload.skill = editForm.skill
      payload.group = editForm.group
      payload.intakeMonth = editForm.intakeMonth
      payload.enrolledMonth = editForm.intakeMonth
      payload.intakeYear = editForm.intakeYear
      payload.enrolledYear = editForm.intakeYear
      payload.durationMonths = editForm.durationMonths
    }

    const res = await axios.put(`/api/admin/students/${editingStudentId.value}`, payload)

    toastSuccess(res.data.message || 'User updated successfully!')
    editingStudentModal.value = false
    broadcastSync('students_updated')
    await loadData()
  } catch (err) {
    toastError(err.response?.data?.message || 'Failed to update user.')
  } finally {
    savingEdit.value = false
  }
}

const confirmDeleteStudent = (student) => {
  userToDelete.value = student
  showDeleteDialog.value = true
}

import { useRealtimePoll, broadcastSync } from '../composables/useRealtimePoll'

const performDelete = async () => {
  if (!userToDelete.value) return
  deleting.value = true
  try {
    await axios.delete(`/api/admin/students/${userToDelete.value.id}`)
    toastSuccess('User deleted successfully.')
    showDeleteDialog.value = false
    broadcastSync('students_updated')
    await loadData()
  } catch (err) {
    toastError(err.response?.data?.message || 'Failed to delete user.')
  } finally {
    deleting.value = false
  }
}

const loadData = async (isBackground = false) => {
  try {
    const [studentsRes, skillsGroupsRes] = await Promise.all([
      axios.get('/api/admin/students'),
      axios.get('/api/admin/skills-groups'),
      fetchUser()
    ])

    studentsList.value = studentsRes.data.students || []
    adminsList.value = studentsRes.data.admins || []
    skillsList.value = skillsGroupsRes.data.skills || []
    groupsList.value = skillsGroupsRes.data.groups || []
    durationsList.value = skillsGroupsRes.data.durations || []
    if (studentsRes.data.exams) {
      examsList.value = studentsRes.data.exams || []
      fastCache.set('exams', examsList.value)
    }

    fastCache.set('students', studentsList.value)
    fastCache.set('admins', adminsList.value)
    fastCache.set('skills', skillsList.value)
    fastCache.set('groups', groupsList.value)
    fastCache.set('durations', durationsList.value)
  } catch (err) {
    if (!isBackground) {
      console.error('Failed to load user management data', err)
    }
  } finally {
    initialLoading.value = false
  }
}

useRealtimePoll(loadData, { interval: 4000, listenEvents: ['students_updated'] })
</script>
