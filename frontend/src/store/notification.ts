import { reactive } from 'vue'

type NoticeType = 'success' | 'error' | 'info'
type Notice = { id: number; type: NoticeType; text: string }

let idCounter = 0

export const notificationStore = reactive({
  messages: [] as Notice[],

  add(type: NoticeType, text: string) {
    const id = idCounter += 1
    this.messages.push({ id, type, text })
    window.setTimeout(() => this.remove(id), 3000)
  },

  remove(id: number) {
    const index = this.messages.findIndex((item) => item.id === id)
    if (index !== -1) {
      this.messages.splice(index, 1)
    }
  },
})
