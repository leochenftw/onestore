<?php

namespace Web\App\ModelAdmin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\Discount;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class DiscountAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Discount::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'discounts';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Discounts';


}
