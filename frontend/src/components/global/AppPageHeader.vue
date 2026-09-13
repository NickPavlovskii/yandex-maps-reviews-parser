<template>
  <v-card
    elevation="0"
    class="app-page-header"
  >
    <div class="app-page-header__top">
      <div class="app-page-header__inner">
        <div
          v-if="icon"
          class="app-page-header__icon"
        >
          <v-icon
            :icon="icon"
            size="36"
          />
        </div>
        <div>
          <h1 class="app-page-header__title">
            {{ title }}
          </h1>
          <p
            v-if="subtitle"
            class="app-page-header__subtitle"
          >
            {{ subtitle }}
          </p>
        </div>
      </div>
      <div
        v-if="$slots.right"
        class="app-page-header__right"
      >
        <slot name="right" />
      </div>
    </div>
    <div
      v-if="$slots.default"
      class="app-page-header__extra"
    >
      <slot />
    </div>
  </v-card>
</template>

<script setup lang="ts">
/**
 * Шапка страницы с заголовком, подзаголовком и слотом справа.
 *
 * @param {String} title - заголовок страницы
 * @param {String} [subtitle = ''] - подзаголовок
 * @param {String} [icon = ''] - имя иконки Vuetify
 */
withDefaults(
  defineProps<{
    title: string
    subtitle?: string
    icon?: string
  }>(),
  {
    subtitle: '',
    icon: '',
  },
)
</script>

<style scoped lang="scss">
.app-page-header {
  background: #fff;
  border: 1px solid var(--light-platinum);
  border-radius: 16px;
  padding: 20px 24px;
  margin-bottom: 8px;
  box-shadow: 0 1px 3px rgb(0 0 0 / 6%);
}

.app-page-header__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.app-page-header__inner {
  display: flex;
  align-items: center;
  gap: 16px;
}

.app-page-header__icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
}

.app-page-header__title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--black-color);
}

.app-page-header__subtitle {
  margin: 4px 0 0;
  color: var(--light-gray);
}

.app-page-header__extra {
  margin-top: 16px;
}
</style>
