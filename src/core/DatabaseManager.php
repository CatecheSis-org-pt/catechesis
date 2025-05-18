<?php


namespace catechesis;



/**
 * Interface DatabaseManager
 * @package catechesis
 * Declares the public methods that are available and that any DatabaseManager implementation should support.
 */
interface DatabaseManager
{

    public function connect(string $username, string $password);
    public function connectAs(int $accessMode);
    public function connectAsNeeded(int $accessMode);                                                                   // Opens a new connection if needed
    public function disconnect();
    public function beginTransaction(int $accessMode = DatabaseAccessMode::UNCHANGED);
    public function commit();
    public function rollBack();


    // Catechumens
    public function getCatechumenById(int $cid);                                                                        // Returns all the data about a particular catechumen
    public function getCatechumensByNameAndBirthdate(string $name, string $birth_date);                                 // Returns catechumen(s) matching the exact name and birthdate
    public function findCatechumensByNameAndBirthdate(string $name, string $birth_date, int $catecheticalYear);         // Finds catechumens by name and birth date
    public function getCatechumensByCatechismWithFilters(int $currentCatecheticalYear,                                  // Finds catechumens by catechism/group with optional filters
                                                         int $searchCatecheticalYear = null, int $catechism = null,
                                                         string $group = null,
                                                         bool $includeAchievementRecord = false,
                                                         int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE,
                                                         int $baptism = SacramentFilter::IRRELEVANT,
                                                         int $communion = SacramentFilter::IRRELEVANT,
                                                         array $excludedCatechisms = array());
    public function getCatechumensByCatechistWithFilters(int $currentCatecheticalYear,                                  // Finds catechumens by their catechist with optional filters
                                                         int $searchCatecheticalYear = null, string $catechist = null,
                                                         int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE,
                                                         int $baptism = SacramentFilter::IRRELEVANT,
                                                         int $communion = SacramentFilter::IRRELEVANT,
                                                         array $excludedCatechisms = array(),
                                                         bool $onlyScouts = false);
    public function getCatechumenCurrentCatechesisGroup(int $cid, int $catecheticalYear);                               // Returns the catechesis group where the catechumen is enrolled, in that year
    public function getCatechumenSiblings(int $cid);                                                                    // Returns the IDs of all catechumens whose responsible is the responsible/father/mother of this catechumen
    public function createCatechumen(string $name, string $birthdate, string $birthplace, string $nif = null,
                                     $father_fid, $mother_fid, int $responsible_fid,
                                     string $responsible_relationship, string $photo, int $numSiblings,
                                     bool $isScout, bool $photosAllowed, bool $allowedToGoOutAlone,
                                     string $observations, string $createdByUsername);
    public function updateCatechumen(int $cid, string $name, string $birthdate, string $birthplace, string $nif = null, // Updates most fields of a catechumen
                                     $father_fid, $mother_fid, int $responsible_fid,
                                     string $responsible_relationship, string $photo, int $numSiblings,
                                     bool $isScout, bool $photosAllowed);
    public function setCatechumenObservationsFields(int $cid, string $observations=null);                               // Updates the catechumen observations field
    public function deleteCatechumen(int $cid);                                                                         // Deletes a catechumen from the database
    public function getCatechumenCatecheticalRecord(int $cid);                                                          // Returns info on all the catechesis groups where this catechumen was enrolled
    public function getCatechumenSchoolingRecord(int $cid);                                                             // Returns all schooling records for a catechumen
    public function insertCatechumenSchoolingRecord(int $cid, int $catecheticalYear, string $schoolYear);               // Registers which school year a catechumen is attending
    public function deleteCatechumenSchoolingRecord(int $cid, int $catecheticalYear=null);                              // Deletes a school year record (or all the records) from a catechumen
    public function getScouts(int $catecheticalYear);                                                                   // Returns catechumens that are also scout and are enrolled in this year
    public function getTodaysGroupBirthdays(int $catecheticalYear, int $catechism, string $group);                      // Returns all catechumens in a group whose birthday is today
    public function updateCatechumenAchievement(int $cid, int $catecheticalYear, int $catechism, string $group,         // Registers if a catechumens passes or fails a catechetical year
                                                int $achievement);
    public function setCatechumenAuthorizationToGoOutAlone(int $cid, bool $canLeaveAlone);                              // Sets if a catechumen can go out from the church alone
    public function getAllDistinctCatechumenNames();                                                                    // Returns all the distinct names of catechumens in the database
    public function getAllDistinctBirthPlaces();                                                                        // Returns all the distinct birth places in the catechumens table


