<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="flex items-center text-gray-700 dark:text-gray-400"
      @click.prevent="toggleDropdown"
    >
      <span
        class="mr-3 flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-brand-500 text-white font-semibold"
      >
        {{ initial }}
      </span>
      <span class="block mr-1 font-medium text-theme-sm">{{ user?.name ?? 'Admin' }}</span>
      <ChevronDownIcon :class="{ 'rotate-180': dropdownOpen }" />
    </button>

    <div
      v-if="dropdownOpen"
      class="absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
    >
      <div>
        <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
          {{ user?.name }}
        </span>
        <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">
          {{ user?.email }}
        </span>
      </div>

      <button
        type="button"
        @click="signOut"
        class="flex items-center gap-3 px-3 py-2 mt-3 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5"
      >
        <LogoutIcon class="text-gray-500" />
        Выход
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ChevronDownIcon, LogoutIcon } from '@/icons'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api/client'

const router = useRouter()
const dropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const user = ref<{ name: string; email: string } | null>(null)

const initial = computed(() => (user.value?.name?.[0] ?? 'A').toUpperCase())

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const signOut = async () => {
  await api('/logout', { method: 'POST' })
  closeDropdown()
  router.push('/login')
}

const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  try {
    const res = await api<{ data: { name: string; email: string } }>('/user')
    user.value = res.data
  } catch {
    user.value = null
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
