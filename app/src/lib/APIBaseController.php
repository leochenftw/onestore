<?php
namespace Leochenftw\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use Leochenftw\Debugger;

/**
 *
 */
class APIBaseController extends Controller
{
    protected static $allowed_request_methods = [];

    public function isAuthenticated()
    {
        return true;
    }

    public function index()
    {
        $request        =   $this->request;
        $header         =   $this->getResponse();
        $method         =   $this->request->httpMethod();

        $this->can_proceed();

        if (!Director::isLive()) {
            $header->addHeader('Access-Control-Allow-Origin', '*');
            $header->addHeader('Access-Control-Allow-Methods', 'GET, PUT, POST, DELETE, OPTIONS');
            $header->addHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            // $header->addHeader('Access-Control-Allow-Credentials', true);
        }

        if ($request->isAjax()) {
            return json_encode($this->$method($this->request));
        }

        return $this->httpError(400, 'ajax request only');
    }

    private function can_proceed()
    {
        $method         =   $this->request->httpMethod();
        if (isset(static::$allowed_request_methods[$method])) {
            $allowed    =   static::$allowed_request_methods[$method];

            if (is_bool($allowed)) {
                if ($allowed) {
                    return true;
                }

                return $this->httpError(400, 'method does not allowed');
            }

            $allowed    =   str_replace('->', '', $allowed);

            if (method_exists($this, $allowed)) {

                if ($this->$allowed()) {
                    return true;
                }

                return $this->httpError(400, 'method does not allowed');
            }

            return $this->httpError(400, 'method does not exist');
        }

        return $this->httpError(400, 'method does not allowed');
    }
}
