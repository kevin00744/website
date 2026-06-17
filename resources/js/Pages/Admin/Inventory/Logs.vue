<template>
  <AdminLayout title="庫存異動紀錄">
    <template #actions>
      <Link href="/admin/inventory" class="text-sm text-gray-500 hover:text-gray-700">← 返回庫存</Link>
    </template>

    <div v-if="stores.length > 1" class="mb-4">
      <select :value="filters.store_id ?? ''" @change="filter($event.target.value)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        <option value="">全部分店</option>
        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">時間</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">分店</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">商品</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">類型</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">數量</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">操作人</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">顧客</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">狀態</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-gray-400 text-xs">{{ formatDate(log.created_at) }}</td>
            <td class="px-4 py-3 text-gray-600">{{ log.store.name }}</td>
            <td class="px-4 py-3 text-gray-900 font-medium">{{ log.product.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ typeLabel(log.type) }}</td>
            <td class="px-4 py-3" :class="log.quantity_change >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ log.quantity_change >= 0 ? '+' : '' }}{{ log.quantity_change }}
            </td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ log.user.name }}</td>
            <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">{{ log.customer?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 rounded-full" :class="statusStyle(log.status)">{{ statusLabel(log.status) }}</span>
            </td>
          </tr>
          <tr v-if="!logs.data.length">
            <td colspan="8" class="px-4 py-12 text-center text-gray-400">尚無紀錄</td>
          </tr>
        </tbody>
      </table>

      <div v-if="logs.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">共 {{ logs.total }} 筆</p>
        <div class="flex gap-1">
          <Link
            v-for="link in logs.links"
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

const props = defineProps({ logs: Object, stores: Array, filters: Object })

const typeLabels = { adjustment: '調整', request: '補貨請求', usage: '顧客使用' }
function typeLabel(t) { return typeLabels[t] ?? t }

function filter(storeId) {
  router.get('/admin/inventory-logs', { store_id: storeId || undefined }, { preserveState: true, replace: true })
}

function formatDate(d) {
  return new Date(d).toLocaleString('zh-TW')
}

const STATUS_STYLE = {
  completed: 'bg-green-50 text-green-700',
  pending:   'bg-amber-50 text-amber-700',
  approved:  'bg-green-50 text-green-700',
  rejected:  'bg-gray-100 text-gray-500',
}
const STATUS_LABEL = {
  completed: '已完成',
  pending:   '待審核',
  approved:  '已核准',
  rejected:  '已駁回',
}

function statusStyle(s) { return STATUS_STYLE[s] ?? '' }
function statusLabel(s) { return STATUS_LABEL[s] ?? s }
</script>
