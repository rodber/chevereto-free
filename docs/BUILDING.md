# Building

## Composer

`todo`

## Docker build

* **Tip:** Tag `ghcr.io/rodber/chevereto-free-httpd-php:1.6` to override the [ghcr package](https://github.com/orgs/rodber/packages?repo_name=chevereto-free) with local

```sh
docker build -t ghcr.io/rodber/chevereto-free-httpd-php:1.6 . \
    -f httpd-php.Dockerfile
```

* For custom tag: Replace `tag` with your own.

```sh
docker build -t rodber/chevereto-free-httpd-php:tag . \
    -f httpd-php.Dockerfile
```
