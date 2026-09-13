<template>
  <div
    :class="['shell', { 'shell--menu-open': menuOpen }]"
  >
    <header class="topbar">
      <router-link
        class="brand"
        :to="{ name: 'settings' }"
        @click="closeMenu"
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

      <nav
        class="nav nav--desktop"
        aria-label="Основное меню"
      >
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

      <button
        type="button"
        aria-controls="mobile-nav"
        :class="['menu-toggle', { 'menu-toggle--open': menuOpen }]"
        :aria-expanded="menuOpen"
        :aria-label="menuToggleLabel"
        @click="menuOpen = !menuOpen"
      >
        <span class="menu-toggle__bar" />
        <span class="menu-toggle__bar" />
        <span class="menu-toggle__bar" />
      </button>
    </header>

    <div
      v-if="menuOpen"
      class="menu-backdrop"
      @click="closeMenu"
    />

    <nav
      id="mobile-nav"
      :class="['nav', 'nav--mobile', { 'nav--mobile-open': menuOpen }]"
      aria-label="Мобильное меню"
    >
      <router-link
        class="nav__link"
        :to="{ name: 'organization' }"
        @click="closeMenu"
      >
        Организация
      </router-link>
      <router-link
        class="nav__link"
        :to="{ name: 'settings' }"
        @click="closeMenu"
      >
        Настройки
      </router-link>
      <app-button
        bg-color="transparent"
        color="#111"
        width="100%"
        :height="44"
        :disabled="loggingOut"
        @click="logout"
      >
        Выйти
      </app-button>
    </nav>

    <main class="shell__main">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authStore } from '@/store/auth'
import otklikMark from '@/assets/otklik-mark.png'

const route = useRoute()
const router = useRouter()
const loggingOut = ref(false)
const menuOpen = ref(false)

const menuToggleLabel = computed(() => (
  menuOpen.value ? 'Закрыть меню' : 'Открыть меню'
))

function closeMenu() {
  menuOpen.value = false
}

watch(() => route.fullPath, closeMenu)

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.removeProperty('overflow')
})

watch(menuOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

async function logout() {
  loggingOut.value = true
  closeMenu()

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
  min-width: 0;
  overflow-x: clip;
  background: #f4f5f6;
  color: #111;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  height: 72px;
  padding: 0 40px;
  background: #fff;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  color: inherit;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: -0.02em;
  text-decoration: none;
}

.brand__mark {
  display: block;
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 10px;
}

.nav--desktop {
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
  white-space: nowrap;
}

.nav__link.router-link-exact-active {
  background: #f3f4f5;
  color: #111;
}

.menu-toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 40px;
  height: 40px;
  padding: 10px;
  border: none;
  border-radius: 12px;
  background: #f3f4f5;
  cursor: pointer;
}

.menu-toggle__bar {
  display: block;
  width: 18px;
  height: 2px;
  border-radius: 999px;
  background: #111;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.menu-toggle--open .menu-toggle__bar:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}

.menu-toggle--open .menu-toggle__bar:nth-child(2) {
  opacity: 0;
}

.menu-toggle--open .menu-toggle__bar:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

.menu-backdrop {
  display: none;
}

.nav--mobile {
  display: none;
}

.shell__main {
  padding: 40px 48px 64px;
}

@media (max-width: 860px) {
  .topbar,
  .shell__main {
    padding-left: 16px;
    padding-right: 16px;
  }

  .nav--desktop {
    display: none;
  }

  .menu-toggle {
    display: flex;
  }

  .menu-backdrop {
    display: block;
    position: fixed;
    inset: 72px 0 0;
    z-index: 20;
    background: rgb(17 17 17 / 28%);
  }

  .nav--mobile {
    display: flex;
    position: fixed;
    top: 72px;
    right: 0;
    left: 0;
    z-index: 25;
    flex-direction: column;
    gap: 4px;
    padding: 12px 16px 20px;
    background: #fff;
    border-bottom: 1px solid #ececee;
    box-shadow: 0 16px 32px rgb(17 17 17 / 8%);
    transform: translateY(-12px);
    opacity: 0;
    pointer-events: none;
    visibility: hidden;
    transition: transform 0.18s ease, opacity 0.18s ease, visibility 0.18s ease;
  }

  .nav--mobile-open {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
    visibility: visible;
  }

  .nav--mobile .nav__link {
    display: block;
    padding: 12px 16px;
    border-radius: 14px;
    font-size: 16px;
  }
}
</style>
