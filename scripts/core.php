<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
if ($this->ADMIN_USER) {
    // if the user is logged in and has admin rights lets check if the shop is fully configured
    $content .= mslib_fe::giveSiteConfigurationNotice();
}
if ($this->conf['page_section']) {
    $this->ms['page'] = $this->conf['page_section'];
} else {
    $this->ms['page'] = $this->get['tx_multishop_pi1']['page_section'];
}
// more items could be added through hook
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/core.php']['corePreProc'])) {
    $params = array();
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/core.php']['corePreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
switch ($this->ms['page']) {
    case 'payment_page':
        if ($this->get['tx_multishop_pi1']['hash']) {
            // display payment button for order
            $order = mslib_fe::getOrder($this->get['tx_multishop_pi1']['hash'], 'hash');
            if ($order['orders_id'] and !$order['paid']) {
                if ($order['payment_method']) {
                    $content .= '<h2 class="pay_order_heading">Pay order ' . $order['orders_id'] . '</h2>';
                    // load optional payment button
                    $mslib_payment = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('mslib_payment');
                    $mslib_payment->init($this);
                    $paymentMethods = $mslib_payment->getEnabledPaymentMethods();
                    if (is_array($paymentMethods)) {
                        foreach ($paymentMethods as $user_method) {
                            if ($user_method['code'] == $order['payment_method']) {
                                if ($user_method['vars'] and $user_method['provider']) {
                                    $vars = unserialize($user_method['vars']);
                                    if ($mslib_payment->setPaymentMethod($user_method['provider'])) {
                                        $extkey = 'multishop_' . $user_method['provider'];
                                        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extkey)) {
                                            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extkey) . 'class.multishop_payment_method.php');
                                            $paymentMethod = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_multishop_payment_method');
                                            $paymentMethod->setPaymentMethod($user_method['provider']);
                                            $paymentMethod->setVariables($vars);
                                            $content .= $paymentMethod->displayPaymentButton($order['orders_id'], $this);
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($order['paid']) {
                // order has been paid, so dont load the psp
                $content .= 'Thank you!<br />Order ' . $order['orders_id'] . ' has been successfully paid.';
            }
        }
        break;
    case 'info':
        // cms information pages
        $output_array = array();
        if ($this->get['tx_multishop_pi1']['cms_hash']) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/info.php');
        }
        break;
    case 'ultrasearch':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/ultrasearch.php');
        break;
    case 'checkout':
        // more items could be added through hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['checkoutPreProc'])) {
            $params = array();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/class.tx_multishop_pi1.php']['checkoutPreProc'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        if ($this->ms['MODULES']['FORCE_CHECKOUT_SHOW_PRICES_INCLUDING_VAT']) {
            $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] = 1;
        }
        if (strstr($this->ms['MODULES']['CHECKOUT_TYPE'], "..")) {
            die('error in CHECKOUT_TYPE value');
        } else {
            if (strstr($this->ms['MODULES']['CHECKOUT_TYPE'], "/")) {
                // relative mode
                require($this->DOCUMENT_ROOT . $this->ms['MODULES']['CHECKOUT_TYPE'] . '/checkout.php');
            } else {
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/includes/checkout/' . $this->ms['MODULES']['CHECKOUT_TYPE'] . '/checkout.php');
            }
        }
        break;
    case 'admin_price_update_dl_xls':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/price_update/mass_price_update_xls_export.php');
        }
        break;
    case 'admin_price_update_up_xls':
        if ($this->ADMIN_USER) {
            if (isset($this->post['Submit'])) {
                $dest = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . $_FILES['datafile']['name'];
                if (move_uploaded_file($_FILES['datafile']['tmp_name'], $dest)) {
                    $filename = $_FILES['datafile']['name'];
                } else {
                    $filename = '';
                }
            }
            if (!empty($filename)) {
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/price_update/mass_price_update_xls_import.php');
            }
        }
        break;
    case 'admin_orders_stats_dl_xls':
        if ($this->ADMIN_USER) {
            $paths = array();
            $paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('phpexcel_service') . 'Classes/Service/PHPExcel.php';
            $paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('phpexcel_service') . 'Classes/PHPExcel.php';
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    break;
                }
            }
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_stats_orders/orders_stats_xls_export.php');
        }
        break;
    case 'shopping_cart':
        if (strstr($this->ms['MODULES']['SHOPPING_CART_TYPE'], "..")) {
            die('error in SHOPPING_CART_TYPE value');
        } else {
            if (strstr($this->ms['MODULES']['SHOPPING_CART_TYPE'], "/")) {
                // relative mode
                require($this->DOCUMENT_ROOT . $this->ms['MODULES']['SHOPPING_CART_TYPE'] . '.php');
            } else {
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/includes/shopping_cart/default.php');
            }
        }
        break;
    case 'products_detail':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/products_detail.php');
        break;
    case 'products_search':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/products_search.php');
        break;
    case 'products_listing':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/products_listing.php');
        break;
    case 'manufacturers_products_listing':
        if (strstr($this->ms['MODULES']['MANUFACTURERS_PRODUCTS_LISTING_TYPE'], "..")) {
            die('error in MANUFACTURERS_PRODUCTS_LISTING_TYPE value');
        } else {
            if (strstr($this->ms['MODULES']['MANUFACTURERS_PRODUCTS_LISTING_TYPE'], "/")) {
                // relative mode
                require($this->DOCUMENT_ROOT . $this->ms['MODULES']['MANUFACTURERS_PRODUCTS_LISTING_TYPE'] . '.php');
            } else {
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/manufacturers_products_listing.php');
            }
        }
        break;
    case 'manufacturers':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/manufacturers.php');
        break;
    case 'psp_accepturl':
    case 'psp_pendingurl':
    case 'psp_declineurl':
    case 'psp_exceptionurl':
    case 'psp_cancelurl':
        $cmsPage = $this->ms['page'];
        $array1 = array();
        $array2 = array();
        $order_session = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_multishop_order');
        if ($order_session['orders_id']) {
            $order = mslib_fe::getOrder($order_session['orders_id']);
            $orders_id = $order['orders_id'];
            // replacing the variables with dynamic values
            $billing_address = '';
            $delivery_address = '';
            $full_customer_name = $order['billing_first_name'];
            if ($order['billing_middle_name']) {
                $full_customer_name .= ' ' . $order['billing_middle_name'];
            }
            if ($order['billing_last_name']) {
                $full_customer_name .= ' ' . $order['billing_last_name'];
            }
            $delivery_full_customer_name = $order['delivery_first_name'];
            if ($order['delivery_middle_name']) {
                $delivery_full_customer_name .= ' ' . $order['delivery_middle_name'];
            }
            if ($order['delivery_last_name']) {
                $delivery_full_customer_name .= ' ' . $order['delivery_last_name'];
            }
            $full_customer_name = preg_replace('/\s+/', ' ', $full_customer_name);
            $delivery_full_customer_name = preg_replace('/\s+/', ' ', $delivery_full_customer_name);
            if ($order['delivery_company']) {
                $delivery_address = $order['delivery_company'] . "<br />";
            }
            if ($delivery_full_customer_name) {
                $delivery_address .= $delivery_full_customer_name . "<br />";
            }
            if ($order['delivery_building']) {
                $delivery_address .= $order['delivery_building'] . "<br />";
            }
            if ($order['delivery_address']) {
                $delivery_address .= $order['delivery_address'] . "<br />";
            }
            if ($order['delivery_zip'] and $order['delivery_city']) {
                $delivery_address .= $order['delivery_zip'] . " " . $order['delivery_city'];
            }
            if ($order['delivery_country'] && mslib_befe::strtolower($order['delivery_country']) != mslib_befe::strtolower($this->tta_shop_info['country'])) {
                // ONLY PRINT COUNTRY IF THE COUNTRY OF THE CUSTOMER IS DIFFERENT THAN FROM THE SHOP
                $delivery_address .= '<br />' . ucfirst(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order['delivery_country']));
            }
            if ($order['billing_company']) {
                $billing_address = $order['billing_company'] . "<br />";
            }
            if ($full_customer_name) {
                $billing_address .= $full_customer_name . "<br />";
            }
            if ($order['billing_building']) {
                $billing_address .= $order['billing_building'] . "<br />";
            }
            if ($order['billing_address']) {
                $billing_address .= $order['billing_address'] . "<br />";
            }
            if ($order['billing_zip'] and $order['billing_city']) {
                $billing_address .= $order['billing_zip'] . " " . $order['billing_city'];
            }
            if ($order['billing_country'] && mslib_befe::strtolower($order['billing_country']) != mslib_befe::strtolower($this->tta_shop_info['country'])) {
                // ONLY PRINT COUNTRY IF THE COUNTRY OF THE CUSTOMER IS DIFFERENT THAN FROM THE SHOP
                $billing_address .= '<br />' . mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order['billing_country']);
            }
            $array1[] = '###GENDER_SALUTATION###';
            $array2[] = mslib_fe::genderSalutation($order['billing_gender']);
            $array1[] = '###DELIVERY_FIRST_NAME###';
            $array2[] = $order['delivery_first_name'];
            $array1[] = '###DELIVERY_LAST_NAME###';
            $array2[] = preg_replace('/\s+/', ' ', $order['delivery_middle_name'] . ' ' . $order['delivery_last_name']);
            $array1[] = '###BILLING_FIRST_NAME###';
            $array2[] = $order['billing_first_name'];
            $array1[] = '###BILLING_LAST_NAME###';
            $array2[] = preg_replace('/\s+/', ' ', $order['billing_middle_name'] . ' ' . $order['billing_last_name']);
            $array1[] = '###BILLING_TELEPHONE###';
            $array2[] = $order['billing_telephone'];
            $array1[] = '###DELIVERY_TELEPHONE###';
            $array2[] = $order['delivery_telephone'];
            $array1[] = '###BILLING_MOBILE###';
            $array2[] = $order['billing_mobile'];
            $array1[] = '###DELIVERY_MOBILE###';
            $array2[] = $order['delivery_mobile'];
            $array1[] = '###FULL_NAME###';
            $array2[] = $full_customer_name;
            $array1[] = '###BILLING_FULL_NAME###';
            $array2[] = $full_customer_name;
            $array1[] = '###DELIVERY_FULL_NAME###';
            $array2[] = $delivery_full_customer_name;
            $array1[] = '###BILLING_NAME###';
            $array2[] = $order['billing_name'];
            $array1[] = '###BILLING_EMAIL###';
            $array2[] = $order['billing_email'];
            $array1[] = '###DELIVERY_EMAIL###';
            $array2[] = $order['delivery_email'];
            $array1[] = '###DELIVERY_NAME###';
            $array2[] = $order['delivery_name'];
            $array1[] = '###CUSTOMER_EMAIL###';
            $array2[] = $order['billing_email'];
            $array1[] = '###STORE_NAME###';
            $array2[] = $this->ms['MODULES']['STORE_NAME'];
            $array1[] = '###TOTAL_AMOUNT###';
            $array2[] = mslib_fe::amount2Cents($order['total_amount']);
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_order.php');
            $mslib_order = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_order');
            $mslib_order->init($this);
            $ORDER_DETAILS = $mslib_order->printOrderDetailsTable($order, 'site');
            $array1[] = '###ORDER_DETAILS###';
            $array2[] = $ORDER_DETAILS;
            $array1[] = '###BILLING_ADDRESS###';
            $array2[] = $billing_address;
            $array1[] = '###DELIVERY_ADDRESS###';
            $array2[] = $delivery_address;
            $array1[] = '###CUSTOMER_ID###';
            $array2[] = $order['customer_id'];
            $array1[] = '###CUSTOMER_NUMBER###';
            $array2[] = $order['customer_id'];
            $array1[] = '###SHIPPING_METHOD###';
            $array2[] = $order['shipping_method_label'];
            $array1[] = '###PAYMENT_METHOD###';
            $array2[] = $order['payment_method_label'];
            $array1[] = '###ORDERS_ID###';
            $array2[] = $order['orders_id'];
            $invoice = mslib_fe::getOrderInvoice($order['orders_id'], 0);
            $invoice_id = '';
            $invoice_link = '';
            if (is_array($invoice)) {
                $invoice_id = $invoice['invoice_id'];
                $invoice_link = '<a href="' . $this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=download_invoice&tx_multishop_pi1[hash]=' . $invoice['hash']) . '">' . $invoice['invoice_id'] . '</a>';
            }
            $array1[] = '###INVOICE_NUMBER###';
            $array2[] = $invoice_id;
            $array1[] = '###INVOICE_LINK###';
            $array2[] = $invoice_link;
            // backwards compatibility
            $time = $order['crdate'];
            $long_date = strftime($this->pi_getLL('full_date_format'), $time);
            $array1[] = '###ORDER_DATE_LONG###'; // ie woensdag 23 juni, 2010
            $array2[] = $long_date;
            $array1[] = '###ORDER_DATE###'; // 21-12-2010 in localized format
            $array2[] = strftime("%x", $time);
            $array1[] = '###LONG_DATE###'; // ie woensdag 23 juni, 2010
            $array2[] = $long_date;
            $time = time();
            $long_date = strftime($this->pi_getLL('full_date_format'), $time);
            $array1[] = '###CURRENT_DATE_LONG###'; // ie woensdag 23 juni, 2010
            $array2[] = $long_date;
            $array1[] = '###STORE_NAME###';
            $array2[] = $this->ms['MODULES']['STORE_NAME'];
            $array1[] = '###STORE_EMAIL###';
            $array2[] = $this->ms['MODULES']['STORE_EMAIL'];
            $array1[] = '###TOTAL_AMOUNT###';
            $array2[] = mslib_fe::amount2Cents($order['total_amount']);
            $array1[] = '###PROPOSAL_NUMBER###';
            $array2[] = $order['orders_id'];
            $array1[] = '###ORDER_NUMBER###';
            $array2[] = $order['orders_id'];
            $array1[] = '###ORDER_LINK###';
            $array2[] = '';
            $array1[] = '###CUSTOMER_ID###';
            $array2[] = $order['customer_id'];
            $array1[] = '###CUSTOMER_COMMENTS###';
            $array2[] = $order['customer_comments'];
            //hook to let other plugins further manipulate
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/core.php']['paymentURLCMSPage'])) {
                $params = array(
                        'array1' => &$array1,
                        'array2' => &$array2,
                        'order' => &$order,
                        'cmsPage' => &$cmsPage
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/core.php']['paymentURLCMSPage'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
        }
        $page = mslib_fe::getCMScontent($cmsPage, $GLOBALS['TSFE']->sys_language_uid);
        if ($page[0]['name']) {
            if (count($array1) && count($array2)) {
                $page[0]['name'] = str_replace($array1, $array2, $page[0]['name']);
            }
            $header_label = $page[0]['name'];
        } else {
            $header_label = 'Payment';
        }
        $content .= '<div class="main-heading"><h2>' . $header_label . '</h2></div>';
        if ($page[0]['content']) {
            if (count($array1) && count($array2)) {
                $page[0]['content'] = str_replace($array1, $array2, $page[0]['content']);
            }
            $content .= $page[0]['content'];
        } else {
            // show standard thank you
            if ($this->ms['page'] == 'psp_accepturl') {
                $content .= $this->pi_getLL('your_payment_has_been_completed');
            } else if ($this->ms['page'] == 'psp_pendingurl') {
                $content .= $this->pi_getLL('your_payment_is_pending');
            } else if ($this->ms['page'] == 'psp_declineurl') {
                $content .= $this->pi_getLL('your_payment_is_declined');
            } else if ($this->ms['page'] == 'psp_exceptionurl') {
                $content .= $this->pi_getLL('your_payment_is_failed_');
            } else if ($this->ms['page'] == 'psp_cancelurl') {
                $content .= $this->pi_getLL('your_payment_has_been_cancelled');
            } else {
                $content .= $this->pi_getLL('your_payment_has_not_been_completed');
            }
        }
        // custom hook that can be controlled by third-party plugin
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/core.php']['paymentFallback'])) {
            $params = array(
                    'page' => $this->ms['page'],
                    'content' => &$content,
                    'order_session' => &$order_session
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/core.php']['paymentFallback'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // make sure the payment fallback marker are replaced with empty string if the plugin not installed
        $array1 = array();
        $array2 = array();
        $array1[] = '###PAYMENT_FALLBACK_LINK###';
        $array2[] = '';
        $array1[] = '###PAYMENT_PAGE_LINK###';
        $array2[] = '';
        $content = str_replace($array1, $array2, $content);
        // custom hook that can be controlled by third-party plugin eof
        break;
    // psp thank you or error pages eof
    case 'payment_reminder_checkout':
        if ($this->get['tx_multishop_pi1']['hash']) {
            $tmpArray = mslib_befe::getRecord($this->get['tx_multishop_pi1']['hash'], 'tx_multishop_orders', 'hash');
            if ($tmpArray['orders_id']) {
                $order = mslib_fe::getOrder($tmpArray['orders_id']);
                // replacing the variables with dynamic values
                $billing_address = '';
                $delivery_address = '';
                $full_customer_name = $order['billing_first_name'];
                if ($order['billing_middle_name']) {
                    $full_customer_name .= ' ' . $order['billing_middle_name'];
                }
                if ($order['billing_last_name']) {
                    $full_customer_name .= ' ' . $order['billing_last_name'];
                }
                $delivery_full_customer_name = $order['delivery_first_name'];
                if ($order['delivery_middle_name']) {
                    $delivery_full_customer_name .= ' ' . $order['delivery_middle_name'];
                }
                if ($order['delivery_last_name']) {
                    $delivery_full_customer_name .= ' ' . $order['delivery_last_name'];
                }
                $full_customer_name = preg_replace('/\s+/', ' ', $full_customer_name);
                $delivery_full_customer_name = preg_replace('/\s+/', ' ', $delivery_full_customer_name);
                if ($order['delivery_company']) {
                    $delivery_address = $order['delivery_company'] . "<br />";
                }
                if ($delivery_full_customer_name) {
                    $delivery_address .= $delivery_full_customer_name . "<br />";
                }
                if ($order['delivery_building']) {
                    $delivery_address .= $order['delivery_building'] . "<br />";
                }
                if ($order['delivery_address']) {
                    $delivery_address .= $order['delivery_address'] . "<br />";
                }
                if ($order['delivery_zip'] and $order['delivery_city']) {
                    $delivery_address .= $order['delivery_zip'] . " " . $order['delivery_city'];
                }
                if ($order['delivery_country'] && mslib_befe::strtolower($order['delivery_country']) != mslib_befe::strtolower($this->tta_shop_info['country'])) {
                    // ONLY PRINT COUNTRY IF THE COUNTRY OF THE CUSTOMER IS DIFFERENT THAN FROM THE SHOP
                    $delivery_address .= '<br />' . ucfirst(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order['delivery_country']));
                }
                if ($order['billing_company']) {
                    $billing_address = $order['billing_company'] . "<br />";
                }
                if ($full_customer_name) {
                    $billing_address .= $full_customer_name . "<br />";
                }
                if ($order['billing_building']) {
                    $billing_address .= $order['billing_building'] . "<br />";
                }
                if ($order['billing_address']) {
                    $billing_address .= $order['billing_address'] . "<br />";
                }
                if ($order['billing_zip'] and $order['billing_city']) {
                    $billing_address .= $order['billing_zip'] . " " . $order['billing_city'];
                }
                if ($order['billing_country'] && mslib_befe::strtolower($order['billing_country']) != mslib_befe::strtolower($this->tta_shop_info['country'])) {
                    // ONLY PRINT COUNTRY IF THE COUNTRY OF THE CUSTOMER IS DIFFERENT THAN FROM THE SHOP
                    $billing_address .= '<br />' . mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order['billing_country']);
                }
                $array1 = array();
                $array2 = array();
                $array1[] = '###GENDER_SALUTATION###';
                $array2[] = mslib_fe::genderSalutation($order['billing_gender']);
                $array1[] = '###DELIVERY_FIRST_NAME###';
                $array2[] = $order['delivery_first_name'];
                $array1[] = '###DELIVERY_LAST_NAME###';
                $array2[] = preg_replace('/\s+/', ' ', $order['delivery_middle_name'] . ' ' . $order['delivery_last_name']);
                $array1[] = '###BILLING_FIRST_NAME###';
                $array2[] = $order['billing_first_name'];
                $array1[] = '###BILLING_LAST_NAME###';
                $array2[] = preg_replace('/\s+/', ' ', $order['billing_middle_name'] . ' ' . $order['billing_last_name']);
                $array1[] = '###BILLING_TELEPHONE###';
                $array2[] = $order['billing_telephone'];
                $array1[] = '###DELIVERY_TELEPHONE###';
                $array2[] = $order['delivery_telephone'];
                $array1[] = '###BILLING_MOBILE###';
                $array2[] = $order['billing_mobile'];
                $array1[] = '###DELIVERY_MOBILE###';
                $array2[] = $order['delivery_mobile'];
                $array1[] = '###FULL_NAME###';
                $array2[] = $full_customer_name;
                $array1[] = '###BILLING_FULL_NAME###';
                $array2[] = $full_customer_name;
                $array1[] = '###DELIVERY_FULL_NAME###';
                $array2[] = $delivery_full_customer_name;
                $array1[] = '###BILLING_NAME###';
                $array2[] = $order['billing_name'];
                $array1[] = '###BILLING_EMAIL###';
                $array2[] = $order['billing_email'];
                $array1[] = '###DELIVERY_EMAIL###';
                $array2[] = $order['delivery_email'];
                $array1[] = '###DELIVERY_NAME###';
                $array2[] = $order['delivery_name'];
                $array1[] = '###CUSTOMER_EMAIL###';
                $array2[] = $order['billing_email'];
                $array1[] = '###STORE_NAME###';
                $array2[] = $this->ms['MODULES']['STORE_NAME'];
                $array1[] = '###TOTAL_AMOUNT###';
                $array2[] = mslib_fe::amount2Cents($order['total_amount']);
                require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_order.php');
                $mslib_order = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_order');
                $mslib_order->init($this);
                $ORDER_DETAILS = $mslib_order->printOrderDetailsTable($order, 'site');
                $array1[] = '###ORDER_DETAILS###';
                $array2[] = $ORDER_DETAILS;
                $array1[] = '###BILLING_ADDRESS###';
                $array2[] = $billing_address;
                $array1[] = '###DELIVERY_ADDRESS###';
                $array2[] = $delivery_address;
                $array1[] = '###CUSTOMER_ID###';
                $array2[] = $order['customer_id'];
                $array1[] = '###SHIPPING_METHOD###';
                $array2[] = $order['shipping_method_label'];
                $array1[] = '###PAYMENT_METHOD###';
                $array2[] = $order['payment_method_label'];
                $array1[] = '###ORDERS_ID###';
                $array2[] = $order['orders_id'];
                $invoice = mslib_fe::getOrderInvoice($order['orders_id'], 0);
                $invoice_id = '';
                $invoice_link = '';
                if (is_array($invoice)) {
                    $invoice_id = $invoice['invoice_id'];
                    $invoice_link = '<a href="' . $this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=download_invoice&tx_multishop_pi1[hash]=' . $invoice['hash']) . '">' . $invoice['invoice_id'] . '</a>';
                }
                $array1[] = '###INVOICE_NUMBER###';
                $array2[] = $invoice_id;
                $array1[] = '###INVOICE_LINK###';
                $array2[] = $invoice_link;
                $time = $order['crdate'];
                $long_date = strftime($this->pi_getLL('full_date_format'), $time);
                $array1[] = '###ORDER_DATE_LONG###'; // ie woensdag 23 juni, 2010
                $array2[] = $long_date;
                // backwards compatibility
                $array1[] = '###ORDER_DATE###'; // 21-12-2010 in localized format
                $array2[] = strftime("%x", $time);
                $array1[] = '###LONG_DATE###'; // ie woensdag 23 juni, 2010
                $array2[] = $long_date;
                $time = time();
                $long_date = strftime($this->pi_getLL('full_date_format'), $time);
                $array1[] = '###CURRENT_DATE_LONG###'; // ie woensdag 23 juni, 2010
                $array2[] = $long_date;
                $array1[] = '###STORE_NAME###';
                $array2[] = $this->ms['MODULES']['STORE_NAME'];
                $array1[] = '###TOTAL_AMOUNT###';
                $array2[] = mslib_fe::amount2Cents($order['total_amount']);
                $array1[] = '###PROPOSAL_NUMBER###';
                $array2[] = $order['orders_id'];
                $array1[] = '###ORDER_NUMBER###';
                $array2[] = $order['orders_id'];
                $array1[] = '###ORDER_LINK###';
                $array2[] = '';
                $array1[] = '###CUSTOMER_ID###';
                $array2[] = $order['customer_id'];
                $array1[] = '###CUSTOMER_COMMENTS###';
                $array2[] = $order['customer_comments'];
                // for on the site eof
                $page = array();
                // psp email template
                $psp_mail_template = array();
                if ($order['payment_method']) {
                    $psp_data = mslib_fe::loadPaymentMethod($order['payment_method']);
                    $psp_vars = unserialize($psp_data['vars']);
                    if (isset($psp_vars['order_thank_you_page'])) {
                        $psp_mail_template['order_thank_you_page'] = '';
                        if ($psp_vars['order_thank_you_page'] > 0) {
                            $psp_mail_template['order_thank_you_page'] = mslib_fe::getCMSType($psp_vars['order_thank_you_page']);
                        }
                    }
                }
                // first try to load the custom thank you page based on the payment method
                if (isset($psp_mail_template['order_thank_you_page'])) {
                    $page = array();
                    if (!empty($psp_mail_template['order_thank_you_page'])) {
                        $page = mslib_fe::getCMScontent($psp_mail_template['order_thank_you_page'], $GLOBALS['TSFE']->sys_language_uid);
                    }
                } else {
                    if ($order['payment_method']) {
                        $page = mslib_fe::getCMScontent('order_received_thank_you_page_' . $order['payment_method'], $GLOBALS['TSFE']->sys_language_uid);
                    }
                    if (!count($page[0])) {
                        $page = mslib_fe::getCMScontent('order_received_thank_you_page', $GLOBALS['TSFE']->sys_language_uid);
                    }
                }
                // custom hook that can be controlled by third-party plugin
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/checkout.php']['checkoutThankYouPageMarkerPreProc'])) {
                    $params = array(
                            'order' => $order,
                            'page' => $page,
                            'array1' => &$array1,
                            'array2' => &$array2,
                            'content' => &$content
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/checkout.php']['checkoutThankYouPageMarkerPreProc'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                // custom hook that can be controlled by third-party plugin eof
                if ($page[0]['name']) {
                    if ($page[0]['name']) {
                        $page[0]['name'] = str_replace($array1, $array2, $page[0]['name']);
                        $content .= '<div class="main-heading"><h2>' . $page[0]['name'] . '</h2></div>';
                    }
                    if ($page[0]['content']) {
                        $page[0]['content'] = str_replace($array1, $array2, $page[0]['content']);
                        $content .= $page[0]['content'];
                    }
                } else {
                    // show standard thank you
                    $content .= '<div class="main-heading"><h2>' . $this->pi_getLL('your_order_has_been_received') . '</h2></div>';
                }
                //	Thank you for ordering on our shop!
                if ($order['payment_method'] and $order['paid']) {
                    // order has been paid, so dont load the psp
                    $content .= 'Your order has been paid.';
                } elseif ($order['payment_method']) {
                    // load optional payment button
                    $mslib_payment = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('mslib_payment');
                    $mslib_payment->init($this);
                    $paymentMethods = $mslib_payment->getEnabledPaymentMethods();
                    if (is_array($paymentMethods)) {
                        foreach ($paymentMethods as $user_method) {
                            if ($user_method['code'] == $order['payment_method']) {
                                if ($user_method['vars'] and $user_method['provider']) {
                                    $vars = unserialize($user_method['vars']);
                                    if ($mslib_payment->setPaymentMethod($user_method['provider'])) {
                                        $extkey = 'multishop_' . $user_method['provider'];
                                        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extkey)) {
                                            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extkey) . 'class.multishop_payment_method.php');
                                            $paymentMethod = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_multishop_payment_method');
                                            $paymentMethod->setPaymentMethod($user_method['provider']);
                                            $paymentMethod->setVariables($vars);
                                            $content .= $paymentMethod->displayPaymentButton($order['orders_id'], $this);
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        break;
    case 'custom_page':
        // custom page hook that can be controlled by third-party plugin
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customPage'])) {
            $params = array(
                    'content' => &$content
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customPage'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // custom page hook that can be controlled by third-party plugin eof
        break;
    case 'clear_last_visited_list':
        // eid
        foreach ($_COOKIE['last_visited'] as $pid) {
            setcookie('last_visited[' . $pid . ']', '', 1, '/');
        }
        // traditional way
        require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_cart.php');
        $mslib_cart = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
        $mslib_cart->init($this);
        $cart = $mslib_cart->getCart();
        $cart['last_visited'] = array();
        tx_mslib_cart::storeCart($cart);
        if ($_SERVER['HTTP_REFERER']) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: /');
        }
        break;
    default:
        $this->ms['page'] = 'home';
        // load cms top
        if (!$this->get['p']) {
            $lifetime = 36000;
            $string = 'home_top_' . $GLOBALS['TSFE']->sys_language_uid;
            if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$tmp = mslib_befe::cacheLite('get', $string, $lifetime, 0))) {
                $tmp = mslib_fe::printCMScontent('home_top', $GLOBALS['TSFE']->sys_language_uid);
                if ($this->ms['MODULES']['CACHE_FRONT_END']) {
                    // if empty we stuff it with a space, so the database query wont be executed next time
                    if (!$tmp) {
                        $tmp = ' ';
                    }
                    mslib_befe::cacheLite('save', $string, $lifetime, 0, $tmp);
                }
            }
            $content .= $tmp;
        }
        // load cms top eof
        if ($this->ms['MODULES']['HOME_PRODUCTS_LISTING']) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/products_listing.php');
        }
        // load cms bottom
        if (!$this->get['p']) {
            $string = 'home_bottom' . $GLOBALS['TSFE']->sys_language_uid;
            if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$tmp = mslib_befe::cacheLite('get', $string, $lifetime, 0))) {
                $tmp = mslib_fe::printCMScontent('home_bottom', $GLOBALS['TSFE']->sys_language_uid);
                if ($this->ms['MODULES']['CACHE_FRONT_END']) {
                    // if empty we stuff it with a space, so the database query wont be executed next time
                    if (!$tmp) {
                        $tmp = ' ';
                    }
                    mslib_befe::cacheLite('save', $string, $lifetime, 0, $tmp);
                }
            }
            $content .= $tmp;
        }
        // load cms bottom eof
        break;
}
if (!$this->ms['MODULES']['DISABLE_CRUMBAR'] and $GLOBALS['TYPO3_CONF_VARS']["tx_multishop"]['crumbar_html']) {
    $content = $GLOBALS['TYPO3_CONF_VARS']["tx_multishop"]['crumbar_html'] . $content;
}
$content = '<div id="tx_multishop_pi1_core">' . $content . '</div>';
?>