import type { Component } from 'vue'
import AppButton from './AppButton.vue'
import AppInfoCard from './AppInfoCard.vue'
import AppPageHeader from './AppPageHeader.vue'

export const globalComponents = [
  { tag: 'app-button', component: AppButton },
  { tag: 'app-info-card', component: AppInfoCard },
  { tag: 'app-page-header', component: AppPageHeader },
] as const satisfies ReadonlyArray<{ tag: string; component: Component }>
