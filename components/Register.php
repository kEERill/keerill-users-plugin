<?php namespace KEERill\Users\Components;

use Mail;
use Lang;
use Flash;
use Event;
use Request;
use Redirect;
use Validator;
use AuthManager;
use Cms\Classes\Page;
use ValidationException;
use ApplicationException;
use Cms\Classes\ComponentBase;
use KEERill\Users\Models\Settings as UserSettings;

class Register extends ComponentBase
{
    public $canRegister;

    public $ip_address;

    public function componentDetails()
    {
        return [
            'name'        => 'keerill.users::lang.register.component_name',
            'description' => 'keerill.users::lang.register.component_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'paramCode' => [
                'title' => 'keerill.users::lang.register.code',
                'description' => 'keerill.users::lang.register.code_desc',
                'type' => 'string',
                'default' => ''
            ],
            'redirect' => [
                'title' => 'keerill.users::lang.register.redirect',
                'description' => 'keerill.users::lang.register.redirect_desc',
                'type' => 'dropdown',
                'default' => ''
            ]
        ];
    }

    public function getRedirectOptions()
    {
        return ['' => ' - none - '] + Page::sortBy('baseFileName')->lists('title', 'baseFileName');
    }

    public function init() 
    {
        $this->canRegister = $this->page['canRegister'] = UserSettings::get('allow_registration', false);
    }

    public function onRun()
    {
        /*
         * Activation code supplied
         */
        $routeParameter = $this->property('paramCode');
        if ($activationCode = $this->param($routeParameter)) {
            $this->page['activate'] = true;
            $this->onActivate($activationCode);
        }
    }

    /**
     * Returns the logged in user, if available
     */
    
    private function user()
    {
        if (!AuthManager::check()) {
            return null;
        }
        return AuthManager::getUser();
    }

     /**
     * Register the user
     */
    public function onRegister()
    {
        try {
            if (!$this->canRegister) {
                throw new ApplicationException(Lang::get('keerill.users::lang.register.register_disable'));
            }

            if ($this->user()) {
                throw new ApplicationException(Lang::get('keerill.users::lang.register.is_user'));
            }

            /*
             * Validate input
             */
            $data = post();

            if (!array_key_exists('password_confirmation', $data)) {
                $data['password_confirmation'] = post('password');
            }

            $rules = [
                'name'    => 'required|regex:/^[\w]{3,}$/i|between:4,50|unique:oc_users',
                'email' => 'required|email|between:6,255',
                'password' => 'required|between:8,255|confirmed',
                'password_confirmation' => 'required_with:password|between:8,255'
            ];

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            /*
             * Register user
             */
            Event::fire('keerill.users.beforeRegister', [$this, &$data]);
            
            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            $user = AuthManager::register($data, $automaticActivation);
            
            Event::fire('keerill.users.register', [$this, $user, $data]);

            /*
             * Activation is by the user, send the email
             */
            if ($userActivation) {
                $this->sendActivationEmail($user);
                Flash::success(Lang::get('keerill.users::lang.messages.user_send_mail'));
            }

            /*
             * Automatically activated or not required, log the user in
             */
            if ($automaticActivation || !$requireActivation) {
                AuthManager::login($user);
            }

            $redirectUrl = $this->pageUrl($this->property('redirect'));

            if ($redirectUrl = post('redirect', $redirectUrl)) {
                return Redirect::to($redirectUrl);
            }

            return Redirect::back();
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
            return Redirect::back();
        }
    }

    /**
     * Activate the user
     * @param  string $code Activation code
     */
    public function onActivate($code = null)
    {
        try {
            $code = post('code', $code);

            /*
             * Break up the code parts
             */
            $parts = explode('!', $code);
            if (count($parts) != 2) {
                return $this->page['activation'] = [
                    'status' => 'error', 
                    'message' => Lang::get('keerill.users::lang.register.activation_invalid_code')
                ];
            }
            list($userId, $code) = $parts;
            if (!strlen(trim($userId)) || !($user = AuthManager::findUserByCredentials(['id' => $userId]))) {
                return $this->page['activation'] = [
                    'status' => 'error', 
                    'message' => Lang::get('keerill.users::lang.messages.user_not_found')
                ];
            }
            if (!$user->attemptActivation($code)) {
                return $this->page['activation'] = [
                    'status' => 'error', 
                    'message' => Lang::get('keerill.users::lang.register.activation_invalid_code')
                ];
            }

            /*
             * Sign in the user
             */
            AuthManager::login($user);

            return $this->page['activation'] = [
                'status' => 'success', 
                'message' => Lang::get('keerill.users::lang.messages.user_activation_success')
                ];
        }
        catch (\Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
            return Redirect::back();
        }
    }

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    private function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);

        $link = $this->currentPageUrl([
            $this->property('paramCode') => $code
        ]);

        $data = [
            'name' => $user->name,
            'code' => $code,
            'link' => $link
        ];
        
        Mail::send('keerill.users::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }
}
