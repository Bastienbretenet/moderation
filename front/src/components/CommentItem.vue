<script setup lang="ts">
import { computed, ref } from 'vue'
import { categoryLabels, rejectionReasonLabels } from '@/api/labels'
import type { Author, Comment, ManualModerationStatus } from '@/api/types'
import ModerationMark from '@/components/ModerationMark.vue'
import ModerationTray from '@/components/ModerationTray.vue'
import StatusPill from '@/components/StatusPill.vue'
import { formatRelativeTime, initials } from '@/format'
import type { ViewMode } from '@/viewMode'

const props = defineProps<{
  comment: Comment
  author: Author | undefined
  viewMode: ViewMode
  isJustPosted: boolean
  moderateManually: (commentId: string, status: ManualModerationStatus, reason: string | null) => Promise<void>
  banAuthor: (authorId: string) => Promise<void>
  unbanAuthor: (authorId: string) => Promise<number>
}>()

const isTrayOpen = ref(false)

const authorName = computed(() => props.comment.authorId ?? 'Anonyme')

const decisionSummary = computed(() => {
  const { rejectionReason, category } = props.comment
  if (rejectionReason === null) {
    return null
  }

  const categoryLabel = category !== null ? (categoryLabels[category] ?? category) : null

  return categoryLabel !== null
    ? `${rejectionReasonLabels[rejectionReason]}, ${categoryLabel}`
    : rejectionReasonLabels[rejectionReason]
})

const bubbleClasses = computed(() => ({
  'border-dashed border-resin/50 bg-resin/5': props.comment.status === 'pending',
  'border-transparent bg-fog': props.comment.status === 'published',
  'border-transparent bg-fog opacity-60': props.comment.status === 'rejected',
}))
</script>

<template>
  <li
    :id="`comment-${comment.id}`"
    class="-mx-2 flex scroll-mt-24 gap-3 rounded-xl px-2 py-1"
    :class="{ 'animate-[just-posted_2.4s_ease-out]': isJustPosted }"
  >
    <div class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-ink/10 text-xs font-semibold" aria-hidden="true">
      {{ initials(authorName) }}
    </div>

    <div class="min-w-0 flex-1">
      <div class="inline-block max-w-full rounded-2xl rounded-tl-sm border px-3.5 py-2.5" :class="bubbleClasses">
        <p class="text-sm font-semibold">
          {{ authorName }}
          <span v-if="viewMode === 'moderator' && author?.banned" class="ml-1 text-xs font-medium text-correction">banni</span>
        </p>
        <p
          class="mt-0.5 font-text text-base leading-relaxed break-words whitespace-pre-line"
          :class="{ 'line-through decoration-correction decoration-2': comment.status === 'rejected' }"
        >
          {{ comment.content }}
        </p>
      </div>

      <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 pl-1 text-xs text-ink-soft">
        <time :datetime="comment.submittedAt">{{ formatRelativeTime(comment.submittedAt) }}</time>
        <template v-if="viewMode === 'moderator'">
          <StatusPill :status="comment.status" />
          <span v-if="decisionSummary">{{ decisionSummary }}</span>
          <button
            type="button"
            class="font-semibold text-ink hover:underline"
            :aria-expanded="isTrayOpen"
            :aria-controls="`tray-${comment.id}`"
            @click="isTrayOpen = !isTrayOpen"
          >
            {{ isTrayOpen ? 'Fermer' : 'Modérer' }}
          </button>
        </template>
      </div>

      <ModerationTray
        v-if="viewMode === 'moderator' && isTrayOpen"
        :id="`tray-${comment.id}`"
        :comment="comment"
        :author="author"
        :moderate-manually="moderateManually"
        :ban-author="banAuthor"
        :unban-author="unbanAuthor"
      />
    </div>

    <ModerationMark
      v-if="viewMode === 'moderator'"
      :key="`${comment.status}-${comment.rejectionReason}`"
      :status="comment.status"
      :rejection-reason="comment.rejectionReason"
      class="mt-1"
    />
  </li>
</template>
