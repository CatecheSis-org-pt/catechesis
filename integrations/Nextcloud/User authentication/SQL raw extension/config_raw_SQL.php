<?php
$CONFIG = array (
  'instanceid' => '<SECRET>',
  'passwordsalt' => '<SECRET>',
  'secret' => '<PRIVATE>',
  'trusted_domains' => 
  array (
    0 => '<YOUR_DOMAIN>',
  ),
  'datadirectory' => '/home/catechesis/nextclouddata',
  'dbtype' => 'mysql',
  'version' => '20.0.5.2',
  'overwrite.cli.url' => 'https://<YOUR_DOMAIN>/cloud',
  'dbname' => '<SECRET>',
  'dbhost' => 'localhost',
  'dbport' => '',
  'dbtableprefix' => 'oc_',
  'dbuser' => '<SECRET>',
  'dbpassword' => '<SECRET>',
  'installed' => true,
  'user_backend_sql_raw' => array(
	'db_type' => 'mariadb',
	'db_host' => 'localhost',
	'db_port' => '3306',
	'db_name' => 'catechesis_catequese',
	'db_user' => '<SECRET>',
	'db_password' => '<PRIVATE>',
	//'mariadb_charset' => 'utf8mb4',
	'queries' => array(
		'get_password_hash_for_user' => 'SELECT password FROM ul_logins WHERE username = :username',
		'user_exists' => 'SELECT EXISTS(SELECT 1 FROM ul_logins WHERE username = :username)',
		'get_users' => 'SELECT l.username FROM ul_logins l, utilizador u WHERE (l.username LIKE :search) OR (u.nome LIKE :search)',
		//'set_password_hash_for_user' => 'UPDATE users SET password_hash = :new_password_hash WHERE local = split_part(:username, \'@\', 1) AND domain = split_part(:username, \'@\', 2)',
		//'delete_user' => 'DELETE FROM users WHERE local = split_part(:username, \'@\', 1) AND domain = split_part(:username, \'@\', 2)',
		'get_display_name' => 'SELECT u.nome FROM utilizador u WHERE username = :username'
		//'set_display_name' => 'UPDATE users SET display_name = :new_display_name WHERE local = split_part(:username, \'@\', 1) AND domain = split_part(:username, \'@\', 2)',
		//'count_users' => 'SELECT COUNT * FROM ul_logins'
		//'get_home' => '',
		//'create_user' => 'INSERT INTO users (local, domain, password_hash) VALUES (split_part(:username, \'@\', 1), split_part(:username, \'@\', 2), :password_hash)',
	)
	//'hash_algorithm_for_new_passwords' => 'bcrypt',
)
);
