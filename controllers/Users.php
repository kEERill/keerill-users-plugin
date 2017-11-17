<?php namespace KEERill\Users\Controllers;

use Lang;
use Flash;
use Exception;
use BackendMenu;
use Backend\Classes\Controller;
use KEERill\Users\Models\Settings as UserSettings;

/**
 * Users Back-end Controller
 */
class Users extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.RelationController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public $requiredPermissions = ['keerill.users.access_users'];
    
    public $bodyClass = 'compact-container';

    protected $bannedFormWidget;

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('KEERill.Users', 'users', 'users');

        $this->createFormBanned();
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if($record->is_banned) {
            return 'negative';
        }
        if (!$record->is_activated) {
            return 'new';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function formExtendFields($widget)
    {
        if (UserSettings::get('use_logs')) {
            $widget->addTabFields([
                'logs' => [
                    'tab' => 'keerill.users::lang.logs.menu_label',
                    'type' => 'partial',
                    'path' => 'field_logs',
                    'context' => [
                        'preview'
                    ]
                ]
            ]);
        }

        if (UserSettings::get('use_access_logs')) {
            $widget->addTabFields([
                'accesslogs' => [
                    'tab' => 'keerill.users::lang.accessLogs.menu_label',
                    'type' => 'partial',
                    'path' => 'field_accesslogs',
                    'context' => [
                        'preview'
                    ]
                ]
            ]);
        }
    }
    
    public function preview_onActivate($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->attemptActivation($model->activation_code);
        
        Flash::success(Lang::get('keerill.users::lang.messages.user_activation_success'));

        if ($redirect = $this->makeRedirect('preview', $model)) {
            return $redirect;
        }
    }

    public function preview_onLoadFormBanned($recordId = null) 
    {
        return $this->makePartial('popup_form', [
            'widget' => $this->bannedFormWidget,
            'options' => [
                'title' => Lang::get('keerill.users::lang.users.block'),
                'request' => 'onBan',
                'form_btn' => Lang::get('backend::lang.form.save')
            ]
        ]);
    }

    public function preview_onBan($recordId = null)
    {
        $data = $this->bannedFormWidget->getSaveData();
        $model = $this->formFindModelObject($recordId);
        $model->ban($data, $this->bannedFormWidget->getSessionKey());

        Flash::success(Lang::get('backend::lang.form.update_success', [
            'name' => Lang::get('keerill.users::lang.user.label')
        ]));

        if ($redirect = $this->makeRedirect('preview', $model)) {
            return $redirect;
        }
    }

    protected function createFormBanned()
    {
        if ($this->bannedFormWidget) {
            return $this->bannedFormWidget;
        }

        if (!$this->params || !$this->params[0]) {
            return null;
        }

        if (!$model = \KEERill\Users\Models\User::find($this->params[0])) {
            return null;
        }

        $config = $this->makeConfig('$/keerill/users/models/user/fields_banned.yaml');
        $config->model = $model;
        $config->context = 'create';

        $config->alias = 'banForm';
        $config->arrayName = 'Ban';

        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->bindToController();

        return $this->bannedFormWidget = $widget;
    }
}
