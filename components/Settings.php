<?php namespace KEERill\Users\Components;

use Event;
use Flash;
use Request;
use Redirect;
use Validator;
use AuthManager;
use Cms\Classes\Page;
use ValidationException;
use ApplicationException;
use KEERill\Users\Models\Log;
use Cms\Classes\ComponentBase;

class Settings extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'keerill.users::lang.settings.component_name',
            'description' => 'keerill.users::lang.settings.component_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title' => 'keerill.users::lang.settings.redirect',
                'description' => 'keerill.users::lang.settings.redirect_desc',
                'type' => 'dropdown',
                'default' => ''
            ]
        ];
    }

    public function getRedirectOptions() 
    {
        return ['' => ' - none - '] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Получение экземпляра пользователя
     * @return \kEERill\Users\Models\User
     */
    protected function user()
    {
        if (!$user = AuthManager::getUser()) {
            return null;
        }

        return $user;
    }

    /**
     * Save User Settings
     * @return redirect
     */
    public function onSaveSettings()
    {
        try {
            if (!$user = $this->user()) {
                throw new ApplicationException(Lang::get('keerill.users::lang.messages.user_not_found'));
            }

            if (!AuthManager::hasAccess('keerill.users.settings')) {
                throw new ApplicationException(
                    Lang::get('keerill.users::lang.messages.user_not_perm_with', [
                        'operation' => Lang::get('keerill.users::lang.settings.not_perm_save')
                    ])
                );
            }

            $data = post();

            Event::fire('keerill.users.beforeSaveSettings', [$this, $user, $data]);

            $settingsFields = $user->getSettingsFields();
            $settingsRules = $user->getSettingsRules();

            $validation = Validator::make($data, $settingsRules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            foreach ($settingsFields as $fieldName) {
                if ($value = array_get($data, $fieldName)) {
                    $user->{$fieldName} = $value;
                }
            }

            Event::fire('keerill.users.afterSaveSettings', [$this, $user]);

            $user->save();

            Log::add($user, Lang::get('keerill.users::lang.settings.save_settings'), 'user_edit');

            Flash::success(Lang::get('keerill.users::lang.settings.save_settings'));

            $redirectUrl = $this->pageUrl($this->property('redirect'));
            
            if ($redirectUrl = post('redirect', $redirectUrl)) {
                return Redirect::to($redirectUrl);
            }
        } catch (\Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
            return Redirect::refresh();
        }
    }
}
