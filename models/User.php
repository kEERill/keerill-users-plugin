<?php namespace kEERill\Users\Models;

use Auth;
use Request;
use October\Rain\Database\Model;
use kEERill\Users\Models\Settings as UserSettings;

/**
 * User Model
 */
class User extends Model
{
    use \October\Rain\Database\Traits\Hashable;
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'oc_users';

    /**
     *Validation rules
     */
    public $rules = [
        'email'    => 'required|between:6,255|email|unique:oc_users',
        'group' => 'required',
        'name' => 'required|between:6,255|unique:oc_users',
        'password' => 'required:create|between:8,255|confirmed',
        'password_confirmation' => 'required_with:password|between:8,255'
    ];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [        
        'name',
        'email',
        'ip_address',
        'password',
        'password_confirmation'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'group' => ['kEERill\Users\Models\Group', 'table' => 'oc_users_groups']
    ];

    public $belongsToMany = [];

    public $attachOne = [
        'avatar' => ['System\Models\File']
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['activated_at', 'last_seen', 'last_activity', 'balance_paid_at'];

    /**
     * @var array The attributes that should be hidden for arrays.
     */
    protected $hidden = [ 'password', 'reset_password_code', 'activation_code', 'persist_code'];

    /**
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = ['reset_password_code', 'activation_code', 'persist_code'];

    /**
     * @var array List of attribute names which should be hashed using the Bcrypt hashing algorithm.
     */
    protected $hashable = ['persist_code'];

    /**
     * @var array List of attribute names which should not be saved to the database.
     */
    protected $purgeable = ['password_confirmation'];

    /**
     * @var array The array of custom attribute names.
     */
    public $attributeNames = [
        'name' => 'Имя пользователя',
        'group' => 'Группа',
        'email' => 'Почта пользователя',
        'password' => 'Пароль'
    ];

    /**
     * @var array The array of custom error messages.
     */
    public $customMessages = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['permissions'];

    /**
     * Allowed permissions values.
     *
     * Possible options:
     *   -1 => Deny (adds to array, but denies regardless of user's group).
     *    0 => Remove.
     *    1 => Add.
     *
     * @var array
     */
    protected $allowedPermissionsValues = [-1, 0, 1];

    /**
     * @var string The login attribute.
     */
    public static $loginAttribute = 'name';

    /**
     * @var array The user groups.
     */
    protected $userGroups;

    /**
     * @var array The user merged permissions.
     */
    protected $mergedPermissions;

    /**
     * @return string Returns the name for the user's login.
     */
    public function getLoginName()
    {
        return static::$loginAttribute;
    }

    /**
     * @return mixed Returns the user's login.
     */
    public function getLogin()
    {
        return $this->{$this->getLoginName()};
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
    
    public function beforeLogin()
    {

    }

    public function afterLogin()
    {
        $this->last_seen = $this->freshTimestamp();
        $this->ip_address = Request::ip();

        $this->forceSave();
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
     * Checks the given persist code.
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
     * @param mixed $activated
     * @return bool
     */
    public function getIsActivatedAttribute($activated)
    {
        return (bool) $activated;
    }

    /**
     * Get an activation code for the given user.
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
     * @param string $activationCode
     * @return bool
     */
    public function attemptActivation($activationCode)
    {
        if ($this->is_activated)
            throw new Exception('User is already active!');

        if ($activationCode == $this->activation_code) {
            $this->activation_code = null;
            $this->is_activated = true;
            $this->activated_at = $this->freshTimestamp();
            $this->group_id = UserSettings::get('group_activated', 3);

            $this->forceSave();
            return true;
        }

        return false;
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
     * Hashing password and set new password salt
     * @return void
     */
    public function makeHashPassword($value, $salt = false)
    {
        if($salt) {
            $this->password_salt = $this->getRandomSalt();
        }

        return md5(md5($this->password_salt).md5($value));
    }

    /**
     * Checks the password passed matches the user's password.
     * @param string $password
     * @return bool
     */
    public function checkPassword($password)
    {
        $password = md5(md5($this->password_salt).md5($password));
        return ($password == $this->password);
    }

    /**
     * Get a reset password code for the given user.
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
     * @param string $resetCode
     * @return bool
     */
    public function checkResetPasswordCode($resetCode)
    {
        return ($this->reset_password_code == $resetCode);
    }

    /**
     * Attempts to reset a user's password by matching the reset code generated with the user's.
     * @param string $resetCode
     * @param string $newPassword
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword)
    {
        if ($this->checkResetPasswordCode($resetCode)) {
            $this->password = $newPassword;
            $this->clearResetPassword();
            return $this->forceSave();
        }

        return false;
    }

    /**
     * Wipes out the data associated with resetting a password.
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
     * Generate a random password salt
     * @return string
     */
    public function getRandomSalt()
    {
        $pool = "#-+()012.34_56#^789abc+()defg\hijk~lmn_opqr{}=stuv.wxyzA.BC#^DE[FGH_IJKLMN#^OPQR\STUV]W~XYZ+()";

        $string	= "";
		$len	= strlen($pool) - 1;  

		while (strlen($string) < 5){
			$string .= $pool[mt_rand(0,$len)];  
		}

        return $string;
    }

    //
    // Manage ban user
    // 

    /**
     * Ban this user, preventing them from signing in.
     * @return void
     */
    public function ban()
    {
        Auth::findThrottleByUserId($this->id)->ban();

        $this->group_id = UserSettings::get('group_banned', 5);
        $this->forceSave();
    }

    /**
     * Remove the ban on this user.
     * @return void
     */
    public function unban()
    {
        Auth::findThrottleByUserId($this->id)->unban();

        $this->group_id =  UserSettings::get(($this->is_activated) ? 'group_activated' : 'group_no_activated', 1);
        $this->forceSave();
    }

    /**
     * Check if the user is banned.
     * @return bool
     */
    public function isBanned()
    {
        $throttle = Auth::createThrottleModel()->where('user_id', $this->id)->first();
        return $throttle ? $throttle->is_banned : false;
    }
}
