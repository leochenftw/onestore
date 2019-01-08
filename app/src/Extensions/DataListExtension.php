<?php

namespace App\Web\Extension;

use SilverStripe\ORM\DataExtension;

class DataListExtension extends DataExtension
{
    public function getData($param = null)
    {
        $result         =   [];
        foreach ($this->owner as $item) {
            $result[]   =   !is_null($param) ? $item->getData($param) : $item->getData();
        }

        return $result;
    }
}
