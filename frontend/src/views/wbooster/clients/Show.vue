<template>
  <admin-layout>
    <PageBreadcrumb :page-title="client?.name ?? 'Заказчик'" />

    <div v-if="loading" :class="loadingTextClass">Загрузка…</div>

    <template v-else-if="client">
      <div class="mb-4 flex flex-wrap gap-2">
        <router-link :to="`/clients/${client.id}/edit`" :class="btnPrimaryClass">Редактировать</router-link>
        <router-link :to="createSiteLink" :class="btnOutlineClass">Добавить проект</router-link>
        <router-link :to="leadsLink" :class="btnOutlineClass">Лиды заказчика</router-link>
        <button
          type="button"
          :class="btnOutlineClass"
          :disabled="!hasActiveCabinetUsers || impersonating"
          @click="loginToCabinet()"
        >
          {{ impersonating ? 'Открываем…' : 'Войти в ЛК' }}
        </button>
        <router-link to="/clients" :class="btnOutlineClass">К списку</router-link>
      </div>

      <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 :class="cardTitleClass">Заказчик</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Статус</dt>
              <dd>
                <span :class="clientStatusBadgeClass(client.status)">{{ statusLabel(client.status) }}</span>
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">ИНН</dt>
              <dd :class="dlValueClass">{{ client.inn || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Контакт</dt>
              <dd :class="dlValueRightClass">{{ client.contact_name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Email</dt>
              <dd :class="dlValueRightClass">{{ client.contact_email || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Телефон</dt>
              <dd :class="dlValueClass">{{ client.contact_phone || '—' }}</dd>
            </div>
            <div v-if="client.manager_comment" class="border-t border-gray-100 pt-3 dark:border-gray-800">
              <dt :class="`${dlLabelClass} mb-1`">Комментарий менеджера</dt>
              <dd class="whitespace-pre-wrap text-gray-800 dark:text-white/90">{{ client.manager_comment }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 :class="cardTitleClass">Сводка</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Проектов</dt>
              <dd :class="dlValueClass">{{ client.sites_count ?? sites.length }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Лидов</dt>
              <dd :class="dlValueClass">{{ client.leads_count ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt :class="dlLabelClass">Пользователей ЛК</dt>
              <dd :class="dlValueClass">{{ users.length }}</dd>
            </div>
          </dl>
          <p :class="`${mutedTextClass} mt-4`">
            Вход в
            <a href="/cabinet/" class="text-brand-500 hover:underline">/cabinet/</a>
            под email пользователя ЛК.
          </p>
        </div>
      </div>

      <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
          <h3 class="font-semibold text-gray-800 dark:text-white">Проекты</h3>
          <router-link :to="createSiteLink" class="text-sm text-brand-500 hover:underline">+ Добавить</router-link>
        </div>
        <div v-if="sites.length" class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead :class="tableHeadRowClass">
              <tr>
                <th :class="tableHeadCellClass">Название</th>
                <th :class="tableHeadCellClass">Домены</th>
                <th :class="tableHeadCellClass">Статус</th>
                <th :class="tableHeadCellRightClass">Лидов</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="site in sites" :key="site.id" :class="tableRowClass">
                <td :class="tableCellMediumClass">
                  <router-link :to="`/sites/${site.id}`" class="text-brand-500 hover:underline">
                    {{ site.name }}
                  </router-link>
                </td>
                <td :class="tableCellClass">{{ (site.domains || []).join(', ') || '—' }}</td>
                <td :class="tableCellClass">
                  <span :class="siteStatusBadgeClass(site.status)">{{ siteStatusLabel(site.status) }}</span>
                </td>
                <td :class="`${tableCellClass} text-right`">{{ site.leads_count ?? 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else :class="emptyStateClass">Проектов пока нет.</p>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div
          class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 dark:border-gray-800"
        >
          <h3 class="font-semibold text-gray-800 dark:text-white">Доступ в личный кабинет</h3>
          <router-link
            :to="`/clients/${client.id}/cabinet-users/create`"
            class="text-sm text-brand-500 hover:underline"
          >
            + Добавить доступ
          </router-link>
        </div>
        <div v-if="users.length" class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead :class="tableHeadRowClass">
              <tr>
                <th :class="tableHeadCellClass">Имя</th>
                <th :class="tableHeadCellClass">Email</th>
                <th :class="tableHeadCellClass">Проекты</th>
                <th :class="tableHeadCellClass">Статус</th>
                <th :class="tableHeadCellRightClass">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" :class="tableRowClass">
                <td :class="tableCellMediumClass">{{ user.name }}</td>
                <td :class="tableCellClass">{{ user.email }}</td>
                <td :class="tableCellClass">{{ accessLabel(user) }}</td>
                <td :class="tableCellClass">
                  <span :class="user.is_active ? statusActiveUserBadgeClass : statusInactiveBadgeClass">
                    {{ user.is_active ? 'Активен' : 'Отключён' }}
                  </span>
                </td>
                <td :class="`${tableCellClass} text-right space-x-3`">
                  <button
                    v-if="user.is_active"
                    type="button"
                    class="text-brand-500 hover:underline disabled:opacity-50"
                    :disabled="impersonating"
                    @click="loginToCabinet(user.id)"
                  >
                    Войти
                  </button>
                  <router-link
                    :to="`/clients/${client.id}/cabinet-users/${user.id}/edit`"
                    class="text-brand-500 hover:underline"
                  >
                    Изменить
                  </router-link>
                  <button
                    type="button"
                    class="text-error-500 hover:underline"
                    @click="removeUser(user.id)"
                  >
                    Удалить
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else :class="emptyStateClass">Пользователи ЛК не назначены.</p>
      </div>
    </template>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import { btnOutlineClass, btnPrimaryClass } from '@/constants/buttonClasses'
import {
  cardTitleClass,
  dlLabelClass,
  dlValueClass,
  dlValueRightClass,
  emptyStateClass,
  loadingTextClass,
  mutedTextClass,
  statusActiveBadgeClass,
  statusActiveUserBadgeClass,
  statusArchivedBadgeClass,
  statusInactiveBadgeClass,
  statusPausedBadgeClass,
  tableCellClass,
  tableCellMediumClass,
  tableHeadCellClass,
  tableHeadCellRightClass,
  tableHeadRowClass,
  tableRowClass,
} from '@/constants/uiClasses'
import { api, ApiError } from '@/api/client'

interface ClientDetail {
  id: string
  name: string
  inn: string | null
  status: string
  contact_name: string | null
  contact_email: string | null
  contact_phone: string | null
  manager_comment: string | null
  sites_count?: number
  leads_count?: number
}

interface SiteRow {
  id: string
  name: string
  domains: string[]
  status: string
  leads_count?: number
}

interface CabinetUser {
  id: number
  name: string
  email: string
  cabinet_all_sites: boolean
  is_active: boolean
  sites?: { id: string; name: string }[]
}

const route = useRoute()
const loading = ref(true)
const client = ref<ClientDetail | null>(null)
const sites = ref<SiteRow[]>([])
const users = ref<CabinetUser[]>([])
const impersonating = ref(false)

const leadsLink = computed(() => `/leads?agency_client_id=${client.value?.id ?? ''}`)
const createSiteLink = computed(() => `/sites/create?agency_client_id=${client.value?.id ?? ''}`)
const hasActiveCabinetUsers = computed(() => users.value.some((u) => u.is_active))

function statusLabel(status: string): string {
  return status === 'active' ? 'Активен' : 'Архив'
}

function clientStatusBadgeClass(status: string): string {
  return status === 'active' ? statusActiveBadgeClass : statusArchivedBadgeClass
}

function siteStatusLabel(status: string): string {
  const map: Record<string, string> = {
    active: 'Активен',
    paused: 'Пауза',
    archived: 'Архив',
  }
  return map[status] ?? status
}

function siteStatusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    active: statusActiveBadgeClass,
    paused: statusPausedBadgeClass,
    archived: statusArchivedBadgeClass,
  }
  return map[status] ?? statusArchivedBadgeClass
}

function accessLabel(user: CabinetUser): string {
  if (user.cabinet_all_sites) {
    return 'Все проекты'
  }
  const count = user.sites?.length ?? 0
  return count ? `${count} проект(ов)` : 'Нет проектов'
}

async function loginToCabinet(userId?: number) {
  if (!client.value) {
    return
  }
  impersonating.value = true
  try {
    const res = await api<{ cabinet_path: string }>(`/clients/${client.value.id}/impersonate`, {
      method: 'POST',
      body: JSON.stringify(userId ? { user_id: userId } : {}),
    })
    const cabinetUrl = `${window.location.origin}${res.cabinet_path}`
    window.open(cabinetUrl, '_blank', 'noopener,noreferrer')
  } catch (e) {
    const message = e instanceof ApiError ? e.message : 'Не удалось открыть личный кабинет'
    alert(message)
  } finally {
    impersonating.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const res = await api<{
      data: ClientDetail
      sites: SiteRow[]
      users: CabinetUser[]
    }>(`/clients/${route.params.id}`)
    client.value = res.data
    sites.value = res.sites ?? []
    users.value = Array.isArray(res.users) ? res.users : []
  } finally {
    loading.value = false
  }
}

async function removeUser(userId: number) {
  if (!client.value || !confirm('Удалить доступ в личный кабинет?')) {
    return
  }
  await api(`/clients/${client.value.id}/cabinet-users/${userId}`, { method: 'DELETE' })
  await load()
}

onMounted(load)
</script>
