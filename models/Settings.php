<?php namespace Keerill\Users\Models;

use Lang;
use Model;
use System\Models\MailTemplate;
use Keerill\Users\Models\Group;

/**
 * Settings Model
 */
class Settings extends Model
{
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
        $this->welcome_template = 'rainlab.user::mail.welcome';

        $this->group_banned = 1;
        $this->group_activated = 3;
        $this->group_guest = 2;
        $this->group_no_activated = 1;
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

    public function getWelcomeTemplateOptions()
    {
        $codes = array_keys(MailTemplate::listAllTemplates());
        $result = ['' => '- Не отправлять уведомление -'];
        $result += array_combine($codes, $codes);
        return $result;
    }
}
