<?php

/* --------------------------------------------------------------------

  G\ library
  https://g.chevereto.com

  @author	Rodolfo Berrios A. <http://rodolfoberrios.com/>

  Copyright (c) Rodolfo Berrios <inbox@rodolfoberrios.com> All rights reserved.

  Licensed under the MIT license
  http://opensource.org/licenses/MIT

  --------------------------------------------------------------------- */

namespace G {

    use Exception;

    /**
     * ROUTE HELPERS
     * ---------------------------------------------------------------------.
     */

    // Returns true if the $route is current /route or mapped-route -> /route
    function is_route($route)
    {
        return Handler::$base_request == $route;
    }

    // Returns true if the $route is has route file
    function is_route_available($route)
    {
        $route_file = 'route.' . $route . '.php';

        return file_exists(G_APP_PATH_ROUTES . $route_file) or file_exists(G_APP_PATH_ROUTES_OVERRIDES . $route_file);
    }

    // Returns true if the route is prevented
    function is_prevented_route()
    {
        return Handler::$prevented_route == true;
    }

    // Get the route path
    // $full=true returns route/and/sub/routes, $full=false returns just the /route base
    function get_route_path($full = false)
    {
        return Handler::getRoutePath($full);
    }

    // Get route name from route.name.php
    function get_route_name()
    {
        return Handler::getRouteName();
    }

    // Get the route template basename file used. Useful when you do route mapping.
    function get_template_used()
    {
        return Handler::getTemplateUsed();
    }

    /**
     * GLOBAL HELPERS
     * ---------------------------------------------------------------------.
     */

    // Outputs a well formatted HTML output of anything (strings, arrays, comma separated "things", anything)
    function debug($arguments)
    {
        if (empty($arguments)) {
            return;
        }
        echo '<pre>';
        foreach (func_get_args() as $value) {
            print_r($value);
        }
        echo '</pre>';
    }

    // Universal check for setted values for strings and arrays
    function check_value($anything)
    {
        if ((@count($anything) > 0 and !@empty($anything) and @isset($anything)) || $anything == '0') {
            return true;
        }
    }

    function get_global($var)
    {
        global ${$var};

        return ${$var};
    }

