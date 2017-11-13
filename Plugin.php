<?php namespace KEERill\Users;

use App;
use Event;
use Backend;
use AuthManager;
use Carbon\Carbon;
use System\Classes\PluginBase;
use KEERill\Users\Models\User;
use KEERill\Users\Models\Group;
use KEERill\Users\Models\AccessLog;
use System\Classes\SettingsManager;
use Illuminate\Foundation\AliasLoader;
use KEERill\Users\Classes\UserEventHandler;
use KEERill\Users\Models\Settings as UserSettings;


/**
 * Users Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * @var Components Loaded
     */
    private $components;
    
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'keerill.users::lang.plugin.name',
            'description' => 'keerill.users::lang.plugin.description',
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
        $alias->alias('AuthManager', 'KEERill\Users\Facades\Auth');

        App::singleton('user.auth', function() {
            return \KEERill\Users\Classes\AuthManager::instance();
        });

        \Event::subscribe(new UserEventHandler);
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        $this->components = [
            'KEERill\Users\Components\Auth' => 'user_auth',
            'KEERill\Users\Components\Register' => 'user_register',
            'KEERill\Users\Components\Session' => 'user_session',
            'KEERill\Users\Components\Settings' => 'user_settings',
            'KEERill\Users\Components\Reset' => 'user_reset',
            'KEERill\Users\Components\Log' => 'user_log',
            'KEERill\Users\Components\Activity' => 'user_activity'
        ];

        Event::fire('keerill.users.extendsComponents', [$this]);

        return $this->components;
    }

    /**
     * Добавление нового компонента
     * @param array ['Namespace' => 'name']
     * @return array Components
     */
    public function addComponent($components)
    {
        return $this->components = array_replace($this->components, $components);
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'keerill.users.access_users' => [
                'tab' => 'keerill.users::lang.plugin.name',
                'label' => 'keerill.users::lang.permissions.users'
            ],
            'keerill.users.access_groups' => [
                'tab' => 'keerill.users::lang.plugin.name',
                'label' => 'keerill.users::lang.permissions.groups'
            ],
            'keerill.users.access_logs' => [
                'tab' => 'keerill.users::lang.plugin.name',
                'label' => 'keerill.users::lang.permissions.accessLogs'
            ],
            'keerill.users.users_logs' => [
                'tab' => 'keerill.users::lang.plugin.name',
                'label' => 'keerill.users::lang.permissions.usersLogs'
            ]
        ];
    }

    /**
     * Registers any users permissions used by this plugin.
     *
     * @return array
     */
    public function registerUsersPermissions()
    {
        return [
            'keerill.users.view' => [
                'tab' => 'keerill.users::lang.plugin.name',
                'label' => 'keerill.users::lang.permissions.frontend.accessSite',
                'order' => '1'
            ],
            'keerill.users.settings' => [
                'tab' => 'keerill.users::lang.plugin.name',
                'label' => 'keerill.users::lang.permissions.frontend.settings',
                'order' => '2'
            ]
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
                'label'       => 'keerill.users::lang.users.label',
                'url'         => Backend::url('keerill/users/users'),
                'icon'        => 'icon-user',
                'permissions' => ['keerill.users.*'],
                'order'       => 234,
                'sideMenu' => [
                    'users' => [
                        'label'       => 'keerill.users::lang.users.sideMenu',
                        'icon'        => 'icon-user',
                        'url'         => Backend::url('keerill/users/users'),
                        'permissions' => ['keerill.users.access_users']
                    ],
                    'groups' => [
                        'label'       => 'keerill.users::lang.groups.sideMenu',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('keerill/users/groups'),
                        'permissions' => ['keerill.users.access_groups']
                    ]
                ]   
            ]
        ];
    }

    public function registerSettings()
    {

        Event::listen('system.settings.extendItems', function ($manager) {
            \KEERill\Users\Models\Settings::filterSettingItems($manager);
        });

        return [
            'users' => [
                'label'       => 'keerill.users::lang.settings.users',
                'description' => 'keerill.users::lang.settings.users_desc',
                'category'    => 'keerill.users::lang.plugin.name',
                'icon'        => 'icon-users',
                'class'       => 'KEERill\Users\Models\Settings',
                'order'       => 500
            ],
            'accesslogs' => [
                'label'       => 'keerill.users::lang.settings.accessLogs',
                'description' => 'keerill.users::lang.settings.accessLogs_desc',
                'category'    => SettingsManager::CATEGORY_LOGS,
                'url'         => Backend::url('keerill/users/accesslogs'),
                'icon'        => 'icon-users',
                'permissions' => ['keerill.users.access_logs']
            ],
            'logs' => [
                'label'       => 'keerill.users::lang.settings.usersLogs',
                'description' => 'keerill.users::lang.settings.usersLogs_desc',
                'category'    => SettingsManager::CATEGORY_LOGS,
                'url'         => Backend::url('keerill/users/logs'),
                'icon'        => 'icon-users',
                'permissions' => ['keerill.users.users_logs']
            ]
        ];

    }

    public function registerMailTemplates()
    {
        return [
            'keerill.users::mail.activate'   => 'keerill.users::lang.mail.activate',
            'keerill.users::mail.restore'   => 'keerill.users::lang.mail.restore'
         ];
    }

    public function registerMarkupTags()
    {
        return [
            'functions' => [
                'hasAccess' => function($permission) { return AuthManager::hasAccess($permission); },
            ]
        ];
    }

    public function registerSchedule($schedule)
    {
        $schedule->call(function () {
            if (!$delDays = intval(UserSettings::get('del_noActUsers_days'))) {
                return;
            }

            $now = new Carbon;
            $now->timezone(config('app.timezone', 'UTC'));

            $users = User::where('is_activated', '0')->where('created_at', '<', $now->subDay($delDays))->get();
           
            foreach ($users as $user) {
                $user->delete();
            }
        })->daily();

        $schedule->call(function () {
            if (!$delDays = intval(UserSettings::get('del_oldAccessLogs_days'))) {
                return;
            }

            $now = new Carbon;
            $now->timezone(config('app.timezone', 'UTC'));

            AccessLog::where('created_at', '<', $now->subDay($delDays))->delete();
        })->daily();
    }
}
