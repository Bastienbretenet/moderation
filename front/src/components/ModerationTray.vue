<script setup lang="ts">
import { ref } from 'vue'
import { errorMessage } from '@/api/client'
import type { Author, Comment, ManualModerationStatus } from '@/api/types'
import StatusHistory from '@/components/StatusHistory.vue'
import { useTransientNotice } from '@/composables/useTransientNotice'
import { formatDateTime } from '@/format'

const props = defineProps<{
  comment: Comment
  author: Author | undefined
  moderateManually: (commentId: string, status: ManualModerationStatus, reason: string | null) => Promise<void>
  banAuthor: (authorId: string) => Promise<void>
  unbanAuthor: (authorId: string) => Promise<number>
}>()

const reason = ref('')
const isBusy = ref(false)
const actionError = ref<string | null>(null)
const { notice, show: showNotice } = useTransientNotice()

async function runAction(action: () => Promise<string>): Promise<void> {
  isBusy.value = true
  actionError.value = null
  try {
    showNotice(await action())
  } catch (error) {
    actionError.value = errorMessage(error)
  } finally {
    isBusy.value = false
  }
}

function moderate(status: ManualModerationStatus): Promise<void> {
  return runAction(async () => {
    const trimmedReason = reason.value.trim()
    await props.moderateManually(props.comment.id, status, trimmedReason === '' ? null : trimmedReason)
    reason.value = ''

    return status === 'published' ? 'Commentaire publié.' : 'Commentaire rejeté.'
  })
}

function toggleBan(authorId: string): Promise<void> {
  return runAction(async () => {
    if (props.author?.banned) {
      const resubmittedCommentCount = await props.unbanAuthor(authorId)

      return resubmittedCommentCount === 0
        ? 'Auteur débanni.'
        : `Auteur débanni : ${resubmittedCommentCount} commentaire(s) renvoyé(s) en modération.`
    }

    await props.banAuthor(authorId)

    return 'Auteur banni : ses prochains commentaires seront rejetés.'
  })
}
</script>

<template>
  <div class="mt-2 divide-y divide-rule rounded-lg border border-rule bg-paper text-sm">
    <section class="px-3.5 py-3" aria-label="Décision">
      <p v-if="comment.moderationExplanation" class="font-text text-base leading-snug italic">
        {{ comment.moderationExplanation }}
      </p>
      <p v-else class="text-ink-soft">
        {{ comment.status === 'pending' ? 'Décision de la modération automatique en cours.' : 'Aucune explication fournie.' }}
      </p>
      <p v-if="comment.moderatedAt" class="mt-1 text-xs text-ink-soft">
        Décision du <time :datetime="comment.moderatedAt">{{ formatDateTime(comment.moderatedAt) }}</time>
      </p>
    </section>

    <section class="px-3.5 py-3" aria-label="Décision manuelle">
      <label :for="`reason-${comment.id}`" class="text-xs font-medium text-ink-soft">Motif de votre décision, facultatif</label>
      <div class="mt-1.5 flex flex-wrap gap-2">
        <input
          :id="`reason-${comment.id}`"
          v-model="reason"
          type="text"
          maxlength="1000"
          placeholder="Ex. critique légitime d'un élu"
          class="min-w-0 flex-1 basis-56 rounded-md border border-rule bg-paper px-2.5 py-1.5 focus-visible:border-tide focus-visible:outline-none"
        />
        <button
          type="button"
          :disabled="isBusy || comment.status === 'published'"
          class="rounded-md bg-pine px-3 py-1.5 font-medium text-paper hover:bg-pine/90 disabled:cursor-not-allowed disabled:bg-ink/15 disabled:text-ink-soft"
          @click="moderate('published')"
        >
          Publier
        </button>
        <button
          type="button"
          :disabled="isBusy || comment.status === 'rejected'"
          class="rounded-md bg-correction px-3 py-1.5 font-medium text-paper hover:bg-correction/90 disabled:cursor-not-allowed disabled:bg-ink/15 disabled:text-ink-soft"
          @click="moderate('rejected')"
        >
          Rejeter
        </button>
      </div>
    </section>

    <section class="flex flex-wrap items-center justify-between gap-2 px-3.5 py-3" aria-label="Auteur">
      <p v-if="comment.authorId" class="text-xs text-ink-soft">
        Identifiant <span class="font-medium text-ink">{{ comment.authorId }}</span>
        <span v-if="author?.banned" class="ml-1.5 rounded-full bg-correction/10 px-2 py-0.5 font-medium text-correction">
          banni
        </span>
      </p>
      <p v-else class="text-xs text-ink-soft">Commentaire anonyme, aucun auteur à bannir.</p>
      <button
        v-if="comment.authorId"
        type="button"
        :disabled="isBusy"
        class="rounded-md border px-3 py-1.5 text-xs font-medium disabled:opacity-50"
        :class="author?.banned ? 'border-ink/30 hover:border-ink' : 'border-correction/40 text-correction hover:border-correction'"
        @click="toggleBan(comment.authorId)"
      >
        {{ author?.banned ? "Débannir l'auteur" : "Bannir l'auteur" }}
      </button>
    </section>

    <p v-if="actionError" class="px-3.5 py-2 text-xs text-correction" role="alert">{{ actionError }}</p>
    <p v-else-if="notice" class="px-3.5 py-2 text-xs text-pine" role="status">{{ notice }}</p>

    <section class="px-3.5 py-3" aria-label="Historique">
      <p class="mb-2 text-xs font-medium text-ink-soft">Historique</p>
      <StatusHistory :key="`${comment.status}-${comment.moderatedAt}`" :comment-id="comment.id" />
    </section>
  </div>
</template>
