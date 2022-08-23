# Installation script testing

This document provides some tricks and haks that may be useful when debugging the CatecheSis setup wizard (```src/setup/index.php```).

## Tips

- Do **NOT** test this on the docker-compose setup that is provided with this repo. That one is for development purposes. 
The `setup` folder in your repo would be deleted afterwards.

- **DO** use a separate Docker container or an actual installation of Apache+MySQL and copy a release package (see [Creating_release_package.md](Creating_release_package.md)) to that Apache root folder, in order to test the setup wizard.

- When you copy and extract the CatecheSis package into the Apache root folder, check that the user running Apache/PHP (or all the users) have write permisisons on that folder and subfolders.

- You have to manually create an empty database in MySQL, and then fill its name when the wizards asks for it;

- The setup wizard *requires* the web server to use HTTPS. For testing purposes, you can skip that requirement by commenting out the following lines in `src/setup/index.php`:

    ```php
    // Check HTTPS
    if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
    {
        $reqs_satisfied = false;
        $https_pass = false;
    }
    ```
    These are located in the first part of the script, before the `<!DOCTYPE html>` and the actual HTML code begins.

    <br>

    Moreover, when the wizard reaches the page where you create an admin user, you have to manually edit the file `<catechesis_root>/core/config/catechesis_config.inc.php` and change the following constant to *false* **before** submitting the form:
    
    ```php
    define('CATECHESIS_HTTPS', false);
    ```


