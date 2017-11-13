<?php return [
    'plugin' => [
        'name' => 'Users',
        'description' => 'Managing users and their permissions'
    ],
    'users' => [
        'label' => 'Users',
        'sideMenu' => 'Manage Users',
        'create_user' => 'Create a new user',
        'create_title' => 'Creating a new user',
        'update_title' => 'Update user',
        'preview_title' => 'Preview user',
        'confirm_delete' => 'Are you sure you want to delete this user?',
        'edit' => 'Edit',
        'block' => 'Lock Management',
        'user_no_activated' => 'If you enable the activation of the account to log in, the user will not be able to log in.',
        'activate'=> 'Activate this user manually.',
        'confirm_activate' => 'Are you sure you want to activate this user?',
        'joined' => 'Joined',
        'status' => 'Status',
        'is_no_activated' => 'Waiting for activation',
        'never' => 'Never'
    ],
    'user' => [
        'label' => 'User',
        'label_name' => 'User :name',
        'name' => 'Username',
        'email' => 'Email',
        'email_desc' => 'Used to send activation email or password recovery account',
        'group' => 'Group',
        'ip_address' => 'IP address',
        'password' => 'Password',
        'password_desc' => 'Enter the password that will be used to sign in',
        'password_update' => 'Reset password',
        'password_update_desc' => 'Enter the password that will be used after saving.',
        'confirm_password' => 'Confirm password',
        'confirm_password_desc' => 'Enter the password again to confirm',
        'avatar' => 'User avatar',
        'last_seen' => 'Last seen',
        'created_at' => 'Date of registration',
        'is_activated' => 'Activated',
        'is_banned' => 'The user is blocked',
        'is_banned_desc' => 'If you check the box, the user will be blocked',
        'is_banned_reason' => 'Reason for blocking',
        'is_banned_reason_desc' => 'This cause of blocking will be displayed to everyone, even a blocked user',
        'tab_account' => 'Account',
        'banned_log' => 'The user has been blocked for a reason: :reason',
        'unbanned_log' => 'The user was unblocked for: :reason',
        'ban_no_reason' => 'Reason not specified',
        'revision' => [
            'label' => 'Changes',
            'field' => 'Field name',
            'old_value' => 'Old value',
            'new_value' => 'New value'
        ]
    ],
    'groups' => [
        'label' => 'Groups',
        'sideMenu' => 'Manage Groups',
        'create_group' => 'Create a new group',
        'create_title' => 'Creating a new group',
        'update_title' => 'Update group'
    ],
    'group' => [
        'label' => 'Group',
        'name' => 'Group name',
        'code' => 'Group code'
    ],
    'settings' => [
        'users' => 'User settings',
        'users_desc' => 'Manage user settings',
        'accessLogs' => 'User Access Log',
        'accessLogs_desc' => 'View all attempts to authenticate a user',
        'usersLogs' => 'User activity log',
        'usersLogs_desc' => 'View log with active user actions',
        'component_name' => 'Settings',
        'component_desc' => 'Displays the form for editing user settings',
        'redirect' => 'Redirection',
        'redirect_desc' => 'Redirecting to the page after saving the settings',
        'save_settings' => 'Settings have been changed successfully',
        'no_perm_save' => 'Change settings',
        'use_logs' => 'Save all possible user actions',
        'use_logs_desc' => 'If the option is enabled, all actions committed by the user will be stored in the database',
        'use_access_logs' => 'Keep all user authentication attempts',
        'use_access_logs_desc' => 'If this option is enabled, after every attempt to authenticate to the database, a record will be added',
        'del_oldAccessLogs_days' => 'Number of days to delete old access logs',
        'del_oldAccessLogs_days_desc' => 'The number of days that the system will read the records to be old and delete them from the database. Enter 0 to disable the function',
        'allow_groups' => 'Prevent authorization',
        'allow_groups_desc' => 'Groups that do not have the ability to log in to the site',
        'use_throttle' => 'Tracking failed authorization attempts',
        'use_throttle_desc' => 'With multiple failed attempts at authorization, the user will be frozen',
        'block_persistence' => 'Disable concurrent sessions',
        'block_persistence_desc' => 'When activated users can not enter multiple devices at the same time',
        'section_activation' => 'Account activation',
        'section_activation_desc' => 'Settings activation of new users',
        'require_activation' => 'Required Account Activation',
        'require_activation_desc' => 'Users must have an activated account to sign in',
        'activate_mode' => 'Activation',
        'activate_mode_desc' => 'User Activation',
        'del_noActUsers_days' => 'Number of days to delete users',
        'del_noActUsers_days_desc' => 'Number of days to activate the user, otherwise the user will be deleted. Enter 0 to disable deletion',
        'section_registration' => 'Registration',
        'section_registration_desc' => 'Settings new user registration',
        'allow_registration' => 'Enable registration',
        'allow_registration_desc' => 'If this option is disabled, only administrators will be able to register users',
        'group_banned' => 'Blocked user group',
        'group_banned_desc' => 'The group is assigned when the user is locked',
        'group_activated' => 'Activated user group',
        'group_activated_desc' => 'The group is assigned when the user activated their account',
        'group_guest' => 'Unauthorized user group',
        'group_guest_desc' => 'If the user is not authorized on the site, then by default the rights will be inherited from this group',
        'group_no_activated' => 'Unactivated user group',
        'group_no_activated_desc' => 'The group is assigned when the user has not activated their account',
        'activate_auto' => 'Automatic',
        'activate_auto_desc' => 'Automatic activation at registration',
        'activate_user' => 'Standard',
        'activate_user_desc' => 'Activating via email',
        'activate_admin' => 'Manual',
        'activate_admin_desc' => 'Only the administrator can activate the user',
        'tabs' => [
            'system' => 'System',
            'user' => 'User',
            'groups' => 'Settings groups'
        ]
    ],
    'logs' => [
        'menu_label' => 'Active actions',
        'message' => 'Message',
        'code' => 'Code',
        'label' => 'Log',
        'created_at' => 'Date&Time'
    ],
    'accessLogs' => [
        'menu_label' => 'Access',
        'status' => 'Status'
    ],
    'permissions' => [
        'users' => 'User management',
        'groups' => 'Manage Groups',
        'settings' => 'Manage user settings',
        'accessLogs' => 'Access to viewing the users access log',
        'usersLogs' => 'Access to viewing the log of the users active actions',
        'frontend' => [
            'accessSite' => 'Access to the page view of the site',
            'settings' => 'Ability to change your settings'
        ]
    ],
    'messages' => [
        'user_no_activate' => 'Unable to authenticate under ": name" because it is not verified',
        'user_credentials_invalid' => 'Login or password, entered incorrectly!',
        'user_is_activated' => 'User already verified',
        'user_no_activated' => 'User already verified',
        'user_activation_success' => 'User successfully activated',
        'user_not_found' => 'User with this data not found',
        'user_cannot_auth' => 'The authorization was limited to the administration of the site',
        'user_auth' => 'Authorization on the site: :status',
        'user_auth_success' => 'Login Successful',
        'user_send_mail' => 'A letter with further instructions sent to your mail',
        'user_not_perm' => 'Not enough permissions to perform this operation',
        'user_not_perm_with' => 'Not enough permisions for :operation'
    ],
    'page' => [
        'label' => 'Settings access',
        'control_permissions' => [
            'label' => 'Page restriction mode',
            'comment' => 'The action that will be applied to the user if the user has not enough permissions to the page',
            'options_all' => 'Visible to everyone',
            'options_redirect' => 'Redirecting to another page'
        ],
        'permission' => [
            'label' => 'Permission to access the page',
            'comment' => 'The user who has this pretmission has access to the viewing page'
        ],
        'use_user_auth' => [
            'label' => 'Redirecting an authorized user',
            'comment' => 'If this option is enabled, authorized users will be redirected to a special page'
        ],
        'page_redirect' => [
            'label' => 'Redirect page',
            'comment' => 'If the option "Redirecting an authorized user" is turned off, then all users will be redirected to this page'
        ],
        'page_user_redirect' => [
            'label' => 'Authorized user redirect page',
            'comment' => 'If the option "Redirecting an authorized user" is enabled, only authorized users will be redirected to this page'
        ],
        'use_referer_param' => [
            'label' => 'Reverse redirection parameter',
            'comment' => 'In case of redirection, the paramete referer with the URL of the current page'
        ]
    ],
    'activity' => [
        'component_name' => 'Active actions',
        'component_desc' => 'Displays a list of active user actions',
        'count' => 'Number of lines per page'
    ],
    'auth' => [
        'component_name' => 'Authentication',
        'component_desc' => 'Displays the user authentication form',
        'redirect' => 'Redirection',
        'redirect_desc' => 'User redirect after successful user authentication'
    ],
    'log' => [
        'component_name' => 'Access log',
        'component_desc' => 'Displays a list of all user login attempts',
        'count' => 'Number of lines per page',
        'created_at' => 'Data & Time'
    ],
    'register' => [
        'component_name' => 'Registration',
        'component_desc' => 'Displays the user registration form',
        'code' => 'Code parameter',
        'code_desc' => 'The name of the parameter in which the account activation code is sent',
        'redirect' => 'Redirection',
        'redirect_desc' => 'User redirect after successful user registration',
        'register_disable' => 'Registration was disabled by the site administration',
        'is_user' => 'You are already authorized and can not register',
        'activation_invalid_code' => 'Invalid activation code or user is already verified'
    ],
    'reset' => [
        'component_name' => 'Reset password',
        'component_desc' => 'Displays the password recovery form',
        'code' => 'Code parameter',
        'code_desc' => 'The name of the parameter in which the account password recovery code is sent',
        'redirect' => 'Redirection',
        'redirect_desc' => 'Redirecting to the page after successful password recovery',
        'reset_code_invalid' => 'Invalid activation code, please check the code',
        'reset_success' => 'User password recovery was successful'
    ],
    'session' => [
        'component_name' => 'Session of the user',
        'component_desc' => 'Specifies the user by cookie and adds a user variable to the page',
        'page_register' => 'Registration page',
        'page_register_desc' => 'Need to create an activation link in a letter',
        'page_not_found' => 'Registration page not selected'
    ],
    'mail' => [
        'activate' => 'A letter with instructions for activating a new user account',
        'restore' => 'A letter with instructions for password recovery'
    ]
];