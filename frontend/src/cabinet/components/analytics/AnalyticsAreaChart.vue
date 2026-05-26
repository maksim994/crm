<template>
  <VueApexCharts
    :key="chartKey"
    type="area"
    height="280"
    :options="options"
    :series="chartSeries"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps<{
  categories: string[]
  series: Array<{ name: string; data: number[] }>
}>()

const chartSeries = computed(() => props.series)

const chartKey = computed(() =>
  [
    props.categories.join('\0'),
    props.series.map((item) => `${item.name}:${item.data.join(',')}`).join('\0'),
  ].join('|'),
)

const options = computed(() => ({
  chart: {
    fontFamily: 'Outfit, sans-serif',
    type: 'area',
    stacked: true,
    toolbar: { show: false },
  },
  colors: ['#14b8a6', '#8b5cf6', '#60a5fa', '#fb923c', '#f472b6', '#94a3b8'],
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      opacityFrom: 0.45,
      opacityTo: 0.05,
    },
  },
  dataLabels: { enabled: false },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '12px',
  },
  xaxis: {
    categories: props.categories,
    labels: { rotate: -45, style: { fontSize: '11px' } },
  },
  yaxis: {
    labels: {
      formatter: (value: number) => String(Math.round(value)),
    },
  },
}))
</script>
