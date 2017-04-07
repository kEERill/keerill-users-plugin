<?php namespace Keerill\Users;

use App;
use Backend;
use System\Classes\PluginBase;
use Keerill\Users\Models\User;
use Illuminate\Foundation\AliasLoader;

/**
 * Users Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Пользователи',
            'description' => 'Управление пользователями и их правами',
            'author'      => 'kEERill',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $alias = AliasLoader::getInstance();
        $alias->alias('Auth', 'Keerill\Users\Facades\Auth');

        App::singleton('user.auth', function() {
            return \Keerill\Users\Classes\AuthManager::instance();
        });
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        User::extend(function($model) {
            $model->bindEvent('model.beforeSetAttribute', function($key, $value) use ($model) {
                if ($key == 'password' && !empty($value)) {
                    return $model->makeHashPassword($value, true);
                }

                if($key == 'password_confirmation' && !empty($value)) {
                    return $model->makeHashPassword($value);
                }
            });
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Keerill\Users\Components\AuthComponent' => 'authComponent',
            'Keerill\Users\Components\RegisterComponent' => 'registerComponent',
            'Keerill\Users\Components\SessionComponent' => 'sessionComponent'
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'october.users.some_permission' => [
                'tab' => 'Users',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {

        return [
            'users' => [
                'label'       => 'Пользователи',
                'url'         => Backend::url('october/users/users'),
                'icon'        => 'icon-user',
                'permissions' => ['october.users.*'],
                'order'       => 500,
                'sideMenu' => [

                    'users' => [
                        'label'       => 'Управление пользователями',
                        'icon'        => 'icon-user',
                        'url'         => Backend::url('october/users/users')
                    ],

                    'groups' => [
                        'label'       => 'Управление группами',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('october/users/groups')
                    ],
                    'permissions' => [
                        'label'       => 'Управление правами',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('october/users/permissions')
                    ]
                ]   
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'users' => [
                'label'       => 'Настройка пользователей',
                'description' => 'Управление настройками пользователей',
                'category'    => 'Пользователи',
                'icon'        => 'icon-users',
                'class'       => 'Keerill\Users\Models\Settings',
                'order'       => 500
            ]
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'october.users::mail.activate'   => 'Письмо с инструкциями для активации аккаунта новых пользователей',
         ];
    }
}
