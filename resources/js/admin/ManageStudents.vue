<template>
  <div class="space-y-6">

    <!-- Header + Search -->
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="font-manrope text-lg font-bold text-slate-900">User Management</h2>
          <p class="text-sm text-slate-500">Manage students and administrators.</p>
        </div>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search users…"
            class="w-64 rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-sm outline-none transition focus:border-[#00288e] focus:bg-white"
          />
        </div>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 items-start">
      <!-- Students Column -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-[#00288e]">
              <span class="material-symbols-outlined text-xl">group</span>
            </div>
            <div>
              <h3 class="font-manrope font-bold text-slate-900 leading-tight">Students</h3>
              <p class="text-xs text-slate-500">{{ filteredStudentsList.length }} enrolled</p>
            </div>
          </div>
          <button @click="openAddModal('Student')" class="flex items-center gap-1 rounded-xl bg-[#00288e] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1e40af]">
            <span class="material-symbols-outlined text-lg">add</span> Add
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
              <tr class="bg-white">
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Student</th>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Skill / Batch</th>
                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="student in filteredStudentsList" :key="student.id" class="hover:bg-blue-50/30 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-medium text-slate-800">{{ student.name }}</div>
                  <div class="text-xs text-slate-500">{{ student.email }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="text-xs font-semibold text-[#00288e]">{{ student.skill }}</div>
                  <div class="text-[10px] text-slate-400">{{ student.batch }} • {{ student.shift }}</div>
                </td>
                <td class="px-4 py-3 text-right">
                  <button @click="editStudent(student)" class="text-slate-400 hover:text-[#00288e] p-1"><span class="material-symbols-outlined text-sm">edit</span></button>
                  <button @click="deleteStudent(student)" class="text-slate-400 hover:text-red-500 p-1"><span class="material-symbols-outlined text-sm">delete</span></button>
                </td>
              </tr>
              <tr v-if="filteredStudentsList.length === 0">
                <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-400">
                  <span class="material-symbols-outlined block text-3xl mb-2">search_off</span>
                  No students found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Admins Column -->
      <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
              <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
            </div>
            <div>
              <h3 class="font-manrope font-bold text-slate-900 leading-tight">Administrators</h3>
              <p class="text-xs text-slate-500">{{ filteredAdminsList.length }} system admins</p>
            </div>
          </div>
          <button @click="openAddModal('Admin')" class="flex items-center gap-1 rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
            <span class="material-symbols-outlined text-lg">add</span> Add
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
              <tr class="bg-white">
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Admin</th>
                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Role</th>
                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="admin in filteredAdminsList" :key="admin.id" class="hover:bg-purple-50/30 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-medium text-slate-800">{{ admin.name }}</div>
                  <div class="text-xs text-slate-500">{{ admin.email }}</div>
                </td>
                <td class="px-4 py-3">
                  <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                    :class="admin.role === 'SuperAdmin' || admin.role === 'Super Admin' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600'">
                    {{ admin.role }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <template v-if="!( (admin.role === 'SuperAdmin' || admin.role === 'Super Admin') && currentUserRole !== 'SuperAdmin' && currentUserRole !== 'Super Admin' )">
                    <button @click="editStudent(admin)" class="text-slate-400 hover:text-purple-600 p-1"><span class="material-symbols-outlined text-sm">edit</span></button>
                    <button @click="deleteStudent(admin)" class="text-slate-400 hover:text-red-500 p-1"><span class="material-symbols-outlined text-sm">delete</span></button>
                  </template>
                </td>
              </tr>
              <tr v-if="filteredAdminsList.length === 0">
                <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-400">
                  <span class="material-symbols-outlined block text-3xl mb-2">search_off</span>
                  No admins found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div
      v-if="editingStudent"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
      <div class="mx-4 w-full max-w-lg rounded-3xl bg-white p-8 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-manrope text-xl font-bold text-slate-900">Edit User</h3>
          <button @click="cancelEdit" class="text-slate-400 hover:text-slate-700">
            <span class="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="block space-y-1 sm:col-span-2">
            <span class="text-xs font-bold text-slate-500 uppercase">Role</span>
            <div class="px-4 py-2.5 bg-slate-100 rounded-xl text-sm font-semibold text-slate-700">{{ editForm.role }}</div>
          </label>
          <label class="block space-y-1">
            <span class="text-xs font-bold text-slate-500 uppercase">First Name</span>
            <input v-model="editForm.firstName" type="text" class="field" />
          </label>
          <label class="block space-y-1">
            <span class="text-xs font-bold text-slate-500 uppercase">Last Name</span>
            <input v-model="editForm.lastName" type="text" class="field" />
          </label>
          <label class="block space-y-1 sm:col-span-2">
            <span class="text-xs font-bold text-slate-500 uppercase">Email</span>
            <input v-model="editForm.email" type="email" class="field" />
          </label>
          <label class="block space-y-1" :class="{'sm:col-span-2': editForm.role !== 'Student'}">
            <span class="text-xs font-bold text-slate-500 uppercase">Phone</span>
            <input v-model="editForm.phone" type="text" class="field" />
          </label>
          
          <template v-if="editForm.role === 'Student'">
            <label class="block space-y-1">
              <span class="text-xs font-bold text-slate-500 uppercase">Shift</span>
              <select v-model="editForm.shift" class="field">
                <option>Morning</option><option>Afternoon</option><option>Evening</option>
              </select>
            </label>
            <label class="block space-y-1">
              <span class="text-xs font-bold text-slate-500 uppercase">Skill</span>
              <input v-model="editForm.skill" type="text" class="field" />
            </label>
            <label class="block space-y-1">
              <span class="text-xs font-bold text-slate-500 uppercase">Batch</span>
              <input v-model="editForm.batch" type="text" class="field" />
            </label>
          </template>

          <!-- Password Change Section -->
          <div class="sm:col-span-2">
            <div class="flex items-center gap-3 my-2">
              <div class="flex-1 h-px bg-slate-200"></div>
              <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Change Password (optional)</span>
              <div class="flex-1 h-px bg-slate-200"></div>
            </div>
          </div>
          <label class="block space-y-1">
            <span class="text-xs font-bold text-slate-500 uppercase">New Password</span>
            <div class="relative">
              <input
                v-model="editForm.newPassword"
                :type="showNewPass ? 'text' : 'password'"
                class="field"
                placeholder="New Password"
              />
              <button type="button" @click="showNewPass = !showNewPass"
                class="absolute right-3 top-1/2 -translate-y-1/2 mt-0.5 text-slate-400 hover:text-slate-700">
                <span class="material-symbols-outlined text-lg">{{ showNewPass ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
          </label>
          <label class="block space-y-1">
            <span class="text-xs font-bold text-slate-500 uppercase">Confirm Password</span>
            <div class="relative">
              <input
                v-model="editForm.confirmPassword"
                :type="showConfirmPass ? 'text' : 'password'"
                class="field"
                placeholder="Repeat new password"
              />
              <button type="button" @click="showConfirmPass = !showConfirmPass"
                class="absolute right-3 top-1/2 -translate-y-1/2 mt-0.5 text-slate-400 hover:text-slate-700">
                <span class="material-symbols-outlined text-lg">{{ showConfirmPass ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
            <p v-if="editForm.newPassword && editForm.confirmPassword && editForm.newPassword !== editForm.confirmPassword"
              class="text-xs text-red-500 mt-1">
              Passwords do not match.
            </p>
          </label>
        </div>
        <div class="mt-6 flex gap-3">
          <button @click="cancelEdit" class="flex-1 rounded-xl border-2 border-slate-200 py-3 font-semibold text-slate-700 hover:border-slate-300">Cancel</button>
          <button @click="saveStudent" class="flex-1 rounded-xl bg-[#00288e] py-3 font-semibold text-white hover:bg-[#1e40af]">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Add Student Modal -->
    <div
      v-if="addingStudent"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
      <div class="mx-4 w-full max-w-lg rounded-3xl bg-white p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-manrope text-xl font-bold text-slate-900">Add User</h3>
          <button @click="addingStudent = false" class="text-slate-400 hover:text-slate-700">
            <span class="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="block space-y-1 sm:col-span-2"><span class="text-xs font-bold text-slate-500 uppercase">Role</span>
            <select v-model="addForm.role" class="field">
              <option>Student</option>
              <option>Admin</option>
              <option v-if="currentUserRole === 'SuperAdmin' || currentUserRole === 'Super Admin'" value="SuperAdmin">Super Admin</option>
            </select>
          </label>
          <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">First Name</span><input v-model="addForm.firstName" type="text" class="field" /></label>
          <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Last Name</span><input v-model="addForm.lastName" type="text" class="field" /></label>
          <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Username</span><input v-model="addForm.username" type="text" class="field" /></label>
          <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Email</span><input v-model="addForm.email" type="email" class="field" /></label>
          <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Password</span><input v-model="addForm.password" type="password" class="field" /></label>
          <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Phone</span><input v-model="addForm.phone" type="text" class="field" /></label>
          
          <template v-if="addForm.role === 'Student'">
            <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Gender</span>
              <select v-model="addForm.gender" class="field"><option>Male</option><option>Female</option><option>Other</option></select>
            </label>
            <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Shift</span>
              <select v-model="addForm.shift" class="field"><option>Morning</option><option>Afternoon</option><option>Evening</option></select>
            </label>
            <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Skill</span>
              <select v-model="addForm.skillId" class="field">
                <option v-for="s in skillsList" :key="s.SkillId" :value="s.SkillId">{{ s.SkillName }}</option>
              </select>
            </label>
            <label class="block space-y-1"><span class="text-xs font-bold text-slate-500 uppercase">Batch</span>
              <select v-model="addForm.batchId" class="field">
                <option v-for="b in batchesList" :key="b.BatchId" :value="b.BatchId">{{ b.BatchName }}</option>
              </select>
            </label>
          </template>
        </div>
        <div class="mt-6 flex gap-3">
          <button @click="addingStudent = false" class="flex-1 rounded-xl border-2 border-slate-200 py-3 font-semibold text-slate-700">Cancel</button>
          <button @click="saveNewStudent" class="flex-1 rounded-xl bg-[#00288e] py-3 font-semibold text-white hover:bg-[#1e40af]">
            {{ addForm.role === 'Student' ? 'Add Student' : 'Add Admin' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const searchQuery = ref('')
const currentUserRole = ref('')
const students = ref([])
const editingStudent = ref(null)
const addingStudent = ref(false)
const skillsList = ref([])
const batchesList = ref([])

const editForm = reactive({ role: 'Student', firstName: '', lastName: '', email: '', phone: '', skill: '', batch: '', shift: '', newPassword: '', confirmPassword: '' })
const showNewPass = ref(false)
const showConfirmPass = ref(false)
const addForm = reactive({ role: 'Student', firstName: '', lastName: '', email: '', username: '', password: '', phone: '', gender: 'Male', shift: 'Morning', skillId: '', batchId: '' })

const filteredStudentsList = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  let list = students.value.filter(s => s.role === 'Student')
  if (!q) return list
  return list.filter(s =>
    (s.name && s.name.toLowerCase().includes(q)) ||
    (s.email && s.email.toLowerCase().includes(q)) ||
    (s.skill && s.skill.toLowerCase().includes(q)) ||
    (s.batch && s.batch.toLowerCase().includes(q))
  )
})

const filteredAdminsList = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  let list = students.value.filter(s => s.role !== 'Student')
  if (!q) return list
  return list.filter(s =>
    (s.name && s.name.toLowerCase().includes(q)) ||
    (s.email && s.email.toLowerCase().includes(q)) ||
    (s.role && s.role.toLowerCase().includes(q))
  )
})

const openAddModal = (roleType) => {
  addForm.role = roleType
  addingStudent.value = true
}

const editStudent = (student) => {
  editingStudent.value = student
  const [first, ...rest] = student.name.split(' ')
  editForm.role      = student.role || 'Student'
  editForm.firstName = first || ''
  editForm.lastName  = rest.join(' ') || ''
  editForm.email     = student.email || ''
  editForm.phone     = student.phone || ''
  editForm.skill     = student.skill || ''
  editForm.batch     = student.batch || ''
  editForm.shift     = student.shift || 'Morning'
  editForm.newPassword     = ''
  editForm.confirmPassword = ''
  showNewPass.value    = false
  showConfirmPass.value = false
}
const cancelEdit = () => { editingStudent.value = null }

const saveStudent = async () => {
  if (!editingStudent.value) return
  if (editForm.newPassword && editForm.newPassword !== editForm.confirmPassword) {
    alert('Passwords do not match.')
    return
  }
  if (editForm.newPassword && editForm.newPassword.length < 6) {
    alert('Password must be at least 6 characters.')
    return
  }
  try {
    const payload = {
      role:      editForm.role,
      firstName: editForm.firstName,
      lastName:  editForm.lastName,
      email:     editForm.email,
      phone:     editForm.phone,
      skill:     editForm.skill,
      batch:     editForm.batch,
      shift:     editForm.shift,
    }
    if (editForm.newPassword) payload.newPassword = editForm.newPassword
    const res = await axios.put(`/api/admin/students/${editingStudent.value.id}`, payload)
    const upd = res.data.student
    const idx = students.value.findIndex(s => s.id === editingStudent.value.id)
    if (idx !== -1) students.value[idx] = { ...students.value[idx], ...upd }
    editingStudent.value = null
  } catch (e) { alert(e.response?.data?.message || 'Update failed.') }
}

const saveNewStudent = async () => {
  try {
    await axios.post('/api/admin/students', addForm)
    addingStudent.value = false
    await loadStudents()
  } catch (e) { alert(e.response?.data?.message || 'Failed to add student.') }
}

const deleteStudent = async (student) => {
  if (!confirm(`Delete ${student.name}? This cannot be undone.`)) return
  try {
    await axios.delete(`/api/admin/students/${student.id}`)
    students.value = students.value.filter(s => s.id !== student.id)
  } catch (e) { alert('Failed to delete student.') }
}

const loadStudents = async () => {
  const res = await axios.get('/api/admin/students')
  students.value = res.data.students.map(s => ({
    id: s.id, name: s.name, email: s.email,
    phone: s.phone, skill: s.skill, batch: s.batch, shift: s.shift, status: s.status, role: s.role,
  }))
}

onMounted(async () => {
  try {
    const profileRes = await axios.get('/api/profile')
    currentUserRole.value = profileRes.data.user?.role || ''
    await loadStudents()
    const sb = await axios.get('/api/admin/skills-batches')
    skillsList.value = sb.data.skills
    batchesList.value = sb.data.batches
    if (skillsList.value.length) addForm.skillId = skillsList.value[0].SkillId
    if (batchesList.value.length) addForm.batchId = batchesList.value[0].BatchId
  } catch (e) { console.error(e) }
})
</script>

<style scoped>
.font-manrope { font-family: 'Manrope', sans-serif; }
.field {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background-color: #f8fafc;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  outline: none;
  transition: border-color 150ms, background-color 150ms;
}
.field:focus {
  border-color: #00288e;
  background-color: #fff;
}
</style>
