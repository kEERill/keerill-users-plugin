<?php namespace kEERill\Users\Components;

use Auth;
use Mail;
use Flash;
use Event;
use Request;
use Redirect;
use Validator;
use ValidationException;
use ApplicationException;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use kEERill\Users\Models\Settings as UserSettings;

class RegisterComponent extends ComponentBase
{
    public $canRegister;

    public function componentDetails()
    {
        return [
            'name'        => 'Регистрация',
            'description' => 'Форма регистрации пользователя'
        ];
    }

    public function defineProperties()
    {
        return [
            'paramCode' => [
                'title' => 'Параметр кода',
                'description' => 'Параметр, в котором передаётся код активации',
                'type' => 'string',
                'default' => 'code'
            ]
        ];
    }

    public function onRun()
    {
        /*
         * Activation code supplied
         */
        $routeParameter = $this->property('paramCode');
        if ($activationCode = $this->param($routeParameter)) {
            $this->onActivate($activationCode);
        }

        $this->page['user'] = $this->user();

    }

    public function init()
    {
        $this->canRegister = $this->page['canRegister'] = UserSettings::get('allow_registration', false);
    }

    /**
     * Returns the logged in user, if available
     */
    
    public function user()
    {
        if (!Auth::check()) {
            return null;
        }
        return Auth::getUser();
    }

     /**
     * Register the user
     */
    public function onRegister()
    {
        try {
            if (!$this->canRegister) {
                throw new ApplicationException("В данный момент регистрация отключена администрацией сайта");
            }

            if($this->user()) {
                throw new ApplicationException("Вы уже вошли в систему");
            }

            /*
             * Validate input
             */
            $data = post();

            if (!array_key_exists('password_confirmation', $data)) {
                $data['password_confirmation'] = post('password');
            }

            if (!array_key_exists('ip_address', $data)) {
                $data['ip_address'] = Request::ip();
            }

            $rules = [
                'name'    => 'required|between:6,255',
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
            Event::fire('october.users.beforeRegister', [&$data]);
            
            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            $user = Auth::register($data, $automaticActivation);
            
            Event::fire('october.users.register', [$user, $data]);

            /*
             * Activation is by the user, send the email
             */
            if ($userActivation) {
                $this->sendActivationEmail($user);
                Flash::success("Письмо с дальнейшими инструкциями по активации было выслано на указанный адрес электронной почты");
            }

            /*
             * Automatically activated or not required, log the user in
             */
            if ($automaticActivation || !$requireActivation) {
                Auth::login($user);
            }
            
            return Redirect::back();
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
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
                throw new ValidationException(['code' => 'Неверный код активации']);
            }
            list($userId, $code) = $parts;
            if (!strlen(trim($userId)) || !($user = Auth::findUserById($userId))) {
                throw new ApplicationException('Пользователь с такими данным не найден');
            }
            if (!$user->attemptActivation($code)) {
                throw new ValidationException(['code' => 'Неверный код активации']);
            }
            Flash::success('Успешная активация пользователя');

            /*
             * Sign in the user
             */
            Auth::login($user);
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * Trigger a subsequent activation email
     */
    public function onSendActivationEmail()
    {
        try {
            if (!$user = $this->user()) {
                throw new ApplicationException("Пользователь с такими данным не найден");
            }
            if ($user->is_activated) {
                throw new ApplicationException("Пользователь уже активирован");
            }
            Flash::success("Письмо с дальнейшими инструкциями по активации было выслано на указанный адрес электронной почты.");
            $this->sendActivationEmail($user);
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
        /*
         * Redirect
         */
        if ($redirect = $this->makeRedirection()) {
            return $redirect;
        }
    }

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    protected function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $link = $this->currentPageUrl([
            $this->property('paramCode') => $code
        ]);
        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];
        Mail::send('october.users::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }
}
