<?php
/**
 * @file Debugger
 *
 * Debugging functions
 * */
namespace Leochenftw;

use SilverStripe\Dev\Debug;

class Debugger
{
    public static function inspect($obj, $die = true)
    {
        Debug::dump($obj);
        if ($die) {
            die;
        }
    }

    public static function methods(&$obj)
    {
        if (!empty($obj)) {
            Debug::dump(get_class_methods($obj));
        } else {
            echo 'object is empty';
        }
        die;
    }
}
