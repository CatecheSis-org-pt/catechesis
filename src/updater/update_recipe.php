<?php


/**
 * Update the updater itself.
 * This function should be run prior to any other update, to prepair the updater
 * to a new API in this file, for example.
 * @return bool
 */
function update_updater()
{
    $source_dir = __DIR__ . "/dummy_changes/src/updater"; //FIXME
    $dest_dir = constant('CATECHESIS_ROOT_DIRECTORY') . "updater";
    return SetupUtils\xcopy($source_dir, $dest_dir);
}

/**
 * Update the CatecheSis licenses and terms first.
 * These will be shown during the update wizard itself.
 * @return bool
 */
function update_licenses()
{
    $source_dir = __DIR__ . "/dummy_changes/src/licenses"; //FIXME
    $dest_dir = constant('CATECHESIS_ROOT_DIRECTORY') . "licenses";
    return SetupUtils\xcopy($source_dir, $dest_dir);
}


/**
 * Update the databasew schema.
 * Create new tables and/or columns, migrate/convert any data as necessary.
 * @return bool
 */
function update_database()
{
    $db_host = constant('CATECHESIS_HOST');
    $db_name = constant('CATECHESIS_DB');
    $db_user = constant('USER_LOG'); //constant('USER_DEFAULT_EDIT'); //  //FIXME
    $db_pass = constant('PASS_LOG'); //constant('PASS_DEFAULT_EDIT'); //  //FIXME

    return SetupUtils\run_sql_script($db_host, $db_name, $db_user, $db_pass, __DIR__ . "/dummy_update.sql"); //FIXME
}


/**
 * Update main application files.
 * @return bool
 */
function update_files()
{
    $source_dir = __DIR__ . "/dummy_changes/src"; //FIXME
    $dest_dir = constant('CATECHESIS_ROOT_DIRECTORY'); //__DIR__ . "/../";
    return SetupUtils\xcopy($source_dir, $dest_dir);
}


/**
 * Remove files that are not used anymore in the new version.
 * @return int|true
 */
function delete_obolete_files()
{
    $res = true;
    $delete_list_file = __DIR__ . "/dummy_changes/deleted_files.txt"; //FIXME
    foreach(file($delete_list_file, FILE_IGNORE_NEW_LINES) as $line)
    {
        // Each line contains one file to remove
        $filename = constant('CATECHESIS_ROOT_DIRECTORY') . $line; //realpath(constant('CATECHESIS_ROOT_DIRECTORY') . "/" . $line);
        if(file_exists($filename))
        {
            $res &= unlink($filename);
        }
    }

    return $res;
}


/**
 * Update the CatecheSis textual configuration files.
 * @return bool
 */
