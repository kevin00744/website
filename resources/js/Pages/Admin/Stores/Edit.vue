<template>
  <AdminLayout :title="store ? '編輯分店' : '新增分店'">
    <template #actions>
      <Link href="/admin/stores" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</Link>
    </template>

    <form @submit.prevent="submit" class="max-w-xl space-y-6">
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">名稱 <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">電話</label>
          <input v-model="form.phone" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">地址</label>
          <input v-model="form.address" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">備註</label>
          <textarea v-model="form.note" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>

        <div class="flex items-center gap-2">
          <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-primary-600" />
          <label for="is_active" class="text-sm text-gray-700">營運中</label>
        </div>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="bg-primary-600 text-white rounded-lg px-5 py-2 text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition"
      >
        {{ form.processing ? '儲存中...' : (store ? '更新分店' : '建立分店') }}
      </button>
    </form>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({ store: Object })

const form = useForm({
  name:      props.store?.name ?? '',
  phone:     props.store?.phone ?? '',
  address:   props.store?.address ?? '',
  note:      props.store?.note ?? '',
  is_active: props.store?.is_active ?? true,
})

function submit() {
  if (props.store) {
    form.put(`/admin/stores/${props.store.id}`)
  } else {
    form.post('/admin/stores')
  }
}
</script>
