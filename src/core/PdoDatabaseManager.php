<?php

namespace catechesis;

require_once(__DIR__ . "/config/catechesis_config.inc.php");
require_once(__DIR__ . "/Configurator.php");
require_once(__DIR__ . "/Utils.php");
require_once(__DIR__ . "/DatabaseManager.php");
require_once(__DIR__ . "/domain/Sacraments.php");
require_once(__DIR__ . "/domain/Marriage.php");
require_once(__DIR__ . "/domain/VirtualRoom.php");
require_once(__DIR__ . "/domain/EnrollmentOrder.php");
require_once(__DIR__ . '/../authentication/ulogin/config/all.inc.php');
require_once(__DIR__ . '/../authentication/ulogin/main.inc.php');


use core\domain\EnrollmentStatus;
use core\domain\Marriage;
use core\domain\Sacraments;
use core\domain\VirtualRoomStatus;
use catechesis\Configurator;
use catechesis\Utils;
use Exception;
use PDO;
use PDOException;
use uLogin;


/**
 * ===================================================================================================================
 * =
 * =                                             DECLARATION
 * =
 * ===================================================================================================================
 **/

/**
 * Interface PdoDatabaseManagerInterface
 * @package catechesis
 * An utility interface just to summarize the available methods in the PdoDatabaseManager class,
 * a DatabaseManager implementation based on MySQL database and PDO.
 */
interface PdoDatabaseManagerInterface extends DatabaseManager
{
    public function connect(string $username, string $password);
    public function connectAs(int $accessMode);
    public function connectAsNeeded(int $accessMode);
    public function disconnect();
    public function beginTransaction(int $accessMode = DatabaseAccessMode::UNCHANGED);
    public function commit();
    public function rollBack();

    // Catechumens
    public function getCatechumenById(int $cid);
    public function getCatechumensByNameAndBirthdate(string $name, string $birth_date);
    public function findCatechumensByNameAndBirthdate(string $name, string $birth_date, int $catecheticalYear);
    public function getCatechumensByCatechismWithFilters(int $currentCatecheticalYear,
                                                         int $searchCatecheticalYear = null, int $catechism = null,
                                                         string $group = null,
                                                         bool $includeAchievementRecord = false,
                                                         int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE,
                                                         int $baptism = SacramentFilter::IRRELEVANT,
                                                         int $communion = SacramentFilter::IRRELEVANT,
                                                         array $excludedCatechisms = array());
    public function getCatechumensByCatechistWithFilters(int $currentCatecheticalYear,
                                                         int $searchCatecheticalYear = null, string $catechist = null,
                                                         int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE,
                                                         int $baptism = SacramentFilter::IRRELEVANT,
                                                         int $communion = SacramentFilter::IRRELEVANT,
                                                         array $excludedCatechisms = array(),
                                                         bool $onlyScouts = false);
    public function getCatechumenCurrentCatechesisGroup(int $cid, int $catecheticalYear);
    public function getCatechumenSiblings(int $cid);
    public function createCatechumen(string $name, string $birthdate, string $birthplace, string $nif = null,
                                     $father_fid, $mother_fid, int $responsible_fid,
                                     string $responsible_relationship, string $photo, int $numSiblings,
                                     bool $isScout, bool $photosAllowed, bool $allowedToGoOutAlone,
                                     string $observations, string $createdByUsername);
    public function updateCatechumen(int $cid, string $name, string $birthdate, string $birthplace, string $nif = null,
                                     $father_fid, $mother_fid, int $responsible_fid,
                                     string $responsible_relationship, string $photo, int $numSiblings,
                                     bool $isScout, bool $photosAllowed);
    public function setCatechumenObservationsFields(int $cid, string $observations=null);
    public function deleteCatechumen(int $cid);
    public function getCatechumenCatecheticalRecord(int $cid);
    public function getCatechumenSchoolingRecord(int $cid);
    public function insertCatechumenSchoolingRecord(int $cid, int $catecheticalYear, string $schoolYear);
    public function deleteCatechumenSchoolingRecord(int $cid, int $catecheticalYear=null);
    public function getScouts(int $catecheticalYear);
    public function getTodaysGroupBirthdays(int $catecheticalYear, int $catechism, string $group);
    public function updateCatechumenAchievement(int $cid, int $catecheticalYear, int $catechism, string $group,
                                                int $achievement);
    public function setCatechumenAuthorizationToGoOutAlone(int $cid, bool $canLeaveAlone);
    public function getAllDistinctCatechumenNames();
    public function getAllDistinctBirthPlaces();


    // Catechumen parents and family
    public function getFamilyMember(int $fid);
    public function getFamilyMembersByName(string $name);
    public function getFamilyMemberChildren(int $fid);
    public function getMarriageInformation(int $fid1, int $fid2);
    public function addMarriageInformation(int $fid1, int $fid2, int $union_type);
    public function deleteMarriage(int $fid1, int $fid2);
    public function createFamilyMember(string $name, string $job=null, string $address=null, string $zipCode=null,
                                       string $phone=null, string $cellPhone=null, string $email=null,
                                       bool $signedRGPD=false);
    public function deleteFamilyMember(int $fid);
    public function updateFamilyMemberName(int $fid, string $name);
    public function updateFamilyMemberJob(int $fid, string $job);
    public function updateFamilyMemberAllFields(int $fid, string $name, string $job, string $address,
                                                string $zipCode, string $phone=null, string $cellPhone=null,
                                                string $email=null, bool $signedRGPD=false);
    public function getCatechumenAuthorizationList(int $cid);
    public function addFamilyMemberToCatechumenAuthorizationlist(int $cid, int $fid, string $relationship);
    public function removeFamilyMemberFromCatechumenAuthorizationList(int $cid, int $fid);
    public function getAllDistinctFamilyMemberNames();
    public function getAllDistinctJobs();
    public function getAllDistinctZipCodes();


    // Online enrollments
    public function getCatechumensEnrollmentRenewalCandidateList(int $previousCatecheticalYear, int $previousCatechism,
                                                                 string $previousGroup, int $enrollmentCatecheticalYear);
    public function getRenewalSubmissions(int $catecheticalYear, int $catechism = null);
    public function getRenewalSubmission(int $rid);
    public function getEnrollmentSubmissions(int $catecheticalYear);
    public function getEnrollmentSubmission(int $eid);
    public function getNumberOfPendingRenewals(int $catecheticalYear = null);
    public function getNumberOfPendingEnrollments(int $catecheticalYear = null);
    public function postRenewalOrder(string $applicantName, string $phone, string $catechumenName, int $lastCatechism,
                                     string $ipAddress, string $email = null, string $obs = null);
    public function postEnrollmentOrder(string $catechumenName, string $birthDay, string $birthPlace, string $nif = null,
                                        int $nSiblings, string $address, string $postalCode,
                                        int $responsibleIndex, string $ipAddress,
                                        bool $scout, bool $photosAllowed, bool $exitAllowed, array $exitAuthorizations,
                                        string $photo = null, string $obs = null,
                                        string $responsibleName = null, string $responsibleJob = null,
                                        string $responsibleRelationship = null,
                                        string $fatherName = null, string $fatherJob = null,
                                        string $motherName = null, string $motherJob = null,
                                        int $marriageType = null,
                                        string $phone = null, string $cellPhone = null, string $email = null,
                                        string $baptismDate = null, string $baptismParish = null,
                                        string $comunionDate = null, string $comunionParish = null,
                                        int $lastCatechism = null);
    public function updateRenewalOrderStatus(int $rid, int $status, int $enrollmentCatecheticalYear = null,
                                             int $enrollmentCatechism = null, string $enrollmentGroup = null);
    public function deleteRenewalOrder(int $rid);
    public function updateEnrollmentOrderFile(int $eid, int $cid = null);
    public function deleteEnrollmentOrder(int $eid);


    // Catechists & users
    public function getAllUsers();
    public function getActiveCatechists();
    public function getUserAccountStatus(string $username);
    public function getUserAccountDetails(string $username);
    public function createUserAccount(string $username, string $name, string $password, bool $isAdmin, bool $isCatechist,
                                      bool $isCatechistActive=true, $phone=null, $email=null);
    public function updateUserAccountDetails(string $username, string $name, $phone, $email);
    public function changeUserAccountStatus(string $username, bool $active);
    public function activateUserAccount(string $username);
    public function blockUserAccount(string $username);
    public function setUserAsAdmin(string $username, bool $isAdmin);
    public function giveAdminRights(string $username);
    public function revokeAdminRights(string $username);
    public function setCatechistStatus(string $username, bool $isActive);
    public function setAsActiveCatechist(string $username);
    public function setAsInactiveCatechist(string $username);
    public function getCatechistGroups(string $username, int $catecheticalYear);
    public function getAllAssignedCatechists(int $catecheticalYear);
    public function getGroupCatechists(int $catecheticalYear, int $catechism, string $group);
    public function addCatechistToGroup(string $username, int $catecheticalYear, int $catechism, string $group);
    public function removeCatechistFromGroup(string $username, int $catecheticalYear, int $catechism, string $group);
    public function checkIfCatechumenBelongsToCatechist(int $cid, string $username, int $catecheticalYear);


    // Catechesis
    public function getCatecheticalYears();
    public function getCatechisms(int $catecheticalYear = null);
    public function getCatechismGroups(int $catecheticalYear, int $catechism);
    public function getCatechismsAndGroups(int $catecheticalYear = null);
    public function getCatechismsAndGroupsFromLatestYear();
    public function hasCatechism(int $catecheticalYear, int $catechism);
    public function getGroupLetters(int $catecheticalYear = null);
    public function createCatechismGroup(int $catecheticalYear, int $catechism, string $group);
    public function deleteCatechismGroup(int $catecheticalYear, int $catechism, string $group);
    public function enrollCatechumenInGroup(int $cid, int $catecheticalYear, int $catechism, string $group,
                                            bool $pass, bool $paid, string $username);
    public function unenrollCatechumenFromGroup(int $cid, int $catecheticalYear, int $catechism, string $group);
    public function unenrollCatechumenFromAllGroups(int $cid, bool $useTransaction=true);
    public function updateCatechumenEnrollmentPayment(int $cid, int $catecheticalYear, int $catechism, string $group,
                                                      bool $paid);
    public function getCatecheticalYearsWhereCatechumenIsNotEnrolled(int $cid);


    // Sacraments
    public function getSacramentsCivilYears(int $sacrament);
    public function getDistinctParishes(int $sacrament);
    public function getAllDistinctParishes();
    public function getCatechumensBySacrament(int $sacrament, int $civilYear = null, string $parish = null,
                                              int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE);
    public function getCatechumensWithAndWithoutSacramentByCatechismAndGroup(int $sacrament, int $catecheticalYear,
                                                                             int $catechism, string $group);
    public function getCatechumenSacramentRecord(int $sacrament, int $cid);
    public function insertSacramentRecord(int $cid, int $sacrament, string $date, string $parish);
    public function updateSacramentRecord(int $cid, int $sacrament, string $date, string $parish);
    public function deleteSacramentRecord(int $cid, int $sacrament);
    public function setSacramentProofDocument(int $cid, int $sacrament, string $proof=null);


    // Decision support system
    public function getBaptismAnalysis(int $catecheticalYear, bool $admin, string $username);
    public function getFirstCommunionAnalysis(int $catecheticalYear, bool $admin, string $username);
    public function getChrismationAnalysis(int $catecheticalYear, bool $admin, string $username);
    public function getDataDumpForInconsistencyAnalysis(string $username, bool $admin, int $currentCatecheticalYear,
                                                        int $catecheticalYear = null, int $catechism = null,
                                                        string $group = null);

    // Statistics
    public function isDataSufficientForResidentsStatistic();
    public function isDataSufficientForAbadonmentStatistic(int $currentCatecheticalYear);
    public function isDataSufficientForCompleteCatecheticalJourneyStatistic(int $currentCatecheticalYear);
    public function isDataSufficientForNumberOfCatechumensByCatechist();
    public function getResidentCatechumensPercentage();
    public function getCatecheticalYearsRangeForCatechumens();
    public function getCatecheticalYearsRangeForCatechumensAndCatechists();
    public function getAbandonmentByCatecheticalYear(int $currentCatecheticalYear, bool $inPercentage);
    public function getCompleteCatecheticalJourneysByCatecheticalYear(int $currentCatecheticalYear, bool $inPercentage);
    public function getCatechumensByCatechistAndYear(bool $accumulated);

    // Virtual catechesis
    public function getVirtualCatechesisSessionDates(int $catechism = null, string $group = null, bool $recursive = true,
                                                     int $limit = 0, string $afterDate = null, string $beforeDate = null);
    public function getVirtualCatechesisContent(string $sessionDate, int $catechism = null, string $group = null,
                                                bool $recursive = true);
    public function postVirtualCatechesisContent(string $contents, string $username, string $sessionDate,
                                                 int $catechism = null, string $group = null);
    public function insertLockInVirtualCatechesis(string $username, string $sessionDate,
                                                  int $catechism = null, string $group = null);
    public function getListOfVirtualCatechesisObservers(string $sessionDate, int $timeThreshold, int $catechism = null,
                                                        string $group = null, string $excludeUsername = null);


    // Settings
    public function getConfigValue(string $key);
    public function setConfigValue(string $key, string $value);

    // Log
    public function getCatechesisLog();
    public function getAuthenticationsLog();
    public function getLogEntry(int $lsn);
    public function addLogEntry(string $username, string $action);
    public function updateCatechumenFileLog(int $cid, int $lsn);
    public function updateCatechumenArchiveLog(int $cid, int $lsn);
    public function updateCatechumenAuthorizationsLog(int $cid, int $lsn);
    public function getOldestLSNtoKeep(int $maxRecords);
    public function deleteLogEntriesOlderThan(int $lsn);


    // Utility methods
    //private function translateSacramentIntoTable(int $sacrament);             // Translates a core\domain\Sacrament:: constant into the corresponding database table name
    //private function translateMarriageTypeIntoString(int $marriageType);      //Translates a core\domain\Marriage:: constant into the corresponding database string value
}





/**
 * ===================================================================================================================
 * =
 * =                                             DEFINITION
 * =
 * ===================================================================================================================
 **/
/**
 * Class PdoDatabaseManager
 * @package catechesis
 * A DatabaseManager implementation based on MySQL database and PDO.
 */
class PdoDatabaseManager implements PdoDatabaseManagerInterface
{

    private $_connection = null;
    private $_db_host = null;
    private $_db_name = null;
    private $_connection_access_mode = null;
    private $_transaction_active = false;


    public function __construct()
    {
        $this->_db_host = constant('CATECHESIS_HOST');
        $this->_db_name = constant('CATECHESIS_DB');
    }

    public function __destruct()
    {
        $this->disconnect();
    }


    /**
     * Connect directly to the database, given a username and password.
     * This should NOT be used directly. The method connectAs(DatabseAccessMode) should be used instead.
     * @param $username
     * @param $password
     * @return bool
     */
    public function connect(string $username, string $password)
    {
        if($this->_transaction_active)
            throw new Exception('Não foi possível estabelecer uma nova ligação à base de dados, porque uma transação está em curso.');

        try
        {
            //$this->_connection = new PDO("mysql:host=" . $this->_db_host . ";dbname=" . $this->_db_name . ";charset=utf8", $username, $password, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $this->_connection = new PDO("mysql:host=" . $this->_db_host . ";dbname=" . $this->_db_name . ";charset=utf8", $username, $password, array(PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            //$this->_connection = new PDO("mysql:host=" . $this->_db_host . ";dbname=" . $this->_db_name . ";charset=utf8", $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
            return true;
        }
        catch (PDOException $e)
        {
            return false;
        }
    }


    /**
     * Opens a connection to the database with the provided access mode (one of the constants from class DatabaseAccessMode)
     * @param int $accessMode
     */
    public function connectAs(int $accessMode)
    {
        if($accessMode == DatabaseAccessMode::UNCHANGED)
            return true; //Do nothing

        if($this->_transaction_active)
            throw new Exception('Não foi possível estabelecer uma nova ligação à base de dados, porque uma transação está em curso.');

        $this->_connection_access_mode = $accessMode;

        switch($accessMode)
        {
            case DatabaseAccessMode::DEFAULT_READ:
                return $this->connect(constant('USER_DEFAULT_READ'), constant('PASS_DEFAULT_READ'));

            case DatabaseAccessMode::DEFAULT_EDIT:
                return $this->connect(constant('USER_DEFAULT_EDIT'), constant('PASS_DEFAULT_EDIT'));

            case DatabaseAccessMode::DEFAULT_DELETE:
                return $this->connect(constant('USER_DEFAULT_DELETE'), constant('PASS_DEFAULT_DELETE'));

            case DatabaseAccessMode::GROUP_MANAGEMENT:
                return $this->connect(constant('USER_GROUP_MANAGEMENT'), constant('PASS_GROUP_MANAGEMENT'));

            case DatabaseAccessMode::USER_MANAGEMENT:
                return $this->connect(constant('USER_USER_MANAGEMENT'), constant('PASS_USER_MANAGEMENT'));

            case DatabaseAccessMode::LOG_EDIT:
                return $this->connect(constant('USER_LOG'), constant('PASS_LOG'));

            case DatabaseAccessMode::LOG_CLEAN:
                return $this->connect(constant('USER_LOG_CLEAN'), constant('PASS_LOG_CLEAN'));

            case DatabaseAccessMode::CONFIGURATION:
                return $this->connect(constant('USER_CONFIG'), constant('PASS_CONFIG'));

            case DatabaseAccessMode::ONLINE_ENROLLMENT:
                return $this->connect(constant('USER_ONLINE_ENROLLMENT'), constant('PASS_ONLINE_ENROLLMENT'));

            default:
                $this->_connection_access_mode = null;
                return false;
        }
    }

    /**
     * Opens a new database connection with the defined access mode, but only if a connection isn't already started or if
     * the current connection has an access mode that is incompatible with the requested one.
     * @param DatabaseAccessMode $accessMode
     */
    public function connectAsNeeded(int $accessMode)
    {
        if($accessMode == DatabaseAccessMode::UNCHANGED)
            return true; //Do nothing

        if($this->_connection == null)
            return $this->connectAs($accessMode);
        else if($this->_connection_access_mode == $accessMode)
            return true; //Already connected in that mode. No need to reconnect.
        else if($accessMode >= DatabaseAccessMode::DEFAULT_READ && $accessMode <= DatabaseAccessMode::DEFAULT_EDIT
                && $this->_connection_access_mode >= DatabaseAccessMode::DEFAULT_READ  && $this->_connection_access_mode <= DatabaseAccessMode::DEFAULT_EDIT
                && $accessMode <= $this->_connection_access_mode)
            return true; //Current connection is stronger than the requested. No need to reconnect
        else
            return $this->connectAs($accessMode);
    }

