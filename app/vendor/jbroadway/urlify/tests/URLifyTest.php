<?php

use PHPUnit\Framework\TestCase;

class URLifyTest extends TestCase {
	function test_downcode () {
		$this->assertEquals ('  J\'etudie le francais  ', URLify::downcode ('  J\'étudie le français  '));
		$this->assertEquals ('Lo siento, no hablo espanol.', URLify::downcode ('Lo siento, no hablo español.'));
		$this->assertEquals ('F3PWS', URLify::downcode ('ΦΞΠΏΣ'));
		$this->assertEquals ('foo-bar', URLify::filter ('_foo_bar_'));
	}

	function test_filter () {
		$this->assertEquals ('jetudie-le-francais', URLify::filter ('  J\'étudie le français  '));
		$this->assertEquals ('lo-siento-no-hablo-espanol', URLify::filter ('Lo siento, no hablo español.'));
		$this->assertEquals ('f3pws', URLify::filter ('ΦΞΠΏΣ'));
		$this->assertEquals ('', URLify::filter('大般若經'));
		$this->assertEquals ('test-.txt', URLify::filter('test-大般若經.txt', 60, "", $file_name = true));
		$this->assertEquals ('yakrhy-ltoytr', URLify::filter('ياكرهي لتويتر'));
		$this->assertEquals ('saaat-25', URLify::filter('ساعت ۲۵'));
		$this->assertEquals ('foto.jpg', URLify::filter ('фото.jpg', 60, "", $file_name = true));
		// priorization of language-specific maps
		$this->assertEquals ('aouaou', URLify::filter ('ÄÖÜäöü',60,"tr"));
		$this->assertEquals ('aeoeueaeoeue', URLify::filter ('ÄÖÜäöü',60,"de"));

		$this->assertEquals ('bobby-mcferrin-dont-worry-be-happy', URLify::filter ("Bobby McFerrin — Don't worry be happy",600,"en"));
		// test stripping and conversion of UTF-8 spaces
		$this->assertEquals ('test-mahito-mukai', URLify::filter('向井　真人test　(Mahito Mukai)'));
		// Treat underscore as space
		$this->assertEquals ('text_with_underscore', URLify::filter('text_with_underscore', 60, "en", true, true, true, false));
	}

	function test_add_chars () {
		$this->assertEquals ('¿ ® ¼ ¼ ¾ ¶', URLify::downcode ('¿ ® ¼ ¼ ¾ ¶'));
		URLify::add_chars (array (
			'¿' => '?', '®' => '(r)', '¼' => '1/4',
			'¼' => '1/2', '¾' => '3/4', '¶' => 'P'
		));
		$this->assertEquals ('? (r) 1/2 1/2 3/4 P', URLify::downcode ('¿ ® ¼ ¼ ¾ ¶'));
	}

	function test_remove_words () {
		$this->assertEquals ('foo-bar', URLify::filter ('foo bar'));
		URLify::remove_words (array ('foo', 'bar'));
		$this->assertEquals ('', URLify::filter ('foo bar'));
	}

	function test_many_rounds_with_unknown_language_code () {
		for ($i = 0; $i < 1000; $i++) {
			URLify::downcode ('Lo siento, no hablo español.',-1);
		}
	}

	function test_remove_words_disable () {
		URLify::remove_words (array ('foo', 'bar'));
		$this->assertEquals ('foo-bar', URLify::filter ('foo bar', 60, '', false, false));
	}
}

?>
