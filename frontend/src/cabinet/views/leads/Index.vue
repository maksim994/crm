<template>
  <cabinet-layout>
    <leads-list mode="all" :client-name="clientName" />
  </cabinet-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import CabinetLayout from '@cabinet/layout/CabinetLayout.vue'
import LeadsList from '@cabinet/components/LeadsList.vue'
import { api } from '@cabinet/api/client'

const clientName = ref('')

onMounted(async () => {
  try {
    const res = await api<{ data: { agency_client?: { name: string } } }>('/user')
    clientName.value = res.data.agency_client?.name ?? ''
  } catch {
    clientName.value = ''
  }
})
</script>
