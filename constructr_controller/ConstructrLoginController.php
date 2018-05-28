<?php

  class ConstructrLoginController
  {

     public function ConstructrLoginStep1($request, $response, $settings, $twig, $csrf, $flash, $db, $logger, $args)
     {
       if(null !== $_SESSION['login_waitr'])
       {
         return $response -> withRedirect('/login-no-no-no-wait');
       }

       if(null !== $_SESSION['login_countr'])
       {
         if($_SESSION['login_countr'] >= $setting['settings']['max_bad_login'])
         {
           $_SESSION['login_waitr'] = (time() + $setting['settings']['waitr']);

           return $response -> withRedirect('/login-no-no-no-wait');
         }
       }

       $NAME_KEY = $csrf -> getTokenNameKey();
       $VALUE_KEY = $csrf -> getTokenValueKey();
       $NAME = $request -> getAttribute($NAME_KEY);
       $VALUE = $request -> getAttribute($VALUE_KEY);
       $MESSAGE = $flash -> getMessages();

       return $twig -> render('constructr_login.html',[
         'CONSTRUCTR_BASE_URL' => $setting['settings']['app']['baseurl'],
         'NAME_KEY' => $NAME_KEY,
         'VALUE_KEY' => $VALUE_KEY,
         'NAME' => $NAME,
         'VALUE' => $VALUE,
         'MESSAGE' => $MESSAGE['Login'][0],
         'LOGIN_COUNTR' => $_SESSION['login_countr']
       ]);

     } // EOF ConstructrLoginStep1 Function



     public function ConstructrLoginStep1Post($request, $response, $settings, $twig, $csrf, $flash, $db, $logger, $PARAMS, $args)
     {
       $USERNAME = filter_var(trim($PARAMS['username']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

       $SELECT_USERNAME = $db -> select()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_username', '=', $USERNAME)
         -> where('constructr_backenduser_active', '=', 1)
         -> limit(1);
       $STMT_USER = $SELECT_USERNAME -> execute();
       $DATA_USER = $STMT_USER -> fetchAll();

       if(1 == count($DATA_USER))
       {
         $_SESSION['constructr_username'] = $DATA_USER[0]['constructr_backenduser_username'];
         $_SESSION['constructr_password'] = $DATA_USER[0]['constructr_backenduser_password'];

         return $response -> withRedirect('/login-step-2');
       }
       else
       {
         $logger -> addInfo('LOGIN: Username not found');
         $flash -> addMessage('Login', 'Invalid Username!');

         if(NULL === $_SESSION['login_countr'])
         {
           $_SESSION['login_countr'] = 1;
         }
         else
         {
           $_SESSION['login_countr'] = (1 + $_SESSION['login_countr']);

           if($_SESSION['login_countr'] >= $setting['settings']['max_bad_login'])
           {
             $_SESSION['login_waitr'] = (time() + $setting['settings']['waitr']);

             return $response -> withRedirect('/login-no-no-no-wait');
           }
         }

         return $response -> withRedirect('/login');
       }
     } // EOF ConstructrLoginStep1Post Function



     public function ConstructrLoginStep2($request, $response, $settings,  $twig, $csrf, $flash, $db, $logger, $args)
     {
       if(null !== $_SESSION['login_waitr'])
       {
         return $response -> withRedirect('/login-no-no-no-wait');
       }

       if(null !== $_SESSION['login_countr'])
       {
         if($_SESSION['login_countr'] >= $setting['settings']['max_bad_login'])
         {
           $_SESSION['login_waitr'] = (time() + $setting['settings']['waitr']);

           return $response -> withRedirect('/login-no-no-no-wait');
         }
       }

       $NAME_KEY = $csrf -> getTokenNameKey();
       $VALUE_KEY = $csrf -> getTokenValueKey();
       $NAME = $request -> getAttribute($NAME_KEY);
       $VALUE = $request -> getAttribute($VALUE_KEY);
       $MESSAGE = $flash -> getMessages();

       return $twig -> render('constructr_login_2.html',[
         'CONSTRUCTR_BASE_URL' => $setting['settings']['app']['baseurl'],
         'NAME_KEY' => $NAME_KEY,
         'VALUE_KEY' => $VALUE_KEY,
         'NAME' => $NAME,
         'VALUE' => $VALUE,
         'USERNAME' => $_SESSION['constructr_username'],
         'MESSAGE' => $MESSAGE['Login'][0]
       ]);
     } // EOF ConstructrLoginStep2 Function



     public function ConstructrLoginStep2Post($request, $response, $settings, $twig, $csrf, $flash, $db, $logger, $PARAMS, $args)
     {
       if (true == password_verify(trim($PARAMS['password']), $_SESSION['constructr_password']))
       {
         $UPDATE_USER = $db -> update(array('constructr_backenduser_last_login' => date('Y-m-d H:i:s')))
           -> table('constructr_backenduser')
           -> where('constructr_backenduser_username', '=', $_SESSION['constructr_username']);

         $AFFECTED_ROWS = $UPDATE_USER -> execute();

         $_SESSION["csrf"] = NULL;

         return $response -> withRedirect('/constructr');
       }
       else
       {
         $logger -> addInfo('LOGIN: Wrong Password!');
         $flash -> addMessage('Login', 'Invalid Password!');

         if(null !== $_SESSION['login_countr'])
         {
           $_SESSION['login_countr'] = 1;
         }
         else
         {
           $_SESSION['login_countr'] = (1 + $_SESSION['login_countr']);

           if($_SESSION['login_countr'] >= $setting['settings']['max_bad_login'])
           {
             $_SESSION['login_waitr'] = (time() + $setting['settings']['waitr']);

             return $response -> withRedirect('/login-no-no-no-wait');
           }
         }

         return $response -> withRedirect('/login-step-2');
       }
     } // EOF ConstructrLoginStep2Post Function



     public function ConstructrLoginNoNoNoWait($request, $response, $args)
     {
       $_SESSION["csrf"] = NULL;
       $_SESSION["slimFlash"] = NULL;

       if(time() > $_SESSION['login_waitr'])
       {
         $_SESSION['login_waitr'] = NULL;
         $_SESSION['login_countr'] = NULL;

         return $response -> withRedirect('/login');
       }

       echo '<pre>Login Error</pre>';
       echo '<pre>No login until: ' . date('d.m.Y // H:i:s',($_SESSION['login_waitr'])) . ' - than reload this page!</pre>';
     } // EOF ConstructrLoginNoNoNoWait Function

  } // EOF ConstructrHomeController Class
