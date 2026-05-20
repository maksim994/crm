<template>
  <admin-layout>
    <PageBreadcrumb page-title="Редактирование лида" />
    <div class="max-w-2xl">
      <form
        @submit.prevent="submit"
        class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8"
      >
        <FormSelect v-model="form.lead_status" label="Статус" id="lead-status">
          <option value="not_processed">Не обработан</option>
          <option value="no_answer">Не ответили</option>
          <option value="preparing_offer">Составляем КП</option>
          <option value="not_interested">Неинтересен</option>
          <option value="deal_lost">Сделка провалена</option>
        </FormSelect>
        <FormInput v-model="form.manager_name" label="Менеджер" id="lead-manager" />
        <FormTextarea v-model="form.manager_comment" label="Комментарий" id="lead-comment" :rows="3" />
        <div class="flex flex-wrap gap-3 pt-2">
          <Button type="submit">Сохранить</Button>
          <router-link :to="`/leads/${route.params.id}`" :class="btnOutlineClass">Отмена</router-link>
        </div>
      </form>
    </div>
  </admin-layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
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

const form = ref({
  lead_status: 'not_processed',
  manager_name: '',
  manager_comment: '',
  acc_status: '',
  acc_comment: '',
  ppc_status: '',
  ppc_comment: '',
})

onMounted(async () => {
  const res = await api<{ data: typeof form.value }>(`/leads/${route.params.id}`)
  Object.assign(form.value, res.data)
})

async function submit() {
  await api(`/leads/${route.params.id}`, { method: 'PUT', body: JSON.stringify(form.value) })
  router.push(`/leads/${route.params.id}`)
}
</script>
