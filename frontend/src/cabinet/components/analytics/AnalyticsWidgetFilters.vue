<template>
  <div class="flex flex-wrap items-end gap-2">
    <div>
      <label class="mb-0.5 block text-[10px] uppercase tracking-wide text-gray-400">с</label>
      <input v-model="localDateFrom" type="date" :class="compactInputClass" />
    </div>
    <div>
      <label class="mb-0.5 block text-[10px] uppercase tracking-wide text-gray-400">по</label>
      <input v-model="localDateTo" type="date" :class="compactInputClass" />
    </div>
    <div v-if="showGroupBy">
      <label class="mb-0.5 block text-[10px] uppercase tracking-wide text-gray-400">групп.</label>
      <select v-model="localGroupBy" :class="compactSelectClass">
        <option value="month">мес.</option>
        <option value="day">день</option>
      </select>
    </div>
    <button
      type="button"
      :class="compactButtonClass"
      :disabled="loading"
      @click="apply"
    >
      OK
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { AnalyticsQuery } from '@cabinet/composables/useProjectAnalytics'

const props = defineProps<{
  query: AnalyticsQuery
  showGroupBy?: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  apply: [query: AnalyticsQuery]
}>()

const compactInputClass =
  'h-8 rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200'
const compactSelectClass =
  'h-8 rounded-lg border border-gray-200 bg-white px-2 pr-7 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200'
const compactButtonClass =
  'h-8 rounded-lg bg-brand-500 px-3 text-xs font-medium text-white hover:bg-brand-600 disabled:opacity-50'

const localDateFrom = ref(props.query.dateFrom)
const localDateTo = ref(props.query.dateTo)
const localGroupBy = ref(props.query.groupBy)

watch(
  () => props.query,
  (query) => {
    localDateFrom.value = query.dateFrom
    localDateTo.value = query.dateTo
    localGroupBy.value = query.groupBy
  },
  { deep: true },
)

function apply() {
  emit('apply', {
    dateFrom: localDateFrom.value,
    dateTo: localDateTo.value,
    groupBy: localGroupBy.value,
  })
}
</script>
