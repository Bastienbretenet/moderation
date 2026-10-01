# Bannissement des auteurs

Un opérateur bannit ou débannit un auteur ; les nouveaux commentaires d'un auteur banni sont rejetés sans passer par le LLM.

## Choix

- **Deux actions explicites** (`POST …/ban`, `POST …/unban`) plutôt qu'un état à écrire : chacune a ses effets propres, le débannissement surtout.
- **Bannir un auteur inconnu le crée** : on peut bannir avant le premier commentaire (un compte déjà repéré ailleurs). Débannir un inconnu répond `404`.
- **Bannir un banni ou débannir un non-banni = `409`**, comme une modération manuelle vers le statut actuel : l'action n'a pas d'effet et signale une vue périmée.
- **Un simple `bannedAt`** sur `Author`, sans historique des bans : l'historique utile est celui des commentaires, qui garde les rejets pour ban et les remodérations.
- **Le ban ne vaut que pour l'avenir** : les commentaires antérieurs ne sont pas touchés, ni les publiés ni les `pending` (soumis avant le ban, ils passent par le LLM). Un commentaire problématique se traite par la modération manuelle.
- **Au débannissement, les commentaires rejetés pour ban (`author_banned`) repassent en modération LLM** : ils n'ont jamais été jugés sur leur contenu. Les publier d'office serait dangereux, les laisser rejetés pénaliserait un ban levé par erreur. Ils redeviennent `pending` (origine `author_unban` dans l'historique) et le flux asynchrone habituel s'applique.
- **Les autres rejets restent intacts** : `illegal_content` (LLM) et `operator` sont de vraies décisions sur le contenu. La règle vit dans l'agrégat : `Comment::resubmitForModeration()` refuse tout autre motif.

## Limites

- Pas d'authentification : endpoints ouverts, identité de l'opérateur non enregistrée.
- Un commentaire remodéré peut paraître longtemps après sa soumission, sur un article qui n'est plus d'actualité.
- Débannissement synchrone : un auteur avec des milliers de commentaires rejetés rendrait la requête longue ; il faudrait alors traiter la remise en modération par lots, en asynchrone.