    function is_apache()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) and preg_match('/Apache/i', $_SERVER['SERVER_SOFTWARE']);
    }

    // Fixed from the original (not working) at: http://stackoverflow.com/a/7859707
    function random_values($min, $max, $limit)
    {
        // Numbers?
        if (!is_numeric($min) or !is_numeric($max)) {
            return null;
        }

        // Get the accurate min and max
        $min = min($min, $max);
        $max = max($min, $max);

        // Go home
        if ($min == $max) {
            return array($min);
        }

        // is the limit ok?
        $minmax_limit = abs($max - $min);
        if ($limit > $minmax_limit) {
            $limit = $minmax_limit;
        }

        $array = array();
        for ($i = 0; $i < $limit; ++$i) {
            $rand = rand($min, $max);
            while (in_array($rand, $array)) {
                $rand = mt_rand($min, $max);
            }
            $array[$i] = $rand;
        }

        return $array;
    }

    /**
     * Generates a random string.
     *
     * @autor Baba
     * @url http://stackoverflow.com/a/17267718
     */
    function random_string($length)
    {
        switch (true) {
            case function_exists('random_bytes'):
                $r = random_bytes($length);
                break;
            case function_exists('openssl_random_pseudo_bytes'):
                $r = openssl_random_pseudo_bytes($length);
                break;
            case is_readable('/dev/urandom'): // deceze
                $r = file_get_contents('/dev/urandom', false, null, 0, $length);
                break;
            default:
                $i = 0;
                $r = '';
                while ($i++ < $length) {
                    $r .= chr(mt_rand(0, 255));
                }
                break;
        }

        return substr(bin2hex($r), 0, $length);
    }

    /**
     * A timing safe equals comparison.
     *
     * To prevent leaking length information, it is important
     * that user input is always used as the second parameter.
     *
     * @param string $safe The internal (safe) value to be checked
     * @param string $user The user submitted (unsafe) value
     *
     * @return bool true if the two strings are identical
     */
    function timing_safe_compare($safe, $user)
    {
        // Prevent issues if string length is 0
        $safe .= chr(0);
        $user .= chr(0);

        $safeLen = strlen($safe);
        $userLen = strlen($user);

        // Set the result to the difference between the lengths
        $result = $safeLen - $userLen;

        // Note that we ALWAYS iterate over the user-supplied length
        // This is to prevent leaking length information
        for ($i = 0; $i < $userLen; ++$i) {
            // Using % here is a trick to prevent notices
            // It's safe, since if the lengths are different
            // $result is already non-0
            $result |= (ord($safe[$i % $safeLen]) ^ ord($user[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return $result === 0;
    }

    function str_replace_first($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    function str_replace_last($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    // Taken from http://stackoverflow.com/a/834355
    function starts_with($needle, $haystack)
    {
        $length = strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }

    // Taken from http://stackoverflow.com/a/834355
    function ends_with($needle, $haystack)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }

    /**
     * DATA HANDLING
     * ---------------------------------------------------------------------.
     */

    // Filter array with another array
    function array_filter_array($array, $filter_keys, $get = 'exclusion')
    {
        $arr = $array;
        $return = [];
        $get = strtolower($get);
        $default_get = 'exclusion';

        foreach ($filter_keys as $k => $v) {
            switch ($get) {
                default:
                case $default_get:
                    $get = $default_get;
                    if (!array_key_exists($v, $array)) {
                        continue 2;
                    }
                    $return[$v] = $array[$v];
                    break;
                case 'rest':
                    unset($array[$v]);
                    break;
            }
        }

        return $get == $default_get ? $return : $array;
    }

    function key_asort(&$array, $key)
    {
        $sorter = [];
        $ret = [];
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }

    /**
     * Recursive UTF-8 encode array.
     */
    function array_utf8encode(&$arr)
    {
        array_walk_recursive($arr, function (&$val, $key) {
            $encoding = mb_detect_encoding($val);
            if ($encoding == false) {
                $val = null;
            } else {
                $val = mb_convert_encoding($val, 'UTF-8', $encoding);
            }
        });

        return $arr;
    }

    /**
     * Recursive remove empty properties.
     */
    function array_remove_empty($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = array_remove_empty($haystack[$key]);
            }
            if (empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    /**
     * Abbreviate a number with suffix output.
     */
    function abbreviate_number($number)
    {
        if ($number === null) {
            $number = 0;
        } else {
            // strip any formatting
            $number = (0 + str_replace(',', '', $number));
        }

        // Not a number, keep it "as is"
        if (!is_numeric($number) or $number == 0) {
            return $number;
        }

        $abbreviations = [
            24 => 'Y',
            21 => 'Z',
            18 => 'E',
            15 => 'P',
            12 => 'T',
            9 => 'G',
            6 => 'M',
            3 => 'K',
            0 => null,
        ];
        foreach ($abbreviations as $exponent => $abbreviation) {
            if ($number >= pow(10, $exponent)) {
                return round(floatval($number / pow(10, $exponent))) . $abbreviation;
            }
        }
    }

    function nullify_string(&$string)
    {
        if (is_string($string) and $string == '') {
            $string = null;
        }
    }

    // http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
    function hex_to_rgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);

        return $rgb; // returns an array with the rgb values
    }
    function rgb_to_hex($rgb)
    {
        $hex = '#';
        $hex .= str_pad(dechex($rgb[0]), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, '0', STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }

    /**
     * Convert HTML code to BBCode
     * http://kuikie.com/snippets/snippet.php/90-17/php-function-to-convert-bbcode-to-html.
     */
    function html_to_bbcode($text)
    {
        $htmltags = array(
            '/\<b\>(.*?)\<\/b\>/is',
            '/\<i\>(.*?)\<\/i\>/is',
            '/\<u\>(.*?)\<\/u\>/is',
            '/\<ul.*?\>(.*?)\<\/ul\>/is',
            '/\<li\>(.*?)\<\/li\>/is',
            '/\<img(.*?) src=\"(.*?)\" alt=\"(.*?)\" title=\"Smile(y?)\" \/\>/is',
            '/\<img(.*?) src=\"(.*?)\" (.*?)\>/is',
            '/\<img(.*?) src=\"(.*?)\" alt=\":(.*?)\" .*? \/\>/is',
            '/\<div class=\"quotecontent\"\>(.*?)\<\/div\>/is',
            '/\<div class=\"codecontent\"\>(.*?)\<\/div\>/is',
            '/\<div class=\"quotetitle\"\>(.*?)\<\/div\>/is',
            '/\<div class=\"codetitle\"\>(.*?)\<\/div\>/is',
            '/\<cite.*?\>(.*?)\<\/cite\>/is',
            '/\<blockquote.*?\>(.*?)\<\/blockquote\>/is',
            '/\<div\>(.*?)\<\/div\>/is',
            '/\<code\>(.*?)\<\/code\>/is',
            '/\<br(.*?)\>/is',
            '/\<strong\>(.*?)\<\/strong\>/is',
            '/\<em\>(.*?)\<\/em\>/is',
            '/\<a href=\"mailto:(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
            '/\<a .*?href=\"(.*?)\"(.*?)\>http:\/\/(.*?)\<\/a\>/is',
            '/\<a .*?href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
        );
        $bbtags = array(
            '[b]$1[/b]',
            '[i]$1[/i]',
            '[u]$1[/u]',
            '[list]$1[/list]',
            '[*]$1',
            '$3',
            '[img]$2[/img]',
            ':$3',
            '\[quote\]$1\[/quote\]',
            '\[code\]$1\[/code\]',
            '',
            '',
            '',
            '\[quote\]$1\[/quote\]',
            '$1',
            '\[code\]$1\[/code\]',
            "\n",
            '[b]$1[/b]',
            '[i]$1[/i]',
            '[email=$1]$3[/email]',
            '[url]$1[/url]',
            '[url=$1]$3[/url]',
        );

        $text = str_replace("\n", ' ', $text);
        $ntext = preg_replace($htmltags, $bbtags, $text);
        $ntext = preg_replace($htmltags, $bbtags, $ntext);

        // for too large text and cannot handle by str_replace
        if (!$ntext) {
            $ntext = str_replace(array('<br>', '<br />'), "\n", $text);
            $ntext = str_replace(array('<strong>', '</strong>'), array('[b]', '[/b]'), $ntext);
            $ntext = str_replace(array('<em>', '</em>'), array('[i]', '[/i]'), $ntext);
        }

        $ntext = strip_tags($ntext);
        $ntext = trim(html_entity_decode($ntext, ENT_QUOTES, 'UTF-8'));

        return $ntext;
    }

    // Linkify functions borrowed from https://github.com/misd-service-development/php-linkify
    function linkify($text, array $options = array())
    {
        $attr = '';
        if (true === array_key_exists('attr', $options)) {
            foreach ($options['attr'] as $key => $value) {
                if (true === is_array($value)) {
                    $value = array_pop($value);
                }
                $attr .= sprintf(' %s="%s"', $key, $value);
            }
        }
        $options['attr'] = $attr;
        $ignoreTags = array('head', 'link', 'a', 'script', 'style', 'code', 'pre', 'select', 'textarea', 'button');
        $chunks = preg_split('/(<.+?>)/is', $text, 0, PREG_SPLIT_DELIM_CAPTURE);
        $openTag = null;
        for ($i = 0; $i < count($chunks); ++$i) {
            if ($i % 2 === 0) { // even numbers are text
                // Only process this chunk if there are no unclosed $ignoreTags
                if (null === $openTag) {
                    $chunks[$i] = linkify_urls($chunks[$i], $options);
                    $chunks[$i] = linkify_emails($chunks[$i], $options);
                }
            } else { // odd numbers are tags
                // Only process this tag if there are no unclosed $ignoreTags
                if (null === $openTag) {
                    // Check whether this tag is contained in $ignoreTags and is not self-closing
                    if (preg_match('`<(' . implode('|', $ignoreTags) . ').*(?<!/)>$`is', $chunks[$i], $matches)) {
                        $openTag = $matches[1];
                    }
                } else {
                    // Otherwise, check whether this is the closing tag for $openTag.
                    if (preg_match('`</\s*' . $openTag . '>`i', $chunks[$i], $matches)) {
                        $openTag = null;
                    }
                }
            }
        }
        $text = implode($chunks);

        return $text;
    }

    function linkify_emails($text, $options = array('attr' => ''))
    {
        $pattern = '~(?xi)
                \b
                (?<!=)           # Not part of a query string
                [A-Z0-9._\'%+-]+ # Username
                @                # At
                [A-Z0-9.-]+      # Domain
                \.               # Dot
                [A-Z]{2,4}       # Something
        ~';
        $callback = function ($match) use ($options) {
            if (isset($options['callback'])) {
                $cb = $options['callback']($match[0], $match[0], true);
                if (!is_null($cb)) {
                    return $cb;
                }
            }

            return '<a href="mailto:' . $match[0] . '"' . $options['attr'] . '>' . $match[0] . '</a>';
        };

        return preg_replace_callback($pattern, $callback, $text);
    }

    function linkify_urls($text, $options = array('attr' => ''))
    {
        $pattern = '~(?xi)
              (?:
                ((ht|f)tps?://)                    # scheme://
                |                                  #   or
                www\d{0,3}\.                       # "www.", "www1.", "www2." ... "www999."
                |                                  #   or
                www\-                              # "www-"
                |                                  #   or
                [a-z0-9.\-]+\.[a-z]{2,4}(?=/)      # looks like domain name followed by a slash
              )
              (?:                                  # Zero or more:
                [^\s()<>]+                         # Run of non-space, non-()<>
                |                                  #   or
                \(([^\s()<>]+|(\([^\s()<>]+\)))*\) # balanced parens, up to 2 levels
              )*
              (?:                                  # End with:
                \(([^\s()<>]+|(\([^\s()<>]+\)))*\) # balanced parens, up to 2 levels
                |                                  #   or
                [^\s`!\-()\[\]{};:\'".,<>?«»“”‘’]  # not a space or one of these punct chars
              )
        ~';
        $callback = function ($match) use ($options) {
            $caption = $match[0];
            $pattern = '~^(ht|f)tps?://~';
            if (0 === preg_match($pattern, $match[0])) {
                $match[0] = 'http://' . $match[0];
            }
            if (isset($options['callback'])) {
                $cb = $options['callback']($match[0], $caption, $options);
                if (!is_null($cb)) {
                    return $cb;
                }
            }

            return '<a href="' . $match[0] . '"' . $options['attr'] . '>' . $caption . '</a>';
        };

        return preg_replace_callback($pattern, $callback, $text);
    }

    function linkify_safe($text, $options = [])
    {
        $options = array_merge(['attr' => ['rel' => 'nofollow', 'target' => '_blank']], $options);

        return linkify(htmlspecialchars($text), $options);
    }

    /**
     * ERROR HANDLING
     * ---------------------------------------------------------------------.
     */

    // Converts an Exception to a formatted PHP like error
    function exception_to_error($e, $die = true)
    {
        // Debug levels
        // 0:NONE 1:ERROR_LOG 2:PRINT(NO ERROR_LOG) 3:PRINT+ERROR_LOG
        $debug_level = get_app_setting('debug_level');
        $internal_code = 500;
        $internal_error = '<b>Aw, snap!</b> ' . get_set_status_header_desc($internal_code);
        $table = [
            0 => 'debug is disabled',
            1 => 'debug @ `error_log`',
            2 => 'debug @ print',
            3 => 'debug @ print,`error_log`',
        ];
        $internal_error .= ' [' . $table[$debug_level] . '] - https://chevereto-free.github.io/manual/troubleshooting/debug.html';
        set_status_header($internal_code);
        if (!in_array($debug_level, [0, 1, 2, 3])) {
            $debug_level = 1;
        }
        if (in_array($debug_level, [1, 3])) {
            error_log($e);
        }
        if (!in_array($debug_level, [2, 3])) {
            die($internal_error);
        }
        $message = [$internal_error];
        $message[] = '';
        $message[] = '<b>Fatal error [' . $e->getCode() . ']:</b> ' . safe_html($e->getMessage());
        $message[] = 'Triggered in ' . absolute_to_relative($e->getFile()) . ' at line ' . $e->getLine() . "\n";
        $message[] = '<b>Stack trace:</b>';
        $rtn = '';
        $count = 0;
        foreach ($e->getTrace() as $frame) {
            $args = '';
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    switch (true) {
                        case is_string($arg):
                            if (file_exists($arg)) {
                                $arg = absolute_to_relative($arg);
                            }
                            $args[] = "'" . $arg . "'";
                            break;
                        case is_array($arg):
                            $args[] = 'Array';
                            break;
                        case is_null($arg):
                            $args[] = 'NULL';
                            break;
                        case is_bool($arg):
                            $args[] = ($arg) ? 'true' : 'false';
                            break;
                        case is_object($arg):
                            $args[] = get_class($arg);
                            break;
                        case is_resource($arg):
                            $args[] = get_resource_type($arg);
                            break;
                        default:
                            $args[] = $arg;
                            break;
                    }
                }
                $args = join(', ', $args);
            }

            $rtn .= sprintf(
                "#%s %s(%s): %s(%s)\n",
                $count,
                isset($frame['file']) ? absolute_to_relative($frame['file']) : 'unknown file',
                isset($frame['line']) ? $frame['line'] : 'unknown line',
                (isset($frame['class'])) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'],
                $args
            );
            ++$count;
        }
        $message[] = $rtn;

        $message = implode("\n", $message);

        if ($die) {
            echo nl2br($message);
            die();
        } else {
            return $message;
        }
    }

    /**
     * MATH
     * ---------------------------------------------------------------------.
     */
    function fraction_to_decimal($fraction)
    {
        list($top, $bottom) = explode('/', $fraction);

        return $bottom == 0 ? $fraction : ($top / $bottom);
    }

    /**
     * TIME
     * ---------------------------------------------------------------------.
     */

    // Returns current GMT datetime
    function datetimegmt($format = null)
    {
        return gmdate(!is_null($format) ? $format : 'Y-m-d H:i:s');
    }

    // Returns current datetime (for the system timezone)
    function datetime($format = null)
    {
        return date(!is_null($format) ? $format : 'Y-m-d H:i:s');
    }

    // Returns current datetime in the specified timezone
    function datetime_tz($tz, $format = null)
    {
        $date = date_create(null, timezone_open($tz));

        return date_format($date, !is_null($format) ? $format : 'Y-m-d H:i:s');
    }

    // http://stackoverflow.com/a/5878722
    function is_valid_timezone($tzid)
    {
        $valid = [];
        $tza = timezone_abbreviations_list();
        foreach ($tza as $zone) {
            foreach ($zone as $item) {
                $valid[$item['timezone_id']] = true;
            }
        }
        unset($valid['']);

        return (bool) $valid[$tzid];
    }

    // http://stackoverflow.com/a/18602474
    function time_elapsed_string($datetime, $full = false)
    {
        $now = new \DateTime(datetimegmt());
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    function datetimegmt_convert_tz($datetimegmt, $tz)
    {
        if (!is_valid_timezone($tz)) {
            return $datetimegmt;
        }
        $date = new \DateTime($datetimegmt . '+00');
        $date->setTimezone(new \DateTimeZone($tz));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns the difference between two UTC dates in the given format (default seconds)
     * @return integer `$newer - $older`
     */
    function datetime_diff($older, $newer = null, $format = 's')
    {
        if (!in_array($format, ['s', 'm', 'h', 'd'])) {
            $format = 's';
        }
        if (!$newer or $newer == null) {
            $newer = datetimegmt();
        }
        $tz = new \DateTimeZone('UTC');
        $datetime1 = new \DateTime($older, $tz);
        $datetime2 = new \DateTime($newer, $tz);
        $diff = $datetime2->getTimestamp() - $datetime1->getTimestamp(); // In seconds
        $timeconstant = [
            's' => 1,
            'm' => 60,
            'h' => 3600,
            'd' => 86400,
        ];

        return intval($diff / $timeconstant[$format]);
    }

    function datetime_add($datetime, $add)
    {
        return datetime_alter($datetime, $add, 'add');
    }

    function datetime_sub($datetime, $sub)
    {
        return datetime_alter($datetime, $sub, 'sub');
    }

    function datetime_modify($datetime, $var)
    {
        return datetime_alter($datetime, $var, 'modify');
    }

    function datetime_alter($datetime, $var, $action = 'add')
    {
        if (!in_array($action, ['add', 'sub', 'modify'])) {
            return $datetime;
        }
        try {
            $DateTime = new \DateTime($datetime);
            if ($action == 'modify') {
                $DateTime->$action($var);
            } else {
                $interval = dateinterval($var); // validation
                if (!$interval) {
                    return $datetime;
                }
                $DateTime->$action($interval);
            }

            return $DateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . ' in ' . __FUNCTION__ . ' (' . $action . ')', $e->getCode());
        }
    }

    function dateinterval($var)
    {
        try {
            $di = new \DateInterval($var);

            return $di;
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * CLIENT
     * ---------------------------------------------------------------------.
     */
    function get_client_ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    function get_client_languages($getSortedList = true, $acceptedLanguages = false)
    {
        if (empty($acceptedLanguages)) {
            $acceptedLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        // regex inspired from @GabrielAnderson on http://stackoverflow.com/questions/6038236/http-accept-language
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})*)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptedLanguages, $lang_parse);
        $langs = $lang_parse[1];
        $ranks = $lang_parse[4];

        // (create an associative array 'language' => 'preference')
        $lang2pref = array();
        for ($i = 0; $i < count($langs); ++$i) {
            $lang2pref[$langs[$i]] = (float) (!empty($ranks[$i]) ? $ranks[$i] : 1);
        }

        $cmpLangs = function ($a, $b) use ($lang2pref) {
            if ($lang2pref[$a] > $lang2pref[$b]) {
                return -1;
            } elseif ($lang2pref[$a] < $lang2pref[$b]) {
                return 1;
            } elseif (strlen($a) > strlen($b)) {
                return -1;
            } elseif (strlen($a) < strlen($b)) {
                return 1;
            } else {
                return 0;
            }
        };

        // Just in case the server is running eAccelerator
        if (is_callable($cmpLangs)) {
            uksort($lang2pref, $cmpLangs);
        }

        if ($getSortedList) {
            return $lang2pref;
        }
        // return the first value's key
        reset($lang2pref);

        return key($lang2pref);
    }

    /**
     * Parses a user agent string into its important parts.
     *
     * @author Jesse G. Donat <donatj@gmail.com>
     *
     * @see https://github.com/donatj/PhpUserAgent
     * @see http://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
     *
     * @param string|null $u_agent
     *
     * @return array an array with browser, version and platform keys
     */
    function parse_user_agent($u_agent = null)
    {
        if (is_null($u_agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $u_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        $platform = null;
        $browser = null;
        $version = null;

        $empty = array('platform' => $platform, 'browser' => $browser, 'version' => $version);

        if (!$u_agent) {
            return $empty;
        }

        if (preg_match('/\((.*?)\)/im', $u_agent, $parent_matches)) {
            preg_match_all('/(?P<platform>Android|CrOS|iPhone|iPad|Linux|Macintosh|Windows(\ Phone\ OS)?|Silk|linux-gnu|BlackBerry|PlayBook|Nintendo\ (WiiU?|3DS)|Xbox)
				(?:\ [^;]*)?
				(?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);

            $priority = array('Android', 'Xbox');
            $result['platform'] = array_unique($result['platform']);
            if (count($result['platform']) > 1) {
                if ($keys = array_intersect($priority, $result['platform'])) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } elseif (isset($result['platform'][0])) {
                $platform = $result['platform'][0];
            }
        }

        if ($platform == 'linux-gnu') {
            $platform = 'Linux';
        } elseif ($platform == 'CrOS') {
            $platform = 'Chrome OS';
        }

        preg_match_all(
            '%(?P<browser>Camino|Kindle(\ Fire\ Build)?|Firefox|Iceweasel|Safari|MSIE|Trident/.*rv|AppleWebKit|Chrome|IEMobile|Opera|OPR|Silk|Lynx|Midori|Version|Wget|curl|NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
				(?:\)?;?)
				(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent,
            $result,
            PREG_PATTERN_ORDER
        );

        // If nothing matched, return null (to avoid undefined index errors)
        if (!isset($result['browser'][0]) || !isset($result['version'][0])) {
            return $empty;
        }

        $browser = $result['browser'][0];
        $version = $result['version'][0];

        $find = function ($search, &$key) use ($result) {
            $xkey = array_search(strtolower($search), array_map('strtolower', $result['browser']));
            if ($xkey !== false) {
                $key = $xkey;

                return true;
            }

            return false;
        };

        $key = 0;
        if ($browser == 'Iceweasel') {
            $browser = 'Firefox';
        } elseif ($find('Playstation Vita', $key)) {
            $platform = 'PlayStation Vita';
            $browser = 'Browser';
        } elseif ($find('Kindle Fire Build', $key) || $find('Silk', $key)) {
            $browser = $result['browser'][$key] == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif ($find('Kindle', $key)) {
            $browser = $result['browser'][$key];
            $platform = 'Kindle';
            $version = $result['version'][$key];
        } elseif ($find('OPR', $key)) {
            $browser = 'Opera Next';
            $version = $result['version'][$key];
        } elseif ($find('Opera', $key)) {
            $browser = 'Opera';
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($find('Chrome', $key)) {
            $browser = 'Chrome';
            $version = $result['version'][$key];
        } elseif ($find('Midori', $key)) {
            $browser = 'Midori';
            $version = $result['version'][$key];
        } elseif ($browser == 'AppleWebKit') {
            if (($platform == 'Android' && !($key = 0))) {
                $browser = 'Android Browser';
            } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
                $browser = 'BlackBerry Browser';
            } elseif ($find('Safari', $key)) {
                $browser = 'Safari';
            }

            $find('Version', $key);

            $version = $result['version'][$key];
        } elseif ($browser == 'MSIE' || strpos($browser, 'Trident') !== false) {
            if ($find('IEMobile', $key)) {
                $browser = 'IEMobile';
            } else {
                $browser = 'MSIE';
                $key = 0;
            }
            $version = $result['version'][$key];
        } elseif ($key = preg_grep("/playstation \d/i", array_map('strtolower', $result['browser']))) {
            $key = reset($key);

            $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $key);
            $browser = 'NetFront';
        }

        return array('platform' => $platform, 'browser' => $browser, 'version' => $version);
    }

    /**
     * MISC. VALIDATIONS
     * ---------------------------------------------------------------------.
     */

    // This checks for real email address.
    function is_real_email_address($email)
    {
        $valid = true;
        $atIndex = strrpos($email, '@');
        if (is_bool($atIndex) && !$atIndex) {
            $valid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $valid = false;
            } elseif ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $valid = false;
            } elseif ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $valid = false;
            } elseif (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $valid = false;
            } elseif (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $valid = false;
            } elseif (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $valid = false;
            } elseif (!preg_match(
                '/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                str_replace('\\\\', '', $local)
            )) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace('\\\\', '', $local))) {
                    $valid = false;
                }
            }
            if ($valid && !(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))) {
                // domain not found in DNS
                $valid = false;
            }
        }

        return $valid;
    }

    function is_valid_hex_color($string, $prefix = true)
    {
        return preg_match('/#' . ($prefix ? '?' : null) . '([\da-fA-F]{2})([\da-fA-F]{2})([\da-fA-F]{2})/', $string);
    }

    function is_valid_ip($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * SANITIZATION
     * ---------------------------------------------------------------------.
     */

    // Removes all the spaces on a given string
    function remove_spaces($string)
    {
        return str_replace(' ', '', $string);
    }

    // Sanitizes double or more slahes in a path
    function sanitize_path_slashes($path)
    {
        return preg_replace('#/+#', '/', $path);
    }

    function sanitize_directory_separator($path)
    {
        return preg_replace('#' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, $path);
    }

    function sanitize_relative_path($path)
    {
        $path = forward_slash($path);
        $path = sanitize_path_slashes($path);
        $path = preg_replace('#(\.+/)+#', '', $path);
        $path = sanitize_path_slashes($path);

        return $path;
    }

    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Returns a sanitized string, typically for URLs
     * This function was borrowed from chyrp.net (MIT License).
     */
    function sanitize_string($string, $force_lowercase = true, $only_alphanumerics = false, $truncate = 100)
    {
        $strip = array(
            '~', '`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '=', '+', '{',
            '}', '\\', '|', ';', ':', '\'', "'", '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8211;', '&#8212;',
            'â€”', 'â€“', ',', '<', '.', '>', '/', '?',
        );
        $clean = trim(str_replace($strip, '', strip_tags($string)));
        $clean = preg_replace('/\s+/', '-', $clean);
        $clean = ($only_alphanumerics ? preg_replace('/[^a-zA-Z0-9]/', '', $clean) : $clean);
        $clean = ($truncate ? substr($clean, 0, $truncate) : $clean);

        return ($force_lowercase) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;
    }

    // Original PHP code by Chirp Internet: www.chirp.com.au
    function truncate($string, $limit, $break = null, $pad = '...')
    {
        $encoding = 'UTF-8';
        if (mb_strlen($string, $encoding) <= $limit) {
            return $string;
        }
        if (is_null($break) or $break == '') {
            $string = trim(mb_substr($string, 0, $limit - strlen($pad), $encoding)) . $pad;
        } else {
            if (false !== ($breakpoint = strpos($string, $break, $limit))) {
                if ($breakpoint < mb_strlen($string, $encoding) - 1) {
                    $string = mb_substr($string, 0, $breakpoint, $encoding) . $pad;
                }
            }
        }

        return $string;
    }

    // Thanks to http://www.evaisse.net/2008/php-translit-remove-accent-unaccent-21001
    function unaccent_string($string)
    {
        $string = (string) $string;

        if (function_exists('mb_detect_encoding')) {
            $utf8 = strtolower(mb_detect_encoding($string)) == 'utf-8';
        } else {
            $length = strlen($string);
            $utf8 = true;
            for ($i = 0; $i < $length; ++$i) {
                $c = ord($string[$i]);
                if ($c < 0x80) {
                    $n = 0;
                } // 0bbbbbbb
                elseif (($c & 0xE0) == 0xC0) {
                    $n = 1;
                } // 110bbbbb
                elseif (($c & 0xF0) == 0xE0) {
                    $n = 2;
                } // 1110bbbb
                elseif (($c & 0xF8) == 0xF0) {
                    $n = 3;
                } // 11110bbb
                elseif (($c & 0xFC) == 0xF8) {
                    $n = 4;
                } // 111110bb
                elseif (($c & 0xFE) == 0xFC) {
                    $n = 5;
                } // 1111110b
                else {
                    return false;
                } // Does not match any model
                for ($j = 0; $j < $n; ++$j) { // n bytes matching 10bbbbbb follow ?
                    if ((++$i == $length)
                        || ((ord($string[$i]) & 0xC0) != 0x80)
                    ) {
                        $utf8 = false;
                        break;
                    }
                }
            }
        }

        if (!$utf8) {
            $string = utf8_encode($string);
        }

        $transliteration = array(
            'Ĳ' => 'I', 'Ö' => 'O', 'Œ' => 'O', 'Ü' => 'U', 'ä' => 'a', 'æ' => 'a',
            'ĳ' => 'i', 'ö' => 'o', 'œ' => 'o', 'ü' => 'u', 'ß' => 's', 'ſ' => 's',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Ç' => 'C', 'Ć' => 'C',
            'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'È' => 'E',
            'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ě' => 'E',
            'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
            'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĵ' => 'J',
            'Ķ' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ł' => 'L',
            'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O',
            'Ŏ' => 'O', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Ş' => 'S',
            'Ŝ' => 'S', 'Ș' => 'S', 'Š' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T',
            'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ū' => 'U', 'Ů' => 'U',
            'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ŷ' => 'Y',
            'Ÿ' => 'Y', 'Ý' => 'Y', 'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z', 'à' => 'a',
            'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a',
            'å' => 'a', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c',
            'ď' => 'd', 'đ' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f',
            'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i',
            'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k',
            'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n',
            'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o',
            'ŏ' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'ś' => 's', 'š' => 's',
            'ť' => 't', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ū' => 'u', 'ů' => 'u',
            'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ÿ' => 'y',
            'ý' => 'y', 'ŷ' => 'y', 'ż' => 'z', 'ź' => 'z', 'ž' => 'z', 'Α' => 'A',
            'Ά' => 'A', 'Ἀ' => 'A', 'Ἁ' => 'A', 'Ἂ' => 'A', 'Ἃ' => 'A', 'Ἄ' => 'A',
            'Ἅ' => 'A', 'Ἆ' => 'A', 'Ἇ' => 'A', 'ᾈ' => 'A', 'ᾉ' => 'A', 'ᾊ' => 'A',
            'ᾋ' => 'A', 'ᾌ' => 'A', 'ᾍ' => 'A', 'ᾎ' => 'A', 'ᾏ' => 'A', 'Ᾰ' => 'A',
            'Ᾱ' => 'A', 'Ὰ' => 'A', 'ᾼ' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D',
            'Ε' => 'E', 'Έ' => 'E', 'Ἐ' => 'E', 'Ἑ' => 'E', 'Ἒ' => 'E', 'Ἓ' => 'E',
            'Ἔ' => 'E', 'Ἕ' => 'E', 'Ὲ' => 'E', 'Ζ' => 'Z', 'Η' => 'I', 'Ή' => 'I',
            'Ἠ' => 'I', 'Ἡ' => 'I', 'Ἢ' => 'I', 'Ἣ' => 'I', 'Ἤ' => 'I', 'Ἥ' => 'I',
            'Ἦ' => 'I', 'Ἧ' => 'I', 'ᾘ' => 'I', 'ᾙ' => 'I', 'ᾚ' => 'I', 'ᾛ' => 'I',
            'ᾜ' => 'I', 'ᾝ' => 'I', 'ᾞ' => 'I', 'ᾟ' => 'I', 'Ὴ' => 'I', 'ῌ' => 'I',
            'Θ' => 'T', 'Ι' => 'I', 'Ί' => 'I', 'Ϊ' => 'I', 'Ἰ' => 'I', 'Ἱ' => 'I',
            'Ἲ' => 'I', 'Ἳ' => 'I', 'Ἴ' => 'I', 'Ἵ' => 'I', 'Ἶ' => 'I', 'Ἷ' => 'I',
            'Ῐ' => 'I', 'Ῑ' => 'I', 'Ὶ' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M',
            'Ν' => 'N', 'Ξ' => 'K', 'Ο' => 'O', 'Ό' => 'O', 'Ὀ' => 'O', 'Ὁ' => 'O',
            'Ὂ' => 'O', 'Ὃ' => 'O', 'Ὄ' => 'O', 'Ὅ' => 'O', 'Ὸ' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Ῥ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Ύ' => 'Y',
            'Ϋ' => 'Y', 'Ὑ' => 'Y', 'Ὓ' => 'Y', 'Ὕ' => 'Y', 'Ὗ' => 'Y', 'Ῠ' => 'Y',
            'Ῡ' => 'Y', 'Ὺ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'P', 'Ω' => 'O',
            'Ώ' => 'O', 'Ὠ' => 'O', 'Ὡ' => 'O', 'Ὢ' => 'O', 'Ὣ' => 'O', 'Ὤ' => 'O',
            'Ὥ' => 'O', 'Ὦ' => 'O', 'Ὧ' => 'O', 'ᾨ' => 'O', 'ᾩ' => 'O', 'ᾪ' => 'O',
            'ᾫ' => 'O', 'ᾬ' => 'O', 'ᾭ' => 'O', 'ᾮ' => 'O', 'ᾯ' => 'O', 'Ὼ' => 'O',
            'ῼ' => 'O', 'α' => 'a', 'ά' => 'a', 'ἀ' => 'a', 'ἁ' => 'a', 'ἂ' => 'a',
            'ἃ' => 'a', 'ἄ' => 'a', 'ἅ' => 'a', 'ἆ' => 'a', 'ἇ' => 'a', 'ᾀ' => 'a',
            'ᾁ' => 'a', 'ᾂ' => 'a', 'ᾃ' => 'a', 'ᾄ' => 'a', 'ᾅ' => 'a', 'ᾆ' => 'a',
            'ᾇ' => 'a', 'ὰ' => 'a', 'ᾰ' => 'a', 'ᾱ' => 'a', 'ᾲ' => 'a', 'ᾳ' => 'a',
            'ᾴ' => 'a', 'ᾶ' => 'a', 'ᾷ' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd',
            'ε' => 'e', 'έ' => 'e', 'ἐ' => 'e', 'ἑ' => 'e', 'ἒ' => 'e', 'ἓ' => 'e',
            'ἔ' => 'e', 'ἕ' => 'e', 'ὲ' => 'e', 'ζ' => 'z', 'η' => 'i', 'ή' => 'i',
            'ἠ' => 'i', 'ἡ' => 'i', 'ἢ' => 'i', 'ἣ' => 'i', 'ἤ' => 'i', 'ἥ' => 'i',
            'ἦ' => 'i', 'ἧ' => 'i', 'ᾐ' => 'i', 'ᾑ' => 'i', 'ᾒ' => 'i', 'ᾓ' => 'i',
            'ᾔ' => 'i', 'ᾕ' => 'i', 'ᾖ' => 'i', 'ᾗ' => 'i', 'ὴ' => 'i', 'ῂ' => 'i',
            'ῃ' => 'i', 'ῄ' => 'i', 'ῆ' => 'i', 'ῇ' => 'i', 'θ' => 't', 'ι' => 'i',
            'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i', 'ἰ' => 'i', 'ἱ' => 'i', 'ἲ' => 'i',
            'ἳ' => 'i', 'ἴ' => 'i', 'ἵ' => 'i', 'ἶ' => 'i', 'ἷ' => 'i', 'ὶ' => 'i',
            'ῐ' => 'i', 'ῑ' => 'i', 'ῒ' => 'i', 'ῖ' => 'i', 'ῗ' => 'i', 'κ' => 'k',
            'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'k', 'ο' => 'o', 'ό' => 'o',
            'ὀ' => 'o', 'ὁ' => 'o', 'ὂ' => 'o', 'ὃ' => 'o', 'ὄ' => 'o', 'ὅ' => 'o',
            'ὸ' => 'o', 'π' => 'p', 'ρ' => 'r', 'ῤ' => 'r', 'ῥ' => 'r', 'σ' => 's',
            'ς' => 's', 'τ' => 't', 'υ' => 'y', 'ύ' => 'y', 'ϋ' => 'y', 'ΰ' => 'y',
            'ὐ' => 'y', 'ὑ' => 'y', 'ὒ' => 'y', 'ὓ' => 'y', 'ὔ' => 'y', 'ὕ' => 'y',
            'ὖ' => 'y', 'ὗ' => 'y', 'ὺ' => 'y', 'ῠ' => 'y', 'ῡ' => 'y', 'ῢ' => 'y',
            'ῦ' => 'y', 'ῧ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'p', 'ω' => 'o',
            'ώ' => 'o', 'ὠ' => 'o', 'ὡ' => 'o', 'ὢ' => 'o', 'ὣ' => 'o', 'ὤ' => 'o',
            'ὥ' => 'o', 'ὦ' => 'o', 'ὧ' => 'o', 'ᾠ' => 'o', 'ᾡ' => 'o', 'ᾢ' => 'o',
            'ᾣ' => 'o', 'ᾤ' => 'o', 'ᾥ' => 'o', 'ᾦ' => 'o', 'ᾧ' => 'o', 'ὼ' => 'o',
            'ῲ' => 'o', 'ῳ' => 'o', 'ῴ' => 'o', 'ῶ' => 'o', 'ῷ' => 'o', 'А' => 'A',
            'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E',
            'Ж' => 'Z', 'З' => 'Z', 'И' => 'I', 'Й' => 'I', 'К' => 'K', 'Л' => 'L',
            'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S',
            'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'K', 'Ц' => 'T', 'Ч' => 'C',
            'Ш' => 'S', 'Щ' => 'S', 'Ы' => 'Y', 'Э' => 'E', 'Ю' => 'Y', 'Я' => 'Y',
            'а' => 'A', 'б' => 'B', 'в' => 'V', 'г' => 'G', 'д' => 'D', 'е' => 'E',
            'ё' => 'E', 'ж' => 'Z', 'з' => 'Z', 'и' => 'I', 'й' => 'I', 'к' => 'K',
            'л' => 'L', 'м' => 'M', 'н' => 'N', 'о' => 'O', 'п' => 'P', 'р' => 'R',
            'с' => 'S', 'т' => 'T', 'у' => 'U', 'ф' => 'F', 'х' => 'K', 'ц' => 'T',
            'ч' => 'C', 'ш' => 'S', 'щ' => 'S', 'ы' => 'Y', 'э' => 'E', 'ю' => 'Y',
            'я' => 'Y', 'ð' => 'd', 'Ð' => 'D', 'þ' => 't', 'Þ' => 'T', 'ა' => 'a',
            'ბ' => 'b', 'გ' => 'g', 'დ' => 'd', 'ე' => 'e', 'ვ' => 'v', 'ზ' => 'z',
            'თ' => 't', 'ი' => 'i', 'კ' => 'k', 'ლ' => 'l', 'მ' => 'm', 'ნ' => 'n',
            'ო' => 'o', 'პ' => 'p', 'ჟ' => 'z', 'რ' => 'r', 'ს' => 's', 'ტ' => 't',
            'უ' => 'u', 'ფ' => 'p', 'ქ' => 'k', 'ღ' => 'g', 'ყ' => 'q', 'შ' => 's',
            'ჩ' => 'c', 'ც' => 't', 'ძ' => 'd', 'წ' => 't', 'ჭ' => 'c', 'ხ' => 'k',
            'ჯ' => 'j', 'ჰ' => 'h', 'ḩ' => 'h', 'ừ' => 'u', 'ế' => 'e', 'ả' => 'a',
            'ị' => 'i', 'ậ' => 'a', 'ệ' => 'e', 'ỉ' => 'i', 'ộ' => 'o', 'ồ' => 'o',
            'ề' => 'e', 'ơ' => 'o', 'ạ' => 'a', 'ẵ' => 'a', 'ư' => 'u', 'ắ' => 'a',
            'ằ' => 'a', 'ầ' => 'a', 'ḑ' => 'd', 'Ḩ' => 'H', 'Ḑ' => 'D', 'ḑ' => 'd',
            'ş' => 's', 'ā' => 'a', 'ţ' => 't', 'ễ' => 'e',
        );
        $string = str_replace(array_keys($transliteration), array_values($transliteration), $string);

        // Missing something?
        if (strpos($string = htmlentities($string, ENT_QUOTES, 'UTF-8'), '&') !== false) {
            $string = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');
        }

        return $string;
    }

    // Safe for HTML output
    function safe_html($var, $flag = ENT_QUOTES)
    {
        if (!is_array($var)) {
            return $var === null ? null : htmlspecialchars($var, $flag, 'UTF-8'); // htmlspecialchars keeps ñ, á and all the UTF-8 valid chars
        }
        $safe_array = array();
        foreach ($var as $k => $v) {
            $safe_array[$k] = is_array($v) ? safe_html($v) : ($v === null ? null : htmlspecialchars($v, $flag, 'UTF-8'));
        }

        return $safe_array;
    }

    /**
     * BYTE AND NUMBER HANDLING
     * ---------------------------------------------------------------------.
     */

    // Converts bytes to whatever
    function format_bytes($bytes, $round = 1)
    {
        if (!is_numeric($bytes)) {
            return false;
        }
        if ($bytes < 1000) {
            return "$bytes B";
        }
        $units = ['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        foreach ($units as $k => $v) {
            $multiplier = pow(1000, $k + 1);
            $threshold = $multiplier * 1000;
            if ($bytes < $threshold) {
                $size = round($bytes / $multiplier, $round);

                return "$size $v";
            }
        }
    }

    // Returns bytes for SIZE + Suffix format (dec + bin)
    function get_bytes($size, $cut = null)
    {
        if ($cut == null) {
            $suffix = substr($size, -3);
            $suffix = preg_match('/([A-Za-z]){3}/', $suffix) ? $suffix : substr($size, -2);
        } else {
            $suffix = substr($size, $cut);
        }

        $suffix = strtoupper($suffix);

        $units = ['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']; // Default dec units

        if (strlen($suffix) == 3) { // Convert units to bin
            foreach ($units as &$unit) {
                $split = str_split($unit);
                $unit = $split[0] . 'I' . $split[1];
            }
        }

        if (strlen($suffix) == 1) {
            $suffix .= 'B'; // Adds missing "B" for shorthand ini notation (Turns 1G into 1GB)
        }
        if (!in_array($suffix, $units)) {
            return $size;
        }
        $pow_factor = array_search($suffix, $units) + 1;

        return $size * pow(strlen($suffix) == 2 ? 1000 : 1024, $pow_factor);
    }

    // Converts bytes to MB
    function bytes_to_mb($bytes)
    {
        return round($bytes / pow(10, 6));
    }

    // Returns bytes (used for the ini_get functions)
    function get_ini_bytes($size)
    {
        return get_bytes($size, -1);
    }

    /**
     * PATHS AND URL HANDLING
     * ---------------------------------------------------------------------.
     */

    // Add trailing slashes
    function add_trailing_slashes($string)
    {
        return add_ending_slash(add_starting_slash($string));
    }

    function add_starting_slash($string)
    {
        return '/' . ltrim($string, '/');
    }

    function add_ending_slash($string)
    {
        return rtrim($string, '/') . '/';
    }

    // Converts backslash into forward slash
    function forward_slash($string)
    {
        return str_replace('\\', '/', $string);
    }

    // Converts relative path to absolute path
    function relative_to_absolute($filepath)
    {
        return str_replace(G_ROOT_PATH_RELATIVE, G_ROOT_PATH, forward_slash($filepath));
    }

    // Converts relative path to url
    function relative_to_url($filepath, $root_url = null)
    {
        if (!check_value($root_url)) {
            $root_url = get_root_url();
        }

        return str_replace(G_ROOT_PATH_RELATIVE, $root_url, forward_slash($filepath));
    }

    // Converts app URL to relative path
    function url_to_relative($url, $root_url = null)
    {
        if (!check_value($root_url)) {
            $root_url = get_root_url();
        }

        return str_replace($root_url, G_ROOT_PATH_RELATIVE, $url);
    }

    // Converts absolute path to relative path
    function absolute_to_relative($filepath)
    {
        return str_replace(G_ROOT_PATH, G_ROOT_PATH_RELATIVE, forward_slash($filepath));
    }

    // Converts absolute path to URL
    function absolute_to_url($filepath, $root_url = null)
    {
        if (!check_value($root_url)) {
            $root_url = get_root_url();
        }
        if (G_ROOT_PATH === G_ROOT_PATH_RELATIVE) {
            return $root_url . ltrim($filepath, '/');
        }

        return str_replace(G_ROOT_PATH, $root_url, forward_slash($filepath));
    }

    // Converts app URL to absolute path
    function url_to_absolute($url, $root_url = null)
    {
        if (!check_value($root_url)) {
            $root_url = get_root_url();
        }

        return str_replace($root_url, G_ROOT_PATH, $url);
    }

    /**
     * GET AND FETCH SOME DATA
     * ---------------------------------------------------------------------.
     */

    // Get current G\ version
    function get_version()
    {
        return G_VERSION;
    }

    // Get the current app version
    function get_app_version($full = true)
    {
        if ($full) {
            return G_APP_VERSION;
        } else {
            preg_match('/\d\.\d/', G_APP_VERSION, $return);

            return $return[0];
        }
    }

    // Get the $settings value for the app
    function get_app_setting($key)
    {
        return get_global('settings')[$key];
    }

    function get_base_url($path = '')
    {
        $path = sanitize_relative_path($path);
        $return = get_root_url() . ltrim($path, '/');

        return rtrim($return, '/');
    }

    function get_host()
    {
        return defined('APP_G_HTTP_HOST') ? APP_G_HTTP_HOST : G_HTTP_HOST;
    }

    function get_host_domain()
    {
        $url = get_root_url();

        return parse_url($url)['host'];
    }

    function get_root_url()
    {
        return defined('APP_G_ROOT_URL') ? APP_G_ROOT_URL : G_ROOT_URL;
    }

    /**
     * @param string Querystring keys to remove (comma separated)
     */
    function get_current_url($safe = true, $removeQs = [])
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_path = rtrim(strtok($request_uri, '?'), '/');
        if ($_SERVER['QUERY_STRING'] && $removeQs) {
            parse_str($_SERVER['QUERY_STRING'], $parse);
            foreach ($removeQs as $v) {
                unset($parse[$v]);
            }
            $querystring = $parse ? http_build_query($parse) : null;
            $request_uri = $request_path;
            if ($querystring) {
                $request_uri .= '/?' . $querystring;
            }
        }
        $path = preg_replace('#' . G_ROOT_PATH_RELATIVE . '#', '', rtrim($request_uri, '/') . '/', 1);

        return get_base_url($path);
    }

    function settings_has_db_info()
    {
        $settings = get_global('settings');
        if (!is_array($settings)) {
            return false;
        }
        $has = true;
        foreach (['db_driver', 'db_host', 'db_name', 'db_user'] as $k) {
            if (!array_key_exists($k, $settings)) {
                $has = false;
                break;
            }
        }

        return $has;
    }

    function get_regex_match($regex, $delimiter = '/', $subject, $key = null)
    {
        preg_match($delimiter . $regex . $delimiter, $subject, $matches);
        if (array_key_exists($key, $matches)) {
            return $matches[$key];
        } else {
            return $matches;
        }
    }

    /**
     * Fetch the contents from an URL
     * if $file is set the downloaed file will be saved there.
     */
    function fetch_url($url, $file = null, $options = [])
    {
        if (!$url) {
            throw new Exception('missing $url in ' . __FUNCTION__);

            return false;
        }

        if (ini_get('allow_url_fopen') !== 1 && !function_exists('curl_init')) {
            throw new Exception('Fatal error in ' . __FUNCTION__ . ": cURL isn't installed and allow_url_fopen is disabled. Can't perform HTTP requests.");

            return false;
        }

        // File get contents is the failover fn
        $fn = (!function_exists('curl_init') ? 'fgc' : 'curl');

        if ($fn == 'curl') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FAILONERROR, 0);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            curl_setopt($ch, CURLOPT_VERBOSE, 0);

            if (is_array($options) && count($options) > 0) {
                foreach ($options as $k => $v) {
                    curl_setopt($ch, $k, $v);
                }
            }

            if ($file) {
                // Save the file to $file destination
                $out = @fopen($file, 'wb');
                if (!$out) {
                    throw new Exception("Can't open " . __FUNCTION__ . '() file for read and write');

                    return false;
                }
                curl_setopt($ch, CURLOPT_FILE, $out);
                @curl_exec($ch);
                fclose($out);
            } else {
                // Return the file string
                $file_get_contents = @curl_exec($ch);
            }
            if (curl_errno($ch)) {
                $curl_error = curl_error($ch);
                curl_close($ch);
                throw new Exception('Curl error ' . $curl_error);

                return false;
            }
            if ($file == null) {
                curl_close($ch);

                return $file_get_contents;
            }
        } else {
            $context = stream_context_create([
                'http' => ['ignore_errors' => true, 'follow_location' => false],
            ]);
            $result = @file_get_contents($url, false, $context);
            if (!$result) {
                throw new Exception("Can't fetch target URL (file_get_contents)");

                return false;
            }
            if ($file) {
                if (file_put_contents($file, $result) === false) {
                    throw new Exception("Can't fetch target URL (file_put_contents)");

                    return false;
                }
            } else {
                return $result;
            }
        }
    }

    /* get complete raw, add success + error properties */
    function getUrlHeaders($url, $options = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.81 Safari/537.36');
        // Inject custom options
        if (is_array($options)) {
            foreach ($options as $k => $v) {
                curl_setopt($ch, $k, $v);
            }
        }
        $raw = curl_exec($ch);
        if (curl_errno($ch)) {
            $return['error'] = curl_error($ch);
            $return['http_code'] = 500;
        } else {
            $return = curl_getinfo($ch);
            $return['raw'] = $raw;
        }
        curl_close($ch);

        return $return;
    }

    // Returns float Execution time at this point of call
    function get_execution_time()
    {
        return microtime(true) - G_APP_TIME_EXECUTION_START;
    }

    // Get bcrypt optimal cost
    function bcrypt_cost($time = 0.2, $cost = 9)
    {
        do {
            ++$cost;
            $inicio = microtime(true);
            password_hash('test', PASSWORD_BCRYPT, ['cost' => $cost]);
            $fin = microtime(true);
        } while (($fin - $inicio) < $time);

        return $cost;
    }

    /**
     * CONDITIONALS
     * ---------------------------------------------------------------------.
     */

    // Checks if $var is a positive integer using filter_var
    function is_integer($var, $range = [])
    {
        $options = [];
        if (!empty($range) && is_array($range)) {
            foreach (['min', 'max'] as $k) {
                if (is_int($range[$k])) {
                    $options['options'][$k . '_range'] = $range[$k];
                }
            }
        }

        return filter_var($var, FILTER_VALIDATE_INT, $options) !== false;
    }

    // This will tell if the string is an URL
    function is_url($string)
    {
        // Only for strings
        if (!is_string($string)) {
            return false;
        }
        // Internal PHP system
        if (filter_var($string, FILTER_VALIDATE_URL)) { // Note: This doesn't validate any non-alphanumeric URL
            return true;
        }
        // Test if it has foreign chars
        if (strlen($string) !== strlen(utf8_encode($string))) {
            $parsed_url = parse_url($string);
            if (count($parsed_url) < 2) { // At least scheme and host
                return false;
            }
            $schemes = ['http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp'];
            if (!in_array(strtolower($parsed_url['scheme']), $schemes)) { // Must be a valid scheme
                return false;
            }
            if (!array_key_exists('host', $parsed_url)) { // Host must be there
                return false;
            }
            // At this point this thing looks like an URL
            return true;
        }

        return false;
    }

    // Tells if the given url is https or not
    function is_https($string)
    {
        return strpos($string, 'https://') !== false;
    }

    // Tell if the string is an URL and if is valid
    function is_valid_url($string)
    {
        if (!is_url($string)) {
            return false;
        }

        $url = preg_replace('/^https/', 'http', $string, 1);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 0);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $result = @curl_exec($ch);
            curl_close($ch);

            return $result !== false ? true : false;
        } else {
            if (ini_get('allow_url_fopen')) {
                $result = file_get_contents($url);

                return $result === false ? false : true;
            }
        }
    }

    // Tell if the string is an image URL
    function is_image_url($string)
    {
        if (!is_string($string)) {
            return false;
        }

        return preg_match('/(?:ftp|https?):\/\/(\w+:\w+@)?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(:[0-9]{1,4}){0,1}|(?:[\w\-]+\.)+[a-z]{2,6})(?:\/[^\/#\?]+)+\.(?:jpe?g|gif|png|bmp)/i', $string);
    }

    // Returns true if the system is in development mode
    // nota: buscar los is_localhost()
    function is_development_env()
    {
        return G_APP_ENV == 'develompent';
    }

    function is_windows_os()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? true : false;
    }

    function is_animated_image($filename)
    {
        $extension = get_file_extension($filename);
        switch ($extension) {
            case 'gif':
                return is_animated_gif($filename);
                break;
            case 'png':
                return is_animated_png($filename);
                break;
            case 'webp':
                return is_animated_webp($filename);
                break;
            default:
                return false;
                break;
        }
    }

    // Taken from http://php.net/manual/en/function.imagecreatefromgif.php#104473
    function is_animated_gif($filename)
    {
        if (!($fh = @fopen($filename, 'rb'))) {
            return false;
        }
        $count = 0;
        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
        }
        fclose($fh);

        return $count > 1;
    }

    // Taken from https://stackoverflow.com/a/4525194/1145912
    function is_animated_png($filename)
    {
        $img_bytes = file_get_contents($filename);
        if ($img_bytes) {
            if (strpos(
                substr($img_bytes, 0, strpos($img_bytes, 'IDAT')),
                'acTL'
            ) !== false) {
                return true;
            }
        }
        return false;
    }

    function is_animated_webp($filename)
    {
        $result = false;
        $fh = fopen($filename, "rb");
        fseek($fh, 12);
        if (fread($fh, 4) === 'VP8X') {
            fseek($fh, 16);
            $myByte = fread($fh, 1);
            $result = ((ord($myByte) >> 1) & 1) ? true : false;
        }
        fclose($fh);
        return $result;
    }

    /**
     * FILE RELATED
     * ---------------------------------------------------------------------.
     */

    // Get mimetype of $file according to your php version
    function get_mimetype($file)
    {
        if (function_exists('finfo_open')) {
            return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        } else {
            if (function_exists('mime_content_type')) {
                return mime_content_type($file);
            } else {
                return extension_to_mime(get_file_extension($file));
            }
        }
    }

    // For now this only works for images
    function mime_to_extension($mime, $reverse = false)
    {
        $mime_to_extension = array(
            'image/x-windows-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'image/bmp' => 'bmp',
            'image/gif' => 'gif',
            'image/pjpeg' => 'jpg',
            'image/jpeg' => 'jpg',
            'image/x-png' => 'png',
            'image/png' => 'png',
            'image/x-tiff' => 'tiff',
            'image/tiff' => 'tiff',
            'image/x-icon' => 'ico',
            'image/x-rgb' => 'rgb',
            'image/webp' => 'webp',
        );

        // Used to get ext -> mime
        if ($reverse) {
            $mime_to_extension = array_flip($mime_to_extension);
        }

        return $mime_to_extension[$mime];
    }

    function extension_to_mime($ext)
    {
        return mime_to_extension($ext, true);
    }

    // Retrieves info about the current image file
    function get_image_fileinfo($file)
    {
        clearstatcache(true, $file); // http://php.net/manual/es/function.clearstatcache.php (add G\ magic for those)

        $info = getimagesize($file);
        $filesize = @filesize($file);

        if (!$info || $filesize === false) {
            return false;
        }

        $mime = strtolower($info['mime']);

        return array(
            'filename' => basename($file), // image.jpg
            'name' => basename($file, '.' . get_file_extension($file)), // image
            'width' => intval($info[0]),
            'height' => intval($info[1]),
            'ratio' => $info[0] / $info[1],
            'size' => intval($filesize),
            'size_formatted' => format_bytes($filesize),
            'mime' => $mime,
            'extension' => mime_to_extension($mime),
            'bits' => $info['bits'],
            'channels' => $info['channels'],
            'url' => absolute_to_url($file),
            'md5' => md5_file($file),
        );
    }

    function get_file_extension($file)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    function get_filename($file)
    {
        return basename($file);
    }

    function get_basename_without_extension($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = basename($filename);

        return str_replace_last(".$extension", null, $filename);
    }

    function get_pathname_without_extension($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return str_replace_last(".$extension", null, $filename);
    }

    function change_pathname_extension($filename, $extension)
    {
        $chop = get_pathname_without_extension($filename);
        if ($chop == $filename) {
            return $filename;
        }

        return "$chop.$extension";
    }

    /**
     * This creates .htaccess file on the target dir using the param rules
     * This can be also used to add rules to a existing .htaccess file.
     */
    function generate_htaccess($rules, $directory, $before = null, $output = false)
    {
        $htaccess = $directory . '.htaccess';

        $rules_stock = [
            'static' => '<Files .*>' . "\n" .
                'order allow,deny' . "\n" .
                'deny from all' . "\n" .
                '</Files>' . "\n\n" .
                'AddHandler cgi-script .php .php3 .phtml .pl .py .jsp .asp .htm .shtml .sh .cgi .fcgi' . "\n" .
                'Options -ExecCGI',

            'deny_php' => '<FilesMatch "\.php$">' . "\n" .
                'Order Deny,Allow' . "\n" .
                'Deny from all' . "\n" .
                '</FilesMatch>',

            'deny' => 'deny from all',
        ];

        if (array_key_exists($rules, $rules_stock)) {
            $rules = $rules_stock[$rules];
        }

        if (file_exists($htaccess)) {
            $fgc = file_get_contents($htaccess);
            if (strpos($fgc, $rules)) {
                $done = true;
            } else {
                if (!is_null($before)) {
                    $rules = str_replace($before, $rules . "\n" . $before, $fgc);
                    $f = 'w';
                } else {
                    $rules = "\n\n" . $rules;
                    $f = 'a';
                }
            }
        } else {
            $f = 'w';
        }

        if (!$done) {
            $fh = @fopen($htaccess, $f);
            if (!$fh) {
                return false;
            }
            if (fwrite($fh, $rules)) {
                @fclose($fh);

                return $output ? $rules : true;
            } else {
                @fclose($fh);

                return $output ? $rules : false;
            }
        } else {
            return $output ? $rules : true;
        }
    }

    /**
     * Get a safe filename
     * $method: original | random | mixed | id
     * $filename: name of the original file.
     */
    function get_filename_by_method($method, $filename)
    {
        $max_length = 200; // Safe limit, ideally this should be 255 - 4

        $extension = get_file_extension($filename);
        $clean_filename = substr($filename, 0, - (strlen($extension) + 1));
        $clean_filename = unaccent_string($clean_filename); // change áéíóú to aeiou
        $clean_filename = preg_replace('/\s+/', '-', $clean_filename); // change all spaces with dash
        $clean_filename = trim($clean_filename, '-'); // get rid of those ugly dashes
        $clean_filename = preg_replace('/[^\.\w\d-]/i', '', $clean_filename); // remove any non alphanumeric, non underscore, non hyphen and non dot

        // Non alphanumeric name uh..
        if (strlen($clean_filename) == 0) {
            $clean_filename = random_string(32);
        }

        $unlimited_filename = $clean_filename; // No max_length limit
        $capped_filename = substr($clean_filename, 0, $max_length); // 1->200

        switch ($method) {
            default:
            case 'original':
                $name = $capped_filename;
                break;
            case 'random':
                $name = random_string(32);
                break;
            case 'mixed':
                $mixed_chars_length = 16;
                $mixed_chars = random_string($mixed_chars_length);
                if (strlen($capped_filename) + $mixed_chars_length > $max_length) {
                    // Bring the scissors Morty
                    $capped_filename = substr($capped_filename, 0, $max_length - $mixed_chars_length - strlen($capped_filename));
                    // Well done Morty you little piece of shit
                }
                $name = $capped_filename . $mixed_chars;
                break;
            case 'id':
                $name = $unlimited_filename;
                break;
        }

        return $name . '.' . $extension; // 200 + 4
    }

    function name_unique_file($path, $method = 'original', $filename)
    {
        $file = $path . get_filename_by_method($method, $filename);
        if ($method == 'id') {
            return $file;
        }
        while (file_exists($file)) {
            if ($method == 'original') {
                $method = 'mixed';
            }
            $file = $path . get_filename_by_method($method, $filename);
        }

        return $file;
    }

    /**
     * IMAGE RELATED
     * ---------------------------------------------------------------------.
     */

    /**
     * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
     * by Sina Salek.
     *
     * Bugfix by Ralph Voigt (bug which causes it
     * to work only for $src_x = $src_y = 0.
     * Also, inverting opacity is not necessary.)
     * 08-JAN-2011
     *
     **/
    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $dst_im_ext)
    {
        if ($dst_im_ext == 'jpg' && $pct == 100) {
            imagealphablending($dst_im, true);
            imagealphablending($src_im, true);
            imagecopy($dst_im, $src_im, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
        } else {
            $transparent_index = imagecolortransparent($dst_im);
            $colors_total = imagecolorstotal($dst_im);
            $cut = imagecreatetruecolor($src_w, $src_h);

            if ($transparent_index >= 0) {
                $transparent_color = imagecolorsforindex($dst_im, $transparent_index);
                $transparent_index = imagecolorallocatealpha($cut, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue'], 127);
                imagefill($cut, 0, 0, $transparent_index);
                imagecolortransparent($cut, $transparent_index);
            } else {
                $color = imagecolorallocatealpha($cut, 0, 0, 0, 127);
                imagefill($cut, 0, 0, $color);
            }

            if ($dst_im_ext == 'png') {
                imagealphablending($dst_im, false);
                $imagesavealpha = true;
            } else {
                if ($dst_im_ext == 'webp') {
                    imagepalettetotruecolor($dst_im);
                    $imagesavealpha = true;
                } else {
                    if ($dst_im_ext !== 'jpg') {
                        imagetruecolortopalette($dst_im, true, 255);
                        $imagesavealpha = false;
                    }
                }
            }

            imagesavealpha($dst_im, $imagesavealpha);

            if (in_array($dst_im_ext, ['png', 'webp']) && $colors_total == 0) {
                if ($pct < 100) {
                    imagefilteropacity($src_im, $pct);
                }
                imagealphablending($dst_im, true);
                imagecopy($dst_im, $src_im, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
                imagealphablending($dst_im, false);
            } else {
                imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
                imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
                imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
            }

            imagedestroy($cut);
        }

        imagedestroy($src_im);
    }

    // Taken from http://www.php.net/manual/en/function.imagefilter.php#82162
    function imagefilteropacity(&$img, $opacity)
    {
        if (!isset($opacity)) {
            return false;
        }
        $opacity /= 100;

        $w = imagesx($img);
        $h = imagesy($img);

        imagealphablending($img, false);

        //find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for ($x = 0; $x < $w; ++$x) {
            for ($y = 0; $y < $h; ++$y) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }

        //loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; ++$x) {
            for ($y = 0; $y < $h; ++$y) {
                //get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                //calculate new alpha
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $opacity;
                }
                //get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                //set pixel with the new color + opacity
                if (!imagesetpixel($img, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }

        return true;
    }

    function image_allocate_transparency($image, $extension)
    {
        if ($extension == 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        } else {
            imagetruecolortopalette($image, true, 255);
            imagesavealpha($image, false);
        }
    }

    function image_copy_transparency($image_source, $image_target)
    {
        $transparent_index = imagecolortransparent($image_source);
        $palletsize = imagecolorstotal($image_source);
        if ($transparent_index >= 0 and $transparent_index < $palletsize) {
            $transparent_color = imagecolorsforindex($image_source, $transparent_index);
            $transparent_index = imagecolorallocatealpha($image_target, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue'], 127);
            imagefill($image_target, 0, 0, $transparent_index);
            imagecolortransparent($image_target, $transparent_index);
        } else {
            $color = imagecolorallocatealpha($image_target, 0, 0, 0, 127);
            imagefill($image_target, 0, 0, $color);
        }
    }

    function get_mask_bit_shift($bits, $mask)
    {
        if ($bits == 16) {
            // 555
            if ($mask == 0x7c00) {
                return 7;
            }
            if ($mask == 0x03e0) {
                return 2;
            }
            // 656
            if ($mask == 0xf800) {
                return 8;
            }
            if ($mask == 0x07e0) {
                return 3;
            }
        } else {
            if ($mask == 0xff000000) {
                return 24;
            }
            if ($mask == 0x00ff0000) {
                return 16;
            }
            if ($mask == 0x0000ff00) {
                return 8;
            }
        }

        return 0;
    }

    // http://www.programmierer-forum.de/function-imagecreatefrombmp-welche-variante-laeuft-t143137.htm
    // Extra bits by https://github.com/djeman
    // https://github.com/rodber/chevereto-free/pull/35
    function imagecreatefrombmp($file)
    {
        // if (function_exists('imagecreatefrombmp')) {
        //     return imagecreatefrombmp($file);
        // }
        // version 1.00
        if (!($fh = fopen($file, 'rb'))) {
            trigger_error('imagecreatefrombmp: Can not open ' . $file, E_USER_WARNING);

            return false;
        }
        // read file header
        $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
        // check for bitmap
        if ($meta['type'] != 19778) {
            trigger_error('imagecreatefrombmp: ' . $file . ' is not a bitmap!', E_USER_WARNING);

            return false;
        }
        // read image header
        $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
        $bytes_read = 40;
        // read additional bitfields
        if ($meta['headersize'] > $bytes_read) {
            // RGB bit field masks
            $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
            $bytes_read += 12;
        } else {
            if ($meta['bits'] == 16) {
                $meta['rMask'] = 0x7c00;
                $meta['gMask'] = 0x03e0;
                $meta['bMask'] = 0x001f;
            } elseif ($meta['bits'] > 16) {
                $meta['rMask'] = 0x00ff0000;
                $meta['gMask'] = 0x0000ff00;
                $meta['bMask'] = 0x000000ff;
            }
        }
        // set bytes and padding
        $meta['bytes'] = $meta['bits'] / 8;
        $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4) - floor($meta['width'] * $meta['bytes'] / 4)));
        if ($meta['decal'] == 4) {
            $meta['decal'] = 0;
        }
        // obtain imagesize
        if ($meta['imagesize'] < 1) {
            $meta['imagesize'] = $meta['filesize'] - $meta['offset'];
            // in rare cases filesize is equal to offset so we need to read physical size
            if ($meta['imagesize'] < 1) {
                $meta['imagesize'] = @filesize($file) - $meta['offset'];
                if ($meta['imagesize'] < 1) {
                    trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $file . '!', E_USER_WARNING);

                    return false;
                }
            }
        }
        // calculate colors
        $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
        // read color palette
        $palette = array();
        if ($meta['bits'] < 16) {
            $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
            // in rare cases the color value is signed
            if ($palette[1] < 0) {
                foreach ($palette as $i => $color) {
                    $palette[$i] = $color + 16777216;
                }
            }
        }
        // ignore extra bitmap headers
        if ($meta['headersize'] > $bytes_read) {
            fread($fh, $meta['headersize'] - $bytes_read);
        }
        // create gd image
        $im = imagecreatetruecolor($meta['width'], $meta['height']);
        $data = fread($fh, $meta['imagesize']);
        $p = 0;
        $vide = chr(0);
        $y = $meta['height'] - 1;
        $error = 'imagecreatefrombmp: ' . $file . ' has not enough data!';
        // loop through the image data beginning with the lower left corner
        while ($y >= 0) {
            $x = 0;
            while ($x < $meta['width']) {
                switch ($meta['bits']) {
                    case 32:
                        if (!($part = substr($data, $p, 4))) {
                            trigger_error($error, E_USER_WARNING);

                            return $im;
                        }
                        $color = unpack('V', $part);
                        $color[1] = (($color[1] & $meta['rMask']) >> get_mask_bit_shift(32, $meta['rMask'])) * 65536 + (($color[1] & $meta['gMask']) >> get_mask_bit_shift(32, $meta['gMask'])) * 256 + (($color[1] & $meta['bMask']) >> get_mask_bit_shift(32, $meta['bMask']));
                        break;
                    case 24:
                        if (!($part = substr($data, $p, 3))) {
                            trigger_error($error, E_USER_WARNING);

                            return $im;
                        }
                        $color = unpack('V', $part . $vide);
                        $color[1] = (($color[1] & $meta['rMask']) >> get_mask_bit_shift(24, $meta['rMask'])) * 65536 + (($color[1] & $meta['gMask']) >> get_mask_bit_shift(24, $meta['gMask'])) * 256 + (($color[1] & $meta['bMask']) >> get_mask_bit_shift(24, $meta['bMask']));
                        break;
                    case 16:
                        if (!($part = substr($data, $p, 2))) {
                            trigger_error($error, E_USER_WARNING);

                            return $im;
                        }
                        $color = unpack('v', $part);
                        $color[1] = (($color[1] & $meta['rMask']) >> get_mask_bit_shift(16, $meta['rMask'])) * 65536 + (($color[1] & $meta['gMask']) >> get_mask_bit_shift(16, $meta['gMask'])) * 256 + (($color[1] & $meta['bMask']) << 3);
                        break;
                    case 8:
                        $color = unpack('n', $vide . substr($data, $p, 1));
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 4:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 1:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        switch (($p * 8) % 8) {
                            case 0:
                                $color[1] = $color[1] >> 7;
                                break;
                            case 1:
                                $color[1] = ($color[1] & 0x40) >> 6;
                                break;
                            case 2:
                                $color[1] = ($color[1] & 0x20) >> 5;
                                break;
                            case 3:
                                $color[1] = ($color[1] & 0x10) >> 4;
                                break;
                            case 4:
                                $color[1] = ($color[1] & 0x8) >> 3;
                                break;
                            case 5:
                                $color[1] = ($color[1] & 0x4) >> 2;
                                break;
                            case 6:
                                $color[1] = ($color[1] & 0x2) >> 1;
                                break;
                            case 7:
                                $color[1] = ($color[1] & 0x1);
                                break;
                        }
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    default:
                        trigger_error('imagecreatefrombmp: ' . $file . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);

                        return false;
                }
                imagesetpixel($im, $x, $y, $color[1]);
                ++$x;
                $p += $meta['bytes'];
            }
            --$y;
            $p += $meta['decal'];
        }
        fclose($fh);

        return $im;
    }

    /**
     * JSON
     * ---------------------------------------------------------------------.
     */

    // Prepare json to only serve XMLHttpRequest
    function json_prepare()
    {
        if (is_development_env()) {
            return;
        }
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            Render\json_output(['status_code' => 400]);
        }
    }

    function json_error($args)
    {
        if (func_num_args() == 1 and is_object($args)) {
            if (method_exists($args, 'getMessage') and method_exists($args, 'getCode')) {
                $message = $args->getMessage();
                $code = $args->getCode();
                $context = get_class($args);
                error_log($message); // log class errors
            } else {
                return;
            }
        } else {
            if (func_num_args() == 1) {
                $message = $args;
                $code = null;
                $context = null;
            } else {
                $message = func_get_arg(0);
                $code = func_get_arg(1);
                $context = null;
            }
        }

        return array(
            'status_code' => 400,
            'error' => array(
                'message' => $message,
                'code' => $code,
                'context' => $context,
            ),
        );
    }

    /**
     * HTTP
     * ---------------------------------------------------------------------.
     */

    /**
     * Redirects to another URL.
     */
    function redirect($to = '', $status = 301)
    {
        if (!filter_var($to, FILTER_VALIDATE_URL)) {
            $to = get_base_url($to);
        }
        $to = preg_replace('|[^a-z0-9-~+_.?#=&;,/:%!]|i', '', $to);
        if (php_sapi_name() != 'cgi-fcgi') {
            set_status_header($status);
        }
        header("Location: $to");
        die();
    }

    /**
     * Set HTTP status header from status code.
     *
     * @Inspired from WordPress
     */
    function set_status_header($code)
    {
        $desc = get_set_status_header_desc($code);
        if (empty($desc)) {
            return false;
        }
        $protocol = $_SERVER['SERVER_PROTOCOL'];
        if ('HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol) {
            $protocol = 'HTTP/1.0';
        }
        $set_status_header = "$protocol $code $desc";

        return @header($set_status_header, true, $code);
    }

    /**
     * Gets header description according to its code.
     *
     * @Inspired from WordPress
     */
    function get_set_status_header_desc($code)
    {
        $codes_to_desc = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Reserved',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            510 => 'Not Extended',
        );
        if (check_value($codes_to_desc[$code])) {
            return $codes_to_desc[$code];
        }
    }

    // @Inspired from WordPress
    function clean_header_comment($string)
    {
        return trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $string));
    }

    /**
     * function xml2array.
     *
     * This function is part of the PHP manual.
     *
     * The PHP manual text and comments are covered by the Creative Commons
     * Attribution 3.0 License, copyright (c) the PHP Documentation Group
     *
     * @author  k dot antczak at livedata dot pl
     * @date    2011-04-22 06:08 UTC
     *
     * @see    http://www.php.net/manual/en/ref.simplexml.php#103617
     *
     * @license http://www.php.net/license/index.php#doc-lic
     * @license http://creativecommons.org/licenses/by/3.0/
     * @license CC-BY-3.0 <http://spdx.org/licenses/CC-BY-3.0>
     */
    function xml2array($xmlObject, $out = array())
    {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = (is_object($node)) ? xml2array($node) : $node;
        }

        return $out;
    }

    /**
     * @param string $domain Pass $_SERVER['SERVER_NAME'] here
     * @param bool   $debug
     *
     * @debug bool $debug
     *
     * @return string
     *
     * @see https://gist.github.com/pocesar/5366899
     */
    function get_domain($domain, $debug = false)
    {
        $original = $domain = strtolower($domain);
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return $domain;
        }
        $debug ? print('<strong style="color:green">&raquo;</strong> Parsing: ' . $original) : false;
        $arr = array_slice(array_filter(explode('.', $domain, 4), function ($value) {
            return $value !== 'www';
        }), 0); //rebuild array indexes
        if (count($arr) > 2) {
            $count = count($arr);
            $_sub = explode('.', $count === 4 ? $arr[3] : $arr[2]);
            $debug ? print(" (parts count: {$count})") : false;
            if (count($_sub) === 2) { // two level TLD
                $removed = array_shift($arr);
                if ($count === 4) { // got a subdomain acting as a domain
                    $removed = array_shift($arr);
                }
                $debug ? print("<br>\n" . '[*] Two level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
            } elseif (count($_sub) === 1) { // one level TLD
                $removed = array_shift($arr); //remove the subdomain
                if (strlen($_sub[0]) === 2 && $count === 3) { // TLD domain must be 2 letters
                    array_unshift($arr, $removed);
                } else {
                    // non country TLD according to IANA
                    $tlds = array(
                        'aero',
                        'arpa',
                        'asia',
                        'biz',
                        'cat',
                        'com',
                        'coop',
                        'edu',
                        'gov',
                        'info',
                        'jobs',
                        'mil',
                        'mobi',
                        'museum',
                        'name',
                        'net',
                        'org',
                        'post',
                        'pro',
                        'tel',
                        'travel',
                        'xxx',
                    );
                    if (count($arr) > 2 && in_array($_sub[0], $tlds) !== false) { //special TLD don't have a country
                        array_shift($arr);
                    }
                }
                $debug ? print("<br>\n" . '[*] One level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
            } else { // more than 3 levels, something is wrong
                for ($i = count($_sub); $i > 1; --$i) {
                    $removed = array_shift($arr);
                }
                $debug ? print("<br>\n" . '[*] Three level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
            }
        } elseif (count($arr) === 2) {
            $arr0 = array_shift($arr);
            if (
                strpos(join('.', $arr), '.') === false
                && in_array($arr[0], array('localhost', 'test', 'invalid')) === false
            ) { // not a reserved domain
                $debug ? print("<br>\n" . 'Seems invalid domain: <strong>' . join('.', $arr) . '</strong> re-adding: <strong>' . $arr0 . '</strong> ') : false;
                // seems invalid domain, restore it
                array_unshift($arr, $arr0);
            }
        }
        $debug ? print("<br>\n" . '<strong style="color:gray">&laquo;</strong> Done parsing: <span style="color:red">' . $original . '</span> as <span style="color:blue">' . join('.', $arr) . "</span><br>\n") : false;

        return join('.', $arr);
    }
    
    function getQsParams()
    {
        $a = array();
        foreach (explode("&", $_SERVER["QUERY_STRING"]) as $q) {
            $p = explode('=', $q, 2);
            $a[$p[0]] = isset($p[1]) ? $p[1] : '';
        }
        return $a;
    }
} // G Namespace

// Global namespace

namespace {
    function class_autoloader($class)
    {
        $array = ['class' => $class, 'exists' => class_exists($class)];
        $explode = explode('\\', $class);
        $last_key = key(array_slice($explode, -1, 1, true));
        $file = ($explode[0] == 'G' ? G_PATH_CLASSES : G_APP_PATH_CLASSES) . 'class.' . strtolower($explode[$last_key]) . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            //trigger_error("Can't autoload ".$class.' class. Class should be at '. $file, E_USER_ERROR);
            // Don't trigger an error so it allows multiple autoloaders to be called
        }
    }
    spl_autoload_register('class_autoloader');

    /*
     * A Compatibility library with PHP 5.5's simplified password hashing API.
     *
     * @author Anthony Ferrara <ircmaxell@php.net>
     * @license http://www.opensource.org/licenses/mit-license.html MIT License
     * @copyright 2012 The Authors
     */

    if (!defined('PASSWORD_DEFAULT')) {
        define('PASSWORD_BCRYPT', 1);
        define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

        /**
         * Hash the password using the specified algorithm.
         *
         * @param string $password The password to hash
         * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
         * @param array  $options  The options for the algorithm to use
         *
         * @return string|false the hashed password, or false on error
         */
        function password_hash($password, $algo, array $options = array())
        {
            if (!function_exists('crypt')) {
                trigger_error('Crypt must be loaded for password_hash to function', E_USER_WARNING);

                return null;
            }
            if (!is_string($password)) {
                trigger_error('password_hash(): Password must be a string', E_USER_WARNING);

                return null;
            }
            if (!is_int($algo)) {
                trigger_error('password_hash() expects parameter 2 to be long, ' . gettype($algo) . ' given', E_USER_WARNING);

                return null;
            }
            switch ($algo) {
                case PASSWORD_BCRYPT:
                    // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                    $cost = 10;
                    if (isset($options['cost'])) {
                        $cost = $options['cost'];
                        if ($cost < 4 || $cost > 31) {
                            trigger_error(sprintf('password_hash(): Invalid bcrypt cost parameter specified: %d', $cost), E_USER_WARNING);

                            return null;
                        }
                    }
                    // The length of salt to generate
                    $raw_salt_len = 16;
                    // The length required in the final serialization
                    $required_salt_len = 22;
                    $hash_format = sprintf('$2y$%02d$', $cost);
                    break;
                default:
                    trigger_error(sprintf('password_hash(): Unknown password hashing algorithm: %s', $algo), E_USER_WARNING);

                    return null;
            }
            if (isset($options['salt'])) {
                switch (gettype($options['salt'])) {
                    case 'NULL':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $salt = (string) $options['salt'];
                        break;
                    case 'object':
                        if (method_exists($options['salt'], '__tostring')) {
                            $salt = (string) $options['salt'];
                            break;
                        }
                        // no break
                    case 'array':
                    case 'resource':
                    default:
                        trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);

                        return null;
                }
                if (strlen($salt) < $required_salt_len) {
                    trigger_error(sprintf('password_hash(): Provided salt is too short: %d expecting %d', strlen($salt), $required_salt_len), E_USER_WARNING);

                    return null;
                } elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                    $salt = str_replace('+', '.', base64_encode($salt));
                }
            } else {
                $buffer = '';
                $buffer_valid = false;
                if (function_exists('mcrypt_create_iv') && !defined('PHALANGER')) {
                    $buffer = mcrypt_create_iv($raw_salt_len, MCRYPT_DEV_URANDOM);
                    if ($buffer) {
                        $buffer_valid = true;
                    }
                }
                if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes')) {
                    $buffer = openssl_random_pseudo_bytes($raw_salt_len);
                    if ($buffer) {
                        $buffer_valid = true;
                    }
                }
                if (!$buffer_valid && is_readable('/dev/urandom')) {
                    $f = fopen('/dev/urandom', 'r');
                    $read = strlen($buffer);
                    while ($read < $raw_salt_len) {
                        $buffer .= fread($f, $raw_salt_len - $read);
                        $read = strlen($buffer);
                    }
                    fclose($f);
                    if ($read >= $raw_salt_len) {
                        $buffer_valid = true;
                    }
                }
                if (!$buffer_valid || strlen($buffer) < $raw_salt_len) {
                    $bl = strlen($buffer);
                    for ($i = 0; $i < $raw_salt_len; ++$i) {
                        if ($i < $bl) {
                            $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
                        } else {
                            $buffer .= chr(mt_rand(0, 255));
                        }
                    }
                }
                $salt = str_replace('+', '.', base64_encode($buffer));
            }

            $salt = substr($salt, 0, $required_salt_len);

            $hash = $hash_format . $salt;
            $ret = crypt($password, $hash);

            if (!is_string($ret) || strlen($ret) <= 13) {
                return false;
            }

            return $ret;
        }

        /**
         * Get information about the password hash. Returns an array of the information
         * that was used to generate the password hash.
         *
         * array(
         *    'algo' => 1,
         *    'algoName' => 'bcrypt',
         *    'options' => array(
         *        'cost' => 10,
         *    ),
         * )
         *
         * @param string $hash The password hash to extract info from
         *
         * @return array the array of information about the hash
         */
        function password_get_info($hash)
        {
            $return = array(
                'algo' => 0,
                'algoName' => 'unknown',
                'options' => array(),
            );
            if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60) {
                $return['algo'] = PASSWORD_BCRYPT;
                $return['algoName'] = 'bcrypt';
                list($cost) = sscanf($hash, '$2y$%d$');
                $return['options']['cost'] = $cost;
            }

            return $return;
        }

        /**
         * Determine if the password hash needs to be rehashed according to the options provided.
         *
         * If the answer is true, after validating the password using password_verify, rehash it.
         *
         * @param string $hash    The hash to test
         * @param int    $algo    The algorithm used for new password hashes
         * @param array  $options The options array passed to password_hash
         *
         * @return bool true if the password needs to be rehashed
         */
        function password_needs_rehash($hash, $algo, array $options = array())
        {
            $info = password_get_info($hash);
            if ($info['algo'] != $algo) {
                return true;
            }
            switch ($algo) {
                case PASSWORD_BCRYPT:
                    $cost = isset($options['cost']) ? $options['cost'] : 10;
                    if ($cost != $info['options']['cost']) {
                        return true;
                    }
                    break;
            }

            return false;
        }

        /**
         * Verify a password against a hash using a timing attack resistant approach.
         *
         * @param string $password The password to verify
         * @param string $hash     The hash to verify against
         *
         * @return bool If the password matches the hash
         */
        function password_verify($password, $hash)
        {
            if (!function_exists('crypt')) {
                trigger_error('Crypt must be loaded for password_verify to function', E_USER_WARNING);

                return false;
            }
            $ret = crypt($password, $hash);
            if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
                return false;
            }

            $status = 0;
            for ($i = 0; $i < strlen($ret); ++$i) {
                $status |= (ord($ret[$i]) ^ ord($hash[$i]));
            }

            return $status === 0;
        }
    }
}
