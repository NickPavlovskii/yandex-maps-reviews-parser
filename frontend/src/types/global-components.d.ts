export {}

declare module 'vue' {
  export interface GlobalComponents {
    AppButton: typeof import('@/components/global/AppButton.vue')['default']
    AppInfoCard: typeof import('@/components/global/AppInfoCard.vue')['default']
    AppPageHeader: typeof import('@/components/global/AppPageHeader.vue')['default']
  }
}
