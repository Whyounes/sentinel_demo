<?php

$app->get('/admin/users', function () use ($app) {
    $loggedUser = $app->container->sentinel->check();

    if (!$loggedUser) {
        echo 'You need to be logged in to access this page.';

        return;
    }

    if (!$loggedUser->hasAccess('user.*')) {
        echo "You don't have the permission to access this page.";

        return;
    }

    echo 'Welcome to the admin page.';
});

$app->get('/logout', function () use ($app) {
    $app->container->sentinel->logout();

    echo 'Logged out successfuly.';
});

$app->get('/login', function () use ($app) {
    $app->twig->display('login.html.twig');
});

$app->post('/login', function () use ($app) {
    $data = $app->request->post();
    $remember = isset($data['remember']) && $data['remember'] == 'on' ? true : false;
    
    try {
        if (!$app->container->sentinel->authenticate([
                'email' => $data['email'],
                'password' => $data['password'],
            ], $remember)) {

            echo 'Invalid email or password.';

            return;
        } else {
            echo 'You\'re logged in';

            return;
        }
    } catch (Cartalyst\Sentinel\Checkpoints\ThrottlingException $ex) {
        echo "Too many attempts!";

        return;
    } catch (Cartalyst\Sentinel\Checkpoints\NotActivatedException $ex){
        echo "Please activate your account before trying to log in";
        
        return;
    }
});

$app->get('/', function () use ($app) {
    $app->twig->display('home.html.twig');
});

$app->post('/', function () use ($app) {
    // we leave validation for another time
    $data = $app->request->post();

    $role = $app->container->sentinel->findRoleByName('Admin');

    if ($app->container->sentinel->findByCredentials([
        'login' => $data['email'],
    ])) {
        echo 'User already exists with this email.';

        return;
    }

    $user = $app->container->sentinel->create([
        'first_name' => $data['firstname'],
        'last_name' => $data['lastname'],
        'email' => $data['email'],
        'password' => $data['password'],
        'permissions' => [
            'user.delete' => false,
        ],
    ]);

    // attach the user to the admin role
    $role->users()->attach($user);

    // create a new activation for the registered user
    $activation = (new Cartalyst\Sentinel\Activations\IlluminateActivationRepository)->create($user);

    //mail($data['email'], "Activate your account", "Click on the link below \n <a href='http://vaprobash.dev/user/activate?code={$activation->code}&login={$user->id}'>Activate your account</a>");
    echo "Please check your email to complete your account registration. (or just use this <a href='http://vaprobash.dev/user/activate?code={$activation->code}&login={$user->id}'>link</a>)";
});

$app->get('/user/activate', function () use ($app) {
    $code = $app->request->get('code');

    $activationRepository = new Cartalyst\Sentinel\Activations\IlluminateActivationRepository;
    $activation = Cartalyst\Sentinel\Activations\EloquentActivation::where("code", $code)->first();

    if (!$activation)
    {
        echo "Activation error!";
        
        return;
    }

    $user = $app->container->sentinel->findById($activation->user_id);

    if (!$user)
    {
        echo "User not found!";
        
        return;
    }


    if (!$activationRepository->complete($user, $code))
    {
        if ($activationRepository->completed($user))
        {
            echo 'User is already activated. Try to log in.';

            return;
        }

        echo "Activation error!";
        
        return;
    }

    echo 'Your account has been activated. Log in to your account.';

    return;
});


