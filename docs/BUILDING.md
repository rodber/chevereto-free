# Building

## Composer

`todo`

## Docker build

* **Tip:** Tag `ghcr.io/rodber/chevereto-free:1.6` to override the [ghcr package](https://github.com/rodber/chevereto-free/pkgs/container/chevereto-free) with local

```sh
docker build -t ghcr.io/rodber/chevereto-free:1.6 . \
    -f httpd-php.Dockerfile
```

* For custom tag: Replace `tag` with your own.

```sh
docker build -t rodber/chevereto-free:tag . \
    -f httpd-php.Dockerfile
```
