import { onUnmounted, ref } from 'vue'

const NOTICE_DURATION_MS = 4000

export function useTransientNotice() {
  const notice = ref<string | null>(null)
  let hideTimer: number | undefined

  function show(message: string): void {
    window.clearTimeout(hideTimer)
    notice.value = message
    hideTimer = window.setTimeout(() => {
      notice.value = null
    }, NOTICE_DURATION_MS)
  }

  onUnmounted(() => window.clearTimeout(hideTimer))

  return { notice, show }
}
