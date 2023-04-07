<?php

namespace catechesis;

require_once(__DIR__ . '/Configurator.php');
require_once(__DIR__ . '/domain/Locale.php');

use core\domain\Locale;
use core\domain\Marriage;
use core\domain\Sacraments;
use catechesis\Configurator;
use Exception;
use DateTime;
use finfo;

/**
 * Class Utils
 * Provides a set of general utility functions.
 * @package catechesis
 */
class Utils
{
    /**
     * Returns the first name of a person, given his/her full name.
     * @param $nome_completo
     * @return mixed|string
     */
    public static function firstName(string $nome_completo)
    {
        $nomes = explode(" ", $nome_completo);
        return $nomes[0];
    }

    /**
     * Returns the first and last name of a person, given his/her full name.
     * @param $nome_completo
     * @return mixed|string
     */
    public static function firstAndLastName(string $nome_completo)
    {
        $nomes = explode(" ", $nome_completo);

        if (count($nomes) > 1)
            return $nomes[0] . " " . $nomes[sizeof($nomes) - 1];
        else
            return $nomes[0];
    }

    /**
     * Returns the last name of a person, given its full name.
     * @param $nome_completo
     * @return mixed|string
     */
    public static function lastName($nome_completo)
    {
        $nomes = explode(" ", $nome_completo);
        return $nomes[sizeof($nomes)-1];
    }

    /**
     * Returns the initials of a user given his full name.
     * @param string $full_name
     * @return string
     */
    public static function userInitials(string $full_name)
    {
        $names = explode(" ", self::firstAndLastName($full_name));
        $initials = "";

        foreach ($names as $w)
            $initials .= $w[0];

        return strtoupper($initials);
    }

    /**
     * Removes the leading "Parish of ..." prefix in strings designating a parish.
     * @param string $parish
     * @return array|string|string[]
     */
    private static function removeParishPrefix(string $parish)
    {
        $parish = str_replace("Paróquia de ", "", $parish);
        $parish = str_replace("paróquia de ", "", $parish);
        $parish = str_replace("Paróquia do ", "", $parish);
        $parish = str_replace("paróquia do ", "", $parish);
        $parish = str_replace("Paróquia da ", "", $parish);
        $parish = str_replace("paróquia da ", "", $parish);
        $parish = str_replace("Paróquia", "", $parish);
        $parish = str_replace("paróquia", "", $parish);

        return $parish;
    }

    /**
     * Decides if a sacrament took place in our parish, or in another parish, or none.
     * Returns:
     *  1 - if it took place in our parish
     *  2 - if it took place in another parish
     *  0 - in case of error
     * @param $parish
     * @return int
     */
    public static function sacramentParish(string $parish = null)
    {
        if ($parish && $parish != "")
        {
            //Remove the word "parish" from the strings and convert to lowercase
            $query_parish = strtolower($parish);
            $query_parish = self::removeParishPrefix($query_parish);

            $this_parish_name = strtolower(Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME));
            $this_parish_name = self::removeParishPrefix($this_parish_name);

            $this_parish_place = strtolower(Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_PLACE));
            $this_parish_place = self::removeParishPrefix($this_parish_place);

