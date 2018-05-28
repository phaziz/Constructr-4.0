<?php

  session_start();

  require_once __DIR__ . '/vendor/autoload.php';
  require_once __DIR__ . '/constructr_settings/constructr.php';

  use Monolog\Logger;
  use Monolog\Handler\StreamHandler;

  use \Psr\Http\Message\ServerRequestInterface as Request;
  use \Psr\Http\Message\ResponseInterface as Response;

  use Ramsey\Uuid\Uuid;
  use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

  $app = new \Slim\App($config);

  $container = $app -> getContainer();

  $container['logger'] = function($config)
  {
    try
    {
      $logger = new \Monolog\Logger($config['settings']['monolog']['name']);
      $file_handler = new \Monolog\Handler\StreamHandler($config['settings']['monolog']['path']);
      $logger -> pushHandler($file_handler);
      return $logger;
    }
    catch (Exception $ex)
    {
      die('Logger exception: ' . $ex -> getMessage());
    }
  };

  $container['csrf'] = function ()
  {
    return new \Slim\Csrf\Guard;
  };

  $container['uuid'] = function($config)
  {
    try
    {
      $uuid5 = Uuid::uuid5(Uuid::NAMESPACE_DNS, $config['settings']['uuid']['namespace']);
      return $uuid5 -> toString();
    }
    catch (UnsatisfiedDependencyException $ex)
    {
      die('UUID exception: ' . $ex -> getMessage());
    }
  };

  $container['flash'] = function ()
  {
    try
    {
      return new \Slim\Flash\Messages();
    }
    catch (Exception $ex)
    {
      die('Caught exception: ' . $ex -> getMessage());
    }
  };

  $container['twig'] = function($config)
  {
    try
    {
      $loader = new Twig_Loader_Filesystem($config['settings']['twig']['tpl_path']);
      $twig = new Twig_Environment($loader,
        [
           'tpl_path' => $config['settings']['twig']['tpl_path'],
           'cache' => false, //$config['settings']['twig']['cache'],
           'debug' => $config['settings']['twig']['debug'],
           'strict_variables' => $config['settings']['twig']['strict_varaibles'],
           'autoescape' => $config['settings']['twig']['autoescape'],
           'optimizations' => $config['settings']['twig']['optimizations'],
           'charset' => $config['settings']['twig']['charset']
        ]
      );
      return $twig;
    }
    catch (Exception $ex)
    {
      die('Twig Error: ' . $ex -> getMessage());
    }
  };

  $container['db'] = function ($config)
  {
    try
    {
      $dsn = 'mysql:host=' . $config['settings']['database']['host'] . ';dbname=' . $config['settings']['database']['database'] . ';charset=' . $config['settings']['database']['charset'];
      $usr = $config['settings']['database']['user'];
      $pwd = $config['settings']['database']['passwd'];
      $pdo = new \Slim\PDO\Database($dsn, $usr, $pwd);

      return $pdo;
    }
    catch (\PDOException $ex)
    {
      die('DB error: ' . $ex -> getMessage());
    }
  };

  $container['notFoundHandler'] = function ($container)
  {
    return function ($request, $response) use ($container)
    {
      $notFoundPage = file_get_contents(__DIR__ . '/constructr_views/constructr_404.html');
      $container['logger'] -> addInfo('404');

      return $container['response']
        -> withStatus(404)
        -> withHeader('Content-Type', 'text/html')
        -> write($notFoundPage);
    };
  };
