<?php namespace KEERill\Users\Models;

use Lang;
use Model;

/**
 * Group Model
 */
class Group extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'oc_users_groups';

    /**
     * Validation rules
     */
    public $rules = [
        'name' => 'required|between:3,64',
        'code' => 'required|regex:/^[a-zA-Z0-9_\-]+$/|unique:oc_users_groups',
    ];

    public $attributeNames = [
        'name' => 'keerill.users::lang.group.name',
        'code' => 'keerill.users::lang.group.code'
    ];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [        
        'code',
        'name'
    ];

    /**
     * @var array Relations
     */
    public $hasMany  = [
        'users'       => [
            'KEERill\Users\Models\User', 
            'table' => 'oc_users'
        ]
    ];

    public $jsonable = ['permissions'];

}
