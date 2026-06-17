<template>
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="cls">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: String })

const map = {
  published: { cls: 'bg-green-100 text-green-700',  label: '已發布' },
  draft:     { cls: 'bg-gray-100 text-gray-600',    label: '草稿'   },
  review:    { cls: 'bg-yellow-100 text-yellow-700', label: '審核中' },
  archived:  { cls: 'bg-red-100 text-red-700',      label: '已封存' },
}

const cls   = computed(() => map[props.status]?.cls   ?? 'bg-gray-100 text-gray-600')
const label = computed(() => map[props.status]?.label ?? props.status)
</script>
