<?php
#
# winauth login page - logs the user in if Windows authentication is enabled
#
include dirname(__FILE__) . '/../../../../include/db.php';
include_once dirname(__FILE__) . '/../../../../include/general.php';
include_once dirname(__FILE__) . '/../../include/winauth_functions.php';
   
$session_hash="";

$winuser = WinauthGetUser();

if(count($winauth_domains) > 0 && !in_array($winuser['domain'], $winauth_domains))
    {
    // Invalid domain
    return false;
    }

if($winuser['user'] != "")
    {
    $username = $winuser['user'];
    }

# Allow login
$userref = sql_value("select ref value from user where username='" . escape_check($username) . "' and active=1",0);
if($userref != 0)
    {
    include_once dirname(__FILE__) . '/../../../../include/login_functions.php';
    $ip=get_ip();
    
    # Generate a new session hash.
    $session_hash = generate_session_hash('sha256', md5("RS" . $username . "WINAUTH"));
    
    # Update the user record.
    sql_query("update user set session='" . escape_check($session_hash) . "' where ref='$userref'"); 

    # Log this
    daily_stat("User session",$userref);
    
    log_activity(null,LOG_CODE_LOGGED_IN,$ip,"user","ref",$userref,null,'',$userref);

    # Blank the IP address lockout counter for this IP
    sql_query("delete from ip_lockout where ip='" . escape_check($ip) . "'");
    
    set_login_cookies($userref, $session_hash, "", $user_preferences, "/");
    
    //redirect($baseurl_short . "/pages/" . $default_home_page);
    echo "<script>window.location = '" .  $baseurl_short . "pages/" . $default_home_page . "';</script>";
    exit();
    }
else
    {
    $userinit = getval("winauth_login","") != "";
    redirect($baseurl_short . "login.php" . ($userinit ? "?error=winauth_nouser" : ""));
    }   

