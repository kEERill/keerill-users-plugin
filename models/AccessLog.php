<?php namespace KEERill\Users\Models;

use Model;
use Request;
use KEERill\Users\Models\Settings as UserSettings;

/**
 * AuthLog Model
 */
class AccessLog extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'oc_users_access_logs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['user_id', 'message', 'is_success'];

    /**
     * @var array Relations
     */

    public $belongsTo = [
        'user' => 'KEERill\Users\Models\User'
    ];

    /**
     * Создание новой записи о входе в систему
     * 
     * @param KEERill\Users\Models\User $user
     * @param string $message
     * @param boolean $success
     * 
     * @return self
     */
    public static function add($user, $message, $success = false) 
    {
        if (!UserSettings::get('use_access_logs')) {
            return false;
        }

        $record = new static;
        
		$record->user = $user;
        $record->ip_address = Request::getClientIp();
        $record->message = $message;
        $record->is_success = $success;

        $record->save();

		return $record;
    }
}
