<?php

  class ConstructrUserController
  {

     public function ConstructrUserManagement($request, $response, $settings, $twig, $csrf, $flash, $db, $logger, $args)
     {
       $SELECT_USERNAME = $db -> select()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_username', '=', $_SESSION['constructr_username'])
         -> where('constructr_backenduser_active', '=', 1)
         -> limit(1);
       $STMT_USER = $SELECT_USERNAME -> execute();
       $DATA_USER = $STMT_USER -> fetchAll();

       $SELECT_USER = $db -> select()
         -> from('constructr_backenduser');
       $STMT_SELECT_USER = $SELECT_USER -> execute();
       $ALL_USER = $STMT_SELECT_USER -> fetchAll();

       $MESSAGE = $flash -> getMessages();

       if(1 == count($DATA_USER) && count($ALL_USER) >= 1)
       {
         $USER_ID = $DATA_USER[0]['constructr_backenduser_id'];
         $USER_EMAIL = $DATA_USER[0]['constructr_backenduser_email'];
         $USER_ACTIVE = 1;
         $USER_LAST_LOGIN = date("d.m.Y, H:i:s",strtotime($DATA_USER[0]['constructr_backenduser_last_login']));
         $NAME_KEY = $csrf -> getTokenNameKey();
         $VALUE_KEY = $csrf -> getTokenValueKey();
         $NAME = $request -> getAttribute($NAME_KEY);
         $VALUE = $request -> getAttribute($VALUE_KEY);

         return $twig -> render('constructr_backend_user_management.html',[
           'USERNAME' => $_SESSION['constructr_username'],
           'BASE_URL' => $settings['app']['baseurl'],
           'USER_ID' => $USER_ID,
           'USER_EMAIL' => $USER_EMAIL,
           'USER_ACTIVE' => $USER_ACTIVE,
           'USER_LAST_LOGIN' => $USER_LAST_LOGIN,
           'CONSTRUCTR_BASE_URL' => $settings['app']['baseurl'],
           'NAME_KEY' => $NAME_KEY,
           'VALUE_KEY' => $VALUE_KEY,
           'NAME' => $NAME,
           'VALUE' => $VALUE,
           'ALL_USER' => $ALL_USER,
           'USER_COUNTR' => count($ALL_USER),
           'MESSAGE' => $MESSAGE['Update'][0]
         ]);
       }
       else
       {
         return $response -> withRedirect('/constructr/logout');
       }
     } // EOF Constructr Function



     public function ConstructrDeleteUser($request, $response, $flash, $db, $id)
     {
       $DELETE_USER = $db -> delete()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_id', '=', $id);
       $DELETE_USER_AFFECTED_ROWS = $DELETE_USER -> execute();

       $flash -> addMessage('Update', 'User deleted successfully!');

       return $response -> withRedirect('/constructr/user-management');
     } // EOF ConstructrDeleteUser Function



     public function ConstructrNewUserForm($request, $response, $settings, $csrf, $flash, $twig)
     {
       $NAME_KEY = $csrf -> getTokenNameKey();
       $VALUE_KEY = $csrf -> getTokenValueKey();
       $NAME = $request -> getAttribute($NAME_KEY);
       $VALUE = $request -> getAttribute($VALUE_KEY);
       $MESSAGE = $flash -> getMessages();

       return $twig -> render('constructr_backend_user_management_new_user.html',[
         'USERNAME' => $_SESSION['constructr_username'],
         'BASE_URL' => $settings['app']['baseurl'],
         'NAME_KEY' => $NAME_KEY,
         'VALUE_KEY' => $VALUE_KEY,
         'NAME' => $NAME,
         'VALUE' => $VALUE,
         'MESSAGE' => $MESSAGE['Insert'][0]
       ]);
     } // EOF ConstructrNewUserForm Function



     public function ConstructrNewUserPost($request, $response, $settings, $PARAMS, $db, $flash)
     {
       if('on' == $PARAMS['user_active'])
       {
         $EDIT_USER_ACTIVE = 1;
       }
       else
       {
         $EDIT_USER_ACTIVE = 0;
       }

       $NEW_USER_ID = $args['id'];
       $NEW_USER_USERNAME = $PARAMS['username'];
       $NEW_USER_EMAIL = $PARAMS['email'];
       $NEW_USER_PASSWORD = $PARAMS['password'];

       $SELECT_USERNAME = $db -> select()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_username', '=', $NEW_USER_USERNAME)
         -> where('constructr_backenduser_active', '=', 1)
         -> limit(1);
       $STMT_USER = $SELECT_USERNAME -> execute();
       $DATA_USER = $STMT_USER -> fetchAll();

       if(1 == count($DATA_USER))
       {
         $flash -> addMessage('Insert', 'Username already exists!');

         return $response -> withRedirect('/constructr/user-management/new-user');
       }

       if($PARAMS['password'] == $PARAMS['password_retype'])
       {
         $NEW_USER_PASSWORD = password_hash($PARAMS['password'], $settings['hash_alg_int'], ['memory_cost' => $settings['hash_memory_cost'], 'time_cost' => $settings['hash_time_cost'], 'threads' => $settings['hash_threads']]);

         $INSERT_USER = $db -> insert(array('constructr_backenduser_username'))
           -> into('constructr_backenduser')
           -> columns(array('constructr_backenduser_password', 'constructr_backenduser_email', 'constructr_backenduser_active', 'constructr_backenduser_last_login'))
           -> values(array($NEW_USER_USERNAME, $NEW_USER_PASSWORD, $NEW_USER_EMAIL, 1, NULL));
         $INSERT_USER_ID = $INSERT_USER -> execute(false);

         $flash -> addMessage('Update', 'User inserted successfully!');
         $_SESSION["csrf"] = NULL;

         return $response -> withRedirect('/constructr/user-management');
       }
       else
       {
         $flash -> addMessage('Insert', 'Password mismatch!');

         return $response -> withRedirect('/constructr/user-management/new-user');
       }
     } // EOF ConstructrNewUserPost Function



     public function ConstructrEditUserForm($request, $response, $db, $args, $csrf, $twig, $settings)
     {
       $SELECT_USERNAME = $db -> select()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_username', '=', $_SESSION['constructr_username'])
         -> where('constructr_backenduser_active', '=', 1)
         -> limit(1);
       $STMT_USER = $SELECT_USERNAME -> execute();
       $DATA_USER = $STMT_USER -> fetchAll();

       $EDIT_USER = $db -> select()
         -> from('constructr_backenduser')
         -> where('constructr_backenduser_id', '=', $args['id'])
         -> limit(1);
       $STMT_EDIT_USER = $EDIT_USER -> execute();
       $EDIT_USER = $STMT_EDIT_USER -> fetchAll();

       if(1 == count($DATA_USER) && 1 == count($EDIT_USER))
       {
         $USER_ID = $DATA_USER[0]['constructr_backenduser_id'];
         $USER_EMAIL = $DATA_USER[0]['constructr_backenduser_email'];
         $USER_ACTIVE = 1;
         $USER_LAST_LOGIN = date("d.m.Y, H:i:s",strtotime($DATA_USER[0]['constructr_backenduser_last_login']));
         $EDIT_USER_ID = $EDIT_USER[0]['constructr_backenduser_id'];
         $EDIT_USER_USERNAME = $EDIT_USER[0]['constructr_backenduser_username'];
         $EDIT_USER_EMAIL = $EDIT_USER[0]['constructr_backenduser_email'];
         $EDIT_USER_ACTIVE = $EDIT_USER[0]['constructr_backenduser_active'];
         $EDIT_USER_LAST_LOGIN = date("d.m.Y, H:i:s",strtotime($EDIT_USER[0]['constructr_backenduser_last_login']));
         $NAME_KEY = $csrf -> getTokenNameKey();
         $VALUE_KEY = $csrf -> getTokenValueKey();
         $NAME = $request -> getAttribute($NAME_KEY);
         $VALUE = $request -> getAttribute($VALUE_KEY);

         return $twig -> render('constructr_backend_user_management_edit_user.html',[
           'USERNAME' => $_SESSION['constructr_username'],
           'BASE_URL' => $settings['app']['baseurl'],
           'USER_ID' => $USER_ID,
           'USER_EMAIL' => $USER_EMAIL,
           'USER_ACTIVE' => $USER_ACTIVE,
           'USER_LAST_LOGIN' => $USER_LAST_LOGIN,
           'EDIT_USER_ID' => $EDIT_USER_ID,
           'EDIT_USER_USERNAME' => $EDIT_USER_USERNAME,
           'EDIT_USER_EMAIL' => $EDIT_USER_EMAIL,
           'EDIT_USER_ACTIVE' => $EDIT_USER_ACTIVE,
           'EDIT_USER_LAST_LOGIN' => $EDIT_USER_LAST_LOGIN,
           'CONSTRUCTR_BASE_URL' => $settings['app']['baseurl'],
           'NAME_KEY' => $NAME_KEY,
           'VALUE_KEY' => $VALUE_KEY,
           'NAME' => $NAME,
           'VALUE' => $VALUE
         ]);
       }
       else
       {
         return $response -> withRedirect('/constructr/logout');
       }
     } // EOF ConstructrEditUserForm Function



     public function ConstructrEditUserPost($request, $response, $PARAMS, $args, $settings, $db, $flash)
     {
       if('on' == $PARAMS['user_active'])
       {
         $EDIT_USER_ACTIVE = 1;
       }
       else
       {
         $EDIT_USER_ACTIVE = 0;
       }

       $EDIT_USER_ID = $args['id'];
       $EDIT_USER_USERNAME = $PARAMS['username'];
       $EDIT_USER_EMAIL = $PARAMS['email'];
       $EDIT_USER_UPDATE_PASSWORD = false;

       if('' != $PARAMS['new_password'])
       {
         if($PARAMS['new_password'] == $PARAMS['new_password_retype'])
         {
           $EDIT_USER_NEW_PASSWORT = password_hash($PARAMS['new_password'], $settings['hash_alg_int'], ['memory_cost' => $settings['hash_memory_cost'], 'time_cost' => $settings['hash_time_cost'], 'threads' => $settings['hash_threads']]);

           $UPDATE_USER = $db -> update(array('constructr_backenduser_username' => $EDIT_USER_USERNAME))
             -> set(array('constructr_backenduser_email' => $EDIT_USER_EMAIL))
             -> set(array('constructr_backenduser_active' => $EDIT_USER_ACTIVE))
             -> set(array('constructr_backenduser_password' => $EDIT_USER_NEW_PASSWORT))
             -> table('constructr_backenduser')
             -> where('constructr_backenduser_id', '=', $EDIT_USER_ID);
           $AFFECTED_ROWS = $UPDATE_USER -> execute();

           $flash -> addMessage('Update', 'User updated successfully!');
           $_SESSION["csrf"] = NULL;

           return $response -> withRedirect('/constructr/user-management');
         }
         else
         {
           $flash -> addMessage('Update', 'Password mismatch!');

           return $response -> withRedirect('/constructr/user-management');
         }
       }
       else
       {
         $UPDATE_USER = $db -> update(array('constructr_backenduser_username' => $EDIT_USER_USERNAME))
           -> set(array('constructr_backenduser_email' => $EDIT_USER_EMAIL))
           -> set(array('constructr_backenduser_active' => $EDIT_USER_ACTIVE))
           -> table('constructr_backenduser')
           -> where('constructr_backenduser_id', '=', $EDIT_USER_ID);
         $AFFECTED_ROWS = $UPDATE_USER -> execute();

         $flash -> addMessage('Update', 'User updated successfully!');
         $_SESSION["csrf"] = NULL;

         return $response -> withRedirect('/constructr/user-management');
       }
     } // EOF ConstructrEditUserPost Function

   } // EOF CLASS ConstructrUserController
