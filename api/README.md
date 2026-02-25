# Base API Symfony

API REST complète construite avec Symfony 7.3, incluant :
- Authentification JWT avec LexikJWTAuthenticationBundle
- Documentation OpenAPI 3.0 avec Nelmio
- DTOs avec validation Symfony
- Architecture propre avec Services et Repositories
- Pagination intégrée
- Gestion d'erreurs enrichies avec informations sur les champs

## Stack Technique

- **PHP**: 8.2+
- **Symfony**: 7.3
- **Base de données**: MySQL 8.0+
- **Doctrine ORM**: 3.x
- **JWT**: LexikJWTAuthenticationBundle
- **Documentation API**: NelmioApiDocBundle (OpenAPI 3.0)
- **Serialization**: Symfony Serializer
- **Validation**: Symfony Validator

## Structure du Projet

```
src/
├── Attribute/
│   ├── Filterable.php           # Marque une entité comme filtrable
│   └── FilterableField.php      # Définit les champs filtrables et leurs opérateurs
├── Controller/
│   ├── BaseApiController.php    # Contrôleur de base (validation/serialization/filtres)
│   ├── AuthController.php       # Authentification & vérification email
│   ├── UserController.php       # CRUD User avec pagination et filtres
│   └── PostController.php       # CRUD Post avec pagination et filtres
├── DTO/
│   ├── Shared/
│   │   ├── FilterRequest.php    # Filtre individuel (field, operator, value)
│   │   ├── FilterCollection.php # Collection de filtres
│   │   ├── Request/             # DTOs partagés (pagination)
│   │   └── Response/            # DTOs partagés (API response, paginated)
│   ├── Auth/
│   │   ├── Request/             # Requêtes auth (login, register, password reset)
│   │   └── Response/            # Réponses auth (tokens, confirmations)
│   ├── User/
│   │   ├── Request/             # Requêtes user (update, list avec filtres)
│   │   └── Response/            # Réponses user (user data, paginated)
│   └── Post/
│       ├── Request/             # Requêtes post (create, update, list avec filtres)
│       └── Response/            # Réponses post (post data, paginated)
├── Entity/
│   ├── User.php                 # Utilisateur (#[Filterable] + #[FilterableField])
│   ├── Post.php                 # Post (#[Filterable] + #[FilterableField])
│   └── PendingVerification.php  # Codes de vérification email
├── Repository/
│   ├── UserRepository.php       # Avec pagination et filtres génériques
│   ├── PostRepository.php       # Avec pagination et filtres génériques
│   └── PendingVerificationRepository.php
├── Service/
│   ├── UserService.php          # Logique métier User (accepte FilterCollection)
│   ├── PostService.php          # Logique métier Post (accepte FilterCollection)
│   ├── MailService.php          # Envoi d'emails (Mailgun)
│   └── VerificationCodeGenerator.php
├── Helper/
│   ├── Paginate.php             # Helper générique pagination
│   └── QueryFilter.php          # Helper générique filtrage (applyFilters)
├── Security/
│   └── Voter/                   # Voters pour autorisations (PostVoter)
├── Exception/
│   └── ApiException.php         # Exception personnalisée
└── EventListener/
    └── ExceptionListener.php    # Gestion globale des erreurs JSON
```

## Installation

### 1. Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0+ ou MariaDB
- Extension PHP : `ext-ctype`, `ext-iconv`

### 2. Configuration de la base de données

Modifiez le fichier `.env` (ou créez `.env.local`) :

```env
# Adaptez selon votre configuration MySQL
DATABASE_URL="mysql://username:password@127.0.0.1:3306/base_api_sf?serverVersion=8.0.32&charset=utf8mb4"
```

Exemples de configurations :
```env
# MySQL sans mot de passe
DATABASE_URL="mysql://root:@127.0.0.1:3306/base_api_sf?serverVersion=8.0.32&charset=utf8mb4"

# MySQL avec mot de passe
DATABASE_URL="mysql://root:password@127.0.0.1:3306/base_api_sf?serverVersion=8.0.32&charset=utf8mb4"
```

### 3. Installer les Git hooks (optionnel mais recommandé)

```bash
# Utiliser le script d'installation (recommandé)
./install-hooks.sh

# OU manuellement : configurer Git pour utiliser le dossier .githooks
git config core.hooksPath .githooks

# OU manuellement : copier le hook
cp .githooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

Ce hook vérifie automatiquement si la version de l'API a été mise à jour quand vous modifiez des DTOs. Voir `.githooks/README.md` pour plus de détails.

### 4. Créer la base de données et les tables

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Générer et exécuter les migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 5. Lancer le serveur

```bash
symfony serve
# OU
php -S localhost:8000 -t public/
```

L'API est maintenant accessible sur `http://localhost:8000`

