<?php namespace KEERill\Users\Classes;

use Mail;
use Lang;
use Event;
use Cookie;
use Session;
use Request;
use Validator;
use ValidationException;
use ApplicationException;
use System\Classes\PluginManager;
use KEERill\Users\Models\AccessLog;
use KEERill\Users\Models\Settings as UserSettings;

class AuthManager
{
    use \October\Rain\Support\Traits\Singleton;

    protected $user;

    protected $throttle = [];

    protected $sessionKey = 'user_auth';

    protected $userModel = 'KEERill\Users\Models\User';

    protected $groupModel = 'KEERill\Users\Models\Group';

    protected $throttleModel = 'KEERill\Users\Models\Throttle';

    protected $useThrottle = true;

    protected $requireActivation = true;

    public $userPermissionsCache = false;

    
    /**
     * @var array Страндарт прав
     */
    protected static $permissionDefaults = [
        'code'    => null,
        'label'   => null,
        'comment' => null,
        'order'   => 500
    ];

    /**
     * @var array List of registered permissions.
     */
    protected $permissions = [];

    /**
     * @var array Cache of registered permissions.
     */
    protected $permissionsCache = false;

    protected function init()
    {
        $this->ipAddress = Request::ip();

        $this->useThrottle = UserSettings::get('use_throttle', $this->useThrottle);
        $this->requireActivation = UserSettings::get('require_activation', $this->requireActivation);
    }

    /**
     * Creates a new instance of the user model
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
        $user = new $class();
        return $user;
    }

    /**
     * Prepares a query derived from the user model.
     */
    protected function createUserModelQuery()
    {
        $model = $this->createUserModel();
        $query = $model->newQuery();
        $this->extendUserQuery($query);
        return $query;
    }

    /**
     * Extend the query used for finding the user.
     * @param \October\Rain\Database\Builder $query
     * @return void
     */
    public function extendUserQuery($query) {}

    /**
     * Creates an instance of the throttle model
     */
    public function createThrottleModel()
    {
        $class = '\\'.ltrim($this->throttleModel, '\\');
        $throttle = new $class();
        return $throttle;
    }

    /**
     * Creates an instance of the permission model
     */
    public function createGroupModel()
    {
        $class = '\\'.ltrim($this->groupModel, '\\');
        $group = new $class();
        return $group;
    }

