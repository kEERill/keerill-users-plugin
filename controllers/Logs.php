<?php namespace KEERill\Users\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;

/**
 * User Logs Back-end Controller
 */
class Logs extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController'
    ];

    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('KEERill.Users', 'logs');    
    }
}
