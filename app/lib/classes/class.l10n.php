<?php

/* --------------------------------------------------------------------

  Chevereto
  http://chevereto.com/

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>
            <inbox@rodolfoberrios.com>

  Copyright (C) Rodolfo Berrios A. All rights reserved.

  BY USING THIS SOFTWARE YOU DECLARE TO ACCEPT THE CHEVERETO EULA
  http://chevereto.com/license

  --------------------------------------------------------------------- */

namespace CHV;

use G;
use Exception;
use DirectoryIterator;
use RegexIterator;

class L10n
{
    protected static $instance;
    protected static $processed;
    const CHV_DEFAULT_LANGUAGE_EXTENSION = 'po';
    const CHV_BASE_LANGUAGE = 'en';

    public static $gettext;
    public static $translation_table;
    public static $available_languages;
    public static $enabled_languages;
    public static $disabled_languages;
    public static $locale = self::CHV_BASE_LANGUAGE;
    public static $forced_locale = false;

    public function __construct()
    {
        try {

            // Stock the available languages
            self::$available_languages = [];
            $directory = new DirectoryIterator(CHV_APP_PATH_CONTENT_LANGUAGES);
            $regex  = new RegexIterator($directory, '/^.+\.'. self::CHV_DEFAULT_LANGUAGE_EXTENSION .'$/i', RegexIterator::GET_MATCH);

            $locales = self::getLocales();
            $missing_locales = [];
            foreach ($regex as $file) {
                $file = $file[0];
                $locale_code = basename($file, '.' . self::CHV_DEFAULT_LANGUAGE_EXTENSION);
                self::$available_languages[$locale_code] = $locales[$locale_code];
            }

            // Remove any missing lang array
            self::$available_languages = array_filter(self::$available_languages);

            // Handle the enabled/disabled languages
            self::$enabled_languages = self::$available_languages;
            self::$disabled_languages = [];
            foreach (getSetting('languages_disable') as $k) {
                self::$disabled_languages[$k] = $locales[$k];
                unset(self::$enabled_languages[$k]);
            }

            if (!self::$forced_locale) {

                // Set default website locale
                if (array_key_exists(getSetting('default_language'), self::$available_languages)) {
                    $locale = getSetting('default_language'); // Default from DB value
                } else {
                    $locale = self::$locale; // Default from class static
                }

                // Auto-language?
                if (getSetting('auto_language') and !Login::isLoggedUser()) {
                    foreach (G\get_client_languages() as $k => $v) {
                        $user_locale = str_replace('_', '-', $k);
                        if (array_key_exists($user_locale, self::$available_languages) and !array_key_exists($user_locale, self::$disabled_languages)) {
                            $locale = $user_locale;
                            break;
                        } else {
                            // try to use a base language
                            foreach (self::$available_languages as $k => $v) {
                                if ($v['base'] == substr($user_locale, 0, 2)) {
                                    $locale = $k;
                                    break;
                                }
                            }
                        }
                        if ($locale) {
                            break;
                        }
                    };
                }

                // Override with the user selected lang
                if (Login::isLoggedUser() || $_COOKIE['USER_SELECTED_LANG']) {
                    $user_lang = Login::getUser() ? Login::getUser()['language'] : $_COOKIE['USER_SELECTED_LANG'];
                    if (array_key_exists($user_lang, self::$available_languages) and !array_key_exists($user_lang, self::$disabled_languages)) {
                        $locale = $user_lang;
                    }
                }
            } else {
                $locale = self::$forced_locale;
            }

            // Set some language definitions
            if (!defined('CHV_LANGUAGE_CODE')) {
                define('CHV_LANGUAGE_CODE', $locale);
            }
            if (!defined('CHV_LANGUAGE_FILE')) {
                define('CHV_LANGUAGE_FILE', CHV_APP_PATH_CONTENT_LANGUAGES . $locale . '.' . self::CHV_DEFAULT_LANGUAGE_EXTENSION);
            }

            self::processTranslation($locale);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function setLocale($locale)
    {
        if (is_null(self::$instance)) {
            self::$forced_locale = $locale;
        } else {
            self::processTranslation($locale);
        }
    }

    public static function processTranslation($locale)
    {
        if (is_null($locale)) {
            $locale = self::$locale;
        }

        // Already did it
        if ($locale == self::$locale and isset(self::$translation_table)) {
            return;
        }

        // If locale isn't available use the default one and if that fails use the first one in the available table
        if (!array_key_exists($locale, self::$available_languages)) {
            if (array_key_exists(self::$locale, self::$available_languages)) {
                $locale = self::$available_languages;
            } else {
                $array = self::$available_languages;
                reset($array);
                $first_key = key($array);
                $locale = $first_key;
            }
        }

        // Filename
        $filename = $locale . '.' . self::CHV_DEFAULT_LANGUAGE_EXTENSION;

        // Overriding?
        $language_file = CHV_APP_PATH_CONTENT_LANGUAGES . $filename;
        $language_override_file = dirname($language_file) . '/overrides/' . $filename;

        // Stock the static $locale
        self::$locale = $locale;

        // Handle translation and its overrides
        $language_handling = [
            'base' => [
                'file'			=> $language_file,
                'cache_path'	=> dirname($language_file) . '/cache/',
                'table'			=> [],
            ],
            'override' => [
                'file'			=> $language_override_file,
                'cache_path'	=> dirname($language_file) . '/cache/overrides/',
                'table' 		=> [],
            ]
        ];

        foreach ($language_handling as $k => $v) {
            $cache_path = $v['cache_path'];
            $cache_file = basename($v['file']) . '.cache.php';

            if (!file_exists($v['file'])) {
                continue;
            }

            if (!file_exists($cache_path) and !@mkdir($cache_path)) {
                $cache_path = dirname($cache_path);
            }

            try {
                self::$gettext = new G\Gettext([
                    'file' 				=> $v['file'],
                    'cache_filepath'	=> $cache_path . $cache_file,
                    'cache_header'		=> $k == 'base',
                ]);
                if ($k == 'base') {
                    $translation_plural = self::$gettext->translation_plural;
                    $translation_header = self::$gettext->translation_header;
                }
                $language_handling[$k]['table'] = self::$gettext->translation_table;
            } catch (GettextException $e) {
                error_log($e); // Silence
            }
        }

        // Re-stock the base language values
        self::$gettext->translation_plural = $translation_plural;
        self::$gettext->translation_header = $translation_header;
        self::$gettext->translation_table = array_merge($language_handling['base']['table'], $language_handling['override']['table']);
        self::$translation_table = self::$gettext->translation_table;
    }

    // gettext wrapper
    public static function gettext($msg)
    {
        $translation = self::getGettext()->gettext($msg);
        return $translation;
    }

    // ngettext wrapper
    public static function ngettext($msg, $msg_plural, $count)
    {
        $translation = self::getGettext()->ngettext($msg, $msg_plural, $count);
        return $translation;
    }

    public static function setStatic($var, $value)
    {
        $instance = self::getInstance();
        $instance::${$var} = $value;
    }

    public static function getStatic($var)
    {
        $instance = self::getInstance();
        return $instance::${$var};
    }
    public static function getAvailableLanguages()
    {
        return self::getStatic('available_languages');
    }
    public static function getEnabledLanguages()
    {
        return self::getStatic('enabled_languages');
    }
    public static function getDisabledLanguages()
    {
        return self::getStatic('disabled_languages');
    }
    public static function getGettext()
    {
        return self::getStatic('gettext');
    }
    public static function getTranslation()
    {
        return self::getStatic('translation_table');
    }
    public static function getLocale()
    {
        return self::getStatic('locale');
    }

    // Locales table
    public static function getLocales()
    {
        return [
          'af' => [
            'code' => 'af',
            'dir' => 'ltr',
            'name' => 'Afrikaans',
            'base' => 'af',
            'short_name' => 'AF',
          ],
          'af-AF' => [
            'code' => 'af-AF',
            'dir' => 'ltr',
            'name' => 'Afrikaans',
            'base' => 'af',
            'short_name' => 'AF (AF)',
          ],
          'am' => [
            'code' => 'am',
            'dir' => 'ltr',
            'name' => 'āmariññā',
            'base' => 'am',
            'short_name' => 'AM',
          ],
          'am-AM' => [
            'code' => 'am-AM',
            'dir' => 'ltr',
            'name' => 'āmariññā',
            'base' => 'am',
            'short_name' => 'AM (AM)',
          ],
          'an' => [
            'code' => 'an',
            'dir' => 'ltr',
            'name' => 'Aragonés',
            'base' => 'an',
            'short_name' => 'AN',
          ],
          'an-AN' => [
            'code' => 'an-AN',
            'dir' => 'ltr',
            'name' => 'Aragonés',
            'base' => 'an',
            'short_name' => 'AN (AN)',
          ],
          'ar' => [
            'code' => 'ar',
            'dir' => 'rtl',
            'name' => 'العربية',
            'base' => 'ar',
            'short_name' => 'AR',
          ],
          'ar-AE' => [
            'code' => 'ar-AE',
            'dir' => 'rtl',
            'name' => 'العربية (الإمارات)',
            'base' => 'ar',
            'short_name' => 'AR (AE)',
          ],
          'ar-BH' => [
            'code' => 'ar-BH',
            'dir' => 'rtl',
            'name' => 'العربية (البحرين)',
            'base' => 'ar',
            'short_name' => 'AR (BH)',
          ],
          'ar-DZ' => [
            'code' => 'ar-DZ',
            'dir' => 'rtl',
            'name' => 'العربية (الجزائر)',
            'base' => 'ar',
            'short_name' => 'AR (DZ)',
          ],
          'ar-EG' => [
            'code' => 'ar-EG',
            'dir' => 'rtl',
            'name' => 'العربية (مصر)',
            'base' => 'ar',
            'short_name' => 'AR (EG)',
          ],
          'ar-IQ' => [
            'code' => 'ar-IQ',
            'dir' => 'rtl',
            'name' => 'العربية (العراق)',
            'base' => 'ar',
            'short_name' => 'AR (IQ)',
          ],
          'ar-JO' => [
            'code' => 'ar-JO',
            'dir' => 'rtl',
            'name' => 'العربية (الأردن)',
            'base' => 'ar',
            'short_name' => 'AR (JO)',
          ],
          'ar-KW' => [
            'code' => 'ar-KW',
            'dir' => 'rtl',
            'name' => 'العربية (الكويت)',
            'base' => 'ar',
            'short_name' => 'AR (KW)',
          ],
          'ar-LB' => [
            'code' => 'ar-LB',
            'dir' => 'rtl',
            'name' => 'العربية (لبنان)',
            'base' => 'ar',
            'short_name' => 'AR (LB)',
          ],
          'ar-LY' => [
            'code' => 'ar-LY',
            'dir' => 'rtl',
            'name' => 'العربية (ليبيا)',
            'base' => 'ar',
            'short_name' => 'AR (LY)',
          ],
          'ar-MA' => [
            'code' => 'ar-MA',
            'dir' => 'rtl',
            'name' => 'العربية (المغرب)',
            'base' => 'ar',
            'short_name' => 'AR (MA)',
          ],
          'ar-OM' => [
            'code' => 'ar-OM',
            'dir' => 'rtl',
            'name' => 'العربية (سلطنة عمان)',
            'base' => 'ar',
            'short_name' => 'AR (OM)',
          ],
          'ar-QA' => [
            'code' => 'ar-QA',
            'dir' => 'rtl',
            'name' => 'العربية (قطر)',
            'base' => 'ar',
            'short_name' => 'AR (QA)',
          ],
          'ar-SA' => [
            'code' => 'ar-SA',
            'dir' => 'rtl',
            'name' => 'العربية (السعودية)',
            'base' => 'ar',
            'short_name' => 'AR (SA)',
          ],
          'ar-SD' => [
            'code' => 'ar-SD',
            'dir' => 'rtl',
            'name' => 'العربية (السودان)',
            'base' => 'ar',
            'short_name' => 'AR (SD)',
          ],
          'ar-SY' => [
            'code' => 'ar-SY',
            'dir' => 'rtl',
            'name' => 'العربية (سوريا)',
            'base' => 'ar',
            'short_name' => 'AR (SY)',
          ],
          'ar-TN' => [
            'code' => 'ar-TN',
            'dir' => 'rtl',
            'name' => 'العربية (تونس)',
            'base' => 'ar',
            'short_name' => 'AR (TN)',
          ],
          'ar-YE' => [
            'code' => 'ar-YE',
            'dir' => 'rtl',
            'name' => 'العربية (اليمن)',
            'base' => 'ar',
            'short_name' => 'AR (YE)',
          ],
          'as' => [
            'code' => 'as',
            'dir' => 'ltr',
            'name' => 'অসমীয়া',
            'base' => 'as',
            'short_name' => 'AS',
          ],
          'as-AS' => [
            'code' => 'as-AS',
            'dir' => 'ltr',
            'name' => 'অসমীয়া',
            'base' => 'as',
            'short_name' => 'AS (AS)',
          ],
          'ast' => [
            'code' => 'ast',
            'dir' => 'ltr',
            'name' => 'Asturianu',
            'base' => 'ast',
            'short_name' => 'AST',
          ],
          'ast-AST' => [
            'code' => 'ast-AST',
            'dir' => 'ltr',
            'name' => 'Asturianu',
            'base' => 'ast',
            'short_name' => 'AST (AST)',
          ],
          'az' => [
            'code' => 'az',
            'dir' => 'ltr',
            'name' => 'Azərbaycan',
            'base' => 'az',
            'short_name' => 'AZ',
          ],
          'az-AZ' => [
            'code' => 'az-AZ',
            'dir' => 'ltr',
            'name' => 'Azərbaycan',
            'base' => 'az',
            'short_name' => 'AZ (AZ)',
          ],
          'ba' => [
            'code' => 'ba',
            'dir' => 'ltr',
            'name' => 'Башҡорт',
            'base' => 'ba',
            'short_name' => 'BA',
          ],
          'ba-BA' => [
            'code' => 'ba-BA',
            'dir' => 'ltr',
            'name' => 'Башҡорт',
            'base' => 'ba',
            'short_name' => 'BA (BA)',
          ],
          'be' => [
            'code' => 'be',
            'dir' => 'ltr',
            'name' => 'Беларускі',
            'base' => 'be',
            'short_name' => 'BE',
          ],
          'be-BY' => [
            'code' => 'be-BY',
            'dir' => 'ltr',
            'name' => 'Беларускі',
            'base' => 'be',
            'short_name' => 'BE (BY)',
          ],
          'bg' => [
            'code' => 'bg',
            'dir' => 'ltr',
            'name' => 'Български',
            'base' => 'bg',
            'short_name' => 'BG',
          ],
          'bg-BG' => [
            'code' => 'bg-BG',
            'dir' => 'ltr',
            'name' => 'Български',
            'base' => 'bg',
            'short_name' => 'BG (BG)',
          ],
          'bn' => [
            'code' => 'bn',
            'dir' => 'ltr',
            'name' => 'Bangla',
            'base' => 'bn',
            'short_name' => 'BN',
          ],
          'bn-BN' => [
            'code' => 'bn-BN',
            'dir' => 'ltr',
            'name' => 'Bangla',
            'base' => 'bn',
            'short_name' => 'BN (BN)',
          ],
          'br' => [
            'code' => 'br',
            'dir' => 'ltr',
            'name' => 'Brezhoneg',
            'base' => 'br',
            'short_name' => 'BR',
          ],
          'br-BR' => [
            'code' => 'br-BR',
            'dir' => 'ltr',
            'name' => 'Brezhoneg',
            'base' => 'br',
            'short_name' => 'BR (BR)',
          ],
          'bs' => [
            'code' => 'bs',
            'dir' => 'ltr',
            'name' => 'Bosanski',
            'base' => 'bs',
            'short_name' => 'BS',
          ],
          'bs-BS' => [
            'code' => 'bs-BS',
            'dir' => 'ltr',
            'name' => 'Bosanski',
            'base' => 'bs',
            'short_name' => 'BS (BS)',
          ],
          'ca' => [
            'code' => 'ca',
            'dir' => 'ltr',
            'name' => 'Сatalà',
            'base' => 'ca',
            'short_name' => 'CA',
          ],
          'ca-ES' => [
            'code' => 'ca-ES',
            'dir' => 'ltr',
            'name' => 'Сatalà (Espanya)',
            'base' => 'ca',
            'short_name' => 'CA (ES)',
          ],
          'ce' => [
            'code' => 'ce',
            'dir' => 'ltr',
            'name' => 'Нохчийн',
            'base' => 'ce',
            'short_name' => 'CE',
          ],
          'ce-CE' => [
            'code' => 'ce-CE',
            'dir' => 'ltr',
            'name' => 'Нохчийн',
            'base' => 'ce',
            'short_name' => 'CE (CE)',
          ],
          'ch' => [
            'code' => 'ch',
            'dir' => 'ltr',
            'name' => 'Chamoru',
            'base' => 'ch',
            'short_name' => 'CH',
          ],
          'ch-CH' => [
            'code' => 'ch-CH',
            'dir' => 'ltr',
            'name' => 'Chamoru',
            'base' => 'ch',
            'short_name' => 'CH (CH)',
          ],
          'co' => [
            'code' => 'co',
            'dir' => 'ltr',
            'name' => 'Corsu',
            'base' => 'co',
            'short_name' => 'CO',
          ],
          'co-CO' => [
            'code' => 'co-CO',
            'dir' => 'ltr',
            'name' => 'Corsu',
            'base' => 'co',
            'short_name' => 'CO (CO)',
          ],
          'cr' => [
            'code' => 'cr',
            'dir' => 'ltr',
            'name' => 'Cree',
            'base' => 'cr',
            'short_name' => 'CR',
          ],
          'cr-CR' => [
            'code' => 'cr-CR',
            'dir' => 'ltr',
            'name' => 'Cree',
            'base' => 'cr',
            'short_name' => 'CR (CR)',
          ],
          'cs' => [
            'code' => 'cs',
            'dir' => 'ltr',
            'name' => 'Čeština',
            'base' => 'cs',
            'short_name' => 'CS',
          ],
          'cs-CZ' => [
            'code' => 'cs-CZ',
            'dir' => 'ltr',
            'name' => 'Čeština',
            'base' => 'cs',
            'short_name' => 'CS (CZ)',
          ],
          'cv' => [
            'code' => 'cv',
            'dir' => 'ltr',
            'name' => 'Чăвашла',
            'base' => 'cv',
            'short_name' => 'CV',
          ],
          'cv-CV' => [
            'code' => 'cv-CV',
            'dir' => 'ltr',
            'name' => 'Чăвашла',
            'base' => 'cv',
            'short_name' => 'CV (CV)',
          ],
          'cy' => [
            'code' => 'cy',
            'dir' => 'ltr',
            'name' => 'Cymraeg',
            'base' => 'cy',
            'short_name' => 'CY',
          ],
          'cy-CY' => [
            'code' => 'cy-CY',
            'dir' => 'ltr',
            'name' => 'Cymraeg',
            'base' => 'cy',
            'short_name' => 'CY (CY)',
          ],
          'da' => [
            'code' => 'da',
            'dir' => 'ltr',
            'name' => 'Dansk',
            'base' => 'da',
            'short_name' => 'DA',
          ],
          'da-DK' => [
            'code' => 'da-DK',
            'dir' => 'ltr',
            'name' => 'Dansk',
            'base' => 'da',
            'short_name' => 'DA (DK)',
          ],
          'de' => [
            'code' => 'de',
            'dir' => 'ltr',
            'name' => 'Deutsch',
            'base' => 'de',
            'short_name' => 'DE',
          ],
          'de-AT' => [
            'code' => 'de-AT',
            'dir' => 'ltr',
            'name' => 'Deutsch (Österreich)',
            'base' => 'de',
            'short_name' => 'DE (AT)',
          ],
          'de-CH' => [
            'code' => 'de-CH',
            'dir' => 'ltr',
            'name' => 'Deutsch (Schweiz)',
            'base' => 'de',
            'short_name' => 'DE (CH)',
          ],
          'de-DE' => [
            'code' => 'de-DE',
            'dir' => 'ltr',
            'name' => 'Deutsch (Deutschland)',
            'base' => 'de',
            'short_name' => 'DE (DE)',
          ],
          'de-LU' => [
            'code' => 'de-LU',
            'dir' => 'ltr',
            'name' => 'Deutsch (Luxemburg)',
            'base' => 'de',
            'short_name' => 'DE (LU)',
          ],
          'el' => [
            'code' => 'el',
            'dir' => 'ltr',
            'name' => 'Ελληνικά',
            'base' => 'el',
            'short_name' => 'EL',
          ],
          'el-CY' => [
            'code' => 'el-CY',
            'dir' => 'ltr',
            'name' => 'Ελληνικά (Κύπρος)',
            'base' => 'el',
            'short_name' => 'EL (CY)',
          ],
          'el-GR' => [
            'code' => 'el-GR',
            'dir' => 'ltr',
            'name' => 'Ελληνικά (Ελλάδα)',
            'base' => 'el',
            'short_name' => 'EL (GR)',
          ],
          'en' => [
            'code' => 'en',
            'dir' => 'ltr',
            'name' => 'English',
            'base' => 'en',
            'short_name' => 'EN',
          ],
          'en-AU' => [
            'code' => 'en-AU',
            'dir' => 'ltr',
            'name' => 'English (Australia)',
            'base' => 'en',
            'short_name' => 'EN (AU)',
          ],
          'en-CA' => [
            'code' => 'en-CA',
            'dir' => 'ltr',
            'name' => 'English (Canada)',
            'base' => 'en',
            'short_name' => 'EN (CA)',
          ],
          'en-GB' => [
            'code' => 'en-GB',
            'dir' => 'ltr',
            'name' => 'English (UK)',
            'base' => 'en',
            'short_name' => 'EN (GB)',
          ],
          'en-IE' => [
            'code' => 'en-IE',
            'dir' => 'ltr',
            'name' => 'English (Ireland)',
            'base' => 'en',
            'short_name' => 'EN (IE)',
          ],
          'en-IN' => [
            'code' => 'en-IN',
            'dir' => 'ltr',
            'name' => 'English (India)',
            'base' => 'en',
            'short_name' => 'EN (IN)',
          ],
          'en-MT' => [
            'code' => 'en-MT',
            'dir' => 'ltr',
            'name' => 'English (Malta)',
            'base' => 'en',
            'short_name' => 'EN (MT)',
          ],
          'en-NZ' => [
            'code' => 'en-NZ',
            'dir' => 'ltr',
            'name' => 'English (New Zealand)',
            'base' => 'en',
            'short_name' => 'EN (NZ)',
          ],
          'en-PH' => [
            'code' => 'en-PH',
            'dir' => 'ltr',
            'name' => 'English (Philippines)',
            'base' => 'en',
            'short_name' => 'EN (PH)',
          ],
          'en-SG' => [
            'code' => 'en-SG',
            'dir' => 'ltr',
            'name' => 'English (Singapore)',
            'base' => 'en',
            'short_name' => 'EN (SG)',
          ],
          'en-US' => [
            'code' => 'en-US',
            'dir' => 'ltr',
            'name' => 'English (US)',
            'base' => 'en',
            'short_name' => 'EN (US)',
          ],
          'en-ZA' => [
            'code' => 'en-ZA',
            'dir' => 'ltr',
            'name' => 'English (South Africa)',
            'base' => 'en',
            'short_name' => 'EN (ZA)',
          ],
          'eo' => [
            'code' => 'eo',
            'dir' => 'ltr',
            'name' => 'Esperanta',
            'base' => 'eo',
            'short_name' => 'EO',
          ],
          'eo-EO' => [
            'code' => 'eo-EO',
            'dir' => 'ltr',
            'name' => 'Esperanta',
            'base' => 'eo',
            'short_name' => 'EO (EO)',
          ],
          'es' => [
            'code' => 'es',
            'dir' => 'ltr',
            'name' => 'Español',
            'base' => 'es',
            'short_name' => 'ES',
          ],
          'es-AR' => [
            'code' => 'es-AR',
            'dir' => 'ltr',
            'name' => 'Español (Argentina)',
            'base' => 'es',
            'short_name' => 'ES (AR)',
          ],
          'es-BO' => [
            'code' => 'es-BO',
            'dir' => 'ltr',
            'name' => 'Español (Bolivia)',
            'base' => 'es',
            'short_name' => 'ES (BO)',
          ],
          'es-CL' => [
            'code' => 'es-CL',
            'dir' => 'ltr',
            'name' => 'Español (Chile)',
            'base' => 'es',
            'short_name' => 'ES (CL)',
          ],
          'es-CO' => [
            'code' => 'es-CO',
            'dir' => 'ltr',
            'name' => 'Español (Colombia)',
            'base' => 'es',
            'short_name' => 'ES (CO)',
          ],
          'es-CR' => [
            'code' => 'es-CR',
            'dir' => 'ltr',
            'name' => 'Español (Costa Rica)',
            'base' => 'es',
            'short_name' => 'ES (CR)',
          ],
          'es-DO' => [
            'code' => 'es-DO',
            'dir' => 'ltr',
            'name' => 'Español (República Dominicana)',
            'base' => 'es',
            'short_name' => 'ES (DO)',
          ],
          'es-EC' => [
            'code' => 'es-EC',
            'dir' => 'ltr',
            'name' => 'Español (Ecuador)',
            'base' => 'es',
            'short_name' => 'ES (EC)',
          ],
          'es-ES' => [
            'code' => 'es-ES',
            'dir' => 'ltr',
            'name' => 'Español (España)',
            'base' => 'es',
            'short_name' => 'ES (ES)',
          ],
          'es-GT' => [
            'code' => 'es-GT',
            'dir' => 'ltr',
            'name' => 'Español (Guatemala)',
            'base' => 'es',
            'short_name' => 'ES (GT)',
          ],
          'es-HN' => [
            'code' => 'es-HN',
            'dir' => 'ltr',
            'name' => 'Español (Honduras)',
            'base' => 'es',
            'short_name' => 'ES (HN)',
          ],
          'es-MX' => [
            'code' => 'es-MX',
            'dir' => 'ltr',
            'name' => 'Español (México)',
            'base' => 'es',
            'short_name' => 'ES (MX)',
          ],
          'es-NI' => [
            'code' => 'es-NI',
            'dir' => 'ltr',
            'name' => 'Español (Nicaragua)',
            'base' => 'es',
            'short_name' => 'ES (NI)',
          ],
          'es-PA' => [
            'code' => 'es-PA',
            'dir' => 'ltr',
            'name' => 'Español (Panamá)',
            'base' => 'es',
            'short_name' => 'ES (PA)',
          ],
          'es-PE' => [
            'code' => 'es-PE',
            'dir' => 'ltr',
            'name' => 'Español (Perú)',
            'base' => 'es',
            'short_name' => 'ES (PE)',
          ],
          'es-PR' => [
            'code' => 'es-PR',
            'dir' => 'ltr',
            'name' => 'Español (Puerto Rico)',
            'base' => 'es',
            'short_name' => 'ES (PR)',
          ],
          'es-PY' => [
            'code' => 'es-PY',
            'dir' => 'ltr',
            'name' => 'Español (Paraguay)',
            'base' => 'es',
            'short_name' => 'ES (PY)',
          ],
          'es-SV' => [
            'code' => 'es-SV',
            'dir' => 'ltr',
            'name' => 'Español (El Salvador)',
            'base' => 'es',
            'short_name' => 'ES (SV)',
          ],
          'es-US' => [
            'code' => 'es-US',
            'dir' => 'ltr',
            'name' => 'Español (Estados Unidos)',
            'base' => 'es',
            'short_name' => 'ES (US)',
          ],
          'es-UY' => [
            'code' => 'es-UY',
            'dir' => 'ltr',
            'name' => 'Español (Uruguay)',
            'base' => 'es',
            'short_name' => 'ES (UY)',
          ],
          'es-VE' => [
            'code' => 'es-VE',
            'dir' => 'ltr',
            'name' => 'Español (Venezuela)',
            'base' => 'es',
            'short_name' => 'ES (VE)',
          ],
          'et' => [
            'code' => 'et',
            'dir' => 'ltr',
            'name' => 'Eesti',
            'base' => 'et',
            'short_name' => 'ET',
          ],
          'et-EE' => [
            'code' => 'et-EE',
            'dir' => 'ltr',
            'name' => 'Eesti (Eesti)',
            'base' => 'et',
            'short_name' => 'ET (EE)',
          ],
          'eu' => [
            'code' => 'eu',
            'dir' => 'ltr',
            'name' => 'Euskera',
            'base' => 'eu',
            'short_name' => 'EU',
          ],
          'eu-EU' => [
            'code' => 'eu-EU',
            'dir' => 'ltr',
            'name' => 'Euskera',
            'base' => 'eu',
            'short_name' => 'EU (EU)',
          ],
          'fa' => [
            'code' => 'fa',
            'dir' => 'rtl',
            'name' => 'فارسی',
            'base' => 'fa',
            'short_name' => 'FA',
          ],
          'fa-FA' => [
            'code' => 'fa-FA',
            'dir' => 'rtl',
            'name' => 'فارسی',
            'base' => 'fa',
            'short_name' => 'FA (FA)',
          ],
          'fi' => [
            'code' => 'fi',
            'dir' => 'ltr',
            'name' => 'Suomi',
            'base' => 'fi',
            'short_name' => 'FI',
          ],
          'fi-FI' => [
            'code' => 'fi-FI',
            'dir' => 'ltr',
            'name' => 'Suomi',
            'base' => 'fi',
            'short_name' => 'FI (FI)',
          ],
          'fj' => [
            'code' => 'fj',
            'dir' => 'ltr',
            'name' => 'Na Vosa Vakaviti',
            'base' => 'fj',
            'short_name' => 'FJ',
          ],
          'fj-FJ' => [
            'code' => 'fj-FJ',
            'dir' => 'ltr',
            'name' => 'Na Vosa Vakaviti',
            'base' => 'fj',
            'short_name' => 'FJ (FJ)',
          ],
          'fo' => [
            'code' => 'fo',
            'dir' => 'ltr',
            'name' => 'Føroyskt',
            'base' => 'fo',
            'short_name' => 'FO',
          ],
          'fo-FO' => [
            'code' => 'fo-FO',
            'dir' => 'ltr',
            'name' => 'Føroyskt',
            'base' => 'fo',
            'short_name' => 'FO (FO)',
          ],
          'fr' => [
            'code' => 'fr',
            'dir' => 'ltr',
            'name' => 'Français',
            'base' => 'fr',
            'short_name' => 'FR',
          ],
          'fr-BE' => [
            'code' => 'fr-BE',
            'dir' => 'ltr',
            'name' => 'Français (Belgique)',
            'base' => 'fr',
            'short_name' => 'FR (BE)',
          ],
          'fr-CA' => [
            'code' => 'fr-CA',
            'dir' => 'ltr',
            'name' => 'Français (Canada)',
            'base' => 'fr',
            'short_name' => 'FR (CA)',
          ],
          'fr-CH' => [
            'code' => 'fr-CH',
            'dir' => 'ltr',
            'name' => 'Français (Suisse)',
            'base' => 'fr',
            'short_name' => 'FR (CH)',
          ],
          'fr-FR' => [
            'code' => 'fr-FR',
            'dir' => 'ltr',
            'name' => 'Français (France)',
            'base' => 'fr',
            'short_name' => 'FR (FR)',
          ],
          'fr-LU' => [
            'code' => 'fr-LU',
            'dir' => 'ltr',
            'name' => 'Français (Luxembourg)',
            'base' => 'fr',
            'short_name' => 'FR (LU)',
          ],
          'fy' => [
            'code' => 'fy',
            'dir' => 'ltr',
            'name' => 'Frysk',
            'base' => 'fy',
            'short_name' => 'FY',
          ],
          'fy-FY' => [
            'code' => 'fy-FY',
            'dir' => 'ltr',
            'name' => 'Frysk',
            'base' => 'fy',
            'short_name' => 'FY (FY)',
          ],
          'ga' => [
            'code' => 'ga',
            'dir' => 'ltr',
            'name' => 'Gaeilge',
            'base' => 'ga',
            'short_name' => 'GA',
          ],
          'ga-IE' => [
            'code' => 'ga-IE',
            'dir' => 'ltr',
            'name' => 'Gaeilge (Éire)',
            'base' => 'ga',
            'short_name' => 'GA (IE)',
          ],
          'gd' => [
            'code' => 'gd',
            'dir' => 'ltr',
            'name' => 'Gàidhlig',
            'base' => 'gd',
            'short_name' => 'GD',
          ],
          'gd-GD' => [
            'code' => 'gd-GD',
            'dir' => 'ltr',
            'name' => 'Gàidhlig',
            'base' => 'gd',
            'short_name' => 'GD (GD)',
          ],
          'gl' => [
            'code' => 'gl',
            'dir' => 'ltr',
            'name' => 'Galego',
            'base' => 'gl',
            'short_name' => 'GL',
          ],
          'gl-GL' => [
            'code' => 'gl-GL',
            'dir' => 'ltr',
            'name' => 'Galego',
            'base' => 'gl',
            'short_name' => 'GL (GL)',
          ],
          'gu' => [
            'code' => 'gu',
            'dir' => 'ltr',
            'name' => 'Gujarati',
            'base' => 'gu',
            'short_name' => 'GU',
          ],
          'gu-GU' => [
            'code' => 'gu-GU',
            'dir' => 'ltr',
            'name' => 'Gujarati',
            'base' => 'gu',
            'short_name' => 'GU (GU)',
          ],
          'he' => [
            'code' => 'he',
            'dir' => 'rtl',
            'name' => 'עברית',
            'base' => 'he',
            'short_name' => 'HE',
          ],
          'he-IL' => [
            'code' => 'he-IL',
            'dir' => 'rtl',
            'name' => 'עברית',
            'base' => 'he',
            'short_name' => 'HE (IL)',
          ],
          'hi' => [
            'code' => 'hi',
            'dir' => 'ltr',
            'name' => 'हिंदी',
            'base' => 'hi',
            'short_name' => 'HI',
          ],
          'hi-IN' => [
            'code' => 'hi-IN',
            'dir' => 'ltr',
            'name' => 'हिंदी (भारत)',
            'base' => 'hi',
            'short_name' => 'HI (IN)',
          ],
          'hr' => [
            'code' => 'hr',
            'dir' => 'ltr',
            'name' => 'Hrvatski',
            'base' => 'hr',
            'short_name' => 'HR',
          ],
          'hr-HR' => [
            'code' => 'hr-HR',
            'dir' => 'ltr',
            'name' => 'Hrvatski',
            'base' => 'hr',
            'short_name' => 'HR (HR)',
          ],
          'hsb' => [
            'code' => 'hsb',
            'dir' => 'ltr',
            'name' => 'Hornjoserbšćina',
            'base' => 'hsb',
            'short_name' => 'HSB',
          ],
          'hsb-HSB' => [
            'code' => 'hsb-HSB',
            'dir' => 'ltr',
            'name' => 'Hornjoserbšćina',
            'base' => 'hsb',
            'short_name' => 'HSB (HSB)',
          ],
          'ht' => [
            'code' => 'ht',
            'dir' => 'ltr',
            'name' => 'Kreyòl Ayisyen',
            'base' => 'ht',
            'short_name' => 'HT',
          ],
          'ht-HT' => [
            'code' => 'ht-HT',
            'dir' => 'ltr',
            'name' => 'Kreyòl Ayisyen',
            'base' => 'ht',
            'short_name' => 'HT (HT)',
          ],
          'hu' => [
            'code' => 'hu',
            'dir' => 'ltr',
            'name' => 'Magyar',
            'base' => 'hu',
            'short_name' => 'HU',
          ],
          'hu-HU' => [
            'code' => 'hu-HU',
            'dir' => 'ltr',
            'name' => 'Magyar',
            'base' => 'hu',
            'short_name' => 'HU (HU)',
          ],
          'hy' => [
            'code' => 'hy',
            'dir' => 'ltr',
            'name' => 'հայերեն',
            'base' => 'hy',
            'short_name' => 'HY',
          ],
          'hy-HY' => [
            'code' => 'hy-HY',
            'dir' => 'ltr',
            'name' => 'հայերեն',
            'base' => 'hy',
            'short_name' => 'HY (HY)',
          ],
          'ia' => [
            'code' => 'ia',
            'dir' => 'ltr',
            'name' => 'Interlingua',
            'base' => 'ia',
            'short_name' => 'IA',
          ],
          'ia-IA' => [
            'code' => 'ia-IA',
            'dir' => 'ltr',
            'name' => 'Interlingua',
            'base' => 'ia',
            'short_name' => 'IA (IA)',
          ],
          'id' => [
            'code' => 'id',
            'dir' => 'ltr',
            'name' => 'Bahasa Indonesia',
            'base' => 'id',
            'short_name' => 'ID',
          ],
          'id-ID' => [
            'code' => 'id-ID',
            'dir' => 'ltr',
            'name' => 'Bahasa Indonesia',
            'base' => 'id',
            'short_name' => 'ID (ID)',
          ],
          'ie' => [
            'code' => 'ie',
            'dir' => 'ltr',
            'name' => 'Interlingue',
            'base' => 'ie',
            'short_name' => 'IE',
          ],
          'ie-IE' => [
            'code' => 'ie-IE',
            'dir' => 'ltr',
            'name' => 'Interlingue',
            'base' => 'ie',
            'short_name' => 'IE (IE)',
          ],
          'in' => [
            'code' => 'in',
            'dir' => 'ltr',
            'name' => 'Bahasa Indonesia',
            'base' => 'in',
            'short_name' => 'IN',
          ],
          'in-ID' => [
            'code' => 'in-ID',
            'dir' => 'ltr',
            'name' => 'Bahasa Indonesia (Indonesia)',
            'base' => 'in',
            'short_name' => 'IN (ID)',
          ],
          'is' => [
            'code' => 'is',
            'dir' => 'ltr',
            'name' => 'Íslenska',
            'base' => 'is',
            'short_name' => 'IS',
          ],
          'is-IS' => [
            'code' => 'is-IS',
            'dir' => 'ltr',
            'name' => 'Íslenska (Ísland)',
            'base' => 'is',
            'short_name' => 'IS (IS)',
          ],
          'it' => [
            'code' => 'it',
            'dir' => 'ltr',
            'name' => 'Italiano',
            'base' => 'it',
            'short_name' => 'IT',
          ],
          'it-CH' => [
            'code' => 'it-CH',
            'dir' => 'ltr',
            'name' => 'Italiano (Svizzera)',
            'base' => 'it',
            'short_name' => 'IT (CH)',
          ],
          'it-IT' => [
            'code' => 'it-IT',
            'dir' => 'ltr',
            'name' => 'Italiano (Italia)',
            'base' => 'it',
            'short_name' => 'IT (IT)',
          ],
          'iu' => [
            'code' => 'iu',
            'dir' => 'ltr',
            'name' => 'Inuktitut',
            'base' => 'iu',
            'short_name' => 'IU',
          ],
          'iu-IU' => [
            'code' => 'iu-IU',
            'dir' => 'ltr',
            'name' => 'Inuktitut',
            'base' => 'iu',
            'short_name' => 'IU (IU)',
          ],
          'iw' => [
            'code' => 'iw',
            'dir' => 'ltr',
            'name' => 'עברית',
            'base' => 'iw',
            'short_name' => 'IW',
          ],
          'iw-IL' => [
            'code' => 'iw-IL',
            'dir' => 'ltr',
            'name' => 'עברית',
            'base' => 'iw',
            'short_name' => 'IW (IL)',
          ],
          'ja' => [
            'code' => 'ja',
            'dir' => 'ltr',
            'name' => '日本語',
            'base' => 'ja',
            'short_name' => 'JA',
          ],
          'ja-JP' => [
            'code' => 'ja-JP',
            'dir' => 'ltr',
            'name' => '日本語',
            'base' => 'ja',
            'short_name' => 'JA (JP)',
          ],
          'ka' => [
            'code' => 'ka',
            'dir' => 'ltr',
            'name' => 'ქართული',
            'base' => 'ka',
            'short_name' => 'KA',
          ],
          'ka-KA' => [
            'code' => 'ka-KA',
            'dir' => 'ltr',
            'name' => 'ქართული',
            'base' => 'ka',
            'short_name' => 'KA (KA)',
          ],
          'kk' => [
            'code' => 'kk',
            'dir' => 'ltr',
            'name' => 'Қазақша',
            'base' => 'kk',
            'short_name' => 'KK',
          ],
          'kk-KK' => [
            'code' => 'kk-KK',
            'dir' => 'ltr',
            'name' => 'Қазақша',
            'base' => 'kk',
            'short_name' => 'KK (KK)',
          ],
          'km' => [
            'code' => 'km',
            'dir' => 'ltr',
            'name' => 'Khmer',
            'base' => 'km',
            'short_name' => 'KM',
          ],
          'km-KM' => [
            'code' => 'km-KM',
            'dir' => 'ltr',
            'name' => 'Khmer',
            'base' => 'km',
            'short_name' => 'KM (KM)',
          ],
          'ko' => [
            'code' => 'ko',
            'dir' => 'ltr',
            'name' => '한국어',
            'base' => 'ko',
            'short_name' => 'KO',
          ],
          'ko-KR' => [
            'code' => 'ko-KR',
            'dir' => 'ltr',
            'name' => '한국어',
            'base' => 'ko',
            'short_name' => 'KO (KR)',
          ],
          'ky' => [
            'code' => 'ky',
            'dir' => 'ltr',
            'name' => 'Кыргызча',
            'base' => 'ky',
            'short_name' => 'KY',
          ],
          'ky-KY' => [
            'code' => 'ky-KY',
            'dir' => 'ltr',
            'name' => 'Кыргызча',
            'base' => 'ky',
            'short_name' => 'KY (KY)',
          ],
          'la' => [
            'code' => 'la',
            'dir' => 'ltr',
            'name' => 'Latina',
            'base' => 'la',
            'short_name' => 'LA',
          ],
          'la-LA' => [
            'code' => 'la-LA',
            'dir' => 'ltr',
            'name' => 'Latina',
            'base' => 'la',
            'short_name' => 'LA (LA)',
          ],
          'lb' => [
            'code' => 'lb',
            'dir' => 'ltr',
            'name' => 'Lëtzebuergesch',
            'base' => 'lb',
            'short_name' => 'LB',
          ],
          'lb-LB' => [
            'code' => 'lb-LB',
            'dir' => 'ltr',
            'name' => 'Lëtzebuergesch',
            'base' => 'lb',
            'short_name' => 'LB (LB)',
          ],
          'lt' => [
            'code' => 'lt',
            'dir' => 'ltr',
            'name' => 'Lietuvių',
            'base' => 'lt',
            'short_name' => 'LT',
          ],
          'lt-LT' => [
            'code' => 'lt-LT',
            'dir' => 'ltr',
            'name' => 'Lietuvių (Lietuva)',
            'base' => 'lt',
            'short_name' => 'LT (LT)',
          ],
          'lv' => [
            'code' => 'lv',
            'dir' => 'ltr',
            'name' => 'Latviešu',
            'base' => 'lv',
            'short_name' => 'LV',
          ],
          'lv-LV' => [
            'code' => 'lv-LV',
            'dir' => 'ltr',
            'name' => 'Latviešu (Latvija)',
            'base' => 'lv',
            'short_name' => 'LV (LV)',
          ],
          'mi' => [
            'code' => 'mi',
            'dir' => 'ltr',
            'name' => 'Te Reo Māori',
            'base' => 'mi',
            'short_name' => 'MI',
          ],
          'mi-MI' => [
            'code' => 'mi-MI',
            'dir' => 'ltr',
            'name' => 'Te Reo Māori',
            'base' => 'mi',
            'short_name' => 'MI (MI)',
          ],
          'mk' => [
            'code' => 'mk',
            'dir' => 'ltr',
            'name' => 'Македонски',
            'base' => 'mk',
            'short_name' => 'MK',
          ],
          'mk-MK' => [
            'code' => 'mk-MK',
            'dir' => 'ltr',
            'name' => 'Македонски (Македонија)',
            'base' => 'mk',
            'short_name' => 'MK (MK)',
          ],
          'ml' => [
            'code' => 'ml',
            'dir' => 'ltr',
            'name' => 'Malayalam',
            'base' => 'ml',
            'short_name' => 'ML',
          ],
          'ml-ML' => [
            'code' => 'ml-ML',
            'dir' => 'ltr',
            'name' => 'Malayalam',
            'base' => 'ml',
            'short_name' => 'ML (ML)',
          ],
          'mo' => [
            'code' => 'mo',
            'dir' => 'ltr',
            'name' => 'Graiul Moldovenesc',
            'base' => 'mo',
            'short_name' => 'MO',
          ],
          'mo-MO' => [
            'code' => 'mo-MO',
            'dir' => 'ltr',
            'name' => 'Graiul Moldovenesc',
            'base' => 'mo',
            'short_name' => 'MO (MO)',
          ],
          'mr' => [
            'code' => 'mr',
            'dir' => 'ltr',
            'name' => 'मराठी',
            'base' => 'mr',
            'short_name' => 'MR',
          ],
          'mr-MR' => [
            'code' => 'mr-MR',
            'dir' => 'ltr',
            'name' => 'मराठी',
            'base' => 'mr',
            'short_name' => 'MR (MR)',
          ],
          'ms' => [
            'code' => 'ms',
            'dir' => 'ltr',
            'name' => 'Bahasa Melayu',
            'base' => 'ms',
            'short_name' => 'MS',
          ],
          'ms-MY' => [
            'code' => 'ms-MY',
            'dir' => 'ltr',
            'name' => 'Bahasa Melayu',
            'base' => 'ms',
            'short_name' => 'MS (MY)',
          ],
          'mt' => [
            'code' => 'mt',
            'dir' => 'ltr',
            'name' => 'Malti',
            'base' => 'mt',
            'short_name' => 'MT',
          ],
          'mt-MT' => [
            'code' => 'mt-MT',
            'dir' => 'ltr',
            'name' => 'Malti',
            'base' => 'mt',
            'short_name' => 'MT (MT)',
          ],
          'nb' => [
            'code' => 'nb',
            'dir' => 'ltr',
            'name' => '‪Norsk Bokmål‬',
            'base' => 'nb',
            'short_name' => 'NB',
          ],
          'nb-NB' => [
            'code' => 'nb-NB',
            'dir' => 'ltr',
            'name' => '‪Norsk Bokmål‬',
            'base' => 'nb',
            'short_name' => 'NB (NB)',
          ],
          'ne' => [
            'code' => 'ne',
            'dir' => 'ltr',
            'name' => 'नेपाली',
            'base' => 'ne',
            'short_name' => 'NE',
          ],
          'ne-NE' => [
            'code' => 'ne-NE',
            'dir' => 'ltr',
            'name' => 'नेपाली',
            'base' => 'ne',
            'short_name' => 'NE (NE)',
          ],
          'ng' => [
            'code' => 'ng',
            'dir' => 'ltr',
            'name' => 'Oshiwambo',
            'base' => 'ng',
            'short_name' => 'NG',
          ],
          'ng-NG' => [
            'code' => 'ng-NG',
            'dir' => 'ltr',
            'name' => 'Oshiwambo',
            'base' => 'ng',
            'short_name' => 'NG (NG)',
          ],
          'nl' => [
            'code' => 'nl',
            'dir' => 'ltr',
            'name' => 'Nederlands',
            'base' => 'nl',
            'short_name' => 'NL',
          ],
          'nl-BE' => [
            'code' => 'nl-BE',
            'dir' => 'ltr',
            'name' => 'Nederlands (België)',
            'base' => 'nl',
            'short_name' => 'NL (BE)',
          ],
          'nl-NL' => [
            'code' => 'nl-NL',
            'dir' => 'ltr',
            'name' => 'Nederlands (Nederland)',
            'base' => 'nl',
            'short_name' => 'NL (NL)',
          ],
          'nn' => [
            'code' => 'nn',
            'dir' => 'ltr',
            'name' => 'Norsk',
            'base' => 'nn',
            'short_name' => 'NN',
          ],
          'nn-NN' => [
            'code' => 'nn-NN',
            'dir' => 'ltr',
            'name' => 'Norsk (Nynorsk)',
            'base' => 'nn',
            'short_name' => 'NN (NN)',
          ],
          'no' => [
            'code' => 'no',
            'dir' => 'ltr',
            'name' => 'Norsk',
            'base' => 'no',
            'short_name' => 'NO',
          ],
          'no-NO' => [
            'code' => 'no-NO',
            'dir' => 'ltr',
            'name' => 'Norsk (Norge)',
            'base' => 'no',
            'short_name' => 'NO (NO)',
          ],
          'nv' => [
            'code' => 'nv',
            'dir' => 'ltr',
            'name' => 'Diné Bizaad',
            'base' => 'nv',
            'short_name' => 'NV',
          ],
          'nv-NV' => [
            'code' => 'nv-NV',
            'dir' => 'ltr',
            'name' => 'Diné Bizaad',
            'base' => 'nv',
            'short_name' => 'NV (NV)',
          ],
          'oc' => [
            'code' => 'oc',
            'dir' => 'ltr',
            'name' => 'Lenga d’òc',
            'base' => 'oc',
            'short_name' => 'OC',
          ],
          'oc-OC' => [
            'code' => 'oc-OC',
            'dir' => 'ltr',
            'name' => 'Lenga d’òc',
            'base' => 'oc',
            'short_name' => 'OC (OC)',
          ],
          'om' => [
            'code' => 'om',
            'dir' => 'ltr',
            'name' => 'Afaan Oromoo',
            'base' => 'om',
            'short_name' => 'OM',
          ],
          'om-OM' => [
            'code' => 'om-OM',
            'dir' => 'ltr',
            'name' => 'Afaan Oromoo',
            'base' => 'om',
            'short_name' => 'OM (OM)',
          ],
          'pa' => [
            'code' => 'pa',
            'dir' => 'rtl',
            'name' => 'भारत गणराज्य',
            'base' => 'pa',
            'short_name' => 'PA',
          ],
          'pa-IN' => [
            'code' => 'pa-IN',
            'dir' => 'rtl',
            'name' => 'भारत गणराज्य (नेपाली)',
            'base' => 'pa',
            'short_name' => 'PA (IN)',
          ],
          'pa-PK' => [
            'code' => 'pa-PK',
            'dir' => 'rtl',
            'name' => 'یپنجاب (پنجاب)',
            'base' => 'pa',
            'short_name' => 'PA (PK)',
          ],
          'pl' => [
            'code' => 'pl',
            'dir' => 'ltr',
            'name' => 'Polski',
            'base' => 'pl',
            'short_name' => 'PL',
          ],
          'pl-PL' => [
            'code' => 'pl-PL',
            'dir' => 'ltr',
            'name' => 'Polski (Polska)',
            'base' => 'pl',
            'short_name' => 'PL (PL)',
          ],
          'pt' => [
            'code' => 'pt',
            'dir' => 'ltr',
            'name' => 'Português',
            'base' => 'pt',
            'short_name' => 'PT',
          ],
          'pt-BR' => [
            'code' => 'pt-BR',
            'dir' => 'ltr',
            'name' => 'Português (Brasil)',
            'base' => 'pt',
            'short_name' => 'PT (BR)',
          ],
          'pt-PT' => [
            'code' => 'pt-PT',
            'dir' => 'ltr',
            'name' => 'Português (Portugal)',
            'base' => 'pt',
            'short_name' => 'PT (PT)',
          ],
          'qu' => [
            'code' => 'qu',
            'dir' => 'ltr',
            'name' => 'Runa Simi',
            'base' => 'qu',
            'short_name' => 'QU',
          ],
          'qu-QU' => [
            'code' => 'qu-QU',
            'dir' => 'ltr',
            'name' => 'Runa Simi',
            'base' => 'qu',
            'short_name' => 'QU (QU)',
          ],
          'rm' => [
            'code' => 'rm',
            'dir' => 'ltr',
            'name' => 'Rumantsch',
            'base' => 'rm',
            'short_name' => 'RM',
          ],
          'rm-RM' => [
            'code' => 'rm-RM',
            'dir' => 'ltr',
            'name' => 'Rumantsch',
            'base' => 'rm',
            'short_name' => 'RM (RM)',
          ],
          'ro' => [
            'code' => 'ro',
            'dir' => 'ltr',
            'name' => 'Română',
            'base' => 'ro',
            'short_name' => 'RO',
          ],
          'ro-RO' => [
            'code' => 'ro-RO',
            'dir' => 'ltr',
            'name' => 'Română (România)',
            'base' => 'ro',
            'short_name' => 'RO (RO)',
          ],
          'ru' => [
            'code' => 'ru',
            'dir' => 'ltr',
            'name' => 'Русский',
            'base' => 'ru',
            'short_name' => 'RU',
          ],
          'ru-RU' => [
            'code' => 'ru-RU',
            'dir' => 'ltr',
            'name' => 'Русский (Россия)',
            'base' => 'ru',
            'short_name' => 'RU (RU)',
          ],
          'sa' => [
            'code' => 'sa',
            'dir' => 'ltr',
            'name' => 'संस्कृत',
            'base' => 'sa',
            'short_name' => 'SA',
          ],
          'sa-SA' => [
            'code' => 'sa-SA',
            'dir' => 'ltr',
            'name' => 'संस्कृत',
            'base' => 'sa',
            'short_name' => 'SA (SA)',
          ],
          'sc' => [
            'code' => 'sc',
            'dir' => 'ltr',
            'name' => 'Sardu',
            'base' => 'sc',
            'short_name' => 'SC',
          ],
          'sc-SC' => [
            'code' => 'sc-SC',
            'dir' => 'ltr',
            'name' => 'Sardu',
            'base' => 'sc',
            'short_name' => 'SC (SC)',
          ],
          'sd' => [
            'code' => 'sd',
            'dir' => 'rtl',
            'name' => 'فارسی',
            'base' => 'sd',
            'short_name' => 'SD',
          ],
          'sd-SD' => [
            'code' => 'sd-SD',
            'dir' => 'rtl',
            'name' => 'فارسی',
            'base' => 'sd',
            'short_name' => 'SD (SD)',
          ],
          'sg' => [
            'code' => 'sg',
            'dir' => 'ltr',
            'name' => 'Sango',
            'base' => 'sg',
            'short_name' => 'SG',
          ],
          'sg-SG' => [
            'code' => 'sg-SG',
            'dir' => 'ltr',
            'name' => 'Sango',
            'base' => 'sg',
            'short_name' => 'SG (SG)',
          ],
          'sk' => [
            'code' => 'sk',
            'dir' => 'ltr',
            'name' => 'Slovenčina',
            'base' => 'sk',
            'short_name' => 'SK',
          ],
          'sk-SK' => [
            'code' => 'sk-SK',
            'dir' => 'ltr',
            'name' => 'Slovenčina (Slovenská republika)',
            'base' => 'sk',
            'short_name' => 'SK (SK)',
          ],
          'sl' => [
            'code' => 'sl',
            'dir' => 'ltr',
            'name' => 'Slovenščina',
            'base' => 'sl',
            'short_name' => 'SL',
          ],
          'sl-SI' => [
            'code' => 'sl-SI',
            'dir' => 'ltr',
            'name' => 'Slovenščina (Slovenija)',
            'base' => 'sl',
            'short_name' => 'SL (SI)',
          ],
          'so' => [
            'code' => 'so',
            'dir' => 'ltr',
            'name' => 'Af Somali',
            'base' => 'so',
            'short_name' => 'SO',
          ],
          'so-SO' => [
            'code' => 'so-SO',
            'dir' => 'ltr',
            'name' => 'Af Somali',
            'base' => 'so',
            'short_name' => 'SO (SO)',
          ],
          'sq' => [
            'code' => 'sq',
            'dir' => 'ltr',
            'name' => 'Shqipe',
            'base' => 'sq',
            'short_name' => 'SQ',
          ],
          'sq-AL' => [
            'code' => 'sq-AL',
            'dir' => 'ltr',
            'name' => 'Shqipe',
            'base' => 'sq',
            'short_name' => 'SQ (AL)',
          ],
          'sr' => [
            'code' => 'sr',
            'dir' => 'ltr',
            'name' => 'Српски',
            'base' => 'sr',
            'short_name' => 'SR',
          ],
          'sr-BA' => [
            'code' => 'sr-BA',
            'dir' => 'ltr',
            'name' => 'Српски (Босна и Херцеговина)',
            'base' => 'sr',
            'short_name' => 'SR (BA)',
          ],
          'sr-CS' => [
            'code' => 'sr-CS',
            'dir' => 'ltr',
            'name' => 'Српски (Србија и Црна Гора)',
            'base' => 'sr',
            'short_name' => 'SR (CS)',
          ],
          'sr-RS' => [
            'code' => 'sr-RS',
            'dir' => 'ltr',
            'name' => 'Српски',
            'base' => 'sr',
            'short_name' => 'SR (RS)',
          ],
          'sv' => [
            'code' => 'sv',
            'dir' => 'ltr',
            'name' => 'Svenska',
            'base' => 'sv',
            'short_name' => 'SV',
          ],
          'sv-SE' => [
            'code' => 'sv-SE',
            'dir' => 'ltr',
            'name' => 'Svenska (Sverige)',
            'base' => 'sv',
            'short_name' => 'SV (SE)',
          ],
          'sw' => [
            'code' => 'sw',
            'dir' => 'ltr',
            'name' => 'Kiswahili',
            'base' => 'sw',
            'short_name' => 'SW',
          ],
          'sw-SW' => [
            'code' => 'sw-SW',
            'dir' => 'ltr',
            'name' => 'Kiswahili',
            'base' => 'sw',
            'short_name' => 'SW (SW)',
          ],
          'ta' => [
            'code' => 'ta',
            'dir' => 'ltr',
            'name' => 'தமிழ',
            'base' => 'ta',
            'short_name' => 'TA',
          ],
          'ta-TA' => [
            'code' => 'ta-TA',
            'dir' => 'ltr',
            'name' => 'தமிழ',
            'base' => 'ta',
            'short_name' => 'TA (TA)',
          ],
          'th' => [
            'code' => 'th',
            'dir' => 'ltr',
            'name' => 'ไทย',
            'base' => 'th',
            'short_name' => 'TH',
          ],
          'th-TH' => [
            'code' => 'th-TH',
            'dir' => 'ltr',
            'name' => 'ไทย (ประเทศไทย)',
            'base' => 'th',
            'short_name' => 'TH (TH)',
          ],
          'tig' => [
            'code' => 'tig',
            'dir' => 'ltr',
            'name' => 'Tigré',
            'base' => 'tig',
            'short_name' => 'TIG',
          ],
          'tig-TIG' => [
            'code' => 'tig-TIG',
            'dir' => 'ltr',
            'name' => 'Tigré',
            'base' => 'tig',
            'short_name' => 'TIG (TIG)',
          ],
          'tk' => [
            'code' => 'tk',
            'dir' => 'ltr',
            'name' => 'Түркмен',
            'base' => 'tk',
            'short_name' => 'TK',
          ],
          'tk-TK' => [
            'code' => 'tk-TK',
            'dir' => 'ltr',
            'name' => 'Түркмен',
            'base' => 'tk',
            'short_name' => 'TK (TK)',
          ],
          'tlh' => [
            'code' => 'tlh',
            'dir' => 'ltr',
            'name' => 'tlhIngan Hol',
            'base' => 'tlh',
            'short_name' => 'TLH',
          ],
          'tlh-TLH' => [
            'code' => 'tlh-TLH',
            'dir' => 'ltr',
            'name' => 'tlhIngan Hol',
            'base' => 'tlh',
            'short_name' => 'TLH (TLH)',
          ],
          'tr' => [
            'code' => 'tr',
            'dir' => 'ltr',
            'name' => 'Türkçe',
            'base' => 'tr',
            'short_name' => 'TR',
          ],
          'tr-TR' => [
            'code' => 'tr-TR',
            'dir' => 'ltr',
            'name' => 'Türkçe',
            'base' => 'tr',
            'short_name' => 'TR (TR)',
          ],
          'uk' => [
            'code' => 'uk',
            'dir' => 'ltr',
            'name' => 'Українська',
            'base' => 'uk',
            'short_name' => 'UK',
          ],
          'uk-UA' => [
            'code' => 'uk-UA',
            'dir' => 'ltr',
            'name' => 'Українська',
            'base' => 'uk',
            'short_name' => 'UK (UA)',
          ],
          've' => [
            'code' => 've',
            'dir' => 'ltr',
            'name' => 'Tshivenda',
            'base' => 've',
            'short_name' => 'VE',
          ],
          've-VE' => [
            'code' => 've-VE',
            'dir' => 'ltr',
            'name' => 'Tshivenda',
            'base' => 've',
            'short_name' => 'VE (VE)',
          ],
          'vi' => [
            'code' => 'vi',
            'dir' => 'ltr',
            'name' => 'Tiếng Việt',
            'base' => 'vi',
            'short_name' => 'VI',
          ],
          'vi-VN' => [
            'code' => 'vi-VN',
            'dir' => 'ltr',
            'name' => 'Tiếng Việt',
            'base' => 'vi',
            'short_name' => 'VI (VN)',
          ],
          'vo' => [
            'code' => 'vo',
            'dir' => 'ltr',
            'name' => 'Volapük',
            'base' => 'vo',
            'short_name' => 'VO',
          ],
          'vo-VO' => [
            'code' => 'vo-VO',
            'dir' => 'ltr',
            'name' => 'Volapük',
            'base' => 'vo',
            'short_name' => 'VO (VO)',
          ],
          'wa' => [
            'code' => 'wa',
            'dir' => 'ltr',
            'name' => 'Walon',
            'base' => 'wa',
            'short_name' => 'WA',
          ],
          'wa-WA' => [
            'code' => 'wa-WA',
            'dir' => 'ltr',
            'name' => 'Walon',
            'base' => 'wa',
            'short_name' => 'WA (WA)',
          ],
          'xh' => [
            'code' => 'xh',
            'dir' => 'ltr',
            'name' => 'isiXhosa',
            'base' => 'xh',
            'short_name' => 'XH',
          ],
          'xh-XH' => [
            'code' => 'xh-XH',
            'dir' => 'ltr',
            'name' => 'isiXhosa',
            'base' => 'xh',
            'short_name' => 'XH (XH)',
          ],
          'yi' => [
            'code' => 'yi',
            'dir' => 'rtl',
            'name' => 'ייִדיש',
            'base' => 'yi',
            'short_name' => 'YI',
          ],
          'yi-YI' => [
            'code' => 'yi-YI',
            'dir' => 'rtl',
            'name' => 'ייִדיש',
            'base' => 'yi',
            'short_name' => 'YI (YI)',
          ],
          'zh' => [
          'code' => 'zh',
          'dir' => 'ltr',
          'name' => '中文',
          'base' => 'zh',
          'short_name' => 'ZH',
          ],
          'zh-CN' => [
          'code' => 'zh-CN',
          'dir' => 'ltr',
          'name' => '简体中文',
          'base' => 'zh',
          'short_name' => 'ZH (CN)',
          ],
          'zh-HK' => [
          'code' => 'zh-HK',
          'dir' => 'ltr',
          'name' => '中文 (香港)',
          'base' => 'zh',
          'short_name' => 'ZH (HK)',
          ],
          'zh-SG' => [
          'code' => 'zh-SG',
          'dir' => 'ltr',
          'name' => '中文 (新加坡)',
          'base' => 'zh',
          'short_name' => 'ZH (SG)',
          ],
          'zh-TW' => [
          'code' => 'zh-TW',
          'dir' => 'ltr',
          'name' => '繁体中文',
          'base' => 'zh',
          'short_name' => 'ZH (TW)',
          ],
          'zu' => [
            'code' => 'zu',
            'dir' => 'ltr',
            'name' => 'isiZulu',
            'base' => 'zu',
            'short_name' => 'ZU',
          ],
          'zu-ZU' => [
            'code' => 'zu-ZU',
            'dir' => 'ltr',
            'name' => 'isiZulu',
            'base' => 'zu',
            'short_name' => 'ZU (ZU)',
          ],
        ];
    }
}

class L10nException extends Exception
{
}

//error_reporting($error_reporting_level);
