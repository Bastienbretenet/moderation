<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import type { Author, Comment, ManualModerationStatus, ModerationStatus } from '@/api/types'
import CommentComposer from '@/components/CommentComposer.vue'
import CommentItem from '@/components/CommentItem.vue'
import { useTransientNotice } from '@/composables/useTransientNotice'
import type { ViewMode } from '@/viewMode'

type StatusFilter = ModerationStatus | 'all'

const props = defineProps<{
  comments: Comment[]
  authors: Record<string, Author>
  isLoading: boolean
  loadError: string | null
  viewMode: ViewMode
  submitComment: (authorId: string, content: string) => Promise<string>
  moderateManually: (commentId: string, status: ManualModerationStatus, reason: string | null) => Promise<void>
  banAuthor: (authorId: string) => Promise<void>
  unbanAuthor: (authorId: string) => Promise<number>
}>()

const statusFilter = ref<StatusFilter>('all')
const justPostedCommentId = ref<string | null>(null)
const composer = ref<InstanceType<typeof CommentComposer> | null>(null)
const { notice: readerNotice, show: showReaderNotice } = useTransientNotice()

const statusFilters: { value: StatusFilter; label: string }[] = [
  { value: 'all', label: 'Tous' },
  { value: 'pending', label: 'En attente' },
  { value: 'published', label: 'Publiés' },
  { value: 'rejected', label: 'Rejetés' },
]

function countFor(filter: StatusFilter): number {
  return filter === 'all' ? props.comments.length : props.comments.filter((comment) => comment.status === filter).length
}

const visibleComments = computed(() => {
  if (props.viewMode === 'reader') {
    return props.comments.filter((comment) => comment.status === 'published')
  }

  return statusFilter.value === 'all'
    ? props.comments
    : props.comments.filter((comment) => comment.status === statusFilter.value)
})

const emptyMessage = computed(() => {
  if (props.comments.length === 0) {
    return "Personne n'a encore réagi. Lancez la discussion."
  }

  return props.viewMode === 'reader' ? 'Aucun commentaire publié pour le moment.' : 'Aucun commentaire avec ce statut.'
})

async function submitAndReveal(authorId: string, content: string): Promise<void> {
  const commentId = await props.submitComment(authorId, content)

  if (props.viewMode === 'reader') {
    showReaderNotice('Merci ! Votre commentaire sera visible après sa relecture par la modération.')
    return
  }

  statusFilter.value = 'all'
  justPostedCommentId.value = commentId
  await nextTick()
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  document
    .getElementById(`comment-${commentId}`)
    ?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'center' })
}

function focusComposer(): void {
  composer.value?.focus()
}

defineExpose({ focusComposer })
</script>

<template>
  <section aria-labelledby="comments-title" class="rounded-xl bg-paper px-4 py-5 shadow-[0_1px_2px_rgb(22_35_58/0.08)]">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h2 id="comments-title" class="text-base font-semibold">Commentaires</h2>
      <p v-if="viewMode === 'reader'" class="text-xs text-ink-soft">Vous voyez la page comme un lecteur.</p>
    </div>

    <div class="mt-4">
      <CommentComposer ref="composer" :submit-comment="submitAndReveal" />
      <p v-if="readerNotice" class="mt-2 ml-12 text-xs text-pine" role="status">{{ readerNotice }}</p>
    </div>

    <fieldset v-if="viewMode === 'moderator' && comments.length > 0" class="mt-5 flex flex-wrap gap-1.5">
      <legend class="sr-only">Filtrer par statut</legend>
      <label
        v-for="filter in statusFilters"
        :key="filter.value"
        class="cursor-pointer rounded-full border px-3 py-1 text-xs transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-tide"
        :class="
          statusFilter === filter.value
            ? 'border-ink bg-ink font-semibold text-paper'
            : 'border-rule text-ink-soft hover:border-ink/40 hover:text-ink'
        "
      >
        <input v-model="statusFilter" type="radio" name="status-filter" :value="filter.value" class="sr-only" />
        {{ filter.label }}
        <span class="ml-1 tabular-nums opacity-70">{{ countFor(filter.value) }}</span>
      </label>
    </fieldset>

    <p v-if="loadError" class="mt-4 rounded-md bg-correction/10 px-3 py-2 text-sm text-correction" role="alert">
      {{ loadError }}
    </p>

    <div v-if="isLoading" class="mt-5 space-y-4" aria-busy="true" aria-label="Chargement des commentaires">
      <div v-for="placeholder in 2" :key="placeholder" class="flex gap-3">
        <div class="size-9 rounded-full bg-fog"></div>
        <div class="h-16 flex-1 rounded-2xl rounded-tl-sm bg-fog"></div>
      </div>
    </div>
    <ol v-else-if="visibleComments.length > 0" class="mt-5 space-y-4">
      <CommentItem
        v-for="comment in visibleComments"
        :key="comment.id"
        :comment="comment"
        :author="comment.authorId !== null ? authors[comment.authorId] : undefined"
        :view-mode="viewMode"
        :is-just-posted="comment.id === justPostedCommentId"
        :moderate-manually="moderateManually"
        :ban-author="banAuthor"
        :unban-author="unbanAuthor"
      />
    </ol>
    <p v-else class="mt-5 py-6 text-center text-sm text-ink-soft">{{ emptyMessage }}</p>
  </section>
</template>
