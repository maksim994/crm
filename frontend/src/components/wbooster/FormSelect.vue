<template>
  <div>
    <label v-if="label" :for="id" :class="formLabelClass">
      {{ label }}<span v-if="required" class="text-error-500">*</span>
    </label>
    <div class="relative z-20 bg-transparent">
      <select
        :id="id"
        :value="modelValue"
        :required="required"
        :class="[formSelectClass, modelValue ? 'text-gray-800 dark:text-white/90' : 'text-gray-400']"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      >
        <slot />
      </select>
      <span
        class="pointer-events-none absolute right-4 top-1/2 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400"
      >
        <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path
            d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formLabelClass, formSelectClass } from '@/constants/formClasses'

withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    id?: string
    required?: boolean
  }>(),
  { required: false },
)

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
