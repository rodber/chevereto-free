# URLify for PHP [![Build Status](https://travis-ci.org/jbroadway/urlify.png)](https://travis-ci.org/jbroadway/urlify)

A PHP port of [URLify.js](https://github.com/django/django/blob/master/django/contrib/admin/static/admin/js/urlify.js)
from the Django project. Handles symbols from Latin languages as well as Arabic, Azerbaijani, Czech, German, Greek, Kazakh,
Latvian, Lithuanian, Persian, Polish, Romanian, Bulgarian, Russian, Serbian, Turkish, Ukrainian, Vietnamese and Slovak. Symbols it cannot
transliterate it will simply omit.

## Installation

Install the latest version with:

```bash
$ composer require jbroadway/urlify
```

## Usage

To generate slugs for URLs:

```php
<?php

echo URLify::filter (' J\'étudie le français ');
// "jetudie-le-francais"

echo URLify::filter ('Lo siento, no hablo español.');
// "lo-siento-no-hablo-espanol"
```

To generate slugs for file names:

```php
<?php

echo URLify::filter ('фото.jpg', 60, "", true);
// "foto.jpg"
```

To simply transliterate characters:

```php
<?php

echo URLify::downcode ('J\'étudie le français');
// "J'etudie le francais"

echo URLify::downcode ('Lo siento, no hablo español.');
// "Lo siento, no hablo espanol."

/* Or use transliterate() alias: */

echo URLify::transliterate ('Lo siento, no hablo español.');
// "Lo siento, no hablo espanol."
```

To extend the character list:

```php
<?php

URLify::add_chars ([
	'¿' => '?', '®' => '(r)', '¼' => '1/4',
	'½' => '1/2', '¾' => '3/4', '¶' => 'P'
]);

echo URLify::downcode ('¿ ® ¼ ¼ ¾ ¶');
// "? (r) 1/2 1/2 3/4 P"
```

To extend the list of words to remove:

```php
<?php

URLify::remove_words (['remove', 'these', 'too']);
```

To prioritize a certain language map:

```php
<?php

echo URLify::filter ('Ägypten und Österreich besitzen wie üblich ein Übermaß an ähnlich öligen Attachés', 60, 'de');
// "aegypten-und-oesterreich-besitzen-wie-ueblich-ein-uebermass-aehnlich-oeligen-attaches"

echo URLify::filter ('Cağaloğlu, çalıştığı, müjde, lazım, mahkûm', 60, 'tr');
// "cagaloglu-calistigi-mujde-lazim-mahkum"
```

Please note that the "ü" is transliterated to "ue" in the first case, whereas it results in a simple "u" in the latter.