    // Catechumen parents and family
    public function getFamilyMember(int $fid);                                                                          // Returns all data about a family member given its fid
    public function getFamilyMembersByName(string $name);                                                               // Returns family members by name
    public function getFamilyMemberChildren(int $fid);                                                                  // Returns catechumens whose parent or responsible is this family member
    public function getMarriageInformation(int $fid1, int $fid2);                                                       // Returns information about marriage of these people, if any
    public function addMarriageInformation(int $fid1, int $fid2, int $union_type);                                      // Registers a union between two family members (marriage, civil or de facto union)
    public function deleteMarriage(int $fid1, int $fid2);                                                               // Removes the marriage between the two family members from the database
    public function createFamilyMember(string $name, string $job=null, string $address=null, string $zipCode=null,      // Registers a new family member and returns its ID
                                       string $phone=null, string $cellPhone=null, string $email=null,
                                       bool $signedRGPD=false);
    public function deleteFamilyMember(int $fid);                                                                       // Deletes a family member from the database
    public function updateFamilyMemberName(int $fid, string $name);                                                     // Updates the name of a family member
    public function updateFamilyMemberJob(int $fid, string $job);                                                       // Updates the job of a family member
    public function updateFamilyMemberAllFields(int $fid, string $name, string $job, string $address,                   // Updates all fields of a family member in a single query
                                                string $zipCode, string $phone=null, string $cellPhone=null,
                                                string $email=null, bool $signedRGPD=false);
    public function getCatechumenAuthorizationList(int $cid);                                                           // Returns the list of family members authorized to pick up the catechumen
    public function addFamilyMemberToCatechumenAuthorizationlist(int $cid, int $fid, string $relationship);             // Adds a family member to the list of authorized members to pick up the catechumen
    public function removeFamilyMemberFromCatechumenAuthorizationList(int $cid, int $fid);                              // Removes a family member from the list of authorized members to pick up the catechumen
    public function getAllDistinctFamilyMemberNames();                                                                  // Returns all distinct names in the family member table
    public function getAllDistinctJobs();                                                                               // Returns all distinct jobs in the family member table
    public function getAllDistinctZipCodes();                                                                           // Returns all distinct zip codes from the addresses in the family members table


    // Online enrollments
    public function getCatechumensEnrollmentRenewalCandidateList(int $previousCatecheticalYear, int $previousCatechism, // Generates list of candidates for enrollment renewal
                                                                 string $previousGroup, int $enrollmentCatecheticalYear);
    public function getRenewalSubmissions(int $catecheticalYear, int $catechism = null);                                // Returns the renewals submitted online in a catechetical year
    public function getRenewalSubmission(int $rid);                                                                     // Returns a renewal submission given its ID
    public function getEnrollmentSubmissions(int $catecheticalYear);                                                    // Returns all the enrollments submitted online in the corresponding catechetical year
    public function getEnrollmentSubmission(int $eid);                                                                  // Returns an enrollment submission given its ID
    public function getNumberOfPendingRenewals(int $catecheticalYear = null);                                           // Counts the number of pending renewal submissions
    public function getNumberOfPendingEnrollments(int $catecheticalYear = null);                                        // Counts the number of pending enrollment submissions
    public function postRenewalOrder(string $applicantName, string $phone, string $catechumenName, int $lastCatechism,  // Submits a renewal order to the database and returns its ID
                                     string $ipAddress, string $email = null, string $obs = null);
    public function postEnrollmentOrder(string $catechumenName, string $birthDay, string $birthPlace, string $nif = null, // Registers a new enrollment order
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
                                             int $enrollmentCatechism = null, string $enrollmentGroup = null);          // Updates the status of a renewal order submission
    public function deleteRenewalOrder(int $rid);                                                                       // Deletes a renewal order from the database
    public function updateEnrollmentOrderFile(int $eid, int $cid = null);                                               // Marks an enrollment order as processed with a corresponding catechumen file
    public function deleteEnrollmentOrder(int $eid);                                                                    // Deletes an enrollment order from the database


