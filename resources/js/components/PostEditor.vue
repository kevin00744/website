<template>
  <AdminLayout>
    <div class="max-w-5xl mx-auto py-8 px-4">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-medium">{{ post ? '編輯文章' : '新增文章' }}</h1>
        <div class="flex gap-2">
          <button @click="save('draft')" class="btn btn-secondary">儲存草稿</button>
          <button @click="save('published')" class="btn btn-primary">
            {{ post?.status === 'published' ? '更新' : '發布' }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-6">
        <!-- Main editor -->
        <div class="col-span-2 space-y-4">
          <input
            v-model="form.title"
            type="text"
            placeholder="文章標題"
            class="w-full text-2xl font-medium border-0 border-b pb-2 focus:outline-none focus:border-indigo-500"
          />
          <input
            v-model="form.slug"
            type="text"
            placeholder="slug (留空自動生成)"
            class="input input-sm text-gray-500"
          />
          <!-- TipTap Editor -->
          <div class="border rounded-lg overflow-hidden">
            <EditorMenuBar :editor="editor" />
            <editor-content :editor="editor" class="prose max-w-none p-4 min-h-64" />
          </div>
          <textarea
            v-model="form.excerpt"
            placeholder="文章摘要（選填）"
            rows="3"
            class="textarea w-full"
          />
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Status -->
          <div class="card">
            <h3 class="font-medium mb-2">狀態</h3>
            <select v-model="form.status" class="select w-full">
              <option value="draft">草稿</option>
              <option value="review">待審核</option>
              <option value="published">已發布</option>
              <option value="archived">已封存</option>
            </select>
            <input
              v-if="form.status === 'published'"
              v-model="form.published_at"
              type="datetime-local"
              class="input mt-2 w-full"
            />
          </div>

          <!-- Category -->
          <div class="card">
            <h3 class="font-medium mb-2">分類</h3>
            <select v-model="form.category_id" class="select w-full">
              <option :value="null">未分類</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <!-- Tags -->
          <div class="card">
            <h3 class="font-medium mb-2">標籤</h3>
            <div class="flex flex-wrap gap-1">
              <label
                v-for="tag in tags"
                :key="tag.id"
                class="flex items-center gap-1 text-sm cursor-pointer"
              >
                <input type="checkbox" :value="tag.id" v-model="form.tags" />
                {{ tag.name }}
              </label>
            </div>
          </div>

          <!-- Featured image -->
          <div class="card">
            <h3 class="font-medium mb-2">封面圖片</h3>
            <img
              v-if="featuredImageUrl"
              :src="featuredImageUrl"
              class="w-full rounded mb-2 object-cover h-32"
            />
            <button @click="openMediaPicker" class="btn btn-secondary btn-sm w-full">
              {{ featuredImageUrl ? '更換圖片' : '選擇圖片' }}
            </button>
          </div>

          <!-- SEO -->
          <div class="card">
            <h3 class="font-medium mb-2">SEO</h3>
            <input v-model="form.seo_title" placeholder="SEO 標題" class="input w-full mb-2" maxlength="60" />
            <textarea v-model="form.seo_description" placeholder="SEO 描述" class="textarea w-full" maxlength="160" rows="3" />
          </div>
        </div>
      </div>
    </div>

    <!-- Media picker modal -->
    <MediaPicker v-if="showMediaPicker" @select="onMediaSelect" @close="showMediaPicker = false" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import EditorMenuBar from '@/Components/EditorMenuBar.vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const props = defineProps({
  post: Object,
  categories: Array,
  tags: Array,
})

const showMediaPicker = ref(false)

const form = ref({
  title:          props.post?.title ?? '',
  slug:           props.post?.slug ?? '',
  excerpt:        props.post?.excerpt ?? '',
  content:        props.post?.content ?? '',
  content_json:   props.post?.content_json ?? null,
  status:         props.post?.status ?? 'draft',
  type:           props.post?.type ?? 'post',
  category_id:    props.post?.category_id ?? null,
  featured_image_id: props.post?.featured_image_id ?? null,
  tags:           props.post?.tags?.map(t => t.id) ?? [],
  is_featured:    props.post?.is_featured ?? false,
  seo_title:      props.post?.seo_title ?? '',
  seo_description: props.post?.seo_description ?? '',
  published_at:   props.post?.published_at ?? '',
})

const featuredImageUrl = computed(() => props.post?.featured_image?.url ?? null)

const editor = useEditor({
  content: form.value.content_json ?? form.value.content,
  extensions: [StarterKit, Image, Link],
  onUpdate({ editor }) {
    form.value.content      = editor.getHTML()
    form.value.content_json = editor.getJSON()
  },
})

onBeforeUnmount(() => editor.value?.destroy())

function openMediaPicker() {
  showMediaPicker.value = true
}

function onMediaSelect(media) {
  form.value.featured_image_id = media.id
  showMediaPicker.value = false
}

function save(status) {
  form.value.status = status
  if (props.post) {
    router.patch(route('admin.posts.update', props.post.id), form.value)
  } else {
    router.post(route('admin.posts.store'), form.value)
  }
}
</script>
