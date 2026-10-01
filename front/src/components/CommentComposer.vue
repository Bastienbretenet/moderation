<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { ApiError, errorMessage } from '@/api/client'
import { initials } from '@/format'

const CONTENT_MAX_LENGTH = 5000
const COUNTER_THRESHOLD = 4500

const props = defineProps<{ submitComment: (authorId: string, content: string) => Promise<void> }>()

const authorName = ref('')
const content = ref('')
const isSubmitting = ref(false)
const submitError = ref<ApiError | string | null>(null)
const contentField = ref<HTMLTextAreaElement | null>(null)

const canSubmit = computed(() => !isSubmitting.value && authorName.value.trim() !== '' && content.value.trim() !== '')
const authorInitials = computed(() => initials(authorName.value) || '?')

const generalError = computed(() => {
  if (submitError.value === null) {
    return null
  }

  if (submitError.value instanceof ApiError) {
    return submitError.value.violations.length > 0 ? null : submitError.value.message
  }

  return submitError.value
})

function fieldError(field: string): string | undefined {
  return submitError.value instanceof ApiError ? submitError.value.violationFor(field) : undefined
}

function resizeContentField(): void {
  const field = contentField.value
  if (field === null) {
    return
  }

  field.style.height = 'auto'
  field.style.height = `${field.scrollHeight}px`
}

async function submit(): Promise<void> {
  if (!canSubmit.value) {
    return
  }

  isSubmitting.value = true
  submitError.value = null
  try {
    await props.submitComment(authorName.value.trim(), content.value.trim())
    content.value = ''
    await nextTick()
    resizeContentField()
  } catch (error) {
    submitError.value = error instanceof ApiError ? error : errorMessage(error)
  } finally {
    isSubmitting.value = false
  }
}

function focus(): void {
  contentField.value?.focus()
}

defineExpose({ focus })
</script>

<template>
  <form class="flex gap-3" novalidate @submit.prevent="submit">
    <div
      class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-full bg-tide/15 text-xs font-semibold text-tide"
      aria-hidden="true"
    >
      {{ authorInitials }}
    </div>

    <div class="min-w-0 flex-1">
      <div
        class="rounded-2xl rounded-tl-sm border bg-fog transition-colors focus-within:border-tide focus-within:bg-paper"
        :class="fieldError('content') || fieldError('authorId') ? 'border-correction' : 'border-transparent'"
      >
        <label for="author-name" class="sr-only">Votre nom</label>
        <input
          id="author-name"
          v-model="authorName"
          type="text"
          autocomplete="nickname"
          maxlength="255"
          placeholder="Votre nom"
          :aria-invalid="fieldError('authorId') !== undefined"
          class="w-full bg-transparent px-3.5 pt-2.5 text-sm font-semibold placeholder:font-normal placeholder:text-ink-soft focus:outline-none"
        />
        <label for="comment-content" class="sr-only">Votre commentaire</label>
        <textarea
          id="comment-content"
          ref="contentField"
          v-model="content"
          rows="2"
          :maxlength="CONTENT_MAX_LENGTH"
          placeholder="Écrire un commentaire…"
          :aria-invalid="fieldError('content') !== undefined"
          class="block max-h-72 w-full resize-none bg-transparent px-3.5 pt-1 pb-2.5 font-text text-base leading-relaxed placeholder:text-ink-soft focus:outline-none"
          @input="resizeContentField"
          @keydown.enter.ctrl.prevent="submit"
          @keydown.enter.meta.prevent="submit"
        ></textarea>
      </div>

      <p v-if="fieldError('authorId')" class="mt-1 pl-1 text-xs text-correction">{{ fieldError('authorId') }}</p>
      <p v-if="fieldError('content')" class="mt-1 pl-1 text-xs text-correction">{{ fieldError('content') }}</p>
      <p v-if="generalError" class="mt-1 pl-1 text-xs text-correction" role="alert">{{ generalError }}</p>

      <div class="mt-2 flex items-center justify-between gap-3 pl-1">
        <p class="text-xs text-ink-soft">
          <template v-if="content.length >= COUNTER_THRESHOLD">{{ content.length }} / {{ CONTENT_MAX_LENGTH }} caractères</template>
          <template v-else>Ctrl + Entrée pour envoyer</template>
        </p>
        <button
          type="submit"
          :disabled="!canSubmit"
          class="shrink-0 rounded-full bg-tide px-4 py-1.5 text-sm font-semibold text-paper hover:bg-tide/90 disabled:cursor-not-allowed disabled:bg-ink/15 disabled:text-ink-soft"
        >
          {{ isSubmitting ? 'Envoi…' : 'Commenter' }}
        </button>
      </div>
    </div>
  </form>
</template>
