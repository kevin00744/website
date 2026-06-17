<template>
  <AdminLayout title="商品目錄">
    <template #actions>
      <Link v-if="can_manage" href="/admin/products/create" class="bg-primary-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-primary-700 transition">
        + 新增商品
      </Link>
    </template>

    <div class="flex gap-3 mb-4">
      <input
        v-model="search"
        type="search"
        placeholder="搜尋商品名稱或條碼..."
        class="flex-1 max-w-sm rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        @input="filter"
      />
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">名稱</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">條碼</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">價格</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">狀態</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ p.name }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ p.barcode ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">{{ p.price ?? '—' }}</td>
            <td class="px-4 py-3">
              <span :class="p.is_active ? 'text-green-600' : 'text-gray-400'">{{ p.is_active ? '上架' : '下架' }}</span>
            </td>
            <td class="px-4 py-3">
              <div v-if="can_manage" class="flex items-center gap-3">
                <Link :href="`/admin/products/${p.id}/edit`" class="text-primary-600 hover:underline text-xs">編輯</Link>
                <button @click="destroy(p)" class="text-red-500 hover:underline text-xs">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!products.data.length">
            <td colspan="5" class="px-4 py-12 text-center text-gray-400">尚無商品</td>
          </tr>
        </tbody>
      </table>

      <div v-if="products.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">共 {{ products.total }} 筆</p>
        <div class="flex gap-1">
          <Link
            v-for="link in products.links"
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

const props = defineProps({ products: Object, filters: Object, can_manage: Boolean })

const search = ref(props.filters?.search ?? '')

let timer
function filter() {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/admin/products', { search: search.value }, { preserveState: true, replace: true })
  }, 300)
}

function destroy(p) {
  if (!confirm(`確定刪除「${p.name}」這個商品？`)) return
  router.delete(`/admin/products/${p.id}`)
}
</script>
