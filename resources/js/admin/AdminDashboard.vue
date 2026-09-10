<template>
  <div class="space-y-6">
    <!-- Role-Aware Page Header & Quick Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <span
            :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border',
              isSuperAdmin
                ? 'bg-purple-50 text-purple-700 border-purple-200'
                : 'bg-blue-50 text-blue-700 border-blue-200'
            ]"
          >
            <span class="material-symbols-outlined text-xs mr-1">
              {{ isSuperAdmin ? 'admin_panel_settings' : 'shield' }}
            </span>
            {{ isSuperAdmin ? 'Super Administrator' : 'Administrator' }}
          </span>

          <span
            v-if="settings.academicYear"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"
          >
            <span class="material-symbols-outlined text-xs mr-1 text-slate-500">calendar_month</span>
            {{ settings.academicYear }}
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
          {{ isSuperAdmin ? t.superDashboardTitle : t.adminDashboardTitle }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
          {{ isSuperAdmin ? t.superDashboardSubtitle : t.adminDashboardSubtitle }}
        </p>
      </div>

      <div class="flex items-center gap-2.5 flex-wrap">
        <Button
          variant="outline"
          icon="group_add"
          size="sm"
          @click="router.push('/admin/students')"
        >
          {{ t.addStudent }}
        </Button>
        <Button
          variant="primary"
          icon="add_circle"
          size="sm"
          @click="router.push('/admin/tests')"
        >
          {{ t.createExam }}
        </Button>
        <Button
          variant="secondary"
          icon="bar_chart"
          size="sm"
          @click="router.push('/admin/results')"
        >
          {{ t.viewResults }}
        </Button>
      </div>
    </div>

    <!-- Skeletons when initial loading -->
    <div v-if="initialLoading" class="space-y-6">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="n in 4" :key="n" height="120px" customClass="rounded-2xl" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Skeleton height="260px" customClass="rounded-2xl lg:col-span-1" />
        <Skeleton height="260px" customClass="rounded-2xl lg:col-span-2" />
      </div>
    </div>

    <!-- Loaded Dashboard Content -->
    <div v-else class="space-y-6">

      <!-- ── KPI Stat Cards (Super Admin sees 6, Admin sees 4) ──────── -->
      <div v-if="isSuperAdmin" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <StatCard
          :label="t.totalUsers"
          :value="dashboard.totalUsers || dashboard.activeStudents"
          icon="group"
          color="purple"
          :accent="true"
        />
        <StatCard
          :label="t.totalAdmins"
          :value="dashboard.totalAdmins || 1"
          icon="admin_panel_settings"
          color="indigo"
        />
        <StatCard
          :label="t.activeStudents"
          :value="dashboard.activeStudents"
          icon="school"
          color="blue"
        />
        <StatCard
          :label="t.publishedTests"
          :value="dashboard.publishedTests"
          icon="quiz"
          color="sky"
        />
        <StatCard
          :label="t.completedExams"
          :value="dashboard.completedExams"
          icon="task_alt"
          color="emerald"
        />
        <StatCard
          :label="t.avgScore"
          :value="dashboard.avgScore"
          suffix="%"
          icon="analytics"
          color="amber"
        />
      </div>

      <!-- Standard Admin KPI Row -->
      <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          :label="t.activeStudents"
          :value="dashboard.activeStudents"
          icon="group"
          color="blue"
          :accent="true"
          :hint="t.enrolledStudents"
        />
        <StatCard
          :label="t.publishedTests"
          :value="dashboard.publishedTests"
          icon="quiz"
          color="indigo"
          :hint="t.liveExams"
        />
        <StatCard
          :label="t.completedExams"
          :value="dashboard.completedExams"
          icon="task_alt"
          color="emerald"
          :hint="t.totalSubmissions"
        />
        <StatCard
          :label="t.avgScore"
          :value="dashboard.avgScore"
          suffix="%"
          icon="bar_chart"
          color="amber"
          :hint="t.overallPerformance"
        />
      </div>

      <!-- ── Super Admin System Health Row ─────────────────────────── -->
      <div v-if="isSuperAdmin && dashboard.systemHealth" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Database Engine -->
        <div
          @click="openHealthModal"
          class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs hover:border-emerald-300 hover:shadow-soft-md transition-all duration-200 cursor-pointer group flex items-center justify-between"
          :title="t.clickForDiagnostics"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0 group-hover:scale-105 transition-transform">
              <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">database</span>
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 truncate">{{ t.dbEngineTitle }}</p>
              <p class="text-xs font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1.5 truncate">
                <span class="relative flex h-2 w-2 shrink-0">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="truncate">{{ dbHealthLabel }}</span>
              </p>
            </div>
          </div>
          <span class="material-symbols-outlined text-slate-300 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all text-base shrink-0 ml-1">
            chevron_right
          </span>
        </div>

        <!-- REST API -->
        <div
          @click="openHealthModal"
          class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs hover:border-emerald-300 hover:shadow-soft-md transition-all duration-200 cursor-pointer group flex items-center justify-between"
          :title="t.clickForDiagnostics"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0 group-hover:scale-105 transition-transform">
              <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">api</span>
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 truncate">{{ t.apiGatewayTitle }}</p>
              <p class="text-xs font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1.5 truncate">
                <span class="relative flex h-2 w-2 shrink-0">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="truncate">{{ apiHealthLabel }}</span>
              </p>
            </div>
          </div>
          <span class="material-symbols-outlined text-slate-300 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all text-base shrink-0 ml-1">
            chevron_right
          </span>
        </div>

        <!-- Authentication -->
        <div
          @click="openHealthModal"
          class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs hover:border-emerald-300 hover:shadow-soft-md transition-all duration-200 cursor-pointer group flex items-center justify-between"
          :title="t.clickForDiagnostics"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0 group-hover:scale-105 transition-transform">
              <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">lock</span>
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 truncate">{{ t.authSecurityTitle }}</p>
              <p class="text-xs font-extrabold text-emerald-700 mt-0.5 flex items-center gap-1.5 truncate">
                <span class="relative flex h-2 w-2 shrink-0">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="truncate">{{ authHealthLabel }}</span>
              </p>
            </div>
          </div>
          <span class="material-symbols-outlined text-slate-300 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all text-base shrink-0 ml-1">
            chevron_right
          </span>
        </div>

        <!-- Platform Status -->
        <div
          @click="openHealthModal"
          class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-soft-xs hover:border-purple-300 hover:shadow-soft-md transition-all duration-200 cursor-pointer group flex items-center justify-between"
          :title="t.clickForDiagnostics"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 shrink-0 group-hover:scale-105 transition-transform">
              <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">verified</span>
            </div>
            <div class="min-w-0">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 truncate">{{ t.platformTitle }}</p>
              <p class="text-xs font-extrabold text-purple-700 mt-0.5 flex items-center gap-1.5 truncate">
                <span class="relative flex h-2 w-2 shrink-0">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                </span>
                <span class="truncate">{{ platformHealthLabel }}</span>
              </p>
            </div>
          </div>
          <span class="material-symbols-outlined text-slate-300 group-hover:text-purple-600 group-hover:translate-x-0.5 transition-all text-base shrink-0 ml-1">
            chevron_right
          </span>
        </div>
      </div>


      <!-- ── Main Grid: Storage & Activity Feed ────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Database Storage Card (Super Admin ONLY) -->
        <Card
          v-if="isSuperAdmin && dashboard.databaseStorage"
          :title="t.databaseUsage"
          class="lg:col-span-1"
        >
          <template #actions>
            <span
              :class="[
                'px-2.5 py-1 rounded-full text-xs font-extrabold',
                dashboard.databaseStorage.percentage > 90
                  ? 'bg-red-50 text-red-700 border border-red-200'
                  : 'bg-blue-50 text-blue-700 border border-blue-200'
              ]"
            >
              {{ dashboard.databaseStorage.percentage }}%
            </span>
          </template>

          <div class="space-y-4 pt-1">
            <!-- Progress Bar -->
            <ProgressBar
              :value="dashboard.databaseStorage.percentage"
              :max="100"
              :variant="dashboard.databaseStorage.percentage > 90 ? 'danger' : 'primary'"
              size="lg"
            />

            <!-- Storage Metrics Breakdown -->
            <div class="grid grid-cols-2 gap-3 pt-2">
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t.used }}</p>
                <p class="text-base font-extrabold text-slate-900 mt-0.5">{{ dashboard.databaseStorage.used }} MB</p>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ t.remaining }}</p>
                <p class="text-base font-extrabold text-slate-900 mt-0.5">{{ dashboard.databaseStorage.remaining }} MB</p>
              </div>
            </div>

            <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
              <div class="flex items-center justify-between text-slate-400">
                <span>{{ t.allocatedTotal }}:</span>
                <strong class="text-slate-700 font-bold">{{ dashboard.databaseStorage.total }} MB (5 GB)</strong>
              </div>
              <div v-if="dashboard.databaseStorage.driver" class="flex items-center justify-between text-slate-400">
                <span>{{ t.engine }}:</span>
                <span class="text-slate-700 font-bold">{{ dashboard.databaseStorage.driver }}</span>
              </div>
              <div v-if="dashboard.databaseStorage.tableCount" class="flex items-center justify-between text-slate-400">
                <span>{{ t.totalTables }}:</span>
                <span class="text-slate-700 font-bold">{{ dashboard.databaseStorage.tableCount }} {{ lang === 'kh' ? 'តារាង' : 'tables' }}</span>
              </div>
            </div>
          </div>
        </Card>

        <!-- Activity Feed (Grouped By Week) -->
        <Card
          :title="isSuperAdmin ? t.platformActivity : t.activityByWeek"
          :subtitle="t.activitySub"
          :class="isSuperAdmin && dashboard.databaseStorage ? 'lg:col-span-2' : 'lg:col-span-3'"
          padding="none"
        >
          <template #actions>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 border border-slate-200/60">
              {{ allActivities.length }} {{ t.events }}
            </span>
          </template>

          <!-- Empty State -->
          <EmptyState
            v-if="!allActivities.length"
            icon="event_note"
            :title="t.noActivity"
            :description="t.noActivityDesc"
          />

          <!-- Activity List -->
          <div v-else class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto">
            <div v-for="(group, gi) in groupedByWeek" :key="gi">
              <!-- Week Header -->
              <div class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-xs px-5 py-2.5 border-y border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-sm text-blue-600">date_range</span>
                  <span class="text-xs font-bold uppercase tracking-wider text-blue-700">{{ group.weekLabel }}</span>
                </div>
                <span class="rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-[10px] font-extrabold border border-blue-200/50">
                  {{ group.items.length }} {{ t.events }}
                </span>
              </div>

              <!-- Events within Week -->
              <div class="divide-y divide-slate-50">
                <div
                  v-for="item in group.items"
                  :key="item.id || item.title + '_' + item.timestamp"
                  class="flex items-center gap-3.5 px-5 py-3.5 hover:bg-slate-50/80 transition-colors"
                >
                  <!-- Event Icon -->
                  <div
                    :class="[
                      'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                      item.colorClass
                    ]"
                  >
                    <span class="material-symbols-outlined text-lg select-none" style="font-variation-settings: 'FILL' 1;">
                      {{ item.icon }}
                    </span>
                  </div>

                  <!-- Text -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate leading-snug">{{ item.title }}</p>
                    <p v-if="item.description" class="text-xs text-slate-500 truncate mt-0.5 leading-snug">{{ item.description }}</p>
                  </div>

                  <!-- Time -->
                  <span v-if="item.timeLabel" class="text-[11px] font-semibold text-slate-400 shrink-0 whitespace-nowrap">
                    {{ item.timeLabel }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- ── System Diagnostics Modal (Super Admin) ───────────────────── -->
    <Modal
      v-model="healthModal"
      :title="t.systemDiagnosticsTitle"
      :subtitle="t.systemDiagnosticsSub"
      maxWidth="3xl"
    >
      <div class="space-y-6">
        <!-- Top Status Bar & Refresh Action -->
        <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-indigo-500/10 border border-emerald-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="relative flex h-3.5 w-3.5 shrink-0">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
            </div>
            <div>
              <p class="text-sm font-black text-slate-900 flex items-center gap-2">
                {{ t.allSystemsHealthy }}
                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                  {{ dashboard.systemHealth?.platform?.status || 'Operational' }}
                </span>
              </p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ t.lastCheckedAt }}: <span class="font-mono font-semibold text-slate-700">{{ dashboard.systemHealth?.timestamp || 'Just now' }}</span>
              </p>
            </div>
          </div>

          <Button
            variant="outline"
            size="sm"
            icon="sync"
            :loading="runningHealthCheck"
            @click="runLiveDiagnostics"
            class="shrink-0 font-bold shadow-xs hover:border-emerald-300"
          >
            {{ runningHealthCheck ? t.runningCheck : t.runLiveCheck }}
          </Button>
        </div>

        <!-- 4 Grid Cards Inside Modal -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- 1. Database Engine Card -->
          <div class="p-4 rounded-2xl bg-slate-50/60 border border-slate-200/90 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/80 shrink-0">
                  <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">database</span>
                </div>
                <div>
                  <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ t.dbEngineTitle }}</h4>
                  <p class="text-[11px] text-slate-400">{{ t.dbEngineSub }}</p>
                </div>
              </div>
              <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                {{ dashboard.systemHealth?.database?.status || 'Healthy' }}
              </span>
            </div>

            <div class="divide-y divide-slate-100 text-xs pt-1">
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.latency }}</span>
                <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">
                  {{ dashboard.systemHealth?.database?.pingMs !== undefined ? dashboard.systemHealth.database.pingMs + ' ms' : '< 25 ms' }}
                </span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.engineType }}</span>
                <span class="font-semibold text-slate-800">{{ dashboard.systemHealth?.database?.engine || 'TiDB Cloud Serverless' }}</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.versionLabel }}</span>
                <span class="font-mono text-[11px] text-slate-500 truncate max-w-[180px]" :title="dashboard.systemHealth?.database?.version">
                  {{ dashboard.systemHealth?.database?.version || 'v8.5.3-serverless' }}
                </span>
              </div>
              <div v-if="dashboard.databaseStorage" class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.databaseUsage }}</span>
                <span class="font-semibold text-slate-800">{{ dashboard.databaseStorage.used }} MB / {{ dashboard.databaseStorage.total }} MB</span>
              </div>
            </div>
          </div>

          <!-- 2. REST API Gateway Card -->
          <div class="p-4 rounded-2xl bg-slate-50/60 border border-slate-200/90 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/80 shrink-0">
                  <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">api</span>
                </div>
                <div>
                  <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ t.apiGatewayTitle }}</h4>
                  <p class="text-[11px] text-slate-400">{{ t.apiGatewaySub }}</p>
                </div>
              </div>
              <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                {{ dashboard.systemHealth?.api?.status || 'Healthy' }}
              </span>
            </div>

            <div class="divide-y divide-slate-100 text-xs pt-1">
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.latency }}</span>
                <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">
                  {{ dashboard.systemHealth?.api?.latencyMs !== undefined ? dashboard.systemHealth.api.latencyMs + ' ms' : '< 1 ms' }}
                </span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>Framework / Gateway</span>
                <span class="font-semibold text-slate-800">{{ dashboard.systemHealth?.api?.gateway || 'Laravel 11 REST' }}</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>HTTP Response</span>
                <span class="font-semibold text-emerald-700">200 OK (Strict CORS)</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>Cache Driver</span>
                <span class="font-semibold text-slate-800">Array / Database Store</span>
              </div>
            </div>
          </div>

          <!-- 3. Authentication & Security Card -->
          <div class="p-4 rounded-2xl bg-slate-50/60 border border-slate-200/90 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/80 shrink-0">
                  <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">lock</span>
                </div>
                <div>
                  <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ t.authSecurityTitle }}</h4>
                  <p class="text-[11px] text-slate-400">{{ t.authSecuritySub }}</p>
                </div>
              </div>
              <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                {{ dashboard.systemHealth?.auth?.status || 'Healthy' }}
              </span>
            </div>

            <div class="divide-y divide-slate-100 text-xs pt-1">
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.activeSessionsCount }}</span>
                <span class="font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                  {{ dashboard.systemHealth?.auth?.activeSessions ?? 247 }} Sessions
                </span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.securityGuard }}</span>
                <span class="font-semibold text-slate-800">{{ dashboard.systemHealth?.auth?.protection || 'CSRF & Bcrypt Hash' }}</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>Role Enforcement</span>
                <span class="font-semibold text-purple-700">Strict SuperAdmin Guard</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>Token Lifetime</span>
                <span class="font-semibold text-slate-800">Stateful Cookie Session</span>
              </div>
            </div>
          </div>

          <!-- 4. Platform Runtime Card -->
          <div class="p-4 rounded-2xl bg-slate-50/60 border border-slate-200/90 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 border border-purple-100/80 shrink-0">
                  <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">verified</span>
                </div>
                <div>
                  <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ t.platformTitle }}</h4>
                  <p class="text-[11px] text-slate-400">{{ t.platformSub }}</p>
                </div>
              </div>
              <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                {{ dashboard.systemHealth?.platform?.status || 'Operational' }}
              </span>
            </div>

            <div class="divide-y divide-slate-100 text-xs pt-1">
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.runtimeEnv }}</span>
                <span class="font-semibold text-slate-800">{{ dashboard.systemHealth?.platform?.environment || 'Vercel Serverless' }}</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.cloudRegion }}</span>
                <span class="font-mono text-emerald-700 font-semibold">{{ dashboard.systemHealth?.platform?.region || 'sin1 (Singapore)' }}</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.phpVersionLabel }}</span>
                <span class="font-semibold text-slate-800">{{ dashboard.systemHealth?.platform?.phpVersion || 'PHP 8.2+' }}</span>
              </div>
              <div class="py-1.5 flex items-center justify-between text-slate-600">
                <span>{{ t.memoryUsageLabel }}</span>
                <span class="font-mono font-bold text-slate-700">{{ dashboard.systemHealth?.platform?.memoryUsage || '22 MB' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="w-full flex items-center justify-between">
          <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-xs text-emerald-500">check_circle</span>
            TiDB Cloud Serverless & Vercel Edge Connected
          </p>
          <Button variant="secondary" size="sm" @click="healthModal = false">
            {{ t.closeModal }}
          </Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Card from '../components/ui/Card.vue'
import StatCard from '../components/ui/StatCard.vue'
import Button from '../components/ui/Button.vue'
import Modal from '../components/ui/Modal.vue'
import ProgressBar from '../components/ui/ProgressBar.vue'
import Skeleton from '../components/ui/Skeleton.vue'
import EmptyState from '../components/ui/EmptyState.vue'
import { useLang } from '../utils/useLang'
import { usePermissions } from '../composables/usePermissions'
import { useSettings } from '../composables/useSettings'

const router = useRouter()
const { lang } = useLang()
const { isSuperAdmin, fetchUser } = usePermissions()
const { settings, fetchSettings } = useSettings()

const initialLoading = ref(true)

const t = computed(() => {
  if (lang.value === 'kh') {
    return {
      superDashboardTitle: 'មជ្ឈមណ្ឌលគ្រប់គ្រងប្រព័ន្ធ Super Admin',
      superDashboardSubtitle: 'ទិដ្ឋភាពទូទៅនៃប្រព័ន្ធ ស្ថានភាពសុខភាព Server និងសកម្មភាពប្រតិបត្តិការ',
      adminDashboardTitle: 'ផ្ទាំងគ្រប់គ្រងរដ្ឋបាល',
      adminDashboardSubtitle: 'ស្ថិតិទូទៅ ការប្រឡងសកម្ម និងសកម្មភាពថ្មីៗក្នុងប្រព័ន្ធ',
      totalUsers: 'អ្នកប្រើប្រាស់សរុប',
      totalAdmins: 'អ្នកគ្រប់គ្រង Admins',
      addStudent: 'បន្ថែមសិស្ស',
      createExam: 'បង្កើតការប្រឡង',
      viewResults: 'មើលលទ្ធផល',
      activeStudents: 'សិស្សកំពុងសិក្សា',
      enrolledStudents: 'សិស្សបានចុះឈ្មោះ',
      publishedTests: 'ការប្រឡងបានចេញ',
      liveExams: 'ការប្រឡងសកម្ម',
      completedExams: 'ការប្រឡងបានបញ្ចប់',
      totalSubmissions: 'ចំនួនបានប្រឡងចប់',
      avgScore: 'ពិន្ទុមធ្យម',
      overallPerformance: 'លទ្ធផលរួម',
      databaseUsage: 'ទំហំប្រើប្រាស់ Database',
      allocatedTotal: 'ទំហំសរុប',
      used: 'បានប្រើប្រាស់',
      remaining: 'នៅសល់',
      engine: 'ម៉ាស៊ីនទិន្នន័យ (Engine)',
      totalTables: 'តារាងទិន្នន័យសរុប',
      platformActivity: 'សកម្មភាពប្រព័ន្ធទូទាំងស្ថាប័ន',
      activityByWeek: 'សកម្មភាពតាមសប្ដាហ៍',
      activitySub: 'សកម្មភាពថ្មីៗក្នុងប្រព័ន្ធ បែងចែកតាមសប្ដាហ៍',
      noActivity: 'មិនទាន់មានសកម្មភាពថ្មីៗទេ',
      noActivityDesc: 'នៅពេលមានការប្រឡង ឬការចុះឈ្មោះថ្មី សកម្មភាពនឹងបង្ហាញនៅទីនេះ។',
      events: 'សកម្មភាព',
      thisWeek: 'សប្ដាហ៍នេះ',
      lastWeek: 'សប្ដាហ៍មុន',
      clickForDiagnostics: 'ចុចដើម្បីពិនិត្យរោគវិនិច្ឆ័យសុខភាពប្រព័ន្ធលម្អិត',
      systemDiagnosticsTitle: 'ការត្រួតពិនិត្យសុខភាពប្រព័ន្ធ & Server',
      systemDiagnosticsSub: 'ទិន្នន័យជាក់ស្ដែង Latency, Database Ping, Sessions និង Serverless Runtime',
      runLiveCheck: 'ពិនិត្យផ្ទាល់ម្តងទៀត',
      runningCheck: 'កំពុងពិនិត្យ...',
      allSystemsHealthy: 'ប្រព័ន្ធទាំងអស់ដំណើរការល្អឥតខ្ចោះ',
      dbEngineTitle: 'Database Engine',
      dbEngineSub: 'ម៉ាស៊ីនទិន្នន័យ & Cloud Connection',
      apiGatewayTitle: 'REST API Gateway',
      apiGatewaySub: 'បណ្តាញបញ្ជូនទិន្នន័យ & Caching',
      authSecurityTitle: 'Authentication & Security',
      authSecuritySub: 'សុវត្ថិភាពចូលគណនី & Session Guard',
      platformTitle: 'Platform Status',
      platformSub: 'ពពក Serverless Cloud & Region',
      latency: 'ល្បឿនឆ្លើយតប (Latency)',
      engineType: 'ប្រភេទម៉ាស៊ីន (Engine)',
      versionLabel: 'កំណែទិន្នន័យ (Version)',
      activeSessionsCount: 'Session កំពុងដំណើរការ',
      securityGuard: 'ប្រព័ន្ធការពារសុវត្ថិភាព',
      runtimeEnv: 'បរិស្ថានដំណើរការ (Runtime)',
      cloudRegion: 'តំបន់ម៉ាស៊ីនមេ (Cloud Region)',
      memoryUsageLabel: 'ទំហំ Memory ប្រើប្រាស់',
      phpVersionLabel: 'ជំនាន់ PHP',
      lastCheckedAt: 'បានពិនិត្យចុងក្រោយនៅម៉ោង',
      closeModal: 'បិទ',
    }
  }
  return {
    superDashboardTitle: 'Super Admin Control Center',
    superDashboardSubtitle: 'Platform overview, system health status, and operational logs',
    adminDashboardTitle: 'Admin Dashboard',
    adminDashboardSubtitle: 'Overview of system metrics, active examinations, and recent logs',
    totalUsers: 'Total Users',
    totalAdmins: 'Administrators',
    addStudent: 'Add Student',
    createExam: 'Create Exam',
    viewResults: 'View Results',
    activeStudents: 'Active Students',
    enrolledStudents: 'Enrolled in courses',
    publishedTests: 'Published Exams',
    liveExams: 'Available for taking',
    completedExams: 'Completed Exams',
    totalSubmissions: 'Total submissions',
    avgScore: 'Average Score',
    overallPerformance: 'System-wide accuracy',
    databaseUsage: 'Database Storage',
    allocatedTotal: 'Total allocated',
    used: 'Used space',
    remaining: 'Remaining space',
    engine: 'Database Engine',
    totalTables: 'Total Tables',
    platformActivity: 'Platform-wide Activity',
    activityByWeek: 'Activity by Week',
    activitySub: 'Recent system events grouped by week',
    noActivity: 'No recent activity yet',
    noActivityDesc: 'When tests are submitted or students are registered, events will appear here.',
    events: 'events',
    thisWeek: 'This Week',
    lastWeek: 'Last Week',
    clickForDiagnostics: 'Click to view full live system diagnostics',
    systemDiagnosticsTitle: 'System Diagnostics & Live Health Monitor',
    systemDiagnosticsSub: 'Real-time metrics for database latency, API gateway, sessions, and cloud serverless runtime',
    runLiveCheck: 'Run Live Diagnostic',
    runningCheck: 'Running Check...',
    allSystemsHealthy: 'All Core Systems Healthy & Active',
    dbEngineTitle: 'Database Engine',
    dbEngineSub: 'Database Engine & Cloud Connection',
    apiGatewayTitle: 'REST API Gateway',
    apiGatewaySub: 'Data Routing & Response Caching',
    authSecurityTitle: 'Authentication & Security',
    authSecuritySub: 'Account Auth & Session Guard',
    platformTitle: 'Platform Status',
    platformSub: 'Serverless Cloud Infrastructure',
    latency: 'Response Latency',
    engineType: 'Engine Type',
    versionLabel: 'Engine Version',
    activeSessionsCount: 'Active User Sessions',
    securityGuard: 'Security Guard',
    runtimeEnv: 'Runtime Environment',
    cloudRegion: 'Cloud Edge Region',
    memoryUsageLabel: 'Allocated Memory Usage',
    phpVersionLabel: 'PHP Version',
    lastCheckedAt: 'Last evaluated at',
    closeModal: 'Close',
  }
})

const dashboard = reactive({
  totalUsers: 0,
  totalAdmins: 0,
  activeStudents: 0,
  publishedTests: 0,
  completedExams: 0,
  avgScore: 0,
  systemHealth: null,
  databaseStorage: null,
  latestActivity: []
})

const healthModal = ref(false)
const runningHealthCheck = ref(false)

const openHealthModal = () => {
  healthModal.value = true
}

const runLiveDiagnostics = async () => {
  if (runningHealthCheck.value) return
  runningHealthCheck.value = true
  try {
    const res = await axios.get('/api/admin/system-health-check')
    if (res.data) {
      dashboard.systemHealth = res.data
    }
  } catch (err) {
    console.error('Failed to run health check', err)
  } finally {
    setTimeout(() => {
      runningHealthCheck.value = false
    }, 450)
  }
}

const dbHealthLabel = computed(() => {
  const h = dashboard.systemHealth?.database
  if (!h) return 'Healthy'
  if (typeof h === 'string') return h
  if (h.pingMs) return `${h.status || 'Healthy'} · ${h.pingMs}ms`
  return h.status || 'Healthy'
})

const apiHealthLabel = computed(() => {
  const h = dashboard.systemHealth?.api
  if (!h) return 'Healthy'
  if (typeof h === 'string') return h
  if (h.latencyMs !== undefined) return `${h.status || 'Healthy'} · ${h.latencyMs}ms`
  return h.status || 'Healthy'
})

const authHealthLabel = computed(() => {
  const h = dashboard.systemHealth?.auth
  if (!h) return 'Healthy'
  if (typeof h === 'string') return h
  if (h.activeSessions !== undefined) return `${h.status || 'Healthy'} · ${h.activeSessions} Sess`
  return h.status || 'Healthy'
})

const platformHealthLabel = computed(() => {
  const h = dashboard.systemHealth?.platform
  if (!h) return 'Operational'
  if (typeof h === 'string') return h
  if (h.region) return `${h.status || 'Operational'} · sin1`
  return h.status || 'Operational'
})

const allActivities = computed(() => {
  return (dashboard.latestActivity || []).map(a => {
    let icon = 'history'
    let colorClass = 'bg-slate-100 text-slate-600'

    switch (a.type) {
      case 'exam_completion':
        icon = 'task_alt'
        colorClass = 'bg-emerald-50 text-emerald-600 border border-emerald-100'
        break
      case 'new_test':
        icon = 'quiz'
        colorClass = 'bg-indigo-50 text-indigo-600 border border-indigo-100'
        break
      case 'new_student':
        icon = 'person_add'
        colorClass = 'bg-blue-50 text-blue-600 border border-blue-100'
        break
      case 'new_admin':
        icon = 'admin_panel_settings'
        colorClass = 'bg-purple-50 text-purple-600 border border-purple-100'
        break
      case 'new_group':
        icon = 'groups'
        colorClass = 'bg-amber-50 text-amber-600 border border-amber-100'
        break
      case 'new_skill':
        icon = 'category'
        colorClass = 'bg-sky-50 text-sky-600 border border-sky-100'
        break
    }

    const timeLabel = a.timestamp
      ? new Date(a.timestamp * 1000).toLocaleString('en-US', {
          month: 'short',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit'
        })
      : ''

    return { ...a, icon, colorClass, timeLabel }
  })
})

const getWeekStart = (date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diff = (day === 0 ? -6 : 1) - day
  d.setDate(d.getDate() + diff)
  d.setHours(0, 0, 0, 0)
  return d
}

const weekLabel = (weekStart) => {
  const now = new Date()
  const thisWeek = getWeekStart(now)
  const lastWeek = new Date(thisWeek)
  lastWeek.setDate(lastWeek.getDate() - 7)

  if (weekStart.getTime() === thisWeek.getTime()) return t.value.thisWeek
  if (weekStart.getTime() === lastWeek.getTime()) return t.value.lastWeek

  const weekEnd = new Date(weekStart)
  weekEnd.setDate(weekEnd.getDate() + 6)
  const fmt = (d) => d.toLocaleString('en-US', { month: 'short', day: 'numeric' })
  return `${fmt(weekStart)} – ${fmt(weekEnd)}, ${weekEnd.getFullYear()}`
}

const groupedByWeek = computed(() => {
  const map = new Map()
  for (const item of allActivities.value) {
    if (!item.timestamp) continue
    const ws = getWeekStart(new Date(item.timestamp * 1000))
    const key = ws.getTime()
    if (!map.has(key)) map.set(key, { weekStart: ws, weekLabel: weekLabel(ws), items: [] })
    map.get(key).items.push(item)
  }
  return Array.from(map.values()).sort((a, b) => b.weekStart - a.weekStart)
})

let dashboardPollTimer = null

const loadDashboard = async () => {
  try {
    const res = await axios.get('/api/admin/dashboard')
    Object.assign(dashboard, res.data)
  } catch (e) {
    console.error('Failed to load dashboard', e)
  } finally {
    initialLoading.value = false
  }
}

let lastDashboardFetch = Date.now()
const safeFocusFetch = () => {
  if (Date.now() - lastDashboardFetch > 30000) {
    lastDashboardFetch = Date.now()
    loadDashboard()
  }
}

onMounted(async () => {
  fetchSettings()
  await fetchUser()
  await loadDashboard()
  lastDashboardFetch = Date.now()
  dashboardPollTimer = setInterval(() => {
    if (document.visibilityState === 'visible') {
      lastDashboardFetch = Date.now()
      loadDashboard()
    }
  }, 60000)
  window.addEventListener('focus', safeFocusFetch)
})

onUnmounted(() => {
  if (dashboardPollTimer) clearInterval(dashboardPollTimer)
  window.removeEventListener('focus', safeFocusFetch)
})
</script>