    // Catechists & users
    public function getAllUsers();                                                                                      // Returns all users registered in the system
    public function getActiveCatechists();                                                                              // Returns the catechists that are currently active
    public function getUserAccountStatus(string $username);                                                             // Returns the name, account/catechist/admin status of a user
    public function getUserAccountDetails(string $username);                                                            // Returns a user's full name, phone and e-mail
    public function createUserAccount(string $username, string $name, string $password, bool $isAdmin,                  // Creates a new user/catechist account
                                      bool $isCatechist, bool $isCatechistActive=true, $phone=null, $email=null);
    public function updateUserAccountDetails(string $username, string $name, $phone, $email);                           // Updates a user's account name, phone and e-mail
    public function changeUserAccountStatus(string $username, bool $active);                                            // Activates or blocks a user account
    public function activateUserAccount(string $username);                                                              // Activates a user account
    public function blockUserAccount(string $username);                                                                 // Blocks a user account
    public function setUserAsAdmin(string $username, bool $isAdmin);                                                    // Sets or unsets a user's admin flag
    public function giveAdminRights(string $username);                                                                  // Give admin rights to a user
    public function revokeAdminRights(string $username);                                                                // Removes admin rights from a user
    public function setCatechistStatus(string $username, bool $isActive);                                               // Sets the catechist (teaching) status
    public function setAsActiveCatechist(string $username);                                                             // Sets the user/catechist as an active catechist
    public function setAsInactiveCatechist(string $username);                                                           // Sets the user/catechist as an inactive catechist
    public function getCatechistGroups(string $username, int $catecheticalYear);                                        // Returns all the groups where this catechist teaches
    public function getAllAssignedCatechists(int $catecheticalYear);                                                    // Returns all the catechists already assigned to at least one group
    public function getGroupCatechists(int $catecheticalYear, int $catechism, string $group);                           // Returns the catechists of a group
    public function addCatechistToGroup(string $username, int $catecheticalYear, int $catechism, string $group);        // Adds a catechist to a catechesis group
    public function removeCatechistFromGroup(string $username, int $catecheticalYear, int $catechism, string $group);   // Removes a catechist from a catechesis group
    public function checkIfCatechumenBelongsToCatechist(int $cid, string $username, int $catecheticalYear);             // Returns true if a catechumen belongs to a catechist's group

    // Catechesis
    public function getCatecheticalYears();                                                                             // Returns all the distinct catechetical years in the database
    public function getCatechisms(int $catecheticalYear = null);                                                        // Returns all the distinct catechisms in the database
    public function getCatechismGroups(int $catecheticalYear, int $catechism);                                          // Returns all the groups for a particular catechism and year
    public function getCatechismsAndGroups(int $catecheticalYear = null);                                               // Returns all distinct pairs of (catechism, group)
    public function getCatechismsAndGroupsFromLatestYear();                                                             // Returns the pairs of (catechism, group) from the latest registered year in the database
    public function hasCatechism(int $catecheticalYear, int $catechism);                                                // Returns true if a particular catechism exists in the database
    public function getGroupLetters(int $catecheticalYear = null);                                                      // Returns all the distinct group (class) letters in the database
    public function createCatechismGroup(int $catecheticalYear, int $catechism, string $group);                         // Inserts a new catechism group
    public function deleteCatechismGroup(int $catecheticalYear, int $catechism, string $group);                         // Deletes a group from the database
    public function enrollCatechumenInGroup(int $cid, int $catecheticalYear, int $catechism, string $group,             // Enrolls a catechumen in a catechesis group
                                            bool $pass, bool $paid, string $username);
    public function unenrollCatechumenFromGroup(int $cid, int $catecheticalYear, int $catechism, string $group);        // Unenrolls a catechumen from a catechesis group
    public function unenrollCatechumenFromAllGroups(int $cid, bool $useTransaction=true);                               // Unenrolls a catechumen from all the groups where he/she is enrolled
    public function updateCatechumenEnrollmentPayment(int $cid, int $catecheticalYear, int $catechism, string $group,   // Updates the payment status of a catechumen enrollment
                                                       bool $paid);
    public function getCatecheticalYearsWhereCatechumenIsNotEnrolled(int $cid);                                         // Returns all the catechetical years where the catechumen is NOT enrolled


