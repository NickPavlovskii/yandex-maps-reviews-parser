export {}

declare module 'vue' {
  export interface GlobalComponents {
    AppAvatar: typeof import('@/components/global/AppAvatar.vue')['default']
    AppButton: typeof import('@/components/global/AppButton.vue')['default']
    AppInfoCard: typeof import('@/components/global/AppInfoCard.vue')['default']
    AppInput: typeof import('@/components/global/AppInput.vue')['default']
    AppPageHeader: typeof import('@/components/global/AppPageHeader.vue')['default']
    AppSelect: typeof import('@/components/global/AppSelect.vue')['default']
    AppSpinner: typeof import('@/components/global/AppSpinner.vue')['default']
  }
}
