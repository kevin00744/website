<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900">CMS 管理後台</h1>
        <p class="mt-1 text-sm text-gray-500">請輸入帳號密碼登入</p>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">電子郵件</label>
          <input
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            placeholder="admin@example.com"
          />
          <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">密碼</label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            placeholder="••••••••"
          />
        </div>

        <div class="flex items-center">
          <input v-model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-primary-600" />
          <label for="remember" class="ml-2 text-sm text-gray-600">記住我</label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-primary-600 text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition"
        >
          {{ form.processing ? '登入中...' : '登入' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post('/login', { onFinish: () => form.reset('password') })
}
</script>
