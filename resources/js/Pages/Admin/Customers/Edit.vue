<template>
  <AdminLayout :title="customer ? '編輯顧客' : '新增顧客'">
    <template #actions>
      <Link href="/admin/customers" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</Link>
    </template>

    <div class="max-w-xl space-y-6">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">姓名 <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">電話 <span class="text-red-500">*</span></label>
            <input v-model="form.phone" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">所屬分店</label>
            <select v-model="form.store_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
              <option value="">未指定</option>
              <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Line</label>
            <input v-model="form.line" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">地址</label>
            <input v-model="form.address" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">備註</label>
            <textarea v-model="form.note" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="bg-primary-600 text-white rounded-lg px-5 py-2 text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition"
        >
          {{ form.processing ? '儲存中...' : (customer ? '更新顧客資料' : '建立顧客資料') }}
        </button>
      </form>

      <!-- 商品使用紀錄（僅編輯既有顧客時顯示） -->
      <div v-if="customer" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <h3 class="text-sm font-semibold text-gray-900">商品使用紀錄</h3>

        <div v-if="can_record_usage" class="flex gap-2">
          <select v-model="usageForm.product_id" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="" disabled>選擇商品</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <input v-model="usageForm.quantity" type="number" min="1" placeholder="數量" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <button @click="addUsage" class="text-sm px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700">新增</button>
        </div>
        <p v-else-if="!customer.store_id" class="text-xs text-amber-600">請先設定顧客所屬分店才能記錄使用紀錄。</p>

        <ul class="divide-y divide-gray-100">
          <li v-for="u in usages" :key="u.id" class="py-2 text-sm flex items-center justify-between">
            <div>
              <p class="text-gray-900">{{ u.product.name }} × {{ Math.abs(u.quantity_change) }}</p>
              <p class="text-xs text-gray-400">{{ u.user.name }} · {{ formatDate(u.created_at) }}</p>
            </div>
          </li>
          <li v-if="!usages.length" class="py-6 text-center text-gray-400 text-sm">尚無使用紀錄</li>
        </ul>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  customer:         Object,
  stores:           { type: Array, default: () => [] },
  usages:           { type: Array, default: () => [] },
  products:         { type: Array, default: () => [] },
  can_record_usage: Boolean,
})

const form = useForm({
  name:     props.customer?.name ?? '',
  phone:    props.customer?.phone ?? '',
  store_id: props.customer?.store_id ?? '',
  line:     props.customer?.line ?? '',
  email:    props.customer?.email ?? '',
  address:  props.customer?.address ?? '',
  note:     props.customer?.note ?? '',
})

function submit() {
  if (props.customer) {
    form.put(`/admin/customers/${props.customer.id}`)
  } else {
    form.post('/admin/customers')
  }
}

const usageForm = ref({ product_id: '', quantity: 1 })

function addUsage() {
  if (!usageForm.value.product_id || !usageForm.value.quantity) return
  router.post(`/admin/customers/${props.customer.id}/usages`, usageForm.value, {
    preserveScroll: true,
    onSuccess: () => { usageForm.value = { product_id: '', quantity: 1 } },
  })
}

function formatDate(d) {
  return new Date(d).toLocaleString('zh-TW')
}
</script>
