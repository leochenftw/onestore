<?php

namespace App\Web\Task;
use SilverStripe\Dev\BuildTask;
use Leochenftw\Debugger;
use App\Web\Model\EndDaySummary;
use Leochenftw\eCommerce\eCollector\Model\Order;
use GuzzleHttp\Client;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class SummariseDaily extends BuildTask
{
    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = true;

    /**
     * @var string $title Shown in the overview on the TaskRunner
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'End Day Summaries creator';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Created/Update End Day Summaries';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        if (!empty($request->getVar('args'))) {
            $args   =   $request->getVar('args');
            if ($args[0] == 'remote') {

                $list   =   EndDaySummary::get()->filter(['Date:LessThan' => '2020-01-01']);
                foreach ($list as $item) {
                    print 'Deleting ' . $item->Date . '...' . PHP_EOL;
                    $item->delete();
                }

                return $this->import_remote();
            } elseif ($args[0] == 'purge') {
                $list   =   EndDaySummary::get();
                foreach ($list as $item) {
                    $item->delete();
                }
            }
        }

        if ( Order::get()->count() > 0) {
            $first_day  =   Order::get()->sort(['Created' => 'ASC'])->limit(1)->first()->Created;
            $last_day   =   Order::get()->sort(['Created' => 'DESC'])->limit(1)->first()->Created;

            $first_date =   date('Y-m-d', strtotime($first_day));
            $last_date  =   date('Y-m-d', strtotime($last_day));

            $dates      =   [];
            $dates[]    =   $first_date;

            $next_date  =   strtotime($first_date . "+1 days");
            while ($next_date < strtotime($last_date)) {
                $next_date  =   date('Y-m-d', $next_date);
                $dates[]    =   $next_date;
                $next_date  =   strtotime($next_date . "+1 days");
            }

            $dates[]    =   $last_date;

            foreach ($dates as $date) {
                print 'Generating: ' . $date . PHP_EOL;
                $this->generate_summary($date);
            }

            return true;
        }

        print 'Nothing to generate';
        print PHP_EOL;
        return false;
    }

    private function generate_summary($date)
    {
        if ($summary = EndDaySummary::get()->filter(['Date' => $date])->first()) {
            return $summary->self_update();
        }

        return EndDaySummary::generate_by_date($date);
    }

    private function fetch_products($page = 0)
    {
        $client = new Client([
            'base_uri' => 'https://www.nzyogo.co.nz/api/v/1/'
        ]);

        $response = $client->request(
            'GET',
            'summary',
            [
                'query' =>  [
                    'page'  =>  $page
                ]
            ]
        );

        $data   =   json_decode($response->getBody());

        return $data;
    }

    private function import_remote()
    {
        $n      =   0;
        $list   =   $this->fetch_products($n);

        $this->do_list($list);

        while (!empty($list)) {
            $n++;
            $list   =   $this->fetch_products($n);
            $this->do_list($list);
        }

        print PHP_EOL;
        print 'Legacy Imported';
        print PHP_EOL;

        return true;
    }

    private function do_list(&$list)
    {
        foreach ($list as $item) {
            EndDaySummary::cumulate($item->amount, $item->method, $item->date);
            print $item->date . ': $' . $item->amount . ', by ' . $item->method;
            print PHP_EOL;
        }
    }
}
