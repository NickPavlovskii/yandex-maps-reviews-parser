<template>
  <div class="login-page">
    <form
      class="login"
      novalidate
      @submit.prevent="handleSubmit"
    >
      <div class="login__brand">
        <img
          class="login__mark"
          alt=""
          width="36"
          height="36"
          :src="otklikMark"
        >
        <span>Отклик</span>
      </div>

      <h1 class="login__title">Вход в кабинет</h1>
      <p class="login__hint">
        Регистрация не требуется — используйте выданные доступы.
      </p>

      <app-input
        v-model="email"
        label="Логин"
        type="email"
        autocomplete="username"
        placeholder="admin@example.com"
        required
        :disabled="isSubmitting"
      />

      <app-input
        v-model="password"
        label="Пароль"
        type="password"
        autocomplete="current-password"
        required
        :disabled="isSubmitting"
      />

      <label class="login__remember">
        <input
          v-model="remember"
          type="checkbox"
          :disabled="isSubmitting"
        >
        <span>Запомнить меня на этом устройстве</span>
      </label>

      <p
        v-if="authError"
        class="login__error"
        role="alert"
      >
        {{ authError }}
      </p>

      <app-button
        type="submit"
        width="100%"
        :disabled="isSubmitting"
        :append-icon="isSubmitting ? '' : arrowRightIcon"
      >
        {{ submitLabel }}
      </app-button>

      <p class="login__note">
        Сессия защищена Sanctum: cookie выдаётся на домен приложения, SPA работает с тем же origin.
      </p>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { isAxiosError } from 'axios'
import { authStore } from '@/store/auth'
import otklikMark from '@/assets/otklik-mark.png'
import arrowRightIcon from '@/assets/arrow-right.svg'

const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const remember = ref(true)
const authError = ref('')
const isSubmitting = ref(false)
const submitLabel = computed(() => (isSubmitting.value ? 'Входим…' : 'Войти'))

async function handleSubmit() {
  authError.value = ''
  isSubmitting.value = true

  try {
    await authStore.login(email.value, password.value, remember.value)

    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    await router.replace(redirect)
  } catch (error) {
    authError.value = loginError(error)
  } finally {
    isSubmitting.value = false
  }
}

function loginError(error: unknown) {
  if (!isAxiosError(error)) {
    return 'Сервис недоступен. Попробуйте ещё раз.'
  }

  if (error.response?.status === 429) {
    return 'Слишком много попыток. Подождите немного и попробуйте снова.'
  }

  if (error.response?.status === 422) {
    const messages = error.response.data?.errors as Record<string, string[]> | undefined
    const first = Object.values(messages ?? {}).flat()[0]

    return first ?? 'Не удалось войти.'
  }

  return 'Сервис недоступен. Попробуйте ещё раз.'
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 72px 24px 48px;
  background: #f4f5f6;
}

.login {
  width: min(400px, 100%);
  color: #111111;
}

.login__brand {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 40px;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: -0.02em;
}

.login__mark {
  display: block;
  width: 36px;
  height: 36px;
  border-radius: 10px;
}

.login__title {
  margin: 0 0 10px;
  font-size: 36px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.1;
}

.login__hint {
  margin: 0 0 36px;
  max-width: 34ch;
  color: var(--muted-gray);
  font-size: 15px;
  line-height: 1.45;
}

.login__remember {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 8px 0 22px;
  font-size: 14px;
  color: #3d4044;
  cursor: pointer;
  user-select: none;
}

.login__remember input {
  width: 16px;
  height: 16px;
  accent-color: #111;
}

.login__error {
  margin: 0 0 16px;
  padding: 10px 14px;
  border-radius: 12px;
  background: #fbebe7;
  color: #a8402c;
  font-size: 14px;
}

.login__note {
  margin: 28px 0 0;
  color: var(--secondary-text-color);
  font-size: 13px;
  line-height: 1.5;
}
</style>
