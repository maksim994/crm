<template>
  <cabinet-layout>
    <div class="mb-6">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Трафик и лиды</h2>
      <p v-if="siteName" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ siteName }}
      </p>
    </div>

    <div
      class="rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <p class="text-base font-medium text-gray-800 dark:text-white">Раздел в разработке</p>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Здесь будет аналитика трафика и лидов по проекту.
      </p>
    </div>
  </cabinet-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import { loadCabinetSites } from '@cabinet/composables/useCabinetSites'

const route = useRoute()
const siteId = computed(() => String(route.params.siteId))
const siteName = ref('')

onMounted(async () => {
  const sites = await loadCabinetSites()
  siteName.value = sites.find((site) => site.id === siteId.value)?.name ?? ''
})
</script>
