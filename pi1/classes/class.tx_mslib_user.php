<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
/***************************************************************
 *  Copyright notice
 *  (c) 2010 BVB Media BV - Bas van Beek <bvbmedia@gmail.com>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 * Hint: use extdeveval to insert/update function index above.
 */
class tx_mslib_user {
    var $get = '';
    var $post = '';
    var $ms = '';
    var $ref = '';
    /*
     * properties of user
    */
    var $username = '';
    var $email = '';
    var $confirmation_email = '';
    var $gender = '';
    var $password = '';
    var $confirmation_password = '';
    var $first_name = '';
    var $middle_name = '';
    var $last_name = '';
    var $name = '';
    var $company = '';
    var $country = '';
    var $address = '';
    var $address_number = '';
    var $address_ext = '';
    var $zip = '';
    var $city = '';
    var $telephone = '';
    var $mobile = '';
    var $newsletter = '';
    var $captcha_code = '';
    var $birthday = '';
    var $region = '';
    var $customFields = array();
    function init(&$ref) {
        $this->ref = $ref;
        $this->DOCUMENT_ROOT = $ref->DOCUMENT_ROOT;
        $this->HTTP_HOST = $ref->HTTP_HOST;
        $this->FULL_HTTP_URL = $ref->FULL_HTTP_URL;
        $this->DOCUMENT_ROOT_MULTISHOP = $ref->DOCUMENT_ROOT_MULTISHOP;
        $this->DOCUMENT_ROOT_MS = $ref->DOCUMENT_ROOT_MS;
        $this->FULL_HTTP_URL_MS = $ref->FULL_HTTP_URL_MS;
        $this->FULL_HTTP_URL_MULTISHOP = $ref->FULL_HTTP_URL_MS;
        $this->get = &$ref->get;
        $this->post = &$ref->post;
        $this->conf = &$ref->conf;
        $this->ms = &$ref->ms;
        $this->cObj = &$ref->cObj;
        $this->extKey = &$ref->extKey;
        $this->server = &$ref->server;
        $this->cart_page_uid = &$ref->cart_page_uid;
        $this->shop_pid = &$ref->shop_pid;
        $this->showCatalogFromPage = &$ref->showCatalogFromPage;
        $this->sys_language_uid = &$ref->sys_language_uid;
        $this->tta_user_info =& $ref->tta_user_info;
        $this->tta_shop_info =& $ref->tta_shop_info;
        $this->sys_language_uid =& $ref->sys_language_uid;
        $this->lang =& $ref->lang;
        $this->LLkey =& $ref->LLkey;
        $this->LOCAL_LANG =& $ref->LOCAL_LANG;
        $this->excluded_userGroups =& $ref->excluded_userGroups;
        $this->categoriesStartingPoint =& $ref->categoriesStartingPoint;
        $this->REMOTE_ADDR =& $ref->REMOTE_ADDR;
        $this->cookie =& $ref->cookie;
        $this->msDebugInfo =& $ref->msDebugInfo;
        $this->masterShop =& $ref->masterShop;
        // PERMISSIONS
        $this->ADMIN_USER = $ref->ADMIN_USER;
        $this->ROOTADMIN_USER = $ref->ROOTADMIN_USER;
        $this->CMSADMIN_USER =& $ref->CMSADMIN_USER;
        $this->CUSTOMERSADMIN_USER =& $ref->CUSTOMERSADMIN_USER;
        $this->CMSADMIN_USER =& $ref->CMSADMIN_USER;
        $this->CATALOGADMIN_USER =& $ref->CATALOGADMIN_USER;
        $this->ORDERSADMIN_USER =& $ref->ORDERSADMIN_USER;
        $this->STORESADMIN_USER =& $ref->STORESADMIN_USER;
        $this->SEARCHADMIN_USER =& $ref->SEARCHADMIN_USER;
        $this->SYSTEMADMIN_USER =& $ref->SYSTEMADMIN_USER;
        $this->STATISTICSADMIN_USER =& $ref->STATISTICSADMIN_USER;
        return true;
    }
    /**
     * @return the $middle_name
     */
    public function getMiddle_name() {
        return $this->middle_name;
    }
    /**
     * @param string $middle_name
     */
    public function setMiddle_name($middle_name) {
        $this->middle_name = trim($middle_name);
    }
    /**
     * @return the $name
     */
    public function getName() {
        return $this->name;
    }
    /**
     * @param string $name
     */
    public function setName($name) {
        $name = preg_replace('/\s+/', ' ', $name);
        $this->name = trim($name);
    }
    /**
     * @return the $address_ext
     */
    public function getAddress_ext() {
        return $this->address_ext;
    }
    /**
     * @param string $address_ext
     */
    public function setAddress_ext($address_ext) {
        $this->address_ext = trim($address_ext);
    }
    /**
     * @return the $telephone
     */
    public function getTelephone() {
        return $this->telephone;
    }
    /**
     * @param string $telephone
     */
    public function setTelephone($telephone) {
        $this->telephone = trim($telephone);
    }
    /**
     * @return the $mobile
     */
    public function getMobile() {
        return $this->mobile;
    }
    /**
     * @param string $mobile
     */
    public function setMobile($mobile) {
        $this->mobile = trim($mobile);
    }
    /**
     * @return the $newsletter
     */
    public function getNewsletter() {
        return $this->newsletter;
    }
    /**
     * @param string $newsletter
     */
    public function setNewsletter($newsletter) {
        $this->newsletter = $newsletter;
    }
    /**
     * @return the $birthday
     */
    public function getBirthday() {
        return $this->birthday;
    }
    /**
     * @param string $birthday
     */
    public function setBirthday($birthday) {
        $this->birthday = trim($birthday);
    }
    /**
     * @return the $region
     */
    public function getRegion() {
        return $this->region;
    }
    /**
     * @param string $region
     */
    public function setRegion($region) {
        $this->region = trim($region);
    }
    public function checkUserData() {
        if ($this->ref->ms['MODULES']['DISABLE_CAPTCHA_IN_CREATE_ACCOUNT']=='0') {
            $captcha_code = $this->getCaptcha_code();
            $session = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_multishop_captcha');
            if (!$captcha_code or $session['captcha_code'] != md5($captcha_code)) {
                $erno['err_captcha_code'] = $this->ref->pi_getLL('captcha_code_is_invalid');
            }
            // clear captcha session
            $session_captcha = array();
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_captcha', $session_captcha);
            $GLOBALS['TSFE']->storeSessionData();
        }
        if (!$this->getGender() && $this->ref->ms['MODULES']['GENDER_INPUT_REQUIRED']) {
            $erno['err_gender'] = $this->ref->pi_getLL('please_select_your_salutation');
        }
        if (!$this->getEmail()) {
            $erno['err_email'] = $this->ref->pi_getLL('no_email_address_has_been_specified');
        }
        if (!$this->getAddress()) {
            $erno['err_address'] = $this->ref->pi_getLL('no_address_has_been_specified');
        }
        if (!$this->getAddress_number()) {
            $erno['err_address_number'] = $this->ref->pi_getLL('no_address_number_has_been_specified');
        }
        if (!$this->getFirst_name()) {
            $erno['err_first_name'] = $this->ref->pi_getLL('no_first_name_has_been_specified');
        }
        if (!$this->getLast_name()) {
            $erno['err_last_name'] = $this->ref->pi_getLL('no_last_name_has_been_specified');
        }
        if (!$this->getZip()) {
            $erno['err_postal_code'] = $this->ref->pi_getLL('no_zip_has_been_specified');
        }
        if (!$this->getCity()) {
            $erno['err_city'] = $this->ref->pi_getLL('no_city_has_been_specified');
        }
        if (!$this->getCountry()) {
            $erno['err_country'] = $this->ref->pi_getLL('no_country_has_been_specified');
        }
        if (!$this->getPassword()) {
            $erno['err_password'] = $this->ref->pi_getLL('password_is_required');
        }
        if ($this->getEmail() != $this->getConfirmation_email()) {
            $erno['err_email_repeat'] = $this->ref->pi_getLL('email_is_not_the_same_as_repeated_email');
        }
        if ($this->getPassword() != $this->getConfirmation_password()) {
            $erno['err_password_repeat'] = $this->ref->pi_getLL('password_is_not_the_same_as_repeated_password');
        }
        if ($this->ref->ms['MODULES']['CHECKOUT_REQUIRED_COMPANY'] && !$this->getCompany()) {
            $erno['err_company'] = $this->ref->pi_getLL('company_is_required');
        }
        /*
                $count = count($erno);
                if ($count == 1) {
                    if (empty($erno[0])) {
                        unset($erno[0]);
                    }
                }
        */
        if ($this->getEmail()) {
            $username = $this->getUsername();
            // check if username is not in use
            $checkUsername = mslib_fe::getUser($username, 'username');
            if ($checkUsername['uid']) {
                // if the quick_checkout indicator is 0 this mean the user already registered as full account before
                if (!$checkUsername['tx_multishop_quick_checkout']) {
                    if ($this->getEmail() == $username) {
                        $erno['err_email_exist'] = $this->ref->pi_getLL('specified_email_address_already_in_use');
                    } else {
                        $erno['err_username_exist'] = $this->ref->pi_getLL('specified_username_already_in_use');
                    }
                }
            }
            $checkEmail = mslib_fe::getUser($this->getEmail(), 'email');
            if (!$checkUsername['uid'] && $checkEmail['uid']) {
                if (!$checkEmail['tx_multishop_quick_checkout']) {
                    $erno['err_email_exist'] = $this->ref->pi_getLL('specified_email_address_already_in_use');
                }
            }
        } else {
            $erno['err_email'] = $this->ref->pi_getLL('email_is_required');
        }
        // custom hook that can be controlled by third-party plugin
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['checkUserDataPostProc'])) {
            $params = array(
                    'erno' => &$erno
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['checkUserDataPostProc'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // custom hook that can be controlled by third-party plugin eof
        return $erno;
    }
    /**
     * @return the $captcha_code
     */
    public function getCaptcha_code() {
        return $this->captcha_code;
    }
    /**
     * @param string $captcha_code
     */
    public function setCaptcha_code($captcha_code) {
        $this->captcha_code = trim($captcha_code);
    }
    /**
     * @return the $gender
     */
    public function getGender() {
        return $this->gender;
    }
    /**
     * @param string $gender
     */
    public function setGender($gender) {
        $this->gender = $gender;
    }
    /**
     * @return the $email
     */
    public function getEmail() {
        return $this->email;
    }
    /**
     * @param string $email
     */
    public function setEmail($email) {
        if (strpos($email, '@') === false) {
            $email = '';
        }
        $this->email = trim($email);
    }
    /**
     * @return the $address
     */
    public function getAddress() {
        return $this->address;
    }
    /**
     * @param string $address
     */
    public function setAddress($address) {
        $this->address = trim($address);
    }
    /**
     * @return the $address_number
     */
    public function getAddress_number() {
        return $this->address_number;
    }
    /**
     * @param string $address_number
     */
    public function setAddress_number($address_number) {
        $this->address_number = trim($address_number);
    }
    /**
     * @return the $first_name
     */
    public function getFirst_name() {
        return $this->first_name;
    }
    /**
     * @param string $first_name
     */
    public function setFirst_name($first_name) {
        $this->first_name = trim($first_name);
    }
    /**
     * @return the $last_name
     */
    public function getLast_name() {
        return $this->last_name;
    }
    /**
     * @param string $last_name
     */
    public function setLast_name($last_name) {
        $this->last_name = trim($last_name);
    }
    /**
     * @return the $zip
     */
    public function getZip() {
        return $this->zip;
    }
    /**
     * @param string $zip
     */
    public function setZip($zip) {
        $this->zip = trim($zip);
    }
    /**
     * @return the $city
     */
    public function getCity() {
        return $this->city;
    }
    /**
     * @param string $city
     */
    public function setCity($city) {
        $this->city = trim($city);
    }
    /**
     * @return the $country
     */
    public function getCountry() {
        return $this->country;
    }
    /**
     * @param string $country
     */
    public function setCountry($country) {
        $this->country = trim($country);
    }
    /**
     * @return the $password
     */
    public function getPassword() {
        return $this->password;
    }
    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }
    /**
     * @return the $confirmation_email
     */
    public function getConfirmation_email() {
        return $this->confirmation_email;
    }
    /**
     * @param string $confirmation_email
     */
    public function setConfirmation_email($confirmation_email) {
        if (strpos($confirmation_email, '@') === false) {
            $confirmation_email = '';
        }
        $this->confirmation_email = trim($confirmation_email);
    }
    /**
     * @return the $confirmation_password
     */
    public function getConfirmation_password() {
        return $this->confirmation_password;
    }
    /**
     * @param string $confirmation_password
     */
    public function setConfirmation_password($confirmation_password) {
        $this->confirmation_password = $confirmation_password;
    }
    /**
     * @return the $company
     */
    public function getCompany() {
        return $this->company;
    }
    /**
     * @param string $company
     */
    public function setCompany($company) {
        $this->company = trim($company);
    }
    /**
     * @return the $username
     */
    public function getUsername() {
        return $this->username;
    }
    /**
     * @param string $username
     */
    public function setUsername($username) {
        $this->username = trim($username);
    }
    function saveUserData() {
        $checkEmail = mslib_fe::getUser($this->getEmail(), 'email');
        // if quick_checkout == 1 then update the fe_users
        if ($checkEmail['tx_multishop_quick_checkout']) {
            $customer_id = $checkEmail['uid'];
            // add the user
            $updateArray = array();
            if ($this->username) {
                $updateArray['username'] = $this->username;
            } else {
                $updateArray['username'] = $this->email;
            }
            $updateArray['email'] = $this->email;
            // fe user table holds integer as value: 0 is male, 1 is female
            // but in tt_address its varchar: m is male, f is female
            switch ($this->gender) {
                case '0':
                case 'm':
                    // male
                    $updateArray['gender'] = '0';
                    break;
                case '1':
                case 'f':
                    // female
                    $updateArray['gender'] = '1';
                    break;
                case '2':
                case 'c':
                    // couple
                    $updateArray['gender'] = '2';
                    break;
            }
            $updateArray['password'] = mslib_befe::getHashedPassword($this->password);
            $updateArray['first_name'] = $this->first_name;
            $updateArray['middle_name'] = $this->middle_name;
            $updateArray['last_name'] = $this->last_name;
            $updateArray['name'] = $this->name;
            $updateArray['company'] = $this->company;
            $updateArray['country'] = $this->country;
            $updateArray['street_name'] = $this->address;
            $updateArray['address_number'] = $this->address_number;
            $updateArray['address_ext'] = $this->address_ext;
            $updateArray['address'] = $updateArray['street_name'] . ' ' . $updateArray['address_number'];
            if ($updateArray['address_ext']) {
                $updateArray['address'] .= '-' . $updateArray['address_ext'];
            }
            $updateArray['address'] = preg_replace('/\s+/', ' ', $updateArray['address']);
            $updateArray['zip'] = $this->zip;
            $updateArray['city'] = $this->city;
            $updateArray['telephone'] = $this->telephone;
            $updateArray['mobile'] = $this->mobile;
            if (isset($this->newsletter) && !empty($this->newsletter)) {
                $updateArray['tx_multishop_newsletter'] = $this->newsletter;
            } else {
                $updateArray['tx_multishop_newsletter'] = '';
            }
            $updateArray['disable'] = 1;
            $updateArray['tstamp'] = time();
            $updateArray['usergroup'] = $this->ref->conf['fe_customer_usergroup'];
            $updateArray['pid'] = $this->ref->conf['fe_customer_pid'];
            $updateArray['tx_multishop_code'] = md5(uniqid('', true));
            $updateArray['crdate'] = time();
            $updateArray['page_uid'] = $this->ref->shop_pid;
            $updateArray['tx_multishop_quick_checkout'] = 0;
            if (isset($this->ref->cookie['HTTP_REFERER']) && !empty($this->ref->cookie['HTTP_REFERER'])) {
                $updateArray['http_referer'] = $this->ref->cookie['HTTP_REFERER'];
            } else {
                $updateArray['http_referer'] = '';
            }
            $updateArray['ip_address'] = $this->ref->REMOTE_ADDR;
            if (is_array($this->customFields) and count($this->customFields)) {
                foreach ($this->customFields as $key => $val) {
                    $updateArray[$key] = $val;
                }
            }
            //hook to let other plugins further manipulate the create table query
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['updateUserPreProc'])) {
                $params = array(
                        'updateArray' => &$updateArray,
                        'customer_id' => &$customer_id
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['updateUserPreProc'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('fe_users', 'uid=\'' . $customer_id . '\'', $updateArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            if ($customer_id) {
                // ADD CUSTOMER_ID TO THE CART CONTENTS
                if ($customer_id) {
                    $updateArray = array();
                    $updateArray['customer_id'] = $customer_id;
                    $updateArray['page_uid'] = $this->ref->shop_pid;
                    $str = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_cart_contents', 'session_id=\'' . $customer_id . '\'', $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($str);
                }
                //hook to let other plugins further manipulate the create table query
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['updateUserPostProc'])) {
                    $params = array(
                            'customer_id' => &$customer_id
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['updateUserPostProc'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                return $customer_id;
            }
        } else {
            // add the user
            $insertArray = array();
            if ($this->username) {
                $insertArray['username'] = $this->username;
            } else {
                $insertArray['username'] = $this->email;
            }
            $insertArray['email'] = $this->email;
            // fe user table holds integer as value: 0 is male, 1 is female
            // but in tt_address its varchar: m is male, f is female
            switch ($this->gender) {
                case '0':
                case 'm':
                    // male
                    $insertArray['gender'] = '0';
                    break;
                case '1':
                case 'f':
                    // female
                    $insertArray['gender'] = '1';
                    break;
                case '2':
                case 'c':
                    // couple
                    $insertArray['gender'] = '2';
                    break;
            }
            $insertArray['password'] = mslib_befe::getHashedPassword($this->password);
            $insertArray['first_name'] = $this->first_name;
            $insertArray['middle_name'] = $this->middle_name;
            $insertArray['last_name'] = $this->last_name;
            $insertArray['name'] = $this->name;
            $insertArray['company'] = $this->company;
            $insertArray['country'] = $this->country;
            $insertArray['street_name'] = $this->address;
            $insertArray['address_number'] = $this->address_number;
            $insertArray['address_ext'] = $this->address_ext;
            $insertArray['address'] = $insertArray['street_name'] . ' ' . $insertArray['address_number'];
            if ($insertArray['address_ext']) {
                $insertArray['address'] .= '-' . $insertArray['address_ext'];
            }
            $insertArray['address'] = preg_replace('/\s+/', ' ', $insertArray['address']);
            $insertArray['zip'] = $this->zip;
            $insertArray['city'] = $this->city;
            $insertArray['telephone'] = $this->telephone;
            $insertArray['mobile'] = $this->mobile;
            if (isset($this->newsletter) && !empty($this->newsletter)) {
                $insertArray['tx_multishop_newsletter'] = $this->newsletter;
            } else {
                $insertArray['tx_multishop_newsletter'] = '';
            }
            $insertArray['disable'] = 1;
            $insertArray['tstamp'] = time();
            $insertArray['usergroup'] = $this->ref->conf['fe_customer_usergroup'];
            $insertArray['pid'] = $this->ref->conf['fe_customer_pid'];
            $insertArray['tx_multishop_code'] = md5(uniqid('', true));
            $insertArray['crdate'] = time();
            $insertArray['page_uid'] = $this->ref->shop_pid;
            if (isset($this->ref->cookie['HTTP_REFERER']) && !empty($this->ref->cookie['HTTP_REFERER'])) {
                $insertArray['http_referer'] = $this->ref->cookie['HTTP_REFERER'];
            } else {
                $insertArray['http_referer'] = '';
            }
            $insertArray['ip_address'] = $this->ref->REMOTE_ADDR;
            if (is_array($this->customFields) and count($this->customFields)) {
                foreach ($this->customFields as $key => $val) {
                    $insertArray[$key] = $val;
                }
            }
            // TYPO3 6.2 is null bugfix
            foreach ($insertArray as $key => $val) {
                if (is_null($insertArray[$key])) {
                    $insertArray[$key] = '';
                }
            }
            //hook to let other plugins further manipulate the create table query
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['createUserPreProc'])) {
                $params = array(
                        'insertArray' => &$insertArray
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['createUserPreProc'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            $query = $GLOBALS['TYPO3_DB']->INSERTquery('fe_users', $insertArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            if (!$res) {
                $error = $GLOBALS['TYPO3_DB']->sql_error();
            } else {
                $customer_id = $GLOBALS['TYPO3_DB']->sql_insert_id();
                // ADD CUSTOMER_ID TO THE CART CONTENTS
                if ($customer_id && $GLOBALS['TSFE']->fe_user->id) {
                    $updateArray = array();
                    $updateArray['customer_id'] = $customer_id;
                    $updateArray['page_uid'] = $this->ref->shop_pid;
                    $str = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_cart_contents', 'session_id=\'' . $GLOBALS['TSFE']->fe_user->id . '\'', $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($str);
                }
                //hook to let other plugins further manipulate the create table query
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['createUserPostProc'])) {
                    $params = array(
                            'customer_id' => &$customer_id
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['createUserPostProc'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                return $customer_id;
            }
        }
        return false;
    }
    function saveUserBillingAddress($customer_id, $is_default = true) {
        if (mslib_fe::getFeUserTTaddressDetails($customer_id, 'billing')) {
            // insert billing into tt_address
            $updateArray = array();
            $updateArray['tstamp'] = time();
            $updateArray['company'] = $this->company;
            $updateArray['name'] = $this->name;
            $updateArray['first_name'] = $this->first_name;
            $updateArray['middle_name'] = $this->middle_name;
            $updateArray['last_name'] = $this->last_name;
            $updateArray['email'] = $this->email;
            $updateArray['street_name'] = $this->address;
            $updateArray['address_number'] = $this->address_number;
            $updateArray['address_ext'] = $this->address_ext;
            $updateArray['address'] = $updateArray['street_name'] . ' ' . $updateArray['address_number'];
            if ($updateArray['address_ext']) {
                $updateArray['address'] .= '-' . $updateArray['address_ext'];
            }
            $updateArray['zip'] = $this->zip;
            $updateArray['phone'] = $this->telephone;
            $updateArray['mobile'] = $this->mobile;
            $updateArray['city'] = $this->city;
            $updateArray['country'] = $this->country;
            // fe user table holds integer as value: 0 is male, 1 is female
            // but in tt_address its varchar: m is male, f is female
            switch ($this->gender) {
                case '0':
                case 'm':
                    $updateArray['gender'] = 'm';
                    break;
                case '1':
                case 'f':
                    $updateArray['gender'] = 'f';
                    break;
                case '2':
                case 'c':
                    $updateArray['gender'] = 'c';
                    break;
            }
            $updateArray['birthday'] = strtotime($this->birthday);
            $updateArray['title'] = (($this->gender == 'm') ? 'Mr.' : 'Mrs.');
            $updateArray['region'] = $this->region;
            $updateArray['pid'] = $this->ref->conf['fe_customer_pid'];
            $updateArray['page_uid'] = $this->ref->shop_pid;
            $updateArray['tstamp'] = time();
            $updateArray['tx_multishop_address_type'] = 'billing';
            $updateArray['tx_multishop_default'] = ($is_default) ? 1 : 0;
            $updateArray['tx_multishop_customer_id'] = $customer_id;
            $updateArray = mslib_befe::rmNullValuedKeys($updateArray);
            //hook to let other plugins further manipulate the create table query
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['saveUserBillingAddressUpdatePreHook'])) {
                $params = array(
                        'updateArray' => &$updateArray
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['saveUserBillingAddressUpdatePreHook'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tt_address', 'tx_multishop_customer_id=' . $customer_id, $updateArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            if ($res) {
                return true;
            } else {
                //echo $GLOBALS['TYPO3_DB']->sql_error();
                //die();
            }
        } else {
            // insert billing into tt_address
            $insertArray = array();
            $insertArray['tstamp'] = time();
            $insertArray['company'] = $this->company;
            $insertArray['name'] = $this->name;
            $insertArray['first_name'] = $this->first_name;
            $insertArray['middle_name'] = $this->middle_name;
            $insertArray['last_name'] = $this->last_name;
            $insertArray['email'] = $this->email;
            $insertArray['street_name'] = $this->address;
            $insertArray['address_number'] = $this->address_number;
            $insertArray['address_ext'] = $this->address_ext;
            $insertArray['address'] = $insertArray['street_name'] . ' ' . $insertArray['address_number'];
            if ($insertArray['address_ext']) {
                $insertArray['address'] .= '-' . $insertArray['address_ext'];
            }
            $insertArray['zip'] = $this->zip;
            $insertArray['phone'] = $this->telephone;
            $insertArray['mobile'] = $this->mobile;
            $insertArray['city'] = $this->city;
            $insertArray['country'] = $this->country;
            // fe user table holds integer as value: 0 is male, 1 is female
            // but in tt_address its varchar: m is male, f is female
            switch ($this->gender) {
                case '0':
                case 'm':
                    $insertArray['gender'] = 'm';
                    break;
                case '1':
                case 'f':
                    $insertArray['gender'] = 'f';
                    break;
                case '2':
                case 'c':
                    $insertArray['gender'] = 'c';
                    break;
            }
            $insertArray['birthday'] = strtotime($this->birthday);
            $insertArray['title'] = (($this->gender == 'm') ? 'Mr.' : 'Mrs.');
            $insertArray['region'] = $this->region;
            $insertArray['pid'] = $this->ref->conf['fe_customer_pid'];
            $insertArray['page_uid'] = $this->ref->shop_pid;
            $insertArray['tstamp'] = time();
            $insertArray['tx_multishop_address_type'] = 'billing';
            $insertArray['tx_multishop_default'] = ($is_default) ? 1 : 0;
            $insertArray['tx_multishop_customer_id'] = $customer_id;
            $insertArray = mslib_befe::rmNullValuedKeys($insertArray);
            //hook to let other plugins further manipulate the create table query
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['saveUserBillingAddressSavingPreHook'])) {
                $params = array(
                        'insertArray' => &$insertArray
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_user.php']['saveUserBillingAddressSavingPreHook'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            $query = $GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            if ($res) {
                return true;
            } else {
                echo $GLOBALS['TYPO3_DB']->sql_error();
                die();
            }
        }
        return false;
    }
    function saveUserDeliveryAddress($customer_id, $is_default = false) {
        // insert billing into tt_address
        $insertArray = array();
        $insertArray['tstamp'] = time();
        $insertArray['company'] = $this->company;
        $insertArray['name'] = $this->name;
        $insertArray['first_name'] = $this->first_name;
        $insertArray['middle_name'] = $this->middle_name;
        $insertArray['last_name'] = $this->last_name;
        $insertArray['email'] = $this->email;
        $insertArray['street_name'] = $this->address;
        $insertArray['address_number'] = $this->address_number;
        $insertArray['address_ext'] = $this->address_ext;
        $insertArray['address'] = $insertArray['street_name'] . ' ' . $insertArray['address_number'];
        if ($insertArray['address_ext']) {
            $insertArray['address'] .= '-' . $insertArray['address_ext'];
        }
        $insertArray['address_number'] = $this->address_number;
        $insertArray['zip'] = $this->zip;
        $insertArray['phone'] = $this->telephone;
        $insertArray['mobile'] = $this->mobile;
        $insertArray['city'] = $this->city;
        $insertArray['country'] = $this->country;
        // fe user table holds integer as value: 0 is male, 1 is female
        // but in tt_address its varchar: m is male, f is female
        switch ($this->gender) {
            case '0':
            case 'm':
                $insertArray['gender'] = 'm';
                break;
            case '1':
            case 'f':
                $insertArray['gender'] = 'f';
                break;
            case '2':
            case 'c':
                $insertArray['gender'] = 'c';
                break;
        }
        $insertArray['birthday'] = strtotime($this->birthday);
        $insertArray['title'] = (($this->gender == 'm') ? 'Mr.' : 'Mrs.');
        $insertArray['region'] = $this->region;
        $insertArray['pid'] = $this->ref->conf['fe_customer_pid'];
        $insertArray['page_uid'] = $this->ref->shop_pid;
        $insertArray['tstamp'] = time();
        $insertArray['tx_multishop_address_type'] = 'delivery';
        $insertArray['tx_multishop_default'] = ($is_default) ? 1 : 0;
        $insertArray['tx_multishop_customer_id'] = $customer_id;
        $query = $GLOBALS['TYPO3_DB']->INSERTquery('tt_address', $insertArray);
        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
        if ($res) {
            return true;
        }
        return false;
    }
    function setCustomField($name, $val) {
        $this->customFields[$name] = trim($val);
    }
}
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/multishop/pi1/classes/class.tx_mslib_user.php"]) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/multishop/pi1/classes/class.tx_mslib_user.php"]);
}
?>