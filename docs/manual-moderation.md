# Modération manuelle

Un opérateur décide lui-même du statut d'un commentaire (publié ou rejeté), avec un motif optionnel. Chaque changement de statut est historisé.

## Choix

- **Historique porté par l'agrégat** : toute transition de `Comment` passe par une méthode unique qui écrit l'entrée d'historique. Un changement sans trace est impossible, quel que soit l'appelant.
- **Tout est historisé**, pas seulement l'opérateur (`submission`, `author_ban`, `llm`, `operator`) : sans la décision du LLM, une correction manuelle serait incompréhensible.
- **L'opérateur agit depuis n'importe quel statut**, y compris `pending` et un rejet pour ban. Un rejet manuel porte `rejectionReason: operator`, sans catégorie légale : l'opérateur n'a pas à qualifier juridiquement sa décision.
- **Statut identique = `409`** plutôt qu'une action ignorée : elle signale une vue périmée côté opérateur et créerait une entrée d'historique vide de sens.
- **L'opérateur l'emporte sur le LLM** : le LLM ne décide que sur un commentaire `pending`, le worker ignore donc un commentaire déjà tranché.
- **Verrou optimiste** (`#[ORM\Version]`) contre la course opérateur / worker : le worker retente puis s'arrête, l'API répond `409`. Un verrou pessimiste aurait bloqué l'opérateur pendant l'appel au LLM.
- **Historique sur un endpoint dédié**, pas dans le détail : la même vue sert à la recherche, où il coûterait une requête par commentaire.

## Limites

- Pas d'authentification : endpoint ouvert, identité de l'opérateur non enregistrée.
- Verrou optimiste non couvert par un test automatisé (SQLite en mémoire, une seule connexion).
