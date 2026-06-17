<template>
  <div>
    <Head>
      <link v-for="href in css" :key="href" rel="stylesheet" :href="href" />
    </Head>

    <!-- 導覽列 -->
    <nav class="site-nav">
      <div class="site-nav-inner">
        <Link href="/" class="site-logo">
          <img src="/wp-content/uploads/2025/12/%E7%92%A8%E5%A6%8D-LOGO-2-e1776403013718.png" alt="璨妍 TSANYEN" />
        </Link>
        <ul class="site-nav-links">
          <li v-for="item in nav" :key="item.slug">
            <Link :href="item.slug === 'home' ? '/' : `/p/${item.slug}`">{{ item.title }}</Link>
          </li>
        </ul>
        <Link v-if="contact" :href="`/p/${contact.slug}`" class="site-nav-cta">{{ contact.title }}</Link>
      </div>
    </nav>

    <!-- Elementor 頁面內容 -->
    <div ref="contentEl" class="elementor-page-wrap" v-html="post?.content" />

    <!-- 頁尾 -->
    <footer class="site-footer">
      <p>© 2025 璨妍 TSANYEN. All rights reserved.</p>
    </footer>
  </div>
</template>

<script setup>
import { ref, nextTick, watch, onMounted } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  post:    Object,
  nav:     Array,
  contact: Object,
  css:     Array,
})

const contentEl = ref(null)

// post.content 是後台用 Elementor/GrapesJS 編輯出來的原始 HTML，用 v-html 直接塞進畫面。
// 聯絡表單沒有對應的 WordPress 後端了，這裡攔截送出動作改打 /contact，並避免整頁刷新。
function wireContactForm() {
  const form = contentEl.value?.querySelector('form.elementor-form')
  if (!form || form.dataset.wired) return
  form.dataset.wired = '1'

  form.addEventListener('submit', async (e) => {
    e.preventDefault()

    const fd = new FormData(form)
    const payload = {
      name:     fd.get('form_fields[name]') ?? '',
      email:    fd.get('form_fields[email]') ?? '',
      message:  fd.get('form_fields[message]') ?? '',
      interest: fd.get('form_fields[field_f4c49cf]') ?? '',
      note:     fd.get('form_fields[field_c141326]') ?? '',
    }

    const button = form.querySelector('button[type="submit"]')
    button?.setAttribute('disabled', 'disabled')

    try {
      await axios.post('/contact', payload)
      form.innerHTML = '<p class="contact-success">感謝您的訊息，我們會盡快與您聯繫。</p>'
    } catch (err) {
      alert('送出失敗，請稍後再試。')
      button?.removeAttribute('disabled')
    }
  })
}

onMounted(() => nextTick(wireContactForm))
watch(() => props.post?.id, () => nextTick(wireContactForm))
</script>

<style>
/* 導覽列 */
.site-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid rgba(0,0,0,0.08);
  padding: 0 24px;
}

.site-nav-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  gap: 24px;
}

.site-logo img {
  height: 40px;
  width: auto;
}

.site-nav-links {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 32px;
  flex: 1;
}

.site-nav-links a {
  text-decoration: none;
  color: #333;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.2s;
}

.site-nav-links a:hover {
  color: #8b6f6f;
}

.site-nav-cta {
  flex-shrink: 0;
  background: #8b6f6f;
  color: #fff;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 18px;
  border-radius: 999px;
  transition: background 0.2s;
}

.site-nav-cta:hover {
  background: #735a5a;
}

/* Elementor 內容區 */
.elementor-page-wrap {
  padding-top: 64px;
}

/* 修正圖片路徑 — Elementor 有時用背景圖 */
.elementor-page-wrap .elementor-section,
.elementor-page-wrap .e-con {
  position: relative;
}

.contact-success {
  padding: 24px;
  text-align: center;
  color: #2f6f4f;
  font-size: 15px;
  font-weight: 500;
}

/* 頁尾 */
.site-footer {
  background: #1a1a1a;
  color: #999;
  text-align: center;
  padding: 24px;
  font-size: 13px;
}
</style>
