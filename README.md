# Sud Ouest — API de modération des commentaires

> 📄 **[Mon analyse](docs/README.md)** : choix, hypothèses, limites et usage de l'IA.

API Symfony 8 (DDD/CQRS) qui collecte les commentaires publiés sur les marques du groupe, les accepte immédiatement puis les fait modérer de façon asynchrone par un LLM, selon les catégories de contenus illicites du droit français ([règles de modération](docs/moderation.md)).

## Prérequis

- Docker avec Docker Compose, et `make`.
- Une clé [OpenRouter](https://openrouter.ai/) pour la modération réelle. Sans clé, l'API fonctionne mais les commentaires restent `pending`.

## Démarrage

```bash
echo "OPENROUTER_API_KEY=sk-or-..." > .env.local
make build
make up
make fixtures
```

`make up` démarre trois conteneurs et attend qu'ils soient prêts :

| Service | Rôle |
| --- | --- |
| `php` | FrankenPHP (PHP 8.5). Au démarrage : `composer install` si `vendor/` manque, puis migrations Doctrine. |
| `worker` | `messenger:consume async` : modère les commentaires en attente. Démarre une fois `php` prêt. |
| `database` | PostgreSQL 18. |

- API : <http://localhost:8080>
- Swagger UI : <http://localhost:8080/doc/>

`make fixtures` charge un jeu de démonstration, dont l'auteur banni `banned-user`. Il vide les tables `author` et `comment` avant le chargement.

## Commandes

| Commande | Effet |
| --- | --- |
| `make build` | Construit l'image PHP. À relancer après une modification du `Dockerfile` ou de `docker/`. |
| `make up` | Démarre la stack. |
| `make bash` | Ouvre un shell dans le conteneur `php`. |
| `make fixtures` | Charge les données de démonstration. |
| `make phpstan` | Analyse statique, niveau 9 sur `src/`. |
| `make test` | PHPStan puis PHPUnit. |

Le worker garde le code en mémoire : après une modification du code, lancer `docker compose restart worker`.

## Endpoints

| Méthode | Route | Description |
| --- | --- | --- |
| `POST` | `/comments` | Soumet un commentaire. Répond `202` avec son `id`. |
| `GET` | `/comments/{id}` | Détail d'un commentaire, dont son statut de modération. |
| `GET` | `/comments` | Recherche paginée. Filtres : `publisher`, `status` (`pending`, `published`, `rejected`), `source`, `authorId`. Pagination : `page` (défaut 1), `limit` (défaut 20, max 100). |
| `GET` | `/` | Nom et version de l'API. |
| `GET` | `/health` | Health check. |

Paramètres invalides : `422`. Commentaire inconnu : `404`.

```bash
# Soumettre un commentaire
curl -s -X POST http://localhost:8080/comments -H 'Content-Type: application/json' \
  -d '{"publisher":"sudouest","source":"article-123","content":"Très bon article.","authorId":"user-1"}'

# Commentaire d'un auteur banni : rejeté immédiatement, sans appel au LLM
curl -s -X POST http://localhost:8080/comments -H 'Content-Type: application/json' \
  -d '{"publisher":"sudouest","source":"article-123","content":"Encore moi.","authorId":"banned-user"}'

# Rechercher
curl -s 'http://localhost:8080/comments?publisher=sudouest&status=published'
```

## Configuration

| Variable | Où | Rôle |
| --- | --- | --- |
| `OPENROUTER_API_KEY` | `.env.local` (non commité) | Clé de l'API OpenRouter. |
| `OPENROUTER_TIMEOUT` | `.env` | Durée maximale d'un appel au LLM, en secondes (défaut 20). |
| `MESSENGER_TRANSPORT_DSN` | `.env` | Transport de la file de modération (Doctrine, sur PostgreSQL). |
| `DATABASE_URL` | `compose.yaml` | DSN PostgreSQL, construit à partir de `POSTGRES_DB`, `POSTGRES_USER` et `POSTGRES_PASSWORD` (défaut `app`). |
| `HTTP_PORT`, `DATABASE_PORT` | environnement du shell | Ports exposés sur l'hôte (défaut 8080 et 5432). |

Le modèle LLM et son prompt ne sont pas configurables : chaque modérateur fixe les siens (`Gpt4oMiniModerator` et [config/moderation/OpenRouter/gpt-4o-mini.md](config/moderation/OpenRouter/gpt-4o-mini.md)).

## Modération en échec

Une panne transitoire du LLM (réseau, 429, 5xx) est retentée 3 fois (après 5, 15 puis 45 s). Ensuite, ou immédiatement en cas d'erreur définitive (autre 4xx, réponse invalide), le message part dans la file `failed` et le commentaire reste `pending`.

```bash
docker compose exec php php bin/console messenger:failed:show
docker compose exec php php bin/console messenger:failed:retry
```

## Architecture

- `src/Domain/` : agrégats `Comment` et `Author`, enums de statut et de catégories, ports `CommentRepository`, `AuthorRepository` et `Moderator`.
- `src/Application/` : commandes `SubmitComment` (synchrone) et `ModerateComment` (asynchrone), requêtes `GetComment` et `SearchComments`.
- `src/Infrastructure/` : repositories Doctrine, `Gpt4oMiniModerator` (OpenRouter), listener d'erreurs JSON, fixtures.
- `src/UI/Api/` : controllers et DTOs d'entrée.

Les conventions de code sont décrites dans [AGENTS.md](AGENTS.md).

## Tests

`make test` lance PHPStan puis PHPUnit. Les tests tournent sur SQLite en mémoire, exécutent la modération en synchrone et remplacent le LLM par un stub à marqueurs (`[moderation:<catégorie>]` dans le contenu) : ils n'appellent jamais OpenRouter et ne consomment aucun crédit.
