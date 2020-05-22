<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    $this->get('flash')->addMessage('success', 'Пользователь успешно создан');
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
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


$schools = [
    ['id' => 1, 'name' => 'first'],
    ['id' => 2, 'name' => 'second'],
    ['id' => 3, 'name' => 'three'],
    ['id' => 4, 'name' => 'four'],
    ['id' => 5, 'name' => 'five']
];

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $flash = $this->get('flash')->getMessages();
    // Валидатор можно сделать через class, и дальнейшие ошибки выводить через errors в сообщениях
    $params = [
        'user' => $user,
        'errors' => 'error',
        'flash' => $flash
    ];
    $file = 'users.txt';
    $current = file_get_contents($file);
    $current .= json_encode($user) . "\n";
    file_put_contents($file, $current);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});


$app->get('/schools', function ($request, $response) use ($schools) {
    $flash = $this->get('flash')->getMessages();
    $params = [
        'schools' => $schools,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, "schools/index.phtml", $params);
})->setName('schools');

$app->get('/schools/new', function ($request, $response) {
    $params = [
        'schoolData' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'schools/new.phtml', $params);
})->setName('newSchool');

$app->get('/schools/{id}', function ($request, $response, array $args) use ($schools) {
    $id = $args['id'];
    foreach ($schools as $number) {
        if (array_search($id, $number)) {
            $school = $number;
        };
    }

    $params = [
        'school' => $school
    ];

    if (!$school) {
        return $response->write('Page not found')
            ->withStatus(404);
    }

    return $this->get('renderer')->render($response, 'school/show.phtml', $params);
})->setName('school');


$router = $app->getRouteCollector()->getRouteParser();

$app->post('/schools', function ($request, $response) use ($router) {
    $schoolData = $request->getParsedBodyParam('school');

    $file = 'schools.txt';
    $current = file_get_contents($file);
    $current .= json_encode($schoolData) . "\n";
    file_put_contents($file, $current);

    $this->get('flash')->addMessage('success', 'School has been created');
    // Обратите внимание на использование именованного роутинга
    $url = $router->urlFor('schools');
    return $response->withRedirect($url);
});

$app->run();