    // Sacraments
    public function getSacramentsCivilYears(int $sacrament);                                                            // Returns all the distinct civil years in the database for this sacrament
    public function getDistinctParishes(int $sacrament);                                                                // Returns all the distinct parishes in the database for this sacrament
    public function getAllDistinctParishes();                                                                           // Returns all the distinct parishes in the database across all sacraments
    public function getCatechumensBySacrament(int $sacrament, int $civilYear = null, string $parish = null,             // Returns all the catechumens that received the given sacrament
                                              int $orderBy = OrderCatechumensBy::NAME_BIRTHDATE);
    public function getCatechumensWithAndWithoutSacramentByCatechismAndGroup(int $sacrament, int $catecheticalYear,     // Returns a list of all the catechumens of a group plus a column stating if they have the sacrament or not
                                                                             int $catechism, string $group);
    public function getCatechumenSacramentRecord(int $sacrament, int $cid);                                             // Returns the sacrament record for a catechumen given his/her cid
    public function insertSacramentRecord(int $cid, int $sacrament, string $date, string $parish);                      // Inserts a new sacrament record
    public function updateSacramentRecord(int $cid, int $sacrament, string $date, string $parish);                      // Updates the date and parish of a sacrament record
    public function deleteSacramentRecord(int $cid, int $sacrament);                                                    // Deletes a sacrament record for a catechumen
    public function setSacramentProofDocument(int $cid, int $sacrament, string $proof=null);                            // Stores the path for a sacrament proof document


    // Decision support system
    public function getBaptismAnalysis(int $catecheticalYear, bool $admin, string $username);                           // Returns Baptism data for decision support
    public function getFirstCommunionAnalysis(int $catecheticalYear, bool $admin, string $username);                    // Returns First Communion data for decision support
    public function getChrismationAnalysis(int $catecheticalYear, bool $admin, string $username);                       // Returns Chrismation data for decision support
    public function getDataDumpForInconsistencyAnalysis(string $username, bool $admin, int $currentCatecheticalYear,    // Dumps all the necessary data to inconsistency analysis
                                                        int $catecheticalYear = null, int $catechism = null,
                                                        string $group = null);

    // Statistics
    public function isDataSufficientForResidentsStatistic();                                                            // Checks if there is sufficient data already for the residents statistic
    public function isDataSufficientForAbadonmentStatistic(int $currentCatecheticalYear);                               // Checks if there is sufficient data for the abandonment statistic
    public function isDataSufficientForCompleteCatecheticalJourneyStatistic(int $currentCatecheticalYear);              // Checks if there is sufficient data for the complete journey statistic
    public function isDataSufficientForNumberOfCatechumensByCatechist();                                                // Checks if there is sufficient data for the number of catechumens by catechist statistic
    public function getResidentCatechumensPercentage();                                                                 // Returns the percentage of catechumens with postal code in the parish area
    public function getCatecheticalYearsRangeForCatechumens();                                                          // Returns the earliest and latest catechetical year with catechumens enrolled
    public function getCatecheticalYearsRangeForCatechumensAndCatechists();                                             // Returns the earliest and latest catechetical year with catechumens enrolled and catechists assigned
    public function getAbandonmentByCatecheticalYear(int $currentCatecheticalYear, bool $inPercentage);                 // Returns the number of catechumens abandoning catechesis by catechetical year
    public function getCompleteCatecheticalJourneysByCatecheticalYear(int $currentCatecheticalYear, bool $inPercentage);// Returns the number of catechumens completing the catechetical journey by catechetical year
    public function getCatechumensByCatechistAndYear(bool $accumulated);                                                // Returns the number of catechumens by catechist and catechetical year

