<?php
// This is just a minimal configuration file.
// Most of the configuration keys are sensitive, and thus were moved to the catechesis data folder
// (which should not be accessible through a browser).


//Catechesis domain
//	Domain name of your site.
//	This must be the same domain name that the browser uses to
//	fetch your website, without the protocol specifier (don't use 'http(s)://').
//	For development on the local machine, use 'localhost'.
//	Takes the same format as the 'domain' parameter of the PHP setcookie function.
define('CATECHESIS_DOMAIN', '<CATECHESIS_DOMAIN>');

//HTTPS
//	Set this to true if your site uses HTTPS, to redirect all pages
//	automatically to HTTPS. A valid certificate must be present in the server
//	to avoid showing security warnings to the client.
define('CATECHESIS_HTTPS', true);

//Base URL
//	The base URL where Catechesis is installed.
//	If you installed it in a subdirectory of your site, such as '/catechesis',
//	then the URL would be 'https://CATECHESIS_DOMAIN/catechesis'.
// NOTE! If you use https protocol, please also ensure that you set define('UL_HTTPS', true) in ulogin/config/main.inc.php.
define('CATECHESIS_BASE_URL', '<CATECHESIS_BASE_URL>');

//Root directory
//	The server directory where the Catechesis root is located.
// NOTE! Please also set the .htaccess file in the CatecheSis root to point to the page 'erro404.php'
// with a path relative to Apache's document root (NOT CatecheSis document root) and beginning with '/'.
define('CATECHESIS_ROOT_DIRECTORY', '<CATECHESIS_ROOT_DIRECTORY>');


//CatecheSis data directory
//  The server directory where user-generated data is stored.
//  This directory should be outside the public_html folder, to guarantee it is NOT accessible through a browser.
define('CATECHESIS_DATA_DIRECTORY', '<CATECHESIS_DATA_DIRECTORY>');


// Load the remaining configurations from file
require_once(constant('CATECHESIS_DATA_DIRECTORY') . '/config/catechesis_config.shadow.php');
?>