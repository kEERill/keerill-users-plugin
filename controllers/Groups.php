<?php namespace KEERill\Users\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Groups Back-end Controller
 */
class Groups extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['keerill.users.access_groups'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('KEERill.Users', 'users', 'groups');
    }

    public function formExtendFields($form)
    {
        /*
         * Add permissions tab
         */
        $form->addTabFields($this->generatePermissionsField());
    }

    /**
     * Adds the permissions editor widget to the form.
     * @return array
     */
    protected function generatePermissionsField()
    {
        return [
            'permissions' => [
                'tab' => 'backend::lang.user.permissions',
                'type' => 'KEERill\Users\FormWidgets\PermissionEditor',
                'mode' => 'checkbox'
            ]
        ];
    }

}
