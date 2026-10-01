export type ModerationStatus = 'pending' | 'published' | 'rejected'

export type ManualModerationStatus = Exclude<ModerationStatus, 'pending'>

export type RejectionReason = 'author_banned' | 'illegal_content' | 'operator'

export type StatusChangeOrigin = 'submission' | 'author_ban' | 'author_unban' | 'llm' | 'operator'

export interface Comment {
  id: string
  publisher: string
  source: string
  authorId: string | null
  content: string
  status: ModerationStatus
  rejectionReason: RejectionReason | null
  category: string | null
  moderationExplanation: string | null
  submittedAt: string
  moderatedAt: string | null
}

export interface CommentSearchResult {
  items: Comment[]
  total: number
  page: number
  limit: number
}

export interface StatusChange {
  previousStatus: ModerationStatus | null
  newStatus: ModerationStatus
  origin: StatusChangeOrigin
  reason: string | null
  changedAt: string
}

export interface Author {
  authorId: string
  banned: boolean
  bannedAt: string | null
}

export interface UnbannedAuthor {
  author: Author
  resubmittedCommentCount: number
}

export interface Violation {
  field: string
  message: string
}
