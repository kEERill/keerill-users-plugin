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
                    'label'   => 'keerill.users::lang.page.control_permissions.label',
                    'type'    => 'dropdown',
                    'options' => [
                            '0' => 'keerill.users::lang.page.control_permissions.options_all',
                            '1' => 'keerill.users::lang.page.control_permissions.options_redirect'
                        ],
                    'tab'     => 'keerill.users::lang.page.label',
                    'span'    => 'left',
                    'comment' => 'keerill.users::lang.page.control_permissions.comment',
                ],
                'settings[permission]' => [
                    'label'   => 'keerill.users::lang.page.permission.label',
                    'type'    => 'dropdown',
                    'default' => '0',
                    'options' => $permOptions,
                    'tab'     => 'keerill.users::lang.page.label',
                    'span'    => 'right',
                    'comment' => 'keerill.users::lang.page.permission.comment',
                ],
                'settings[use_user_auth]' => [
                    'label'   => 'keerill.users::lang.page.use_user_auth.label',
                    'type'    => 'switch',
                    'tab'     => 'keerill.users::lang.page.label',
                    'span'    => 'left',
                    'comment' => 'keerill.users::lang.page.use_user_auth.comment'
                ],
                'settings[page_redirect]' => [
                    'label'   => 'keerill.users::lang.page.page_redirect.label',
                    'type'    => 'dropdown',
                    'tab'     => 'keerill.users::lang.page.label',
                    'span'    => 'right',
                    'options' => $pageOptions,
                    'comment' => 'keerill.users::lang.page.page_redirect.comment',
                ],
                'settings[use_referer_param]' => [
                    'label'   => 'keerill.users::lang.page.use_referer_param.label',
                    'type'    => 'switch',
                    'tab'     => 'keerill.users::lang.page.label',
                    'span'    => 'left',
                    'comment' => 'keerill.users::lang.page.use_referer_param.comment'
                ],
                'settings[page_user_redirect]' => [
                    'label'   => 'keerill.users::lang.page.page_user_redirect.label',
                    'type'    => 'dropdown',
                    'tab'     => 'keerill.users::lang.page.label',
                    'span'    => 'right',
                    'options' => $pageOptions,
                    'comment' => 'keerill.users::lang.page.page_user_redirect.comment',
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
                $params = ($refererUse) ? ['referer' => sprintf('?referer=%s', urlencode($controller->pageUrl($page->baseFileName)))] : [];
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