            if ((strpos($query_parish, $this_parish_name) !== false) ||
                (strpos($query_parish, $this_parish_place) !== false))
                return 1; // Our parish
            else
                return 2; // Other parish
        }
        else
            return 0; // None
    }


    /**
     * Computes the current catechetical year in the format '20152016'.
     *
     * @return false|float|int|string
     */
    public static function currentCatecheticalYear(string $date = null)
    {
        return Utils::computeCatecheticalYear(null);
    }

    /**
     * Computes the catechetical year in the format '20152016', given a date.
     * If no date is given, the current catechetical year is returned.
     *
     * @return false|float|int|string
     */
    public static function computeCatecheticalYear(string $date = null)
    {
        $ano_actual = date("Y");
        $mes_actual = date("m");

        if(!is_null($date))
        {
            $dateObj = DateTime::createFromFormat("d-m-Y", $date);
            $ano_actual = $dateObj->format("Y");
            $mes_actual = $dateObj->format("m");
        }

        if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::PORTUGAL)
        {
            if ($mes_actual >= 7)    //De Julho a Dezembro
                return $ano_actual * 10000 + ($ano_actual + 1);
            else
                return ($ano_actual - 1) * 10000 + $ano_actual;
        }
        else //if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)
        {
            //De Março a Dezembro
            return $ano_actual * 10000 + $ano_actual;
        }
    }

    /**
     * Returns the starting civil year from an enconded catechetical year.
     * E.g. For catechetical year '20202021' returns 2020.
     * @param int $catecheticalYear
     * @return int
     */
    public static function getCatecheticalYearStart(int $catecheticalYear)
    {
        return intval($catecheticalYear / 10000);
    }

    /**
     * Returns the ending civil year from an enconded catechetical year.
     * E.g. For catechetical year '20202021' returns 2021.
     * @param int $catecheticalYear
     * @return int
     */
    public static function getCatecheticalYearEnd(int $catecheticalYear)
    {
        return intval($catecheticalYear % 10000);
    }


    /**
     * Transforms an internal representation of a catechetical year (e.g. '20202021')
     * into the external usual textual representation (e.g. '2020/2021').
     * @param $catecheticalYear
     * @return string
     */
    public static function formatCatecheticalYear(int $catecheticalYear)
    {
        if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::PORTUGAL)
            return Utils::getCatecheticalYearStart($catecheticalYear) . "/" . Utils::getCatecheticalYearEnd($catecheticalYear);
        else //if(Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) == Locale::BRASIL)
            return "" . Utils::getCatecheticalYearStart($catecheticalYear);
    }


    /**
     * Sanitizes input data.
     * @param $data
     * @return string
     */
    public static function sanitizeInput(string $data = null)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }


    /**
     * Sanitizes output data (to protect against second-order injections).
     * @param $data
     * @return string
     */
    public static function sanitizeOutput(string $data = null)
    {
        $data = trim($data);
        $data = stripslashes($data);
        //$data = strip_tags($data);
        $data = htmlspecialchars($data);
        return $data;
    }


    /**
     * Sanitizes input/output data that is HTML formatting.
     * Keeps the formatting tags, and escapes everything else that might be dangerous.
     * @param string $data
     * @return string
     */
    public static function sanitizeKeepFormattingTags(string $data)
    {
        $data = trim($data);
        $data = strip_tags($data, "<i><b><u><br><p><span><small><strong><em>");
        return $data;
    }


    /**
     * Removes white space characters and tabs from a string.
     * @param string $value
     * @return array|string|string[]
     */
    public static function removeWhiteSpaces(string $value)
    {
        return str_replace("\t", "", str_replace(" ", "", $value));
    }

    /**
     * Removes white space characters from a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function removeLineManipulators(string $str)
    {
        return str_replace("\t", "", str_replace("\r", "", str_replace("\n", "", $str)));
    }

    /**
     * Escapes white space characters in a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function escapeLineManipulators(string $str)
    {
        return str_replace("\t", "\\t", str_replace("\r", "\\r", str_replace("\n", "\\n", $str)));
    }


    /**
     * Escapes white espace characters in a string and also escapes the escape characters introduced,
     * so that this can be safely rendered inside a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function doubleEscapeWhiteSpaces(string $str)
    {
        return str_replace("\\t", "\\\\t", str_replace("\\r", "\\\\r", str_replace("\\n", "\\\\n", $str)));

    }

    /**
     * Escapes the double quote character in a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function escapeDoubleQuotes(string $str)
    {
        return str_replace("\"", "\\\"", $str);
    }

    /**
     * Escapes the double quote character in a string, and also escapes the escape characters introduced,
     * so that this can be safely rendered inside a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function doubleEscapeDoubleQuotes(string $str)
    {
        return str_replace("\\\"", "\\\\\"", $str);
    }

    /**
     * Escapes the double quote character with an HTML &quot; character in a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function doubleEscapeDoubleQuotesHTML(string $str)
    {
        return str_replace("\\\"", "&quot;", $str);
    }

    /**
     * Escapes the single quote character from a string.
     * @param $str
     * @return array|string|string[]
     */
    public static function escapeSingleQuotes(string $str)
    {
        return str_replace("'", "\\'", $str);
    }




    /**
     * Converts basic HTML markup into Word 2007 (.docx) XML markup.
     * @param string $str
     * @return array|string|string[]
     */
    public static function convertHtmlMarkupToWord(string $str)
    {
        $res = $str;
        $res = str_replace("<strong>", "<b>", $res);
        $res = str_replace("</strong>", "</b>", $res);
        $res = str_replace("<em>", "<i>", $res);
        $res = str_replace("</em>", "</i>", $res);
        $res = str_replace("</p><p>", "<br>", $res);
        $res = str_replace("<p>", "", $res);
        $res = str_replace("</p>", "", $res);
        //$res= str_replace('<br>', '<w:br/>', $res);

        return self::f_html2docx(self::f_handleUnsupportedTags($res));
    }

    /**
     * Auxiliary function to convertHtmlMarkupToWord().
     * @param $currVal
     * @return array|string|string[]
     */
    private static function f_html2docx($currVal)
    {
        // handling <i> tag
        $el = 'i';
        $tag_open  = '<' . $el . '>';
        $tag_close = '</' . $el . '>';
        $nb = substr_count($currVal, $tag_open);

        if ( ($nb > 0) && ($nb == substr_count($currVal, $tag_open)) ) {
            $currVal= str_replace($tag_open,  '</w:t></w:r><w:r><w:rPr><w:i/></w:rPr><w:t>', $currVal);
            $currVal= str_replace($tag_close, '</w:t></w:r><w:r><w:t>', $currVal);
        }

        // handling <b> tag
        $el = 'b';
        $tag_open  = '<' . $el . '>';
        $tag_close = '</' . $el . '>';
        $nb = substr_count($currVal, $tag_open);

        if ( ($nb > 0) && ($nb == substr_count($currVal, $tag_open)) ) {
            $currVal= str_replace($tag_open,  '</w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t>', $currVal);
            $currVal= str_replace($tag_close, '</w:t></w:r><w:r><w:t>', $currVal);
        }

        // handling <u> tag
        $el = 'u';
        $tag_open  = '<' . $el . '>';
        $tag_close = '</' . $el . '>';
        $nb = substr_count($currVal, $tag_open);

        if ( ($nb > 0) && ($nb == substr_count($currVal, $tag_open)) ) {
            $currVal= str_replace($tag_open,  '</w:t></w:r><w:r><w:rPr><w:u w:val="single"/></w:rPr><w:t>', $currVal);
            $currVal= str_replace($tag_close, '</w:t></w:r><w:r><w:t>', $currVal);
        }

        // handling <br> tag
        $el = 'br';
        //$currVal= str_replace('<br />', '<w:br/>', $currVal);
        $currVal= str_replace('<br />', '</w:t><w:br/><w:t xml:space="preserve">', $currVal);

        return $currVal;
    }

    /**
     * Auxiliary function to convertHtmlMarkupToWord().
     * @param $fieldValue
     * @return array|string|string[]
     */
    private static function f_handleUnsupportedTags($fieldValue)
    {
        $fieldValue = strip_tags($fieldValue, '<b><i><u><br>');

        $fieldValue = str_replace('&nbsp;',' ',$fieldValue);
        $fieldValue = str_replace('<br>','<br />',$fieldValue);

        return $fieldValue;
    }

    /**
     * Encodes a string in UTF-8 format, if it is not encoded in that format already.
     * @param $str
     * @return mixed|string
     */
    public static function toUTF8(string $str)
    {
        if (preg_match('!!u', $str))
            return $str;
        else
            return utf8_encode($str);
    }

    /**
     * Converts an alphabet character to a number.
     * Ex: 'A' is 1, 'B' is 2, ...
     */
    public static function toNumber(string $char)
    {
        if ($char)
            return ord(strtolower($char)) - ord('a');
        else
            return 0;
    }

    /**
     * Generates a cryptographically-safe random hexadecimal "string" with the number of bytes specified in the intput.
     * @param int $length
     * @return false|string
     * @throws Exception
     */
    public static function secureRandomString(int $length = 8)
    {
        $token = null;

        if (function_exists('openssl_random_pseudo_bytes'))
        {
            $token = bin2hex(openssl_random_pseudo_bytes($length));
            //echo("<!-- openssl token -->");
        }
        else if (function_exists('mcrypt_create_iv'))
        {
            $token = bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
            //echo("<!-- mcrypt token -->")
        }
        else if (function_exists('random_bytes'))
        {
            $token = bin2hex(random_bytes($length));
            //echo("<!-- random_bytes token -->");
        }
        else
            return false; // Nao existe nenhuma funcao criptografica para gerar um token seguro.


        return $token;
    }


    /**
     * Interrupts a script and returns an error message in the form of an HTML paragraph.
     * @param string $msg
     */
    public static function error(string $msg)
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo("<p>" . $msg . "</p>");
        die();
    }


    /**
     * Returns the full URL of the page requested by the user, including query parameters.
     * @return string
     */
    public static function getCurrentPageURL()
    {
        $protocol = "";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $protocol = "https";
        else
            $protocol = "http";

        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
    }

    /**
     * Returns the BASE of the public URL of the page invoking this function.
     * E.g. if the page invoking this function is /myproject/index.php, this function returns
     *  something like https://localhost/myproject/ (ending with a slash, without the index.php).
     * @return string
     */
    public static function getBaseUrl()
    {
        // output: /myproject/index.php
        $currentPath = $_SERVER['PHP_SELF'];

        // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
        $pathInfo = pathinfo($currentPath);

        // output: localhost
        $hostName = $_SERVER['HTTP_HOST'];

        $protocol = "";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $protocol = "https";
        else
            $protocol = "http";

        // return: http://localhost/myproject/
        $baseUrl = $protocol . '://' . $hostName . $pathInfo['dirname'];
        if (substr($baseUrl, -1) != '/')
            $baseUrl = $baseUrl . "/";

        return $baseUrl;
    }


    /**
     * Converts a textual (external) representation of a union type into the corresponding internal class.
     * @param string $married_how
     * @return int
     */
    public static function marriageTypeFromString(string $married_how)
    {
        switch($married_how)
        {
            case "igreja":
                return Marriage::CHURCH;

            case "civil":
                return Marriage::CIVIL;

            case "uniao de facto":
                return Marriage::DE_FACTO_UNION;

            default:
                return -1;
        }
    }


    /**
     * Given a MIME type, returns the usual file extension corresponding to that MIME type.
     * e.g. The MIME type "image/jpeg" corresponds to files with extension ".jpg".
     * @param $mime_type
     * @return string
     */
    public static function convertMimeTypeToFileExtension($mime_type)
    {
        $extensions = array('image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif'
        );

        foreach($extensions as $type => $extension)
        {
            if (strpos($mime_type, $type) !== false)
                return "." . $extension;
        }

        return ""; //Unknown
    }


    /**
     * Returns a string greeting the user, adjusted to the time of the day.
     * @param $username - The user name, to include in the greeting string.
     * @return string
     */
    public static function greeting($username): string
    {
        date_default_timezone_set('Europe/Lisbon'); //FIXME Make this a configurable setting
        $hour = date('H');

        if($hour >= 2 && $hour < 7)
            return "A madrugar, $username?";
        else if($hour >= 7 && $hour < 12)
            return "Bom dia, $username!";
        else if($hour >= 12 && $hour < 20)
            return "Boa tarde, $username!";
        else if($hour >= 20 || $hour < 2)
            return "Boa noite, $username!";
    }


    /**
     * Executes a PHP file and outputs the resulting HTML to a string.
     * An optional array may be passed with values that can be used inside that PHP script.
     * @param $path
     * @param array $args
     * @return false|string
     */
    public static function renderPhp($path, array $args=array())
    {
        ob_start();
        include($path);
        $var=ob_get_contents();
        ob_end_clean();
        return $var;
    }


    /**
     * Scales an image mantaining aspect ratio.
     * @param $image_name - Path of the original image
     * @param $outputImageFile - Path of the rescaled image
     * @param $new_width - Width of the resized photo (maximum)
     * @param $new_height - Height of the resized photo (maximum)
     * @return bool
     */
    public static function resizeKeepAspectRatio($inputImageFile, $outputImageFile, $new_width, $new_height)
    {
        $path = $inputImageFile;

        $mime = getimagesize($path);

        if($mime['mime']=='image/png') {
            $src_img = imagecreatefrompng($path);

            //Convert transparency to white
            $white_background = imagecolorallocate($src_img, 255, 255, 255);
            imagefill($src_img,0,0,$white_background);
        }
        if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
            $src_img = imagecreatefromjpeg($path);
        }

        $old_x          =   imageSX($src_img);
        $old_y          =   imageSY($src_img);

        if($old_x > $old_y)
        {
            $thumb_w    =   $new_width;
            $thumb_h    =   $old_y*($new_height/$old_x);
        }

        if($old_x < $old_y)
        {
            $thumb_w    =   $old_x*($new_width/$old_y);
            $thumb_h    =   $new_height;
        }

        if($old_x == $old_y)
        {
            $thumb_w    =   $new_width;
            $thumb_h    =   $new_height;
        }

        $dst_img        =   ImageCreateTrueColor($thumb_w,$thumb_h);

        imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);


        // New save location
        $new_thumb_loc = $outputImageFile;

        if($mime['mime']=='image/png') {
            $result = imagepng($dst_img, $new_thumb_loc,8);
        }
        if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg') {
            $result = imagejpeg($dst_img, $new_thumb_loc,80);
        }

        imagedestroy($dst_img);
        imagedestroy($src_img);

        return $result;
    }


    /**
     * Scales an image and crops it to a new aspect ratio.
     * @param $image_name - Path of the original image
     * @param $outputImageFile - Path of the rescaled image
     * @param $new_width - Width of the resized photo (maximum)
     * @param $new_height - Height of the resized photo (maximum)
     * @return bool
     */
    public static function resizeCrop($inputImageFile, $outputImageFile, $new_width, $new_height)
    {
        $max_width = $new_width;
        $max_height = $new_height;
        $image_temp = $inputImageFile;

        $image_size_info = getimagesize($image_temp);
        $image_width = $image_size_info[0];
        $image_height = $image_size_info[1];

        $image_res = imagecreatefrompng($image_temp); //imagecreatefromjpeg($image_temp);

        if ($image_height < $image_width)
        {
            $cropfactor = $image_height / $image_width;
            $cropfactor = ($cropfactor > 0.75) ? $cropfactor : 0.75;
            $max_height = ceil($max_height * $cropfactor);
        }
        elseif ($image_width < $image_height)
        {
            $cropfactor = $image_width / $image_height;
            $cropfactor = ($cropfactor > 0.75) ? $cropfactor : 0.75;
            $max_width = ceil($max_width * $cropfactor);
        }

        $new_width = $image_height * $max_width / $max_height;
        $new_height = $image_width * $max_height / $max_width;

        $canvas = imagecreatetruecolor($max_width, $max_height);

        if ($new_width > $image_width)
        {
            $cut_x = 0;
            $cut_y = (($image_height - $new_height) / 2);
            $new_width_canvas = $image_width;
            $new_height_canvas = $new_height;
        }
        else
        {
            $cut_x = (($image_width - $new_width) / 2);
            $cut_y = 0;
            $new_width_canvas = $new_width;
            $new_height_canvas = $image_height;
        }

        imagecopyresampled($canvas, $image_res, 0, 0, $cut_x, $cut_y, $max_width, $max_height, $new_width_canvas, $new_height_canvas);
        //imagejpeg($canvas, $outputImageFile, 85);
        imagepng($canvas, $outputImageFile);
        imagedestroy($image_res);
    }


    /**
     * Scales an image and crops it to a new aspect ratio, adding a letterbox if necessary.
     * @param string $source_image  - Path of the original image
     * @param string $outputImageFile - Path of the rescaled image
     * @param int $destination_width -  Width of the resized photo
     * @param int $destination_height - Height of the resized photo
     * @param int $type - Operation type. 1=Crop 2=Letterbox
     * @return void
     */
    public static function resizeLetterbox(string $source_image, string $outputImageFile, int $destination_width, int $destination_height, int $type = 2)
    {
        $mime = getimagesize($source_image);

        if($mime['mime']=='image/png')
        {
            $src_img = imagecreatefrompng($source_image);

            //Convert transparency to white
            $white_background = imagecolorallocate($src_img, 255, 255, 255);
            imagefill($src_img,0,0,$white_background);
        }
        if($mime['mime']=='image/jpg' || $mime['mime']=='image/jpeg' || $mime['mime']=='image/pjpeg')
        {
            $src_img = imagecreatefromjpeg($source_image);
        }

        // $type (1=crop to fit, 2=letterbox)
        $source_width = imagesx($src_img);
        $source_height = imagesy($src_img);
        $source_ratio = $source_width / $source_height;
        $destination_ratio = $destination_width / $destination_height;
        if ($type == 1) {
            // crop to fit
            if ($source_ratio > $destination_ratio) {
                // source has a wider ratio
                $temp_width = (int)($source_height * $destination_ratio);
                $temp_height = $source_height;
                $source_x = (int)(($source_width - $temp_width) / 2);
                $source_y = 0;
            } else {
                // source has a taller ratio
                $temp_width = $source_width;
                $temp_height = (int)($source_width / $destination_ratio);
                $source_x = 0;
                $source_y = (int)(($source_height - $temp_height) / 2);
            }
            $destination_x = 0;
            $destination_y = 0;
            $source_width = $temp_width;
            $source_height = $temp_height;
            $new_destination_width = $destination_width;
            $new_destination_height = $destination_height;
        } else {
            // letterbox
            if ($source_ratio < $destination_ratio) {
                // source has a taller ratio
                $temp_width = (int)($destination_height * $source_ratio);
                $temp_height = $destination_height;
                $destination_x = (int)(($destination_width - $temp_width) / 2);
                $destination_y = 0;
            } else {
                // source has a wider ratio
                $temp_width = $destination_width;
                $temp_height = (int)($destination_width / $source_ratio);
                $destination_x = 0;
                $destination_y = (int)(($destination_height - $temp_height) / 2);
            }
            $source_x = 0;
            $source_y = 0;
            $new_destination_width = $temp_width;
            $new_destination_height = $temp_height;
        }

        $destination_image = imagecreatetruecolor($destination_width, $destination_height);
        if ($type > 1) {
            imagefill($destination_image, 0, 0, imagecolorallocate ($destination_image, 255, 255, 255));
        }
        imagecopyresampled($destination_image, $src_img, $destination_x, $destination_y, $source_x, $source_y, $new_destination_width, $new_destination_height, $source_width, $source_height);

        imagepng($destination_image, $outputImageFile);
    }
}