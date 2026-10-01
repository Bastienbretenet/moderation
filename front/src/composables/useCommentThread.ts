import { onMounted, onUnmounted, ref } from 'vue'
import { api, errorMessage } from '@/api/client'
import type { Author, Comment, ManualModerationStatus } from '@/api/types'

const THREAD_SIZE = 100
const PENDING_REFRESH_INTERVAL_MS = 2000

export function useCommentThread(publisher: string, source: string) {
  const comments = ref<Comment[]>([])
  const total = ref(0)
  const authors = ref<Record<string, Author>>({})
  const isLoading = ref(true)
  const loadError = ref<string | null>(null)

  async function load(): Promise<void> {
    try {
      const searchResult = await api.searchComments(publisher, source, THREAD_SIZE)
      comments.value = [...searchResult.items].reverse()
      total.value = searchResult.total
      await loadAuthors()
      loadError.value = null
    } catch (error) {
      loadError.value = errorMessage(error)
    } finally {
      isLoading.value = false
    }
  }

  async function loadAuthors(): Promise<void> {
    const authorIds = [
      ...new Set(comments.value.map((comment) => comment.authorId).filter((authorId) => authorId !== null)),
    ]
    const loadedAuthors = await Promise.all(authorIds.map((authorId) => api.getAuthor(authorId)))
    authors.value = Object.fromEntries(loadedAuthors.map((author) => [author.authorId, author]))
  }

  function replaceComment(updatedComment: Comment): void {
    comments.value = comments.value.map((comment) => (comment.id === updatedComment.id ? updatedComment : comment))
  }

  async function submitComment(authorId: string, content: string): Promise<string> {
    const { id } = await api.submitComment(publisher, source, content, authorId)
    comments.value = [...comments.value, await api.getComment(id)]
    total.value += 1
    await loadAuthors()

    return id
  }

  async function refreshPendingComments(): Promise<void> {
    const pendingComments = comments.value.filter((comment) => comment.status === 'pending')
    const refreshedComments = await Promise.all(pendingComments.map((comment) => api.getComment(comment.id)))
    refreshedComments.forEach(replaceComment)
  }

  async function moderateManually(commentId: string, status: ManualModerationStatus, reason: string | null): Promise<void> {
    replaceComment(await api.moderateManually(commentId, status, reason))
  }

  async function banAuthor(authorId: string): Promise<void> {
    await api.banAuthor(authorId)
    await load()
  }

  async function unbanAuthor(authorId: string): Promise<number> {
    const unbannedAuthor = await api.unbanAuthor(authorId)
    await load()

    return unbannedAuthor.resubmittedCommentCount
  }

  let pendingRefreshTimer: number | undefined

  onMounted(() => {
    void load()
    pendingRefreshTimer = window.setInterval(() => {
      refreshPendingComments().catch((error: unknown) => {
        loadError.value = errorMessage(error)
      })
    }, PENDING_REFRESH_INTERVAL_MS)
  })

  onUnmounted(() => window.clearInterval(pendingRefreshTimer))

  return {
    comments,
    total,
    authors,
    isLoading,
    loadError,
    submitComment,
    moderateManually,
    banAuthor,
    unbanAuthor,
  }
}
