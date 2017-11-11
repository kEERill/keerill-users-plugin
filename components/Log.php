<?php namespace KEERill\Users\Components;

use AuthManager;
use Cms\Classes\ComponentBase;

class Log extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Логи авторизации',
            'description' => 'Выводит список авторизаций пользователя'
        ];
    }

    public function defineProperties()
    {
        return [
            'limit' => [
                'title' => 'Количество строк',
                'type' => 'string',
                'validationPattern' => '^[0-9]+$',
                'default' => '10'
            ]
        ];
    }

    public function onRun()
    {
        if ($user = $this->user()) {
            $this->page['user_logs'] = $user->accesslogs()->limit($this->property('limit'))->orderBy('created_at', 'DESC')->get();
        }
    }

    /**
     * Returns the logged in user, if available, and touches
     * the last seen timestamp.
     * @return KEERill\Users\Models\User
     */
    public function user()
    {
        if (!$user = AuthManager::getUser()) {
            return null;
        }
        return $user;
    }
}
