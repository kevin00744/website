<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Sidebar -->
    <aside class="w-60 flex-shrink-0 bg-gray-900 flex flex-col">
      <div class="px-6 py-5 border-b border-gray-800">
        <span class="text-white font-bold text-lg">⚡ CMS</span>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <NavItem :href="route('admin.dashboard')" icon="🏠">總覽</NavItem>
        <NavItem :href="route('admin.posts.index')" icon="📝">文章管理</NavItem>
        <NavItem :href="route('admin.posts.create')" icon="✏️">新增文章</NavItem>
        <NavItem :href="route('admin.contacts.index')" icon="📨">聯絡訊息</NavItem>
        <NavItem :href="route('admin.customers.index')" icon="🧾">顧客資料</NavItem>
        <NavItem v-if="isAdmin || user.store_id" :href="route('admin.inventory.index')" icon="📦">庫存管理</NavItem>
        <NavItem v-if="isAdmin" :href="route('admin.products.index')" icon="🏷️">商品目錄</NavItem>
        <NavItem v-if="isAdmin" :href="route('admin.stores.index')" icon="🏬">分店管理</NavItem>
        <NavItem :href="route('admin.users.index')" icon="👤">帳號管理</NavItem>
      </nav>

      <div class="px-4 py-4 border-t border-gray-800">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-bold">
            {{ user.name?.charAt(0) }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
          </div>
        </div>
        <Link
          href="/logout"
          method="post"
          as="button"
          class="w-full text-left text-xs text-gray-400 hover:text-white transition"
        >登出</Link>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">{{ title }}</h1>
        <slot name="actions" />
      </header>

      <!-- Flash message -->
      <div v-if="flash.success" class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        ✅ {{ flash.success }}
      </div>

      <main class="flex-1 p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps({ title: { type: String, default: '' } })

const page = usePage()
const user  = computed(() => page.props.auth?.user ?? {})
const flash = computed(() => page.props.flash ?? {})
const isAdmin = computed(() => user.value.role === 'admin')

function route(name) {
  const map = {
    'admin.dashboard':       '/admin',
    'admin.posts.index':     '/admin/posts',
    'admin.posts.create':    '/admin/posts/create',
    'admin.contacts.index':  '/admin/contacts',
    'admin.customers.index': '/admin/customers',
    'admin.inventory.index': '/admin/inventory',
    'admin.products.index':  '/admin/products',
    'admin.stores.index':    '/admin/stores',
    'admin.users.index':     '/admin/users',
  }
  return map[name] ?? '/'
}
</script>

<script>
import NavItem from '@/Components/NavItem.vue'
export default { components: { NavItem } }
</script>
