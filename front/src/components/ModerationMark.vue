<script setup lang="ts">
import { computed } from 'vue'
import { statusLabels } from '@/api/labels'
import type { ModerationStatus, RejectionReason } from '@/api/types'

const props = defineProps<{ status: ModerationStatus; rejectionReason: RejectionReason | null }>()

const strokeColors: Record<ModerationStatus, string> = {
  pending: 'stroke-resin',
  published: 'stroke-pine',
  rejected: 'stroke-correction',
}

const isAuthorBanned = computed(() => props.rejectionReason === 'author_banned')
const label = computed(() => (isAuthorBanned.value ? 'Rejeté, auteur banni' : statusLabels[props.status]))
</script>

<template>
  <svg viewBox="0 0 32 32" class="size-7 shrink-0 fill-none" :class="strokeColors[status]" role="img" :aria-label="label">
    <title>{{ label }}</title>
    <path v-if="status === 'pending'" d="M7 22 L16 9 L25 22" pathLength="1" class="mark-stroke" />
    <path
      v-else-if="status === 'published'"
      d="M6 17 C9 19 11 22 13 25 C16 17 20 11 27 6"
      pathLength="1"
      class="mark-stroke"
    />
    <template v-else-if="isAuthorBanned">
      <path
        d="M16 4.5 C22.5 4.3 27.6 9.4 27.5 15.8 C27.4 22.4 22.3 27.6 15.9 27.5 C9.4 27.4 4.4 22.2 4.5 15.9 C4.6 9.6 9.5 4.7 16.6 4.6"
        pathLength="1"
        class="mark-stroke"
      />
      <path d="M8.2 8.4 C13 13.4 18.6 18.9 23.8 23.9" pathLength="1" class="mark-stroke mark-stroke-delayed" />
    </template>
    <path
      v-else
      d="M4 16 C10 16 18 15 22 14 C27 12 27 6 23 6 C19 6 19 12 22 16 C24 20 26 24 28 27"
      pathLength="1"
      class="mark-stroke"
    />
  </svg>
</template>

<style scoped>
.mark-stroke {
  stroke-width: 2.6;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 1;
  animation: mark-drawn 420ms ease-out both;
}

.mark-stroke-delayed {
  animation-delay: 300ms;
}
</style>
