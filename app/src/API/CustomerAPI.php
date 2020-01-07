<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use Leochenftw\eCommerce\eCollector\Model\Customer;
use Leochenftw\Util;
use Leochenftw\SocketEmitter;

class CustomerAPI extends RestfulController
{
    private $page_size  =   50;
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  "->isAuthenticated",
        'post'  =>  '->isAuthenticated'
    ];

    public function get($request)
    {
        if ($id = $request->param('ID')) {
            // return $id;
            if ($id != 'All') {
                if ($customer = Customer::get()->byID($id)) {
                    return $customer->getData();
                }

                return $this->httpError(404, 'Not found');
            } elseif ($action = $request->param('Action')) {
                return $this->$action($request);
            }
        }

        $page       =   !empty($request->getVar('page')) ? $request->getVar('page') : 0;
        $sort       =   !empty($request->getVar('sort')) ? $request->getVar('sort') : 'ID';
        $by         =   !empty($request->getVar('by')) ? $request->getVar('by') : 'DESC';

        $customers  =   Customer::get()->filter(['Suspended' => false]);
        if (($type = $request->getVar('type')) && ($term = $request->getVar('term'))) {
            $filter =   [];
            if ($type == 'phone') {
                $filter['PhoneNumber:StartsWith']   =   $term;
                $customers  =   $customers->filter($filter);
            } elseif ($type == 'name') {
                $filter['FirstName:StartsWith'] =   $term;
                $filter['Surname:StartsWith']   =   $term;
                $customers  =   $customers->filterAny($filter);
            }
        }
        $count      =   $customers->count();
        $customers  =   $customers->sort([$sort => $by])->limit($this->page_size, $page * $this->page_size);

        return [
            'total_page'    =>  ceil($count / $this->page_size),
            'list'          =>  $customers->getListData()
        ];
    }

    public function post($request)
    {
        $action =   $request->param('Action');
        if (empty($action)) {
            $action =   'update';
        }

        return $this->$action($request);
    }

    private function create_customer(&$request)
    {
        $phone      =   $request->postVar('phone');
        $email      =   $request->postVar('email');
        $fn         =   $request->postVar('firstname');
        $wechat     =   Util::null_it($request->postVar('wechat'));

        if (empty($fn)) {
            return $this->httpError(400, 'Missing first name');
        }

        if (empty($phone) && empty($email)) {
            return $this->httpError(400, 'Missing phone/email');
        }

        if (empty($email)) {
            $email  =   $phone . '@' . $phone . '.com';
        }

        $customer   =   Customer::get()->filterAny(['PhoneNumber' => $phone])->first();

        if (empty($customer)) {
            $customer   =   Customer::create();
            $customer->FirstName    =   $fn;

            if ($sn = $request->postVar('surname')) {
                $customer->Surname  =   $sn;
            }

            $customer->Email        =   $email;
            $customer->PhoneNumber  =   $phone;
            $customer->Wechat       =   $wechat;

            $customer->write();

            SocketEmitter::emit('new_member');

            return [
                'message'   =>  'Customer created'
            ];
        }

        return $this->httpError(400, 'Account already exists');
    }

    private function delete(&$request)
    {
        if ($member = Customer::get()->byID($request->param('ID'))) {
            if ($member->Orders()->count() > 0) {
                $member->write();
            } else {
                $member->delete();
            }

            return [
                'message'   =>  'Customer deleted'
            ];
        }

        return $this->httpError(404, 'No such member');
    }

    private function update(&$request)
    {
        if ($member = Customer::get()->byID($request->param('ID'))) {
            $member->FirstName  =   $request->postVar('firstname');

            if (!empty($request->postVar('surname'))) {
                $member->Surname    =   $request->postVar('surname');
            }


            if (!empty($request->postVar('email'))) {
                $member->Email      =   $request->postVar('email');
            }

            $member->PhoneNumber    =   $request->postVar('phone');
            $member->Wechat         =   $request->postVar('wechat');
            $member->write();

            return $member->getData();
        }

        return $this->httpError(404, 'No such customer');
    }
}
