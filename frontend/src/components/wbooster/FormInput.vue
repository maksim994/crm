<template>
  <div>
    <label v-if="label" :for="id" :class="formLabelClass">
      {{ label }}<span v-if="required" class="text-error-500">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :required="required"
      :placeholder="placeholder"
      :class="formInputClass"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
  </div>
</template>

<script setup lang="ts">
import { formInputClass, formLabelClass } from '@/constants/formClasses'

withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    id?: string
    type?: string
    required?: boolean
    placeholder?: string
  }>(),
  { type: 'text', required: false },
)

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
