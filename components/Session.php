<?php namespace KEERill\Users\Components;

use Mail;
use Lang;
use Event;
use Flash;
use Request;
use Redirect;
use AuthManager;
use Cms\Classes\Page;
use ApplicationException;
use Cms\Classes\ComponentBase;

class Session extends ComponentBase
{
    public $user;

    public function componentDetails()
    {
        return [
            'name'        => 'keerill.users::lang.session.component_name',
            'description' => 'keerill.users::lang.session.component_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'page_register' => [
                'title' => 'keerill.users::lang.session.page_register',
                'description' => 'keerill.users::lang.session.page_register_desc',
                'type' => 'dropdown',
                'default' => ''
            ],
            'paramCode' => [
                'title' => 'keerill.users::lang.register.code',
                'description' => 'keerill.users::lang.register.code_desc',
                'type' => 'string',
                'default' => 'code'
            ]
        ];
    }

    public function getPageRegisterOptions()
    {
        return ['' => ' - none - '] + Page::sortBy('baseFileName')->lists('title', 'baseFileName');
    }

    public function onRun()
    {
        $this->user = $this->page['user'] = $this->user();
    }

    /**
     * Returns the logged in user, if available, and touches
     * the last seen timestamp.
     * @return KEERill\Users\Models\User
     */
    private function user()
    {
        if (!$user = AuthManager::getUser()) {
            return null;
        }
        return $user;
    }

    /**
     * Trigger a subsequent activation email
     */
    public function onSendActivationEmail()
    {
        try {
            if (!$user = $this->user()) {
                throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_not_found'));
            }
            if ($user->is_activated) {
                throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_is_activated'));
            }

            Flash::success(Lang::get('keerill.users::lang.messages.user_send_mail'));

            $this->sendActivationEmail($user);
        }
        catch (\Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
            return Redirect::back();
        }
    }
    
    /**
     * Logout user. Use "<a data-request="onLogout" data-request-data="redirect: '/good-bye'">Sign out</a>"
     * @return redirect
     */

    public function onLogout()
    {
        $user = $this->user();

        if($user) {
            Event::fire('keerill.users.logout', [$this, $user]);
        }

        AuthManager::logout();

        $url = post('redirect', Request::fullUrl());

        return Redirect::to($url);
    }

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    private function sendActivationEmail($user)
    {
        if (!$page = $this->property('page_register')) {
            throw new ApplicationException(Lang::get('keerill.users::lang.session.page_not_found'));
        }

        $code = implode('!', [$user->id, $user->getActivationCode()]);
        
        $link = $this->pageUrl($page, [
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
