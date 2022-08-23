<?php
/*
 * jQuery File Upload Plugin PHP Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

//require_once('document_uploads/UploadHandler.php');
require_once(__DIR__ . '/core/document_uploads/MyUploadHandler.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');

//$upload_handler = new UploadHandler();
$upload_handler = new CustomUploadHandler();