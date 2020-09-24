# Chevereto Free

Chevereto Free is an image hosting software that allows you to create a beautiful and full-featured image hosting website on your own server. It's your hosting and your rules, so say goodbye to closures and restrictions.

## It's a fork

Chevereto Free is a fork of Chevereto V3 in which only the essential features are preserved and released as Open Source software. This fork  **doesn't include**:

- Social network login
- External storage support
- Likes + Followers
- Manage banners

The support response time is currently about ~1-2 weeks. Users helping each other is highly welcome.

> 👍🏾 Consider [purchasing](https://chevereto.com/pricing) a license to get the entire pack of features, an extra layer of support, and to sustain the development of this software.

## Community

Join thousands of other Chevereto users in our [community](https://chevereto.com/community/) for sharing, supporting and contributing to Chevereto development.

## Documentation

Chevereto documentation can be found at [v3-docs.chevereto.com](https://v3-docs.chevereto.com/)

> 📝 Contributing for a better documentation is highly appreciated

## Known issues

### Can't write into `/app/install/update/temp/` path

Older releases (`1.2.0` and below) are missing the temp folder required for the one-click update process. Simply create the folder for the `www-data` user:

```sh
sudo -u www-data mkdir /var/www/html/app/install/update/temp/
```

## 🤯 Next-gen in the works

Chevereto is being modernized by updating its stack and turning towards Open Source. [Read all updates](https://chevereto.com/community/threads/building-the-next-gen-chevereto.11140/) and check the [Chevere Framework](https://chevere.org/).

While is being modernized, V3 (commercial) still gets regular updates so if you want the full package (and to support my development which is highly appreciated) consider to [purchase a license](https://v3-docs.chevereto.com/licenses.html).

## Support

Use our [Bug Tracking](https://chevereto.com/bug-tracking) to report bugs and our [Community Support](https://chevereto.com/community-support) forums for any support-related concern.

Please **don't** open issues here unless is code related.

## License

Copyright [Rodolfo Berríos Arce](http://rodolfoberrios.com) - Released under the [MIT License](LICENSE).

## Author

Chevereto is made by the guy at the license.

## Warranty

This software doesn't include support. It may contain bugs. Use it at your own risk. This software is offered on an “as-is” basis. No warranty, either expressed or implied, is given.
