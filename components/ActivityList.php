<?php namespace KEERill\Users\Components;

use AuthManager;
use Cms\Classes\ComponentBase;

class ActivityList extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'keerill.users::lang.activity.component_name',
            'description' => 'keerill.users::lang.activity.component_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'limit' => [
                'title' => 'keerill.users::lang.activity.count',
                'type' => 'string',
                'validationPattern' => '^[0-9]+$',
                'default' => '10'
            ]
        ];
    }

    public function onRun()
    {
        if (!$user = $this->user()) {
            return;
        }

        $this->page['user_activity'] = $user->logs()->limit($this->property('limit'))->orderBy('created_at', 'DESC')->get();
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
