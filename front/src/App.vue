<script setup lang="ts">
import { computed, ref } from 'vue'
import CommentSection from '@/components/CommentSection.vue'
import ModeratorBar from '@/components/ModeratorBar.vue'
import SocialPost from '@/components/SocialPost.vue'
import { useCommentThread } from '@/composables/useCommentThread'
import type { ViewMode } from '@/viewMode'

const PUBLISHER = 'sudouest'
const SOURCE = 'article-456'

const { comments, total, authors, isLoading, loadError, submitComment, moderateManually, banAuthor, unbanAuthor } =
  useCommentThread(PUBLISHER, SOURCE)

const viewMode = ref<ViewMode>('moderator')
const commentSection = ref<InstanceType<typeof CommentSection> | null>(null)

const visibleCommentCount = computed(() =>
  viewMode.value === 'reader'
    ? comments.value.filter((comment) => comment.status === 'published').length
    : total.value,
)
</script>

<template>
  <div class="min-h-screen">
    <ModeratorBar v-model="viewMode" />
    <main class="mx-auto max-w-2xl space-y-4 px-4 py-8 sm:py-10">
      <SocialPost :comment-count="visibleCommentCount" @comment="commentSection?.focusComposer()" />
      <CommentSection
        ref="commentSection"
        :comments="comments"
        :authors="authors"
        :is-loading="isLoading"
        :load-error="loadError"
        :view-mode="viewMode"
        :submit-comment="submitComment"
        :moderate-manually="moderateManually"
        :ban-author="banAuthor"
        :unban-author="unbanAuthor"
      />
    </main>
  </div>
</template>
