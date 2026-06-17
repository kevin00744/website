<template>
  <AdminLayout title="顧客資料">
    <template #actions>
      <Link href="/admin/customers/create" class="bg-primary-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-primary-700 transition">
        + 新增顧客
      </Link>
    </template>

    <div class="flex gap-3 mb-4">
      <input
        v-model="search"
        type="search"
        placeholder="搜尋姓名或電話..."
        class="flex-1 max-w-sm rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        @input="filter"
      />
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">姓名</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">分店</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">電話</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Line</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Email</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">地址</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="c in customers.data" :key="c.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ c.name }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ c.store?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.phone }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ c.line ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ c.email ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 hidden lg:table-cell truncate max-w-xs">{{ c.address ?? '—' }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Link :href="`/admin/customers/${c.id}/edit`" class="text-primary-600 hover:underline text-xs">編輯</Link>
                <button @click="destroy(c)" class="text-red-500 hover:underline text-xs">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!customers.data.length">
            <td colspan="7" class="px-4 py-12 text-center text-gray-400">尚無顧客資料</td>
          </tr>
        </tbody>
      </table>

      <div v-if="customers.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">共 {{ customers.total }} 筆</p>
        <div class="flex gap-1">
          <Link
            v-for="link in customers.links"
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
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ customers: Object, filters: Object })

const search = ref(props.filters?.search ?? '')

let timer
function filter() {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/admin/customers', { search: search.value }, { preserveState: true, replace: true })
  }, 300)
}

function destroy(c) {
  if (!confirm(`確定刪除「${c.name}」的顧客資料？`)) return
  router.delete(`/admin/customers/${c.id}`)
}
</script>
