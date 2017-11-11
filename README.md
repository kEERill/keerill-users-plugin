# User Plugin
Плагин добавляет Вам на сайт систему пользователей, которыми в дальнейшем Вы сможете управлять. Так же Вы сможете написать свои дополнения к этому плагину.

## User permissions

Для создания своих прав, в главном файле **Plugin.php** добавьте новый метод **registerUsersPermissions**

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

Также для сортировки можно добавить в массив **`order`**, чем ниже, тем выше будет сортироваться

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

Для получения аутентифицированного пользователя, добавьте этот компонент в layout/page. После того, как добавите этот компонент, на странице будет доступна переменная **{{ user }}**

### User variable

    {% if user %}
        <p>Hello {{ user.name }}</p>
    {% else %}
        <p>Nobody is logged in</p>
    {% endif %}

### Signing out

Для завершения сессии пользователя вставьте этот фрагмент кода на страницу. 

    <a data-request="onLogout" data-request-data="redirect: '/good-bye'">Sign out</a>

## AuthManager facade

Фасад **`AuthManager`**, который вы можете использовать для обычных задач, он в основном наследует класс **`KEERill\Users\Classes\AuthManager`** для функциональности.

Используйте **`AuthManager::register`** для регистрации нового пользователя:

    $user = AuthManager::register([
        'name' => 'Some User',
        'email' => 'some@website.tld',
        'password' => 'changeme',
        'password_confirmation' => 'changeme',
    ]);

Используйте второй аргумент для автоматической активации пользователя:

    // Auto activate this user
    $user = AuthManager::register([...], true);

**`AuthManager::check`** метод проверяет, аутенфитирован ли пользователь

    // Returns true if signed in.
    $loggedIn = AuthManager::check();

Для получения модели аутентифицированного пользователя, используйте **`AuthManager::getUser`** метод.

    // Returns the signed in user
    $user = AuthManager::getUser();

Вы можете аутентифицировать пользователя, указав логин и пароль с помощью **`AuthManager::authenticate`**.

    // Authenticate user by credentials
    $user = AuthManager::authenticate([
        'login' => post('login'),
        'password' => post('password')
    ]);

Второй аргумент используется для хранения файла cookie для пользователя.

    $user = AuthManager::authenticate([...], true);

Вы также можете аутентифицироваться как пользователь, просто передав модель пользователя вместе с **`AuthManager::login`**.

    // Sign in as a specific user
    AuthManager::login($user);

Второй аргумент запомнит вас.

    // Sign in and remember the user
    AuthManager::login($user, true);

Используйте **`AuthManager::findUserByCredentials`** метод, для поиска пользователя.

    $user = AuthManager::findUserByCredentials(['name' => 'Username']);

## Transactions
Для управляния балансом пользователя, в плагине используются транзакции. Для изменения баланса пользователя, просто создайте новую транзакции с суммой, которой Вы хотите пополнить или же вычесть с баланса пользователя. 

### Transactions operations

 1. Сложение

 2. Вычитание

 3. Присвоение

Для пополнения счёта пользователя создайте транзакцию с операцией "**1**" с помощью **`KEERill\Users\Model\Transaction`** класса

    // Create new transaction 
    $transaction = Transaction::create([
        'user_id' => '1',
        'description' => 'Sell new computer',
        'operation' => '1',
        'pay' => 100
    ]);

или

    $transaction = $user->transactions()->create([
        'description' => 'Sell new computer',
        'operation' => '1',
        'pay' => 100
    ]);

Для проверки того, что баланс успешно пополнен используйте метод `isValid()`

    // Check valid transaction
    if ($transaction->isValid()) {
        echo 'This transaction valid';
    }

Для получения транзакций пользователя используйте компонент **Transaction Component**

## User Logs

В плагине можно включить ведение журнала о активности пользователя. С помощью метода add() используемый в **`KEERill\Users\Models\Log`** можно добавить запись в журнал. В качестве аргументов нужно передать модель пользователя, сообщение и код записи

    // Create new log
    Log::add($user, 'Message log', 'code');

## User Access Logs

В плагине можно включить ведение журнала доступа к учетной записи пользователя. С помощью метода add() используемый в **`KEERill\Users\Models\AccessLog`** можно добавить запись в журнал. В качестве аргументов нужно передать модель пользователя, сообщение и статус входа [true - если вход выполнен успешно, false - наоборот]

    // Create new accesslog
    AccessLog::add($user, 'Auth success', true);

## User Events

 - #####**keerill.users.beforeAuthenticate** `$component, $credentials, $postDatas` and **keerill.users.authenticate** `$component, $user, $postDatas`: 
 срабатывает, когда пользователь проходит аунтефикацию на сайте

 - #####**keerill.users.beforeRegister** `$component, $postDatas` and **keerill.users.register** `$component, $user, $postDatas`: 
 срабатывает, когда пользователь проходит регистрацию на сайте

 - #####**keerill.users.beforeSaveSettings** `$component, $user, $postDatas` and **keerill.users.afterSaveSettings** `$component, $user`: 
 срабатывает, когда пользователь сохраняет свои настройки

 - #####**keerill.users.logout** `$component, $user`: 
 срабатывает, когда пользователь выходит из своего аккаунта

 - #####**keerill.users.activation** `$user`: 
 срабатывает, когда пользователь проходит активацию своего аккаунта

 - #####**keerill.users.reset** `$user`:
 срабатывает, когда пользователь восстановил пароль

 - #####**keerill.users.ban** `$user`: 
 срабатывает, когда пользователя блокирует администрация

 - #####**keerill.users.pay** `$user, $transaction`: 
 срабатывает, когда пользователь пополняет счёт

 - #####**keerill.users.extendsComponents** `$plugin`: 
 срабатывает, когда происходит регистрация компонентов плагина, используется для добавление своих компонентов в плагин