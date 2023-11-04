<?php

namespace catechesis;

require_once(__DIR__ . '/config/catechesis_config.inc.php');
require_once(__DIR__ . '/PdoDatabaseManager.php');
require_once(__DIR__ . '/domain/WeekDay.php');

use catechesis\PdoDatabaseManager;
use core\domain\WeekDay;
use Exception;


/**
 * Class that represents a CatecheSis configuration parameter.
 * The type specification is used by the Configurator class to automatically
 * convert the stored values into the correct primitive types, when reading
 * from the database.
 */
class ConfigurationObject
{
    //Types definition
    const TYPE_BOOL = "bool";
    const TYPE_INT = "int";
    const TYPE_FLOAT = "float";
    const TYPE_DOUBLE = "double";
    const TYPE_STRING = "string";


    private /*string*/ $key;
    private /*string*/ $type;
    private $defaultValue;
    //public /*bool|int|float|double|string*/ $value;


    public function __construct(string $key, string $type, $defaultValue = null)
    {
        $this->key = $key;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Converts the value stored in the database (for a key-value pair)
     * into the corresponding primitive type in PHP,
     * according to the ConfigurationObject specification.
     * @param string $value
     * @return bool|float|int|string
     */
    public function coerceValue(string $value)
    {
        switch($this->type)
        {
            case self::TYPE_BOOL:
                return $value == "true" || $value == "activo" || $value == "1"; //FIXME

            case self::TYPE_INT:
                return intval($value);

            case self::TYPE_FLOAT:
                return floatval($value);

            case self::TYPE_DOUBLE:
                return doubleval($value);

            case self::TYPE_STRING:
            default:
                return $value;
        }
    }
}



/**
 * Class Configurator.
 * Provides methods to read and set CatecheSis configuration settings.
 */
class Configurator
{

    //Definition of keys to store key-value configurations in the database
    const KEY_NUM_CATECHISMS = "NUM_CATECHISMS";                                                                        //The highest catechism in this parish (in some parishes may be higher than the default 10)
    const KEY_CATECHESIS_WEEK_DAY = "CATECHESIS_WEEK_DAY";                                                              //The day of the week on which catechesis is ministered
    const KEY_CATECHUMENS_EVALUATION = "CATECHUMENS_EVALUTATIONS_OPEN";                                                 //Whether catechumens evaluations are open for catechists or not

    const KEY_PARISH_NAME = "PARISH_NAME";                                                                              //Name of this parish (shown in page footers and several places)
    const KEY_PARISH_PLACE = "PARISH_PLACE";                                                                            //Name of the place where this parish is located
    const KEY_PARISH_DIOCESE = "PARISH_DIOCESE";                                                                        //Diocese to which this parish belongs
    const KEY_PARISH_CUSTOM_TABLE_FOOTER = "PARISH_CUSTOM_TABLE_FOOTER";                                                //Text/HTML footer used in printed tables

    const KEY_LOCALIZATION_CODE = "LOCALIZATION_CODE";                                                                  //Country code for localization purposes

    const KEY_GDPR_RESPONSIBLE_NAME = "GDPR_RESPONSIBLE_NAME";                                                          //Name of the responsible for data processing
    const KEY_GDPR_RESPONSBILE_ADDRESS = "GDPR_RESPONSIBLE_ADDRESS";                                                    //Address of the responsible for data processing
    const KEY_GDPR_RESPONSIBLE_EMAIL = "GDPR_RESPONSIBLE_EMAIL";                                                        //E-mail of the responsible for data processing
    const KEY_GDPR_DPO_NAME = "GDPR_DPO_NAME";                                                                          //Name of the data protection officer
    const KEY_GDPR_DPO_ADDRESS = "GDPR_DPO_ADDRESS";                                                                    //Address of the data protection officer
    const KEY_GDPR_DPO_EMAIL = "GDPR_DPO_EMAIL";                                                                        //E-mail of the data protection officer

    const KEY_USE_CUSTOM_PUBLIC_PAGE_IMAGE = "USE_CUSTOM_PUBLIC_PAGE_IMAGE";                                            //Whether to use a custom image on the public homepage or the default one

    const KEY_ONLINE_ENROLLMENTS_OPEN = "ONLINE_ENROLLMENTS_OPEN";                                                      //Whether online enrollments are open for parents or not
    const KEY_ENROLLMENT_CUSTOM_TEXT = "ENROLLMENT_CUSTOM_TEXT";                                                        //Custom information text shown on online enrollment pages
    const KEY_ENROLLMENT_SHOW_PAYMENT_DATA = "ENROLLMENT_SHOW_PAYMENT_DATA";                                            //Whether bank payment data should be shown after an enrollment submission
    const KEY_ENROLLMENT_PAYMENT_ENTITY = "ENROLLMENT_PAYMENT_ENTITY";                                                  //Bank entity (number)
    const KEY_ENROLLMENT_PAYMENT_REFERENCE = "ENROLLMENT_PAYMENT_REFERENCE";                                            //Bank reference (number)
    const KEY_ENROLLMENT_PAYMENT_AMOUNT = "ENROLLMENT_PAYMENT_AMOUNT";                                                  //Enrollment price tag (euro)
    const KEY_ENROLLMENT_PAYMENT_ACCEPT_BIGGER_DONATIONS = "ENROLLMENT_PAYMENT_ACCEPT_BIGGER_DONATIONS";                //Whether the payer is suggested to donate more than the required amount
    const KEY_ENROLLMENT_PAYMENT_PROOF = "ENROLLMENT_PAYMENT_PROOF";                                                    //E-mail address or URL (ex: a cloud folder) to which payers should send the proof of payment

