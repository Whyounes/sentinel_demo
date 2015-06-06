<?php
//Cartalyst\Sentry\Throttling\Eloquent\Throttle::setAttemptLimit(19);
$app->get('/admin/users', function () use ($app) {
    if (!$app->container->sentry->check()) {
        echo 'You need to be logged in to access this page.';

        return;
    }

    $loggedUser = $app->container->sentry->getUser();
    if (!$loggedUser->hasPermission('user.*')) {
        echo "You don't have the permission to access this page.";

        return;
    }

    echo 'Welcome to the admin page.';
});

$app->get('/logout', function () use ($app) {
    $app->container->sentry->logout();
    echo 'Logged out successfuly.';
});

$app->get('/login', function () use ($app) {
	//dump($app->container->sentry->findThrottlerByUserLogin('younes.rafie@gmail.com')->unsuspend());
    $app->twig->display('login.html.twig');
});

$app->post('/login', function () use ($app) {
    $data = $app->request->post();
    $remember = isset($data['remember']) && $data['remember'] == 'on' ? true : false;

    try {
        $app->container->sentry->authenticate([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $remember);
    } catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
    	$user = $app->container->sentry->findThrottlerByUserLogin($data['email']);
    	    	
        echo 'Invalid email or password.';

        return;
    } catch (\Cartalyst\Sentry\Users\UserNotActivatedException $e) {
        echo 'You need to activate your account before logging in. Please check your inbox.';

        return;
    }

    echo 'You are logged in';
    // getLoginAttempts
    // setSuspensionTime
});

$app->get('/', function () use ($app) {
    // $sentry = \Cartalyst\Sentry\Facades\Native\Sentry::createSentry();
    // dump($sentry->findUserById(2)->getActivationCode());
    // die();
    $app->twig->display('home.html.twig');
});

$app->post('/', function () use ($app) {
    // we leave validation for another time
    $data = $app->request->post();
    $sentry = $app->container->sentry;

    $group = $sentry->findGroupByName('User');
	$user = $sentry->createUser([
	    'first_name' => $data['firstname'],
	    'last_name' => $data['lastname'],
	    'email' => $data['email'],
	    'password' => $data['password'],
	    'permissions' => [
	        'user.delete' => 0,
	    ],
	]);
    $user->addGroup($group);
    $user->save();
    //mail($data['email'], "Activate your account", "Click on the link below \n <a href='http://vaprobash.dev/user/activate/{$user->getActivationCode()}'>Activate your account</a>");
    echo 'Please check your email to complete your account registration.';
});

$app->get('/user/activate/:code', function ($code) use ($app) {
    try {
        $user = $app->container->sentry->findUserByActivationCode($code);

        if ($user->isActivated()) {
            echo 'User is already activated. Try to log in.';

            return;
        }

        $user->attemptActivation($code);
        echo 'Your account has been activated. Log in to your account.';
    } catch (\Cartalyst\Sentry\Users\UserNotFoundException $ex) {
        echo 'Invalid activation code';
    }
});
