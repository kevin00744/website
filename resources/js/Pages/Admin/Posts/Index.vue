<template>
  <AdminLayout title="文章管理">
    <template #actions>
      <Link href="/admin/posts/create" class="bg-primary-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-primary-700 transition">
        + 新增文章
      </Link>
    </template>

    <!-- Filters -->
    <div class="flex gap-3 mb-4">
      <input
        v-model="search"
        type="search"
        placeholder="搜尋文章標題..."
        class="flex-1 max-w-sm rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        @input="filter"
      />
      <select v-model="status" @change="filter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
        <option value="">全部狀態</option>
        <option value="published">已發布</option>
        <option value="draft">草稿</option>
        <option value="review">審核中</option>
        <option value="archived">已封存</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">標題</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">作者</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">分類</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">狀態</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">建立時間</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900 truncate max-w-xs">{{ post.title }}</p>
              <p class="text-xs text-gray-400">{{ post.slug }}</p>
            </td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ post.author?.name }}</td>
            <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">{{ post.category?.name ?? '—' }}</td>
            <td class="px-4 py-3"><StatusBadge :status="post.status" /></td>
            <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">{{ formatDate(post.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Link :href="`/admin/posts/${post.id}/edit`" class="text-primary-600 hover:underline text-xs">編輯</Link>
                <button @click="deletePost(post)" class="text-red-500 hover:underline text-xs">刪除</button>
              </div>
            </td>
          </tr>
          <tr v-if="!posts.data.length">
            <td colspan="6" class="px-4 py-12 text-center text-gray-400">沒有符合的文章</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="posts.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">共 {{ posts.total }} 篇</p>
        <div class="flex gap-1">
          <Link
            v-for="link in posts.links"
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
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({ posts: Object, filters: Object })

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

let timer
function filter() {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/admin/posts', { search: search.value, status: status.value }, { preserveState: true, replace: true })
  }, 300)
}

function deletePost(post) {
  if (!confirm(`確定刪除「${post.title}」？`)) return
  router.delete(`/admin/posts/${post.id}`)
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('zh-TW')
}
</script>