/*
  $container['notAllowedHandler'] = function ($container)
  {
    return function ($request, $response) use ($container)
    {
      $notAllowedPage = file_get_contents(__DIR__ . '/constructr_views/constructr_405.html');
      $container['logger'] -> addInfo('405');

      return $container['response']
        -> withStatus(405)
        -> withHeader('Content-Type', 'text/html')
        -> write($notAllowedPage);
    };
  };

  $container['phpErrorHandler'] = function ($container)
  {
    return function ($request, $response) use ($container)
    {
      $errorPage = file_get_contents(__DIR__ . '/constructr_views/constructr_error.html');
      $container['logger'] -> addInfo('500');

      return $container['response']
        -> withStatus(500)
        -> withHeader('Content-Type', 'text/html')
        -> write($errorPage);
    };
  };



  /************************************************************ROUTING************************************************************/



  $app->get('/', function ($request, $response, $args) use ($app)
    {
        if(true == $this -> get('settings')['constructr']['backend_active'])
        {
          return ConstructrHomeController::ConstructrHome($request, $response, $this -> db, $this -> logger, $args);
        }
        else
        {
          $this -> logger -> addInfo('BACKEND INACTIVE (/)');

          return $this -> twig -> render('constructr_index.html',[]);
        }
    }
  );



  $app -> get('/login', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $this -> get('settings')['constructr']['backend_active'])
      {
        return ConstructrLoginController::ConstructrLoginStep1($request, $response, $settings, $this -> twig, $this -> csrf, $this -> flash, $this -> db, $this -> logger, $args);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  ) -> add( $container -> get('csrf') );



  $app -> post('/login-step-1', function ($request, $response, $args) use ($app)
    {
      $PARAMS = $request -> getParams();
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrLoginController::ConstructrLoginStep1Post($request, $response, $settings, $this -> twig, $this -> csrf, $this -> flash, $this -> db, $this -> logger, $PARAMS, $args);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/login-step-2', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrLoginController::ConstructrLoginStep2($request, $response, $settings, $this -> twig, $this -> csrf, $this -> flash, $this -> db, $this -> logger, $args);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/login-step-2)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  ) -> add($container -> get('csrf'));



  $app -> post('/login-step-2', function ($request, $response, $args) use ($app)
    {
      $PARAMS = $request -> getParams();
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrLoginController::ConstructrLoginStep2Post($request, $response, $settings, $this -> twig, $this -> csrf, $this -> flash, $this -> db, $this -> logger, $PARAMS, $args);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/login-step-2)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/login-no-no-no-wait', function ($request, $response, $args) use ($app)
    {
      return ConstructrLoginController::ConstructrLoginNoNoNoWait($request, $response, $args);
    }
  );



  $app -> get('/constructr', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrHomeController::Constructr($request, $response, $settings, $this -> twig);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/constructr/user-management', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrUserController::ConstructrUserManagement($request, $response, $settings, $this -> twig, $this -> csrf, $this -> flash, $this -> db, $this -> logger, $args);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr/user-management)');
        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/constructr/user-management/delete-user/{id}', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrUserController::ConstructrDeleteUser($request, $response, $this -> flash, $this -> db, $args['id']);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr/user-management/delete-user/{id}');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/constructr/user-management/new-user', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrUserController::ConstructrNewUserForm($request, $response, $settings, $this -> csrf, $this -> flash, $this -> twig);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr/user-management/new-user)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  ) -> add($container -> get('csrf'));



  $app -> post('/constructr/user-management/insert-new-user', function ($request, $response, $args) use ($app)
    {
      $PARAMS = $request -> getParams();
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrUserController::ConstructrNewUserPost($request, $response, $settings, $PARAMS, $this -> db, $this -> flash);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr/user-management/new-user)');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/constructr/user-management/edit/{id}', function ($request, $response, $args) use ($app)
    {
      $settings = $this -> get('settings');

      if(true == $settings['constructr']['backend_active'])
      {
        return ConstructrUserController::ConstructrEditUserForm($request, $response, $this -> db, $args, $this -> csrf, $this -> twig, $settings);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr/user-management/edit/{id})');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  ) -> add($container -> get('csrf'));



  $app -> post('/constructr/user-management/edit/{id}', function ($request, $response, $args) use ($app)
    {
      $PARAMS = $request -> getParams();

      if(true == $this -> get('settings')['constructr']['backend_active'])
      {
        return ConstructrUserController::ConstructrEditUserPost($request, $response, $PARAMS, $args, $settings, $this -> db, $this -> flash);
      }
      else
      {
        $this -> logger -> addInfo('BACKEND INACTIVE (/constructr/user-management/edit/{id})');

        return $this -> twig -> render('constructr_index.html',[]);
      }
    }
  );



  $app -> get('/constructr/logout', function ($request, $response, $args) use ($app)
    {
      return ConstructrHomeController::ConstructrLogout($request, $response);
    }
  );



/************************************************************ROUTING************************************************************/



  $app -> run();
