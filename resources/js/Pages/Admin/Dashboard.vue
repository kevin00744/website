<template>
  <AdminLayout title="總覽">
    <!-- Stats grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard label="文章總數" :value="stats.total_posts" color="blue" icon="📝" />
      <StatCard label="已發布" :value="stats.published_posts" color="green" icon="✅" />
      <StatCard label="草稿" :value="stats.draft_posts" color="yellow" icon="📋" />
      <StatCard label="分類" :value="stats.total_categories" color="purple" icon="📁" />
    </div>

    <!-- Recent posts -->
    <div class="bg-white rounded-xl border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">最近文章</h2>
        <Link href="/admin/posts" class="text-xs text-primary-600 hover:underline">查看全部 →</Link>
      </div>
      <ul class="divide-y divide-gray-100">
        <li
          v-for="post in recent_posts"
          :key="post.id"
          class="px-6 py-3 flex items-center justify-between gap-4"
        >
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ post.title }}</p>
            <p class="text-xs text-gray-400">{{ post.author?.name }} · {{ formatDate(post.created_at) }}</p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <StatusBadge :status="post.status" />
            <Link :href="`/admin/posts/${post.id}/edit`" class="text-xs text-primary-600 hover:underline">編輯</Link>
          </div>
        </li>
      </ul>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

defineProps({
  stats: Object,
  recent_posts: Array,
})

function formatDate(d) {
  return new Date(d).toLocaleDateString('zh-TW', { month: 'short', day: 'numeric' })
}
</script>
