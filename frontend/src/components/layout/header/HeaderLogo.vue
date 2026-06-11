<template>
  <router-link to="/" class="lg:hidden">
    <img class="dark:hidden h-8 w-auto" :src="logoLightUrl" alt="Lead CRM" />
    <img class="hidden h-8 w-auto dark:block" :src="logoDarkUrl" alt="Lead CRM" />
  </router-link>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { logoDarkUrl, logoLightUrl } from '@/constants/branding'
</script>
