# Chevereto-Free

> 🔔 Subscribe to the [newsletter](https://newsletter.chevereto.com/subscription?f=PmL892XuTdfErVq763PCycJQrrnQgNmDybvvbXt7hbfEtgCJrjxKnBK4i9LmtXEOfM7MQBwP36vhsCGYOogbSIfBYw) to don't miss any update regarding Chevereto.

<img alt="Chevereto" src="content/images/system/default/logo.svg" width="100%">

[![Community](https://img.shields.io/badge/chv.to-community-blue?style=flat-square)](https://chv.to/community)
[![Discord](https://img.shields.io/discord/759137550312407050?style=flat-square)](https://chv.to/discord)

[Chevereto](https://chevereto.com) allows you to create a full-featured image hosting website on your own server. It's your hosting and your rules, say goodbye to closures and restrictions.

## Project status

Starting on **2022-01** the [Chevereto](https://chevereto.com) organization won't be in charge of this project and the repo ownership will be transferred to [@rodber](https://github.com/rodber).

## Screens

![Homepage](.github/screen/1.webp)

![Uploader](.github/screen/2.webp)

![Explorer](.github/screen/3.webp)

![Dashboard](.github/screen/4.webp)

## About this fork

Chevereto-Free is a mature fork of [Chevereto V3.16.2](https://releases.chevereto.com/3.X/3.16/3.16.2.html) in which only basic features are preserved. This fork was created for personal usage and small communities.

👉 **This fork removes**

* Social network login
* External Storage servers
* User likes and following
* Manage banners

👉 **This fork misses**

* 12FA Support
* User interface upgrade (V3.20)
* CLI API
* Installer tooling

## Installation

### Requirements

* PHP 7.4
* MySQL 5.7 / 8 - MariaDB 10
* Apache HTTP Web Server
  * mod_rewrite

## Composer-based installation

* Requires [Composer](https://getcomposer.org)

```sh
composer create-project chevereto/chevereto-free . \
    --repository='{"url": "https://github.com/chevereto/chevereto-free.git", "type": "vcs"}' \
    --remove-vcs \
    --ignore-platform-reqs
```

## Manual installation

* Pick the [latest release](https://github.com/chevereto/chevereto-free/releases/latest)
* Download the tagged `$TAG.zip` release artifact
* Unzip the release in your target `public` web-server directory

## Updating

### HTTP self-update

* Go to `/dashboard`
* Click on "Check for updates"
* Follow the on-screen process

### Manual update

See [Manual Installation](#manual-installation)

## License

Copyright [Rodolfo Berríos Arce](http://rodolfoberrios.com) - [AGPLv3](LICENSE).
