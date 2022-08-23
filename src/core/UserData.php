<?php

namespace catechesis;
require_once(__DIR__ . '/config/catechesis_config.inc.php');

use Exception;
use finfo;

/**
 * An utility class providing methods to access user generated content,
 * such as catechumens photos, uploaded documents, or parish logo.
 */
class UserData
{
    private const CATECHUMEN_PHOTOS_DIR = 'photos/catechumens';
    private const PARISH_LOGOS_DIR = 'photos/parish';
    private const UPLOAD_DOCUMENTS_DIR = 'documents';
    private const TEMP_DIR = 'tmp';

    /**
     * Returns the root directory where user content is stored.
     * @return mixed
     */
    public static function getDataDirectoryRoot()
    {
        $data_root_dir = constant('CATECHESIS_DATA_DIRECTORY');
        if (substr($data_root_dir, -1) != '/')
            $data_root_dir = $data_root_dir . "/";

        return $data_root_dir;
    }


    /**
     * Returns the directory where catechumens photos are stored.
     * @return string
     */
    public static function getCatechumensPhotosFolder()
    {
        return self::getDataDirectoryRoot() . self::CATECHUMEN_PHOTOS_DIR;
    }

    /**
     * Returns the directory where user uploaded documents are saved.
     * @return string
     */
    public static function getUploadDocumentsFolder()
    {
        return self::getDataDirectoryRoot() . self::UPLOAD_DOCUMENTS_DIR;
    }


    /**
     * Returns the path to a directory where temporary files can be saved,
     * outside the public_html folder.
     * @return string
     */
    public static function getTempFolder()
    {
        return self::getDataDirectoryRoot() . self::TEMP_DIR;
    }


    /**
     * Returns the path to the file containing the parish logo.
     * If $absolute=true, returns the full path. Otherwise, returns a relative path from the data directory root.
     * @param bool $absolute
     * @return string
     */
    public static function getParishLogoFile(bool $absolute=true)
    {
        $path = self::PARISH_LOGOS_DIR . '/parish_logo.png';
        if($absolute)
            return self::getDataDirectoryRoot() . $path;
        else
            return $path;
    }

    /**
     * Returns the path to the file containing the parish public image.
     * If $absolute=true, returns the full path. Otherwise, returns a relative path from the data directory root.
     * @param bool $absolute
     * @return string
     */
    public static function getParishPublicImageFile(bool $absolute=true)
    {
        $path = self::PARISH_LOGOS_DIR . '/index.jpg';
        if($absolute)
            return self::getDataDirectoryRoot() . $path;
        else
            return $path;
    }

    /**
     * Return the URL, relative to the CatecheSis root, to use (in the frontend) to fetch the parish custom image
     * used in the public index page.
     * Note that this returns a URL to get the image, not the image itself.
     * @return string
     */
    public static function getParishCustomFrontPageImageQueryURL()
    {
        return "resources/parishPublicPicture.php";
    }

    /**
     * Return the URL, relative to the CatecheSis root, to use (in the frontend) to fetch the parish logo.
     * Note that this returns a URL to get the image, not the image itself.
     * @return string
     */
    public static function getParishLogoQueryURL()
    {
        return "resources/parishLogo.php";
    }


    /**
     * Saves the provided catechumens image into a file.
     * Returns the path of the file where the photo was saved, relative to the CatecheSis user data directory.
     * @throws Exception
     */
    public static function saveUploadedCatechumenPhoto(string $imageData, string $filename = null)
    {
        $path = self::saveUploadedPhoto($imageData, self::CATECHUMEN_PHOTOS_DIR, $filename, ["image/jpeg", "image/png"]);

        $pathInfo = pathinfo($path); // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
        return $pathInfo['basename'];
    }


    /**
     * Saves the provided parish logo image into a file.
     * Returns the path of the file where the photo was saved, relative to the CatecheSis user data directory.
     * @throws Exception
     */
    public static function saveUploadedParishLogo(string $imageData)
    {
        self::saveUploadedPhoto($imageData, self::PARISH_LOGOS_DIR, "parish_logo.png", ["image/png"]);
    }


    /**
     * Saves the provided parish picture into a file.
     * Returns the path of the file where the photo was saved, relative to the CatecheSis user data directory.
     * @throws Exception
     */
    public static function saveUploadedParishPublicPicture(string $imageData)
    {
        self::saveUploadedPhoto($imageData, self::PARISH_LOGOS_DIR, "index.jpg", ["image/jpeg"]);
    }



    /**
     * Checks that the provided base64 data is an image and saves it.
     * Returns the path of the file where the photo was saved, relative to the CatecheSis user data directory.
     * @param string $imageData         - Image contents, encoded in base64 (usually from an HTML form).
     * @param string $directory         - Directory name, under Catechesis data folder, where this image will be stored
     * @param string|null $filename     - [Optional] Name of the file to store the image, including extension. If none is provided, a unique name will be automatically generated.
     * @param array $allowedTypes       - [Optional] Accept only these MIME types. By default, the MIME types corresponding to JPEG and PNG images are accepted.
     * @return string|null
     * @throws Exception
     */
    public static function saveUploadedPhoto(string $imageData, string $directory, string $filename = null, array $allowedTypes = ["image/jpeg", "image/png"]): ?string
    {
        $MAX_SIZE = 5 * (1024 * 1024); // 5 MB

        if ($imageData == null || $imageData == '')
            return "";

        //Remove the form submission preamble
        $imageData = preg_replace("#^data:image/[^;]+;base64,#", "", $imageData);

        // Verificar que o tamanho nao excede o permitido
        $encoded_data = $imageData;
        if (strlen($encoded_data) > $MAX_SIZE)
        {
            throw new \Exception("O tamanho da fotografia excede o máximo permitido. A fotografia não foi guardada.");
        }

        $binary_data = base64_decode($encoded_data);

        // Check the uploaded image has one of the allowed formats, using magic numbers
        $file_info = new finfo(FILEINFO_MIME);
        $mime_type = $file_info->buffer($binary_data);
        $valid_format = false;
        foreach ($allowedTypes as $type)
        {
            if (strpos($mime_type, $type) !== false)
            {
                //OK
                $valid_format = true;
                break;
            }
        }
        if (!$valid_format)
        {
            throw new \Exception("Os dados submetidos no campo fotografia não correspondem a uma imagem, ou o formato não é aceite.");
        }

        // Create a unique filename, if none is provided
        if ($filename == null)
            $filename = Utils::secureRandomString(8) . Utils::convertMimeTypeToFileExtension($mime_type);

        $filename = $directory . "/" . $filename;
        $result = file_put_contents(self::getDataDirectoryRoot() . $filename, $binary_data);

        if (!$result)
        {
            throw new \Exception("Não foi possível guardar a fotografia no servidor.");
        }

        return $filename;
    }
}