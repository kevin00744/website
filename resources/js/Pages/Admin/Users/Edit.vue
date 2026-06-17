<template>
  <AdminLayout :title="user ? '編輯帳號' : '新增帳號'">
    <template #actions>
      <Link href="/admin/users" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</Link>
    </template>

    <form @submit.prevent="submit" class="max-w-xl space-y-6">
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">名稱 <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
          <input v-model="form.email" type="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">電話</label>
          <input v-model="form.phone" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">角色</label>
          <select
            v-if="assignable_roles.length > 1"
            v-model="form.role"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option v-for="r in assignable_roles" :key="r.value" :value="r.value">{{ r.label }}</option>
          </select>
          <p v-else class="text-sm text-gray-500 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
            {{ roleLabel(form.role) }}
            <span class="text-xs text-gray-400">（無權限變更角色）</span>
          </p>
        </div>

        <div v-if="requiresStore">
          <label class="block text-sm font-medium text-gray-700 mb-1">分店 <span class="text-red-500">*</span></label>
          <select
            v-if="stores.length"
            v-model="form.store_id"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="" disabled>請選擇分店</option>
            <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <p v-else class="text-sm text-gray-500 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
            {{ locked_store?.name }}
          </p>
          <p v-if="form.errors.store_id" class="mt-1 text-xs text-red-600">{{ form.errors.store_id }}</p>
        </div>

        <div class="flex items-center gap-2">
          <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-primary-600" />
          <label for="is_active" class="text-sm text-gray-700">啟用此帳號</label>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <h3 class="text-sm font-semibold text-gray-900">{{ user ? '變更密碼' : '設定密碼' }}</h3>
        <p v-if="user" class="text-xs text-gray-400">留空表示不變更密碼。</p>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ user ? '新密碼' : '密碼' }} <span v-if="!user" class="text-red-500">*</span></label>
          <input v-model="form.password" type="password" :required="!user" minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">確認密碼</label>
          <input v-model="form.password_confirmation" type="password" minlength="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
        </div>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="bg-primary-600 text-white rounded-lg px-5 py-2 text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition"
      >
        {{ form.processing ? '儲存中...' : (user ? '更新帳號' : '建立帳號') }}
      </button>
    </form>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  user:             Object,
  assignable_roles: { type: Array, default: () => [] },
  stores:           { type: Array, default: () => [] },
  locked_store:     { type: Object, default: null },
})

const roleLabels = { admin: '管理員', editor: '編輯', manager: '店長', staff: '店員' }
function roleLabel(role) {
  return roleLabels[role] ?? role
}

const ROLES_REQUIRING_STORE = ['manager', 'staff']
const requiresStore = computed(() => ROLES_REQUIRING_STORE.includes(form.role))

const form = useForm({
  name:                  props.user?.name ?? '',
  email:                 props.user?.email ?? '',
  phone:                 props.user?.phone ?? '',
  role:                  props.user?.role ?? props.assignable_roles[0]?.value ?? 'staff',
  store_id:              props.user?.store_id ?? props.locked_store?.id ?? '',
  is_active:             props.user?.is_active ?? true,
  password:              '',
  password_confirmation: '',
})

function submit() {
  if (props.user) {
    form.put(`/admin/users/${props.user.id}`)
  } else {
    form.post('/admin/users')
  }
}
</script>
