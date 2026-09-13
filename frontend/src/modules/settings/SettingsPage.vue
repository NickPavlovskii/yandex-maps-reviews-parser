<template>
  <div class="settings">
    <h1 class="settings__title">
      Подключение
      <span class="settings__mark">
        <img
          class="settings__mark-line"
          :src="titleStroke"
          alt=""
          aria-hidden="true"
        >
        карточки
      </span>
    </h1>
    <p class="settings__hint">
      Вставьте ссылку на организацию в Яндекс.Картах. После сохранения сервис
      соберёт отзывы, рейтинг и точные счётчики оценок.
    </p>

    <form
      class="connect"
      novalidate
      @submit.prevent="handleSubmit"
    >
      <div class="connect__row">
        <app-input
          v-model.trim="url"
          label="Ссылка на организацию"
          type="url"
          placeholder="https://yandex.ru/maps/org/..."
          required
          :disabled="organizationStore.isSaving"
        >
          <template #lead>
            <img
              :src="linkIcon"
              width="18"
              height="18"
              alt=""
              aria-hidden="true"
            >
          </template>
          <template
            v-if="isUrlValid"
            #trail
          >
            <span
              class="connect__check"
              aria-hidden="true"
            >✓</span>
          </template>
        </app-input>

        <app-button
          type="submit"
          :disabled="organizationStore.isSaving || !isUrlValid"
        >
          {{ submitLabel }}
        </app-button>
      </div>

      <p class="connect__help">
        Допустимы ссылки вида yandex.ru/maps/org/... и короткие yandex.ru/maps/-/...
        ID организации извлекается автоматически.
      </p>
    </form>

    <div
      v-if="bannerText"
      class="banner"
      role="alert"
    >
      <span
        class="banner__icon"
        aria-hidden="true"
      >⚠</span>
      <p>{{ bannerText }}</p>
    </div>

    <section
      v-if="organization"
      class="org"
    >
      <div class="org__head">
        <h2>Подключённая организация</h2>
        <app-spinner
          v-if="isBusy"
          :label="statusLabel"
        />
        <p
          v-else
          :class="['org__status', { 'org__status--failed': isFailed }]"
        >
          <span
            class="org__dot"
            aria-hidden="true"
          />
          {{ statusLabel }}
        </p>
      </div>

      <article class="card">
        <div class="card__top">
          <div>
            <h3 class="card__name">
              {{ organization.name || 'Собираем данные…' }}
            </h3>
            <a
              class="card__maps"
              :href="organization.url"
              target="_blank"
              rel="noreferrer"
            >
              Открыть на Яндекс.Картах
              <span aria-hidden="true">↗</span>
            </a>
          </div>

          <div class="card__actions">
            <app-button
              bg-color="#fff"
              color="#111"
              border
              :disabled="organizationStore.isSaving || isBusy"
              @click="refresh"
            >
              <template #prepend>
                <span aria-hidden="true">↻</span>
              </template>
              Обновить
            </app-button>
            <app-button
              bg-color="#fff"
              color="#111"
              border
              :to="{ name: 'organization' }"
            >
              К отзывам
              <template #append>
                <span aria-hidden="true">→</span>
              </template>
            </app-button>
          </div>
        </div>

        <dl
          v-if="organization.parse_status === 'success'"
          class="stats"
        >
          <div>
            <dt>Средний рейтинг</dt>
            <dd>{{ formatRating(organization.avg_rating) }}</dd>
          </div>
          <div>
            <dt>Оценок</dt>
            <dd>{{ formatNumber(organization.ratings_count) }}</dd>
          </div>
          <div>
            <dt>Отзывов</dt>
            <dd>{{ formatNumber(organization.reviews_count) }}</dd>
          </div>
          <div>
            <dt>Собрано за</dt>
            <dd>{{ formatDuration(organization.last_parse_duration_seconds) }}</dd>
          </div>
        </dl>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import linkIcon from '@/assets/link.svg'
import titleStroke from '@/assets/title-stroke.svg'
import { FORMAT_ERROR, STATUS_LABELS } from '@/constants/organization'
import { organizationStore } from '@/store/organization'
import {
  formatDateTime,
  formatDuration,
  formatNumber,
  formatRating,
  looksLikeYandexMapsUrl,
} from '@/utils/format'

const url = ref('')
const organization = computed(() => organizationStore.organization)
const isUrlValid = computed(() => looksLikeYandexMapsUrl(url.value))
const submitLabel = computed(() => (organizationStore.isSaving ? 'Сохраняем…' : 'Сохранить и собрать'))
const showFormatWarning = computed(() => url.value !== '' && !isUrlValid.value)

