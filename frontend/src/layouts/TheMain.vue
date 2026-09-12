<template>
  <div class="the-main-layout">
    <v-navigation-drawer v-model="drawer">
      <v-list nav>
        <v-list-item
          title="Настройки"
          prepend-icon="mdi-cog-outline"
          to="/"
        />
      </v-list>
    </v-navigation-drawer>

    <v-app-bar flat>
      <v-app-bar-nav-icon @click="drawer = !drawer" />
      <v-app-bar-title>Отзывы</v-app-bar-title>
      <v-spacer />
      <span
        v-if="authStore.user"
        class="the-main-layout__user"
      >
        {{ authStore.user.email }}
      </span>
      <v-btn
        variant="text"
        :loading="loggingOut"
        @click="logout"
      >
        Выйти
      </v-btn>
    </v-app-bar>

    <v-main class="main-fill">
      <div class="content-wrap">
        <router-view />
      </div>
    </v-main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authStore } from '@/store/auth'

const router = useRouter()
const drawer = ref(true)
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
.the-main-layout {
  display: flex;
  flex-direction: column;
  min-height: 100%;
}

.main-fill {
  min-height: 0;
}

.content-wrap {
  padding: 24px;
}

.the-main-layout__user {
  margin-right: 8px;
  color: #64748b;
  font-size: 0.875rem;
}
</style>
