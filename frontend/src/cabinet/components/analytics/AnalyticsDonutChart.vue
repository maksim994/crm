<template>
  <VueApexCharts
    :key="chartKey"
    type="donut"
    height="280"
    :options="options"
    :series="series"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps<{
  labels: string[]
  values: number[]
}>()

const series = computed(() => props.values)

const chartKey = computed(() => `${props.labels.join('\0')}|${props.values.join(',')}`)

const options = computed(() => ({
  chart: {
    fontFamily: 'Outfit, sans-serif',
    toolbar: { show: false },
  },
  labels: props.labels,
  colors: ['#14b8a6', '#8b5cf6', '#60a5fa', '#fb923c', '#f472b6', '#94a3b8', '#facc15', '#4ade80'],
  legend: {
    position: 'right',
    fontSize: '12px',
  },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '62%',
      },
    },
  },
}))
</script>
