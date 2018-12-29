<?php
/**
 * @file Debugger
 *
 * Debugging functions
 * */
namespace Leochenftw;

class Debugger
{
    public static function inspect($obj, $die = true)
    {
        print '<pre>';
        print_r($obj);
        print '</pre>';
        if ($die) {
            die;
        }
    }

    public static function methods(&$obj)
    {
        if (!empty($obj)) {
            echo '<pre>';
            print_r(get_class_methods($obj));
            echo '</pre>';
        } else {
            echo 'object is empty';
        }
        die;
    }
}