    public function disconnect()
    {
        $this->_connection = null;
        $this->_connection_access_mode = null;
    }

    /**
     * Begins a database transaction.
     * If the optional argument $accessMode is provided, it changes the connection access type before starting the transaction
     * (this is useful if the following queries inside the transaction require privileges escalation).
     * @param int $accessMode
     */
    public function beginTransaction(int $accessMode = DatabaseAccessMode::UNCHANGED)
    {
        if($accessMode != DatabaseAccessMode::UNCHANGED)
            $this->connectAsNeeded($accessMode);

        $this->_connection->beginTransaction();
        $this->_transaction_active = true;
    }

    public function commit()
    {
        $this->_transaction_active = false;
        return $this->_connection->commit();
    }

    public function rollBack()
    {
        $this->_transaction_active = false;
        return $this->_connection->rollBack();
    }





    /**
     * Returns all the data about a particular catechumen.
     * The returned data even resolves the full name of the user who last modified the catechumen file.
     * @param int $cid
     * @return mixed
     * @throws Exception
     */
    public function getCatechumenById(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Build query
        $sql = "SELECT c.nome AS nome, data_nasc, local_nasc, nif, num_irmaos, escuteiro, autorizou_fotos, autorizou_saida_sozinho, pai, mae, enc_edu, enc_edu_quem, foto, obs, criado_por, DATE(criado_em) AS criado_em, u.nome AS criado_por_nome, lastLSN_ficha, lastLSN_arquivo, lastLSN_autorizacoes FROM catequizando c, utilizador u WHERE c.cid = :cid AND c.criado_por=u.username;";

        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);


