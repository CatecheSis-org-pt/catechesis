<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Criar conta de administrador</title>
</head>
<body>
<?php
require_once(__DIR__ . '/../core/PdoDatabaseManager.php');

///////////// Default account credentials //////////
$username = "admin";
$name = "Administrador";
$password = "admin";
////////////////////////////////////////////////////

try
{
    $db = new catechesis\PdoDatabaseManager();

    if ($db->createUserAccount($username, $name, $password, true, false, false, null, null))
        echo('<h1> Success! </h1>');
    else
        echo('<h1> Failed! </h1>');
}
catch (Exception $e)
{
    echo('<h1> Failed! <br> ' . $e->getMessage() . ' </h1>');
}

?>
</body>
</html>