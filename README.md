# Chevereto-Free

> 🔔 Subscribe to the [newsletter](https://newsletter.chevereto.com/subscription?f=PmL892XuTdfErVq763PCycJQrrnQgNmDybvvbXt7hbfEtgCJrjxKnBK4i9LmtXEOfM7MQBwP36vhsCGYOogbSIfBYw) to don't miss any update regarding Chevereto.

![Chevereto](content/images/system/default/logo.svg)

[![Discord](https://img.shields.io/discord/759137550312407050?style=flat-square)](https://chv.to/discord)

Chevereto-Free is an image hosting software that allows you to create a beautiful and full-featured image hosting website on your own server. It's your hosting and your rules, so say goodbye to closures and restrictions.

In a nutshell, Chevereto-Free is:

- 👨🏾‍💻 An indie developed [mature project](https://github.com/chevereto/chevereto#history)
- ❤ A large [Community](https://chevereto.com/community/)
- 🤯 Used everywhere, including at [high scale](https://github.com/chevereto/chevereto#-powered-by-chevereto)
- ⚖ Open Source Software

## 🤯 Viva la modernización!

The Chevereto software **is being heavily modernized** by updating the technology stack. The new Chevereto is **extensible**, **progressive**, **headless** and is the most [_chévere_](https://chevere.org/get-started/#name-meaning) Chevereto ever made.

It is also **evolving the business**, specially for [how users will monetize](https://rodolfo.is/2021/01/20/thoughts-on-monetize-chevereto-installations/) their instances.

👍🏾 Check the all-new Chevereto at [chevereto/chevereto](https://github.com/chevereto/chevereto) for more information and to contribute to its development.

## It's a fork

Chevereto-Free is a fork of Chevereto V3 (proprietary software) in which the essential features are preserved. This fork **removes**:

- Social network login
- External Storage
- Likes + Followers
- Manage Banners

This fork trends to be more stable than the proprietary software (way less frequent updates). If you really need the edge features consider to go with the paid edition.

## Documentation

Chevereto documentation can be found at [v3-docs.chevereto.com](https://v3-docs.chevereto.com/)


## Community

Join other Chevereto users in our [community](https://chevereto.com/community/) for sharing, supporting and contributing to Chevereto development.

## Known issues

### Can't write into `/app/install/update/temp/` path

Older releases (`1.2.0` and below) are missing the temp folder required for the one-click update process. Simply create the folder for the `www-data` user:

```sh
sudo -u www-data mkdir /var/www/html/app/install/update/temp/
```

## Support

Users helping each other is highly welcome. Use our [Bug Tracking](https://chevereto.com/bug-tracking) to report bugs and our [Community Support](https://chevereto.com/community-support) forums for any support-related concern.

👍🏾 Consider [purchasing](https://chevereto.com/pricing) a license to get the entire pack of features, an extra layer of support, and to sustain the development of this software.

> Please **don't** open issues here unless is code related.

## License

Copyright [Rodolfo Berríos Arce](http://rodolfoberrios.com) - Released under the [MIT License](LICENSE).
