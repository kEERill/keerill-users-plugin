<?php namespace KEERill\Users\Models;

use Lang;
use Model;
use System\Models\MailTemplate;
use KEERill\Users\Models\Group;

/**
 * Settings Model
 */
class Settings extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Validation rules
     */
    public $rules = [
        'del_oldAccessLogs_days' => 'integer|min:0',
        'del_noActUsers_days' => 'integer|min:0'
    ];

    /**
     * @var array The array of custom attribute names.
     */
    public $attributeNames = [
        'del_oldAccessLogs_days' => 'Количество дней на удаление старых логов доступа',
        'del_noActUsers_days' => 'Количество дней на удаление неактивированных пользователей'
    ];

    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'oc_users_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    const ACTIVATE_AUTO = 'auto';
    const ACTIVATE_USER = 'user';
    const ACTIVATE_ADMIN = 'admin';

    public function initSettingsData()
    {
        $this->require_activation = true;
        $this->activate_mode = self::ACTIVATE_AUTO;
        $this->use_throttle = true;
        $this->block_persistence = false;

        $this->allow_registration = true;

        $this->use_logs = false;
        $this->use_access_logs = false;
    }

    public function getActivateModeOptions()
    {
        return [
            self::ACTIVATE_AUTO => [
                'Автоматическая',
                'Автоматическая активация при регистрации.'
            ],
            self::ACTIVATE_USER => [
                'Стандартная',
                'Активация при помощи электронной почты.'
            ],
            self::ACTIVATE_ADMIN => [
                'Ручная',
                'Только администратор может активировать пользователя.'
            ]
        ];
    }

    public function getGroups()
    {
        return Group::lists('name', 'id');
    }

    public function getActivateModeAttribute($value)
    {
        if (!$value) {
            return self::ACTIVATE_AUTO;
        }
        return $value;
    }

    /**
     * Фильтруем навигацию, убираем не нужные элементы из навигации
     * 
     * @param $manager
     * @return void
     */
    public static function filterSettingItems($manager)
    {
        if (!self::get('use_logs')) {
            $manager->removeSettingItem('KEERill.Users', 'logs');
        }

        if (!self::get('use_access_logs')) {
            $manager->removeSettingItem('KEERill.Users', 'accesslogs');
        }
    }
}
