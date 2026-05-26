<template>
  <cabinet-layout>
    <leads-list
      v-if="siteName"
      mode="project"
      :site-id="siteId"
      :site-name="siteName"
    />
  </cabinet-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import LeadsList from '@cabinet/components/LeadsList.vue'
import { loadCabinetSites } from '@cabinet/composables/useCabinetSites'

const route = useRoute()
const siteId = computed(() => String(route.params.siteId))
const siteName = ref('')

onMounted(async () => {
  const sites = await loadCabinetSites()
  siteName.value = sites.find((site) => site.id === siteId.value)?.name ?? ''
})
</script>
