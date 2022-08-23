<?php
/**
 * CatecheSis endpoint to generate or refresh CAPTCHAs.
 */

require_once(__DIR__ . '/securimage.php');


if (isset($_GET['refresh']))
{
    //Refresh the CAPTCHA code

    $captcha = Securimage::getCaptchaId(true);
    $data    = array('captchaId' => $captcha);

    echo json_encode($data);
    exit;

}
else if (isset($_GET['display']))
{
    // Display the captcha with the supplied ID from the URL
    $captchaId = $_GET['captchaId'];

    if (empty($captchaId))
        die('no id');

    // Construct options specifying the existing captcha ID
    $options = array('captchaId' => $captchaId);
    $captcha = new Securimage($options);

    //Security/difficulty settings
    $captcha->perturbation  = .5;
    $captcha->num_lines = 3;

    // Show the image, this sends proper HTTP headers
    $captcha->show();
    exit;
}
else
    die();