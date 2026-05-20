<template>
  <admin-layout>
    <PageBreadcrumb :page-title="title" />
    <div class="max-w-2xl">
      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
      >
        <FormInput v-model="form.name" label="Название" id="client-name" required />
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <FormInput v-model="form.inn" label="ИНН" id="client-inn" />
          <FormSelect v-model="form.status" label="Статус" id="client-status">
            <option value="active">Активен</option>
            <option value="archived">В архиве</option>
          </FormSelect>
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <FormInput v-model="form.contact_name" label="Контакт" id="client-contact" />
          <FormInput
            v-model="form.contact_email"
            label="Email"
            id="client-email"
            type="email"
          />
        </div>
        <FormInput v-model="form.contact_phone" label="Телефон" id="client-phone" />
        <FormTextarea v-model="form.manager_comment" label="Комментарий" id="client-comment" :rows="3" />
        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit" :disabled="loading">Сохранить</Button>
          <router-link to="/clients" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminLayout from '@/components/layout/AdminLayout.vue'
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/wbooster/FormInput.vue'
import FormSelect from '@/components/wbooster/FormSelect.vue'
import FormTextarea from '@/components/wbooster/FormTextarea.vue'
import { btnOutlineClass } from '@/constants/buttonClasses'
import { api } from '@/api/client'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const title = computed(() => (isEdit.value ? 'Редактирование заказчика' : 'Новый заказчик'))
const loading = ref(false)

const form = ref({
  name: '',
  inn: '',
  contact_name: '',
  contact_email: '',
  contact_phone: '',
  manager_comment: '',
  status: 'active',
})

onMounted(async () => {
  if (!isEdit.value) return
  const res = await api<{ data: typeof form.value }>(`/clients/${route.params.id}`)
  Object.assign(form.value, res.data)
})

async function submit() {
  loading.value = true
  try {
    if (isEdit.value) {
      await api(`/clients/${route.params.id}`, {
        method: 'PUT',
        body: JSON.stringify(form.value),
      })
    } else {
      const res = await api<{ data: { id: string } }>('/clients', {
        method: 'POST',
        body: JSON.stringify(form.value),
      })
      router.push(`/clients/${res.data.id}`)
      return
    }
    router.push(`/clients/${route.params.id}`)
  } finally {
    loading.value = false
  }
}
</script>
