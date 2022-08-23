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
define('CATECHESIS_UL_SITE_KEY', '/!451pJ{@=&z@;gyHI;#.N?,23!Uv23241RGW92xs14.9}V;5tDD&]L23L9lyPV');


//Databse host
define('CATECHESIS_HOST', 'db');

//Database name
define('CATECHESIS_DB', 'catequese');



//Database users

$HIGH_SECURITY = true; //If your host supports creating users and setting permissions

if($HIGH_SECURITY)
{
	//Isolate privileges by creating several users. Run utilizadores.sql to setup these users.

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
	define('PASS_UL_AUTH', 'oCBO1nbf0HUHFCTjbIFUsWf4Ki9ONavIOTISkyshy3JHjBabPcS4hh9pn8LTS4C');
	define('PASS_UL_UPDATE', 'Knk8rzMNlZQxarrL46QMbCUjlkqVpCjHvpVtDyzQwAvahgARbcTi4SkWGe5uuEq');
	define('PASS_UL_DELETE', 'uTm0N6wDi4P4e7uHxQcJecavUL0sxenFKx98gypKcfMLZ12yNf6SwBqfyQdjBhO');
	define('PASS_UL_SESSION', 'DjqzVtcUR6VHb4g9vXQcxX9Sdt6ldFw991aktrmJKv731VbCp3vU6uH30MR5owu');
	define('PASS_UL_LOG', '8e2ucXaF4FyuiKuYKf0Wf4bQz6E56BFsQt3IMVMYvLHYEXrPHrHyylEsj4zXjFn');

	define('PASS_LOG', 'LitGL44YNOQEGJZSyt7jUvn0K8ywElkbke4MvzdhMX88ZrE4Qo65nDRDQfMx0kq');
	define('PASS_LOG_CLEAN', '59zj8mRebRH0TtTqovJwo2EevUtAa52lir8UZC4Gm8OeVLsNzPRSnLPQZRfNSEY');
	define('PASS_DEFAULT_READ', 'BE4ZRxGBIStaGQTxIDsp53AOo15CC8MLjJ7jH04XZ8Xmx95Szpa0Bdo6k85KhM5');
	define('PASS_DEFAULT_EDIT', 'ZDUGQSsDfj5TgPTW4YNc1ytewaMUTEnlaAf4BbMLI0NbzW2EDfObCwKWHE4P2uM');
	define('PASS_DEFAULT_DELETE', 'TZERJqnuXas5O0gdfP6UENTSqj7w4XQTs2VhxVYh81fVjf94TR892mOSDmP4YWn');
	define('PASS_GROUP_MANAGEMENT', 'p890a6bdeGNiBiy54n38SXmdzM7l1IMq82rPQ9YSJTFCg0cyuivTaDg9LAHJkIo');
	define('PASS_USER_MANAGEMENT', 'DCoeK71fnjfqEt8DEYOUt8dglsBGfJRU7mlJcIN8trWzqlUkicg5tbIAQwm7Mt0');
	define('PASS_CONFIG', 'AdRfK71fnjfqEt8DTuiUt8dglsBGfJRU7mlJcIN8trWzql1s8cg5tbIAUJM09Ol');
    define('PASS_ONLINE_ENROLLMENT', 'AX4ZRgGBISjaGQTxIDsp53AOo15CC8MLjH7jH04XZ8Xmx68Szpa0Bdo6k85KhM5');

    define('PASS_CAPTCHA', 'RebRHqnuXas5O0gdjfqEtNTSqj7w4AOo15CC8MLjJ7jHjf94TRVjf94TR892mOS');

}
else
{
	//Create a single db user with permissions to the whole db
	// Then configure it below:
	$single_user_name = 'db_root_user';
	$single_user_password = 'your_password';
	
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