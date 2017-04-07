<?php namespace KEERill\Users\Classes;

use October\Rain\Auth\Manager as RainAuthManager;
use KEERill\Users\Models\Settings as UserSettings;
use KEERill\Users\Models\Group as UserGroupModel;
use October\Rain\Auth\AuthException;

class AuthManager extends RainAuthManager
{
    protected static $instance;

    protected $sessionKey = 'user_auth';

    protected $userModel = 'KEERill\Users\Models\User';

    protected $groupModel = 'KEERill\Users\Models\Group';

    protected $throttleModel = 'KEERill\Users\Models\Throttle';

    public function init()
    {
        $this->useThrottle = UserSettings::get('use_throttle', $this->useThrottle);
        $this->requireActivation = UserSettings::get('require_activation', $this->requireActivation);
        parent::init();
    }

    /**
     * Finds a user by the given credentials.
     */
    public function findUserByCredentials(array $credentials)
    {
        $model = $this->createUserModel();
        $loginName = $model->getLoginName();

        if (!array_key_exists($loginName, $credentials)) {
            throw new AuthException(sprintf('Атрибут "%s" не был передан.', $loginName));
        }

        $query = $this->createUserModelQuery();

        /*
         * Build query from given credentials
         */
        foreach ($credentials as $credential => $value) {
            if($credential != 'password'){
                $query = $query->where($credential, '=', $value);
            } 
        }

        if (!$user = $query->first()) {
            throw new AuthException('Неверные данные! Проверьте, правильность логина и пароля.');
        }

        if(!$user->checkPassword($credentials['password'])){
            throw new AuthException('Неверные данные! Проверьте, правильность логина и пароля.');
        }

        return $user;
    }

    /**
     * Find a throttle record by login and ip address
     */
    public function findThrottleByLogin($loginName, $ipAddress)
    {
        $user = $this->findUserByLogin($loginName);
        if (!$user) {
            throw new AuthException("Неверные данные! Проверьте, правильность логина и пароля.");
        }

        $userId = $user->getKey();
        return $this->findThrottleByUserId($userId, $ipAddress);
    }

    /**
     * {@inheritDoc}
     */
    public function login($user, $remember = true)
    {
        parent::login($user, $remember);
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
     * Returns the current user, if any.
     */
    public function getUser()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }
}
