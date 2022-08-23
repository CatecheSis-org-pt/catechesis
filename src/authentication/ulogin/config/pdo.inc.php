<?php

require_once(__DIR__ . "/../../../core/config/catechesis_config.inc.php");


// ------------------------------------------------
//	DATABASE ACCESS
// ------------------------------------------------

// Connection string to use for connecting to a PDO database.
$connectionString = "mysql:host=" . constant('CATECHESIS_HOST') . ";dbname=" . constant('CATECHESIS_DB');
define('UL_PDO_CON_STRING', $connectionString);
// Example for SQLite: 
//define('UL_PDO_CON_STRING', 'sqlite:/path/to/db.sqlite');

// SQL query to execute at the start of each PDO connection.
// For example, "SET NAMES 'UTF8'" if your database engine supports it.
// Unused if empty.
define('UL_PDO_CON_INIT_QUERY', "");

// ------------------------------------------------
//	DATABASE USERS
// ------------------------------------------------

// Following database users should only have access to their specified table(s).
// Optimally, no other user should have access to the same tables, except
// where listed otherwise.

// If you do not want to create all the different users, you can of course
// create just one with appropriate credentials and supply the same username and password
// to all the following fields. However, that is not recommended. You should at least have
// a separate user for the AUTH user.

// You do not need to set logins for functionality that you do not use
// (for example, if you use a different user database).

// AUTH
// Used to log users in.
// Database user with SELECT access to the
// logins table.
define('UL_PDO_AUTH_USER', constant('USER_UL_AUTH'));
define('UL_PDO_AUTH_PWD', constant('PASS_UL_AUTH'));

// LOGIN UPDATE
// Used to add new and modify login data.
// Database user with SELECT, UPDATE and INSERT access to the
// logins table.
define('UL_PDO_UPDATE_USER', constant('USER_UL_UPDATE'));
define('UL_PDO_UPDATE_PWD', constant('PASS_UL_UPDATE'));

// LOGIN DELETE
// Used to remove logins.
// Database user with SELECT and DELETE access to the
// logins table
define('UL_PDO_DELETE_USER', constant('USER_UL_DELETE'));
define('UL_PDO_DELETE_PWD', constant('PASS_UL_DELETE'));

// SESSION
// Database user with SELECT, UPDATE and DELETE permissions to the
// sessions and nonces tables.
define('UL_PDO_SESSIONS_USER', constant('USER_UL_SESSION'));
define('UL_PDO_SESSIONS_PWD', constant('PASS_UL_SESSION'));

// LOG
// Used to log events and analyze previous activity.
// Database user with SELECT, INSERT and DELETE access to the
// logins-log table.
define('UL_PDO_LOG_USER', constant('USER_UL_LOG'));
define('UL_PDO_LOG_PWD', constant('PASS_UL_LOG'));

?>
