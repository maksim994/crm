<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
  >
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
      <h3 class="text-base font-semibold text-gray-800 dark:text-white">{{ title }}</h3>
      <div class="flex flex-wrap items-center gap-2">
        <slot name="controls" />
        <button
          type="button"
          class="rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
          :disabled="loading"
          @click="$emit('refresh')"
        >
          Обновить
        </button>
      </div>
    </div>

    <div class="p-4">
      <div v-if="loading" class="py-10 text-center text-sm text-gray-500">Загрузка…</div>
      <div v-else-if="error" class="py-10 text-center text-sm text-red-500">{{ error }}</div>
      <div v-else-if="empty" class="py-10 text-center text-sm text-gray-500">Нет данных за период</div>
      <slot v-else />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  loading?: boolean
  error?: string | null
  empty?: boolean
}>()

defineEmits<{ refresh: [] }>()
</script>
