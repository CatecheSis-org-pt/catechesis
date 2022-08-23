<?php

require_once(__DIR__ . '/config/catechesis_config.inc.php');
require_once(__DIR__ . '/PdoDatabaseManager.php');
require_once(__DIR__ . '/Configurator.php');
require_once(__DIR__ . '/log_functions.php');
require_once(__DIR__ . '/domain/EnrollmentOrder.php');
require_once(__DIR__ . '/UserData.php');

use catechesis\DatabaseAccessMode;
use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use core\domain\EnrollmentStatus;
use catechesis\UserData;


/**
 * Updates the status of a renewal order submission.
 * If 'status' is true, the submission is marked as processed.
 * If 'status' is false, the submission is marked as pending processing.
 * Optional parameters catechetical year, catechism and group can be set in case the status is processed.
 * @param int $rid
 * @param bool $status
 * @param int|null $enrollmentCatecheticalYear
 * @param int|null $enrollmentCatechism
 * @param string|null $enrollmentGroup
 * @return bool
 */
function setRenewalOrderStatus(int $rid, bool $status,
                               int $enrollmentCatecheticalYear = null, int $enrollmentCatechism = null, string $enrollmentGroup = null)
{
    $db = new PdoDatabaseManager();

    try
    {
        if($db->updateRenewalOrderStatus($rid, $status, $enrollmentCatecheticalYear, $enrollmentCatechism, $enrollmentGroup))
        {
            $logMessage = "Pedido de renovação de matrícula com ID " . $rid . " marcado como";
            if($status == EnrollmentStatus::PROCESSED)
                $logMessage = $logMessage . " processado.";
            else
                $logMessage = $logMessage . " não processado.";

            writeLogEntry($logMessage);
            $db = null;
            return true;
        }
        else
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao atualizar estado do pedido de renovação de matrícula. Pâmetros inválidos.</div>");
        }
    }
    catch(Exception $e)
    {
        //echo $e->getMessage();
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
    }
    $db = null;

    return false;
}


/**
 * Deletes a renewal order from the database and write an entry in the log.
 * @param int $rid
 * @return bool
 */
function deleteRenewalOrder(int $rid)
{
    $db = new PdoDatabaseManager();

    try
    {
        // Get order data to write log message
        $order = $db->getRenewalSubmission($rid);

        // Delete the renewal order
        if($db->deleteRenewalOrder($rid))
        {
            $logMessage = "Pedido de renovação de matrícula com ID " . $rid . " (" . $order['catequizando_nome'] . ") eliminado.";
            writeLogEntry($logMessage);
            $db = null;
            return true;
        }
        else
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao remover pedido de renovação de matrícula. Pâmetros inválidos.</div>");
        }
    }
    catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        return false;
    }
    $db = null;

    return false;
}







/**
 * Marks an enrollment order as processed, either with a corresponding catechumen file (cid) or without it (cid=-1),
 * and adds a log entry.
 * @param $eid - Enrollment order ID
 * @param $cid - ID of the catechumen to associate with the processed order (or null, to process without associating a file)
 */
function setEnrollmentOrderAsProcessed(int $eid, int $cid = null)
{
    $db = new PdoDatabaseManager();

    try
    {
        if($db->updateEnrollmentOrderFile($eid, $cid))
        {
            $logMessage = "Pedido de inscrição com ID " . $eid . " marcado como processado";
            if(isset($cid))
                $logMessage .= " e associado à ficha com cid=" . $cid ;
            $logMessage .= ".";

            writeLogEntry($logMessage);

            $db = null;
            return true;
        }
        else
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao atualizar estado do pedido de inscrição. Pâmetros inválidos.</div>");
        }
    }
    catch(Exception $e)
    {
        //echo $e->getMessage();
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
    }

    $db = null;
    return false;
}


/**
 * Deletes an enrollment order from the database and writes an
 * entry in the log.
 * @param $eid
 * @return bool
 */
function deleteEnrollmentOrder($eid)
{
    $db = new PdoDatabaseManager();

    try
    {
        // Get order data to delete photo and write log message
        $order = $db->getEnrollmentSubmission($eid);

        // Delete photo file
        $photoFile = $order['foto'];
        unlink(UserData::getCatechumensPhotosFolder() . '/' . $photoFile);

        // Delete enrollment order
        if($db->deleteEnrollmentOrder($eid))
        {
            $logMessage = "Pedido de inscrição com ID " . $eid . " (" . $order['nome'] . ") eliminado.";
            writeLogEntry($logMessage);
            return true;
        }
        else
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao remover pedido de inscrição. Pâmetros inválidos.</div>");
        }
    }
    catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        return false;
    }
    $db = null;

    return false;
}



/**
 * Adds a new family member to the list of people authorized to pick up the catechumen.
 * Note that this function always creates a new familly member (without checking if someone with that name/phone already
 * exists).
 * @param $cid
 * @param $name
 * @param $relationship
 * @param $cellPhone
 * @return false|string|null
 */
function addAuthorizationToList(int $cid, string $name, string $relationship, string $cellPhone)
{
    $db = new PdoDatabaseManager();

    try
    {
        $db->beginTransaction(DatabaseAccessMode::DEFAULT_EDIT);

        // Insert familly member in the database
        //  NOTE: We don't check if the familly member already exists, because people usually fill just the first
        //  and last names in this table, and with just the first and last name we could very likely find someone else
        //  with the same name...
        //  For this purpose, we simply assume that the familly member is always to add.
        $fid_familiar = $db->createFamilyMember($name, null, null, null, null, $cellPhone);

        // Insert the familly member in the catechumen's authorization list
        if($db->addFamilyMemberToCatechumenAuthorizationlist($cid, $fid_familiar, $relationship))
        {
            $db->commit();
            catechumenAuthorizationsLog($cid, "Adicionado familiar " . $name . " (fid=" . $fid_familiar . ") à lista de autorizações do catequizando com cid=" . $cid);
            echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Familiar adicionado à lista de autorizações deste catequizando.</div>");
            return $fid_familiar;
        }
        else
        {
            $db->rollBack();
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao actualizar autorizações do catequizando.</div>");
            return null;
        }
    }
    catch(Exception $e)
    {
        $db->rollBack();
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        return null;
    }
}





/**
 * Computes the recommended catechism for a catechumen given its birth date and his/her previous catechism (if he/she
 * frequented catechesis in another parish).
 * @param $birthDate
 * @param null $previousCatechism
 */
function computeRecommendedCatechism($birthDate, $previousCatechism = null)
{
    $recommendation = 1;

    if(isset($previousCatechism) && intval($previousCatechism) >= 1 && intval($previousCatechism) <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
    {
        //Catechumen already frequented catechesis before (perhaps in another parish)
        $recommendation = intval($previousCatechism) + 1;
    }
    else
    {
        //Catechumen never frequented catechesis before.
        //We will try to enroll him/her in the most suitable catechism given his/her age

        $ageInOctober = date_diff(date_create($birthDate), date_create(date('Y-10-31')))->y;
        $ageInDecember = date_diff(date_create($birthDate), date_create(date('Y-12-31')))->y;

        if($ageInOctober < 6)
            $recommendation = $ageInDecember - 6 + 1;   //Conditional catechumens
        else
            $recommendation = $ageInOctober - 6 + 1;    //6 years old maps to 1st catechism
                                                        //7 years old maps to 2nd catechism
                                                        //...
    }

    if($recommendation < 1)
        $recommendation = 1;
    if($recommendation > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
        $recommendation = intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS));

    return $recommendation;
}