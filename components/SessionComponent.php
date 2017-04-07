<?php namespace kEERill\Users\Components;

use Auth;
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
     * @return kEERill\Users\Models\User
     */
    public function user()
    {
        if (!$user = Auth::getUser()) {
            return null;
        }
        return $user;
    }
}
