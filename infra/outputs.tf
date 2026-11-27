output "api_url" {
  description = "URL publique de l'API GSB sur Render"
  value       = render_web_service.api.url
}