const bannerText = computed(() => {
  if (organizationStore.urlError) {
    return organizationStore.urlError
  }

  if (showFormatWarning.value) {
    return FORMAT_ERROR
  }

  return null
})

const isBusy = computed(() =>
  organization.value != null
  && (organization.value.parse_status === 'pending' || organization.value.parse_status === 'in_progress'),
)

const isFailed = computed(() =>
  organization.value != null
  && organization.value.parse_status.startsWith('failed_'),
)

const statusLabel = computed(() => {
  if (!organization.value) {
    return null
  }

  const label = STATUS_LABELS[organization.value.parse_status] ?? organization.value.parse_status

  if (organization.value.parse_status === 'success') {
    return `${label} · ${formatDateTime(organization.value.last_parsed_at)}`
  }

  return label
})

onMounted(async () => {
  await organizationStore.restore()

  if (organizationStore.organization && !url.value) {
    url.value = organizationStore.organization.url
  }
})

async function handleSubmit() {
  if (!isUrlValid.value) {
    return
  }

  try {
    await organizationStore.saveUrl(url.value)
  } catch {
    // urlError already set in store
  }
}

async function refresh() {
  const current = organization.value?.url || url.value

  if (!current) {
    return
  }

  url.value = current
  await handleSubmit()
}
</script>

<style scoped>
.settings {
  width: min(760px, 100%);
  min-width: 0;
  color: #111;
}

.settings__title {
  margin: 0 0 12px;
  font-size: 36px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.2;
}

.settings__mark {
  position: relative;
  z-index: 0;
  display: inline-block;
}

.settings__mark-line {
  position: absolute;
  top: 58%;
  left: 50%;
  z-index: -1;
  width: 118%;
  pointer-events: none;
  transform: translate(-50%, -50%);
}

.settings__hint {
  margin: 0 0 36px;
  max-width: 58ch;
  color: var(--muted-gray);
  font-size: 15px;
  line-height: 1.5;
}

.connect__row {
  display: flex;
  align-items: flex-end;
  gap: 12px;
}

.connect__row :deep(.app-input) {
  flex: 1;
  width: auto;
  min-width: 0;
}

.connect__row :deep(.app-input + .app-input) {
  margin-top: 0;
}

.connect__row :deep(.app-button) {
  flex-shrink: 0;
}

.connect__help {
  margin: 10px 0 0;
  max-width: 62ch;
  color: var(--secondary-text-color);
  font-size: 13px;
  line-height: 1.5;
}

.connect__check {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #2f8f5b;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
}

.banner {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 20px;
  padding: 14px 16px;
  border-radius: 16px;
  background: #f7f1ea;
  color: #6d5a4a;
  font-size: 14px;
  line-height: 1.45;
}

.banner p {
  margin: 0;
}

.banner__icon {
  flex-shrink: 0;
}

.org {
  margin-top: 40px;
}

.org__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}

.org__head h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.org__status {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  color: #6f7378;
  font-size: 13px;
}

.org__status--failed {
  color: #a8402c;
}

.org__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
}

.org__status--failed .org__dot {
  background: #a8402c;
}

.card {
  overflow: hidden;
  background: #fff;
  border-radius: 20px;
}

.card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  padding: 24px 28px;
}

.card__name {
  margin: 0 0 6px;
  font-size: 20px;
  font-weight: 650;
  letter-spacing: -0.03em;
}

.card__maps {
  color: #8b8e93;
  font-size: 14px;
  text-decoration: none;
}

.card__maps:hover {
  color: #111;
}

.card__actions {
  display: flex;
  flex-shrink: 0;
  gap: 8px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 40px;
  padding: 0 16px;
  border-radius: 999px;
  font-size: 14px;
  text-decoration: none;
  cursor: pointer;
}

.btn--ghost {
  border: 1px solid #ececee;
  background: #fff;
  color: #111;
}

.btn:disabled {
  opacity: 0.5;
  cursor: default;
}

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  margin: 0;
  padding: 20px 28px 24px;
  border-top: 1px solid var(--divider-color);
}

.stats dt {
  margin: 0 0 8px;
  color: #8b8e93;
  font-size: 13px;
}

.stats dd {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.04em;
}

@media (max-width: 860px) {
  .settings__title {
    font-size: 28px;
  }

  .connect__row,
  .card__top,
  .org__head {
    flex-direction: column;
    align-items: stretch;
  }

  .connect__row :deep(.app-button),
  .card__actions :deep(.app-button) {
    width: 100%;
  }

  .card__actions {
    flex-direction: column;
    width: 100%;
  }

  .stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    padding: 16px;
  }
}
</style>
