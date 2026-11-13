# GSB API (Symfony 8)

API backend du projet **GSB** développée sous Symfony 8, PHP 8.4, PostgreSQL 15 et Nginx, entièrement conteneurisée avec Docker.

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé sur votre machine :
* [Docker](https://docs.docker.com/get-docker/) et **Docker Compose**
* **Git**

---

## Installation et mise en route

Suivez ces étapes dans l'ordre après avoir cloné le dépôt pour éviter tout problème de configuration ou de routage.

### 1. Cloner le dépôt
```bash
git clone https://gitlab.com/remyHamed/gsb.git
cd gsb
```

### 2. Configurer les variables d'environnement
Créez un fichier `.env.local` à la racine du projet pour définir vos paramètres de développement et de base de données :

```env
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=ChangeMeWithARandomSecretKey

# Base de données PostgreSQL (doit correspondre aux variables Docker)
DB_USER=app
DB_PASSWORD=ChangeMe
DB_NAME=app
DATABASE_URL="postgresql://${DB_USER}:${DB_PASSWORD}@database:5432/${DB_NAME}?serverVersion=15&charset=utf8"
```

### 3. Vérifier la configuration Docker locale (`docker-compose.override.yml`)
Assurez-vous que votre fichier `docker-compose.override.yml` monte correctement les volumes entre PHP (`app`) et Nginx (`web`) afin que le point d'entrée `public/index.php` soit synchronisé :

```yaml
services:
  app:
    build:
      target: dev
    volumes:
      - .:/var/www/html
      - ./public:/var/www/html/public
    env_file:
      - .env.local
    environment:
      - APP_ENV=dev
    healthcheck:
      disable: true

  web:
    ports:
      - "8080:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./public:/var/www/html/public:ro
    depends_on:
      app:
        condition: service_started

  database:
    env_file:
      - .env.local
    ports:
      - "5433:5432"
```

### 4. Construire et démarrer les conteneurs
Lancez l'environnement Docker en arrière-plan :
```bash
docker compose up -d --build
```

### 5. Installer les dépendances et configurer l'application
Exécutez les commandes suivantes à l'intérieur du conteneur `app` :

1. **Installer les dépendances PHP (Composer) :**
   ```bash
   docker compose exec app composer install
   ```

2. **Générer les clés JWT (si le dossier `config/jwt/` est vide) :**
   ```bash
   docker compose exec app php bin/console lexik:jwt:generate-keypair --skip-if-exists
   ```

3. **Créer les tables en base de données (Migrations Doctrine) :**
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Vider le cache Symfony :**
   ```bash
   docker compose exec app php bin/console cache:clear
   ```

---

## Utilisation et tests de l'API

* **URL de base :** `http://localhost:8080`
* **Port PostgreSQL exposé (hôte) :** `5433`

### Routes principales
| Méthode | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/auth/register` | Inscription d'un nouvel utilisateur (Body JSON) |
| `POST` | `/auth/login` | Authentification et récupération du token JWT |
| `GET` | `/api/employees` | Liste des employés |
| `GET` | `/shift` | Gestion des créneaux (shifts) |
| `GET` | `/week` | Gestion des semaines |

---

## Commandes utiles au quotidien

* **Afficher toutes les routes disponibles :**
  ```bash
  docker compose exec app php bin/console debug:router
  ```
* **Suivre les logs de l'application PHP en temps réel :**
  ```bash
  docker compose logs -f app
  ```
* **Vider le cache après modification des routes :**
  ```bash
  docker compose exec app php bin/console cache:clear
  ```
* **Arrêter les conteneurs :**
  ```bash
  docker compose down
  ```