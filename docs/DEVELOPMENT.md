# Development

## Quick start

* Clone [rodber/chevereto-free](https://github.com/rodber/chevereto-free)
* Run [docker-compose up](#up)
* [Sync code](#sync-code) to sync changes

## Reference

* `SOURCE` is the absolute path to the cloned chevereto project
* You need to replace `SOURCE=~/git/rodber/chevereto-free` with your own path
* `SOURCE` will be mounted at `/var/www/source/` inside the container
* Chevereto will be available at [localhost:8910](http://localhost:8910)

✨ This dev setup mounts `SOURCE` to provide the application files to the container. We provide a sync system that copies these files on-the-fly to the actual application runner for better isolation.

## docker-compose

Compose file: [httpd-php-dev.yml](../httpd-php-dev.yml)

Alter `SOURCE` in the commands below to reflect your project path.

## Up

Run this command to spawn (start) Chevereto Installer.

```sh
SOURCE=~/git/rodber/chevereto-free \
docker-compose \
    -p chevereto-free-dev \
    -f httpd-php-dev.yml \
    up -d
```

## Stop

Run this command to stop Chevereto Installer.

```sh
SOURCE=~/git/rodber/chevereto-free \
docker-compose \
    -p chevereto-free-dev \
    -f httpd-php-dev.yml \
    stop
```

## Start

Run this command to start Chevereto if stopped.

```sh
SOURCE=~/git/rodber/chevereto-free \
docker-compose \
    -p chevereto-free-dev \
    -f httpd-php-dev.yml \
    start
```

## Down (uninstall)

Run this command to down Chevereto (stop containers, remove networks and volumes created by it).

```sh
SOURCE=~/git/rodber/chevereto-free \
docker-compose \
    -p chevereto-free-dev \
    -f httpd-php-dev.yml \
    down --volumes
```

## Sync code

Run this command to sync the application code with your working project.

```sh
docker exec -it \
    chevereto-free-dev_app \
    bash /var/www/sync.sh
```

This system will observe for changes in your working project filesystem and it will automatically sync the files inside the container.

**Note:** This command must keep running to provide the sync functionality. You should close it once you stop working with the source.

## Logs

`todo`
