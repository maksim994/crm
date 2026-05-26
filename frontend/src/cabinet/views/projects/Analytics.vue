<template>
  <cabinet-layout>
    <div class="mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Аналитика</h2>
      <p v-if="siteName" class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ siteName }}</p>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Период и группировка настраиваются отдельно для каждого блока.
      </p>
    </div>

    <div
      v-if="unavailableMessage"
      class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200"
    >
      {{ unavailableMessage }}
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <analytics-widget
        title="Переходы из поисковых систем"
        :loading="widgets.searchEngines.loading"
        :error="widgets.searchEngines.error"
        :empty="isAnalyticsEmpty(widgets.searchEngines.data)"
        @refresh="reload('searchEngines', true)"
      >
        <template #controls>
          <analytics-widget-filters
            :query="widgets.searchEngines.query"
            show-group-by
            :loading="widgets.searchEngines.loading"
            @apply="applyQuery('searchEngines', $event)"
          />
        </template>
        <analytics-area-chart
          v-if="widgets.searchEngines.data?.timeseries?.series?.length"
          :categories="widgets.searchEngines.data.timeseries.categories"
          :series="widgets.searchEngines.data.timeseries.series"
        />
        <analytics-donut-chart
          v-if="widgets.searchEngines.data?.summary?.values?.length"
          :class="{ 'mt-4': widgets.searchEngines.data?.timeseries?.series?.length }"
          :labels="widgets.searchEngines.data.summary.labels"
          :values="widgets.searchEngines.data.summary.values"
        />
      </analytics-widget>

      <analytics-widget
        title="Распределение трафика по каналам"
        :loading="widgets.trafficSources.loading"
        :error="widgets.trafficSources.error"
        :empty="isAnalyticsEmpty(widgets.trafficSources.data)"
        @refresh="reload('trafficSources', true)"
      >
        <template #controls>
          <analytics-widget-filters
            :query="widgets.trafficSources.query"
            :loading="widgets.trafficSources.loading"
            @apply="applyQuery('trafficSources', $event)"
          />
        </template>
        <analytics-donut-chart
          v-if="widgets.trafficSources.data?.summary?.values?.length"
          :labels="widgets.trafficSources.data.summary.labels"
          :values="widgets.trafficSources.data.summary.values"
        />
        <analytics-breakdown-table
          v-if="widgets.trafficSources.data?.table?.length"
          class="mt-4"
          :rows="widgets.trafficSources.data.table"
        />
      </analytics-widget>

      <analytics-widget
        title="Поисковый брендированный трафик"
        :loading="widgets.searchBranded.loading"
        :error="widgets.searchBranded.error"
        :empty="isAnalyticsEmpty(widgets.searchBranded.data)"
        @refresh="reload('searchBranded', true)"
      >
        <template #controls>
          <analytics-widget-filters
            :query="widgets.searchBranded.query"
            show-group-by
            :loading="widgets.searchBranded.loading"
            @apply="applyQuery('searchBranded', $event)"
          />
        </template>
        <analytics-area-chart
          v-if="widgets.searchBranded.data?.timeseries?.series?.length"
          :categories="widgets.searchBranded.data.timeseries.categories"
          :series="widgets.searchBranded.data.timeseries.series"
        />
        <analytics-donut-chart
          v-if="widgets.searchBranded.data?.summary?.values?.length"
          :class="{ 'mt-4': widgets.searchBranded.data?.timeseries?.series?.length }"
          :labels="widgets.searchBranded.data.summary.labels"
          :values="widgets.searchBranded.data.summary.values"
        />
      </analytics-widget>

      <analytics-widget
        title="Поисковый небрендированный трафик"
        :loading="widgets.searchNonBranded.loading"
        :error="widgets.searchNonBranded.error"
        :empty="isAnalyticsEmpty(widgets.searchNonBranded.data)"
        @refresh="reload('searchNonBranded', true)"
      >
        <template #controls>
          <analytics-widget-filters
            :query="widgets.searchNonBranded.query"
            show-group-by
            :loading="widgets.searchNonBranded.loading"
            @apply="applyQuery('searchNonBranded', $event)"
          />
        </template>
        <analytics-area-chart
          v-if="widgets.searchNonBranded.data?.timeseries?.series?.length"
          :categories="widgets.searchNonBranded.data.timeseries.categories"
          :series="widgets.searchNonBranded.data.timeseries.series"
        />
        <analytics-donut-chart
          v-if="widgets.searchNonBranded.data?.summary?.values?.length"
          :class="{ 'mt-4': widgets.searchNonBranded.data?.timeseries?.series?.length }"
          :labels="widgets.searchNonBranded.data.summary.labels"
          :values="widgets.searchNonBranded.data.summary.values"
        />
      </analytics-widget>

      <analytics-widget
        title="География посетителей"
        :loading="widgets.geography.loading"
        :error="widgets.geography.error"
        :empty="isAnalyticsEmpty(widgets.geography.data)"
        @refresh="reload('geography', true)"
      >
        <template #controls>
          <analytics-widget-filters
            :query="widgets.geography.query"
            :loading="widgets.geography.loading"
            @apply="applyQuery('geography', $event)"
          />
        </template>
        <analytics-donut-chart
          v-if="widgets.geography.data?.summary?.values?.length"
          :labels="widgets.geography.data.summary.labels"
          :values="widgets.geography.data.summary.values"
        />
      </analytics-widget>

      <analytics-widget
        title="Тип устройств"
        :loading="widgets.devices.loading"
        :error="widgets.devices.error"
        :empty="isAnalyticsEmpty(widgets.devices.data)"
        @refresh="reload('devices', true)"
      >
        <template #controls>
          <analytics-widget-filters
            :query="widgets.devices.query"
            :loading="widgets.devices.loading"
            @apply="applyQuery('devices', $event)"
          />
        </template>
        <analytics-donut-chart
          v-if="widgets.devices.data?.summary?.values?.length"
          :labels="widgets.devices.data.summary.labels"
          :values="widgets.devices.data.summary.values"
        />
        <analytics-breakdown-table
          v-if="widgets.devices.data?.table?.length"
          class="mt-4"
          :rows="widgets.devices.data.table"
          label-column="Тип устройства"
        />
      </analytics-widget>
    </div>
  </cabinet-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import AnalyticsWidget from '@cabinet/components/analytics/AnalyticsWidget.vue'
