<template>
  <div>
    <label v-if="label" :for="id" :class="formLabelClass">
      {{ label }}<span v-if="required" class="text-error-500">*</span>
    </label>
    <textarea
      :id="id"
      :value="modelValue"
      :required="required"
      :rows="rows"
      :placeholder="placeholder"
      :class="formTextareaClass"
      @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
  </div>
</template>

<script setup lang="ts">
import { formLabelClass, formTextareaClass } from '@/constants/formClasses'

withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    id?: string
    rows?: number
    required?: boolean
    placeholder?: string
  }>(),
  { rows: 4, required: false },
)

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
