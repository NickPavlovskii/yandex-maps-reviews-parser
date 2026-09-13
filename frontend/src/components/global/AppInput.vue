<template>
  <div class="app-input">
    <label
      v-if="label"
      class="app-input__label"
      :for="inputId"
    >
      {{ label }}
    </label>
    <div class="app-input__field">
      <span
        v-if="$slots.lead"
        class="app-input__lead"
      >
        <slot name="lead" />
      </span>
      <input
        :id="inputId"
        v-model="model"
        class="app-input__control"
        :type="inputType"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :disabled="disabled"
        :required="required"
      >
      <span
        v-if="$slots.trail"
        class="app-input__trail"
      >
        <slot name="trail" />
      </span>
      <button
        v-if="canClear"
        class="app-input__clear"
        type="button"
        aria-label="Очистить"
        :disabled="disabled"
        @click.stop.prevent="clear"
      >
        <v-icon
          icon="mdi-close"
          size="18"
        />
      </button>
      <button
        v-if="canReveal"
        class="app-input__reveal"
        type="button"
        tabindex="0"
        :disabled="disabled"
        :aria-label="revealLabel"
        :aria-pressed="revealed"
        @click.stop.prevent="revealed = !revealed"
      >
        <v-icon
          :icon="revealIcon"
          size="22"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Текстовое поле с меткой; для пароля показывает кнопку скрыть/показать.
 *
 * @param {String} [modelValue = ''] - значение поля
 * @param {String} [label = ''] - подпись над полем
 * @param {String} [type = 'text'] - тип input
 * @param {String} [placeholder = ''] - плейсхолдер
 * @param {String} [autocomplete = 'off'] - значение autocomplete
 * @param {Boolean} [disabled = false] - заблокировать поле
 * @param {Boolean} [required = false] - обязательное поле
 * @param {Boolean} [clearable = false] - кнопка очистки значения
 */
import { computed, ref, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    label?: string
    type?: string
    placeholder?: string
    autocomplete?: string
    disabled?: boolean
    required?: boolean
    clearable?: boolean
  }>(),
  {
    label: '',
    type: 'text',
    placeholder: '',
    autocomplete: 'off',
    disabled: false,
    required: false,
    clearable: false,
  },
)

const model = defineModel<string>({ default: '' })
const inputId = useId()
const revealed = ref(false)
const canReveal = computed(() => props.type === 'password')
const canClear = computed(() => (
  props.clearable && model.value !== '' && !props.disabled && !canReveal.value
))

function clear() {
  model.value = ''
}
const inputType = computed(() => {
  if (!canReveal.value) {
    return props.type
  }

  return revealed.value ? 'text' : 'password'
})

const revealLabel = computed(() => (
  revealed.value ? 'Скрыть пароль' : 'Показать пароль'
))

const revealIcon = computed(() => (
  revealed.value ? 'mdi-eye-off-outline' : 'mdi-eye-outline'
))
</script>

<style scoped>
.app-input {
  display: block;
  width: 100%;
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

.app-input__field {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  height: 48px;
  overflow: hidden;
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 0 0 1px #ececee;
}

.app-input__field:focus-within {
  box-shadow: 0 0 0 2px #111;
}

.app-input__lead,
.app-input__trail {
  position: absolute;
  top: 50%;
  z-index: 2;
  display: flex;
  align-items: center;
  color: #c0c3c7;
  transform: translateY(-50%);
}

.app-input__lead {
  left: 16px;
}

.app-input__trail {
  right: 16px;
}

.app-input__clear,
.app-input__reveal {
  position: absolute;
  top: 50%;
  right: 8px;
  z-index: 3;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: #6f7378;
  cursor: pointer;
  transform: translateY(-50%);
}

.app-input__clear:hover:not(:disabled),
.app-input__clear:focus-visible,
.app-input__reveal:hover:not(:disabled),
.app-input__reveal:focus-visible {
  color: #111;
  outline: none;
}

.app-input__clear:disabled,
.app-input__reveal:disabled {
  cursor: default;
  opacity: 0.5;
}

.app-input__control {
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  height: 100%;
  padding: 0 18px;
  border: none;
  border-radius: 999px;
  background: transparent;
  font-size: 15px;
  color: #111;
}

.app-input__field:has(.app-input__lead) .app-input__control {
  padding-left: 42px;
}

.app-input__field:has(.app-input__trail) .app-input__control,
.app-input__field:has(.app-input__clear) .app-input__control,
.app-input__field:has(.app-input__reveal) .app-input__control {
  padding-right: 46px;
}

.app-input__field:has(.app-input__trail):has(.app-input__clear) .app-input__trail {
  right: 44px;
}

.app-input__field:has(.app-input__trail):has(.app-input__clear) .app-input__control {
  padding-right: 80px;
}

.app-input__control:-webkit-autofill,
.app-input__control:-webkit-autofill:hover,
.app-input__control:-webkit-autofill:focus {
  border-radius: 999px;
  box-shadow: 0 0 0 1000px #fff inset;
  -webkit-text-fill-color: #111;
  caret-color: #111;
  transition: background-color 99999s ease-out;
}

.app-input__control::-ms-reveal,
.app-input__control::-ms-clear {
  display: none;
}

.app-input__control::placeholder {
  color: #c0c3c7;
}

.app-input__control:focus-visible {
  outline: none;
}

.app-input__control:disabled {
  color: var(--muted-gray);
}

.app-input__field:has(.app-input__control:disabled) {
  background: #f2f3f3;
}
</style>
