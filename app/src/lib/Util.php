<?php

namespace Leochenftw;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Parsers\ShortcodeParser;
use Page;

class Util
{
    public static function jsonToCsv($json, $csvFilePath = false, $boolOutputFile = false)
    {
        // See if the string contains something
        if (empty($json)) {
          die("The JSON string is empty!");
        }

        // If passed a string, turn it into an array
        if (is_array($json) === false) {
          $json = json_decode($json, true);
        }

        // If a path is included, open that file for handling. Otherwise, use a temp file (for echoing CSV string)
        if ($csvFilePath !== false) {
          $f = fopen($csvFilePath,'w+');
          if ($f === false) {
            die("Couldn't create the file to store the CSV, or the path is invalid. Make sure you're including the full path, INCLUDING the name of the output file (e.g. '../save/path/export.csv')");
          }
        }
        else {
          $boolEchoCsv = true;
          if ($boolOutputFile === true) {
            $boolEchoCsv = false;
          }
          $strTempFile = 'export-' . date("U") . ".csv";
          $f = fopen($strTempFile,"w+");
        }

        $firstLineKeys = false;
        foreach ($json as $line) {
          if (empty($firstLineKeys)) {
            $firstLineKeys = array_keys($line);
            fputcsv($f, $firstLineKeys);
            $firstLineKeys = array_flip($firstLineKeys);
          }

          // Using array_merge is important to maintain the order of keys acording to the first element
          fputcsv($f, array_merge($firstLineKeys, $line));
        }
        fclose($f);

        // Take the file and put it to a string/file for output (if no save path was included in function arguments)
        if ($boolOutputFile === true) {
          if ($csvFilePath !== false) {
            $file = $csvFilePath;
          }
          else {
            $file = $strTempFile;
          }

          // Output the file to the browser (for open/save)
          if (file_exists($file)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Length: ' . filesize($file));
            readfile($file);
          }
        }
        elseif ($boolEchoCsv === true) {
          if (($handle = fopen($strTempFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
              echo implode(",",$data);
              echo "<br />";
            }
            fclose($handle);
          }
        }

        // Delete the temp file
        unlink($strTempFile);
    }

    public static function check_csrf(&$request)
    {
        if (($csrf = static::null_it($request->postVar('csrf'))) || ($csrf = static::null_it($request->getVar('csrf')))) {
            return SecurityToken::inst()->getSecurityID() == $csrf;
        }

        return false;
    }

    public static function null_it($value)
    {
        if ($value == 'null') {
            return null;
        }

        if ($value == 'true') {
            return true;
        }

        if ($value == 'false') {
            return false;
        }

        return $value;
    }

    public static function parse_file_array($input)
    {
        $array      =   [];

        foreach ($input as $key => $value) {
            for ($i = 0; $i < count($value); $i++) {
                if (empty($array[$i]))  {
                    $array[]        =   [];
                }
                $array[$i][$key]    =   $value[$i];
            }
        }

        return $array;
    }

    public static function to_utf($kanji_chars)
    {
        //split word
        preg_match_all('/./u', $kanji_chars, $matches);

        $c = "";
        foreach($matches[0] as $m){
                $c .= "&#".base_convert(bin2hex(iconv('UTF-8',"UCS-4",$m)),16,10);
        }
        return $c;
    }

    public static function EmailGravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() )
    {
        if (self::valid_email($email)) {
            $url = 'https://www.gravatar.com/avatar/';
            $url .= md5( strtolower( trim( $email ) ) );
            $url .= "?s=$s&d=$d&r=$r";
            if ( $img ) {
                $url = '<img src="' . $url . '"';
                foreach ( $atts as $key => $val )
                    $url .= ' ' . $key . '="' . $val . '"';
                $url .= ' />';
            }
            return $url;
        }

        return null;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param boole $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source https://gravatar.com/site/implement/images/php/
     */
    public static function Gravatar( $member_id, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() )
    {
        if ($member = Member::get()->byID($member_id)) {
            $email = $member->Email;
            $url = 'https://www.gravatar.com/avatar/';
            $url .= md5( strtolower( trim( $email ) ) );
            $url .= "?s=$s&d=$d&r=$r";
            if ( $img ) {
                $url = '<img src="' . $url . '"';
                foreach ( $atts as $key => $val )
                    $url .= ' ' . $key . '="' . $val . '"';
                $url .= ' />';
            }
            return $url;
        }

        return null;
    }

    public static function stripTags($strip_list, $html)
    {
        foreach ($strip_list as $tag)
        {
            $html = preg_replace('/<\/?' . $tag . '(.|\s)*?>/', '', $html);
        }
        return $html;
    }

    public static function endsWith($haystack, $needle)
    {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public static function startsWith($haystack, $needle)
    {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function sanitise($string, $space_replacement = '-', $replacement = '')
    {
        $words = explode(' ', trim(strtolower($string)));
        $new_words = array();
        foreach($words as $word) {
            $word = preg_replace('/[^A-Za-z0-9]/', $replacement, trim($word));
            if (strlen($word) > 0) {
                $new_words[] = $word;
            }
        }

        return implode($space_replacement, $new_words);
    }

    public static function LinkThis($all_vars, $var_name, $var_value = null)
    {
        $attach = true;
        if (empty($var_value)) {
            unset($all_vars[$var_name]);
        } elseif (!empty($all_vars[$var_name]) && !empty($var_value)) {
            $all_vars[$var_name] = $var_value;
            $attach = false;
        }

        $link = $all_vars['url'] . '?';
        unset($all_vars['url']);
        foreach ($all_vars as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $value_item) {
                    $link .= $key . '[]=' . $value_item . '&';
                }
            } else {
                $link .= ($key . '=' . $value . '&');
            }
        }

        if (!empty($var_value) && $attach) {
            $link .= $var_name . '=' .$var_value;
        }

        $link = rtrim(rtrim($link, '&'), '?');
        return $link;
    }

    public static function stringify($query)
    {
        unset($query['url']);
        unset($query['SecurityID']);

        if (empty($query['start'])) {
            $query['start'] = '0';
        }

        ksort($query);
        $str = '';
        foreach ($query as $key => $value) {
            $key = self::sanitise($key);
            if (is_array($value)) {
                sort($value);
                foreach ($value as $value_item) {
                    $str .= $key . '_' . self::sanitise($value_item) . '__';
                }
            } else {
                $str .= ($key . '_' . self::sanitise($value)) . '__';
            }
        }
        return rtrim($str, '_');
    }

    public static function paramStringify($params, $prefix = '')
    {
        $str = '';
        if (count($params) > 0) {
            foreach ($params as $name => $value) {
                $value = str_replace(' ', '+', $value);
                $str .= $name . '=' . $value . '&';
            }

            $str = $prefix . rtrim($str, '&');
        }
        return $str;
    }

    public static function match_string($pattern, $str)
    {
        return fnmatch($pattern, $str);
    }

    public static function truncate_html($s, $l, $e = '&hellip;', $isHTML = true)
    {
        $s = trim($s);
        $e = (strlen(strip_tags($s)) > $l) ? $e : '';
        $i = 0;
        $tags = array();

        if($isHTML) {
            preg_match_all('/<[^>]+>([^<]*)/', $s, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            foreach($m as $o) {
                if($o[0][1] - $i >= $l) {
                    break;
                }
                $t = substr(strtok($o[0][0], " \t\n\r\0\x0B>"), 1);
                if($t[0] != '/') {
                    $tags[] = $t;
                }
                elseif(end($tags) == substr($t, 1)) {
                    array_pop($tags);
                }
                $i += $o[1][1] - $o[0][1];
            }
        }
        $output = substr($s, 0, $l = min(strlen($s), $l + $i)) . (count($tags = array_reverse($tags)) ? ($e . '</') . implode('></', $tags) . '>' : '');
        return $output;
    }

    /**
     * find the key that matches a specific pattern.
     * Used primarily with dbo field tags.
     *
     * e.g. UtilityFunctions::getValidKey('/*Description/', $this->db);
     * */
    public static function getValidKey($pattern, $arr)
    {
        $keys = array();
        foreach($arr as $key => $value) {
            if (preg_match($pattern, $key)){
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Get $count words from a piece of text.
     * */
    public static function getWords($sentence, $count = 10)
    {
        $sentence = str_replace("\r", '', str_replace("\n", '', trim(strip_tags($sentence))));
        $words = explode(' ', $sentence);

        if (count($words) <= $count) {
            return $sentence;
        }

        $trimmed = '';
        for ($i = 0; $i < $count; $i++) {
            $trimmed .= $words[$i] . ' ';
        }

        $trimmed = rtrim(rtrim(trim($trimmed), ','), '.');

        return $trimmed . '...';
    }

    /**
     * Get max number of words within a character limit.
     * */
    public static function getWordsWithinCharLimit($sentence, $limit = 150)
    {
        $str = '';
        $i = 1;

        if(strlen($sentence) < $limit) {
            return $sentence;
        }

        while (strlen($current = self::getWords($sentence, $i++)) < $limit) {
            $str = $current;
        }

        return $str;
    }

    public static function parse_raw_http_request($input)
    {
        $a_data = array();
        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $id => $block)
        {
            if (empty($block))
              continue;

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE)
            {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            }
            // parse all other fields
            else
            {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }

            $a_data[$matches[1]] = $matches[2];
        }

        return $a_data;
    }

    public static function shorten_number($n, $from = 9999)
    {
        if ($n > $from && $n <= 999999) {
            $n = (round($n / 100) * 0.1) . 'K';
        } elseif ($n > 999999) {
            $n = (round($n / 100000) * 0.1) . 'M';
        } else {
            $n = number_format($n);
        }

        return $n;
    }

    public static function preprocess_content($content)
    {
        $content    =   ShortcodeParser::get_active()->parse($content);
        $ref        =   !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['HTTP_HOST'];
        if (strpos($ref, 'localhost') !== false) {
            $content   =   str_replace('<img src="', '<img src="' . rtrim(Director::absoluteBaseURL(), '/'), $content);
        }
        return $content;
    }

    public static function get_initials($str, $ignore = ['with', 'to', 'from', 'of', 'via', 'by'])
    {
        if (strlen(trim($str)) == 0) return 'O';

        $words      =   explode(" ", $str);
        $acronym    =   "";

        foreach ($words as $w) {
            if (!in_array(strtolower($w), $ignore)) {
                $acronym    .=  $w[0];
            }
        }

        return $acronym;
    }
}
