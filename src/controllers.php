<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = md5($request->get('password'));

    if ($username) {
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
  $user = $app['session']->get('user');
    $user_id = $user['id'];

    if ($id){
        
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {

        $sql = "SELECT * FROM todos WHERE user_id = '$user_id'";
        $todos = $app['db']->fetchAll($sql);
        return $app['twig']->render('todos.html', [
            'todos' => $todos,
        ]);
    }
})
->value('id', null);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $errors = $app['validator']->validate($request->get('description'), new Assert\NotBlank());

    if (count($errors) == 0) {

    $user_id = $user['id'];
    $description = $request->get('description');
    $sql = "INSERT INTO todos (user_id, description) VALUES ('$user_id', '$description')";
    $app['db']->executeUpdate($sql);

    # Set flash message and return redirect
    $app['session']->getFlashBag()->add('success_msg', 'The task has been added successfully.');
    return $app->redirect('/todo');
    
    }
    else{

    # Set flash message and return redirect
    $app['session']->getFlashBag()->add('error_msg', 'Description field is required.');
    return $app->redirect('/todo');

    }
    

});


$app->match('/todo/delete/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
    
    $user    = $app['session']->get('user');
    $user_id = $user['id'];
    //$sql = "DELETE FROM todos WHERE id = '$id'";
    $sql = "DELETE FROM todos WHERE id = '$id' AND user_id = '$user_id'";
    $app['db']->executeUpdate($sql);

    # Set flash message and return redirect
    $app['session']->getFlashBag()->add('success_msg', 'The task has been deleted successfully.');
    return $app->redirect('/todo');
});

$app->match('/todo/edit/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user    = $app['session']->get('user');
    $user_id = $user['id'];

    $sql = "SELECT * FROM todos WHERE id = '$id'";
    $todo = $app['db']->fetchAssoc($sql);

   // print_r($todo);exit;

    return $app['twig']->render('edit.html', [
            'todo' => $todo,
    ]);

});

$app->post('/todo/update', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $errors = $app['validator']->validate($request->get('description'), new Assert\NotBlank());

    if (count($errors) == 0) {

    $user_id = $user['id'];
    $description = $request->get('description');
    $id = $request->get('id');
    $sql = "UPDATE todos SET description = '$description' WHERE id= '$id'";
    $app['db']->executeUpdate($sql);

    # Set flash message and return redirect
    $app['session']->getFlashBag()->add('success_msg', 'The task has been updated successfully.');
    return $app->redirect('/todo');
    
    }
    else{

    # Set flash message and return redirect
    $app['session']->getFlashBag()->add('error_msg', 'Description field is required.');
    return $app->redirect('/todo');

    }
    

});