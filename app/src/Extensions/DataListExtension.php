<?php

namespace App\Web\Extension;

use SilverStripe\ORM\DataExtension;

class DataListExtension extends DataExtension
{
    public function getMiniData($param = null)
    {
        $result         =   [];
        foreach ($this->owner as $item) {
            $result[]   =   !is_null($param) ? $item->getMiniData($param) : $item->getMiniData();
        }

        return $result;
    }

    public function getData($param = null)
    {
        $result         =   [];
        foreach ($this->owner as $item) {
            if (!is_null($param)) {
                if (!is_array($param)) {
                    $result[]   =   $item->getData($param);
                } else {
                    $result[]   =   call_user_func_array([$item, 'getData'], $param);
                }
            } else {
                $result[]   =   $item->getData();
            }
        }

        return $result;
    }

    public function getListData()
    {
        $result =   [];
        foreach ($this->owner as $item) {
            $result[]   =   $item->getListData();
        }

        return $result;
    }
}
