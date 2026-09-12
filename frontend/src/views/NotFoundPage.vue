<template>
  <div class="not-found">
    <header class="not-found__topbar">
      <router-link
        class="not-found__brand"
        :to="{ name: 'settings' }"
      >
        <img
          class="not-found__mark"
          alt=""
          width="36"
          height="36"
          :src="otklikMark"
        >
        <span>Отклик</span>
      </router-link>
    </header>

    <main class="not-found__main">
      <p class="not-found__code">Ошибка 404</p>
      <h1 class="not-found__title">Такой страницы нет</h1>
      <p class="not-found__text">
        Возможно, адрес введён с опечаткой или карточка организации
        была отключена от кабинета. Проверьте ссылку или вернитесь к отзывам.
      </p>

      <div class="not-found__actions">
        <app-button :to="{ name: 'organization' }">
          <template #prepend>
            <span aria-hidden="true">←</span>
          </template>
          Вернуться к отзывам
        </app-button>
        <app-button
          bg-color="#fff"
          color="#111"
          border
          :disabled="leaving"
          @click="goToLogin"
        >
          На экран входа
        </app-button>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import otklikMark from '@/assets/otklik-mark.png'
import { authStore } from '@/store/auth'

const router = useRouter()
const leaving = ref(false)

async function goToLogin() {
  leaving.value = true

  try {
    if (authStore.user) {
      await authStore.logout()
    }

    await router.push({ name: 'login' })
  } finally {
    leaving.value = false
  }
}
</script>

<style scoped>
.not-found {
  min-height: 100vh;
  background: #f4f5f6;
  color: #111;
}

.not-found__topbar {
  display: flex;
  align-items: center;
  height: 72px;
  padding: 0 40px;
  background: #fff;
}

.not-found__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  color: inherit;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: -0.02em;
  text-decoration: none;
}

.not-found__mark {
  display: block;
  width: 36px;
  height: 36px;
  border-radius: 10px;
}

.not-found__main {
  width: min(640px, 100%);
  padding: 72px 48px 64px;
}

.not-found__code {
  margin: 0 0 12px;
  color: var(--secondary-text-color);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.not-found__title {
  margin: 0 0 16px;
  font-size: 36px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.1;
}

.not-found__text {
  margin: 0 0 32px;
  max-width: 46ch;
  color: var(--muted-gray);
  font-size: 15px;
  line-height: 1.5;
}

.not-found__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

@media (max-width: 720px) {
  .not-found__topbar,
  .not-found__main {
    padding-left: 20px;
    padding-right: 20px;
  }
}
</style>
