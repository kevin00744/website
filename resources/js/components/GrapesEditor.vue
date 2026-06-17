<template>
  <div class="grapes-wrap">
    <div ref="container" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import grapesjs from 'grapesjs'
import blocksBasic from 'grapesjs-blocks-basic'
import 'grapesjs/dist/css/grapes.min.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  css: { type: Array, default: () => [] },
  height: { type: String, default: '75vh' },
})

const container = ref(null)
let editor = null

onMounted(() => {
  editor = grapesjs.init({
    container: container.value,
    height: props.height,
    fromElement: false,
    storageManager: false,
    plugins: [blocksBasic],
    pluginsOpts: {
      [blocksBasic]: { blocks: ['text', 'image', 'link', 'video', 'map'] },
    },
    canvas: {
      styles: props.css,
    },
    components: props.modelValue || '<p>開始拖拉左側元件來編輯內容…</p>',
  })
})

onBeforeUnmount(() => {
  editor?.destroy()
  editor = null
})

defineExpose({
  getHtml: () => editor?.getHtml() ?? '',
})
</script>

<style scoped>
.grapes-wrap {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
}
</style>
