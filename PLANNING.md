# Plan de Migration et Gestion de Projet : API TypeScript vers Symfony

Ce document détaille le planning et la méthodologie suivis pour la refonte de l'API [gensceduleback](https://gitlab.com/remyHamed/gensceduleback), initialement écrite en TypeScript, vers une architecture Symfony (PHP).

> **Périmètre** : ce planning couvre la migration à parité de fonctionnalités avec l'API TypeScript existante (Auth + module Employee). L'ajout de nouvelles features ou de nouvelles routes/CRUD fait l'objet d'itérations et d'issues ultérieures, hors du scope de ce document.

## 1. Diagramme de Gantt (Planning prévisionnel)

```mermaid
gantt
    title Migration API : Stratégie Symfony & Docker
    dateFormat  YYYY-MM-DD
    axisFormat  %d/%m

    section 0. Fondation
    Initialisation projet & commit   :done, init, 2026-08-17, 1d
    Docker Compose & Environnement   :done, env, after init, 3d
    Configuration GitLab CI/CD       :active, ci, after env, 2d

    section 1. Modélisation
    Création Entités (Doctrine)      :b1, after ci, 3d
    Migrations BDD                   :b2, after b1, 1d

    section 2. Sécurité
    Mise en place User & Auth        :auth1, after b2, 3d
    Configuration JWT (LexikBundle)  :auth2, after auth1, 2d

    section 3. Module Employee (CRUD métier)
    Endpoint GET liste + actifs       :c1, after auth2, 2d
    Endpoint GET par ID              :c2, after c1, 1d
    Endpoint POST creation (+ lien Account) :c3, after c2, 2d
    Endpoint PUT mise a jour          :c4, after c3, 2d
    Endpoint PATCH desactivation      :c5, after c4, 1d
    Regles metier (disponibilites, skills) :c6, after c5, 2d
    Tests Unitaires & Fonctionnels (PHPUnit) :c7, after c6, 3d
```

## 2. Roadmap et Découpage des Tâches

Chaque étape ci-dessous correspond à une branche ou une issue sur ce dépôt.

### Phase 0 : Infrastructure (Fondations)

- [x] Initialisation du projet Symfony et premier commit sur GitLab.
- [x] Dockerisation : mise en place de php-fpm, nginx, postgresql via docker-compose (Dockerfile multi-stage dev/prod, healthchecks, séparation des secrets).
- [X] CI/CD : configuration du `.gitlab-ci.yml` pour le linting et les tests automatiques.

### Phase 1 : Modélisation (ORM)

- [X] Entités : utilisation de `make:entity` pour reproduire le modèle de données de l'API TS.
  - Note : le champ sera nommé `availableSlots` (nom retenu suite à un renommage en cours dans la source TS, non finalisé).
- [X] Migrations : génération et exécution des fichiers de migration Doctrine.

### Phase 2 : Sécurité & Authentification

- [x] UserProvider : configuration de l'entité `User` (`Account`) pour l'auth Symfony.
- [x] JWT : installation et configuration de `lexik/jwt-authentication-bundle` pour sécuriser les endpoints.
- [x] Endpoints `register` / `login` (portage direct de `AuthController` / `AuthService`).

> **Note de périmètre** : l'API source ne définit aucun CRUD complet pour `Account` (pas de liste, mise à jour, ni suppression — uniquement `register`, `login` et les lookups internes par `id`/`email`). Ce planning ne couvre donc que l'authentification ; un CRUD `Account` complet serait une nouvelle feature, hors scope ici.

### Phase 3 : Implémentation Métier — Module `Employee`

Reprend fidèlement les endpoints exposés par `EmployeeController` / `employeeRoutes.js` dans l'API TypeScript source.

- [x] `GET /employees` : liste complète des employés.
- [ ] `GET /employees/active` : liste filtrée sur `isActive = true`.
- [ ] `GET /employees/{id}` : détail d'un employé (404 si absent).
- [x] `POST /employees` : création, avec liaison obligatoire à un `Account` existant (`accountId`) et vérification qu'un `Account` n'est pas déjà lié à un employé.
- [ ] `PUT /employees/{id}` : mise à jour partielle (nom, `weeklyHours`, `joursDisponibles`, `datesExclues`, `skills`).
- [ ] `PATCH /employees/{id}/deactivate` : désactivation (soft delete via `isActive`).
- [ ] Portage des enums `Role` (ADMIN, EMPLOYEE, MANAGER) et `DayOfWeek` (0-6) en PHP (enums natifs Symfony 8+).
- [ ] Règles métier : disponibilité d'un employé sur une date donnée (`joursDisponibles` + `datesExclues`), et matching par `skills`.
- [ ] Tests PHPUnit : un test par endpoint + cas d'erreur (compte introuvable, compte déjà lié, employé introuvable).

## 3. Definition of Done

Une tâche n'est considérée comme terminée que si les 4 critères suivants sont remplis (applicable à chaque endpoint de la Phase 3, et de toute phase ultérieure) :

1. Code implémenté et respectant l'architecture du projet (Controller / Service / Repository).
2. Test unitaire sur la logique métier (Service).
3. Test fonctionnel/intégration sur l'endpoint (Controller + BDD).
4. Merge Request revue (auto-relecture) et liée à son issue GitLab (`Closes #<numéro>`).

## 4. Notes de méthodologie

- Une issue GitLab est créée pour chaque tâche listée ci-dessus.
- Les commits utilisent `Closes #<numéro>` pour fermer automatiquement l'issue liée.
- Le frontend Angular fait l'objet d'un projet séparé et n'est pas inclus dans ce planning.