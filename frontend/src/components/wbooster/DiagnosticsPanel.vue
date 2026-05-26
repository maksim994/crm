<template>
  <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
      <div>
        <h3 class="font-semibold text-gray-800 dark:text-white">{{ title }}</h3>
        <p v-if="checkedAt" class="mt-1 text-xs text-gray-500">
          Проверено: {{ formatCheckedAt(checkedAt) }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <span v-if="overallStatus" :class="statusBadgeClass(overallStatus)">
          {{ statusLabel(overallStatus) }}
        </span>
        <button
          type="button"
          :class="btnOutlineClass"
          class="!px-3 !py-2 text-xs"
          :disabled="loading"
          @click="$emit('refresh')"
        >
          {{ loading ? 'Проверка…' : 'Проверить снова' }}
        </button>
      </div>
    </div>

    <div v-if="loading && !groups.length" class="p-6 text-sm text-gray-500">Загрузка…</div>
    <div v-else-if="error" class="p-6 text-sm text-red-500">{{ error }}</div>
    <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
      <section v-for="group in groups" :key="group.id" class="p-4">
        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ group.title }}</h4>
        <ul class="space-y-3">
          <li
            v-for="check in group.checks"
            :key="check.id"
            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 dark:border-gray-800 dark:bg-gray-900/40"
          >
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-sm font-medium text-gray-800 dark:text-white">{{ check.label }}</span>
                  <span :class="statusBadgeClass(check.status)">{{ statusLabel(check.status) }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ check.message }}</p>
                <p v-if="check.hint" class="mt-1 text-xs text-gray-500 dark:text-gray-500">{{ check.hint }}</p>
              </div>
            </div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { btnOutlineClass } from '@/constants/buttonClasses'

export interface DiagnosticCheckItem {
  id: string
  label: string
  status: 'ok' | 'warning' | 'error' | 'skipped'
  message: string
  hint?: string | null
}

export interface DiagnosticGroup {
  id: string
  title: string
  checks: DiagnosticCheckItem[]
}

defineProps<{
  title: string
  groups: DiagnosticGroup[]
  overallStatus?: string | null
  checkedAt?: string | null
  loading?: boolean
  error?: string | null
}>()

defineEmits<{ refresh: [] }>()

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    ok: 'OK',
    warning: 'Внимание',
    error: 'Ошибка',
    skipped: 'Пропущено',
  }
  return map[status] ?? status
}

function statusBadgeClass(status: string): string {
  const base = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium'
  const map: Record<string, string> = {
    ok: `${base} bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400`,
    warning: `${base} bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400`,
    error: `${base} bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400`,
    skipped: `${base} bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400`,
  }
  return map[status] ?? `${base} bg-gray-100 text-gray-600`
}

function formatCheckedAt(value: string): string {
  return new Date(value).toLocaleString('ru-RU')
}
</script>
