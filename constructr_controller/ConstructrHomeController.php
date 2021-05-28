<?php

  class ConstructrHomeController
  {

     public function ConstructrHome($request, $response, $db, $logger, $args)
     {
       $SELECT_USERNAME = $db -> select()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_username', '=', $_SESSION['constructr_username'])
         -> where('constructr_backenduser_password', '=', $_SESSION['constructr_password'])
         -> where('constructr_backenduser_active', '=', 1)
         -> limit(1);

       $STMT_USER = $SELECT_USERNAME -> execute();
       $DATA_USER = $STMT_USER -> fetchAll();

       if(1 == count($DATA_USER))
       {
         $logger -> addInfo('Returning User: ' . $_SESSION['constructr_username']);

         return $response -> withRedirect('/constructr');
       }
       else
       {
         return $response -> withRedirect('/login');
       }
     } // EOF ConstructrHome Function



     public function Constructr($request, $response, $settings, $twig)
     {
       $_SESSION["csrf"] = NULL;

       return $twig -> render('constructr_backend.html',[
         'USERNAME' => $_SESSION['constructr_username'],
         'BASE_URL' => $settings['app']['baseurl']
       ]);
     } // EOF Constructr -> MainEntryPageFunction



     public function ConstructrLogout($request, $response)
     {
       $_SESSION['constructr_username'] = NULL;
       $_SESSION['constructr_password'] = NULL;
       $_SESSION["csrf"] = NULL;
       $_SESSION["slimFlash"] = NULL;
       $_SESSION["login_countr"] = NULL;
       $_SESSION["login_waitr"] = NULL;

       session_regenerate_id();

       return $response -> withRedirect('/');
     } // EOF ConstructrLogout Function

  } // EOF ConstructrHomeController Class
