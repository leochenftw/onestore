<?php

namespace Web\App\ModelAdmin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\EndDaySummary;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class EndDaySummaryAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        EndDaySummary::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'end-day-summaries';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'End Day Summaries';


}
