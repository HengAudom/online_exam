<template>
  <div class="min-h-screen bg-[#f0f4ff] flex">

    <!-- Mobile Sidebar Overlay (Drawer) -->
    <div 
      v-if="isMobileMenuOpen" 
      class="fixed inset-0 z-50 lg:hidden bg-slate-900/60 backdrop-blur-sm transition-all animate-in fade-in duration-300"
      @click="isMobileMenuOpen = false"
    >
      <div 
        class="w-72 h-full bg-[#00288e] text-white flex flex-col shadow-2xl animate-in slide-in-from-left duration-300"
        @click.stop
      >
        <!-- Mobile Logo Area -->
        <div class="px-6 py-6 border-b border-white/10 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
              <span class="material-symbols-outlined text-white text-xl">school</span>
            </div>
            <p class="font-manrope font-bold text-white leading-tight">Exam Admin</p>
          </div>
          <button @click="isMobileMenuOpen = false" class="text-white/60 hover:text-white transition-colors">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <!-- Mobile Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
          <button
            v-for="item in navItems"
            :key="item.route"
            @click="selectNav(item.route); isMobileMenuOpen = false"
            class="w-full flex items-center gap-3 rounded-xl px-4 py-3.5 text-sm font-medium transition-all"
            :class="isActive(item.route)
              ? 'bg-white text-[#00288e] shadow-lg'
              : 'text-white/80 hover:bg-white/10'"
          >
            <span class="material-symbols-outlined text-xl">{{ item.icon }}</span>
            <span>{{ item.label }}</span>
          </button>
        </nav>

        <!-- Mobile User Info Footer -->
        <div class="p-4 border-t border-white/10 bg-white/5">
          <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/10">
            <!-- Avatar with upload -->
            <div class="relative h-9 w-9 shrink-0 rounded-full overflow-hidden border border-white/20">
              <img v-if="currentUser.profileImage" :src="currentUser.profileImage" class="h-full w-full object-cover" />
              <span v-else class="flex h-full w-full items-center justify-center material-symbols-outlined text-white text-lg bg-white/20">person</span>
              <!-- Invisible input overlay — works on iOS -->
              <input
                type="file"
                accept="image/*"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                style="font-size:0;"
                @change="onFileChange"
              />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-white truncate">{{ currentUser.name }}</p>
              <p class="text-[9px] text-blue-300 uppercase font-bold tracking-tighter">{{ currentUser.role }}</p>
            </div>
            <button @click="handleLogout; isMobileMenuOpen = false" title="Sign Out" class="text-white/60 hover:text-white transition-colors">
              <span class="material-symbols-outlined text-xl">logout</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ Sidebar ══════════════════════════════════════════════════════ -->
    <aside
      class="hidden lg:flex flex-col w-72 shrink-0 bg-[#00288e] text-white sticky top-0 h-screen"
    >
      <!-- Logo -->
      <div class="px-6 py-6 border-b border-white/10">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
            <span class="material-symbols-outlined text-white text-xl">school</span>
          </div>
          <div>
            <p class="font-manrope font-bold text-white leading-tight">Exam Admin</p>
            <p class="text-xs text-blue-300">Management Portal</p>
          </div>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-blue-400">Navigation</p>
        <button
          v-for="item in navItems"
          :key="item.route"
          @click="selectNav(item.route)"
          class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all"
          :class="isActive(item.route)
            ? 'bg-white text-[#00288e] shadow-md'
            : 'text-white/80 hover:bg-white/10 hover:text-white'"
        >
          <span
            class="material-symbols-outlined text-xl"
            :style="isActive(item.route) ? 'font-variation-settings:\'FILL\' 1;' : ''"
          >{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </button>
      </nav>

      <!-- User info / logout -->
      <div class="px-4 py-4 border-t border-white/10">
        <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/10 group relative">
          <!-- Avatar with invisible input overlay — works on iOS/Android -->
          <div class="relative h-10 w-10 shrink-0 rounded-full overflow-hidden border-2 border-white/20 hover:border-white/50 transition-all cursor-pointer">
            <img 
              v-if="currentUser.profileImage" 
              :src="currentUser.profileImage" 
              class="h-full w-full object-cover"
            />
            <div 
              v-else 
              class="flex h-full w-full items-center justify-center bg-white/20"
            >
              <span class="material-symbols-outlined text-white text-xl">person</span>
            </div>
            <!-- Upload Overlay -->
            <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
              <span class="material-symbols-outlined text-white text-sm">photo_camera</span>
            </div>
            <!-- Invisible input covers full avatar — works on iOS -->
            <input
              type="file"
              accept="image/*"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              style="font-size:0;"
              @change="onFileChange"
            />
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white truncate">{{ currentUser.name }}</p>
            <p class="text-[10px] text-blue-300 uppercase tracking-widest font-bold">{{ currentUser.role }}</p>
          </div>
          
          <button @click="handleLogout" title="Sign Out" class="text-white/60 hover:text-white transition-colors">
            <span class="material-symbols-outlined text-xl">logout</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- ══ Main ═════════════════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col min-w-0">

      <!-- Top bar -->
      <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-20 shadow-sm">
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <!-- Mobile Menu Toggle -->
            <button 
              @click="isMobileMenuOpen = true" 
              class="lg:hidden flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100 transition-colors border border-slate-200"
              title="Open Menu"
            >
              <span class="material-symbols-outlined">menu</span>
            </button>
            <div>
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400 leading-none mb-1">Admin Panel</p>
              <h1 class="font-manrope text-xl font-bold text-slate-900 leading-none">{{ activeLabel }}</h1>
            </div>
          </div>

        </div>
      </header>

      <main class="flex-1 p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const isMobileMenuOpen = ref(false)
const currentUser = ref({ name: 'Loading...', role: '...', profileImage: null })

const fetchProfile = async () => {
  try {
    const res = await axios.get('/api/profile')
    const usr = res.data.user
    if (usr) {
      currentUser.value = {
        name: usr.name || 'Admin',
        role: usr.role === 'SuperAdmin' ? 'Super Admin' : (usr.role || 'Administrator'),
        profileImage: usr.profileImage
      }
    }
  } catch (e) {
    console.error('Failed to load profile', e)
    currentUser.value = { name: 'Admin', role: 'Error loading profile', profileImage: null }
  }
}

onMounted(fetchProfile)

const onFileChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)

  try {
    const res = await axios.post('/api/profile/upload-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    currentUser.value.profileImage = res.data.profileImage
    // Optional: show success toast
  } catch (err) {
    alert(err.response?.data?.message || 'Upload failed')
  }
}

const navItems = [
  { label: 'Dashboard',       icon: 'dashboard',       route: '/admin/dashboard' },
  { label: 'Users',           icon: 'manage_accounts',  route: '/admin/students' },
  { label: 'Tests',           icon: 'quiz',             route: '/admin/tests' },
  { label: 'Skills & Batches',icon: 'category',         route: '/admin/skills-batches' },
  { label: 'Results',         icon: 'bar_chart',        route: '/admin/results' },
]

const isActive = (navRoute) => route.path.startsWith(navRoute)

const activeLabel = computed(() => {
  const match = navItems.find(item => route.path.startsWith(item.route))
  return match?.label ?? 'Dashboard'
})

const selectNav = (navRoute) => router.push(navRoute)

const handleLogout = async () => {
  try { await axios.post('/api/logout') } catch {}
  router.push('/login')
}
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
</style>