    // Virtual catechesis
    public function getVirtualCatechesisSessionDates(int $catechism = null, string $group = null,                       // Returns the calendar dates for which a virtual catechesis session exists
                                                     bool $recursive = true, int $limit = 0,
                                                     string $afterDate = null, string $beforeDate = null);
    public function getVirtualCatechesisContent(string $sessionDate, int $catechism = null, string $group = null,       // Returns the contents of a virtual catechesis session
                                                bool $recursive = true);
    public function postVirtualCatechesisContent(string $contents, string $username, string $sessionDate,               // Saves the contents of a virtual catechesis session
                                                 int $catechism = null, string $group = null);
    public function insertLockInVirtualCatechesis(string $username, string $sessionDate,                                // Registers that a user has a virtual catechesis open for editing
                                                  int $catechism = null, string $group = null);
    public function getListOfVirtualCatechesisObservers(string $sessionDate, int $timeThreshold, int $catechism = null, // Returns the list of users currently editing a virtual catechesis
                                                        string $group = null, string $excludeUsername = null);

    // Settings
    public function getConfigValue(string $key);                                                                        // Returns the configuration value associated with the given key
    public function setConfigValue(string $key, string $value);                                                         // Stores or updates a configuration value in the key-value store

    // Log
    public function getCatechesisLog();                                                                                 // Returns the whole main Catechesis log contents
    public function getAuthenticationsLog();                                                                            // Returns the whole authentications log
    public function getLogEntry(int $lsn);                                                                              // Returns a particular log entry, given its sequence number
    public function addLogEntry(string $username, string $action);                                                      // Inserts a new CatecheSis log entry
    public function updateCatechumenFileLog(int $cid, int $lsn);                                                        // Updates the log sequence number for the last modification of a catechumen's file
    public function updateCatechumenArchiveLog(int $cid, int $lsn);                                                     // Updates the log sequence number for the last modification of a catechumen's archive
    public function updateCatechumenAuthorizationsLog(int $cid, int $lsn);                                              // Updates the log sequence number for the last modification of a catechumen's authorizations
    public function getOldestLSNtoKeep(int $maxRecords);                                                                // Returns the oldest LSN to keep when doing a cleanup, maintaining the most recent $maxRecords records
    public function deleteLogEntriesOlderThan(int $lsn);                                                                // Deletes CatecheSis log entries older than the provided LSN
}




abstract class OrderCatechumensBy
{
    const NAME_BIRTHDATE = 0;       //Order by name and birthdate
    const LAST_CHANGED = 1;         //Order by archive change date
    const SACRAMENT_DATE = 1;       //Order by sacrament date (for sacrament related queries)
}

abstract class SacramentFilter
{
    const IRRELEVANT = 0;           //It is irrelevant whether the catechumen has the sacrament or not
    const HAS = 1;                  //Only return catechumens that have the sacrament
    const HAS_NOT = 2;              //Only return catechumens that do NOT have the sacrament
}


abstract class DatabaseAccessMode
{
    const UNCHANGED = -1;                   //Special keyword to keep the acess mode of the previous connection
    const DEFAULT_READ = 0;                 //Permissions to read only on most CatecheSis tables
    const DEFAULT_EDIT = 1;                 //Permissions to read and write on most CatecheSis tables
    const DEFAULT_DELETE = 2;               //Permissions to read/write and delete on most CatecheSis tables
    const GROUP_MANAGEMENT = 3;             //Permissions to alter groups table
    const USER_MANAGEMENT = 4;              //Permissions to alter users table
    const LOG_EDIT = 5;                     //Permissions to read and edit log
    const LOG_CLEAN = 6;                    //Permissions to delete log entries
    const CONFIGURATION = 7;                //Permissions to read/change configurations
    const ONLINE_ENROLLMENT = 8;            //Permissions to add entries to online enrollment tables
}