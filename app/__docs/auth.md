# Application Authentication

## \app\AppLogin

Application state is stored in $_SESSION['_user'] , which is decanted into self::$_user at initialisation

self::$_user contains an object 

{
    sid : <system id>       
    pid : <project id>
    atkn : <access token>
    rtkn : <refresh token> 
}

A user is considered logged in when we have sid > 0 , pid > 0 and an non-empty access token.

Optional refresh token , if present , is used to refresh the access token.

Refresh tokens are stored in php session , so have fixed lifetime , forcing relogin when a session expires.