function update_configuration_files()
{
    $updated_main_config_file = __DIR__ . "/dummy_changes/src/core/config/catechesis_config.inc.template.php"; //FIXME
    if(file_exists($updated_main_config_file))
    {
        //Rewrite main config file

        //$main_config_file = constant('CATECHESIS_ROOT_DIRECTORY') . "core/config/catechesis_config.inc.php"; //FIXME
        $main_config_file = constant('CATECHESIS_ROOT_DIRECTORY') . "core/config/catechesis_config.updated.php"; //FIXME

        if(!SetupUtils\xcopy($updated_main_config_file, $main_config_file))
        {
            return false;
        }

        $main_settings = array();
        $main_settings['<CATECHESIS_DOMAIN>'] = constant('CATECHESIS_DOMAIN');
        $main_settings['<CATECHESIS_BASE_URL>'] = constant('CATECHESIS_BASE_URL');
        $main_settings['<CATECHESIS_ROOT_DIRECTORY>'] = constant('CATECHESIS_ROOT_DIRECTORY');
        $main_settings['<CATECHESIS_DATA_DIRECTORY>'] = constant('CATECHESIS_DATA_DIRECTORY');
        SetupUtils\replace_strings_in_file($main_config_file, $main_settings);
    }


    $updated_shadow_config_file = __DIR__ . "/dummy_changes/catechesis_data/config/catechesis_config.shadow.template.php"; //FIXME
    if(file_exists($updated_shadow_config_file))
    {
        //Rewrite shadow config file

        //$shadow_config_file = constant('CATECHESIS_DATA_DIRECTORY') . "/config/catechesis_config.shadow.php"; //FIXME
        $shadow_config_file = constant('CATECHESIS_DATA_DIRECTORY') . "/config/catechesis_config.shadow.updated.php"; //FIXME

        if(!SetupUtils\xcopy($updated_shadow_config_file, $shadow_config_file))
        {
            return false;
        }

        $shadow_settings = array();
        $shadow_settings['<CATECHESIS_UL_SITE_KEY>'] = constant('CATECHESIS_UL_SITE_KEY');
        $shadow_settings['<CATECHESIS_HOST>'] = constant('CATECHESIS_HOST');
        $shadow_settings['<CATECHESIS_DB>'] = constant('CATECHESIS_DB');

        global $HIGH_SECURITY;
        if($HIGH_SECURITY)
        {
            $shadow_settings['$HIGH_SECURITY = false;']  = "\$HIGH_SECURITY = true;";

            $shadow_settings['ul_auth_user'] = constant('USER_UL_AUTH');
            $shadow_settings['ul_update_user'] = constant('USER_UL_UPDATE');
            $shadow_settings['ul_delete_user'] = constant('USER_UL_DELETE');
            $shadow_settings['ul_session_user'] = constant('USER_UL_SESSION');
            $shadow_settings['ul_log_user'] = constant('USER_UL_LOG');

            $shadow_settings['cat_log_user'] = constant('USER_LOG');
            $shadow_settings['cat_log_cln_user'] = constant('USER_LOG_CLEAN');
            $shadow_settings['cat_read_user'] = constant('USER_DEFAULT_READ');
            $shadow_settings['cat_edit_user'] = constant('USER_DEFAULT_EDIT');
            $shadow_settings['cat_delete_user'] = constant('USER_DEFAULT_DELETE');
            $shadow_settings['cat_gp_mgt_user'] = constant('USER_GROUP_MANAGEMENT');
            $shadow_settings['cat_usr_mgt_user'] = constant('USER_USER_MANAGEMENT');
            $shadow_settings['cat_config_user'] = constant('USER_CONFIG');
            $shadow_settings['cat_online_enrollment_user'] = constant('USER_ONLINE_ENROLLMENT');

            $shadow_settings['secureimage_user'] = constant('USER_CAPTCHA');

            $shadow_settings['<PASS_UL_AUTH>'] = constant('PASS_UL_AUTH');
            $shadow_settings['<PASS_UL_UPDATE>'] = constant('PASS_UL_UPDATE');
            $shadow_settings['<PASS_UL_DELETE>'] = constant('PASS_UL_DELETE');
            $shadow_settings['<PASS_UL_SESSION>'] = constant('PASS_UL_SESSION');
            $shadow_settings['<PASS_UL_LOG>'] = constant('PASS_UL_LOG');

            $shadow_settings['<PASS_LOG>'] = constant('PASS_LOG');
            $shadow_settings['<PASS_LOG_CLEAN>'] = constant('PASS_LOG_CLEAN');
            $shadow_settings['<PASS_DEFAULT_READ>'] = constant('PASS_DEFAULT_READ');
            $shadow_settings['<PASS_DEFAULT_EDIT>'] = constant('PASS_DEFAULT_EDIT');
            $shadow_settings['<PASS_DEFAULT_DELETE>'] = constant('PASS_DEFAULT_DELETE');
            $shadow_settings['<PASS_GROUP_MANAGEMENT>'] = constant('PASS_GROUP_MANAGEMENT');
            $shadow_settings['<PASS_USER_MANAGEMENT>'] = constant('PASS_USER_MANAGEMENT');
            $shadow_settings['<PASS_CONFIG>'] = constant('PASS_CONFIG');
            $shadow_settings['<PASS_ONLINE_ENROLLMENT>'] = constant('PASS_ONLINE_ENROLLMENT');

            $shadow_settings['<PASS_CAPTCHA>'] = constant('PASS_CAPTCHA');
        }
        else
        {
            $shadow_settings['$HIGH_SECURITY = true;']  = "\$HIGH_SECURITY = false;";

            $shadow_settings['<DB_ROOT_USER>'] = constant('USER_UL_AUTH');
            $shadow_settings['<DB_ROOT_PASSWORD>'] = constant('PASS_UL_AUTH');
        }

        SetupUtils\replace_strings_in_file($shadow_config_file, $shadow_settings);
    }

    $updated_htaccess_file = __DIR__ . "/dummy_changes/src/.htaccess"; //FIXME
    if(file_exists($updated_htaccess_file))
    {
        //$htaccess_file = constant('CATECHESIS_ROOT_DIRECTORY') . ".htaccess"; //FIXME
        $htaccess_file = constant('CATECHESIS_ROOT_DIRECTORY') . "updated.htaccess"; //FIXME

        // Write URLs for error pages in main .htaccess file
        $error_pages = array();
        $error_pages['<ERROR_PAGE_404>'] = constant('CATECHESIS_BASE_URL') . '/erro404.php';
        $error_pages['<ERROR_PAGE_400>'] = constant('CATECHESIS_BASE_URL') . '/erro500.html';
        $error_pages['<ERROR_PAGE_500>'] = constant('CATECHESIS_BASE_URL') . '/erro500.html';
        SetupUtils\xcopy($updated_htaccess_file, $htaccess_file);
        SetupUtils\replace_strings_in_file($htaccess_file, $error_pages);
    }


    return true;
}


/**
 * Main script to execute this update in a shell (without the wizard GUI).
 * @return void
 */
function main()
{
    //Execute this when calling the script in a shell
}

?>