<?php namespace KEERill\Users\Components;

use Auth;
use Event;
use Cms\Classes\ComponentBase;

class SessionComponent extends ComponentBase
{
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
        $this->page['user'] = $this->user();
    }

    /**
     * Returns the logged in user, if available, and touches
     * the last seen timestamp.
     * @return KEERill\Users\Models\User
     */
    public function user()
    {
        if (!$user = Auth::getUser()) {
            return null;
        }
        return $user;
    }

    
    /**
     * Logout user. Use "<a data-request="onLogout" data-request-data="redirect: '/good-bye'">Sign out</a>"
     * @return redirect
     */

     public function onLogout()
     {
         $user = Auth::getUser();

         if($user) {
            Event::fire('keerill.users.logout', [$user]);
         }

         Auth::logout();

        $url = post('redirect', Request::fullUrl());

        return Redirect::to($url);
     }
}
