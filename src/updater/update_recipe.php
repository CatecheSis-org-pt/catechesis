<?php

error_reporting(E_ALL | E_STRICT);

function update_database()
{
    $db_host = constant('CATECHESIS_HOST');
    $db_name = constant('CATECHESIS_DB');
    $db_user = constant('USER_LOG'); //constant('USER_DEFAULT_EDIT'); //  //FIXME
    $db_pass = constant('PASS_LOG'); //constant('PASS_DEFAULT_EDIT'); //  //FIXME

    return SetupUtils\run_sql_script($db_host, $db_name, $db_user, $db_pass, __DIR__ . "/dummy_update.sql"); //FIXME
}


function update_files()
{
    $source_dir = __DIR__ . "/dummy_changes/src"; //FIXME
    $dest_dir = constant('CATECHESIS_ROOT_DIRECTORY'); //__DIR__ . "/../";
    return SetupUtils\xcopy($source_dir, $dest_dir);
}


function delete_obolete_files()
{
    $res = true;
    $delete_list_file = __DIR__ . "/dummy_changes/deleted_files.txt"; //FIXME
    foreach(file($delete_list_file, FILE_IGNORE_NEW_LINES) as $line)
    {
        // Each line contains one file to remove
        $filename = constant('CATECHESIS_ROOT_DIRECTORY') . $line; //realpath(constant('CATECHESIS_ROOT_DIRECTORY') . "/" . $line);
        file_put_contents(__DIR__ . "/debug.txt", "Removing $filename\n");
        if(file_exists($filename))
        {
            file_put_contents(__DIR__ . "/debug.txt", "Removing $filename\n");
            $res &= unlink($filename);
        }
        else
        {
            file_put_contents(__DIR__ . "/debug.txt", "File $filename not found\n");
        }
    }

    return $res;
}

function update_configuration_files()
{
    return true;
}


function main()
{
    //Execute this when calling the script in a shell
}

?>