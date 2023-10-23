<?php

require_once(__DIR__ . '/../core/UpdateChecker.php');
use catechesis\UpdateChecker;


function check_for_updates()
{
    if(true) //FIXME Condition to avoid checking on every login (e.g. once in a week, and no more than once in the same day)
    {
        /**
         * Store the information about available update in session variables,
         * to reuse during the current session and avoid further API calls.
         */

        $updater = new UpdateChecker();

        $_SESSION['IS_UPDATE_AVAILABLE'] = $updater->isUpdateAvailable();
        $_SESSION['LATEST_AVAILABLE_VERSION'] = $updater->getLatestVersion();
        $_SESSION['CURRENT_VERSION'] = $updater->getCurrentVersion();
        $_SESSION['UPDATE_CHANGELOG_URL'] = $updater->getChangelogUrl();
    }
    else
    {
        $_SESSION['IS_UPDATE_AVAILABLE'] = false;
    }
}