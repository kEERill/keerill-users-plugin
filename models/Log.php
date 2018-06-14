<?php namespace KEERill\Users\Models;

use Model;
use Request;
use KEERill\Users\Models\Settings as UserSettings;

/**
 * UsersActivity Model
 */
class Log extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'oc_users_logs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['user_id', 'message', 'data', 'code'];

    /**
     * @var array Datas fields
     */
    protected $dates = ['updated_at', 'created_at'];

    /**
     * @var array Jsonable fields
     */
    protected $jsonable = ['data'];
    
    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => 'kEERill\Users\Models\User'
    ];

    public function beforeCreate()
    {
        $this->ip_address = Request::ip();
    }

     /**
     * Создание новой записи о активности пользователя
     * 
     * @param KEERill\Users\Models\User $user
     * @param string $message
     * @param string $code
     * @param array $data
     * 
     * @return self
     */
    public static function add($user, $message = '', $code = '', array $data = []) 
    {
        if (!UserSettings::get('use_logs')) {
            return false;
        }

        $record = new static;
        
		$record->user = $user;
        $record->ip_address = Request::getClientIp();
        $record->message = $message;
        $record->code = $code;
        $record->data = $data;

        $record->save();

		return $record;
    }
}
