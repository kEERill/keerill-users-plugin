<?php namespace KEERill\Users\Models;

use October\Rain\Auth\AuthException;
use October\Rain\Auth\Models\Throttle as ThrottleBase;

/**
 * Throttle Model
 */
class Throttle extends ThrottleBase
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'oc_users_throttles';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => 'KEERill\Users\Models\User'
    ];

    /**
     * Check user throttle status.
     * @return bool
     * @throws AuthException
     */
    public function check()
    {
        if ($this->checkSuspended()) {
            throw new AuthException(sprintf(
                'Пользователь [%s] был заморожен. Попробуйте позже.', $this->user->getLogin()
            ));
        }

        return true;
    }
    
}