<?php namespace KEERill\Users\Classes;

use App;
use View;
use Request;
use Backend;
use Response;
use Redirect;
use AuthManager;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use ApplicationException;
use Illuminate\Support\Str;

Class UserEventHandler
{

    /**
     * Page event, add new parametrs in page. Permission has denied.
     */
    private function pageSettings($widget)
    {
        if (!$widget->model instanceof Page) return;

        if (!($theme = Theme::getEditTheme())) {
            throw new ApplicationException(Lang::get('cms::lang.theme.edit.not_found'));
        }

        $pages = Page::all()->sort(function($a, $b) {
            return strcasecmp($a->title, $b->title);
        });

        $permOptions = $this->buildPermOptions(AuthManager::listPermissions());
        $pageOptions = $this->buildPageOptions($pages);

        $widget->addFields(
            [
                'settings[control_permissions]' => [
                    'label'   => 'Доступ к данной странице',
                    'type'    => 'dropdown',
                    'options' => [
                            '0' => 'Страница доступна всем',
                            '1' => 'Перенаправление на другую страницу'
                        ],
                    'tab'     => 'Настройка Прав',
                    'span'    => 'left',
                    'comment' => 'Если это опция включена, то доступ к странице будет ограничен',
                ],
                'settings[permission]' => [
                    'label'   => 'Права',
                    'type'    => 'dropdown',
                    'default' => '0',
                    'options' => $permOptions,
                    'tab'     => 'Настройка Прав',
                    'span'    => 'right',
                    'comment' => 'В случае, если недостаточно прав, будет перенаправление',
                ],
                'settings[use_user_auth]' => [
                    'label'   => 'Перенаправление авторизированного пользователя',
                    'type'    => 'switch',
                    'tab'     => 'Настройка Прав',
                    'span'    => 'left',
                    'comment' => 'Если это опция включена, то авторизированные пользователи будут перенаправлятся на другую страницу'
                ],
                'settings[page_redirect]' => [
                    'label'   => 'Страница перенаправления гостя',
                    'type'    => 'dropdown',
                    'tab'     => 'Настройка Прав',
                    'span'    => 'right',
                    'options' => $pageOptions,
                    'comment' => 'Перенаправление НЕ авторизированного пользователя',
                ],
                'settings[use_referer_param]' => [
                    'label'   => 'Параметр обратного перенаправления',
                    'type'    => 'switch',
                    'tab'     => 'Настройка Прав',
                    'span'    => 'left',
                    'comment' => 'В случае перенаправления, будет отправлятся параметр referer текущей страницы'
                ],
                'settings[page_user_redirect]' => [
                    'label'   => 'Страница перенаправления пользователя',
                    'type'    => 'dropdown',
                    'tab'     => 'Настройка Прав',
                    'span'    => 'right',
                    'options' => $pageOptions,
                    'comment' => 'Перенаправление авторизированного пользователя',
                ]
            ],
            'primary'
        );
    }

    private function buildPageOptions($pages)
    {
        $pageOptions = [];

        foreach($pages as $page) {
            $pageOptions[$page->baseFileName] = "{$page->title} ({$page->url})";
        }

        return $pageOptions;
    }

    private function buildPermOptions($permissions)
    {
        $permOptions = [];

        if(!$permissions) {
            return $permOptions;
        }

        foreach($permissions as $permission) {
            $permOptions[$permission->code] = "{$permission->label}";
        }

        return $permOptions;
    }

    /**
     * Осуществляет контроль прав на каждой странице. Нам ведь не нужны не прошенные пользователи на закрытые страницы?
     */
    private function pageView($controller, $url, $page)
    {
        if ($page instanceof Page) {
            if (!$settings = $page->settings) {
                return $page;
            }

            $mode = array_get($settings, 'control_permissions');
            $permission = array_get($settings, 'permission');
            $redirect = array_get($settings, 'page_redirect');

            if (!$mode || $mode == 0 || !$permission) {
                return $page;
            }

            $userUse = array_get($settings, 'use_user_auth');
            $userPage = array_get($settings, 'page_user_redirect');
            $user = ($userUse) ? AuthManager::getUser() : false;

            $refererUse = array_get($settings, 'use_referer_param');

            if(!AuthManager::hasAccess($permission)) {
                $params = ($refererUse) ? ['' => sprintf('?referer=%s', urlencode($controller->pageUrl($page->baseFileName)))] : [];
                if ($redirectUrl = $controller->pageUrl($user ? $userPage : $redirect, $params)) {
                    return Redirect::to($redirectUrl);                                                                            
                }
            }
        }

        return $page;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('backend.form.extendFields', function($widget) {
            $this->pageSettings($widget);
        });

        $events->listen('cms.page.beforeDisplay', function($controller, $url, $page) {
            return $this->pageView($controller, $url, $page);
        });
    }
}