<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-3">
      <span class="text-xl">{{ icon }}</span>
      <span class="text-2xl font-bold" :class="textColor">{{ value }}</span>
    </div>
    <p class="text-xs text-gray-500 font-medium">{{ label }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  value: [Number, String],
  icon: String,
  color: { type: String, default: 'blue' },
})

const textColor = computed(() => ({
  blue:   'text-blue-600',
  green:  'text-green-600',
  yellow: 'text-yellow-600',
  purple: 'text-purple-600',
  red:    'text-red-600',
}[props.color] ?? 'text-gray-900'))
</script>
