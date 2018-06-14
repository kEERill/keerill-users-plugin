<?php namespace KEERill\Users\Classes;

use RainLab\Notify\Classes\EventBase;

class UserEventBase extends EventBase
{

    /**
     * @var integer Позиция модели пользователя в параметрах события
     */
    protected static $userPosition = 0;

    /**
     * @var array Local conditions supported by this event.
     */
    public $conditions = [
        \KEERill\Users\NotifyRules\UserAttributeCondition::class
    ];

    /**
     * Defines the usable parameters provided by this class.
     */
    public function defineParams()
    {
        return [
            'name' => [
                'title' => 'Name',
                'label' => 'Имя пользователя',
            ],
            'email' => [
                'title' => 'Email',
                'label' => "Электронная почта пользователя",
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function makeParamsFromEvent(array $args, $eventName = null)
    {
        $user = array_get($args, self::$userPosition);

        $params = $user->getNotificationVars();
        $params['user'] = $user;

        return $params;
    }
}