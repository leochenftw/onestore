<?php

namespace Web\App\ModelAdmin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Member\Operator;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class OperatorAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Operator::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'operators';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Operators';


}
