<template>
  <AdminLayout title="帳號管理">
    <template #actions>
      <Link v-if="can_create" href="/admin/users/create" class="bg-primary-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-primary-700 transition">
        + 新增帳號
      </Link>
    </template>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">名稱</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">電話</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">角色</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">分店</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">狀態</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ user.phone ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ user.role_label }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ user.store?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <span :class="user.is_active ? 'text-green-600' : 'text-gray-400'">
                {{ user.is_active ? '啟用' : '停用' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Link v-if="user.can_manage" :href="`/admin/users/${user.id}/edit`" class="text-primary-600 hover:underline text-xs">編輯</Link>
                <button v-if="user.can_delete" @click="deleteUser(user)" class="text-red-500 hover:underline text-xs">刪除</button>
                <span v-if="!user.can_manage" class="text-xs text-gray-300">無權限</span>
              </div>
            </td>
          </tr>
          <tr v-if="!users.length">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400">尚無帳號</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({ users: Array, can_create: Boolean })

function deleteUser(user) {
  if (!confirm(`確定刪除「${user.name}」這個帳號？`)) return
  router.delete(`/admin/users/${user.id}`)
}
</script>
