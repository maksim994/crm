<template>
  <admin-layout>
    <PageBreadcrumb :page-title="client?.name ?? 'Заказчик'" />

    <div v-if="loading" class="text-sm text-gray-500">Загрузка…</div>

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
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Заказчик</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Статус</dt>
              <dd>
                <span :class="clientStatusBadgeClass(client.status)">{{ statusLabel(client.status) }}</span>
              </dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">ИНН</dt>
              <dd>{{ client.inn || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Контакт</dt>
              <dd class="text-right">{{ client.contact_name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Email</dt>
              <dd class="text-right">{{ client.contact_email || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Телефон</dt>
              <dd>{{ client.contact_phone || '—' }}</dd>
            </div>
            <div v-if="client.manager_comment" class="border-t border-gray-100 pt-3 dark:border-gray-800">
              <dt class="mb-1 text-gray-500">Комментарий менеджера</dt>
              <dd class="whitespace-pre-wrap text-gray-800 dark:text-white">{{ client.manager_comment }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
          <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">Сводка</h3>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Проектов</dt>
              <dd>{{ client.sites_count ?? sites.length }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Лидов</dt>
              <dd>{{ client.leads_count ?? 0 }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-gray-500">Пользователей ЛК</dt>
              <dd>{{ users.length }}</dd>
            </div>
          </dl>
          <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
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
            <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800">
              <tr>
                <th class="p-4 text-left">Название</th>
                <th class="p-4 text-left">Домены</th>
                <th class="p-4 text-left">Статус</th>
                <th class="p-4 text-right">Лидов</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="site in sites"
                :key="site.id"
                class="border-b border-gray-100 dark:border-gray-800"
              >
                <td class="p-4 font-medium">
                  <router-link :to="`/sites/${site.id}`" class="text-brand-500 hover:underline">
                    {{ site.name }}
                  </router-link>
                </td>
                <td class="p-4">{{ (site.domains || []).join(', ') || '—' }}</td>
                <td class="p-4">
                  <span :class="siteStatusBadgeClass(site.status)">{{ siteStatusLabel(site.status) }}</span>
                </td>
                <td class="p-4 text-right">{{ site.leads_count ?? 0 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="p-6 text-sm text-gray-500">Проектов пока нет.</p>
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
            <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800">
              <tr>
                <th class="p-4 text-left">Имя</th>
                <th class="p-4 text-left">Email</th>
                <th class="p-4 text-left">Проекты</th>
                <th class="p-4 text-left">Статус</th>
                <th class="p-4 text-right">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in users"
                :key="user.id"
                class="border-b border-gray-100 dark:border-gray-800"
              >
                <td class="p-4 font-medium text-gray-800 dark:text-white">{{ user.name }}</td>
                <td class="p-4">{{ user.email }}</td>
                <td class="p-4">{{ accessLabel(user) }}</td>
                <td class="p-4">
                  <span
                    class="rounded-full px-2 py-0.5 text-xs"
                    :class="
                      user.is_active
                        ? 'bg-success-50 text-success-700 dark:bg-success-500/10'
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-800'
                    "
                  >
                    {{ user.is_active ? 'Активен' : 'Отключён' }}
                  </span>
                </td>
                <td class="p-4 text-right space-x-3">
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
        <p v-else class="p-6 text-sm text-gray-500">Пользователи ЛК не назначены.</p>
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
  const base = 'rounded-full px-2 py-0.5 text-xs'
  return status === 'active'
    ? `${base} bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400`
    : `${base} bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400`
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
  const base = 'rounded-full px-2 py-0.5 text-xs'
  const map: Record<string, string> = {
    active: `${base} bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400`,
    paused: `${base} bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400`,
    archived: `${base} bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400`,
  }
  return map[status] ?? `${base} bg-gray-100 text-gray-600`
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
