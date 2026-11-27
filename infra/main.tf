resource "render_postgres" "db" {
  name    = "gsb-db"
  plan    = "free"
  region  = "frankfurt"
  version = "15"
}

resource "render_web_service" "api" {
  name   = "gsb-api"
  plan   = "free"
  region = "frankfurt"

  runtime_source = {
    docker = {
      repo_url            = var.repo_url
      branch              = "main"
      dockerfile_path     = "./Dockerfile"
      docker_build_target = "render"
      auto_deploy         = true
    }
  }

  env_vars = {
    APP_ENV = {
      value = "prod"
    }
    APP_DEBUG = {
      value = "0"
    }
    APP_SECRET = {
      value = var.app_secret
    }
    DATABASE_URL = {
      value = render_postgres.db.connection_info.internal_connection_string
    }
    JWT_SECRET_KEY = {
      value = var.jwt_secret_key
    }
    JWT_PUBLIC_KEY = {
      value = var.jwt_public_key
    }
    JWT_PASSPHRASE = {
      value = var.jwt_passphrase
    }
  }

  health_check_path = "/health"
}