## Documentation OpenAPI

Une fois le serveur lancé, accédez à la **documentation interactive Swagger** :

**URL**: [http://localhost:8000/doc](http://localhost:8000/doc)

Cette documentation vous permet de :
- 📖 Explorer tous les endpoints disponibles
- 🧪 Tester les requêtes directement depuis le navigateur
- 📋 Voir les schémas de requêtes/réponses avec exemples
- 🔐 Comprendre les exigences d'authentification pour chaque endpoint

**Note** : La documentation se met à jour **automatiquement** quand vous modifiez les DTOs ou contrôleurs grâce aux attributs OpenAPI (`#[OA\Schema]`, `#[OA\Property]`). Pas besoin de modifier manuellement des fichiers YAML.

**Versioning** : Un hook Git pre-commit vous rappelle de mettre à jour la version de l'API dans `config/packages/dev/nelmio_api_doc.yaml` quand vous modifiez des DTOs. Voir `.githooks/README.md` pour plus de détails.

## Endpoints Principaux

### Authentification (publics)

- `POST /register/request` - Demander un code de vérification
- `POST /register/complete` - Compléter l'inscription avec le code
- `POST /login` - Se connecter et obtenir un JWT
- `POST /password-reset/request` - Demander un reset de mot de passe
- `POST /password-reset/complete` - Réinitialiser le mot de passe

### Users (JWT requis)

- `GET /users` - Liste paginée avec filtrage générique
- `GET /users/{id}` - Détails utilisateur
- `PUT /users/{id}` - Modifier utilisateur
- `DELETE /users/{id}` - Supprimer utilisateur

### Posts (JWT requis)

- `GET /posts` - Liste paginée avec filtrage générique
- `GET /posts/{id}` - Détails post
- `POST /posts` - Créer un post
- `PUT /posts/{id}` - Modifier son post (avec voter authorization)
- `DELETE /posts/{id}` - Supprimer son post (avec voter authorization)

**Note** : Pour les détails complets des schémas de requête/réponse, consultez la [documentation OpenAPI](#documentation-openapi).

## Système de Filtrage Générique

L'API dispose d'un système de filtrage générique puissant et réutilisable pour tous les endpoints de listing.

### Paramètres de requête

- `page` - Numéro de page (défaut: 1)
- `limit` - Nombre d'éléments par page (défaut: 10, max: 100)
- `filters` - Tableau JSON de filtres

### Opérateurs disponibles

| Opérateur | Description | Exemple de valeur |
|-----------|-------------|-------------------|
| `eq` | Égal à (equals) | `"John"` |
| `ne` | Différent de (not equals) | `"Admin"` |
| `gt` | Plus grand que (greater than) | `"100"` |
| `lt` | Plus petit que (less than) | `"50"` |
| `gte` | Plus grand ou égal (>=) | `"2024-01-01"` |
| `lte` | Plus petit ou égal (<=) | `"2024-12-31"` |
| `in` | Dans une liste (séparé par virgules) | `"1,2,3"` |
| `not_in` | Pas dans une liste | `"admin,guest"` |
| `like` | Contient (recherche textuelle) | `"post"` → `%post%` |
| `starts_with` | Commence par | `"Jo"` → `Jo%` |
| `ends_with` | Se termine par | `".com"` → `%.com` |

### Format des filtres

```json
[
  {
    "field": "title",
    "operator": "like",
    "value": "API"
  },
  {
    "field": "author.firstName",
    "operator": "eq",
    "value": "John"
  }
]
```

### Exemples d'utilisation

**Rechercher tous les posts contenant "API" dans le titre :**
```bash
GET /posts?filters=[{"field":"title","operator":"like","value":"API"}]
```

**Filtrer les users dont le prénom commence par "Jo" :**
```bash
GET /users?filters=[{"field":"firstName","operator":"starts_with","value":"Jo"}]
```

**Filtrer les posts par plusieurs auteurs :**
```bash
GET /posts?filters=[{"field":"author.id","operator":"in","value":"1,2,3"}]
```

**Filtrer les posts créés après une date :**
```bash
GET /posts?filters=[{"field":"createdAt","operator":"gte","value":"2024-01-01"}]
```

**Filtres multiples (ET logique) :**
```bash
GET /posts?filters=[
  {"field":"title","operator":"like","value":"Symfony"},
  {"field":"author.firstName","operator":"eq","value":"John"}
]&page=1&limit=10
```

**Rechercher les emails se terminant par un domaine :**
```bash
GET /users?filters=[{"field":"email","operator":"ends_with","value":"@example.com"}]
```

### Champs filtrables

#### Posts (`/posts`)
- `id` (int) - eq, ne, in, not_in
- `title` (string) - eq, ne, in, not_in, like, starts_with, ends_with
- `content` (string) - eq, ne, like, starts_with, ends_with
- `author.id` (int) - eq, ne, in, not_in
- `author.firstName` (string) - eq, ne, in, not_in, like, starts_with, ends_with
- `author.lastName` (string) - eq, ne, in, not_in, like, starts_with, ends_with
- `createdAt` (date) - eq, ne, gt, gte, lt, lte
- `updatedAt` (date) - eq, ne, gt, gte, lt, lte

#### Users (`/users`)
- `id` (int) - eq, ne, in, not_in
- `email` (string) - eq, ne, in, not_in, like, starts_with, ends_with
- `firstName` (string) - eq, ne, in, not_in, like, starts_with, ends_with
- `lastName` (string) - eq, ne, in, not_in, like, starts_with, ends_with
- `createdAt` (date) - eq, ne, gt, gte, lt, lte

### Validation des filtres

Le système valide automatiquement :
- ✅ Format JSON valide
- ✅ Champs filtrables autorisés (whitelist)
- ✅ Opérateurs autorisés par champ
- ✅ Types de données

**Erreurs possibles :**
- `INVALID_FILTER_FORMAT` - JSON invalide
- `INVALID_FILTER_FIELD` - Champ non filtrable
- `INVALID_FILTER_OPERATOR` - Opérateur non autorisé pour ce champ
- `VALIDATION_ERROR` - Erreur de validation des valeurs

## Format des Réponses

### Succès simple
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "createdAt": "2024-01-15T10:30:00+00:00"
}
```

### Succès paginé
```json
{
  "data": [...],
  "pagination": {
    "total": 50,
    "page": 1,
    "limit": 10,
    "pages": 5
  }
}
```

### Erreur simple (codes en UPPERCASE_SNAKE_CASE)
```json
{
  "message": "USER_NOT_FOUND"
}
```

### Erreur de validation avec violations
```json
{
  "message": "VALIDATION_ERROR",
  "errors": [
    {"field": "email", "message": "This value is not a valid email."},
    {"field": "firstName", "message": "This value should not be blank."}
  ]
}
```

**Note** : Les codes d'erreur suivent toujours le format `UPPERCASE_SNAKE_CASE` (ex: `EMAIL_ALREADY_EXISTS`, `INVALID_CREDENTIALS`, `VERIFICATION_CODE_EXPIRED`)

## Architecture

- **BaseApiController** : Contrôleur de base avec méthodes `validateRequest()`, `jsonResponse()` et `parseFilters()`
- **Controllers slim** : Désérialisation → Validation → Appel service → Conversion DTO → Réponse JSON
- **Services** : Toute la logique métier (retournent des entités ou tableaux)
- **DTOs Request** : Validation avec Symfony Validator attributes + OpenAPI docs
- **DTOs Response** : Méthode `fromEntity()` pour convertir entités → réponses
- **ApiException** : Exception personnalisée avec codes UPPERCASE_SNAKE_CASE et violations
- **ExceptionListener** : Gestion globale des erreurs, conversion en JSON structuré
- **Voters** : Autorisations granulaires (ex: seul l'auteur peut modifier son post)
- **QueryFilter** : Helper générique pour appliquer des filtres aux QueryBuilder Doctrine
- **Filterable Attributes** : Définition des champs filtrables via attributes PHP (#[Filterable], #[FilterableField])

### Pattern de réponse
```
Entity → ResponseDTO::fromEntity($entity) → Serializer → JSON
```

### Pattern de filtrage
```
JSON filters → FilterCollection → QueryFilter → Doctrine QueryBuilder → Filtered Results
```

## Git Hooks

Le projet inclut un hook pre-commit qui vérifie le versioning de l'API. Voir `HOOKS_SETUP.md` pour le guide complet.

**Installation rapide** :
```bash
./install-hooks.sh
```

**Ce que fait le hook** :
- Détecte les modifications dans `src/DTO/`
- Vérifie que la version de l'API a été mise à jour dans `config/packages/dev/nelmio_api_doc.yaml`
- Vous alerte si la version n'a pas changé (avec option de continuer quand même)

## CORS (optionnel)

Pour activer CORS :
```bash
composer require nelmio/cors-bundle
```

Configurez ensuite `config/packages/nelmio_cors.yaml`.