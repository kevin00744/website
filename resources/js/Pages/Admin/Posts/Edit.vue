<template>
  <AdminLayout :title="post ? '編輯文章' : '新增文章'">
    <template #actions>
      <Link href="/admin/posts" class="text-sm text-gray-500 hover:text-gray-700">← 返回列表</Link>
    </template>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- 標題 -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">標題 <span class="text-red-500">*</span></label>
        <input
          v-model="form.title"
          type="text"
          required
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          placeholder="文章標題"
        />
        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
      </div>

      <!-- 內容編輯器（全寬、放大） -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <label class="block text-sm font-medium text-gray-700 mb-2">內容 <span class="text-red-500">*</span></label>
        <GrapesEditor ref="grapesEditor" :model-value="form.content" :css="pageCss" height="calc(100vh - 220px)" />
        <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main -->
        <div class="lg:col-span-2 space-y-5">
          <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">摘要</label>
              <textarea
                v-model="form.excerpt"
                rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                placeholder="文章摘要（選填）"
              />
            </div>
          </div>

          <!-- SEO -->
          <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">SEO 設定</h3>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Meta 標題</label>
              <input v-model="form.seo_title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Meta 描述</label>
              <textarea v-model="form.seo_description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Publish box -->
          <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">發布設定</h3>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">網址代稱 (slug)</label>
              <input v-model="form.slug" type="text" placeholder="留空自動依標題生成" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
              <p class="mt-1 text-xs text-gray-400">頁面網址會是 /p/{{ form.slug || 'slug' }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">類型</label>
              <select v-model="form.type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="post">文章</option>
                <option value="page">頁面（網站固定頁面）</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">狀態</label>
              <select v-model="form.status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="draft">草稿</option>
                <option value="review">審核中</option>
                <option value="published">已發布</option>
                <option value="archived">已封存</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">發布時間</label>
              <input v-model="form.published_at" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            </div>
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full bg-primary-600 text-white rounded-lg py-2 text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 transition"
            >
              {{ form.processing ? '儲存中...' : (post ? '更新文章' : '建立文章') }}
            </button>
          </div>

          <!-- Nav (只有類型=頁面 時顯示) -->
          <div v-if="form.type === 'page'" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-900">導覽列</h3>
            <p class="text-xs text-gray-400">填了「導覽順序」就會自動出現在網站上方選單；留空則不顯示。</p>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">導覽順序</label>
              <input v-model="form.nav_order" type="number" placeholder="例如 6（留空 = 不顯示）" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">選單顯示文字</label>
              <input v-model="form.nav_label" type="text" placeholder="留空則使用標題" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" />
            </div>
          </div>

          <!-- Category -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">分類</h3>
            <select v-model="form.category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
              <option value="">未分類</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>

          <!-- Tags -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">標籤</h3>
            <div class="space-y-1.5">
              <label v-for="tag in tags" :key="tag.id" class="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  :value="tag.id"
                  v-model="form.tags"
                  class="rounded border-gray-300 text-primary-600"
                />
                <span class="text-sm text-gray-700">{{ tag.name }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import GrapesEditor from '@/Components/GrapesEditor.vue'

const props = defineProps({
  post:       Object,
  categories: Array,
  tags:       Array,
  css:        { type: Array, default: () => [] },
})

const grapesEditor = ref(null)
const pageCss = props.css

const form = useForm({
  title:           props.post?.title ?? '',
  slug:            props.post?.slug ?? '',
  type:            props.post?.type ?? 'post',
  excerpt:         props.post?.excerpt ?? '',
  content:         props.post?.content ?? '',
  status:          props.post?.status ?? 'draft',
  category_id:     props.post?.category_id ?? '',
  tags:            props.post?.tags?.map(t => t.id) ?? [],
  seo_title:       props.post?.seo_title ?? '',
  seo_description: props.post?.seo_description ?? '',
  published_at:    props.post?.published_at ? props.post.published_at.slice(0, 16) : '',
  nav_order:       props.post?.nav_order ?? '',
  nav_label:       props.post?.nav_label ?? '',
})

function submit() {
  form.content = grapesEditor.value?.getHtml() ?? form.content

  if (props.post) {
    form.put(`/admin/posts/${props.post.id}`)
  } else {
    form.post('/admin/posts')
  }
}
</script>
