<?php

namespace App\Web\Task;
use SilverStripe\Dev\BuildTask;
use Leochenftw\Debugger;
use Leochenftw\SocketEmitter;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Emitter extends BuildTask
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
    protected $title = 'Emit test';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Test socket.io emitter';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        if ($args = $request->getVar('args')) {
            SocketEmitter::emit($args[0]);
            print 'Emitted';
            print PHP_EOL;
            return true;
        }

        print 'Missing variable';
        print PHP_EOL;
        return false;
    }
}
