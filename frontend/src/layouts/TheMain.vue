<template>
  <div class="shell">
    <header class="topbar">
      <router-link
        class="brand"
        :to="{ name: 'settings' }"
      >
        <img
          class="brand__mark"
          alt=""
          width="36"
          height="36"
          :src="otklikMark"
        >
        <span>Отклик</span>
      </router-link>

      <nav class="nav">
        <router-link
          class="nav__link"
          :to="{ name: 'organization' }"
        >
          Организация
        </router-link>
        <router-link
          class="nav__link"
          :to="{ name: 'settings' }"
        >
          Настройки
        </router-link>
        <app-button
          bg-color="transparent"
          color="#b0b3b8"
          :height="36"
          :disabled="loggingOut"
          @click="logout"
        >
          Выйти
        </app-button>
      </nav>
    </header>

    <main class="shell__main">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authStore } from '@/store/auth'
import otklikMark from '@/assets/otklik-mark.png'

const router = useRouter()
const loggingOut = ref(false)

async function logout() {
  loggingOut.value = true

  try {
    await authStore.logout()
    await router.replace({ name: 'login' })
  } finally {
    loggingOut.value = false
  }
}
</script>

<style scoped>
.shell {
  min-height: 100vh;
  background: #f4f5f6;
  color: #111;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  padding: 0 40px;
  background: #fff;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  color: inherit;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: -0.02em;
  text-decoration: none;
}

.brand__mark {
  display: block;
  width: 36px;
  height: 36px;
  border-radius: 10px;
}

.nav {
  display: flex;
  align-items: center;
  gap: 4px;
}

.nav__link {
  padding: 8px 16px;
  border-radius: 999px;
  color: #6f7378;
  font-size: 14px;
  text-decoration: none;
}

.nav__link.router-link-exact-active {
  background: #f3f4f5;
  color: #111;
}

.nav__logout {
  margin-left: 8px;
  padding: 8px 12px;
  border: none;
  background: none;
  color: #b0b3b8;
  font-size: 13px;
  cursor: pointer;
}

.nav__logout:disabled {
  cursor: default;
}

.shell__main {
  padding: 40px 48px 64px;
}

@media (max-width: 720px) {
  .topbar,
  .shell__main {
    padding-left: 20px;
    padding-right: 20px;
  }
}
</style>
