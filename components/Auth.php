<?php namespace KEERill\Users\Components;

use Event;
use Input;
use Flash;
use Request;
use Redirect;
use Validator;
use AuthManager;
use Cms\Classes\Page;
use ValidationException;
use ApplicationException;
use Cms\Classes\ComponentBase;
use KEERill\Users\Models\Settings as UserSettings;

class Auth extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'keerill.users::lang.auth.component_name',
            'description' => 'keerill.users::lang.auth.component_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title' => 'keerill.users::lang.auth.redirect',
                'description' => 'keerill.users::lang.auth.redirect_desc',
                'type' => 'dropdown',
                'default' => ''
            ]
        ];
    }

    public function getRedirectOptions() 
    {
        return ['' => ' - none - '] + Page::sortBy('baseFileName')->lists('title', 'baseFileName');
    }

    public function user()
    {
        if (!$user = AuthManager::getUser()) {
            return null;
        }

        return $user;
    }

     /**
     * Sign in the user
     */
    public function onSignin()
    {
        try {
            /*
             * Validate input
             */

            $data = post();

            $rules = [
                'name' => 'required|between: 4, 50',
                'password' => 'required|between: 2, 255'
            ];

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
            
            /*
             * Authenticate user
             */
             
            $credentials = [
                'name'    => array_get($data, 'name'),
                'password' => array_get($data, 'password')
            ];

            Event::fire('keerill.users.beforeAuthenticate', [$this, $credentials, $data]);

            $user = AuthManager::authenticate($credentials, array_get($data, 'remember_me'));

            Event::fire('keerill.users.authenticate', [$this, $user, $data]);

            if (!$redirect = array_get(Request::query(), 'referer')) {
                $redirect = $this->property('redirect');
            }

            $redirectUrl = $this->pageUrl($redirect)
                ?: $redirect;

            if ($redirectUrl = post('redirect', $redirectUrl)) {
                return Redirect::to($redirectUrl);
            }
        }
        catch (\Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
            return Redirect::back();
        }
    }

}
