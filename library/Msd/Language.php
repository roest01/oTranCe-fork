<?php
/**
 * This file is part of MySQLDumper released under the GNU/GPL 2 license
 * http://www.mysqldumper.net
 *
 * @package         MySQLDumper
 * @subpackage      Language
 * @version         SVN: $Rev$
 * @author          $Author$
 */
/**
 * Language class implemented as singleton
 *
 * Handles translation of language variables.
 *
 * @package         MySQLDumper
 * @subpackage      Language
 */
class Msd_Language
{
    /**
     * Instance
     *
     * @var Msd_Language
     */
    private static $_instance = NULL;

    /**
     * Holds the current language
     * @var string
     */
    private $_language;

    /**
     * Translator
     *
     * @var Zend_Translate
     */
    private $_translate = NULL;

    /**
     * Base directory for language files
     *
     * @var string
     */
    private $_baseLanguageDir = null;

    /**
     * Constructor loads the selected language of the user
     *
     * @param string $language Iso-Code of language to load
     */
    private function __construct($language = '')
    {
        if ($language == '') {
            $user            = new Application_Model_User();
            $this->_language = $user->loadSetting('interfaceLanguage', 'en');
        } else {
            $this->_language = $language;
        }
        $this->loadLanguageByLocale($this->_language);
    }

    /**
     * Load new language.
     *
     * @param string $language Locale of language to load
     *
     * @return void
     */
    public function loadLanguageByLocale($language)
    {
        if (empty($language)) {
            $language = 'en';
        }
        $this->_baseLanguageDir = APPLICATION_PATH . '/language/';
        $file                   = $this->_baseLanguageDir . $language . '/lang.php';
        $translator             = $this->getTranslator();
        if ($translator === null) {
            $translator = new Zend_Translate('array', $file, $language);
        } else {
            $translator->setAdapter(
                array(
                    'adapter' => 'array',
                    'content' => $file,
                    'locale'  => $language
                )
            );
        }
        $this->setTranslator($translator);
        Zend_Registry::set('Zend_Translate', $translator);
    }

    /**
     * No cloning for singleton
     *
     * @throws Msd_Exception
     *
     * @return void
     */
    public function __clone()
    {
        throw new Msd_Exception('Cloning of Msd_Language is not allowed!');
    }

    /**
     * Magic getter to keep syntax in rest of script short
     *
     * @param mixed $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        return $this->translate($property);
    }

    /**
     * Returns the single instance
     *
     * @param string $language Iso-Code of language to load
     *
     * @return Msd_Language
     */
    public static function getInstance($language = '')
    {
        if (NULL == self::$_instance) {
            self::$_instance = new self($language);
        }

        return self::$_instance;
    }

    /**
     * Translate a key.
     *
     * If key was not found remove the prefix "L_".
     *
     * @param string $key The key of the langugae var to translate
     *
     * @return string
     */
    public function translate($key)
    {
        $translated = $this->getTranslator()->_($key);
        if ($translated == $key && substr($key, 0, 2) == 'L_') {
            // no translation found -> remove prefix L_
            return substr($key, 2);
        }

        return $translated;
    }

    /**
     * Translate a Message from Zend_Validate.
     *
     * @param string $zendMessageId Message ID from Zend_Validate
     * @param string $messageText   Pre-translatet message
     *
     * @return string
     */
    public function translateZendId($zendMessageId, $messageText = '')
    {
        if (substr($zendMessageId, 0, 6) == 'access' && $messageText > '') {
            // message is already translated by validator access
            return $messageText;
        }

        return $this->_translate->_(
            $this->_transformMessageId($zendMessageId)
        );
    }

    /**
     * Translate Zend message ids into our own ones.
     *
     * @param array $messages Zend messages
     *
     * @return array
     */
    public function translateZendMessageIds($messages)
    {
        $ret = array();
        foreach (array_keys($messages) as $messageId) {
            $ret[] = $this->translateZendId($messageId);
        }

        return $ret;
    }

    /**
     * Transforms a message ID in Zend_Validate format into Msd_Language format.
     *
     * @param string $zendMessageId Message ID from Zend_Validate
     *
     * @return string
     */
    private function _transformMessageId($zendMessageId)
    {
        $result = preg_replace('/([A-Z])/', '_${1}', $zendMessageId);
        $result = strtoupper($result);

        return 'L_ZEND_ID_' . $result;
    }

    /**
     * Get Translator
     *
     * @return Zend_Translate
     */
    public function getTranslator()
    {
        return $this->_translate;
    }

    /**
     * Set Translator
     *
     * @param Zend_Translate $translate
     *
     * @return void
     */
    public function setTranslator(Zend_Translate $translate)
    {
        $this->_translate = $translate;
    }

    /**
     * Retrieve a list of available languages.
     *
     * @return array
     */
    public function getAvailableLanguages()
    {
        $currentTranslator = $this->getTranslator();
        $languageDirs      = glob(APPLICATION_PATH . '/language/*', GLOB_ONLYDIR);
        $ret               = array();
        foreach ($languageDirs as $dir) {
            $parts = explode('/', $dir);
            $lang  = array_pop($parts);
            $this->loadLanguageByLocale($lang);
            $translator = $this->getTranslator();
            $ret[$lang] = array('locale' => $lang, 'name' => $translator->translate('L_LANGUAGE_NAME'));
        }
        $this->setTranslator($currentTranslator);
        $this->loadLanguageByLocale($this->_language);

        return $ret;
    }

    /**
     * Get the locale of the currently loaded language
     *
     * @return string
     */
    public function getActiveLanguage()
    {
        return $this->_language;
    }

    /**
     * Add an additional translation file to our translation adapter.
     *
     * Try to load it from $basePath/{locale}/$fileName.
     * If we can't find the file try to fallback to given fallback locale.
     *
     * @param string $basePath       Base path of language files
     * @param string $fileName       Name of language file to add
     * @param string $fallbackLocale If we can't find the file in the current locale fallback to this one.
     *
     * @return void
     */
    public function addTranslationFile($basePath, $fileName, $fallbackLocale = 'en')
    {
        $locale = $this->getActiveLanguage();
        $file   = $basePath . '/' . $locale . '/' . $fileName;
        if (!is_readable($file)) {
            $file = $basePath . '/' . $fallbackLocale . '/' . $fileName;
            if (!is_readable($file)) {
                // Ok. We can't help it. No translation file found.
                return;
            }
        }

        $translate = new Zend_Translate(
            array(
                'adapter' => 'array',
                'content' => $file,
                'locale'  => $locale)
        );
        $this->getTranslator()->addTranslation($translate);
    }
}
