<template>
  <v-card
    class="app-info-card"
    elevation="0"
  >
    <div class="app-info-card__inner">
      <div
        v-if="icon"
        class="app-info-card__icon"
      >
        <v-icon
          :icon="icon"
          size="28"
        />
      </div>
      <div>
        <div class="app-info-card__title">
          {{ title }}
        </div>
        <div class="app-info-card__value">
          {{ value }}
        </div>
        <div
          v-if="subtitle"
          class="app-info-card__subtitle"
        >
          {{ subtitle }}
        </div>
      </div>
    </div>
  </v-card>
</template>

<script setup lang="ts">
/**
 * Карточка со значением, заголовком и необязательной иконкой.
 *
 * @param {String} title - заголовок карточки
 * @param {String|Number} value - основное значение
 * @param {String} [icon = ''] - имя иконки Vuetify
 * @param {String} [subtitle = ''] - подпись под значением
 */
withDefaults(
  defineProps<{
    title: string
    value: string | number
    icon?: string
    subtitle?: string
  }>(),
  {
    icon: '',
    subtitle: '',
  },
)
</script>

<style scoped lang="scss">
.app-info-card {
  background: #fff;
  border: 1px solid var(--light-platinum);
  border-radius: 16px;
  padding: 20px 24px;
}

.app-info-card__inner {
  display: flex;
  align-items: center;
  gap: 16px;
}

.app-info-card__icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
}

.app-info-card__title {
  font-size: 13px;
  color: var(--light-gray);
}

.app-info-card__value {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--black-color);
}

.app-info-card__subtitle {
  margin-top: 2px;
  font-size: 13px;
  color: var(--light-gray);
}
</style>
