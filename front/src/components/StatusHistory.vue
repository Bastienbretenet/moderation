<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import { originLabels, statusLabels } from '@/api/labels'
import type { ModerationStatus, StatusChange } from '@/api/types'
import { formatDateTime } from '@/format'

const props = defineProps<{ commentId: string }>()

const statusChanges = ref<StatusChange[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const dotClasses: Record<ModerationStatus, string> = {
  pending: 'bg-resin',
  published: 'bg-pine',
  rejected: 'bg-correction',
}

onMounted(async () => {
  try {
    statusChanges.value = await api.getStatusHistory(props.commentId)
  } catch (error) {
    loadError.value = errorMessage(error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <p v-if="isLoading" class="text-xs text-ink-soft">Chargement de l'historique…</p>
  <p v-else-if="loadError" class="text-xs text-correction" role="alert">{{ loadError }}</p>
  <ol v-else class="relative ml-1 space-y-3 border-l border-rule pl-4">
    <li v-for="statusChange in statusChanges" :key="statusChange.changedAt + statusChange.origin" class="relative text-xs">
      <span
        class="absolute top-1 -left-[1.3rem] size-2 rounded-full ring-2 ring-paper"
        :class="dotClasses[statusChange.newStatus]"
        aria-hidden="true"
      ></span>
      <p class="font-medium">{{ originLabels[statusChange.origin] }}</p>
      <p class="text-ink-soft">
        <time :datetime="statusChange.changedAt">{{ formatDateTime(statusChange.changedAt) }}</time>,
        <template v-if="statusChange.previousStatus">
          {{ statusLabels[statusChange.previousStatus].toLowerCase() }} puis {{ statusLabels[statusChange.newStatus].toLowerCase() }}
        </template>
        <template v-else>{{ statusLabels[statusChange.newStatus].toLowerCase() }}</template>
      </p>
      <p v-if="statusChange.reason" class="mt-0.5 font-text text-sm text-ink italic">{{ statusChange.reason }}</p>
    </li>
  </ol>
</template>
