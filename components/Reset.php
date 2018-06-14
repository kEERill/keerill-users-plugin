<?php namespace KEERill\Users\Components;

use Mail;
use Lang;
use Flash;
use Request;
use Redirect;
use Validator;
use AuthManager;
use Cms\Classes\Page;
use ValidationException;
use ApplicationException;
use KEERill\Users\Models\Log;
use Cms\Classes\ComponentBase;

class Reset extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'keerill.users::lang.reset.component_name',
            'description' => 'keerill.users::lang.reset.component_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'paramCode' => [
                'title' => 'keerill.users::lang.reset.code',
                'description' => 'keerill.users::lang.reset.code_desc',
                'type' => 'string',
                'default' => 'code'
            ],
            'redirect' => [
                'title' => 'keerill.users::lang.reset.redirect',
                'description' => 'keerill.users::lang.reset.redirect_desc',
                'type' => 'dropdown',
                'default' => ''
            ]
        ];
    }

    public function getRedirectOptions()
    {
        return ['' => ' - none - '] + Page::sortBy('baseFileName')->lists('title', 'baseFileName');
    }

    /**
     * Trigger the password reset email
     */
    public function onRestorePassword()
    {
        $rules = [
            'email' => 'required|email|between:6,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        if (!$user = AuthManager::findUserByCredentials(['email' => post('email')])) {
            throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_not_found'));
        }

        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);
        $link = $this->controller->currentPageUrl([
            $this->property('paramCode') => $code
        ]);

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('keerill.users::mail.restore', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });

        Flash::success(Lang::get('keerill.users::lang.messages.user_send_mail'));
    }

    /**
     * Perform the password reset
     */
    public function onResetPassword()
    {
        try {
            $rules = [
                'code'     => 'required',
                'password' => 'required|between:8,255|confirmed',
                'password_confirmation' => 'required_with:password|between:8,255'
            ];
            $validation = Validator::make(post(), $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }
    
            /*
             * Break up the code parts
             */
            $parts = explode('!', post('code'));
            if (count($parts) != 2) {
                throw new ValidationException(['code' => Lang::get('keerill.users::lang.reset.reset_code_invalid')]);
            }
    
            list($userId, $code) = $parts;
    
            if (!strlen(trim($userId)) || !($user = AuthManager::findUserByCredentials(['id' => $userId]))) {
                throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_not_found'));
            }
    
            if (!$user->attemptResetPassword($code, post('password'))) {
                throw new ValidationException(['code' => Lang::get('keerill.users::lang.reset.reset_code_invalid')]);
            }
    
            Log::add($user, Lang::get('keerill.users::lang.reset.reset_success'), 'user_reset');
    
            Flash::success(Lang::get('keerill.users::lang.reset.reset_success'));
    
            $redirectUrl = $this->pageUrl($this->property('redirect'));
    
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

    /**
     * Returns the reset password code from the URL
     * @return string
     */
    public function code()
    {
        $routeParameter = $this->property('paramCode');
        return $this->param($routeParameter);
    }
}