    /**
     * Returns the current user, if any.
     */
    public function getUser()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }

    /**
     * Sets the user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Check to see if the user is logged in and activated, and hasn't been banned or suspended.
     *
     * @return bool
     */
    public function check()
    {
        if (is_null($this->user)) {

            /*
             * Check session first, follow by cookie
             */
            if (
                !($userArray = Session::get($this->sessionKey)) &&
                !($userArray = Cookie::get($this->sessionKey))
            ) {
                return false;
            }

            /*
             * Check supplied session/cookie is an array (username, persist code)
             */
            if (!is_array($userArray) || count($userArray) !== 2) {
                return false;
            }

            list($id, $persistCode) = $userArray;

            /*
             * Look up user
             */
            if (!$user = $this->createUserModel()->find($id)) {
                return false;
            }

            /*
             * Confirm the persistence code is valid, otherwise reject
             */
            if (!$user->checkPersistCode($persistCode)) {
                return false;
            }

            /*
             * Pass
             */
            $this->user = $user;
        }

        /*
         * Check cached user is activated
         */
        if (!($user = $this->getUser()) || ($this->requireActivation && !$user->is_activated)) {
            return false;
        }

        return true;
    }

    /**
     * Logs the current user out.
     */
    public function logout()
    {
        if ($this->user) {
            $this->user->setRememberToken(null);
            $this->user->forceSave();
        }

        $this->user = null;

        Session::forget($this->sessionKey);
        Cookie::queue(Cookie::forget($this->sessionKey));
    }

    /**
     * Finds a user by the given credentials.
     */
    public function findUserByCredentials(array $credentials)
    {
        $model = $this->createUserModel();

        $hashableAttributes = $model->getHashableAttributes();
        $hashedCredentials = [];

        $query = $model->newQuery();

        /*
         * Build query from given credentials
         */
        foreach ($credentials as $credential => $value) {
            if (in_array($credential, $hashableAttributes)) {
                $hashedCredentials = array_merge($hashedCredentials, [$credential => $value]);
            }
            else {
                $query = $query->where($credential, '=', $value);
            }
        }

        if (!$user = $query->first()) {
            throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_credentials_invalid'));
        }

        /*
         * Check the hashed credentials match
         */
        foreach ($hashedCredentials as $credential => $value) {

            if (!$user->checkHashValue($credential, $value)) {
                // Incorrect password
                if ($credential == 'password') {
                    throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_credentials_invalid'));
                }

                // User not found
                throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_not_found'));
            }
        }

        return $user;
    }

    /**
     * Find a throttle record by user id and ip address
     */
    public function findThrottleByUserId($userId, $ipAddress = null)
    {
        $cacheKey = md5($userId.$ipAddress);
        if (isset($this->throttle[$cacheKey])) {
            return $this->throttle[$cacheKey];
        }

        $model = $this->createThrottleModel();
        $query = $model->where('user_id', '=', $userId);

        if ($ipAddress) {
            $query->where(function($query) use ($ipAddress) {
                $query->where('ip_address', '=', $ipAddress);
                $query->orWhere('ip_address', '=', null);
            });
        }

        if (!$throttle = $query->first()) {
            $throttle = $this->createThrottleModel();
            $throttle->user_id = $userId;
            if ($ipAddress) {
                $throttle->ip_address = $ipAddress;
            }

            $throttle->save();
        }

        return $this->throttle[$cacheKey] = $throttle;
    }

    /**
     * Logs in the given user and sets properties
     * in the session.
     */
    public function login($user, $remember = true)
    {
        /*
         * Fire the 'beforeLogin' event
         */
        $user->beforeLogin();

        /*
         * Activation is required, user not activated
         */
        if ($this->requireActivation && !$user->is_activated) {
            throw new ApplicationException(
                Lang::get('keerill.users::lang.messages.user_no_activate', ['name' => $user->name])
            );
        }

        $this->setUser($user);

        /*
         * Create session/cookie data to persist the session
         */
        $toPersist = [$user->getKey(), $user->getPersistCode()];
        Session::put($this->sessionKey, $toPersist);

        if ($remember) {
            Cookie::queue(Cookie::forever($this->sessionKey, $toPersist));
        }

        /*
         * Fire the 'afterLogin' event
         */
        $user->afterLogin();
    }

    /**
     * {@inheritDoc}
     */
    public function register(array $credentials, $activate = false)
    {

        $user = $this->createUserModel();
        $user->fill($credentials);

        $user->group_id = UserSettings::get( ($activate) ? 'group_activated' : 'group_no_activated', 1);
        
        $user->save();

        if ($activate) {
            $user->attemptActivation($user->getActivationCode());
        }

        // Prevents revalidation of the password field
        // on subsequent saves to this model object
        $user->password = null;

        return $this->user = $user;
    }

    /**
     * Attempts to authenticate the given user according to the passed credentials.
     *
     * @param array $credentials The user login details
     * @param bool $remember Store a non-expire cookie for the user
     */
    public function authenticate(array $credentials, $remember = true, $customMessage = '')
    {
            
        $customMessage = $customMessage ?: 'keerill.users::lang.messages.user_auth';

        /*
         * Проверка важных параметров для авторизации
         */
        if (empty($credentials['name'])) {
            throw new ApplicationException('The name attribute is required.');
        }

        if (empty($credentials['password'])) {
            throw new ApplicationException('The password attribute is required.');
        }

        /*
         * Получаем пользователя
         */
        $user = $this->findUserByCredentials(['name' => $credentials['name']]);

        /*
         * If throttling is enabled, check they are not locked out first and foremost.
         */
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByUserId($user->id, $this->ipAddress);
            $throttle->check();
        }

        $allow_groups = UserSettings::get('allow_groups', 0);

        /*
         * Проверка на доступ к авторизации
         */
        if ($allow_groups) {
            foreach ($allow_groups as $num => $group) {
                if ($group == $user->group_id) {
                    throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_credentials_invalid'));
                }
            }
        }

        /*
         * Look up the user by authentication credentials.
         */
        try {
            if (!$user->checkHashValue('password', array_get($credentials, 'password'))) {
                throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_credentials_invalid'));
            }
        }
        catch (ApplicationException $ex) {

            /*
            * Добавляем запись о том, что авторизация не удалась
            */
            AccessLog::add($user, Lang::get($customMessage, [
                'status' => $ex->getMessage()
            ]), false);

            /*
            * Добавляем неверную попытку
            */
            if ($this->useThrottle) {
                $throttle->addLoginAttempt();
            }

            throw $ex;
        }

        /*
         * Очищаем все попытки авторизации
         */
        if ($this->useThrottle) {
            $throttle->clearLoginAttempts();
        }

        $user->clearResetPassword();
        $this->login($user, $remember);

        /*
         * Добавляем запись о том, что пользователь успешно авторизировался
         */
        AccessLog::add($user, Lang::get($customMessage, [
            'status' => Lang::get('keerill.users::lang.messages.user_auth_success')
        ]), true);

        return $this->user = $user;
    }

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    public function getGroupIdToUser($user = null)
    {
        if(!$user) {
            $user = $this->getUser();
        }

        if($user) {
            return $user->group_id;
        }

        return UserSettings::get('group_guest', 2);
    }

    /**
     * Регистрация прав пользователей
     * 
     * @param string ID плагина
     * @param array 
     */
    public function registerPermissions($owner, array $definitions)
    {
        foreach ($definitions as $code => $definition) {
            $permission = (object) array_merge(self::$permissionDefaults, array_merge($definition, [
                'code' => $code,
                'owner' => $owner
            ]));

            $this->permissions[] = $permission;
        }
    }

    /**
     * Returns a list of the registered permissions items.
     * @return array
     */
    public function listPermissions()
    {
        if ($this->permissionsCache !== false) {
            return $this->permissionsCache;
        }

        /*
        * Load plugin items
        */
        $plugins = PluginManager::instance()->getPlugins();

        foreach ($plugins as $id => $plugin) {
            if (method_exists($plugin, 'registerUsersPermissions')) {
                $items = $plugin->registerUsersPermissions();
                if (!is_array($items)) {
                    continue;
                }
    
                $this->registerPermissions($id, $items);
            }
        }

        /*
        * Sort permission items
        */
        usort($this->permissions, function ($a, $b) {
            if ($a->order == $b->order) {
                return 0;
            }

            return $a->order > $b->order ? 1 : -1;
        });

        return $this->permissionsCache = $this->permissions;
    }

    /**
     * Returns an array of registered permissions, grouped by tabs.
     * @return array
     */
    public function listTabbedPermissions()
    {
        $tabs = [];

        foreach ($this->listPermissions() as $permission) {
            $tab = isset($permission->tab)
                ? $permission->tab
                : 'backend::lang.form.undefined_tab';

            if (!array_key_exists($tab, $tabs)) {
                $tabs[$tab] = [];
            }

            $tabs[$tab][] = $permission;
        }

        return $tabs;
    }


    /**
     * Returns a list of the permissions group.
     * @return array 
     */
    public function listUserPermissions()
    {
        if ($this->userPermissionsCache !== false) {
            return $this->userPermissionsCache;
        }

        if ($perm = $this->createGroupModel()->find($this->getGroupIdToUser())) {
            return  $this->userPermissionsCache = $perm->permissions;
        }
        
        return $this->userPermissionsCache = null;
    }

    /**
     * Has permissions
     * @return array 
     */
    public function hasAccess($permission)
    {
        $groupPermissions = $this->listUserPermissions();

        if($groupPermissions) {
            foreach ($groupPermissions as $userPermission => $value) {
                if($userPermission == $permission && $groupPermissions[$permission] == 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