            if ($stm->execute())
            {
                return $stm->fetch();
            }
            else
            {
                throw new Exception("Catequizando não encontrado.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns catechumen(s) matching the exact name and birthdate
     * If multiple catechumens with the exact same name and birthdat are found, a list is returned.
     * @param string $name
     * @param string $birth_date
     */
    public function getCatechumensByNameAndBirthdate(string $name, string $birth_date)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $sql = "SELECT cid FROM catequizando c WHERE c.nome = :nome AND c.data_nasc = STR_TO_DATE(:data_nasc, '%d-%m-%Y');";

        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":nome", $name);
            $stm->bindParam(":data_nasc", $birth_date);

            if($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
            {
                throw new Exception("Catequizando não encontrado.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Finds and retuns all the catechumens matching the name and/or birth date.
     * The name is searched with wildcards in the place of spaces, so partial name searches are supported.
     * Also returns the catechism in which each catechumen is currently enrolled (if any),
     * the catechumen attributes and sacraments.
     * @param string $name
     * @param string $birth_date
     * @param int $catecheticalYear - used to give the catechism in which the catechumens are enrolled in the current year
     * @return mixed
     * @throws Exception
     */
    public function findCatechumensByNameAndBirthdate(string $name, string $birth_date, int $catecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Build query
        $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.nif, t1.foto, t1.escuteiro, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t1.obs, t2.ano_catecismo, t2.turma,  t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma FROM (SELECT c.cid, nome, data_nasc, nif, foto, escuteiro, autorizou_fotos, autorizou_saida_sozinho, obs FROM catequizando c ";

        if (($name && $name != "") || ($birth_date && $birth_date != ""))
        {
            $sql = $sql . " WHERE ";
        }
        if ($name && $name != "")
        {
            $name = strtoupper($name);                                              //Search in uppercase
            $name = "%" . str_replace(" ", "%", $name) . "%";
            $sql = $sql . "upper(nome) LIKE :nome ";

            if ($birth_date && $birth_date != "")
            {
                $sql = $sql . " AND ";
            }
        }
        if ($birth_date && $birth_date != "") {
            $sql = $sql . "data_nasc=STR_TO_DATE(:data_nasc, '%d-%m-%Y') ";
        }

        //Complete query with sacraments and order by clause
        $sql = $this->generateQueryWithFilters($sql, null, SacramentFilter::IRRELEVANT, SacramentFilter::IRRELEVANT, array());
        $sql = $this->addCatechumenOrderByClause($sql, OrderCatechumensBy::NAME_BIRTHDATE);

        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_catequetico_actual", $catecheticalYear, PDO::PARAM_INT);
            if ($name && $name != "")
                $stm->bindParam(":nome", $name);
            if ($birth_date && $birth_date != "")
                $stm->bindParam(":data_nasc", $birth_date);

            if ($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
            {
                throw new Exception("Falha ao efectuar pesquisa. Parâmetros inválidos.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Finds and retuns all the catechumens matching the catechism/group.
     * Also returns the catechism in which each catechumen is currently enrolled (if any) and its sacraments.
     * If $includeAchievementRecord is true, returns also the catechumen achievement (if he/she passes or fails the year).
     * All arguments are optional. If no catechism is provided, for instance, all are included in the search.
     * If no search catechetical year is provided, the search returns all the catechumens ever enrolled in this parish.
     *
     * To choose the ordering criteria for the results:
     * - orderBy
     *      OrderCatechumensBy::NAME_BIRTHDATE  - by catechumen name and birthdate
     *      NAME_BIRTHDATE                      - by archive change date
     *
     * It is possible to apply filters as follows:
     * - baptism
     *   SacramentFilter::IRRELEVANT    - Any catechumen
     *   SacramentFilter::HAS           - Only baptized catechumens
     *   SacramentFilter::HAS_NOT       - Only NON baptized catechumens
     *
     * - communion
     *   SacramentFilter::IRRELEVANT    - Any catechumen
     *   SacramentFilter::HAS           - Only catechumens that received the First Communion
     *   SacramentFilter::HAS_NOT       - Only catechumens that did NOT receive the First Communion
     *
     * - excludedCatechisms
     *      Excludes catechumens enrolled in the catechism(s) included in this array, in the search catechetical year.
     * @param int $currentCatecheticalYear
     * @param $searchCatecheticalYear
     * @param $catechism
     * @param $group
     * @param bool $includeAchievementRecord
     * @param int $orderBy
     * @param int $baptism
     * @param int $communion
     * @param array $excludedCatechisms - array of integers in {1..10}, corresponding to the catechisms to exclude from the search
     * @return null
     * @throws Exception
     */
    public function getCatechumensByCatechismWithFilters(int $currentCatecheticalYear, 
                                                         int $searchCatecheticalYear = null, int $catechism = null,
                                                         string $group = null,
                                                         bool $includeAchievementRecord = false,
                                                         int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE,
                                                         int $baptism = SacramentFilter::IRRELEVANT,
                                                         int $communion = SacramentFilter::IRRELEVANT,
                                                         array $excludedCatechisms = array())
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Build query
        $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.nif, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.escuteiro, t1.foto, t1.obs, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t1.lastLSN_ficha, t1.lastLSN_arquivo, t2.ano_catecismo, t2.turma,";
        if($includeAchievementRecord)
            $sql = $sql . " t2.passa, ";
        $sql = $sql . " t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma FROM (SELECT DISTINCT c.cid, nome, data_nasc, nif, pai, mae, enc_edu, enc_edu_quem, escuteiro, foto, obs, autorizou_fotos, autorizou_saida_sozinho, lastLSN_ficha, lastLSN_arquivo ";
        if($includeAchievementRecord)
            $sql = $sql . ", p.passa ";
        $sql = $sql . " FROM catequizando c, pertence p WHERE c.cid=p.cid";

        if ($catechism && $catechism != "") {
            $sql = $sql . " AND p.ano_catecismo=:catecismo";
        }
        if ($group && $group != "") {
            $sql = $sql . " AND p.turma=:turma";
        }

        try
        {
            //Complete query with filters and exclusions
            $sql = $this->generateQueryWithFilters($sql, $searchCatecheticalYear, $baptism, $communion, $excludedCatechisms);
            $sql = $this->addCatechumenOrderByClause($sql, $orderBy);

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_catequetico_actual", $currentCatecheticalYear, PDO::PARAM_INT);

            if (($searchCatecheticalYear && $searchCatecheticalYear != "" && $searchCatecheticalYear != 0))
                $stm->bindParam(":ano_catequetico", $searchCatecheticalYear, PDO::PARAM_INT);
            if ($catechism && $catechism != "")
                $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            if ($group && $group != "")
                $stm->bindParam(":turma", $group);

            if ($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
            {
                throw new Exception("Falha ao efectuar pesquisa. Parâmetros inválidos.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Internal function to perform catechumens searches.
     * @param int $currentCatecheticalYear
     * @param $searchCatecheticalYear
     * @param $catechist
     * @param int $orderBy
     * @param int $baptism
     * @param int $communion
     * @param array $excludedCatechisms
     * @param bool $onlyScouts
     * @return mixed
     * @throws Exception
     */
    public function getCatechumensByCatechistWithFilters(int $currentCatecheticalYear,
                                                         int $searchCatecheticalYear = null, string $catechist = null,
                                                         int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE,
                                                         int $baptism = SacramentFilter::IRRELEVANT,
                                                         int $communion = SacramentFilter::IRRELEVANT,
                                                         array $excludedCatechisms = array(),
                                                         bool $onlyScouts = false)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Build query
        $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.nif, t1.foto, t1.escuteiro, t1.obs, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t2.ano_catecismo, t2.turma, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma FROM (SELECT DISTINCT c.cid, nome, data_nasc, nif, foto, escuteiro, obs, autorizou_fotos, autorizou_saida_sozinho FROM catequizando c, pertence p, lecciona l WHERE c.cid=p.cid AND p.ano_lectivo=l.ano_lectivo AND p.ano_catecismo=l.ano_catecismo AND p.turma=l.turma";

        if($catechist && $catechist!="")
            $sql = $sql . " AND l.username=:catequista";

        try
        {
            //Complete query with filters and exclusions
            $sql = $this->generateQueryWithFilters($sql, $searchCatecheticalYear, $baptism, $communion, $excludedCatechisms, $onlyScouts);
            $sql = $this->addCatechumenOrderByClause($sql, $orderBy);

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_catequetico_actual", $currentCatecheticalYear, PDO::PARAM_INT);
            if (($searchCatecheticalYear && $searchCatecheticalYear != "" && $searchCatecheticalYear != 0))
                $stm->bindParam(":ano_catequetico", $searchCatecheticalYear, PDO::PARAM_INT);
            if($catechist && $catechist!="")
                $stm->bindParam(":catequista", $catechist);

            if ($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao efectuar pesquisa. Parâmetros inválidos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Internal method that encapsulates the building of a query that is common to several methods, such as
     * getCatechumensByCatechismWithFilters() and getCatechumensByCatechistWithFilters().
     * @param string $baseQuery
     * @param int|null $searchCatecheticalYear
     * @param int $baptism
     * @param int $communion
     * @param array $excludedCatechisms - array of integers in {1..10}, corresponding to the catechisms to exclude from the search
     * @param bool $onlyScouts - if true, only scouts are returned (this is useful for scouts listings). Else, every catechumen is returned.
     * @return string
     * @throws Exception
     */
    private function generateQueryWithFilters(string $baseQuery, int $searchCatecheticalYear = null,
                                              int $baptism = SacramentFilter::IRRELEVANT,
                                              int $communion = SacramentFilter::IRRELEVANT,
                                              array $excludedCatechisms = array(),
                                              bool $onlyScouts = false)
    {
        //Check parameters
        if($baptism < SacramentFilter::IRRELEVANT || $baptism > SacramentFilter::HAS_NOT)
            throw new Exception("Filtro de baptismo invalido.");
        if($communion < SacramentFilter::IRRELEVANT || $communion > SacramentFilter::HAS_NOT)
            throw new Exception("Filtro de primeira Comunhao invalido.");

        //Build query
        $sql = $baseQuery;

        if ($searchCatecheticalYear && $searchCatecheticalYear != "" && $searchCatecheticalYear != 0) {
            $sql = $sql . " AND p.ano_lectivo=:ano_catequetico";
        }
        if ($baptism && $baptism == SacramentFilter::HAS) {
            $sql .= " AND c.cid IN (SELECT cid FROM baptismo)";
        }
        if ($baptism && $baptism == SacramentFilter::HAS_NOT) {
            $sql .= " AND c.cid NOT IN (SELECT cid FROM baptismo)";
        }
        if ($communion && $communion == SacramentFilter::HAS) {
            $sql .= " AND c.cid IN (SELECT cid FROM primeiraComunhao)";
        }
        if ($communion && $communion == SacramentFilter::HAS_NOT) {
            $sql .= " AND c.cid NOT IN (SELECT cid FROM primeiraComunhao)";
        }
        if($onlyScouts)
            $sql .= " AND c.escuteiro=1";

        for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)); $i++)
        {
            if(in_array($i, $excludedCatechisms))
            {
                $sql .= " AND c.cid NOT IN (SELECT cid FROM pertence WHERE ano_catecismo=" . $i; //Exclude results from i^th catechism
                if ($searchCatecheticalYear && $searchCatecheticalYear != "" && $searchCatecheticalYear != 0) {
                    $sql .= " AND ano_lectivo=:ano_catequetico";

                }
                $sql .= ")";
            }
        }


        // Enrolled catechism in the current catechetical year
        $sql = $sql . ") AS t1 LEFT OUTER JOIN pertence t2 ON (t1.cid=t2.cid AND t2.ano_lectivo=:ano_catequetico_actual)";

        // Sacraments
        $sql = $sql . " LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid)  LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid)  LEFT OUTER JOIN confirmacao t5 ON (t1.cid=t5.cid)";


        return $sql;
    }


    /**
     * Internal method that encapsulates the translation of a constant into an ORDER BY clause that is common to several
     * query methods.
     * Returns the query with the ORDER BY appended to the end.
     * @param $orderBy
     * @return string
     */
    private function addCatechumenOrderByClause(string $sql, int $orderBy)
    {
        switch($orderBy)
        {
            case OrderCatechumensBy::NAME_BIRTHDATE:
                $sql = $sql . " ORDER BY nome, data_nasc";
                break;

            case OrderCatechumensBy::LAST_CHANGED:
                $sql = $sql . " ORDER BY lastLSN_arquivo, lastLSN_ficha, nome";
                break;

            default:
                break;
        }

        return $sql;
    }



    /**
     * Returns the catechism and group where the catechumen is enrolled,
     * in a particular catechetical year.
     * @param int $cid
     * @param int $catecheticalYear
     * @return mixed
     * @throws Exception
     */
    public function getCatechumenCurrentCatechesisGroup(int $cid, int $catecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT ano_catecismo, turma FROM pertence WHERE cid=:cid AND ano_lectivo=:ano_lectivo;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
            {
                if($stm->rowCount()==1)
                    return $stm->fetch();
                else
                    return null;
            }
            else
                throw new Exception("Falha ao obter catecismo onde o catequizando se encontra inscrito.");
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }




    /**
     * Returns the IDs of all catechumens whose responsible is the responsible/father/mother of this catechumen, or an empty array.
     * Includes the catechumen itself.
     * @param int $cid
     */
    public function getCatechumenSiblings(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT cid FROM catequizando c WHERE c.enc_edu IN (SELECT enc_edu FROM catequizando WHERE cid=:cid UNION SELECT pai FROM catequizando WHERE cid=:cid UNION SELECT mae FROM catequizando WHERE cid=:cid) OR c.pai IN (SELECT enc_edu FROM catequizando WHERE cid=:cid UNION SELECT pai FROM catequizando WHERE cid=:cid) OR c.mae IN (SELECT enc_edu FROM catequizando WHERE cid=:cid UNION SELECT mae FROM catequizando WHERE cid=:cid);";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
                return array('cid' => $cid); //Only the catechumen itself
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Inserts a new catechumen in the database.
     * @param string $name
     * @param string $birthdate
     * @param string $birthplace
     * @param int $father_fid
     * @param int $mother_fid
     * @param int $responsible_fid
     * @param string $responsible_relationship
     * @param string $photo
     * @param int $numSiblings
     * @param bool $isScout
     * @param bool $photosAllowed
     * @param bool $allowedToGoOutAlone
     * @param string $observations
     * @param string $createdByUsername
     */
    public function createCatechumen(string $name, string $birthdate, string $birthplace, $nif,
                                     $father_fid, $mother_fid, int $responsible_fid,
                                     string $responsible_relationship, string $photo, int $numSiblings,
                                     bool $isScout, bool $photosAllowed, bool $allowedToGoOutAlone,
                                     string $observations, string $createdByUsername)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "INSERT INTO catequizando(nome, data_nasc, local_nasc, nif, num_irmaos, escuteiro, autorizou_fotos, autorizou_saida_sozinho, pai, mae, enc_edu, enc_edu_quem, foto, obs, criado_por, criado_em) VALUES (:nome, STR_TO_DATE(:data_nasc, '%d-%m-%Y'), :local_nasc, :nif, :num_irmaos, :escuteiro, :autorizacao_fotos, :autorizou_saida_sozinho, :fid_pai, :fid_mae, :fid_ee, :enc_edu_quem, :foto, :obs, :utilizador, NOW());";

            $stm = $this->_connection->prepare($sql);

            $mynull = null;
            $scout = $isScout?1:0;
            $photosAuthorization = $photosAllowed?1:0;
            $authorizationGoOutAlone = $allowedToGoOutAlone?1:0;

            $stm->bindParam(":nome", $name);
            $stm->bindParam(":data_nasc", $birthdate);
            $stm->bindParam(":local_nasc", $birthplace);
            if(isset($nif) && $nif!=="" && nif!==0)
                $stm->bindParam(":nif", $nif, PDO::PARAM_INT);
            else
                $stm->bindParam(":nif", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":num_irmaos", $numSiblings, PDO::PARAM_INT);
            $stm->bindParam(":escuteiro", $scout, PDO::PARAM_INT);
            $stm->bindParam(":autorizacao_fotos", $photosAuthorization, PDO::PARAM_INT);
            $stm->bindParam(":autorizou_saida_sozinho", $authorizationGoOutAlone, PDO::PARAM_INT);
            if(isset($father_fid))
                $stm->bindParam(":fid_pai", $father_fid, PDO::PARAM_INT);
            else
                $stm->bindParam(":fid_pai", $mynull, PDO::PARAM_NULL);
            if(isset($mother_fid))
                $stm->bindParam(":fid_mae", $mother_fid, PDO::PARAM_INT);
            else
                $stm->bindParam(":fid_mae", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":fid_ee", $responsible_fid, PDO::PARAM_INT);
            if($responsible_fid != $father_fid  &&  $responsible_fid != $mother_fid)
                $stm->bindParam(":enc_edu_quem", $responsible_relationship);
            else
                $stm->bindParam(":enc_edu_quem", $mynull, PDO::PARAM_NULL);
            if(isset($photo) && $photo!="")
                $stm->bindParam(":foto", $photo);
            else
                $stm->bindParam(":foto", $mynull, PDO::PARAM_NULL);
            if(isset($observations) && $observations!="")
                $stm->bindParam(":obs", $observations);
            else
                $stm->bindParam(":obs", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":utilizador", $createdByUsername);



            if($stm->execute())
            {
                return $this->_connection->lastInsertId();
            }
            else
                throw new Exception("Falha ao registar catequizando.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            if (strpos($e->getMessage(), 'Duplicate entry') !== false)
                throw new Exception("Entrada duplicada. Já existe um catequizando com este nome e data de nascimento ou NIF.");
            else
                throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Updates most fields of a catechumen.
     * @param int $cid
     * @param string $name
     * @param string $birthdate
     * @param string $birthplace
     * @param int $father_fid
     * @param int $mother_fid
     * @param int $responsible_fid
     * @param string $responsible_relationship
     * @param string $photo
     * @param int $numSiblings
     * @param bool $isScout
     * @param bool $photosAllowed
     * @param string $createdByUsername
     */
    public function updateCatechumen(int $cid, string $name, string $birthdate, string $birthplace, $nif,
                                     $father_fid, $mother_fid, int $responsible_fid,
                                     string $responsible_relationship, string $photo, int $numSiblings,
                                     bool $isScout, bool $photosAllowed)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE catequizando SET nome=:nome, data_nasc=STR_TO_DATE(:data_nasc, '%d-%m-%Y'), local_nasc=:local_nasc, nif=:nif, num_irmaos=:num_irmaos, escuteiro=:escuteiro, autorizou_fotos=:autorizacao_fotos, pai=:fid_pai, mae=:fid_mae, enc_edu=:fid_ee, enc_edu_quem=:enc_edu_quem, foto=:foto WHERE cid=:cid;";

            $stm = $this->_connection->prepare($sql);

            $mynull = null;
            $scout = $isScout?1:0;
            $photosAuthorization = $photosAllowed?1:0;

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":nome", $name);
            $stm->bindParam(":data_nasc", $birthdate);
            $stm->bindParam(":local_nasc", $birthplace);
            if(isset($nif))
                $stm->bindParam(":nif", $nif, PDO::PARAM_INT);
            else
                $stm->bindParam(":nif", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":num_irmaos", $numSiblings, PDO::PARAM_INT);
            $stm->bindParam(":escuteiro", $scout, PDO::PARAM_INT);
            $stm->bindParam(":autorizacao_fotos", $photosAuthorization, PDO::PARAM_INT);
            if(isset($father_fid))
                $stm->bindParam(":fid_pai", $father_fid, PDO::PARAM_INT);
            else
                $stm->bindParam(":fid_pai", $mynull, PDO::PARAM_NULL);
            if(isset($mother_fid))
                $stm->bindParam(":fid_mae", $mother_fid, PDO::PARAM_INT);
            else
                $stm->bindParam(":fid_mae", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":fid_ee", $responsible_fid, PDO::PARAM_INT);
            if($responsible_fid != $father_fid  &&  $responsible_fid != $mother_fid)
                $stm->bindParam(":enc_edu_quem", $responsible_relationship);
            else
                $stm->bindParam(":enc_edu_quem", $mynull, PDO::PARAM_NULL);
            if(isset($photo) && $photo!="")
                $stm->bindParam(":foto", $photo);
            else
                $stm->bindParam(":foto", $mynull, PDO::PARAM_NULL);


            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the catechumen observations field.
     * @param int $cid
     * @param string|null $observations
     */
    public function setCatechumenObservationsFields(int $cid, string $observations=null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE catequizando SET obs=:obs WHERE cid=:cid;";

            $stm = $this->_connection->prepare($sql);

            $mynull = null;
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            if(isset($observations))
                $stm->bindParam(":obs", $observations);
            else
                $stm->bindParam(":obs", $mynull, PDO::PARAM_NULL);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes a catechumen from the database.
     * @param int $cid
     */
    public function deleteCatechumen(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM catequizando WHERE cid = :cid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns info on all the catechesis groups where this catechumen was enrolled.
     * @param int $cid
     */
    public function getCatechumenCatecheticalRecord(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT p.ano_lectivo, p.ano_catecismo, p.turma, p.passa, u.nome, i.pago FROM pertence p, utilizador u, inscreve i  WHERE p.cid=:cid AND u.username=i.username AND i.cid=:cid AND i.ano_catecismo=p.ano_catecismo AND i.turma=p.turma AND i.ano_lectivo=p.ano_lectivo ORDER BY p.ano_lectivo, p.ano_catecismo, p.turma;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter percurso catequético do catequizando.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all schooling records for a catechumen.
     * @param int $cid
     */
    public function getCatechumenSchoolingRecord(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT e.ano_lectivo, e.ano_escolaridade FROM escolaridade e WHERE e.cid=:cid ORDER BY e.ano_lectivo;";

            $stm = $this->_connection->prepare($sql);

            $mynull = null;
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter escolaridade do catequizando.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Registers which school year a catechumen is attending.
     * @param int $cid
     * @param int $catecheticalYear
     * @param string $schoolYear
     */
    public function insertCatechumenSchoolingRecord(int $cid, int $catecheticalYear, string $schoolYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "INSERT INTO escolaridade(cid, ano_lectivo, ano_escolaridade) values (:cid, :ano_lectivo, :ano_escolar);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":ano_escolar", $schoolYear);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes a school year record from a catechumen (if a catechetical year is provided),
     * or ALL the records (if null is passed).
     * @param int $cid
     * @param int $catecheticalYear
     */
    public function deleteCatechumenSchoolingRecord(int $cid, int $catecheticalYear=null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM escolaridade WHERE cid=:cid";

            if(isset($catecheticalYear))
                $sql .= " AND ano_lectivo=:ano_lectivo";
            $sql .= ";";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            if(isset($catecheticalYear))
                $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }





    /**
     * Returns catechumens that are also scouts and are enrolled in the provided catechetical year.
     * @param int $catecheticalYear
     * @return mixed
     * @throws Exception
     */
    public function getScouts(int $catecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.nif, t1.foto, t1.obs, t1.escuteiro, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t2.ano_catecismo, t2.turma, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma FROM (SELECT DISTINCT c.cid, nome, data_nasc, nif, foto, obs, escuteiro, autorizou_fotos, autorizou_saida_sozinho FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual AND c.escuteiro=1) AS t1 LEFT OUTER JOIN pertence t2 ON (t1.cid=t2.cid AND t2.ano_lectivo=:ano_catequetico_actual) ";

            //Sacraments
            $sql = $sql . " LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid)  LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid)  LEFT OUTER JOIN confirmacao t5 ON (t1.cid=t5.cid)";
            $sql = $sql . " ORDER BY t1.nome, t1.data_nasc;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":ano_catequetico_actual", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os escuteitos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all catechumens in a group whose birthday is today.
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     * @return mixed
     * @throws Exception
     */
    public function getTodaysGroupBirthdays(int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT c.cid, c.nome, c.data_nasc FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_lectivo AND p.ano_catecismo=:ano_catecismo AND p.turma=:turma AND DAY(data_nasc)=DAY(NOW()) AND MONTH(data_nasc)=MONTH(NOW());";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":ano_catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter aniversários.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Registers if a catechumens passes or fails a catechetical year.
     * Possible achievement values:
     *  -1  - the catechumen fails the year
     *  +1  - the catechumen passes the year
     * @param int $cid
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     * @param int $achievement
     * @return bool
     * @throws Exception
     */
    public function updateCatechumenAchievement(int $cid, int $catecheticalYear, int $catechism, string $group,
                                                int $achievement)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE pertence SET passa=:decisao WHERE cid=:cid AND ano_lectivo=:ano_lectivo AND ano_catecismo=:ano_catecismo AND turma=:turma;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":ano_catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":decisao", $achievement, PDO::PARAM_INT);

            if($stm->execute())
                return true;
            else
                throw new Exception("Falha ao atualizar aproveitamento do catequizando.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Sets/updates the authorization information of whether a catechumen may go out of the church alone,
     * after catechesis.
     * Returns true in case of success, and false (or an Exception) if something goes wrong.
     * @param int $cid
     * @param bool $canLeaveAlone
     */
    public function setCatechumenAuthorizationToGoOutAlone(int $cid, bool $canLeaveAlone)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE catequizando SET autorizou_saida_sozinho = :pode_sair WHERE cid = :cid;";

            $stm = $this->_connection->prepare($sql);

            $canLeaveValue = $canLeaveAlone?1:0;
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":pode_sair", $canLeaveValue, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the distinct names of catechumens in the database.
     */
    public function getAllDistinctCatechumenNames()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        try
        {
            $sql = "SELECT DISTINCT nome FROM catequizando ORDER BY nome;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the distinct birth places in the catechumens table.
     */
    public function getAllDistinctBirthPlaces()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        try
        {
            $sql = "SELECT DISTINCT local_nasc FROM catequizando ORDER BY local_nasc;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns all data about a family member given its fid.
     * @param int $fid
     */
    public function getFamilyMember(int $fid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT nome, prof, morada, cod_postal, telefone, telemovel, email, RGPD_assinado FROM familiar WHERE fid=:fid;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetch();
            else
                throw new Exception("Falha ao obter dados do familiar.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Finds family members by name.
     * @param string $name
     */
    public function getFamilyMembersByName(string $name)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT fid, nome, prof, morada, cod_postal, telefone, telemovel, email, RGPD_assinado FROM familiar WHERE nome=:nome;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":nome", $name);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns catechumens whose parent or responsible is this family member.
     * @param int $fid
     */
    public function getFamilyMemberChildren(int $fid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT cid FROM catequizando WHERE mae=:fid OR pai=:fid OR enc_edu=:fid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns information about marriage of these people, if any.
     * The order of the arguments is irrelevant.
     * @param int $fid1
     * @param int $fid2
     */
    public function getMarriageInformation(int $fid1, int $fid2)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT como FROM casados WHERE (fid1=:fid1 AND fid2=:fid2) OR (fid1=:fid2 AND fid2=:fid1);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":fid1", $fid1, PDO::PARAM_INT);
            $stm->bindParam(":fid2", $fid2, PDO::PARAM_INT);


            if($stm->execute())
                return $stm->fetch();
            else
                throw new Exception("Falha ao obter dados do matrimónio.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Registers a union between two family members (marriage, civil or 'de facto' union).
     * @param int $fid1
     * @param int $fid2
     * @param int $union_type - One of the constants defined in core\domain\Marriage
     * @return bool
     * @throws Exception
     */
    public function addMarriageInformation(int $fid1, int $fid2, int $union_type)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $result = null;
        try
        {
            $sql = "INSERT INTO casados(fid1, fid2, como) VALUES(:fid1, :fid2, :casados_como);";

            $stm = $this->_connection->prepare($sql);

            $union_type_str = $this->translateMarriageTypeIntoString($union_type);

            $stm->bindParam(":fid1", $fid1, PDO::PARAM_INT);
            $stm->bindParam(":fid2", $fid2, PDO::PARAM_INT);
            $stm->bindParam(":casados_como", $union_type_str);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Removes the marriage between the two family members from the database.
     * The order in which the family member IDs are passed is irrelevant.
     * @param int $fid1
     * @param int $fid2
     * @return mixed
     * @throws Exception
     */
    public function deleteMarriage(int $fid1, int $fid2)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $result = null;
        try
        {
            $sql = "DELETE FROM casados WHERE (fid1 = :fid1 AND fid2 = :fid2) OR (fid1 = :fid2 AND fid2 = :fid1);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":fid1", $fid1, PDO::PARAM_INT);
            $stm->bindParam(":fid2", $fid2, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Registers a new family member and returns its ID.
     * Some parameters are optional. If null is passed, they get the default values.
     * @param string $name
     * @param string $phone
     */
    public function createFamilyMember(string $name, string $job=null, string $address=null, string $zipCode=null,
                                       string $phone=null, string $cellPhone=null, string $email=null,
                                       bool $signedRGPD=false)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $selected_fields = array();
            $selected_field_values = array();

            // Build query
            if(isset($job))
            {
                array_push($selected_fields, "prof");
                array_push($selected_field_values, ":prof");
            }
            if(isset($address))
            {
                array_push($selected_fields, "morada");
                array_push($selected_field_values, ":morada");
            }
            if(isset($zipCode))
            {
                array_push($selected_fields, "cod_postal");
                array_push($selected_field_values, ":cod_postal");
            }
            if(isset($phone))
            {
                array_push($selected_fields, "telefone");
                array_push($selected_field_values, ":telefone");
            }
            if(isset($cellPhone))
            {
                array_push($selected_fields, "telemovel");
                array_push($selected_field_values, ":telemovel");
            }
            if(isset($email))
            {
                array_push($selected_fields, "email");
                array_push($selected_field_values, ":email");
            }
            if(isset($signedRGPD))
            {
                array_push($selected_fields, "RGPD_assinado");
                array_push($selected_field_values, ":RGPD_assinado");
            }

            //$sql = "INSERT INTO familiar(nome, prof, morada, cod_postal, telefone, telemovel, email, RGPD_assinado) VALUES(:nome_ee, :prof_ee, :morada, :codigo_postal, :telefone, :telemovel, :email, :rgpd);";
            $sql = "INSERT INTO familiar(nome";
            foreach ($selected_fields as $field)
                $sql .= ", " . $field;
            $sql .= ") VALUES(:nome";
            foreach ($selected_field_values as $field_value)
                $sql .= ", " . $field_value;
            $sql .= ");";


            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":nome", $name);
            if(isset($job))
                $stm->bindParam(":prof", $job);
            if(isset($address))
                $stm->bindParam(":morada", $address);
            if(isset($zipCode))
                $stm->bindParam(":cod_postal", $zipCode);
            if(isset($phone))
                $stm->bindParam(":telefone", $phone);
            if(isset($cellPhone))
                $stm->bindParam(":telemovel", $cellPhone);
            if(isset($email))
                $stm->bindParam(":email", $email);
            if(isset($signedRGPD))
            {
                $rgpd = ($signedRGPD)?1:0;
                $stm->bindParam(":RGPD_assinado", $rgpd, PDO::PARAM_INT);
            }

            if($stm->execute())
            {
                return $this->_connection ->lastInsertId();
            }
            else
                throw new Exception("Falha ao registar familiar.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Removes a family member from the database.
     * Returns true in case of success, and an exception otherwise.
     * @param int $fid
     */
    public function deleteFamilyMember(int $fid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM familiar WHERE fid = :fid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Updates the name of a family member.
     * @param int $fid
     * @param string $name
     */
    public function updateFamilyMemberName(int $fid, string $name)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE familiar SET nome=:nome WHERE fid=:fid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);
            $stm->bindParam(":nome", $name);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the job of a family member.
     * @param int $fid
     * @param string $job
     */
    public function updateFamilyMemberJob(int $fid, string $job)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE familiar SET prof=:prof WHERE fid=:fid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);
            $stm->bindParam(":prof", $job);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates all fields of a family member in a single query.
     * @param int $fid
     * @param string $name
     * @param string|null $job
     * @param string|null $address
     * @param string|null $zipCode
     * @param string|null $phone
     * @param string|null $cellPhone
     * @param string|null $email
     * @param bool $signedRGPD
     */
    public function updateFamilyMemberAllFields(int $fid, string $name, string $job, string $address,
                                                string $zipCode, string $phone=null, string $cellPhone=null,
                                                string $email=null, bool $signedRGPD=false)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE familiar SET nome=:nome, prof=:prof, morada=:morada, cod_postal=:codigo_postal, telefone=:telefone, telemovel=:telemovel, email=:email, RGPD_assinado=:rgpd WHERE fid=:fid;";

            $stm = $this->_connection->prepare($sql);

            $mynull = null;
            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);
            $stm->bindParam(":nome", $name);
            $stm->bindParam(":prof", $job);
            $stm->bindParam(":morada", $address);
            $stm->bindParam(":codigo_postal", $zipCode);
            if(isset($phone) && $phone!="")
                $stm->bindParam(":telefone", $phone);
            else
                $stm->bindParam(":telefone", $mynull, PDO::PARAM_NULL);
            if(isset($cellPhone) && $cellPhone!="")
                $stm->bindParam(":telemovel", $cellPhone);
            else
                $stm->bindParam(":telemovel", $mynull, PDO::PARAM_NULL);
            if(isset($email) && $email!="")
                $stm->bindParam(":email", $email);
            else
                $stm->bindParam(":email", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":rgpd", $signedRGPD, PDO::PARAM_INT);if(isset($signedRGPD))
            {
                $rgpd = ($signedRGPD)?1:0;
                $stm->bindParam(":rgpd", $rgpd, PDO::PARAM_INT);
            }

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns the list of family members authorized to pick up the catechumen.
     * For each family member, the following attributes are included in the results:
     *  - ID, name, phone(s), relationship with the catechumen
     * @param int $cid
     */
    public function getCatechumenAuthorizationList(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT a.parentesco, f.fid, f.nome, f.telefone, f.telemovel  FROM autorizacaoSaidaMenores a, familiar f WHERE a.cid = :cid AND a.fid = f.fid ORDER BY f.nome;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao ao obter lista de familiares autorizados a vir buscar o catequizando.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Adds a family member to the list of authorized members to pick up the catechumen.
     * Returns true if it succeeds, and an exception otherwise.
     * @param $cid          - ID of the catechumen
     * @param $fid          - ID of the family member
     * @param $relationship - String representing the kind of relationship between the catechumen and the family member (e.g. grandmother, uncle, ...)
     */
    public function addFamilyMemberToCatechumenAuthorizationlist(int $cid, int $fid, string $relationship)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "INSERT INTO autorizacaoSaidaMenores(cid, fid, parentesco) VALUES(:cid, :fid, :parentesco);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);
            $stm->bindParam(":parentesco", $relationship);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Removes a family member from the list of authorized members to pick up the catechumen.
     * Returns true if it succeeds, and an exception otherwise.
     * @param int $cid
     * @param int $fid
     */
    public function removeFamilyMemberFromCatechumenAuthorizationList(int $cid, int $fid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM autorizacaoSaidaMenores WHERE cid = :cid AND fid = :fid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":fid", $fid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the distinct names in the familly member table.
     */
    public function getAllDistinctFamilyMemberNames()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT nome FROM familiar ORDER BY nome;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the distinct jobs in the familly member table.
     */
    public function getAllDistinctJobs()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT prof FROM familiar ORDER BY prof;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all distinct zip codes from the addresses in the family members table.
     */
    public function getAllDistinctZipCodes()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT cod_postal FROM familiar ORDER BY cod_postal;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                return array();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Generates list of candidates for enrollment renewal.
     * @param int $previousCatecheticalYear
     * @param int $previousCatechism
     * @param int $previousGroup
     * @param int $enrollmentCatecheticalYear
     */
    public function getCatechumensEnrollmentRenewalCandidateList(int $previousCatecheticalYear, int $previousCatechism,
                                                                 string $previousGroup, int $enrollmentCatecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            if($previousCatechism < intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                $sql = "SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, c.escuteiro, p.passa, 0 AS 'renovou' FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_prec AND p.ano_catecismo=:cat_prec AND p.turma=:turma_prec AND c.cid NOT IN ( SELECT p2.cid from pertence p2 WHERE p2.ano_lectivo=:ano_mat ) 
							UNION
							SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, c.escuteiro,  p.passa, 1 AS 'renovou' FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_prec AND p.ano_catecismo=:cat_prec AND p.turma=:turma_prec AND c.cid IN ( SELECT p2.cid from pertence p2 WHERE p2.ano_lectivo=:ano_mat ) 
							ORDER BY renovou, nome;";
            else
                $sql = "SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, c.escuteiro, p.passa, 0 AS 'renovou' FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_prec AND p.ano_catecismo=:cat_prec AND p.turma=:turma_prec AND p.passa=-1 AND c.cid NOT IN ( SELECT p2.cid from pertence p2 WHERE p2.ano_lectivo=:ano_mat ) 
							UNION
							SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, c.escuteiro,  p.passa, 1 AS 'renovou' FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_prec AND p.ano_catecismo=:cat_prec AND p.turma=:turma_prec AND c.cid IN ( SELECT p2.cid from pertence p2 WHERE p2.ano_lectivo=:ano_mat ) 
							ORDER BY renovou, nome;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_prec", $previousCatecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":cat_prec", $previousCatechism, PDO::PARAM_INT);
            $stm->bindParam(":turma_prec", $previousGroup);
            $stm->bindParam(":ano_mat", $enrollmentCatecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
                throw new Exception("Falha ao obter listagem de catequizandos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }




    /**
     * Returns all the renewals submitted online in a catechetical year.
     * If the optional 'catechism' parameter is passed, returns only results pertaining to that particular catechism.
     * @param $catecheticalYear - the current catechetical year, for which enrollments will take place
     * @param null $catechism
     * @return bool|PDOStatement|null
     */
    public function getRenewalSubmissions(int $catecheticalYear, int $catechism = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Build query
        $catecheticalYearStart = intval($catecheticalYear / 10000);
        $catecheticalYearEnd = $catecheticalYear % 10000;

        $sql = "SELECT rid, data_hora, endereco_ip, enc_edu_nome, enc_edu_tel, enc_edu_email, catequizando_nome, ultimo_catecismo, observacoes, processado, ano_catecismo_inscricao, turma_inscricao, ano_lectivo_inscricao FROM pedidoRenovacaoMatricula WHERE ((YEAR(data_hora)=:ano_i AND MONTH(data_hora) >= 7) OR (YEAR(data_hora)=:ano_f  AND MONTH(data_hora) < 7)) ";

        if(isset($catechism) && $catechism >= 1 && $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
            $sql = $sql . " AND ultimo_catecismo=:catecismo";

        $sql = $sql . " ORDER BY catequizando_nome;";

        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_i", $catecheticalYearStart, PDO::PARAM_INT);
            $stm->bindParam(":ano_f", $catecheticalYearEnd, PDO::PARAM_INT);
            if(isset($catechism) && $catechism >= 1 && $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
            {
                $catechism = intval($catechism);
                $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            }

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter pedidos de renovação de matrícula. Pâmetros inválidos.");
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns a renewal submission with a particular ID.
     * @param $rid
     * @return bool|PDOStatement|null
     */
    function getRenewalSubmission(int $rid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT rid, data_hora, endereco_ip, enc_edu_nome, enc_edu_tel, enc_edu_email, catequizando_nome, ultimo_catecismo, observacoes, processado, ano_catecismo_inscricao, turma_inscricao, ano_lectivo_inscricao FROM pedidoRenovacaoMatricula WHERE rid=:rid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":rid", $rid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetch();
            else
                throw new Exception("Falha ao obter pedido de renovação de matrícula. Pâmetros inválidos.");
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }




    /**
     * Returns all the enrollments submitted online in the corresponding catechetical year.
     * @param $catecheticalYear
     * @return array
     */
    public function getEnrollmentSubmissions(int $catecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        //Build query
        $catecheticalYearStart = intval($catecheticalYear / 10000);
        $catecheticalYearEnd = $catecheticalYear % 10000;

        $sql = "SELECT iid, data_hora, endereco_ip, nome, data_nasc, local_nasc, nif, num_irmaos, escuteiro, autorizou_fotos, autorizou_saida_sozinho, enc_edu, foto, obs, pai_nome, prof_pai, mae_nome, prof_mae, enc_edu_parentesco, enc_edu_nome, prof_enc_edu, casados_como, morada, cod_postal, telefone, telemovel, email, data_baptismo, paroquia_baptismo, data_comunhao, paroquia_comunhao, autorizacoesSaidaMenores, ultimo_catecismo, cid FROM pedidoInscricao WHERE ((YEAR(data_hora)=:ano_i AND MONTH(data_hora) >= 6) OR (YEAR(data_hora)=:ano_f  AND MONTH(data_hora) < 6)) ";
        $sql = $sql . " ORDER BY nome;";

        $result = [];
        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_i", $catecheticalYearStart, PDO::PARAM_INT);
            $stm->bindParam(":ano_f", $catecheticalYearEnd, PDO::PARAM_INT);

            if($stm->execute())
            {
                $aux = $stm->fetchAll();
                foreach($aux as $order)
                {
                    $order['autorizacoesSaidaMenores'] = unserialize($order['autorizacoesSaidaMenores']);
                    array_push($result, $order);
                }
            }
            else
            {
                throw new Exception("Falha ao obter pedidos de inscrição.");
            }
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $result;
    }




    /**
     * Returns an enrollment submitted online based on its ID.
     * @param $catecheticalYear
     * @return array
     */
    public function getEnrollmentSubmission(int $eid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $result = [];
        try
        {
            $sql = "SELECT iid, data_hora, endereco_ip, nome, data_nasc, local_nasc, nif, num_irmaos, escuteiro, autorizou_fotos, autorizou_saida_sozinho, enc_edu, foto, obs, pai_nome, prof_pai, mae_nome, prof_mae, enc_edu_parentesco, enc_edu_nome, prof_enc_edu, casados_como, morada, cod_postal, telefone, telemovel, email, data_baptismo, paroquia_baptismo, data_comunhao, paroquia_comunhao, autorizacoesSaidaMenores, ultimo_catecismo, cid FROM pedidoInscricao WHERE iid=:iid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":iid", $eid, PDO::PARAM_INT);

            if($stm->execute())
            {
                if($stm->rowCount() > 0)
                {
                    $result = $stm->fetch();
                    $result['autorizacoesSaidaMenores'] = unserialize($result['autorizacoesSaidaMenores']);
                }
            }
            else
            {
                throw new Exception("Falha ao obter pedido de inscrição.");
            }
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $result;
    }



    /**
     * Returns the number of pending renewals.
     * If a $catecheticalYear is provided, returns only results pertaining to that particular year.
     * Otherwise, returns all pending renewals in the database.
     * @param int $catecheticalYear
     * @return bool
     */
    public function getNumberOfPendingRenewals(int $catecheticalYear = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $result = NULL;
        try
        {
            //Build query
            $sql = "SELECT COUNT(rid) as 'pendingRenewals' FROM pedidoRenovacaoMatricula WHERE processado=0";

            if(isset($catecheticalYear))
                $sql = $sql . " AND ((YEAR(data_hora)=:ano_i AND MONTH(data_hora) >= 7) OR (YEAR(data_hora)=:ano_f  AND MONTH(data_hora) < 7)) ";
            $sql = $sql .  ";";

            $stm = $this->_connection->prepare($sql);

            if(isset($catecheticalYear))
            {
                $catecheticalYearStart = intval($catecheticalYear / 10000);
                $catecheticalYearEnd = $catecheticalYear % 10000;

                $stm->bindParam(":ano_i", $catecheticalYearStart, PDO::PARAM_INT);
                $stm->bindParam(":ano_f", $catecheticalYearEnd, PDO::PARAM_INT);
            }

            if($stm->execute())
            {
                $result = $stm->fetch();
                return $result['pendingRenewals'];
            }
            else
                throw new Exception("Falha ao obter pedidos de renovação de matrícula.");
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }




    /**
     * Returns the number of pending enrollments.
     * If a $catecheticalYear is provided, returns only results pertaining to that particular year.
     * Otherwise, returns all pending enrollments in the database.
     * @param int $catecheticalYear
     * @return bool
     */
    public function getNumberOfPendingEnrollments(int $catecheticalYear = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "SELECT COUNT(iid) as 'pendingEnrollments' FROM pedidoInscricao WHERE cid IS NULL";

            if(isset($catecheticalYear))
                $sql = $sql . " AND ((YEAR(data_hora)=:ano_i AND MONTH(data_hora) >= 7) OR (YEAR(data_hora)=:ano_f  AND MONTH(data_hora) < 7)) ";
            $sql = $sql .  ";";

            $stm = $this->_connection->prepare($sql);

            if(isset($catecheticalYear))
            {
                $catecheticalYearStart = intval($catecheticalYear / 10000);
                $catecheticalYearEnd = $catecheticalYear % 10000;

                $stm->bindParam(":ano_i", $catecheticalYearStart, PDO::PARAM_INT);
                $stm->bindParam(":ano_f", $catecheticalYearEnd, PDO::PARAM_INT);
            }

            if($stm->execute())
            {
                $result = $stm->fetch();
                return $result['pendingEnrollments'];
            }
            else
                throw new Exception("Falha ao obter pedidos de inscrição.");
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }





    /**
     * Submits a renewal order to the database.
     * Returns the ID of the renewal order (rid).
     * @param $applicantName
     * @param $phone
     * @param $email
     * @param $catechumenName
     * @param $lastCatechism
     * @param $obs
     * @return string|null
     */
    public function postRenewalOrder(string $applicantName, string $phone, string $catechumenName, int $lastCatechism,
                                     string $ipAddress, string $email = null, string $obs = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::ONLINE_ENROLLMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "INSERT INTO pedidoRenovacaoMatricula(data_hora, endereco_ip, enc_edu_nome, enc_edu_tel, enc_edu_email, catequizando_nome, ultimo_catecismo, observacoes, processado) VALUES(NOW(), :endereco_ip, :enc_edu_nome, :enc_edu_tel, :enc_edu_email, :catequizando_nome, :ultimo_catecismo, :observacoes, 0);";

            $stm = $this->_connection->prepare($sql);

            $mynull = NULL;
            $stm->bindParam(":endereco_ip", $ipAddress, PDO::PARAM_STR);
            $stm->bindParam(":enc_edu_nome", $applicantName, PDO::PARAM_STR);
            $stm->bindParam(":enc_edu_tel", $phone, PDO::PARAM_STR);
            if(isset($email) && $email!=null && $email!="")
                $stm->bindParam(":enc_edu_email", $email, PDO::PARAM_STR);
            else
                $stm->bindParam(":enc_edu_email", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":catequizando_nome", $catechumenName, PDO::PARAM_STR);
            $stm->bindParam(":ultimo_catecismo", $lastCatechism, PDO::PARAM_INT);
            if(isset($obs) && $obs!=null && $obs!="")
                $stm->bindParam(":observacoes", $obs, PDO::PARAM_STR);
            else
                $stm->bindParam(":observacoes", $mynull, PDO::PARAM_NULL);


            if($stm->execute())
                return $this->_connection->lastInsertId();
            else
                throw new Exception("Falha aoregistar pedido de renovaçao de matricula.");
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Posts an enrollment order to the database.
     * @param string $catechumenName
     * @param string $birthDay
     * @param string $birthPlace
     * @param int $nSiblings
     * @param string $address
     * @param string $postalCode
     * @param int $responsibleIndex - {0=father, 1=mother, 2=other}
     * @param string $ipAddress
     * @param bool $scout
     * @param bool $photosAllowed
     * @param bool $exitAllowed
     * @param array $exitAuthorizations - php array of objects, each one with fields {'nome', 'parentesco', 'telefone'}
     * @param string|null $photo
     * @param string|null $obs
     * @param string|null $responsibleName
     * @param string|null $responsibleJob
     * @param string|null $responsibleRelationship
     * @param string|null $fatherName
     * @param string|null $fatherJob
     * @param string|null $motherName
     * @param string|null $motherJob
     * @param int|null $marriageType - one of the constants in the class Marriage, or null
     * @param string|null $phone
     * @param string|null $cellPhone
     * @param string|null $email
     * @param string|null $baptismDate
     * @param string|null $baptismParish
     * @param string|null $comunionDate
     * @param string|null $comunionParish
     * @param int|null $lastCatechism
     * @return mixed
     * @throws Exception
     */
    public function postEnrollmentOrder(string $catechumenName, string $birthDay, string $birthPlace, string $nif = null,              // Registers a new enrollment order
                                        int $nSiblings, string $address, string $postalCode,
                                        int $responsibleIndex, string $ipAddress,
                                        bool $scout, bool $photosAllowed, bool $exitAllowed, array $exitAuthorizations,
                                        string $photo = null, string $obs = null,
                                        string $responsibleName = null, string $responsibleJob = null,
                                        string $responsibleRelationship = null,
                                        string $fatherName = null, string $fatherJob = null,
                                        string $motherName = null, string $motherJob = null,
                                        int $marriageType = null,
                                        string $phone = null, string $cellPhone = null, string $email = null,
                                        string $baptismDate = null, string $baptismParish = null,
                                        string $comunionDate = null, string $comunionParish = null,
                                        int $lastCatechism = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::ONLINE_ENROLLMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Convert parameters
        if($scout)
            $scout = 1;
        else
            $scout = 0;
        if($photosAllowed)
            $photosAllowed = 1;
        else
            $photosAllowed = 0;
        if($exitAllowed)
            $exitAllowed = 1;
        else
            $exitAllowed = 0;
        if(!isset($photo))
            $photo = '';
        if(!isset($obs))
            $obs = '';
        if(!isset($marriageType))
            $marriage = null;
        else
            $marriage = $this->translateMarriageTypeIntoString($marriageType);

        $mynull = NULL;

        try
        {
            //Build query
            $sql = "INSERT INTO pedidoInscricao(data_hora, endereco_ip, nome, data_nasc, local_nasc, nif, num_irmaos, escuteiro, autorizou_fotos, autorizou_saida_sozinho, enc_edu, foto, obs, pai_nome, prof_pai, mae_nome, prof_mae, enc_edu_parentesco, enc_edu_nome, prof_enc_edu, casados_como, morada, cod_postal, telefone, telemovel, email, data_baptismo, paroquia_baptismo, data_comunhao, paroquia_comunhao, autorizacoesSaidaMenores, ultimo_catecismo, cid)";
            $sql = $sql . "VALUES(NOW(), :endereco_ip, :nome, STR_TO_DATE(:data_nasc, '%d-%m-%Y'), :local_nasc, :nif, :num_irmaos, :escuteiro, :autorizou_fotos, :autorizou_saida_sozinho, :enc_edu, :foto, :obs, :pai_nome, :prof_pai, :mae_nome, :prof_mae, :enc_edu_parentesco, :enc_edu_nome, :prof_enc_edu, :casados_como, :morada, :cod_postal, :telefone, :telemovel, :email, STR_TO_DATE(:data_baptismo, '%d-%m-%Y'), :paroquia_baptismo, STR_TO_DATE(:data_comunhao, '%d-%m-%Y'), :paroquia_comunhao, :autorizacoesSaidaMenores, :ultimo_catecismo, NULL);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":endereco_ip", $ipAddress, PDO::PARAM_STR);
            $stm->bindParam(":nome", $catechumenName, PDO::PARAM_STR);
            $stm->bindParam(":data_nasc", $birthDay, PDO::PARAM_STR);
            $stm->bindParam(":local_nasc", $birthPlace, PDO::PARAM_STR);
            if(isset($nif) && $nif!="")
                $stm->bindParam(":nif", $nif, PDO::PARAM_INT);
            else
                $stm->bindParam(":nif", $mynull, PDO::PARAM_NULL);
            $stm->bindParam(":num_irmaos", $nSiblings, PDO::PARAM_INT);
            $stm->bindParam(":escuteiro", $scout, PDO::PARAM_INT);
            $stm->bindParam(":autorizou_fotos", $photosAllowed, PDO::PARAM_INT);
            $stm->bindParam(":autorizou_saida_sozinho", $exitAllowed, PDO::PARAM_INT);
            $stm->bindParam(":enc_edu", $responsibleIndex, PDO::PARAM_INT);
            $stm->bindParam(":foto", $photo, PDO::PARAM_STR);
            $stm->bindParam(":obs", $obs, PDO::PARAM_STR);

            if(isset($fatherName) && $fatherName!="")
                $stm->bindParam(":pai_nome", $fatherName, PDO::PARAM_STR);
            else
                $stm->bindParam(":pai_nome", $mynull, PDO::PARAM_NULL);
            if(isset($fatherJob) && $fatherJob!="")
                $stm->bindParam(":prof_pai", $fatherJob, PDO::PARAM_STR);
            else
                $stm->bindParam(":prof_pai", $mynull, PDO::PARAM_NULL);

            if(isset($motherName) && $motherName!="")
                $stm->bindParam(":mae_nome", $motherName, PDO::PARAM_STR);
            else
                $stm->bindParam(":mae_nome", $mynull, PDO::PARAM_NULL);
            if(isset($motherJob) && $motherJob!="")
                $stm->bindParam(":prof_mae", $motherJob, PDO::PARAM_STR);
            else
                $stm->bindParam(":prof_mae", $mynull, PDO::PARAM_NULL);

            if($responsibleIndex == 2) //Responsible other than mother or father
            {
                if(isset($responsibleRelationship) && $responsibleRelationship != "")
                    $stm->bindParam(":enc_edu_parentesco", $responsibleRelationship, PDO::PARAM_STR);
                else
                    $stm->bindParam(":enc_edu_parentesco", $mynull, PDO::PARAM_NULL);

                if(isset($responsibleName) && $responsibleName != "")
                    $stm->bindParam(":enc_edu_nome", $responsibleName, PDO::PARAM_STR);
                else
                    $stm->bindParam(":enc_edu_nome", $mynull, PDO::PARAM_NULL);

                if(isset($responsibleJob) && $responsibleJob != "")
                    $stm->bindParam(":prof_enc_edu", $responsibleJob, PDO::PARAM_STR);
                else
                    $stm->bindParam(":prof_enc_edu", $mynull, PDO::PARAM_NULL);
            }
            else
            {
                $stm->bindParam(":enc_edu_parentesco", $mynull, PDO::PARAM_NULL);
                $stm->bindParam(":enc_edu_nome", $mynull, PDO::PARAM_NULL);
                $stm->bindParam(":prof_enc_edu", $mynull, PDO::PARAM_NULL);
            }

            if(isset($marriage) && $marriage!="")
                $stm->bindParam(":casados_como", $marriage, PDO::PARAM_STR);
            else
                $stm->bindParam(":casados_como", $mynull, PDO::PARAM_NULL);

            $stm->bindParam(":morada", $address, PDO::PARAM_STR);
            $stm->bindParam(":cod_postal", $postalCode, PDO::PARAM_STR);

            if(isset($phone) && $phone!="")
                $stm->bindParam(":telefone", $phone, PDO::PARAM_STR);
            else
                $stm->bindParam(":telefone", $mynull, PDO::PARAM_NULL);

            if(isset($cellPhone) && $cellPhone!="")
                $stm->bindParam(":telemovel", $cellPhone, PDO::PARAM_STR);
            else
                $stm->bindParam(":telemovel", $mynull, PDO::PARAM_NULL);

            if(isset($email) && $email!=null && $email!="")
                $stm->bindParam(":email", $email, PDO::PARAM_STR);
            else
                $stm->bindParam(":email", $mynull, PDO::PARAM_NULL);

            if(isset($baptismDate) && $baptismDate!=null && $baptismDate!="")
                $stm->bindParam(":data_baptismo", $baptismDate, PDO::PARAM_STR);
            else
                $stm->bindParam(":data_baptismo", $mynull, PDO::PARAM_NULL);

            if(isset($baptismParish) && $baptismParish!=null && $baptismParish!="")
                $stm->bindParam(":paroquia_baptismo", $baptismParish, PDO::PARAM_STR);
            else
                $stm->bindParam(":paroquia_baptismo", $mynull, PDO::PARAM_NULL);

            if(isset($comunionDate) && $comunionDate!=null && $comunionDate!="")
                $stm->bindParam(":data_comunhao", $comunionDate, PDO::PARAM_STR);
            else
                $stm->bindParam(":data_comunhao", $mynull, PDO::PARAM_NULL);

            if(isset($comunionParish) && $comunionParish!=null && $comunionParish!="")
                $stm->bindParam(":paroquia_comunhao", $comunionParish, PDO::PARAM_STR);
            else
                $stm->bindParam(":paroquia_comunhao", $mynull, PDO::PARAM_NULL);


            $exitAuthorizations = serialize($exitAuthorizations);
            $stm->bindParam(":autorizacoesSaidaMenores", $exitAuthorizations, PDO::PARAM_STR);

            if(isset($lastCatechism) && $lastCatechism!="" && $lastCatechism >=1 && $lastCatechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                $stm->bindParam(":ultimo_catecismo", $lastCatechism, PDO::PARAM_INT);
            else
                $stm->bindParam(":ultimo_catecismo", $mynull, PDO::PARAM_NULL);



            if($stm->execute())
                return $this->_connection->lastInsertId();
            else
            {
                //print_r($stm->errorInfo()); //DEBUG
                throw new Exception("Falha ao registar pedido de inscrição. Pâmetros inválidos.");
            }
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the status of a renewal order submission.
     * If 'status' is EnrollmentStatus::PROCESSED, the submission is marked as processed.
     * If 'status' is EnrollmentStatus::PENDING, the submission is marked as pending processing.
     * @param int $rid
     * @param int $status - One of the constants defined in the domain class EnrollmentStatus
     * @return bool
     */
    public function updateRenewalOrderStatus(int $rid, int $status, int $enrollmentCatecheticalYear = null,
                                             int $enrollmentCatechism = null, string $enrollmentGroup = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $mynull = null;
        $processed = 0;
        switch($status)
        {
            case EnrollmentStatus::PENDING:
                $processed = 0;
                break;

            case EnrollmentStatus::PROCESSED:
                $processed = 1;
                break;

            default:
                throw new Exception('Erro ao atualizar estado do pedido de renovação de matrícula. Estado desconhecido.');
        }

        $sql = "UPDATE pedidoRenovacaoMatricula SET processado=:estado, ano_lectivo_inscricao=:ano_lectivo, ano_catecismo_inscricao=:catecismo, turma_inscricao=:turma WHERE rid=:rid;";

        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":estado", $processed, PDO::PARAM_INT);
            $stm->bindParam(":rid", $rid, PDO::PARAM_INT);
            if(isset($enrollmentCatecheticalYear))
                $stm->bindParam(":ano_lectivo", $enrollmentCatecheticalYear, PDO::PARAM_INT);
            else
                $stm->bindParam(":ano_lectivo", $mynull, PDO::PARAM_NULL);
            if(isset($enrollmentCatechism))
                $stm->bindParam(":catecismo", $enrollmentCatechism, PDO::PARAM_INT);
            else
                $stm->bindParam(":catecismo", $mynull, PDO::PARAM_NULL);
            if(isset($enrollmentGroup))
                $stm->bindParam(":turma", $enrollmentGroup, PDO::PARAM_STR);
            else
                $stm->bindParam(":turma", $mynull, PDO::PARAM_NULL);

            return $stm->execute();
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes a renewal order from the database.
     * @param int $rid
     * @return bool
     */
    public function deleteRenewalOrder(int $rid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM pedidoRenovacaoMatricula WHERE rid=:rid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":rid", $rid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Marks an enrollment order as processed, either with a corresponding catechumen file (cid) or without it (if cid= is null).
     * @param $eid - Enrollment order ID
     * @param $cid - ID of the catechumen file to associate with the processed order, or null
     */
    public function updateEnrollmentOrderFile(int $eid, int $cid = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        // Internal code for processed enrollment orders without an associated file is cid=-1s
        if(!isset($cid))
            $cid = -1;

        try
        {
            $sql = "UPDATE pedidoInscricao SET cid=:cid WHERE iid=:iid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":iid", $eid, PDO::PARAM_INT);
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes an enrollment order from the database.
     * @param int $eid
     * @return mixed
     * @throws Exception
     */
    public function deleteEnrollmentOrder(int $eid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM pedidoInscricao WHERE iid=:iid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":iid", $eid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch(PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }





    /**
     * Returns all users registered in the system, with basic account information.
     * Details about catechists are also included, in case of users which are catechists.
     */
    public function getAllUsers()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::USER_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT users.username, users.nome, users.estado AS u_estado, c.estado AS cat_estado, COALESCE(users.estado, 0) + COALESCE(c.estado, 0) > 0 AS mostrar, users.admin, users.tel, users.email FROM catequista c RIGHT OUTER JOIN (SELECT u.username, u.nome, u.admin, u.tel, u.email, u.estado FROM utilizador u, ul_logins ul WHERE u.username=ul.username) AS users ON (c.username=users.username) ORDER BY mostrar DESC, users.nome ASC;";
            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter contas de utlizadores.");
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the catechists that are currently active, i.e. ready to be teach.
     * @return mixed
     * @throws Exception
     */
    public function getActiveCatechists()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT u.nome, c.username FROM catequista c, utilizador u WHERE c.username=u.username AND c.estado=1 ORDER BY nome;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os catequistas ativos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns account status for a user:
     * user full name, admin flag, account status (active/inactive) and catechist status (active/inactive/not catechist).
     * @param string $username
     * @return array
     * @throws Exception
     */
    public function getUserAccountStatus(string $username)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $result = [];
        try
        {
            $sql = "SELECT nome, admin, u.estado AS u_estado, c.estado AS c_estado FROM utilizador u LEFT OUTER JOIN catequista c ON(u.username=c.username) WHERE u.username COLLATE utf8_bin = :username COLLATE utf8_bin;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":username", $username);

            if($stm->execute())
            {
                $row = $stm->fetch();
                $result["name"] = $row['nome'];
                $result["admin"] = ($row['admin']==1);
                $result["account_status"] = $row['u_estado'];
                if(isset($row['c_estado']) && ($row['c_estado']==0 || $row['c_estado']==1))
                    $result['catechist_status'] = $row['c_estado'];  //Active/inactive catechist
                else
                    $result['catechist_status'] = -1;                //User is not a catechist
            }
            else
                throw new Exception("Falha ao obter os dados da conta de utilizador.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $result;
    }


    /**
     * Returns a user's full name, phone and e-mail.
     * @param string $username
     * @return array
     * @throws Exception
     */
    public function getUserAccountDetails(string $username)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $result = [];
        try
        {
            $sql = "SELECT nome, email, tel FROM utilizador WHERE username=:username;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":username", $username);

            if($stm->execute())
            {
                $row = $stm->fetch();
                $result['name'] = $row['nome'];
                $result['phone'] = $row['tel'];
                $result['email'] = $row['email'];
            }
            else
                throw new Exception("Falha ao obter os dados da conta de utilizador.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $result;
    }


    /**
     * Creates a new user/catechist account.
     * @param string $username
     * @param string $name
     * @param bool $isAdmin                 - Whether this user should have administrator privileges.
     * @param bool $isCatechist             - Whether this user is also a catechist.
     * @param bool $isCatechistActive       - Whether this catechist is currently active (only makes sense if the previous is true)
     * @param null $phone                   - [optional]
     * @param null $email                   - [optional]
     */
    public function createUserAccount(string $username, string $name, string $password, bool $isAdmin, bool $isCatechist,
                                      bool $isCatechistActive=true, $phone=null, $email=null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::USER_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $this->beginTransaction(DatabaseAccessMode::USER_MANAGEMENT);
        $ulogin = new uLogin();

        $mynull = null;
        $admin_int = $isAdmin?1:0;
        $catechist_status_int = $isCatechistActive?1:0;

        try
        {
            $sql = "INSERT INTO utilizador(username, nome, admin, tel, email, estado) VALUES(:un, :nome, :admin, :tel, :email, 1);";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":un", $username);
            $stm->bindParam(":nome", $name);
            $stm->bindParam(":admin", $admin_int, PDO::PARAM_INT);
            if(isset($phone) && $phone!=0 && $phone!='')
                $stm->bindParam(":tel", $phone, PDO::PARAM_INT);
            else
                $stm->bindParam(":tel", $mynull, PDO::PARAM_NULL);
            if(isset($email) && $email!="")
                $stm->bindParam(":email", $email);
            else
                $stm->bindParam(":email", $mynull, PDO::PARAM_NULL);


            if($stm->execute())
            {
                if($isCatechist)
                {
                    $sql = "INSERT INTO catequista(username, estado) VALUES(:un, :estado);";
                    $stm = $this->_connection->prepare($sql);

                    $stm->bindParam(":un", $username);
                    $stm->bindParam(":estado", $catechist_status_int, PDO::PARAM_INT);

                    if($stm->execute())
                    {
                        if ( !$ulogin->CreateUser($username, $password) )
                        {
                            $this->rollBack();
                            throw new Exception("Falha ao criar catequista.");
                        }
                        else
                        {
                            $this->commit();
                            return true;
                        }
                    }
                    else
                    {
                        $this->rollBack();
                        throw new Exception("Falha ao criar catequista.");
                    }
                }
                else
                {
                    if ( !$ulogin->CreateUser($username,  $password) )
                    {
                        $this->rollBack();
                        throw new Exception("Falha ao criar conta de utilizador.");
                    }
                    else
                    {
                        $this->commit();
                        return true;
                    }
                }
            }
            else
            {
                $this->rollBack();
                throw new Exception("Falha ao criar conta de utilizador.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates a user's account name, phone and e-mail.
     * Returns true in case of success. Throws an exception otherwise.
     * @param string $username
     * @param string $name
     * @param $phone
     * @param $email
     * @return bool
     * @throws Exception
     */
    public function updateUserAccountDetails(string $username, string $name, $phone, $email)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::USER_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $mynull = NULL;

        try
        {
            $sql = "UPDATE utilizador SET nome=:nome, tel=:telefone, email=:email WHERE username=:username;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":username", $username);
            $stm->bindParam(":nome", $name);
            if(isset($phone) && $phone!=0)
                $stm->bindParam(":telefone", $phone, PDO::PARAM_INT);
            else
                $stm->bindParam(":telefone", $mynull, PDO::PARAM_NULL);
            if(isset($email) && $email!="")
                $stm->bindParam(":email", $email);
            else
                $stm->bindParam(":email", $mynull, PDO::PARAM_NULL);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Activates or blocks a user account.
     * @param string $username
     * @param bool $active
     */
    public function changeUserAccountStatus(string $username, bool $active)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::USER_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE utilizador SET estado=:estado WHERE username=:username;";

            $stm = $this->_connection->prepare($sql);

            $status = $active?1:0;
            $stm->bindParam(":username", $username);
            $stm->bindParam(":estado", $status, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Activates a user account.
     * @param string $username
     */
    public function activateUserAccount(string $username)
    {
        //This is really just syntactic sugar
        return $this->changeUserAccountStatus($username, true);
    }


    /**
     * Blocks a user account.
     * @param string $username
     */
    public function blockUserAccount(string $username)
    {
        //This is really just syntactic sugar
        return $this->changeUserAccountStatus($username, false);
    }


    /**
     * Sets or unsets a user's admin flag.
     * @param string $username
     * @param bool $isAdmin
     */
    public function setUserAsAdmin(string $username, bool $isAdmin)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::USER_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE utilizador SET admin=:admin WHERE username=:username;";

            $stm = $this->_connection->prepare($sql);

            $adminStatus = $isAdmin?1:0;
            $stm->bindParam(":username", $username);
            $stm->bindParam(":admin", $adminStatus, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Give admin rights to a user.
     * @param string $username
     */
    public function giveAdminRights(string $username)
    {
        //This is really just syntactic sugar
        return $this->setUserAsAdmin($username, true);
    }


    /**
     * Removes admin rights from a user.
     * @param string $username
     */
    public function revokeAdminRights(string $username)
    {
        //This is really just syntactic sugar
        return $this->setUserAsAdmin($username, false);
    }


    /**
     * Sets the catechist (teaching) status.
     * If the user is not currently registered as a catechist, his/her account is automatically upgraded to a catechist
     * one, and then the status is set.
     * @param string $username
     * @param bool $isActive
     */
    public function setCatechistStatus(string $username, bool $isActive)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::USER_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Assume that the user is not yet registered as a catechist, and try to regiter it with the appropriate status
            $sql = "INSERT INTO catequista(username, estado) VALUES(:username, :estado);";

            $stm = $this->_connection->prepare($sql);

            $catechistStatus = $isActive?1:0;
            $stm->bindParam(":username", $username);
            $stm->bindParam(":estado", $catechistStatus, PDO::PARAM_INT);

            if($stm->execute())
                return true;
            else
                throw new Exception("Falha ao atualizar estado do catequista.");
        }
        catch (PDOException $e)
        {
            try
            {
                //The user is already registered as catechist. We just need to update his/her status
                $sql = "UPDATE catequista SET estado=:estado WHERE username=:username;";

                $stm = $this->_connection->prepare($sql);

                $catechistStatus = $isActive?1:0;
                $stm->bindParam(":username", $username);
                $stm->bindParam(":estado", $catechistStatus, PDO::PARAM_INT);

                return $stm->execute();
            }
            catch (PDOException $e)
            {
                //echo $e->getMessage();
                throw new Exception("Falha interna ao tentar aceder à base de dados.");
            }
        }
    }


    /**
     * Sets the user/catechist as an active catechist.
     * If the user is not currently registered as a catechist, his/her account is automatically upgraded to a catechist
     * one, and then the status is set.
     * @param string $username
     */
    public function setAsActiveCatechist(string $username)
    {
        //This is really just syntactic sugar
        return $this->setCatechistStatus($username, true);
    }

    /**
     * Sets the user/catechist as an inactive catechist.
     * If the user is not currently registered as a catechist, his/her account is automatically upgraded to a catechist
     * one, and then the status is set.
     * @param string $username
     */
    public function setAsInactiveCatechist(string $username)
    {
        //This is really just syntactic sugar
        return $this->setCatechistStatus($username, false);
    }


    /**
     * Returns all the groups where this catechist teaches.
     * @param string $username
     * @param int $catecheticalYear
     * @return mixed
     * @throws Exception
     */
    public function getCatechistGroups(string $username, int $catecheticalYear)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT ano_lectivo, ano_catecismo, turma FROM lecciona l WHERE l.username=:username AND l.ano_lectivo=:ano_lectivo ORDER BY ano_lectivo DESC, ano_catecismo ASC, turma ASC;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":username", $username);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os dados da conta de utilizador.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the catechists already assigned to at least one group, in the given catechetical year.
     * @param int $catecheticalYear
     */
    public function getAllAssignedCatechists(int $catecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT c.username FROM lecciona l, catequista c WHERE l.username=c.username AND ano_lectivo=:ano_catequetico;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":ano_catequetico", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter a listagem de catequistas atribuídos a grupos de catequese.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the catechists of a group.
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function getGroupCatechists(int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT u.nome, u.username FROM utilizador u, lecciona l WHERE l.username=u.username and l.ano_catecismo=:catecismo and l.turma=:turma and l.ano_lectivo=:ano ORDER BY u.nome;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":ano", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os dados dos catequistas.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Adds a catechist to a catechesis group.
     * @param string $username
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function addCatechistToGroup(string $username, int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::GROUP_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "INSERT INTO lecciona(username, ano_catecismo, turma, ano_lectivo) VALUES (:catequista, :cat_catecismo, :cat_turma, :cat_ano_catequetico);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":catequista", $username);
            $stm->bindParam(":cat_catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":cat_turma", $group);
            $stm->bindParam(":cat_ano_catequetico", $catecheticalYear, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Removes a catechist from a catechesis group.
     * @param string $username
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function removeCatechistFromGroup(string $username, int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::GROUP_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM lecciona WHERE username=:catequista AND ano_catecismo=:catecismo AND turma=:turma AND ano_lectivo=:ano_catequetico;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":catequista", $username);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":ano_catequetico", $catecheticalYear, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns true if a catechumen belongs to a catechist's group, and false otherwise.
     * This is a more lightweight approach to check if a user has permissions over a catechumen file than
     * querying all the catechumens of a catechist with getCatechumensByCatechismWithFilters().
     * @param int $cid
     * @param string $username
     * @param int $catecheticalYear
     */
    public function checkIfCatechumenBelongsToCatechist(int $cid, string $username, int $catecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT cid FROM pertence p, lecciona l WHERE p.cid = :cid AND p.ano_catecismo=l.ano_catecismo AND p.ano_lectivo=l.ano_lectivo AND p.turma=l.turma AND l.username=:username AND l.ano_lectivo=:ano_lectivo;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":username", $username);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
            {
                $result = $stm->fetchAll();
                return isset($result) && count($result)>=1;
            }
            else
                return false;
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns all the distinct catechetical years in the database.
     * @return mixed
     * @throws Exception
     */
    public function getCatecheticalYears()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT ano_lectivo FROM grupo ORDER BY ano_lectivo DESC;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os anos catequéticos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the distinct catechisms in the database.
     * If a catecheticalYear is provided (optional), returns only catechisms in that catechetical year.
     * @param int|null $catecheticalYear
     * @return mixed
     * @throws Exception
     */
    public function getCatechisms(int $catecheticalYear = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "SELECT DISTINCT ano_catecismo FROM grupo";
            if(isset($catecheticalYear))
                $sql = $sql . " WHERE ano_lectivo=:ano_actual ";
            $sql = $sql . " ORDER BY ano_catecismo;";

            $stm = $this->_connection->prepare($sql);

            if(isset($catecheticalYear))
                $stm->bindParam(":ano_actual", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
                throw new Exception("Falha ao obter os catecismos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the groups for a particular catechism and year.
     * @param int $catecheticalYear
     * @param int $catechism
     */
    public function getCatechismGroups(int $catecheticalYear, int $catechism)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $result = null;
        try
        {
            //Build query
            $sql = "SELECT DISTINCT turma FROM grupo WHERE ano_catecismo=:catecismo AND ano_lectivo=:ano_lectivo ORDER BY turma ASC;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);;

            if($stm->execute())
            {
                return $stm->fetchAll();
            }
            else
                throw new Exception("Falha ao obter os grupos de catequese.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

    }



    /**
     * Returns all distinct pairs of (catechism, group).
     * @param int|null $catecheticalYear
     */
    public function getCatechismsAndGroups(int $catecheticalYear = null)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "SELECT DISTINCT ano_catecismo, turma FROM grupo";
            if(isset($catecheticalYear))
                $sql = $sql . " WHERE ano_lectivo=:ano_actual ";
            $sql = $sql . " ORDER BY ano_catecismo, turma;";

            $stm = $this->_connection->prepare($sql);

            if(isset($catecheticalYear))
                $stm->bindParam(":ano_actual", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os catecismos e grupos de catequese.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the pairs of (catechism, group) from the latest registered year in the database.
     */
    public function getCatechismsAndGroupsFromLatestYear()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "SELECT ano_catecismo, turma, ano_lectivo FROM grupo WHERE ano_lectivo >= ALL(SELECT ano_lectivo FROM grupo);";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os catecismos e grupos de catequese.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns true if a particular catechism exists in the database.
     * @param int $catecheticalYear
     * @param int $catechism
     * @return bool
     */
    public function hasCatechism(int $catecheticalYear, int $catechism)
    {
        try
        {
            $result = $this->getCatechismGroups($catecheticalYear, $catechism);
            return isset($result) && count($result) >= 1;
        }
        catch (Exception $e)
        {
            return false;
        }
    }


    /**
     * Returns all the distinct group (class) letters in the database.
     * If a catecheticalYear is provided (optional), returns only groups in that catechetical year.
     * @param int|null $catecheticalYear
     * @return mixed
     * @throws Exception
     */
    public function getGroupLetters(int $catecheticalYear = null)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT turma FROM grupo";
            if(isset($catecheticalYear))
                $sql = $sql . " WHERE ano_lectivo=:ano_actual ";
            $sql = $sql . " ORDER BY turma;";

            $stm = $this->_connection->prepare($sql);

            if(isset($catecheticalYear))
                $stm->bindParam(":ano_actual", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os grupos de catequese.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Inserts a new catechism group.
     * Returns true in case of success, and false (or an exception) if something goes wrong.
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function createCatechismGroup(int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $result = null;
        try
        {
            $sql = "INSERT INTO grupo(ano_catecismo, turma, ano_lectivo) VALUES (:catecismo, :turma, :ano_lectivo);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (Exception $e)
        {
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes a group from the database.
     * Also atomically removes all the links from catechists teaching this group (but does not delete the catechists themselves).
     * Returns true of the whole operation suceeded, and false (or an Exception) if something went wrong during the process. In the
     * latter case, the database changes are rolled back.
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function deleteCatechismGroup(int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::GROUP_MANAGEMENT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $this->beginTransaction(DatabaseAccessMode::GROUP_MANAGEMENT);

        try
        {
            // Remove catechists from this group
            $sql = "DELETE FROM lecciona WHERE ano_catecismo=:el_catecismo AND turma=:el_turma AND ano_lectivo=:el_ano_catequetico;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":el_catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":el_turma", $group);
            $stm->bindParam(":el_ano_catequetico", $catecheticalYear, PDO::PARAM_INT);

            if(!$stm->execute())
            {
                $this->rollBack();
                throw new Exception("Falha ao remover os catequistas do grupo. Não foi possível eliminar o grupo de catequese.");
            }


            // Delete the group
            $sql = "DELETE FROM grupo WHERE ano_catecismo=:el_catecismo AND turma=:el_turma AND ano_lectivo=:el_ano_catequetico;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":el_catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":el_turma", $group);
            $stm->bindParam(":el_ano_catequetico", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
            {
                $this->commit();
                return true;
            }
            else
            {
                $this->rollBack();
                throw new Exception("Falha ao eliminar o grupo de catequese.");
            }

        }
        catch (Exception $e)
        {
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Enrolls a catechumen in a catechesis group.
     * Returns true if the enrollment was successful, and false (or an Exception) in case something went wrong.
     * In case of failure, the database is rolledback to its original state.
     * @param int $cid
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     * @param bool $pass                - Whether the catechumen passes or fails(ed) this catechetical year.
     * @param bool $paid                - Whether the enrollment has been paid or not.
     * @param string $username          - Username of the user that processed the enrollment.
     */
    public function enrollCatechumenInGroup(int $cid, int $catecheticalYear, int $catechism, string $group,
                                            bool $pass, bool $paid, string $username)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $this->beginTransaction(DatabaseAccessMode::DEFAULT_EDIT);

        $pass_int = $pass?1:-1;
        $paid_int = $paid?1:0;

        try
        {

            //Add the catechumen to the group
            $sql = "INSERT INTO pertence(cid, ano_catecismo, turma, ano_lectivo, passa) values (:cid, :catecismo, :turma, :ano_lectivo, :passa);";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":passa", $pass_int, PDO::PARAM_INT);

            if($stm->execute())
            {
                //Register the enrollment act
                $sql = "INSERT INTO inscreve(username, cid, ano_catecismo, turma, ano_lectivo, pago) values (:username, :cid, :catecismo, :turma, :ano_lectivo, :pago);";
                $stm = $this->_connection->prepare($sql);

                $stm->bindParam(":username", $username);
                $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
                $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
                $stm->bindParam(":turma", $group);
                $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);
                $stm->bindParam(":pago", $paid_int, PDO::PARAM_INT);

                if($stm->execute())
                {
                    $this->commit();
                    return true;
                }
                else
                {
                    $this->rollBack();
                    throw new Exception("Falha ao registar inscrição do catequizando na catequese.");
                }
            }
            else
            {
                $this->rollBack();
                throw new Exception("Falha ao inscrever catequizando no grupo de catequese.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Unenrolls a catechumen from a catechesis group.
     * @param int $cid
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function unenrollCatechumenFromGroup(int $cid, int $catecheticalYear, int $catechism, string $group)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $this->beginTransaction(DatabaseAccessMode::DEFAULT_DELETE);

        try
        {
            //Delete the enrollment act
            $sql = "DELETE FROM inscreve WHERE cid=:cid AND ano_catecismo=:catecismo AND turma=:turma AND ano_lectivo=:ano_lectivo;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

            if($stm->execute())
            {
                //Delete the catechumen from the group
                $sql = "DELETE FROM pertence WHERE cid=:cid AND ano_catecismo=:catecismo AND turma=:turma AND ano_lectivo=:ano_lectivo;";
                $stm = $this->_connection->prepare($sql);

                $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
                $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
                $stm->bindParam(":turma", $group);
                $stm->bindParam(":ano_lectivo", $catecheticalYear, PDO::PARAM_INT);

                if($stm->execute())
                {
                    $this->commit();
                    return true;
                }
                else
                {
                    $this->rollBack();
                    throw new Exception("Falha ao remover catequizando do grupo de catequese.");
                }
            }
            else
            {
                $this->rollBack();
                throw new Exception("Falha ao remover inscrição do catequizando na catequese.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            $this->rollBack();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Unenrolls a catechumen from all the groups where he/she is enrolled.
     * This is useful when deleting a whole catechumen file.
     *
     * If $useTransaction is false, it does not use database transactions
     * (useful in case you are colling this in the middle of another transaction, e.g. to delete a whole catechumen file).
     * @param int $cid
     */
    public function unenrollCatechumenFromAllGroups(int $cid, bool $useTransaction=true)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        if($useTransaction)
            $this->beginTransaction(DatabaseAccessMode::DEFAULT_DELETE);

        try
        {
            //Delete all enrollment acts
            $sql = "DELETE FROM inscreve WHERE cid=:cid;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
            {
                //Delete the catechumen from all groups
                $sql = "DELETE FROM pertence WHERE cid=:cid ;";
                $stm = $this->_connection->prepare($sql);

                $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

                if($stm->execute())
                {
                    if($useTransaction)
                        $this->commit();
                    return true;
                }
                else
                {
                    if($useTransaction)
                        $this->rollBack();
                    throw new Exception("Falha ao remover catequizando do grupo de catequese.");
                }
            }
            else
            {
                if($useTransaction)
                    $this->rollBack();
                throw new Exception("Falha ao remover inscrição do catequizando na catequese.");
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Updates the payment status of a catechumen enrollment.
     * @param int $cid
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     * @param bool $paid
     */
    public function updateCatechumenEnrollmentPayment(int $cid, int $catecheticalYear, int $catechism, string $group,
                                                      bool $paid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $paid_int = $paid?1:0;

        try
        {
            $sql = "UPDATE inscreve SET pago=:pago WHERE cid=:cid AND ano_catecismo=:catecismo AND turma=:turma AND ano_lectivo=:ano_catequetico;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":ano_catequetico", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":pago", $paid_int, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the catechetical years where the catechumen is NOT enrolled.
     * This is useful to present a list of possible catechetical years to enroll the catechumen.
     * @param int $cid
     */
    public function getCatecheticalYearsWhereCatechumenIsNotEnrolled(int $cid)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT ano_lectivo FROM grupo WHERE ano_lectivo NOT IN (SELECT DISTINCT ano_lectivo FROM pertence WHERE cid=:cid) ORDER BY ano_lectivo DESC;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter anos catequéticos onde o catequizando não está inscrito.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the civil years in the database for which there are records of the sacrament type provided.
     * @param int $sacrament  - one of the core::domain::Sacraments class constants
     * @return mixed
     * @throws Exception
     */
    public function getSacramentsCivilYears(int $sacrament)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT YEAR(data) AS ano_civil FROM " . $this->translateSacramentIntoTable($sacrament) . " ORDER BY YEAR(data) DESC;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os anos civis.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
        catch (Exception $e)
        {
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the parishes in the database for which there are records of the sacrament type provided.
     * @param int $sacrament  - one of the core::domain::Sacraments class constants
     * @return mixed
     * @throws Exception
     */
    public function getDistinctParishes(int $sacrament)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT paroquia FROM " . $this->translateSacramentIntoTable($sacrament) . " ORDER BY paroquia;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os nomes das paróquias.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
        catch (Exception $e)
        {
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the distinct parishes in the database across all sacraments.
     * @return mixed
     * @throws Exception
     */
    public function getAllDistinctParishes()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DISTINCT paroquia FROM (SELECT DISTINCT paroquia FROM baptismo UNION SELECT DISTINCT paroquia FROM primeiraComunhao UNION SELECT DISTINCT paroquia FROM profissaoFe UNION SELECT DISTINCT paroquia FROM confirmacao) AS paroquias ORDER BY paroquia;";

            $stm = $this->_connection->prepare($sql);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter os nomes das paróquias.");
        }
        catch (Exception $e)
        {
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns all the catechumens that received the given sacrament, with optional filters.
     * Filter work as follows:
     *  - civiYear
     *      If provided, limit the search to sacraments received in that year.
     *  - parish
     *      If provided, limit the search to sacraments received in that parish.
     * - orderBy
     *      "Name" - order the list by catechumens name first
     *      "Date" - order the list by sacrament date first
     * @param int $sacrament  - one of the core::domain::Sacraments class constants
     * @param int|null $civilYear
     * @param string|null $parish
     * @param int $orderBy
     * @return mixed
     * @throws Exception
     */
    public function getCatechumensBySacrament(int $sacrament, int $civilYear = null, string $parish = null,
                                              int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        try
        {
            //Build query
            $sql = "SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, s.data AS data_sacramento, s.paroquia FROM catequizando c, " . $this->translateSacramentIntoTable($sacrament) . " s WHERE c.cid=s.cid";

            if($civilYear && $civilYear!="")
                $sql = $sql . " AND YEAR(s.data)=:ano_civil";

            if($parish && $parish!="")
                $sql = $sql . " AND s.paroquia=:paroquia";

            if($orderBy==OrderCatechumensBy::NAME_BIRTHDATE)
                $sql = $sql . " ORDER BY nome, data_nasc, data_sacramento;";
            else if($orderBy==OrderCatechumensBy::SACRAMENT_DATE)
                $sql = $sql . " ORDER BY data_sacramento DESC, nome, data_nasc;";
            else
                throw new Exception("Critério de ordenação inválido.");


            $stm = $this->_connection->prepare($sql);

            if($civilYear && $civilYear!="")
                $stm->bindParam(":ano_civil", $civilYear, PDO::PARAM_INT);
            if($parish && $parish!="")
                $stm->bindParam(":paroquia", $parish);


            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter listagem de baptismos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns a list of all the catechumens of a group plus a column 'registado' stating if they have
     * the sacrament (value 1) or not (value 0).
     * @param int $sacrament
     * @param int $catecheticalYear
     * @param int $catechism
     * @param string $group
     */
    public function getCatechumensWithAndWithoutSacramentByCatechismAndGroup(int $sacrament, int $catecheticalYear,
                                                                             int $catechism, string $group)
    {
        if (!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, p.passa, 0 AS 'registado' FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_prec AND p.ano_catecismo=:cat_prec AND p.turma=:turma_prec AND c.cid NOT IN ( SELECT cid FROM " . $this->translateSacramentIntoTable($sacrament) . " ) 
						UNION
						SELECT DISTINCT c.cid, c.nome, c.data_nasc, c.foto, p.passa, 1 AS 'registado' FROM catequizando c, pertence p WHERE c.cid=p.cid AND p.ano_lectivo=:ano_prec AND p.ano_catecismo=:cat_prec AND p.turma=:turma_prec AND c.cid IN ( SELECT cid FROM " . $this->translateSacramentIntoTable($sacrament) . "  ) 
						ORDER BY registado, nome;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_prec", $catecheticalYear, PDO::PARAM_INT);
            $stm->bindParam(":cat_prec", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma_prec", $group);

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter registos do sacramento.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the sacrament record for a catechumen given his/her cid.
     * @param int $sacrament
     * @param int $cid
     */
    public function getCatechumenSacramentRecord(int $sacrament, int $cid)
    {
        if (!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        try
        {
            //Build query
            $sql = "SELECT data, paroquia, comprovativo FROM ". $this->translateSacramentIntoTable($sacrament) ." WHERE cid=:cid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            if($stm->execute())
                return $stm->fetch();
            else
                throw new Exception("Falha ao obter registo de sacramento.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Inserts a new sacrament record.
     * Returns true if the operation succeeds, and false (or an Exception) in case something goes wrong.
     * @param int $cid
     * @param int $sacrament  - One of the constants in core\domain\Sacraments class
     * @param string $date
     * @param string $parish
     */
    public function insertSacramentRecord(int $cid, int $sacrament, string $date, string $parish)
    {
        if (!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "INSERT INTO " . $this->translateSacramentIntoTable($sacrament) .  "(cid, data, paroquia) VALUES (:cid, STR_TO_DATE(:data_sacramento, '%d-%m-%Y'), :paroquia);";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":data_sacramento", $date);
            $stm->bindParam(":paroquia", $parish);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the date and parish of a sacrament record.
     * @param int $cid
     * @param int $sacrament
     * @param string $date
     * @param string $parish
     */
    public function updateSacramentRecord(int $cid, int $sacrament, string $date, string $parish)
    {
        if (!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "UPDATE " . $this->translateSacramentIntoTable($sacrament) .  " SET data=STR_TO_DATE(:nova_data, '%d-%m-%Y'), paroquia=:nova_paroquia WHERE cid=:cid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            $stm->bindParam(":nova_data", $date);
            $stm->bindParam(":nova_paroquia", $parish);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes a sacrament record for a catechumen.
     * @param int $cid
     * @param int $sacrament  - One of the constants in core\domain\Sacraments class
     */
    public function deleteSacramentRecord(int $cid, int $sacrament)
    {
        if (!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_DELETE))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Build query
            $sql = "DELETE FROM " . $this->translateSacramentIntoTable($sacrament) .  " WHERE cid=:cid;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Stores the path for a sacrament proof document.
     * @param int $cid
     * @param int $sacrament
     * @param string|null $proof
     */
    public function setSacramentProofDocument(int $cid, int $sacrament, string $proof=null)
    {
        if (!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE " . $this->translateSacramentIntoTable($sacrament) . " SET comprovativo=:comprovativo WHERE cid=:cid;";

            $stm = $this->_connection->prepare($sql);

            $mynull = null;
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);
            if(isset($proof))
                $stm->bindParam(":comprovativo", $proof);
            else
                $stm->bindParam(":comprovativo", $mynull, PDO::PARAM_NULL);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }

    /**
     * Extracts the necessary data to the decision support system to analyse baptisms.
     * If the authenticated user is an admin, returns all the data. Otherwise, returns only data pertaining to his/her
     * catechumens.
     * @param int $catecheticalYear - catechetical year to analyse
     * @param bool $admin            - whether the authenticated user is admin or not
     * @param string $username         - the username of the authenticated user
     * @return mixed
     * @throws Exception
     */
    public function getBaptismAnalysis(int $catecheticalYear, bool $admin, string $username)
    {
        $sql = "";
        if ($admin)
        {
            //Get all baptism data from the database

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.foto, t1.obs, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
				 t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
				 t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao
				 FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, foto, obs, autorizou_fotos, autorizou_saida_sozinho, ano_catecismo, turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, familiar f WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual) AS t1
				 LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
				 LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
				 LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid) ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }
        else
        {
            //Get baptism data only for the catechumens of this catechist

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.foto, t1.obs, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
				 t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
				 t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao
				 FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, foto, obs, autorizou_fotos, autorizou_saida_sozinho, p.ano_catecismo, p.turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, lecciona l, familiar f 
				 	WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual AND p.ano_catecismo=l.ano_catecismo AND p.ano_lectivo=l.ano_lectivo AND p.turma=l.turma AND l.username=:username) AS t1
				 LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
				 LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
				 LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid) ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }

        return $this->genericDecisionSupportAnalysisQuery($sql, $catecheticalYear, $admin, $username);
    }




    /**
     * Extracts the necessary data to the decision support system to analyse First Communions.
     * If the authenticated user is an admin, returns all the data. Otherwise, returns only data pertaining to his/her
     * catechumens.
     * @param int $catecheticalYear - catechetical year to analyse
     * @param bool $admin            - whether the authenticated user is admin or not
     * @param string $username         - the username of the authenticated user
     * @return mixed
     * @throws Exception
     */
    public function getFirstCommunionAnalysis(int $catecheticalYear, bool $admin, string $username)
    {
        $sql = "";
        if ($admin)
        {
            //Get all First Communion data from the database

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.foto, t1.obs, t1.autorizou_fotos, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
				 t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
				 t4.data AS data_comunhao, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao
				 FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, foto, obs, autorizou_fotos, ano_catecismo, turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, familiar f WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual) AS t1
				 LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
				 LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
				 LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid) ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }
        else
        {
            //Get First Communion data only for the catechumens of this catechist

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.foto, t1.obs, t1.autorizou_fotos, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
				 t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
				 t4.data AS data_comunhao, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao
				 FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, foto, obs, autorizou_fotos, p.ano_catecismo, p.turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, lecciona l, familiar f 
				 	WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual AND p.ano_catecismo=l.ano_catecismo AND p.ano_lectivo=l.ano_lectivo AND p.turma=l.turma AND l.username=:username) AS t1
				 LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
				 LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
				 LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid) ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }

        return $this->genericDecisionSupportAnalysisQuery($sql, $catecheticalYear, $admin, $username);
    }




    /**
     * Extracts the necessary data to the decision support system to analyse Chrismations.
     * If the authenticated user is an admin, returns all the data. Otherwise, returns only data pertaining to his/her
     * catechumens.
     * @param int $catecheticalYear - catechetical year to analyse
     * @param bool $admin            - whether the authenticated user is admin or not
     * @param string $username         - the username of the authenticated user
     * @return mixed
     * @throws Exception
     */
    public function getChrismationAnalysis(int $catecheticalYear, bool $admin, string $username)
    {
        $sql = "";
        if ($admin)
        {
            //Get all Chrismation data from the database

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.foto, t1.obs, t1.autorizou_fotos, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
				 t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
				 t4.data AS data_comunhao, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao,
				 t5.data AS data_crisma, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma
				 FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, foto, obs, autorizou_fotos, ano_catecismo, turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, familiar f WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual) AS t1
				 LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
				 LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
				 LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid)
				 LEFT OUTER JOIN confirmacao t5 ON (t1.cid=t5.cid)
				  ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }
        else
        {
            //Get Chrismation data only for the catechumens of this catechist

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.foto, t1.obs, t1.autorizou_fotos, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
    			 t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
    			 t4.data AS data_comunhao, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao,
    			 t5.data AS data_crisma, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma
    			 FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, foto, obs, autorizou_fotos, p.ano_catecismo, p.turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, lecciona l, familiar f 
    			 	WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual AND p.ano_catecismo=l.ano_catecismo AND p.ano_lectivo=l.ano_lectivo AND p.turma=l.turma AND l.username=:username) AS t1
    			 LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
    			 LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
    			 LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid)
    			 LEFT OUTER JOIN confirmacao t5 ON (t1.cid=t5.cid)
    			  ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }

        return $this->genericDecisionSupportAnalysisQuery($sql, $catecheticalYear, $admin, $username);
    }




    /**
     * Generic method to extract data for decision support analysis.
     * @param $sql              - the SQL query to execute
     * @param $catecheticalYear - catechetical year to analyse
     * @param $admin            - whether the authenticated user is admin or not
     * @param $username         - the username of the authenticated user
     * @return mixed
     * @throws Exception
     */
    private function genericDecisionSupportAnalysisQuery(string $sql, int $catecheticalYear, bool $admin, string $username = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_catequetico_actual", $catecheticalYear, PDO::PARAM_INT);
            if (!$admin)
                $stm->bindParam(":username", $username, PDO::PARAM_STR);

            if ($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao efectuar pesquisa. Parâmetros inválidos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Dumps all the necessary data to inconsistency analysis.
     * If the user is NOT an administrator, he/she can only access data pertaining to his/her catechumens.
     * @param string $username
     * @param bool $admin
     * @param int $currentCatecheticalYear      - the catechetical year to fetch catechists<->groups relatioships
     * @param int|null $catecheticalYear             - the catechetical year to which the data analysis reports
     * @param int|null $catechism
     * @param string|null $group
     * @throws Exception
     */
    public function getDataDumpForInconsistencyAnalysis(string $username, bool $admin, int $currentCatecheticalYear, int $catecheticalYear = null, int $catechism = null, string $group = null)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        //Build query
        $sql = "";
        if(isset($admin) && ($admin || $admin==1))
        {
            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.nif, t1.foto, t1.obs, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
                     t3.ano_catecismo, t3.turma, 
                     t4.data AS data_baptismo, t4.paroquia AS paroquia_batismo, t4.comprovativo AS comprovativo_batismo,
                     t5.data AS data_comunhao, t5.paroquia AS paroquia_comunhao, t5.comprovativo AS comprovativo_comunhao,
                     t6.data AS data_crisma,   t6.paroquia AS paroquia_crisma, t6.comprovativo AS comprovativo_crisma
                     FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, nif, foto, obs, autorizou_fotos, autorizou_saida_sozinho, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, familiar f";

            //Filters
            if((isset($catecheticalYear) && $catecheticalYear > 1000000)
                || (isset($catechism) && $catechism > 0 && $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                || (isset($group) && $group!=""))
                $sql .= ", pertence p";


            $sql .= " WHERE c.enc_edu=f.fid";


            //Filters
            if((isset($catecheticalYear) && $catecheticalYear > 1000000)
                || (isset($catechism) && $catechism > 0 && $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                || (isset($group) && $group!=""))
                $sql .= " AND c.cid=p.cid";

            if(isset($catecheticalYear) && $catecheticalYear > 1000000)
                $sql .= " AND p.ano_lectivo=:ano_catequetico";

            if(isset($catechism) && $catechism > 0 && $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                $sql .= " AND p.ano_catecismo=:catecismo";

            if(isset($group) && $group!="")
                $sql .= " AND p.turma=:turma";


            $sql .= ") AS t1
                     LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid) 
                     LEFT OUTER JOIN (SELECT p.cid, p.ano_catecismo, p.turma FROM pertence p WHERE p.ano_lectivo=:ano_catequetico_actual) AS t3 ON (t1.cid=t3.cid)
                     LEFT OUTER JOIN baptismo AS t4 ON (t1.cid=t4.cid) 
                     LEFT OUTER JOIN primeiraComunhao AS t5 ON (t1.cid=t5.cid)
                     LEFT OUTER JOIN confirmacao AS t6 ON (t1.cid=t6.cid)
                          ORDER BY t3.ano_catecismo, t3.turma, t1.nome;";
        }
        else
        {

            $sql = "SELECT t1.cid, t1.nome, t1.data_nasc, t1.nif, t1.foto, t1.obs, t1.autorizou_fotos, t1.autorizou_saida_sozinho, t1.ano_catecismo, t1.turma, t1.pai, t1.mae, t1.enc_edu, t1.enc_edu_quem, t1.email, t1.telefone, t1.telemovel, t2.inscricoes,
                     t3.data AS data_baptismo, t3.paroquia AS paroquia_batismo, t3.comprovativo AS comprovativo_batismo,
                     t4.data AS data_comunhao, t4.paroquia AS paroquia_comunhao, t4.comprovativo AS comprovativo_comunhao,
                     t5.data AS data_crisma, t5.paroquia AS paroquia_crisma, t5.comprovativo AS comprovativo_crisma
                     FROM (SELECT DISTINCT c.cid, c.nome, data_nasc, nif, foto, obs, autorizou_fotos, autorizou_saida_sozinho, p.ano_catecismo, p.turma, pai, mae, enc_edu, enc_edu_quem, email, telemovel, telefone FROM catequizando c, pertence p, lecciona l, familiar f 
                        WHERE c.enc_edu=f.fid AND c.cid=p.cid AND p.ano_lectivo=:ano_catequetico_actual AND p.ano_catecismo=l.ano_catecismo AND p.ano_lectivo=l.ano_lectivo AND p.turma=l.turma AND l.username=:username) AS t1
                     LEFT OUTER JOIN (SELECT p.cid, COUNT(DISTINCT p.ano_catecismo) AS inscricoes FROM pertence p GROUP BY p.cid) AS t2 ON (t1.cid=t2.cid)
                     LEFT OUTER JOIN baptismo t3 ON (t1.cid=t3.cid) 
                     LEFT OUTER JOIN primeiraComunhao t4 ON (t1.cid=t4.cid)
                     LEFT OUTER JOIN confirmacao t5 ON (t1.cid=t5.cid)
                      ORDER BY t1.ano_catecismo, t1.turma, t1.nome;";
        }

        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_catequetico_actual", $currentCatecheticalYear, PDO::PARAM_INT);
            if(!$admin || $admin!=1)
                $stm->bindParam(":username", $username, PDO::PARAM_STR);
            else
            {
                if(isset($catecheticalYear) && $catecheticalYear > 1000000)
                    $stm->bindParam(":ano_catequetico", $catecheticalYear, PDO::PARAM_INT);
                if(isset($catechism) && $catechism > 0 && $catechism <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
                    $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
                if(isset($group) && $group!="")
                    $stm->bindParam(":turma", $group);
            }

            if($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao efectuar pesquisa. Parâmetros inválidos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Checks and returns true if there is sufficient data to compute the percentage of resident catechumens statistic.
     * @return bool
     * @throws Exception
     */
    public function isDataSufficientForResidentsStatistic()
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $num = 0;
        try
        {
            $sql = "SELECT MIN(num) AS 'cont' FROM (SELECT COUNT(DISTINCT cid) AS 'num' FROM catequizando UNION SELECT COUNT(DISTINCT codigo) FROM cod_postais_paroquia) AS tb;";
            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
            {
                $result = $stm;
                foreach($result as $row)
                {
                    $num = $row['cont'];
                }
            }
            else
            {
                throw new Exception("Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $num > 0;
    }


    /**
     * Checks and returns true if there is sufficient data to compute the percentage of abadonment statistic.
     * @param int $currentCatecheticalYear
     * @return bool
     * @throws Exception
     */
    public function isDataSufficientForAbadonmentStatistic(int $currentCatecheticalYear)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $num = 0;
        try
        {
            $sql = "SELECT DISTINCT p.ano_lectivo FROM pertence p WHERE p.ano_lectivo<=:ano_actual;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_actual", $currentCatecheticalYear, PDO::PARAM_INT);

            if ($stm->execute())
            {
                $num = $stm->rowCount();
            }
            else
            {
                throw new Exception("Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $num >= 2;
    }


    /**
     * Checks and returns true if there is sufficient data to compute the complete catechetical journeys statistic.
     * @param int $currentCatecheticalYear
     * @return bool
     * @throws Exception
     */
    public function isDataSufficientForCompleteCatecheticalJourneyStatistic(int $currentCatecheticalYear)
    {
        //In this implementation, the requirements for these statistics are the same
        return $this->isDataSufficientForAbadonmentStatistic($currentCatecheticalYear);
    }


    /**
     * Checks and returns true if there is sufficient data to compute the number of catechumens by catechist statistic.
     * @return bool
     * @throws Exception
     */
    public function isDataSufficientForNumberOfCatechumensByCatechist()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        $num = 0;
        try
        {
            $sql = "SELECT cid, username FROM pertence p, lecciona l WHERE p.ano_catecismo=l.ano_catecismo AND p.turma=l.turma AND p.ano_lectivo=l.ano_lectivo;";
            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
            {
                $num = $stm->rowCount();
            }
            else
            {
                throw new Exception("Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $num >= 1;
    }



    /**
     * Computes the percentage of catechumens living in a postal code belonging to the parish area.
     * @return int
     * @throws Exception
     */
    public function getResidentCatechumensPercentage()
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $residentsPercentage = 0;
        try
        {
            $sql = "SELECT COUNT(DISTINCT cid)/(SELECT COUNT(DISTINCT cid) FROM catequizando)*100 AS 'residentes' FROM catequizando c, familiar f WHERE c.enc_edu=f.fid AND SUBSTRING(f.cod_postal,1,8) IN (SELECT codigo FROM cod_postais_paroquia);";
            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
            {
                $result = $stm;
                foreach($result as $row)
                {
                    $residentsPercentage = $row['residentes'];
                }
            }
            else
            {
                throw new Exception("Não foi possível obter a percentagem de catequizandos residentes na paróquia.");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $residentsPercentage;
    }


    /**
     * Returns a dictionary with the first year of catechetical data (key 'first') and the last year of catechetical
     * data (key 'last') in the database.
     * @return array
     * @throws Exception
     */
    public function getCatecheticalYearsRangeForCatechumens()
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $catecheticalYearsRange=[];
        try
        {
            $sql = "SELECT MIN(p.ano_lectivo) AS min, MAX(p.ano_lectivo) AS max FROM pertence p;";
            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
            {
                $result = $stm;

                if(($result->rowCount())!=1)
                    throw new Exception("Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.");

                foreach($result as $row)
                {
                    $catecheticalYearsRange['first'] = $row['min'];
                    $catecheticalYearsRange['last'] = $row['max'];
                }
            }
            else
            {
                throw new Exception("Não foi possível obter os anos catequéticos.");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $catecheticalYearsRange;
    }


    public function getCatecheticalYearsRangeForCatechumensAndCatechists()
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        $catecheticalYearsRange=[];
        try
        {
            $sql = "SELECT MIN(l.ano_lectivo) AS min, MAX(l.ano_lectivo) AS max FROM pertence p, lecciona l WHERE p.ano_catecismo=l.ano_catecismo AND p.turma=l.turma AND p.ano_lectivo=l.ano_lectivo;";
            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
            {
                $result = $stm;

                if(($result->rowCount())!=1)
                    throw new Exception("Ainda não existem dados suficientes na base de dados para gerar estas estatísticas. Por favor volte a tentar mais tarde.");

                foreach($result as $row)
                {
                    $catecheticalYearsRange['first'] = $row['min'];
                    $catecheticalYearsRange['last'] = $row['max'];
                }
            }
            else
            {
                throw new Exception("Não foi possível obter os anos catequéticos.");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $catecheticalYearsRange;
    }

    /**
     * Computes and returns the absolute frequency ($inPercentage=false) or relative frequency ($inPercentage=true) of
     * catechumens abandoning catechesis by catechetical year.
     * @param int $currentCatecheticalYear
     * @param bool $inPercentage
     * @return mixed
     * @throws Exception
     */
    public function getAbandonmentByCatecheticalYear(int $currentCatecheticalYear, bool $inPercentage)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "";
            if($inPercentage)
                $sql = "SELECT p.ano_lectivo, COUNT(p.cid)/(SELECT COUNT(cid) FROM pertence p1 WHERE p1.ano_lectivo=p.ano_lectivo)*100 AS desistencias FROM pertence p WHERE p.ano_lectivo<:ano_actual AND p.ano_catecismo<10 AND p.cid NOT IN (SELECT cid FROM pertence p2 WHERE p2.ano_lectivo=(p.ano_lectivo + 10001)) GROUP BY ano_lectivo;";
            else
                $sql = "SELECT p.ano_lectivo, COUNT(p.cid) AS desistencias FROM pertence p WHERE p.ano_lectivo<:ano_actual AND p.ano_catecismo<10 AND p.cid NOT IN  (SELECT cid FROM pertence p2 WHERE p2.ano_lectivo=(p.ano_lectivo + 10001)) GROUP BY ano_lectivo;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_actual", $currentCatecheticalYear, PDO::PARAM_INT);

            if ($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Não foi possível obter a informação sobre desistências.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Computes and returns the absolute frequency ($inPercentage=false) or relative frequency ($inPercentage=true) of
     * catechumens completing the catechetical journey (all catechisms + Chrismation) by catechetical year.
     * The number of catechisms is obtained from the configuration.
     * @param int $currentCatecheticalYear
     * @param bool $inPercentage
     * @return mixed
     * @throws Exception
     */
    public function getCompleteCatecheticalJourneysByCatecheticalYear(int $currentCatecheticalYear, bool $inPercentage)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        // Get the number of catechisms from configuration
        $numCatechisms = intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS));

        try
        {
            $sql = "";
            if($inPercentage)
            {
                // Build the dynamic part of the SQL query for the denominator
                $denominatorConditions = [];
                for($i = 1; $i < $numCatechisms; $i++)
                {
                    $offset = ($numCatechisms - $i) * 10001;
                    $denominatorConditions[] = "(p1.ano_catecismo={$i} AND p1.ano_lectivo=(p.ano_lectivo-{$offset}))";
                }
                $denominatorConditions[] = "(p1.ano_catecismo={$numCatechisms} AND p1.ano_lectivo=(p.ano_lectivo))";
                $denominatorSql = implode(" OR ", $denominatorConditions);

                $sql = "SELECT p.ano_lectivo, COUNT(p.cid)/(select COUNT(p1.cid) from pertence p1 where {$denominatorSql})*100 AS 'percentagem' FROM pertence p WHERE p.cid NOT IN (SELECT DISTINCT p.cid AS desistencias FROM pertence p where p.ano_lectivo<:ano_actual AND p.ano_catecismo<{$numCatechisms} AND p.cid NOT IN (SELECT cid FROM pertence p2 WHERE p2.ano_lectivo=(p.ano_lectivo + 10001))) AND p.ano_catecismo={$numCatechisms} AND p.cid IN (SELECT cid FROM confirmacao c WHERE YEAR(c.data)=(p.ano_lectivo%10000)) GROUP BY p.ano_lectivo;";
            }
            else
            {
                $sql = "SELECT p.ano_lectivo, COUNT(p.cid) as 'completos' FROM pertence p WHERE p.cid NOT IN (SELECT DISTINCT p.cid AS desistencias FROM pertence p WHERE p.ano_lectivo<:ano_actual AND p.ano_catecismo<{$numCatechisms} AND p.cid NOT IN (SELECT cid FROM pertence p2 WHERE p2.ano_lectivo=(p.ano_lectivo + 10001))) AND p.ano_catecismo={$numCatechisms} AND p.cid IN (SELECT cid FROM confirmacao c WHERE YEAR(c.data)=(p.ano_lectivo%10000)) GROUP BY p.ano_lectivo;";
            }
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":ano_actual", $currentCatecheticalYear, PDO::PARAM_INT);

            if ($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Não foi possível obter a informação sobre percursos catequéticos completos.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns the number of catechumens by catechist and catechetical year.
     * If $accumulated=true gives the accumulated count of distinct and possible duplicate catechumens as 'distintos' and 'nao distintos' keys respectively.
     * Otherwise, just gives the number of catechumens year by year.
     * @return mixed
     * @throws Exception
     */
    public function getCatechumensByCatechistAndYear(bool $accumulated)
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "";
            if($accumulated)
                $sql = "SELECT l.username, u.nome, COUNT(c.cid) AS 'nao distintos', COUNT(DISTINCT c.cid) AS distintos FROM catequizando c, pertence p, lecciona l, utilizador u, catequista ct WHERE c.cid=p.cid AND p.ano_catecismo=l.ano_catecismo AND p.turma=l.turma AND p.ano_lectivo=l.ano_lectivo AND l.username=u.username AND u.username=ct.username AND ct.estado=1 GROUP BY l.username, u.nome ORDER BY u.nome;";
            else
                $sql = "SELECT l.username, u.nome, l.ano_lectivo, COUNT(DISTINCT c.cid) AS contagem FROM catequizando c, pertence p, lecciona l, utilizador u, catequista ct WHERE c.cid=p.cid AND p.ano_catecismo=l.ano_catecismo AND p.turma=l.turma AND p.ano_lectivo=l.ano_lectivo AND l.username=u.username AND u.username=ct.username AND ct.estado=1 GROUP BY l.username, u.nome, l.ano_lectivo ORDER BY u.nome, l.ano_lectivo;";
            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Não foi possível obter a informação sobre catequizandos por catequista.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns an array with all the calendar dates for which a virtual catechesis session exists in the database,
     * for the particular catechism/group provided.
     * If 'recursive' is true, searches for catechesis sessions recursively in the contents pyramid, starting from a session for this specific group,
     * up to a generic session for all groups or all catechisms. Otherwise, only returns perfect matches for this specific group.
     * If 'limit' is greater than 0, limit the results only to the 'limit' most recent sessions.
     * If 'afterDate' is not null, returns only sessions after the specified date or equal to it.
     * If 'beforeDate' is not null, returns only sessions before the specified date or equal to it.
     * @param int|null $catechism
     * @param string|null $group
     * @param bool $recursive
     * @param int $limit
     * @param string|null $afterDate
     * @param string|null $beforeDate
     * @return array
     * @throws Exception
     */
    public function getVirtualCatechesisSessionDates(int $catechism = null, string $group = null, bool $recursive = true,
                                                     int $limit = 0, string $afterDate = null, string $beforeDate = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        if(!isset($catechism))
            $catechism = -1;
        if (!isset($group))
            $group = '';        //Default value when no group is selected


        // Build the query
        $sql = "SELECT DISTINCT data FROM catequese_virtual WHERE";
        if($recursive)
            $sql = $sql . " ((ano_catecismo=:catecismo AND turma=:turma) OR (ano_catecismo=:catecismo AND turma='') OR (ano_catecismo=-1 AND turma=''))";
        else
            $sql = $sql . " ano_catecismo=:catecismo AND turma=:turma";

        if(isset($afterDate))
            $sql = $sql . " AND data >= STR_TO_DATE(:after, '%d-%m-%Y')";
        if(isset($beforeDate))
            $sql = $sql . " AND data <= STR_TO_DATE(:before, '%d-%m-%Y')";

        $sql = $sql . " ORDER BY data DESC";

        if($limit > 0)
            $sql = $sql . " LIMIT " . intval($limit);
        $sql = $sql . ";";

        $dates = array();
        try
        {
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group, PDO::PARAM_STR);
            if(isset($afterDate))
                $stm->bindParam(":after", $afterDate, PDO::PARAM_STR);
            if(isset($beforeDate))
                $stm->bindParam(":before", $beforeDate, PDO::PARAM_STR);

            if ($stm->execute())
            {
                $result = $stm->fetchAll();
                if(isset($result))
                {
                    foreach ($result as $row)
                    {
                        $phpdate = strtotime($row['data']);                 // Convert MySQL date to PHP date
                        $frontendDate = date('d-m-Y', $phpdate);     // Format used in the frontend
                        array_push($dates, $frontendDate);
                    }
                }
                else
                {
                    throw new Exception("Falha ao carregar datas das sessões. [1]");
                }
            }
            else
            {
                throw new Exception("Falha ao carregar o datas das sessões. [2]");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $dates;
    }



    /**
     * Returns the contents of a virtual catechesis session.
     * If 'recursive' is true, searches for a catechesis session recursively in the contents pyramid, starting from a session for this specific group,
     * up to a generic session for all groups or all catechisms. Otherwise, only returns a perfect match for this specific group.
     * @param $sessionDate
     * @param $catechism
     * @return string|string[]|null
     * @throws Exception
     */
    public function getVirtualCatechesisContent(string $sessionDate, int $catechism = null, string $group = null, bool $recursive = true)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        if(!isset($catechism))
            $catechism = -1;
        if (!isset($group))
            $group = '';        //Default value when no group is selected

        $contents = null;
        try
        {
            $sql = "";
            if($recursive)
                $sql = "SELECT data, ano_catecismo, turma, conteudo, ultima_modificacao_user, ultima_modificacao_timestamp FROM catequese_virtual WHERE data=STR_TO_DATE(:data, '%d-%m-%Y') AND ((ano_catecismo=:catecismo AND turma=:turma) OR (ano_catecismo=:catecismo AND turma='') OR (ano_catecismo=-1 AND turma=''));";
            else
                $sql = "SELECT data, ano_catecismo, turma, conteudo, ultima_modificacao_user, ultima_modificacao_timestamp FROM catequese_virtual WHERE data=STR_TO_DATE(:data, '%d-%m-%Y') AND ano_catecismo=:catecismo AND turma=:turma;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":data", $sessionDate, PDO::PARAM_STR);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group, PDO::PARAM_STR);

            if ($stm->execute())
            {
                $result = $stm->fetchAll();
                if (isset($result))
                {
                    foreach ($result as $row)
                    {
                        $contents = Utils::escapeSingleQuotes(Utils::doubleEscapeDoubleQuotes(Utils::doubleEscapeWhiteSpaces(htmlspecialchars($row["conteudo"], ENT_NOQUOTES)))); //htmlspecialchars_decode( $row["conteudo"] ); //Reverter a codificacao de caracteres especiais
                    }
                }
                else
                {
                    //print_r($stm->errorInfo());
                    throw new Exception("Falha ao carregar o conteudo da sessão. [1]");
                }
            }
            else
            {
                //print_r($stm->errorInfo());
                throw new Exception("Falha ao carregar o conteudo da sessão. [2]");
            }

        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        return $contents;
    }


    /**
     * Saves or updates the contents of a virtual catechesis session.
     * @param $sessionDate
     * @param $catechism
     * @param $content      - contents of the virtual catechesis session
     * @param $username     - user submitting the changes
     * @throws Exception
     */
    public function postVirtualCatechesisContent(string $contents, string $username, string $sessionDate,
                                                 int $catechism = null, string $group = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        if(!isset($catechism))
            $catechism = -1;
        if (!isset($group))
            $group = '';        //Default value when no group is selected

        try
        {
            $sql = "INSERT INTO catequese_virtual(data, ano_catecismo, turma, conteudo, ultima_modificacao_user, ultima_modificacao_timestamp) VALUES(STR_TO_DATE(:data, '%d-%m-%Y'), :catecismo, :turma, :conteudo, :user, NOW()) ON DUPLICATE KEY UPDATE conteudo=:conteudo, ultima_modificacao_user=:user, ultima_modificacao_timestamp=NOW();";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":data", $sessionDate);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":conteudo", $contents);
            $stm->bindParam(":turma", $group);
            $stm->bindParam(":user", $username);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Registers thah a user has a virtual catechesis open for editing
     * @param $sessionDate
     * @param $catechism
     * @param $username
     * @return bool
     * @throws Exception
     */
    public function insertLockInVirtualCatechesis(string $username, string $sessionDate,
                                                  int $catechism = null, string $group = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        if(!isset($catechism))
            $catechism = -1;
        if (!isset($group))
            $group = '';        //Default value when no group is selected

        try
        {
            $sql = "INSERT INTO catequese_virtual_lock(data, ano_catecismo, turma, lock_user, lock_timestamp) VALUES(STR_TO_DATE(:data, '%d-%m-%Y'), :catecismo, :turma, :user, NOW()) ON DUPLICATE KEY UPDATE lock_timestamp=NOW();";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":data", $sessionDate);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group, PDO::PARAM_STR);
            $stm->bindParam(":user", $username);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns the list of users currently editing a virtual catechesis.
     * A user is considered as editing a page if he/she has accessed it in the '$timeThreshold' last seconds.
     * If an optional $excludeUsername is provided, that username is excluded from the result list.
     * @param $sessionDate
     * @param $catechism
     * @param $timeThreshold
     */
    public function getListOfVirtualCatechesisObservers(string $sessionDate, int $timeThreshold,
                                                        int $catechism = null, string $group = null, string $excludeUsername = null)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');


        if(!isset($catechism))
            $catechism = -1;
        if (!isset($group))
            $group = '';        //Default value when no group is selected

        try
        {
            $sql = "SELECT DISTINCT l.lock_user, u.nome FROM catequese_virtual_lock l, utilizador u WHERE l.lock_user = u.username AND data=STR_TO_DATE(:data, '%d-%m-%Y') AND ano_catecismo=:catecismo AND turma=:turma AND lock_timestamp > (NOW() - INTERVAL :threshold SECOND) ORDER BY u.nome;";
            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":data", $sessionDate);
            $stm->bindParam(":catecismo", $catechism, PDO::PARAM_INT);
            $stm->bindParam(":turma", $group, PDO::PARAM_STR);
            $stm->bindParam(":threshold", $timeThreshold, PDO::PARAM_INT);

            if ($stm->execute())
            {
                $result = $stm->fetchAll();
                if (isset($result))
                {
                    $active_users_names = array();
                    $active_users_logins = array();
                    foreach ($result as $row)
                    {
                        if (!isset($excludeUsername) || $row['lock_user'] != $excludeUsername)  //Exclude self from the result list
                        {
                            $active_users_logins[] = $row['lock_user'];
                            $active_users_names[] = $row['nome'];
                        }
                    }

                    return array("active_users_logins" => $active_users_logins, "active_users_names" => $active_users_names);
                }
            }
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }

        //Empty array
        return array("active_users_logins" => array(), "active_users_names" => array());
    }



    /**
     * Returns the configuration value associated with the given key.
     * @param string $key
     */
    public function getConfigValue(string $key)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::CONFIGURATION))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT valor FROM configuracoes WHERE chave=:chave;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":chave", $key);

            if ($stm->execute())
            {
                $result = $stm->fetch();
                if(isset($result))
                    return $result['valor'];
                else
                    return null;
            }
            else
                throw new Exception("Falha ao obter valor de configuração.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Stores or updates a configuration value in the key-value store.
     * @param string $key
     * @param string $value
     */
    public function setConfigValue(string $key, string $value)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::CONFIGURATION))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            //Try to insert a new configuration
            $sql = "INSERT INTO configuracoes(chave, valor) VALUES (:chave, :valor) ON DUPLICATE KEY UPDATE valor=:valor;";

            $stm = $this->_connection->prepare($sql);

            $stm->bindParam(":chave", $key);
            $stm->bindParam(":valor", $value);

            if ($stm->execute())
                return true;
            else
                throw new Exception("Falha ao inserir valor de configuração.");
        }
        catch (PDOException $e)
        {
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the whole main Catechesis log contents.
     * @return mixed
     * @throws Exception
     */
    public function getCatechesisLog()
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::LOG_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT LSN, DATE(data_hora) AS data, DATE_FORMAT(data_hora, '%H:%i:%s') AS hora, username, accao FROM registosLog ORDER BY data_hora DESC, LSN DESC;";

            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
                return  $stm->fetchAll();
            else
                throw new Exception("Falha ao obter registos de actividade do CatecheSis.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the whole authentications log.
     * @return mixed
     * @throws Exception
     */
    public function getAuthenticationsLog()
    {

        if(!$this->connectAsNeeded(DatabaseAccessMode::LOG_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT DATE(timestamp) AS data, DATE_FORMAT(timestamp, '%H:%i:%s') AS hora, action, comment, user, ip FROM ul_log ORDER BY timestamp DESC;";

            $stm = $this->_connection->prepare($sql);

            if ($stm->execute())
                return $stm->fetchAll();
            else
                throw new Exception("Falha ao obter registos de eventos de autenticação do CatecheSis.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }



    /**
     * Returns a particular log entry, given its sequence number.
     * @param int $lsn
     */
    public function getLogEntry(int $lsn)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_READ))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT LSN, DATE(data_hora) AS data, DATE_FORMAT(data_hora, '%H:%i:%s') AS hora, l.username, u.nome AS nome_modificacao, accao FROM registosLog l, utilizador u WHERE l.username=u.username AND LSN=:lsn ORDER BY data_hora DESC;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":lsn", $lsn, PDO::PARAM_INT);

            if ($stm->execute())
                return $stm->fetch();
            else
                throw new Exception("Falha ao obter registo de actividade do CatecheSis.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Inserts a new CatecheSis log entry.
     * Returns the Log Sequence Number (LSN) of the inserted log entry.
     * @param string $username
     * @param string $action
     */
    public function addLogEntry(string $username, string $action)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::LOG_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "INSERT INTO registosLog(data_hora, username, accao) VALUES (NOW(), :username, :accao);";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":username", $username);
            $stm->bindParam(":accao", $action);

            if ($stm->execute())
                return $this->_connection->lastInsertId();
            else
                throw new Exception("Falha ao inserir registo de actividade do CatecheSis.");
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the log sequence number for the last modification of a catechumen's file.
     * @param int $cid
     * @param int $lsn
     */
    public function updateCatechumenFileLog(int $cid, int $lsn)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE catequizando SET lastLSN_ficha=:lsn WHERE cid = :cid;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":lsn", $lsn, PDO::PARAM_INT);
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the log sequence number for the last modification of a catechumen's archive.
     * @param int $cid
     * @param int $lsn
     * @return mixed
     * @throws Exception
     */
    public function updateCatechumenArchiveLog(int $cid, int $lsn)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE catequizando SET lastLSN_arquivo=:lsn WHERE cid = :cid;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":lsn", $lsn, PDO::PARAM_INT);
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Updates the log sequence number for the last modification of a catechumen's authorizations.
     * @param int $cid
     * @param int $lsn
     */
    public function updateCatechumenAuthorizationsLog(int $cid, int $lsn)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::DEFAULT_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "UPDATE catequizando SET lastLSN_autorizacoes=:lsn WHERE cid = :cid;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":lsn", $lsn, PDO::PARAM_INT);
            $stm->bindParam(":cid", $cid, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Returns the oldest LSN to keep when doing a cleanup, maintaining the most recent $maxRecords records.
     * @param int $maxRecords
     */
    public function getOldestLSNtoKeep(int $maxRecords)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::LOG_EDIT))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "SELECT MIN(LSN) AS oldest_lsn FROM (SELECT LSN FROM registosLog ORDER BY LSN DESC LIMIT :limit) AS latest_records;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":limit", $maxRecords, PDO::PARAM_INT);

            if($stm->execute())
            {
                $result = $stm->fetch();
                if(isset($result))
                    return $result['oldest_lsn'];
                else
                    return null;
            }
            return null;
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }


    /**
     * Deletes CatecheSis log entries older than the provided LSN.
     * @param int $lsn
     */
    public function deleteLogEntriesOlderThan(int $lsn)
    {
        if(!$this->connectAsNeeded(DatabaseAccessMode::LOG_CLEAN))
            throw new Exception('Não foi possível estabelecer uma ligação à base de dados.');

        try
        {
            $sql = "DELETE FROM registosLog WHERE LSN < :oldest_lsn;";

            $stm = $this->_connection->prepare($sql);
            $stm->bindParam(":oldest_lsn", $lsn, PDO::PARAM_INT);

            return $stm->execute();
        }
        catch (PDOException $e)
        {
            //echo $e->getMessage();
            throw new Exception("Falha interna ao tentar aceder à base de dados.");
        }
    }








    /**
     * =================================================================================================================
     * =
     * =                                             PRIVATE AUXILIARY METHODS
     * =
     * =================================================================================================================
     */


    /**
     * Auxiliary method to translate sacrament names into the corresponding table names.
     * @param $sacrament
     * @return string
     * @throws Exception
     */
    private function translateSacramentIntoTable(int $sacrament)    // Translates a core\domain\Sacrament:: constant into the corresponding database table name
    {
        switch($sacrament)
        {
            case Sacraments::BAPTISM:
                return "baptismo";

            case Sacraments::FIRST_COMMUNION:
                return "primeiraComunhao";

            case Sacraments::PROFESSION_OF_FAITH:
                return "profissaoFe";

            case Sacraments::CHRISMATION:
                return "confirmacao";

            default:
                throw new Exception("PdoDatabaseManager::translateSacramentIntoTable: Unknown sacrament.");
        }
    }

    private function translateMarriageTypeIntoString(int $marriageType)
    {
        switch($marriageType)
        {
            case Marriage::CHURCH:
                return "igreja";
                break;

            case Marriage::CIVIL:
                return "civil";
                break;

            case Marriage::DE_FACTO_UNION:
                return "uniao de facto";
                break;

            default:
                throw new Exception("Tipo de união desconhecido.");
        }
    }
}
