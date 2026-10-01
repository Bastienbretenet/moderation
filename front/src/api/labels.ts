import type { ModerationStatus, RejectionReason, StatusChangeOrigin } from './types'

export const statusLabels: Record<ModerationStatus, string> = {
  pending: 'En attente de modération',
  published: 'Publié',
  rejected: 'Rejeté',
}

export const rejectionReasonLabels: Record<RejectionReason, string> = {
  author_banned: 'auteur banni',
  illegal_content: 'contenu illicite',
  operator: 'décision d’un modérateur',
}

export const originLabels: Record<StatusChangeOrigin, string> = {
  submission: 'Soumission',
  author_ban: 'Rejet automatique, auteur banni',
  author_unban: 'Remise en modération après débannissement',
  llm: 'Modération automatique',
  operator: 'Modérateur',
}

export const categoryLabels: Record<string, string> = {
  child_sexual_content: 'contenu pédopornographique',
  terrorism_apology: 'apologie du terrorisme',
  crime_against_humanity_denial: 'négationnisme',
  threat: 'menace',
  hate_speech: 'incitation à la haine',
  harassment: 'harcèlement',
  privacy_violation: 'atteinte à la vie privée',
  defamation: 'diffamation',
  insult: 'injure',
  other: 'autre contenu illicite',
}
