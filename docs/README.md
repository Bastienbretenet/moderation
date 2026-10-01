# Sud Ouest — Modération des commentaires

**Une session de dev, de 9h45 à 12h15**

Lancement : `make build` +  `make up`

**Choix**

- Modération **asynchrone** (Messenger, transport Doctrine) : un appel LLM est lent et faillible, il ne doit pas bloquer l'accusé de réception. Le **ban** est vérifié en synchrone : rejet immédiat, sans appel LLM.
- Port `Moderator` dans le domaine, implémenté par `Gpt4oMiniModerator` via OpenRouter (JSON schema strict, température 0, un prompt par modèle). Un autre modèle ou un classifieur dédié (Jev AI), moins cher et plus rapide, s'ajouterait comme une nouvelle implémentation.
- Éditeurs et sources en strings (slug validé) : ce sont des référentiels externes dont ce service n'est pas la source de vérité.
- Un rejet porte `rejectionReason` (`author_banned` | `illegal_content` | `operator`) et, pour `illegal_content`, une catégorie parmi 10 infractions du droit français ([moderation.md](moderation.md), sources recherchées avec un LLM).

**Hypothèses** : un auteur inconnu n'est pas banni; le ban s'applique à la soumission; après un échec définitif du LLM, le commentaire reste `pending` dans le failure transport.

**Limites et suite** : les tests n'appellent jamais le LLM (stub à marqueurs `[moderation:<catégorie>]`) ; un appel coûte environ 0,0002 $, des tests réels demanderaient une clé à budget plafonné. Extensions traitées : modération manuelle historisée ([manual-moderation.md](manual-moderation.md)), car elle corrige les erreurs du LLM, et ban/déban avec remodération des commentaires rejetés pour ban ([author-ban.md](author-ban.md)). Ensuite : traçabilité des appels LLM, webhook Facebook.

**Usage de l'IA** : code écrit avec Claude Code, étape par étape (voir les commits). Arbitrages :

- port de modération absent de la première itération, ajouté ensuite ; enum de catégories plutôt qu'une string libre ; éditeurs en strings plutôt qu'en entité ;
- stub à mots-clés réellement illicites rejeté au profit de marqueurs (aucun contenu haineux dans le dépôt) ;
- le Moderator n'était pas généraliste (le modèle LLM était en variable d'environnement) je voulais une gestion plus précise.
- les tests ont révélé deux erreurs de l'IA (UUID stocké en texte sous SQLite, 404 par défaut de `MapQueryString`) ; une config Nelmio écrasée sans lecture préalable a été restaurée depuis Git.
