<template>
  <Link
    :href="href"
    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition"
    :class="isActive
      ? 'bg-primary-600 text-white'
      : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
  >
    <span>{{ icon }}</span>
    <slot />
  </Link>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  href: String,
  icon: String,
})

const page = usePage()
const isActive = computed(() => page.url.startsWith(props.href) && props.href !== '/admin' || page.url === props.href)
</script>
