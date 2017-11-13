# Users Plugin
This plugin adds a user system and the distribution of permissions between groups. Also you can write your own additions to this plugin.

## User permissions

To create your own rights, in the main file **Plugin.php** add a new method **registerUsersPermissions**

    //Create new permissions
    public function registerUsersPermissions()
    {
        return [
            'keerill.users.access' => [
                'label' => 'Label premission',
                'tab' => 'Other'
            ],
            'keerill.users.some_permission' => [
                'label' => 'Some Permission',
                'tab' => 'Some tab'
            ]
        ]
    }

Also for sorting, you can add to the array **`order`**, the lower, the higher this point will be

    //Create new permissions
    public function registerUsersPermissions()
    {
        return [
            'keerill.users.access' => [
                'label' => 'Label premission',
                'tab' => 'Other',
                'order' => '10'
            ]
        ]
    }

## Session component

To get an authenticated user, add this component to the layout/page. After adding this component, a variable will be avaliable on the page **{{ user }}**

### User variable

    {% if user %}
        <p>Hello {{ user.name }}</p>
    {% else %}
        <p>Nobody is logged in</p>
    {% endif %}

### Signing out

To end the user session, paste this code snippet into the page. 

    <a data-request="onLogout" data-request-data="redirect: '/good-bye'">Sign out</a>

## AuthManager facade

The facade **`AuthManager`**, that you can use for common tasks, it basically inherits the class **`KEERill\Users\Classes\AuthManager`** for functionality.

Use **`AuthManager::register`** to register a new user:

    $user = AuthManager::register([
        'name' => 'Some User',
        'email' => 'some@website.tld',
        'password' => 'changeme',
        'password_confirmation' => 'changeme',
    ]);

Use the second argument to automatically active the user:

    // Auto activate this user
    $user = AuthManager::register([...], true);

**`AuthManager::check`** the method checks whether the user is authenticated

    // Returns true if signed in.
    $loggedIn = AuthManager::check();

To get the model of an authenticated user, use **`AuthManager::getUser`** method.

    // Returns the signed in user
    $user = AuthManager::getUser();

You can authenticate a user by specifying a login and password using **`AuthManager::authenticate`**.

    // Authenticate user by credentials
    $user = AuthManager::authenticate([
        'login' => post('login'),
        'password' => post('password')
    ]);

The second argument is used to store the cookie for the user.

    $user = AuthManager::authenticate([...], true);

The method also uses 2 optional arguments, **`$login`** and **`$customMessage`**. 

**Login** used for the API **false**, the old session will not be reset and no new one will be created, only the user model **`KEERill\Users\Models\User`** well be created by standard authorization

**Custom Message** used for the API, this message will be insterted into the user's access log

You can also authenticate as a user simply by transferring the user model togeth **`AuthManager::login`**.

    // Sign in as a specific user
    AuthManager::login($user);

The second argument will remember you.

    // Sign in and remember the user
    AuthManager::login($user, true);

Use **`AuthManager::findUserByCredentials`** method, to search for the user.

    $user = AuthManager::findUserByCredentials(['name' => 'Username']);

## User Logs

In the plugin, you can enable logging of user activity. Using the add() method used in **`KEERill\Users\Models\Log`** you can add an entry to the log. As arguments, you need to transfer the user model, the message and the write code

    // Create new log
    Log::add($user, 'Message log', 'code');

## User Access Logs

In the plugin, you can enable logging access to the user account. Using the add() method used in **`KEERill\Users\Models\AccessLog`** you can add a log entry. As arguments, you need to transfer tge user model, message adn login status [true - if the input is successful, false - conversely]

    // Create new accesslog
    AccessLog::add($user, 'Auth success', true);

## User Events

 - #####**keerill.users.beforeAuthenticate** `$component, $credentials, $postDatas` and **keerill.users.authenticate** `$component, $user, $postDatas`: 
 it works when the user is authenticated on the site

 - #####**keerill.users.beforeRegister** `$component, $postDatas` and **keerill.users.register** `$component, $user, $postDatas`: 
 it works when a user registers on the site

 - #####**keerill.users.beforeSaveSettings** `$component, $user, $postDatas` and **keerill.users.afterSaveSettings** `$component, $user`: 
 it works when the user saves his settings

 - #####**keerill.users.logout** `$component, $user`: 
 it works when a user exits from his account

 - #####**keerill.users.activation** `$user`: 
 triggers when the user activates their account

 - #####**keerill.users.reset** `$user`:
 triggers when the user retrieves the password

 - #####**keerill.users.ban** `$user`: 
 it works when the user is blocked by the administration

 - #####**keerill.users.extendsComponents** `$plugin`: 
triggered when registration of plugin components takes place, is used to add their components to the plugin