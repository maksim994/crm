<template>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800">
        <tr>
          <th class="p-3 text-left">#</th>
          <th class="p-3 text-left">{{ labelColumn }}</th>
          <th class="p-3 text-right">Визиты</th>
          <th v-if="showMetrics" class="p-3 text-right">Отказы</th>
          <th v-if="showMetrics" class="p-3 text-right">Глубина</th>
          <th v-if="showMetrics" class="p-3 text-right">Время</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, index) in rows"
          :key="row.label"
          class="border-b border-gray-100 dark:border-gray-800"
        >
          <td class="p-3 text-gray-500">{{ index + 1 }}</td>
          <td class="p-3">{{ row.label }}</td>
          <td class="p-3 text-right">{{ row.visits }}</td>
          <td v-if="showMetrics" class="p-3 text-right">{{ row.bounce_rate ?? '—' }}</td>
          <td v-if="showMetrics" class="p-3 text-right">{{ row.page_depth ?? '—' }}</td>
          <td v-if="showMetrics" class="p-3 text-right">
            {{ row.avg_duration_sec != null ? formatDuration(row.avg_duration_sec) : '—' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { formatDuration, type AnalyticsTableRow } from '@cabinet/composables/useProjectAnalytics'

const props = defineProps<{
  rows: AnalyticsTableRow[]
  labelColumn?: string
}>()

const labelColumn = computed(() => props.labelColumn ?? 'Источник')
const showMetrics = computed(() =>
  props.rows.some((row) => row.bounce_rate != null || row.page_depth != null),
)
</script>
