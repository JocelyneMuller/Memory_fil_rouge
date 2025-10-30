# Copilot Instructions for Memory

## Vue d'ensemble du projet

Memory est une application de gestion de notes et de feedbacks pour une équipe de développeurs, composée d'un backend PHP (architecture maison type MVC) et d'un frontend Vue 3 (Vite).

## Architecture principale

- **backend/** : API REST PHP, structure MVC simplifiée
  - `app/Core/Database.php` : Singleton PDO pour la connexion MySQL (localhost, root/root, base 'Memory')
  - `app/Core/Router.php` : Routeur maison, routes déclarées dans `public/index.php`
  - `app/Controllers/` : Contrôleurs (ex : `CompetenceController.php`, `NoteController.php`)
  - `app/Models/` : Modèles (ex : `Competence.php`, `Note.php`)
  - `public/index.php` : Point d'entrée, déclare les routes API (`/api/competences`, `/api/notes`, etc.)
  - **Convention** : Les contrôleurs renvoient du JSON, gèrent les erreurs HTTP, et attendent les données POST/PUT en JSON (pas de formulaire classique).
- **frontend/** : SPA Vue 3
  - `src/views/` : Pages principales (Dashboard, Notes, Projects, Skills, Login)
  - `src/components/` : Composants réutilisables (ex : `NotesList.vue`)
  - `src/services/api.js` : Service centralisé pour les appels API (fetch, gestion des erreurs, endpoints REST)
  - `src/router/` : Définition des routes Vue Router
  - **Convention** : Utilisation de l'alias `@` pour `src/` dans les imports

## Flux de données & intégration

- Le frontend communique exclusivement avec le backend via fetch/REST (voir `api.js`).
- Les endpoints sont préfixés par `/api/` et renvoient toujours du JSON.
- Les opérations CRUD sont exposées pour les entités principales (Compétence, Note, Projet).
- Les erreurs côté backend sont renvoyées avec un code HTTP approprié et un message JSON (`{ success: false, error: ... }`).

## Workflows développeur

- **Démarrage backend** :
  - Utiliser MAMP (MySQL 8, PHP 8+), base 'Memory' (voir `database/memory_schema.sql` et `memory_seed_data.sql` pour initialisation)
  - Tester l'API :
    - `php backend/test_api.php` (teste la connexion DB et le modèle Compétence)
    - Accès API : `http://localhost/Memory_VF/Memory/backend/public/api/competences`
- **Démarrage frontend** :
  - Depuis `frontend/` :
    - `npm install`
    - `npm run dev` (Vite)
    - Accès : `http://localhost:5173` (par défaut)
- **Tests** :
  - Pas de framework de test automatisé intégré (tests manuels via scripts PHP ou le frontend)

## Conventions spécifiques

- **PHP** :
  - Les modèles reçoivent la connexion PDO en argument ou via singleton
  - Les contrôleurs gèrent la sérialisation JSON et les codes HTTP
  - Les routes sont déclarées explicitement dans `public/index.php`
- **Vue** :
  - Les appels API passent par `src/services/api.js` (ne pas dupliquer la logique fetch)
  - Les vues utilisent `v-model` pour les formulaires, et gèrent les états de chargement/erreur localement
  - Les routes Vue sont centralisées dans `src/router/index.js`

## Points d'intégration & dépendances

- **Frontend** : Vue 3, Vite, vue-router
- **Backend** : PHP 8+, PDO, MySQL
- **Base de données** : voir `database/memory_schema.sql` (structure) et `memory_seed_data.sql` (données de test)

## Exemples de patterns

- **Ajout d'une compétence** :
  - Frontend : POST `/api/competences` via `api.createCompetence({ libelle })`
  - Backend : `CompetenceController::createCompetence()` → `Competence::create()`
- **Récupération des notes** :
  - Frontend : GET `/api/notes` via `api.getNotes()`
  - Backend : `NoteController::getAllNotes()` → `Note::getAll()`

## Fichiers clés à consulter

- `backend/public/index.php` (routes API)
- `backend/app/Core/Router.php` (routing maison)
- `frontend/src/services/api.js` (logique d'appel API)
- `database/memory_schema.sql` (structure DB)

---

Pour toute nouvelle fonctionnalité, respecter la séparation frontend/backend, centraliser les accès API côté frontend, et suivre les conventions de sérialisation JSON côté backend.