target "docker-metadata-action" {}

target "build" {
  inherits = ["docker-metadata-action"]
  context = "./"
  dockerfile = "httpd-php.Dockerfile"
  platforms = [
    "linux/amd64",
    "linux/arm64",
  ]
}
