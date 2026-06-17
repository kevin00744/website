<template>
  <AdminLayout title="分店管理">
    <template #actions>
      <Link href="/admin/stores/create" class="bg-primary-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-primary-700 transition">
        + 新增分店
      </Link>
    </template>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">名稱</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">電話</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">地址</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">狀態</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in stores" :key="s.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ s.name }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ s.phone ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">{{ s.address ?? '—' }}</td>
            <td class="px-4 py-3">
              <span :class="s.is_active ? 'text-green-600' : 'text-gray-400'">{{ s.is_active ? '營運中' : '停用' }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Link :href="`/admin/stores/${s.id}/edit`" class="text-primary-600 hover:underline text-xs">編輯</Link>
                <button @click="destroy(s)" class="text-red-500 hover:underline text-xs">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!stores.length">
            <td colspan="5" class="px-4 py-12 text-center text-gray-400">尚無分店</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({ stores: Array })

function destroy(s) {
  if (!confirm(`確定刪除「${s.name}」這個分店？`)) return
  router.delete(`/admin/stores/${s.id}`)
}
</script>
