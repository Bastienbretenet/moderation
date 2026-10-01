import type {
  Author,
  Comment,
  CommentSearchResult,
  ManualModerationStatus,
  StatusChange,
  UnbannedAuthor,
  Violation,
} from './types'

export class ApiError extends Error {
  constructor(
    message: string,
    readonly violations: Violation[],
  ) {
    super(message)
  }

  violationFor(field: string): string | undefined {
    return this.violations.find((violation) => violation.field === field)?.message
  }
}

interface ErrorBody {
  message?: string
  violations?: Violation[]
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  let response: Response
  try {
    response = await fetch(`/api${path}`, {
      ...init,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    })
  } catch {
    throw new ApiError("L'API est injoignable. Vérifiez que le conteneur php tourne.", [])
  }

  const body: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const errorBody = (body ?? {}) as ErrorBody
    throw new ApiError(errorBody.message ?? `L'API a répondu ${response.status}.`, errorBody.violations ?? [])
  }

  return body as T
}

const encode = encodeURIComponent

export const api = {
  health: () => request<{ status: string }>('/health'),

  searchComments: (publisher: string, source: string, limit: number) =>
    request<CommentSearchResult>(
      `/comments?${new URLSearchParams({ publisher, source, limit: String(limit) })}`,
    ),

  getComment: (commentId: string) => request<Comment>(`/comments/${encode(commentId)}`),

  submitComment: (publisher: string, source: string, content: string, authorId: string) =>
    request<{ id: string }>('/comments', {
      method: 'POST',
      body: JSON.stringify({ publisher, source, content, authorId }),
    }),

  getStatusHistory: (commentId: string) =>
    request<StatusChange[]>(`/comments/${encode(commentId)}/status-history`),

  moderateManually: (commentId: string, status: ManualModerationStatus, reason: string | null) =>
    request<Comment>(`/comments/${encode(commentId)}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status, reason }),
    }),

  getAuthor: (authorId: string) => request<Author>(`/authors/${encode(authorId)}`),

  banAuthor: (authorId: string) => request<Author>(`/authors/${encode(authorId)}/ban`, { method: 'POST' }),

  unbanAuthor: (authorId: string) =>
    request<UnbannedAuthor>(`/authors/${encode(authorId)}/unban`, { method: 'POST' }),
}

export function errorMessage(error: unknown): string {
  return error instanceof ApiError ? error.message : 'Une erreur inattendue est survenue.'
}
