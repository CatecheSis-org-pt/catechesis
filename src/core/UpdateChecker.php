<?php

namespace catechesis;
require_once(__DIR__ . '/Configurator.php');
require_once(__DIR__ . '/version_info.php');

use catechesis\Configurator;


/**
 * Encapsulates requests to the catechesis.org.pt API to check for updates.
 */
class UpdateChecker
{
    private /*bool*/   $_isUpdateAvailable;
    private /*string*/ $_latestVersion;
    private /*string/ $_downloadUrl;

    private /*string*/ $_changelogUrl;


    public function __construct()
    {
        $this->checkForUpdates();
    }


    public function getCurrentVersion()
    {
        // Current version stored in version_info.php
        return constant("VERSION_STRING");
    }

    public function isUpdateAvailable()
    {
        return $this->_isUpdateAvailable;
    }

    public function getLatestVersion()
    {
        return $this->_latestVersion;
    }

    public function getDownloadUrl()
    {
        return $this->_downloadUrl;
    }

    public function getChangelogUrl()
    {
        return $this->_changelogUrl;
    }



    private function checkForUpdates()
    {
        $url = 'https://catechesis.org.pt/api/update_info.php';
        $data = ['installed_version' => $this->getCurrentVersion(),
                 'parish' => Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME),
                 'diocese' => Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_DIOCESE),
                 'locality' => Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_PLACE)
                ];

        // use key 'http' even if you send the request to https://...
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                            "User-Agent: CatecheSis\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === false)
        {
            /* Handle error */
            $this->_isUpdateAvailable = false;
        }
        else
        {
            $response = json_decode($response, true);

            switch($response['status'])
            {
                case "update available":
                    $this->_isUpdateAvailable = true;
                    $this->_latestVersion = $response["update_version"];
                    $this->_downloadUrl = $response["download_url"];
                    $this->_changelogUrl = $response["changelog"];
                    break;

                default:
                case "no update available":
                case "already up to date":
                    $this->_isUpdateAvailable = false;
                    break;
            }
        }
    }

}