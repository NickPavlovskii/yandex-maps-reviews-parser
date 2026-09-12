import type { Component } from 'vue'
import AppAvatar from './AppAvatar.vue'
import AppButton from './AppButton.vue'
import AppInfoCard from './AppInfoCard.vue'
import AppInput from './AppInput.vue'
import AppPageHeader from './AppPageHeader.vue'
import AppSelect from './AppSelect.vue'
import AppSpinner from './AppSpinner.vue'

export const globalComponents = [
  { tag: 'app-avatar', component: AppAvatar },
  { tag: 'app-button', component: AppButton },
  { tag: 'app-info-card', component: AppInfoCard },
  { tag: 'app-input', component: AppInput },
  { tag: 'app-page-header', component: AppPageHeader },
  { tag: 'app-select', component: AppSelect },
  { tag: 'app-spinner', component: AppSpinner },
] as const satisfies ReadonlyArray<{ tag: string; component: Component }>
