variable "render_api_key" {
  description = "Clé API Render"
  type        = string
  sensitive   = true
}

variable "render_owner_id" {
  description = "Workspace ID Render (usr-... ou tea-...)"
  type        = string
}

variable "repo_url" {
  description = "URL HTTPS du dépôt Git"
  type        = string
}

variable "app_secret" {
  description = "APP_SECRET Symfony"
  type        = string
  sensitive   = true
}

variable "jwt_secret_key" {
  description = "Contenu complet de la clé privée PEM JWT"
  type        = string
  sensitive   = true
}

variable "jwt_public_key" {
  description = "Contenu complet de la clé publique PEM JWT"
  type        = string
  sensitive   = true
}

variable "jwt_passphrase" {
  description = "Passphrase de la clé privée JWT"
  type        = string
  sensitive   = true
}