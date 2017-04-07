<?php namespace Keerill\Users\Components;

use Auth;
use Event;
use Input;
use Flash;
use Request;
use Redirect;
use Validator;
use ValidationException;
use ApplicationException;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Keerill\Users\Models\Settings as UserSettings;

class AuthComponent extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Аутентификация',
            'description' => 'Форма аутентификации'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    // public function init()
    // {
    //     $this->canRegister = UserSettings::get('allow_registration', true);
    // }

    public function user()
    {
        if(!Auth::check())
        {
            return null;
        }

        return Auth::getUser();
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
                'name' => 'required|between: 6, 50',
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
            
            Event::fire('october.users.beforeAuthenticate', [$this, $credentials]);
            $user = Auth::authenticate($credentials, true);

            return Redirect::back();
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

}
