<?php namespace KEERill\Users\NotifyRules;

use ApplicationException;
use RainLab\Notify\Classes\ModelAttributesConditionBase;

class UserAttributeCondition extends ModelAttributesConditionBase
{
    protected $modelClass = \KEERill\Users\Models\User::class;

    public function getGroupingTitle()
    {
        return 'Users';
    }

    public function getTitle()
    {
        return 'Users';
    }

    /**
     * Checks whether the condition is TRUE for specified parameters
     * @param array $params Specifies a list of parameters as an associative array.
     * @return bool
     */
    public function isTrue(&$params)
    {
        $hostObj = $this->host;
        $attribute = $hostObj->subcondition;

        if (!$user = array_get($params, 'user')) {
            throw new ApplicationException('Error evaluating the user attribute condition: the user object is not found in the condition parameters.');
        }

        return parent::evalIsTrue($user);
    }
}