    const KEY_CATECHESIS_NEXTCLOUD_BASE_URL = "CATECHESIS_NEXTCLOUD_BASE_URL";                                          //Path to Nextcloud front page
    const KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL = "CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL";                //Path to public shared folder in Nextcloud to store virtual catechesis resources

    const KEY_MAINTENANCE_MODE = "MAINTENANCE_MODE";                                                                    //Enabling the maintenance mode forbids logins and shows the error 500 page on the landing page

    private static $CONFIGURATIONS = null; //Stores the configuration objects


    /**
     * Initializes the array $CONFIGURATIONS (if needed), populating it with
     * the available configuration keys and types.
     * This method is called automatically on the first invocation of any method of this class.
     * @return void
     */
    private static function __initConfigurations()
    {
        if(self::$CONFIGURATIONS == null)
        {
            self::$CONFIGURATIONS = array(
                self::KEY_NUM_CATECHISMS => new ConfigurationObject(self::KEY_NUM_CATECHISMS, ConfigurationObject::TYPE_INT, 10),
                self::KEY_CATECHESIS_WEEK_DAY => new ConfigurationObject(self::KEY_CATECHESIS_WEEK_DAY, ConfigurationObject::TYPE_INT, WeekDay::SUNDAY),
                self::KEY_CATECHUMENS_EVALUATION => new ConfigurationObject(self::KEY_CATECHUMENS_EVALUATION, ConfigurationObject::TYPE_BOOL, true),

                self::KEY_PARISH_NAME => new ConfigurationObject(self::KEY_PARISH_NAME, ConfigurationObject::TYPE_STRING, null),
                self::KEY_PARISH_PLACE => new ConfigurationObject(self::KEY_PARISH_PLACE, ConfigurationObject::TYPE_STRING, null),
                self::KEY_PARISH_DIOCESE => new ConfigurationObject(self::KEY_PARISH_DIOCESE, ConfigurationObject::TYPE_STRING, null),
                self::KEY_PARISH_CUSTOM_TABLE_FOOTER => new ConfigurationObject(self::KEY_PARISH_CUSTOM_TABLE_FOOTER, ConfigurationObject::TYPE_STRING, null),

                self::KEY_LOCALIZATION_CODE => new ConfigurationObject(self::KEY_LOCALIZATION_CODE, ConfigurationObject::TYPE_STRING, "PT"),

                self::KEY_GDPR_RESPONSIBLE_NAME => new ConfigurationObject(self::KEY_GDPR_RESPONSIBLE_NAME, ConfigurationObject::TYPE_STRING, "a Catequese Paroquial NOME DA PARÓQUIA"),
                self::KEY_GDPR_RESPONSBILE_ADDRESS => new ConfigurationObject(self::KEY_GDPR_RESPONSBILE_ADDRESS, ConfigurationObject::TYPE_STRING, null),
                self::KEY_GDPR_RESPONSIBLE_EMAIL => new ConfigurationObject(self::KEY_GDPR_RESPONSIBLE_EMAIL, ConfigurationObject::TYPE_STRING, null),
                self::KEY_GDPR_DPO_NAME => new ConfigurationObject(self::KEY_GDPR_DPO_NAME, ConfigurationObject::TYPE_STRING, "o Pároco da Paróquia de NOME DA PARÓQUIA"),
                self::KEY_GDPR_DPO_ADDRESS => new ConfigurationObject(self::KEY_GDPR_DPO_ADDRESS, ConfigurationObject::TYPE_STRING, null),
                self::KEY_GDPR_DPO_EMAIL => new ConfigurationObject(self::KEY_GDPR_DPO_EMAIL, ConfigurationObject::TYPE_STRING, null),

                self::KEY_USE_CUSTOM_PUBLIC_PAGE_IMAGE => new ConfigurationObject(self::KEY_USE_CUSTOM_PUBLIC_PAGE_IMAGE, ConfigurationObject::TYPE_BOOL, false),

                self::KEY_ONLINE_ENROLLMENTS_OPEN => new ConfigurationObject(self::KEY_ONLINE_ENROLLMENTS_OPEN, ConfigurationObject::TYPE_BOOL, false),
                self::KEY_ENROLLMENT_CUSTOM_TEXT => new ConfigurationObject(self::KEY_ENROLLMENT_CUSTOM_TEXT, ConfigurationObject::TYPE_STRING, "<p>À semelhança dos outros anos, pede-se um contributo, em forma de donativo, para fazer face aos custos dos catecismos, materiais didáticos e outros materiais, utilizados ao longo do ano de catequese. Para este ano definiu-se um valor de <strong>XX euros</strong>.</p>\n"
                                                                                                                                                                   . "<p>O contributo deverá ser efetuado através da referência multibanco indicada <u>na página seguinte</u>, após a submissão do pedido. A validação da inscrição só ficará concluída após receção do respetivo comprovativo de pagamento, porém <strong>que nenhuma criança/adolescente deixe de se inscrever por causa do referido contributo.</strong></p>"),
                self::KEY_ENROLLMENT_SHOW_PAYMENT_DATA => new ConfigurationObject(self::KEY_ENROLLMENT_SHOW_PAYMENT_DATA, ConfigurationObject::TYPE_BOOL, false),
                self::KEY_ENROLLMENT_PAYMENT_ENTITY => new ConfigurationObject(self::KEY_ENROLLMENT_PAYMENT_ENTITY, ConfigurationObject::TYPE_INT, null),
                self::KEY_ENROLLMENT_PAYMENT_REFERENCE => new ConfigurationObject(self::KEY_ENROLLMENT_PAYMENT_REFERENCE, ConfigurationObject::TYPE_INT, null),
                self::KEY_ENROLLMENT_PAYMENT_AMOUNT => new ConfigurationObject(self::KEY_ENROLLMENT_PAYMENT_AMOUNT, ConfigurationObject::TYPE_FLOAT, 0.0),
                self::KEY_ENROLLMENT_PAYMENT_ACCEPT_BIGGER_DONATIONS => new ConfigurationObject(self::KEY_ENROLLMENT_PAYMENT_ACCEPT_BIGGER_DONATIONS, ConfigurationObject::TYPE_BOOL, true),
                self::KEY_ENROLLMENT_PAYMENT_PROOF => new ConfigurationObject(self::KEY_ENROLLMENT_PAYMENT_PROOF, ConfigurationObject::TYPE_STRING, null),

                self::KEY_CATECHESIS_NEXTCLOUD_BASE_URL => new ConfigurationObject(self::KEY_CATECHESIS_NEXTCLOUD_BASE_URL, ConfigurationObject::TYPE_STRING, null),
                self::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL => new ConfigurationObject(self::KEY_CATECHESIS_NEXTCLOUD_VIRTUAL_RESOURCES_URL, ConfigurationObject::TYPE_STRING, null),

                self::KEY_MAINTENANCE_MODE => new ConfigurationObject(self::KEY_MAINTENANCE_MODE, ConfigurationObject::TYPE_BOOL, false)
            );
        }
    }
    //------------------------------------------------------------------


