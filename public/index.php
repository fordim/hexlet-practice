<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если вам интересно, то посмотрите DI Container
use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');

    if ($term == null) {
        $params = ['users' => $users];
    } else {
        foreach ($users as $user) {
            if (strpos ($user, $term) !== false) {
                $filterUsers[] = $user;
            }
        }
        $params = ['users' => $filterUsers];
    }

    return $this->get('renderer')->render($response, "users/users.phtml", $params);
});

$app->run();