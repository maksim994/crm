<template>
  <ThemeProvider>
    <SidebarProvider>
      <router-view />
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup lang="ts">
import SidebarProvider from '@/components/layout/SidebarProvider.vue'
import ThemeProvider from '@/components/layout/ThemeProvider.vue'
</script>