    /**
     * Checks if a configuration key exists (is set) in the database. If it exists, its corresponding value is returned.
     * Else, a new configuration is created with the provided default value, and that value is returned.
     * @param string $key
     * @return string|null
     * @throws Exception
     */
    public static function getConfigurationValueOrDefault(string $key)
    {
        self::__initConfigurations();

        $configObject = self::$CONFIGURATIONS[$key];

        if (!Configurator::configurationExists($key))
        {
            Configurator::setConfigurationValue($key, strval($configObject->getDefaultValue()));
            return $configObject->getDefaultValue();
        }
        else
        {
            return $configObject->coerceValue(Configurator::getConfigurationValue($key));
        }
    }


    /**
     * Checks if a configuration key exists (is set) in the database. Returns true or false, accordingly.
     * May throw an exception if a database error occurs.
     * @param $key
     * @return bool
     * @throws Exception
     */
    public static function configurationExists(string $key)
    {
        self::__initConfigurations();

        try
        {
            $db = new PdoDatabaseManager();
            $value = $db->getConfigValue($key);
            $db = null;

            return isset($value);
        }
        catch (Exception $e)
        {
            throw new Exception("Falha ao verificar existência de configuração.");
        }
    }

    /**
     * Returns a configuration value, given its key.
     * Throws an exception if no such key exists.
     * @param $key
     * @return null
     * @throws Exception
     */
    public static function getConfigurationValue(string $key)
    {
        self::__initConfigurations();

        try
        {
            $db = new PdoDatabaseManager();
            $value = $db->getConfigValue($key);
            $db = null;

            return self::$CONFIGURATIONS[$key]->coerceValue( $value );
        }
        catch (Exception $e)
        {
            throw new Exception("Falha ao obter valor de configuração.");
        }
    }


    /**
     * Sets or updates a configuration value in the key-value store.
     * Returns true in case of success, and an exception if a database error occurs.
     * @param $key
     * @param $value
     * @return bool
     * @throws Exception
     */
    public static function setConfigurationValue(string $key, $value)
    {
        self::__initConfigurations();

        try
        {
            $db = new PdoDatabaseManager();
            $res = $db->setConfigValue($key, strval($value));
            $db = null;

            return $res;
        }
        catch (Exception $e)
        {
            throw new Exception("Falha ao definir valor de configuração.");
        }
    }
}