import AnalyticsWidgetFilters from '@cabinet/components/analytics/AnalyticsWidgetFilters.vue'
import AnalyticsDonutChart from '@cabinet/components/analytics/AnalyticsDonutChart.vue'
import AnalyticsAreaChart from '@cabinet/components/analytics/AnalyticsAreaChart.vue'
import AnalyticsBreakdownTable from '@cabinet/components/analytics/AnalyticsBreakdownTable.vue'
import { loadCabinetSites } from '@cabinet/composables/useCabinetSites'
import {
  createAnalyticsQuery,
  createYearAnalyticsQuery,
  fetchAnalyticsReport,
  isAnalyticsEmpty,
  type AnalyticsQuery,
  type AnalyticsReportData,
  type AnalyticsReportType,
} from '@cabinet/composables/useProjectAnalytics'
import { ApiError } from '@cabinet/api/client'

type WidgetKey =
  | 'trafficSources'
  | 'searchEngines'
  | 'searchBranded'
  | 'searchNonBranded'
  | 'geography'
  | 'devices'

interface WidgetState {
  loading: boolean
  error: string | null
  data: AnalyticsReportData | null
  query: AnalyticsQuery
}

const reportMap: Record<WidgetKey, AnalyticsReportType> = {
  trafficSources: 'traffic-sources',
  searchEngines: 'search-engines',
  searchBranded: 'search-branded',
  searchNonBranded: 'search-non-branded',
  geography: 'geography',
  devices: 'devices',
}

function createWidgetState(query: AnalyticsQuery): WidgetState {
  return {
    loading: false,
    error: null,
    data: null,
    query,
  }
}

const route = useRoute()
const siteId = computed(() => String(route.params.siteId))
const siteName = ref('')
const unavailableMessage = ref<string | null>(null)

const widgets = reactive<Record<WidgetKey, WidgetState>>({
  trafficSources: createWidgetState(createAnalyticsQuery({ days: 30 })),
  searchEngines: createWidgetState(createYearAnalyticsQuery('month')),
  searchBranded: createWidgetState(createYearAnalyticsQuery('month')),
  searchNonBranded: createWidgetState(createYearAnalyticsQuery('month')),
  geography: createWidgetState(createAnalyticsQuery({ days: 30 })),
  devices: createWidgetState(createAnalyticsQuery({ days: 30 })),
})

async function reload(key: WidgetKey, refresh = false) {
  const widget = widgets[key]
  widget.loading = true
  widget.error = null

  try {
    widget.data = await fetchAnalyticsReport(siteId.value, reportMap[key], widget.query, refresh)
  } catch (error) {
    if (error instanceof ApiError && error.status === 503) {
      unavailableMessage.value = error.message
      widget.error = error.message
    } else {
      widget.error = error instanceof Error ? error.message : 'Ошибка загрузки'
    }
    widget.data = null
  } finally {
    widget.loading = false
  }
}

function applyQuery(key: WidgetKey, query: AnalyticsQuery) {
  widgets[key].query = query
  void reload(key, false)
}

onMounted(async () => {
  const sites = await loadCabinetSites()
  siteName.value = sites.find((site) => site.id === siteId.value)?.name ?? ''

  await Promise.all(
    (Object.keys(widgets) as WidgetKey[]).map((key) => reload(key, false)),
  )
})
</script>
