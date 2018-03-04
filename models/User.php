<?php namespace KEERill\Users\Models;

use Lang;
use Event;
use Request;
use AuthManager;
use ApplicationException;
use KEERill\Users\Models\Log;
use October\Rain\Database\Model;
use KEERill\Users\Models\Settings as UserSettings;

Class User extends Model 
{
    use \October\Rain\Database\Traits\Hashable;
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Revisionable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'oc_users';

    /**
     * Validation rules
     */
    public $rules = [
        'email'    => 'required|between:6,255|email|unique:oc_users',
        'group' => 'required',
        'name' => 'required|between:4,50|unique:oc_users',
        'password' => 'required:create|between:8,255|confirmed',
        'password_confirmation' => 'required_with:password|between:8,255'
    ];

    /**
     * @var array The array of custom attribute names.
     */
    public $attributeNames = [
        'name' => 'keerill.users::lang.user.name',
        'group' => 'keerill.users::lang.user.group',
        'email' => 'keerill.users::lang.user.email',
        'password' => 'keerill.users::lang.user.password',
        'password_confirmation' => 'keerill.users::lang.user.confirm_password'
    ];

    /**
     * @var array The array of custom error messages.
     */
    public $customMessages = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'group' => ['KEERill\Users\Models\Group', 'table' => 'oc_users_groups']
    ];

    public $hasMany = [
        'accesslogs' => [
            'KEERill\Users\Models\AccessLog', 
            'delete' => true
        ],
        'logs'     => [
            'KEERill\Users\Models\Log', 
            'delete' => true
        ]
    ];

    public $attachOne = [
        'avatar' => ['System\Models\File']
    ];

    public $morphMany = [
        'revision_history' => [
            'System\Models\Revision', 
            'name' => 'revisionable',
            'delete' => true
        ]
    ];

    /**
     * @var array Settings Field
     */
    public $settingsFields = [
        'password',
        'password_confirmation'
    ];
    /**
     * @var array Settings Rules
     */
    public $settingsRules = [
        'password' => 'between:8,255|confirmed',
        'password_confirmation' => 'required_with:password|between:8,255'
    ];

    /**
     * @var int Maximum number of revision records to keep.
     */
    public $revisionableLimit = 500;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['activated_at', 'last_seen', 'last_activity', 'is_banned_at'];

    /**
     * @var array The attributes that should be hidden for arrays.
     */
    protected $hidden = ['password', 'reset_password_code', 'activation_code', 'persist_code', 'balance'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'email', 'ip_address', 'is_banned', 'is_banned_reason', 'password', 'password_confirmation'];

    /**
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = ['reset_password_code', 'activation_code', 'persist_code', 'balance'];

    /**
     * @var array List of attribute names which should be hashed using the Bcrypt hashing algorithm.
     */
    protected $hashable = ['password', 'persist_code'];

    /**
     * @var array List of attribute names which should not be saved to the database.
     */
    protected $purgeable = ['password_confirmation'];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['permissions'];

    /**
     * @var array $revisionable
     */
    protected $revisionable = ['name', 'email', 'group', 'balance'];
    
    /**
     * @return mixed Returns the user's login.
     */
    public function getLogin()
    {
        return $this->name;
    }
    
    /**
     * Get the password for the user.
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the e-mail address where password reminders are sent.
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    /**
     * Get the token value for the "remember me" session.
     * @return string
     */
    public function getRememberToken()
    {
        return $this->getPersistCode();
    }

    /**
     * Set the token value for the "remember me" session.
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->persist_code = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'persist_code';
    }

    /**
     * Gets a code for when the user is persisted to a cookie or session which identifies the user.
     * @return string
     */
    public function getPersistCode()
    {
        $this->persist_code = $this->getRandomString();

        // Our code got hashed
        $persistCode = $this->persist_code;

        $this->forceSave();

        return $persistCode;
    }

    /**
     * Получение полей, которые можно изменить
     * @return array Массив полей
     */
    public function getSettingsFields()
    {
        return $this->settingsFields;
    }

    /**
     * Получение правил заполнения полей настроек пользователей
     * @return array Массив правил
     */
    public function getSettingsRules()
    {
        return $this->settingsRules;
    }

    /**
     * Получение причины блокировки пользователя
     * @return string
     */
    public function getBannedReason()
    {
        return $this->is_banned_reason ?: Lang::get('keerill.users::lang.user.ban_no_reason');
    }

    /**
     * Проверка, заблокирован ли пользователь
     * @return boolean
     */
    public function hasBanned()
    {
        return $this->is_banned;
    }

    /**
     * Проверка поля на запись изменений
     * @param string Название поля
     * @return bool
     */
    public function isRevisionableField($field) 
    {
        return in_array($field, $this->revisionable);
    }

    /**
     * Добавление новых полей для контроля изменения
     * 
     * @param array Название полей
     * @return void
     */
    public function addRevisionableFields($fields = [])
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $key => $field) {
            if (!$this->isRevisionableField($field)) {
                $this->revisionable[] = $field;
            }
        }
    }

    /**
     * Добавление нового поля для контроля изменения данного поля
     * 
     * @param string Название поля
     * @return void
     */
    public function addRevisionableField($field)
    {
        if (!$this->isRevisionableField($field)) {
            $this->revisionable[] = $field;
        }
    }

    /*
     * The 'beforeLogin' event
     */
    public function beforeLogin() {}
        
    /*
     * The 'afterLogin' event
     */
    public function afterLogin()
    {
        $this->last_seen = $this->freshTimestamp();
        $this->ip_address = Request::ip();

        $this->forceSave();
    }

    /**
     * Checks the given persist code.
     * 
     * @param string $persistCode
     * @return bool
     */
    public function checkPersistCode($persistCode)
    {
        if (!$persistCode || !$this->persist_code) {
            return false;
        }

        return $persistCode == $this->persist_code;
    }

    //
    // Activation
    //

    /**
     * Get mutator for giving the activated property.
     * 
     * @param mixed $activated
     * @return bool
     */
    public function getIsActivatedAttribute($activated)
    {
        return (bool) $activated;
    }

    /**
     * Get an activation code for the given user.
     * 
     * @return string
     */
    public function getActivationCode()
    {
        $this->activation_code = $activationCode = $this->getRandomString();

        $this->forceSave();

        return $activationCode;
    }

    /**
     * Attempts to activate the given user by checking the activate code. If the user is activated already, an Exception is thrown.
     * 
     * @param string $activationCode
     * @return bool
     */
    public function attemptActivation($activationCode)
    {
        if ($this->is_activated)
            return false;

        if ($activationCode == $this->activation_code) {
            $this->activation_code = null;
            $this->is_activated = true;
            $this->activated_at = $this->freshTimestamp();
            $this->group_id = UserSettings::get('group_activated', 3);

            Event::fire('keerill.users.activation', [$this]);

            $this->forceSave();
            return true;
        }

        return false;
    }

    /**
     * Events
     */
    public function beforeCreate()
    {
        $this->ip_address = \Request::ip();
    }

    // 
    // Scope
    // 

    public function scopeFilterByGroup($query, $filter)
    {
        return $query->whereHas('group', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
    }

    //
    // Password
    //

    /**
     * Get a reset password code for the given user.
     * 
     * @return string
     */
    public function getResetPasswordCode()
    {
        $this->reset_password_code = $resetCode = $this->getRandomString();
        $this->forceSave();
        return $resetCode;
    }

    /**
     * Checks if the provided user reset password code is valid without actually resetting the password.
     * 
     * @param string $resetCode
     * @return bool
     */
    public function checkResetPasswordCode($resetCode)
    {
        return $this->reset_password_code && ($this->reset_password_code == $resetCode);
    }

    /**
     * Attempts to reset a user's password by matching the reset code generated with the user's.
     * 
     * @param string $resetCode
     * @param string $newPassword
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword)
    {
        if ($this->checkResetPasswordCode($resetCode)) {
            $this->password = $newPassword;
            $this->clearResetPassword();

            Event::fire('keerill.users.reset', [$this]);

            return $this->forceSave();
        }

        return false;
    }

    /**
     * Wipes out the data associated with resetting a password.
     * 
     * @return void
     */
    public function clearResetPassword()
    {
        if ($this->reset_password_code) {
            $this->reset_password_code = null;
            $this->forceSave();
        }
    }

    /**
     * Protects the password from being reset to null.
     */
    public function setPasswordAttribute($value)
    {
        if ($this->exists && empty($value)) {
            unset($this->attributes['password']);
        }
        else {
            $this->attributes['password'] = $value;

            // Password has changed, log out all users
            $this->attributes['persist_code'] = null;
        }
    }

    //
    // Helpers
    //

    /**
     * Generate a random string
     * 
     * @return string
     */
    public function getRandomString($length = 42)
    {
        /*
         * Use OpenSSL (if available)
         */
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);

            if ($bytes === false) {
                throw new RuntimeException('Unable to generate a random string');
            }

            return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
        }

        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * Ban this user, preventing them from signing in.
     * 
     * @return void
     */
    public function ban(array $data, $sessionKey = null)
    {
        if (!$data) {
            return false;
        }

        if (array_get($data, 'is_banned')) {

            $this->group_id = UserSettings::get('group_banned', 4);

            /**
             * Добавляем запись о блокировке пользователя
             */
            Log::add($this, Lang::get('keerill.users::lang.user.banned_log', [
                'reason' => array_get($data, 'is_banned_reason') ?: Lang::get('keerill.users::lang.user.ban_no_reason')
            ]), 'user_ban');
        
        } else {
            $this->group_id = UserSettings::get('group_activated', 3);

            /**
             * Добавляем запись о разблокировке пользователя
             */
            Log::add($this, Lang::get('keerill.users::lang.user.unbanned_log', [
                'reason' => array_get($data, 'is_banned_reason') ?: Lang::get('keerill.users::lang.user.ban_no_reason')
            ]), 'user_ban');
        }

        Event::fire('keerill.users.ban', [$this, $data]);

        $this->fill($data);
        $this->is_banned_at = $this->freshTimestamp();

        $this->save(null, $sessionKey);
    }
}
