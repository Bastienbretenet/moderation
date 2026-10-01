<script setup lang="ts">
import { statusLabels } from '@/api/labels'
import type { ModerationStatus } from '@/api/types'

defineProps<{ status: ModerationStatus }>()

const pillClasses: Record<ModerationStatus, string> = {
  pending: 'bg-resin/12 text-resin',
  published: 'bg-pine/12 text-pine',
  rejected: 'bg-correction/10 text-correction',
}

const dotClasses: Record<ModerationStatus, string> = {
  pending: 'bg-resin animate-pulse motion-reduce:animate-none',
  published: 'bg-pine',
  rejected: 'bg-correction',
}
</script>

<template>
  <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium" :class="pillClasses[status]">
    <span class="size-1.5 rounded-full" :class="dotClasses[status]" aria-hidden="true"></span>
    {{ statusLabels[status] }}
  </span>
</template>
