<template>
  <AdminLayout title="聯絡訊息">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">姓名</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">興趣項目</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">訊息</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">送出時間</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in submissions.data" :key="s.id" :class="!s.is_read && 'bg-primary-50/40'">
            <td class="px-4 py-3 font-medium text-gray-900">{{ s.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ s.email }}</td>
            <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">{{ s.interest ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 max-w-xs truncate" :title="[s.message, s.note].filter(Boolean).join(' / ')">
              {{ [s.message, s.note].filter(Boolean).join(' / ') || '—' }}
            </td>
            <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">{{ formatDate(s.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <button @click="toggleRead(s)" class="text-primary-600 hover:underline text-xs">
                  {{ s.is_read ? '標記未讀' : '標記已讀' }}
                </button>
                <button @click="destroy(s)" class="text-red-500 hover:underline text-xs">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!submissions.data.length">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400">尚無聯絡訊息</td>
          </tr>
        </tbody>
      </table>

      <div v-if="submissions.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">共 {{ submissions.total }} 筆</p>
        <div class="flex gap-1">
          <Link
            v-for="link in submissions.links"
            :key="link.label"
            :href="link.url ?? '#'"
            v-html="link.label"
            class="px-2.5 py-1 rounded text-xs border"
            :class="link.active ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({ submissions: Object })

function toggleRead(s) {
  router.patch(`/admin/contacts/${s.id}`, { is_read: !s.is_read }, { preserveScroll: true })
}

function destroy(s) {
  if (!confirm(`確定刪除「${s.name}」的訊息？`)) return
  router.delete(`/admin/contacts/${s.id}`, { preserveScroll: true })
}

function formatDate(d) {
  return new Date(d).toLocaleString('zh-TW')
}
</script>
