<template>
  <AdminLayout :title="product ? '編輯商品' : '新增商品'">
    <template #actions>
      <Link href="/admin/products" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</Link>
    </template>

    <form @submit.prevent="submit" class="max-w-xl space-y-6">
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">名稱 <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">條碼</label>
          <input v-model="form.barcode" type="text" placeholder="選填，供未來 App 掃條碼快速選用" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <p v-if="form.errors.barcode" class="mt-1 text-xs text-red-600">{{ form.errors.barcode }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">價格</label>
          <input v-model="form.price" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">說明</label>
          <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>

        <div class="flex items-center gap-2">
          <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-primary-600" />
          <label for="is_active" class="text-sm text-gray-700">上架中</label>
        </div>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="bg-primary-600 text-white rounded-lg px-5 py-2 text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition"
      >
        {{ form.processing ? '儲存中...' : (product ? '更新商品' : '建立商品') }}
      </button>
    </form>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ product: Object })

const form = useForm({
  name:        props.product?.name ?? '',
  barcode:     props.product?.barcode ?? '',
  price:       props.product?.price ?? '',
  description: props.product?.description ?? '',
  is_active:   props.product?.is_active ?? true,
})

function submit() {
  if (props.product) {
    form.put(`/admin/products/${props.product.id}`)
  } else {
    form.post('/admin/products')
  }
}
</script>
