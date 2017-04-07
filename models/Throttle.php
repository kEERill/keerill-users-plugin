<?php namespace Keerill\Users\Models;

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
        'user' => 'Keerill\Users\Models\User'
    ];
    
}