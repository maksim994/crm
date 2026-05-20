<template>
  <admin-layout>
    <PageBreadcrumb :page-title="title" />
    <div class="max-w-2xl">
      <p
        v-if="passwordAlert"
        class="mb-4 rounded-lg bg-success-50 p-4 text-sm whitespace-pre-wrap dark:bg-success-500/10"
      >
        {{ passwordAlert }}
      </p>
      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
      >
        <FormInput v-model="form.name" label="Имя" id="cabinet-user-name" required />
        <FormInput v-model="form.email" label="Email для входа" id="cabinet-user-email" type="email" required />
        <FormInput
          v-if="!isEdit"
          v-model="form.password"
          label="Пароль"
          id="cabinet-user-password"
          type="password"
          required
        />
        <FormInput
          v-else
          v-model="form.password"
          label="Новый пароль (оставьте пустым, чтобы не менять)"
          id="cabinet-user-password-edit"
          type="password"
        />

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input v-model="form.cabinet_all_sites" type="checkbox" class="rounded border-gray-300" />
          Все проекты заказчика
        </label>

        <div
          class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
          :class="{ 'opacity-50': form.cabinet_all_sites }"
        >
          <p class="mb-3 text-sm font-medium text-gray-800 dark:text-white">Доступные проекты</p>
          <p v-if="!clientSites.length" class="text-sm text-gray-500">У заказчика пока нет проектов.</p>
          <div v-else class="space-y-2">
            <label
              v-for="site in clientSites"
              :key="site.id"
              class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
            >
              <input
                v-model="selectedSiteIds"
                type="checkbox"
                :value="site.id"
                :disabled="form.cabinet_all_sites"
                class="rounded border-gray-300"
              />
              {{ site.name }}
            </label>
          </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" />
          Доступ активен
        </label>

        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit" :disabled="loading">Сохранить</Button>
          <router-link :to="`/clients/${clientId}`" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/wbooster/FormInput.vue'
import { btnOutlineClass } from '@/constants/buttonClasses'
import { api } from '@/api/client'

interface SiteOption {
  id: string
  name: string
}

interface CabinetUser {
  id: string
  name: string
  email: string
  cabinet_all_sites: boolean
  is_active: boolean
  site_ids?: string[]
}

const route = useRoute()
const router = useRouter()
const clientId = computed(() => route.params.id as string)
const userId = computed(() => route.params.userId as string | undefined)
const isEdit = computed(() => !!userId.value)
const title = computed(() => (isEdit.value ? 'Редактирование доступа в ЛК' : 'Доступ в личный кабинет'))

const loading = ref(false)
const passwordAlert = ref('')
const clientSites = ref<SiteOption[]>([])
const selectedSiteIds = ref<string[]>([])

const form = ref({
  name: '',
  email: '',
  password: '',
  cabinet_all_sites: true,
  is_active: true,
})

watch(
  () => form.value.cabinet_all_sites,
  (allSites) => {
    if (allSites) {
      selectedSiteIds.value = []
    }
  },
)

onMounted(async () => {
  const clientRes = await api<{
    data: { name: string }
    sites: SiteOption[]
  }>(`/clients/${clientId.value}`)
  clientSites.value = clientRes.sites ?? []

  if (!isEdit.value) {
    return
  }

  const userRes = await api<{ data: CabinetUser }>(
    `/clients/${clientId.value}/cabinet-users/${userId.value}`,
  )
  const user = userRes.data
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    cabinet_all_sites: user.cabinet_all_sites,
    is_active: user.is_active,
  }
  selectedSiteIds.value = user.site_ids ?? []
})

async function submit() {
  loading.value = true
  const payload: Record<string, unknown> = {
    name: form.value.name,
    email: form.value.email,
    cabinet_all_sites: form.value.cabinet_all_sites,
    is_active: form.value.is_active,
    site_ids: form.value.cabinet_all_sites ? [] : selectedSiteIds.value,
  }

  if (form.value.password) {
    payload.password = form.value.password
  }

  try {
    if (isEdit.value) {
      await api(`/clients/${clientId.value}/cabinet-users/${userId.value}`, {
        method: 'PUT',
        body: JSON.stringify(payload),
      })
      router.push(`/clients/${clientId.value}`)
    } else {
      if (!form.value.password) {
        return
      }
      payload.password = form.value.password
      const res = await api<{ data: CabinetUser; generated_password: string }>(
        `/clients/${clientId.value}/cabinet-users`,
        {
          method: 'POST',
          body: JSON.stringify(payload),
        },
      )
      passwordAlert.value = `Пользователь создан.\n\nПароль для входа:\n${res.generated_password}\n\nСохраните пароль — повторно он не отображается.`
      setTimeout(() => {
        router.push(`/clients/${clientId.value}`)
      }, 4000)
    }
  } finally {
    loading.value = false
  }
}
</script>
