<?php

namespace catechesis;

require_once(__DIR__ . '/ulogin/config/all.inc.php');
require_once(__DIR__ . '/ulogin/main.inc.php');
require_once(__DIR__ . '/../core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../core/database_cleanup.php');
require_once(__DIR__ . '/../core/check_for_updates.php');


use Exception;
use catechesis\PdoDatabaseManager;



class Authenticator
{

    /**
     * Starts a secure session if none is running.
     */
    public static function startSecureSession()
    {
        if (!sses_running())
            sses_start();
    }

    /**
     * Cheks if the user Accessing the page that calls this is logged in.
     * @return bool
     */
    public static function isAppLoggedIn()
    {
        return (isset($_SESSION['uid']) && isset($_SESSION['username']) && isset($_SESSION['loggedIn']) && ($_SESSION['loggedIn'] === true));
    }


    /**
     * Redirects the user to the front page of CatecheSis.
     * Optionally, a URL may be provided so that the user is redirected to it after sucessful login.
     * @param string|null $redirectTo - URL to redirect the user to after successful login
     */
    public static function denyAccess(string $redirectTo = null)
    {
        if($redirectTo)
            header("Location: " . constant('CATECHESIS_BASE_URL') . "/login.php?redirect=" . urlencode($redirectTo));
        else
            header("Location: " . constant('CATECHESIS_BASE_URL') . "/login.php");
        exit();
    }


    /**
     * Logout the user from CatecheSis. Ends the active session and redirects the user to the front page.
     * @param $ulogin - a uLogin object
     */
    public static function appLogout($ulogin)
    {
        // When a user explicitly logs out you'll definitely want to disable
        // autologin for the same user.
        $ulogin->SetAutologin($_SESSION['username'], false);

        unset($_SESSION['uid']);
        unset($_SESSION['username']);
        unset($_SESSION['loggedIn']);
        unset($_SESSION['nome_utilizador']);
        unset($_SESSION['admin']);
        unset($_SESSION['catequista']);

        Authenticator::denyAccess();
    }


    /**
     * Callback function to be run in case a login attempt fails in uLogin.
     * @param $uid
     * @param $username
     * @param $ulogin
     */
    public static function appLoginFail($uid, $username, $ulogin)
    {
        // Note, in case of a failed login, $uid, $username or both
        // might not be set (might be NULL).
        //Authenticator::denyAccess();
    }


    /**
     * Callback function to complete a user login, given the UID and username
     * (the password should have been already validated before calling this callback).
     * Denies the access if the user account was blocked by an administrator in CatecheSis.
     * @param $uid
     * @param $username
     * @param $ulogin - a uLogin object
     */
    public static function appLogin($uid, $username, $ulogin)
    {
        $_SESSION['uid'] = $uid;
        $_SESSION['username'] = $username;
        $_SESSION['loggedIn'] = true;
        $_SESSION['admin'] = false;
        $_SESSION['catequista'] = -1;
        $_SESSION['nome_utilizador'] = "";


        /*if (isset($_SESSION['appRememberMeRequested']) && ($_SESSION['appRememberMeRequested'] === true))
        {
            // Enable remember-me
            if ( !$ulogin->SetAutologin($username, true))
                Utils::error("Impossível activar autologin");

            unset($_SESSION['appRememberMeRequested']);
        }
        else
        {
            // Disable remember-me
            if ( !$ulogin->SetAutologin($username, false))
                Utils::error("Impossível desactivar autologin");
        }*/


        //Obter nome real, estado, admin e catequista
        $db = new PdoDatabaseManager();
        try
        {
            $userAccount = $db->getUserAccountStatus($username);

            if ($userAccount['account_status'] == 1)
            {
                $_SESSION['nome_utilizador'] = Utils::sanitizeOutput($userAccount['name']);
                $_SESSION['catequista'] = $userAccount['catechist_status'];
                $_SESSION['admin'] = $userAccount['admin'];

                if ($userAccount['admin'])
                {
                    database_cleanup();     // Executes periodic log cleanup, if necessary
                    check_for_updates();    // Checks for updates (if some conditions are met)
                }
            }
            else
            {//Conta bloqueada

                //Desfazer authentication
                $ulogin->SetAutologin($_SESSION['username'], false);

                unset($_SESSION['uid']);
                unset($_SESSION['username']);
                unset($_SESSION['loggedIn']);
                unset($_SESSION['nome_utilizador']);
                unset($_SESSION['admin']);
                unset($_SESSION['catequista']);

                Authenticator::denyAccess();
            }

        }
        catch (Exception $e)
        {
            //Desfazer authentication
            $ulogin->SetAutologin($_SESSION['username'], false);

            unset($_SESSION['uid']);
            unset($_SESSION['username']);
            unset($_SESSION['loggedIn']);
            unset($_SESSION['nome_utilizador']);
            unset($_SESSION['admin']);
            unset($_SESSION['catequista']);

            Authenticator::denyAccess();
        }
    }


    /**
     * Creates a uLogin nonce and returns its code.
     * This is needed in authentication forms.
     * @return false|string
     */
    public static function createNonce()
    {
        return \ulNonce::Create('fidedigno');
    }



    /**
     * Returns the system username of the currently logged in user.
     * @return mixed
     */
    public static function getUsername()
    {
        return $_SESSION['username'];
    }


    /**
     * Returns the full name of the currently logged in user.
     * @return mixed
     */
    public static function getUserFullName()
    {
        return $_SESSION['nome_utilizador'];
    }

    /**
     * Returns true if the user currently logged in is an administrator.
     */
    public static function isAdmin()
    {
        return isset($_SESSION['admin']) && $_SESSION['admin']==1;
    }

    /**
     * Returns true if the user currently logged in is a catechist.
     * @return bool
     */
    public static function isCatechist()
    {
        return isset($_SESSION['catequista']);
    }


    /**
     * Returns true if the user currently logged in is a catechist AND is set
     * as an active catechist.
     * @return bool
     */
    public static function isActiveCatechist()
    {
        return isset($_SESSION['catequista']) && $_SESSION['catequista']==1;
    }

    /**
     * Returns true if the user currently logged in is a catechist but is not
     * currently set as an active catechist.
     * NOTE: If the user is NOT a catechist at all, this function will return false.
     * @return bool
     */
    public static function isInactiveCatechist()
    {
        return isset($_SESSION['catequista']) && $_SESSION['catequista']==0;
    }
}