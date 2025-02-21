<?php

namespace SetupUtils;

use InvalidArgumentException;
use PDO;
use PDOException;


/**
 * Return the user's home directory.
 */
function drush_server_home() {
    // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
    // getenv('HOME') isn't set on Windows and generates a Notice.
    $home = getenv('HOME');
    if (!empty($home)) {
        // home should never end with a trailing slash.
        $home = rtrim($home, '/');
    }
    elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
        // home on windows
        $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
        // If HOMEPATH is a root directory the path can end with a slash. Make sure
        // that doesn't happen.
        $home = rtrim($home, '\\/');
    }
    return empty($home) ? NULL : $home;
}

function homeDir()
{
    if(isset($_SERVER['HOME'])) {
        $result = $_SERVER['HOME'];
    } else {
        $result = getenv("HOME");
    }

    if(empty($result) && function_exists('exec')) {
        if(strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $result = exec("echo %userprofile%");
        } else {
            $result = exec("echo ~");
        }
    }

    return $result;
}


function getUserHomeDir()
{
    if(PHP_OS_FAMILY == "Windows")
    {
        if(isset($_SERVER['HOME']))
        {
            return $_SERVER['HOME'];
        }
        else if(!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH']))
        {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
            return $home;
        }
        else
        {
            return getenv("HOME");
        }
    }
    else //Assume Linux / Unix
    {
        return posix_getpwuid(posix_getuid())['dir'];
    }
}

/**
 * Join path snippets, without duplicating slashes.
 * Sample usage:
 *      joinPaths(array('my/path', 'is', '/an/array'));
 *      joinPaths('my/paths/', '/are/', 'a/r/g/u/m/e/n/t/s/');
 * @return string
 */
function joinPaths()
{
    $args = func_get_args();
    $paths = array();

    foreach($args as $arg)
        $paths = array_merge($paths, (array)$arg);

    foreach($paths as &$path)
        $path = trim($path, '/');

    if (substr($args[0], 0, 1) == '/')
        $paths[0] = '/' . $paths[0];

    return join('/', $paths);
}


/**
 * Copy a file, or recursively copy a folder and its contents
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       int      $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 *@version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @author      Aidan Lister <aidan@php.net>
 */
function xcopy($source, $dest, $permissions = 0755)
{
    $sourceHash = hashDirectory($source);
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        if($sourceHash != hashDirectory($source."/".$entry)){
            xcopy("$source/$entry", "$dest/$entry", $permissions);
        }
    }

    // Clean up
    $dir->close();
    return true;
}

// In case of coping a directory inside itself, there is a need to hash check the directory otherwise and infinite loop of coping is generated
function hashDirectory($directory)
{
    if (! is_dir($directory)){ return false; }

    $files = array();
    $dir = dir($directory);

    while (false !== ($file = $dir->read())){
        if ($file != '.' and $file != '..') {
            if (is_dir($directory . '/' . $file)) { $files[] = hashDirectory($directory . '/' . $file); }
            else { $files[] = md5_file($directory . '/' . $file); }
        }
    }

    $dir->close();

    return md5(implode('', $files));
}


/**
 * Receives a file path and a dicitonary of strings, and replaces the occurrences of each dictionary
 * key in the file by the respective value.
 * @param string $filepath
 * @param array $replacements
 * @return void
 */
function replace_strings_in_file(string $filepath, array $replacements)
{
    $contents = file_get_contents($filepath);

    //Replace all the keys by the respective values
    foreach($replacements as $key => $value)
        $contents = str_replace($key, $value, $contents);

    file_put_contents($filepath, $contents);
}


/**
 * Checks if the connection to the database is successful with the given credentials.
 * @param string $db_host
 * @param string $db_name
 * @param string $username
 * @param string $password
 * @return bool
 */
function test_db_connection(string $db_host, string $db_name, string $username, string $password)
{
    try
    {
        $connection = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=utf8", $username, $password, array(PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $connection = null;
        return true;
    }
    catch (PDOException $e)
    {
        return false;
    }
}


/**
 * Runs an ".sql" script on the database.
 * @param string $db_host
 * @param string $db_name
 * @param string $username
 * @param string $password
 * @param string $scriptFilePath
 * @return bool
 */
function run_sql_script(string $db_host, string $db_name, string $username, string $password, string $scriptFilePath)
{
    try
    {
        $connection = new PDO("mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=utf8", $username, $password, array(PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $query = file_get_contents($scriptFilePath);
        $stmt = $connection->prepare($query);
        $res = $stmt->execute();
        $connection = null;
        return $res;
    }
    catch (PDOException $e)
    {
        return false;
    }
}



/**
 * Deletes a directory an its contents.
 */
function delete_dir($dirPath)
{
    if (! is_dir($dirPath))
        throw new InvalidArgumentException("$dirPath tem de ser uma diretoria");

    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
        $dirPath .= '/';

    $files = glob($dirPath . '{,.}[!.,!..]*',GLOB_BRACE); //Captures all files, including those hidden files started by ".", but does not capture the folders "." and "..".
    foreach ($files as $file)
    {
        if (is_dir($file))
            delete_dir($file);
        else
            unlink($file);
    }

    return rmdir($dirPath);
}


/**
 * Some PHP files import the ulogin module, which automatically starts a secure ssesion (sses) and breaks the current
 * session started by the installation wizard.
 * This function allows to make such imports, closing the sses created by ulogin  and resuming the plain old php session
 * started by the installation wizard.
 * @param string $php_file
 * @param string|null $session_id  - Pass the session_id you want to keep. Otherwise, the current session id (at the time this function is called) will be used.
 * @return void
 */
function require_once_keep_session(string $php_file, string $session_id = null, array $old_session_data = null)
{
    $old_session_id = $session_id?:session_id();            //Use the provided session_id, if not null. Otherwise, get the current session id.
    $old_session_data = $old_session_data?:$_SESSION;       //Use the provided session data, if not null. Otherwise, get the current session data.
    require_once($php_file);
    sses_destroy();
    session_id($old_session_id);
    session_start();
    $_SESSION = $old_session_data;
}