<template>
  <div
    ref="root"
    :class="['app-select', { 'app-select--open': open }]"
  >
    <button
      class="app-select__control"
      type="button"
      role="combobox"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="listbox"
      :aria-controls="listId"
      :aria-activedescendant="open ? optionId(highlighted) : undefined"
      @click="open = !open"
      @keydown="onKeydown"
    >
      <span>{{ currentLabel }}</span>
      <img
        class="app-select__chevron"
        :src="chevronIcon"
        width="16"
        height="16"
        alt=""
        aria-hidden="true"
      >
    </button>

    <ul
      v-if="open"
      :id="listId"
      class="app-select__list"
      role="listbox"
    >
      <li
        v-for="(option, index) in options"
        :id="optionId(index)"
        :key="String(option.value)"
        role="option"
        :aria-selected="option.value === model"
      >
        <button
          type="button"
          tabindex="-1"
          :class="['app-select__option', {
            'app-select__option--active': option.value === model,
            'app-select__option--highlight': index === highlighted,
          }]"
          @click="choose(option.value)"
        >
          {{ option.label }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts" generic="T extends string | number">
/**
 * Выпадающий список с клавиатурной навигацией.
 *
 * @param {String|Number} [modelValue] - выбранное значение
 * @param {Array} options - варианты списка: value и label
 * @param {Boolean} [disabled = false] - заблокировать список
 * @param {String} [placeholder = 'Выберите'] - текст, пока ничего не выбрано
 */
import { computed, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import chevronIcon from '@/assets/chevron.svg'

const props = withDefaults(
  defineProps<{
    options: Array<{ value: T; label: string }>
    disabled?: boolean
    placeholder?: string
  }>(),
  {
    disabled: false,
    placeholder: 'Выберите',
  },
)

const model = defineModel<T>()
const open = ref(false)
const highlighted = ref(0)
const root = ref<HTMLElement | null>(null)
const listId = useId()

const currentLabel = computed(() => (
  props.options.find((option) => option.value === model.value)?.label ?? props.placeholder
))

function optionId(index: number) {
  return `${listId}-option-${index}`
}

function highlightCurrent() {
  const index = props.options.findIndex((option) => option.value === model.value)
  highlighted.value = index >= 0 ? index : 0
}

function choose(value: T) {
  model.value = value
  open.value = false
}

function onKeydown(event: KeyboardEvent) {
  if (props.disabled) {
    return
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault()

    if (!open.value) {
      open.value = true
      return
    }

    highlighted.value = Math.min(highlighted.value + 1, props.options.length - 1)
    return
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault()

    if (!open.value) {
      open.value = true
      return
    }

    highlighted.value = Math.max(highlighted.value - 1, 0)
    return
  }

  if ((event.key === 'Enter' || event.key === ' ') && open.value) {
    event.preventDefault()
    const option = props.options[highlighted.value]

    if (option) {
      choose(option.value)
    }
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    highlightCurrent()
  }
})

function onDocumentClick(event: MouseEvent) {
  if (!root.value?.contains(event.target as Node)) {
    open.value = false
  }
}

function onEscape(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onEscape)
})
</script>

<style scoped>
.app-select {
  position: relative;
  display: inline-block;
  min-width: 200px;
}

.app-select__control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  height: 48px;
  padding: 0 18px;
  border: none;
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 0 0 1px #ececee;
  color: #111;
  font: inherit;
  font-size: 14px;
  white-space: nowrap;
  cursor: pointer;
}

.app-select--open .app-select__control,
.app-select__control:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px #111;
}

.app-select__control:disabled {
  background: #f2f3f3;
  color: var(--muted-gray);
  cursor: default;
}

.app-select__chevron {
  flex-shrink: 0;
  display: block;
  transition: transform 0.16s ease;
}

.app-select--open .app-select__chevron {
  transform: rotate(180deg);
}

.app-select__list {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  left: 0;
  z-index: 8;
  margin: 0;
  padding: 6px;
  list-style: none;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgb(17 17 17 / 10%), 0 0 0 1px #ececee;
}

.app-select__option {
  display: block;
  width: 100%;
  padding: 10px 12px;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: #111;
  font: inherit;
  font-size: 14px;
  text-align: left;
  cursor: pointer;
}

.app-select__option:hover {
  background: #f4f5f6;
}

.app-select__option--active {
  background: #111;
  color: #fff;
}

.app-select__option--highlight:not(.app-select__option--active) {
  background: #f4f5f6;
}

.app-select__option--active:hover {
  background: #111;
}
</style>
