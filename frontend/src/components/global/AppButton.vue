<template>
  <v-btn
    :class="['app-button', { 'app-button--border': border }]"
    :variant="variant"
    :type="type"
    :disabled="disabled"
    :to="to || undefined"
    :href="href || undefined"
    :target="target || undefined"
    :rel="rel || undefined"
    @click="$emit('click', $event)"
  >
    <template
      v-if="prependIcon && !$slots.prepend"
      #prepend
    >
      <img
        alt=""
        class="app-button__icon app-button__icon--prepend"
        width="16"
        height="16"
        :src="prependIcon"
      >
    </template>

    <template
      v-if="$slots.prepend"
      #prepend
    >
      <slot name="prepend" />
    </template>

    <slot>{{ title }}</slot>

    <template
      v-if="appendIcon && !$slots.append"
      #append
    >
      <img
        alt=""
        class="app-button__icon app-button__icon--append"
        width="16"
        height="16"
        :src="appendIcon"
      >
    </template>

    <template
      v-if="$slots.append"
      #append
    >
      <slot name="append" />
    </template>
  </v-btn>
</template>

<script setup lang="ts">
/**
 * Кнопка с текстом, иконками и опциональной ссылкой.
 *
 * @param {String} [title = ''] - текст, если нет слота по умолчанию
 * @param {String} [prependIcon = ''] - URL иконки слева
 * @param {String} [appendIcon = ''] - URL иконки справа
 * @param {String} [bgColor = '#111'] - цвет фона
 * @param {String} [color = '#fff'] - цвет текста
 * @param {String} [borderColor = '#ececee'] - цвет обводки
 * @param {String|Number} [width = 'auto'] - ширина кнопки
 * @param {String|Number} [height = 48] - высота кнопки
 * @param {Boolean} [border = false] - показывать обводку
 * @param {Boolean} [disabled = false] - заблокировать кнопку
 * @param {String} [type = 'button'] - тип кнопки: button или submit
 * @param {String|Object} [to = ''] - маршрут vue-router
 * @param {String} [href = ''] - внешняя ссылка
 * @param {String} [target = ''] - target ссылки
 * @param {String} [rel = ''] - rel ссылки
 */
import { computed } from 'vue'
import type { RouteLocationRaw } from 'vue-router'

const props = withDefaults(
  defineProps<{
    title?: string
    prependIcon?: string
    appendIcon?: string
    bgColor?: string
    color?: string
    borderColor?: string
    width?: string | number
    height?: string | number
    border?: boolean
    disabled?: boolean
    type?: 'button' | 'submit'
    to?: RouteLocationRaw | string
    href?: string
    target?: string
    rel?: string
  }>(),
  {
    title: '',
    prependIcon: '',
    appendIcon: '',
    bgColor: '#111',
    color: '#fff',
    borderColor: '#ececee',
    width: 'auto',
    height: 48,
    border: false,
    disabled: false,
    type: 'button',
    to: '',
    href: '',
    target: '',
    rel: '',
  },
)

defineEmits<{
  click: [event: MouseEvent]
}>()

const variant = computed(() => (props.border ? 'outlined' : 'flat'))

const widthValue = computed(() => (
  typeof props.width === 'number' ? `${props.width}px` : props.width
))

const heightValue = computed(() => (
  typeof props.height === 'number' ? `${props.height}px` : props.height
))
</script>

<style scoped lang="scss">
.app-button {
  --app-button-bg: v-bind('props.bgColor');
  --app-button-color: v-bind('props.color');
  --app-button-border: v-bind('props.borderColor');
  --app-button-width: v-bind(widthValue);
  --app-button-height: v-bind(heightValue);

  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  min-width: 0 !important;
  width: var(--app-button-width);
  height: var(--app-button-height);
  padding: 0 22px;
  border-radius: 999px !important;
  background-color: var(--app-button-bg) !important;
  color: var(--app-button-color) !important;
  box-shadow: none !important;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none !important;
}

.app-button--border {
  border: 1px solid var(--app-button-border) !important;
}

.app-button:disabled {
  opacity: 0.6;
}

.app-button__icon {
  display: block;
  width: 16px;
  height: 16px;
}

.app-button:not(.app-button--border) .app-button__icon {
  filter: brightness(0) invert(1);
}

.app-button__icon--prepend {
  margin-right: 8px;
}

.app-button__icon--append {
  margin-left: 8px;
}
</style>
