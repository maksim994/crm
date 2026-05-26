<template>
  <admin-layout>
    <PageBreadcrumb page-title="Диагностика интеграций" />

    <p class="mb-6 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
      Проверка доступов к Яндекс Метрике, входящей почте, инфраструктуре и очереди.
      Секреты (OAuth, пароли) не отображаются — только результат проверки.
    </p>

    <diagnostics-panel
      title="Платформа"
      :groups="groups"
      :overall-status="overallStatus"
      :checked-at="checkedAt"
      :loading="loading"
      :error="error"
      @refresh="load"
    />
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import DiagnosticsPanel, {
  type DiagnosticGroup,
} from '@/components/wbooster/DiagnosticsPanel.vue'
import { api } from '@/api/client'

const loading = ref(false)
const error = ref<string | null>(null)
const groups = ref<DiagnosticGroup[]>([])
const overallStatus = ref<string | null>(null)
const checkedAt = ref<string | null>(null)

async function load() {
  loading.value = true
  error.value = null

  try {
    const res = await api<{
      status: string
      checked_at: string
      groups: DiagnosticGroup[]
    }>('/diagnostics')

    groups.value = res.groups
    overallStatus.value = res.status
    checkedAt.value = res.checked_at
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Не удалось выполнить диагностику'
    groups.value = []
    overallStatus.value = null
    checkedAt.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
