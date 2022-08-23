<?php
// The most sensitive configuration keys are stored here.
// A minimal configuration file is located at the catechesis root folder, and
// automatically includes this one.

//Site key
//	A random string. Make it as random as possible and keep it secure.
//	This is a crypthographic key that uLogin will use to generate some data
//	and later verify its identity.
//	The longer the better, should be 40+ characters.
//	Once set and your site is live, do not change this.
define('CATECHESIS_UL_SITE_KEY', '<CATECHESIS_UL_SITE_KEY>');


//Databse host
define('CATECHESIS_HOST', '<CATECHESIS_HOST>');

//Database name
define('CATECHESIS_DB', '<CATECHESIS_DB>');



//Database users

$HIGH_SECURITY = false; //If your host supports creating users and setting permissions

if($HIGH_SECURITY)
{
	//Isolate privileges by creating several users. Run users.sql to setup these users.

	//Users for ulogin
	define('USER_UL_AUTH', 'ul_auth_user');
	define('USER_UL_UPDATE', 'ul_update_user');
	define('USER_UL_DELETE', 'ul_delete_user');
	define('USER_UL_SESSION', 'ul_session_user');
	define('USER_UL_LOG', 'ul_log_user');

	//Users for CatecheSis
	define('USER_LOG', 'cat_log_user');
	define('USER_LOG_CLEAN', 'cat_log_cln_user');
	define('USER_DEFAULT_READ', 'cat_read_user');
	define('USER_DEFAULT_EDIT', 'cat_edit_user');
	define('USER_DEFAULT_DELETE', 'cat_delete_user');
	define('USER_GROUP_MANAGEMENT', 'cat_gp_mgt_user');
	define('USER_USER_MANAGEMENT', 'cat_usr_mgt_user');
	define('USER_CONFIG', 'cat_config_user');
	define('USER_ONLINE_ENROLLMENT', 'cat_online_enrollment_user');

	//User for secureimage (CAPTCHA)
    define('USER_CAPTCHA', 'secureimage_user');



	//Passwords
	define('PASS_UL_AUTH', '<PASS_UL_AUTH>');
	define('PASS_UL_UPDATE', '<PASS_UL_UPDATE>');
	define('PASS_UL_DELETE', '<PASS_UL_DELETE>');
	define('PASS_UL_SESSION', '<PASS_UL_SESSION>');
	define('PASS_UL_LOG', '<PASS_UL_LOG>');

	define('PASS_LOG', '<PASS_LOG>');
	define('PASS_LOG_CLEAN', '<PASS_LOG_CLEAN>');
	define('PASS_DEFAULT_READ', '<PASS_DEFAULT_READ>');
	define('PASS_DEFAULT_EDIT', '<PASS_DEFAULT_EDIT>');
	define('PASS_DEFAULT_DELETE', '<PASS_DEFAULT_DELETE>');
	define('PASS_GROUP_MANAGEMENT', '<PASS_GROUP_MANAGEMENT>');
	define('PASS_USER_MANAGEMENT', '<PASS_USER_MANAGEMENT>');
	define('PASS_CONFIG', '<PASS_CONFIG>');
	define('PASS_ONLINE_ENROLLMENT', '<PASS_ONLINE_ENROLLMENT>');

    define('PASS_CAPTCHA', '<PASS_CAPTCHA>');

}
else
{
	//Create a single db user with permissions to the whole db
	// Then configure it below:
	$single_user_name = '<DB_ROOT_USER>';
	$single_user_password = '<DB_ROOT_PASSWORD>';
	
	//Users for ulogin
	define('USER_UL_AUTH', $single_user_name);
	define('USER_UL_UPDATE', $single_user_name);
	define('USER_UL_DELETE', $single_user_name);
	define('USER_UL_SESSION', $single_user_name);
	define('USER_UL_LOG', $single_user_name);

	//Users for CatecheSis
	define('USER_LOG', $single_user_name);
	define('USER_LOG_CLEAN', $single_user_name);
	define('USER_DEFAULT_READ', $single_user_name);
	define('USER_DEFAULT_EDIT', $single_user_name);
	define('USER_DEFAULT_DELETE', $single_user_name);
	define('USER_GROUP_MANAGEMENT', $single_user_name);
	define('USER_USER_MANAGEMENT', $single_user_name);
	define('USER_CONFIG', $single_user_name);
	define('USER_ONLINE_ENROLLMENT', $single_user_name);

    //User for secureimage (CAPTCHA)
    define('USER_CAPTCHA', $single_user_name);



	//Passwords
	define('PASS_UL_AUTH', $single_user_password);
	define('PASS_UL_UPDATE', $single_user_password);
	define('PASS_UL_DELETE', $single_user_password);
	define('PASS_UL_SESSION', $single_user_password);
	define('PASS_UL_LOG', $single_user_password);

	define('PASS_LOG', $single_user_password);
	define('PASS_LOG_CLEAN', $single_user_password);
	define('PASS_DEFAULT_READ', $single_user_password);
	define('PASS_DEFAULT_EDIT', $single_user_password);
	define('PASS_DEFAULT_DELETE', $single_user_password);
	define('PASS_GROUP_MANAGEMENT', $single_user_password);
	define('PASS_USER_MANAGEMENT', $single_user_password);
	define('PASS_CONFIG', $single_user_password);
	define('PASS_ONLINE_ENROLLMENT', $single_user_password);

    define('PASS_CAPTCHA', $single_user_password);

}
?>