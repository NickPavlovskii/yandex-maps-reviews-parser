<template>
  <label class="app-input">
    <span
      v-if="label"
      class="app-input__label"
    >
      {{ label }}
    </span>
    <input
      v-model="model"
      class="app-input__control"
      :type="type"
      :placeholder="placeholder"
      :autocomplete="autocomplete"
      :disabled="disabled"
      :required="required"
    >
  </label>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    label?: string
    type?: string
    placeholder?: string
    autocomplete?: string
    disabled?: boolean
    required?: boolean
  }>(),
  {
    label: '',
    type: 'text',
    placeholder: '',
    autocomplete: 'off',
    disabled: false,
    required: false,
  },
)

const model = defineModel<string>({ default: '' })
</script>

<style scoped>
.app-input {
  display: block;
}

.app-input + .app-input {
  margin-top: 18px;
}

.app-input__label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: #6f7378;
}

.app-input__control {
  width: 100%;
  height: 48px;
  padding: 0 18px;
  border: none;
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 0 0 1px #ececee;
  font-size: 15px;
  color: #111;
}

.app-input__control::placeholder {
  color: #c0c3c7;
}

.app-input__control:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px #111;
}

.app-input__control:disabled {
  background: #f2f3f3;
  color: var(--muted-gray);
}
</style>
