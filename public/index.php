<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$repo = new App\SchoolRepository();
$router = $app->getRouteCollector()->getRouteParser();


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

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $flash = $this->get('flash')->getMessages();

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


$schools = [
    ['id' => 1, 'name' => 'first'],
    ['id' => 2, 'name' => 'second'],
    ['id' => 3, 'name' => 'three'],
    ['id' => 4, 'name' => 'four'],
    ['id' => 5, 'name' => 'five']
];

$app->get('/schools', function ($request, $response) use ($schools, $repo) {
    $flash = $this->get('flash')->getMessages();
    $params = [
        'schools' => $schools,
        'newSchools' => $repo->all(),
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

$app->post('/schools', function ($request, $response) use ($router, $repo) {
    $schoolData = $request->getParsedBodyParam('school');

    $validator = new App\Validator();
    // Проверяем корректность данных
    $errors = $validator->validate($schoolData);

    if (count($errors) === 0) {
        // Если данные корректны, то сохраняем, добавляем флеш и выполняем редирект
        $repo->save($schoolData);
        $file = 'schools.txt';
        $current = file_get_contents($file);
        $current .= json_encode($schoolData) . "\n";
        file_put_contents($file, $current);
        $this->get('flash')->addMessage('success', 'School has been created');
        // Обратите внимание на использование именованного роутинга
        $url = $router->urlFor('schools');
        return $response->withRedirect($url);
    }

    $params = [
        'schoolData' => $schoolData,
        'errors' => $errors
    ];

    // Если возникли ошибки, то устанавливаем код ответа в 422 и рендерим форму с указанием ошибок
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'schools/new.phtml', $params);
});

$app->get('/schools/{id}/edit', function ($request, $response, array $args) use ($repo) {
    $id = $args['id'];
    $school = $repo->find($id);
    $params = [
        'school' => $school,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'schools/edit.phtml', $params);
})->setName('editSchool');

$app->patch('/schools/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    $school = $repo->find($id);
    $data = $request->getParsedBodyParam('school');

    $validator = new App\Validator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        // Ручное копирование данных из формы в нашу сущность
        $school['name'] = $data['name'];
        $school['body'] = $data['body'];

        $this->get('flash')->addMessage('success', 'School has been updated');
        $repo->save($school);
        $url = $router->urlFor('schools', ['id' => $school['id']]);
        return $response->withRedirect($url);
    }

    $params = [
        'schoolData' => $data,
        'school' => $school,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'schools/edit.phtml', $params);
});

$app->run();