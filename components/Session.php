<?php namespace KEERill\Users\Components;

use Mail;
use AuthManager;
use Event;
use Flash;
use Request;
use Redirect;
use ApplicationException;
use Cms\Classes\ComponentBase;

class Session extends ComponentBase
{
    public $user;

    public function componentDetails()
    {
        return [
            'name'        => 'Сессия',
            'description' => 'Определяет пользователя по кукам или сессии'
        ];
    }

    public function defineProperties()
    {
        return [];
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
        $code = implode('!', [$user->id, $user->getActivationCode()]);
        $data = [
            'name' => $user->name,
            'code' => $code
        ];
        Mail::send('keerill.users::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }
}
