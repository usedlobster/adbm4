<?php

namespace app\wd\auth;

trait AuthLoginViewTrait {

    // deal with ui stuff
    public function authLoginPage() {

        $user = &$this->_user ;


        if ( ($user->sid ?? 0) < 1) {
            $errormsg = '';

            if (isset($_POST['_login']) && $this->checkCSRF()) {
                $errormsg = $this->doLogin($_POST['username'], $_POST['password']);
                if (empty($errormsg) && (($user->sid ?? 0) > 0)) {
                    header('Location: '.$_SERVER['REQUEST_URI']);
                    exit;
                }
            }

            $this->showBlade('auth.login', ['errormsg' => $errormsg ?? false]);
        }

        if (($user->pid ?? 0) < 1) {
            // no project selected
            $errormsg = '';
            // get list of available , projects
            $list = $this->getProjects($user->sid);
            if (isset($_POST['_pickprj']) && \app\wd\AppMaster::checkCSRF()) {
                // submit pressed
                $pick = $_POST['prj'] ?? 0;
                if ($pick > 0) {
                    $errormsg = $this->doChangeProject($user->sid, $pick);
                    if (empty($errormsg) && (($user->pid ?? 0) > 0)) {
                        header('Location: '.$_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
                else
                    $errormsg = 'Please select a project';
            }

            $this->showBlade('auth.project', ['errormsg' => $errormsg ?? false, 'list' => $list]);
        }
    }

    public function authActionPage( $action ) : never
    {

        switch ( $action ) {
            case 'signout' :
                $this->setLogin( null ) ;
                $this->showBlade('auth.signout') ;
                exit;
            case 'cancel' :
                $this->setLogin( null ) ;
                header('Location: /');
                exit;
            case 'reset-password' :
                (new \app\wd\auth\AuthPasswordReset())->start($this) ;
                break ;
            case 'enter-code' :
                (new \app\wd\auth\AuthPasswordReset())->code($this) ;
                break ;
            case 'change-password' :
                (new \app\wd\auth\AuthPasswordReset())->change($this) ;
                break ;
            case 'done' :
                (new \app\wd\auth\AuthPasswordReset())->done($this) ;
                break ;


        }


        exit ;
    }

}