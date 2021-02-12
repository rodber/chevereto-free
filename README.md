# Chevereto-Free

> 🔔 Subscribe to the [newsletter](https://newsletter.chevereto.com/subscription?f=PmL892XuTdfErVq763PCycJQrrnQgNmDybvvbXt7hbfEtgCJrjxKnBK4i9LmtXEOfM7MQBwP36vhsCGYOogbSIfBYw) to don't miss any update regarding Chevereto.

![Chevereto](content/images/system/default/logo.svg)

[![Discord](https://img.shields.io/discord/759137550312407050?style=flat-square)](https://chv.to/discord)

Chevereto-Free is an image hosting software that allows you to create an image hosting website on your own server. It's your hosting and your rules, so say goodbye to closures and restrictions.

In a nutshell, Chevereto-Free is:

- 👨🏾‍💻 An indie developed [mature project](https://github.com/chevereto/chevereto#history)
- ❤ A large [Community](https://chevereto.com/community/)
- ⭐ Used everywhere, including at [high scale](https://github.com/chevereto/chevereto#-powered-by-chevereto)

## 🤯 Viva la modernización!

Chevereto is being modernized by **remaking** the software **completely**. The new Chevereto is extensible, progressive, headless and is the most [_chévere_](https://chevere.org/get-started/#name-meaning) Chevereto ever made!

It is also **changing the business**, towards Open Source and for [how users will monetize](https://rodolfo.is/2021/01/20/monetize-chevereto-installations/) their instances. It also changes [monetizing](https://rodolfo.is/2021/02/03/monetizing-chevereto/) for Chevereto itself.

👻 Check the all-new Chevereto at [chevereto/chevereto](https://github.com/chevereto/chevereto), 😘 **gimme a star** will you?

## Open Source fork

Chevereto-Free is a fork of Chevereto V3 (paid software) in which only the essential features are preserved.

**This fork removes**:

- ❌ Social network login
- ❌ External Storage
- ❌ Likes + Followers
- ❌ Manage Banners

**Note**: Chevereto-Free isn't capped in any single way neither is bloated. It just removes the above features.

Chevereto-Free is **more stable** than the paid edition, but only because takes way longer to get new features. Sadly, I can only issue a couple releases per year and I don't have that much collaboration anyway.

💸 If you need all features consider to [purchase a license](https://chevereto.com/pricing).

## Documentation

Documentation can be found at [v3-docs.chevereto.com](https://v3-docs.chevereto.com/)

## Community

Join other Chevereto users in our [community](https://chevereto.com/community/) for sharing, supporting and contributing to Chevereto development.

🤗 Share your knowledge (please do) and help other users just like you.

## Bugs

🐞 Found something else? Use the [bug tracking](https://chv.to/open-bug).

### Can't write into `/app/install/update/temp/` path

Older releases (`1.2.0` and below) are missing the temp folder required for the one-click update process. Simply create the folder for the `www-data` user:

```sh
sudo -u www-data mkdir /var/www/html/app/install/update/temp/
```

## Support

Chevereto-Free **only includes** [community support](https://chevereto.com/community/forums/community-support.135/), no tech support is offered for this edition.

💸 Consider to [purchase a license](https://chevereto.com/pricing) to get the entire pack of features, support, and to sustain the development of this software.

## License

Copyright [Rodolfo Berríos Arce](http://rodolfoberrios.com) - Released under the [MIT License](LICENSE).
