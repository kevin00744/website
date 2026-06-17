<template>
  <AdminLayout title="庫存管理">
    <template #actions>
      <Link href="/admin/inventory-logs" class="text-sm text-gray-500 hover:text-gray-700">查看異動紀錄 →</Link>
    </template>

    <div v-if="stores.length > 1" class="mb-4">
      <select :value="selected_store.id" @change="switchStore($event.target.value)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
    </div>
    <h2 v-else class="text-base font-semibold text-gray-900 mb-4">{{ selected_store.name }}</h2>

    <!-- 待審核請求 -->
    <div v-if="can_review && pending_requests.length" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 space-y-3">
      <h3 class="text-sm font-semibold text-amber-900">待審核補貨請求</h3>
      <div v-for="r in pending_requests" :key="r.id" class="flex items-center justify-between bg-white rounded-lg px-4 py-3 border border-amber-100">
        <div class="text-sm">
          <p class="font-medium text-gray-900">{{ r.product.name }} ＋{{ r.quantity_change }}</p>
          <p class="text-xs text-gray-400">{{ r.user.name }} 提出 · {{ formatDate(r.created_at) }}{{ r.note ? ' · ' + r.note : '' }}</p>
        </div>
        <div class="flex gap-2">
          <button @click="approve(r)" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700">核准</button>
          <button @click="reject(r)" class="text-xs bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-300">駁回</button>
        </div>
      </div>
    </div>

    <!-- 商品庫存表 -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">商品</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">條碼</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">庫存數量</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in products" :key="p.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ p.name }}</td>
            <td class="px-4 py-3 text-gray-400 hidden md:table-cell">{{ p.barcode ?? '—' }}</td>
            <td class="px-4 py-3" :class="p.quantity <= 0 ? 'text-red-600 font-semibold' : 'text-gray-700'">{{ p.quantity }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <button v-if="can_adjust" @click="openAdjust(p)" class="text-primary-600 hover:underline text-xs">調整庫存</button>
                <button v-if="can_request" @click="openRequest(p)" class="text-amber-600 hover:underline text-xs">請求補貨</button>
              </div>
            </td>
          </tr>
          <tr v-if="!products.length">
            <td colspan="4" class="px-4 py-12 text-center text-gray-400">尚無商品</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 調整 / 請求 modal -->
    <div v-if="modal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" @click.self="modal = null">
      <div class="bg-white rounded-xl p-5 w-full max-w-sm space-y-4">
        <h3 class="text-sm font-semibold text-gray-900">
          {{ modal.mode === 'adjust' ? '調整庫存' : '請求補貨' }} － {{ modal.product.name }}
        </h3>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            {{ modal.mode === 'adjust' ? '異動數量（正數=入庫，負數=出庫）' : '請求補貨數量' }}
          </label>
          <input v-model="modal.quantity" type="number" :min="modal.mode === 'request' ? 1 : undefined" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">備註</label>
          <textarea v-model="modal.note" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>
        <div class="flex justify-end gap-2">
          <button @click="modal = null" class="text-sm px-3 py-1.5 rounded-lg text-gray-500 hover:bg-gray-100">取消</button>
          <button @click="submitModal" class="text-sm px-4 py-1.5 rounded-lg bg-primary-600 text-white hover:bg-primary-700">送出</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  stores:           Array,
  selected_store:   Object,
  products:         Array,
  pending_requests: Array,
  can_adjust:       Boolean,
  can_request:      Boolean,
  can_review:       Boolean,
})

const modal = ref(null)

function switchStore(storeId) {
  router.get('/admin/inventory', { store_id: storeId })
}

function openAdjust(product) {
  modal.value = { mode: 'adjust', product, quantity: '', note: '' }
}

function openRequest(product) {
  modal.value = { mode: 'request', product, quantity: '', note: '' }
}

function submitModal() {
  const { mode, product, quantity, note } = modal.value
  const url = mode === 'adjust' ? '/admin/inventory/adjust' : '/admin/inventory/request'

  router.post(url, {
    store_id: props.selected_store.id,
    product_id: product.id,
    quantity_change: quantity,
    note,
  }, {
    preserveScroll: true,
    onSuccess: () => { modal.value = null },
  })
}

function approve(r) {
  router.post(`/admin/inventory/logs/${r.id}/approve`, {}, { preserveScroll: true })
}

function reject(r) {
  router.post(`/admin/inventory/logs/${r.id}/reject`, {}, { preserveScroll: true })
}

function formatDate(d) {
  return new Date(d).toLocaleString('zh-TW')
}
</script>
