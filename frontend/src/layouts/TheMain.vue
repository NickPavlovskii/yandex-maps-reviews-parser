<template>
  <div class="the-main-layout">
    <v-navigation-drawer v-model="drawer">
      <v-list nav>
        <v-list-item
          title="Главная"
          prepend-icon="mdi-home-outline"
          to="/"
        />
      </v-list>
    </v-navigation-drawer>

    <v-app-bar flat>
      <v-app-bar-nav-icon @click="drawer = !drawer" />
      <v-app-bar-title>App</v-app-bar-title>
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

const drawer = ref(true)
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
</style>
