<?php

namespace Web\App\ModelAdmin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\Manufacturer;
use App\Web\Model\Supplier;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ManufacturerSupplierAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Manufacturer::class,
        Supplier::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'manufacturer-supplier';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Manufacturer & Suppliers';


}
