<?php
/**
 * This file is part of MySQLDumper released under the GNU/GPL 2 license
 * http://www.mysqldumper.net
 *
 * @package         MySQLDumper
 * @subpackage      View_Helpers
 * @version         SVN: $Rev$
 * @author          $Author$
 */

/**
 * Print translator names
 *
 * @package         MySQLDumper
 * @subpackage      View_Helpers
 */
class Msd_View_Helper_PrintTranslators extends Zend_View_Helper_Abstract
{
    /**
     * Holds all user names taken from database
     *
     * @var array
     */
    private static $_translators;

    /**
     * Print name/s of user_id's
     *
     * @param int|array $userIds Id list of users
     *
     * @return string
     */
    public function printTranslators($userIds)
    {

        if (!is_array($userIds)) {
            $userIds = array($userIds);
        }

        if (self::$_translators === null) {
            $translatorModel = new Application_Model_User();
            self::$_translators = $translatorModel->getUsers();
        }
        $ret = '';

        foreach ($userIds as $userId) {
            if (isset(self::$_translators[$userId])) {
                $ret .= self::$_translators[$userId]['username'] .', ';
            }
        }
        $ret = substr($ret, 0, -2);
        return $ret;
    }
}
