<?php namespace Keerill\Users\Controllers;

use Flash;
use BackendMenu;
use Backend\Classes\Controller;
use Illuminate\Support\Facades\Request;

/**
 * Users Back-end Controller
 */
class Users extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['october.users.access_users'];
    
    public $bodyClass = 'compact-container';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.Users', 'users', 'users');
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if($record->isBanned()) {
            return 'negative';
        }
        if (!$record->is_activated) {
            return 'new';
        }
    }

    public function formBeforeCreate($model)
    {
        $model->ip_address = Request::ip();
    }
    
    public function preview_onActivate($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->attemptActivation($model->activation_code);
        
        Flash::success('Пользователь успешно активирован');

        if ($redirect = $this->makeRedirect('update-close', $model)) {
            return $redirect;
        }
    }

    public function preview_onBan($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->ban();

        Flash::success("Пользователь успешно заблокирован");

        if ($redirect = $this->makeRedirect('update-close', $model)) {
            return $redirect;
        }
    }

    public function preview_onUnBan($recordId = null)
    {
        $model = $this->formFindModelObject($recordId);
        $model->unban();

        Flash::success("Пользователь успешно заблокирован");

        if ($redirect = $this->makeRedirect('update-close', $model)) {
            return $redirect;
        }
    }

}
