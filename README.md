<p align="center"><a href="https://chevereto.com/"><img src="https://chevereto.com/app/themes/v3/img/chevereto-large.png" alt="Chevereto"></a></p>

<p align="center">Chevereto is an image hosting script that allows you to create a beautiful and full featured image hosting website in your own server. It's your hosting and your rules, say goodbye to closures and restrictions. <a href="https://chevereto.com">Chevereto.com</a></p>

<p>&nbsp;</p>

<p align="center"><a href="https://chevereto.com/"><img src="https://chevereto.com/app/themes/v3/img/devices.png" alt=""></a></p>

Chevereto Free
=

<a href="https://chevereto.com/free" title="♫♪ Ha llegado tu tiempo, es el momento de Freeeeeeeeeeeeeeee"><img src="https://chevereto.com/app/themes/v3/img/chevereto-free-cover.jpg" alt="Chevereto Sugar Free Cola"></a>

###About this repo
This is the repository of Chevereto Free which has been forked from Chevereto 3.X series. Chevereto Free has the same look, feel and taste of our [paid version](https://chevereto.com/pricing) but it comes in a free and Open Source package which includes all the basic image hosting functionalities. Free to use, forever.

###Free vs paid Chevereto
Chevereto Free includes all the basic image hosting stuff but it comes without dedicated support. Paid Chevereto comes with all features, updates and support. The following table summarizes the differences between paid vs free.

| Item                                         	| Free            	| Paid                   	|
|----------------------------------------------	|-----------------	|------------------------	|
| Contribution to Chevereto                    	| ☆                 |   ★★★★              	|
| Access to latest features                    	| Delayed         	| Always                 	|
| One click system update                       | Yes              	| Yes                    	|
| Tech support                                 	| No 	            | Yes 	|
| External storage support                     	| No              	| Yes                    	|
| Manage banners                               	| No              	| Yes                    	|
| Likes + Followers                            	| No              	| Yes                    	|
| Facebook, Twitter, Google and VK signup      	| No              	| Yes                    	|

##Minimum system requirements
Make sure your server meets the minimum system requirements which are:

 - Apache / NGiNX web server
 - PHP 5.5.0 (standard libraries)
 - MySQL 5.0 (ALL PRIVILEGES)

In most servers that's all you need. The system has a built-in system check that it will tell you right away when you have to fix some stuff in your server.

##We install it for you (free of charges)
We will be happy to install Chevereto for you, just send us an [installation request](https://chevereto.com/panel/request-installation) and we will do all the installation job for you. For free.

##Install via web installer
1. Download the [Chevereto Free web installer](https://cdn.rawgit.com/Chevereto/php-repo-installer/master/index.php)
2. Upload this file to your target destination (usually the `public_html` folder)
3. Open your website target destination URL and follow the install process

##Install via zip/tarball
 1. Download the [latest release](https://github.com/Chevereto/Chevereto-Free/releases/latest) of Chevereto Free
 2. Upload the contents of your download to your server (usually the `public_html` folder)
 3. Go to your website and follow the instructions

For additional install instructions refer to our [official documentation](https://chevereto.com/docs/install).

##Updates
Chevereto Free has a built-in system that everyday pings the [Chevereto API](https://chevereto.com/api/get/info/free) for new updates available for your website. To update your website go to `/update` and the system will download and install the update for you. For manual update just follow the install procedure.

##Upgrade to paid edition
To upgrade Chevereto Free to paid Chevereto you need to [get a license](https://chevereto.com/pricing) and then do the following:
 1. Download the [paid edition web installer](https://chevereto.com/panel/downloads/?get=web-installer)
 2. Follow steps (2) and (3) from the "[install via web installer](#install-via-web-installer)" instructions.
 3. Go to `/update` to complete the process.
 
###Alternative method
 1. Download latest Chevereto paid edition from [Chevereto.com/panel/downloads](http://chevereto.com/panel/downloads)
 2. Upload `app/license` and `app/install` folders
 3. Open your current website `app/app.php` file and remove this line: `define('G_APP_GITHUB_REPO_URL', 'https://github.com/' . G_APP_GITHUB_OWNER . '/' . G_APP_GITHUB_REPO);`
 4. Go to `/update` to complete the process

##Support
Chevereto Free doesn't include dedicated support. However, feel free to browse current and previous [support tickets](https://chevereto.com/tech-support). If you need further assistance please consider buying a license. By purchasing a license you get same-day support, all features and it makes you a supporter of Chevereto development.

##License
Copyright 2016 [Rodolfo Berríos](http://rodolfoberrios.com) - Released under the AGPLv3 license. The software is offered on an “as-is” basis and no warranty, either expressed or implied, is given.
