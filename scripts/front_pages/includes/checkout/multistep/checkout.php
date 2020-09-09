<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
// Add header to prevent search-engines from indexing this page
header("X-Robots-Tag: noindex", true);
$GLOBALS['TSFE']->additionalHeaderData[] = '<meta name="robots" content="noindex">' . "\n";
if (!mslib_fe::loggedin() and $this->ms['MODULES']['DISABLE_CHECKOUT_FOR_GUESTS']) {
    // redirect to login page
    header('Location: ' . $this->FULL_HTTP_URL . mslib_fe::typolink($this->conf['login_pid']));
    exit();
}
// minimum order amount
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_cart.php');
$mslib_cart = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
$mslib_cart->init($this);
$cart = $mslib_cart->getCart();
$cart_total_amount = $mslib_cart->countCartTotalPrice(0);
$count_products = count($cart['products']);
if (!empty($this->ms['MODULES']['MINIMUM_ORDER_AMOUNT']) && $this->ms['MODULES']['MINIMUM_ORDER_AMOUNT'] > 0) {
    if ($cart_total_amount < $this->ms['MODULES']['MINIMUM_ORDER_AMOUNT']) {
        // redirect to shopping cart page
        header('Location: ' . $this->FULL_HTTP_URL . mslib_fe::typolink($this->conf['shoppingcart_page_pid'], '&tx_multishop_pi1[page_section]=shopping_cart'));
        exit();
    }
}
// maximum order amount
if (!empty($this->ms['MODULES']['MAXIMUM_ORDER_AMOUNT']) && $this->ms['MODULES']['MAXIMUM_ORDER_AMOUNT'] > 0) {
    if ($cart_total_amount > $this->ms['MODULES']['MAXIMUM_ORDER_AMOUNT']) {
        // redirect to shopping cart page
        header('Location: ' . $this->FULL_HTTP_URL . mslib_fe::typolink($this->conf['shoppingcart_page_pid'], '&tx_multishop_pi1[page_section]=shopping_cart'));
        exit();
    }
}
if (is_array($cart['products']) and $count_products) {
    // load customer country
    // use for filtering the available payment on the customer country
    $address = $cart['user'];
    if (isset($this->post['country']) && !empty($this->post['country']) && $this->post['country'] != $address['country']) {
        $address['country'] = $this->post['country'];
    }
    if (!$address['country']) {
        $user_country = 0;
    } else {
        $iso_customer = mslib_fe::getCountryByName($address['country']);
        $user_country = $iso_customer['cn_iso_nr'];
    }
    $delivery_user_country = $user_country;
    if (isset($this->post['different_delivery_address']) && $this->post['different_delivery_address'] > 0 && !empty($this->post['delivery_country'])) {
        $iso_customer = mslib_fe::getCountryByName($this->post['delivery_country']);
        $delivery_user_country = $iso_customer['cn_iso_nr'];
    }
    $payment_methods_product = array();
    $shipping_methods_product = array();
    $payment_methods_group = array();
    $shipping_methods_group = array();
    $payment_methods_user = array();
    $shipping_methods_user = array();
    $payment_methods = array();
    $shipping_methods = array();
    $load_mappings_order = array();
    $load_mappings_order[] = 'products';
    $load_mappings_order[] = 'customers_groups';
    $load_mappings_order[] = 'customers';
    foreach ($load_mappings_order as $mapping) {
        switch ($mapping) {
            case 'products':
                if ($this->ms['MODULES']['PRODUCT_EDIT_METHOD_FILTER']) {
                    $payment_methods = array();
                    $shipping_methods = array();
                    $pids = array();
                    foreach ($cart['products'] as $key => $array) {
                        if (is_numeric($array['products_id'])) {
                            $pids[] = $array['products_id'];
                        }
                    }
                    if (count($pids)) {
                        $payment_methods_product = mslib_fe::getProductMappedMethods($pids, 'payment', $user_country);
                        $shipping_methods_product = mslib_fe::getProductMappedMethods($pids, 'shipping');
                    }
                }
                break;
            case 'customers_groups':
                if ($this->ms['MODULES']['GROUP_EDIT_METHOD_FILTER']) {
                    $payment_methods = array();
                    $shipping_methods = array();
                    $tmp_user_groups = explode(',', $GLOBALS['TSFE']->fe_user->user['usergroup']);
                    $user_groups = array();
                    foreach ($tmp_user_groups as $tmp_user_group) {
                        if ($tmp_user_group > 0) {
                            $user_groups[] = $tmp_user_group;
                        }
                    }
                    if (count($user_groups)) {
                        $payment_methods_group = mslib_fe::getCustomerGroupMappedMethods($user_groups, 'payment', $user_country);
                        $shipping_methods_group = mslib_fe::getCustomerGroupMappedMethods($user_groups, 'shipping');
                        if (!count($payment_methods_group)) {
                            $payment_methods = $payment_methods_product;
                        }
                        if (!count($shipping_methods_group)) {
                            $shipping_methods = $shipping_methods_product;
                        }
                    }
                }
                break;
            case 'customers':
                if ($this->ms['MODULES']['CUSTOMER_EDIT_METHOD_FILTER']) {
                    $payment_methods = array();
                    $shipping_methods = array();
                    $user_id = array();
                    $user_id = $GLOBALS['TSFE']->fe_user->user['uid'];
                    if (is_numeric($user_id)) {
                        $payment_methods_user = mslib_fe::getCustomerMappedMethods($user_id, 'payment', $user_country);
                        $shipping_methods_user = mslib_fe::getCustomerMappedMethods($user_id, 'shipping');
                        if (!count($payment_methods_user)) {
                            $payment_methods = $payment_methods_group;
                        }
                        if (!count($shipping_methods)) {
                            $shipping_methods = $shipping_methods_group;
                        }
                    }
                }
                break;
        }
    }
    if (!count($payment_methods)) {
        // nothing is loaded. this cant be valid so let's load the default methods.
        // loading payment methods
        $payment_methods = mslib_fe::loadPaymentMethods(0, $user_country, true, true);
    }
    if (!count($shipping_methods)) {
        // loading shipping methods eof
        $shipping_methods = mslib_fe::loadShippingMethods(0, $delivery_user_country, true, true);
    }
}
if (count($shipping_methods) == 0 and count($payment_methods) == 0) {
    $stepCodes = array(
            'checkout_address',
            'checkout_review',
            'checkout_finished'
    );
} else {
    $stepCodes = array(
            'checkout_address',
            'checkout_shipping_payment_method',
            'checkout_review',
            'checkout_finished'
    );
}
function CheckoutStepping(&$steps, $current, $pointer) {
    $output = '<ul id="checkout_crumbar">';
    for ($i = 0; $i < count($steps); $i++) {
        $output .= '<li class="' . $steps[$i] . ' ' . (($current == $steps[$i]) ? 'active' : '') . '">';
        if ($steps[$i] == $current) {
            $output .= '<strong>';
        }
        $output .= '<span class="step">' . $pointer->pi_getLL('step') . ' ' . ($i + 1) . ':</span><span class="step_label">' . $pointer->pi_getLL($steps[$i]) . '</span>';
        if ($steps[$i] == $current) {
            $output .= '</strong>';
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    return $output;
}

if ($this->get['tx_multishop_pi1']['previous_checkout_section']) {
    for ($i = 1; $i <= count($stepCodes); $i++) {
        if ($this->get['tx_multishop_pi1']['previous_checkout_section'] == current($stepCodes)) {
            if ($this->post) {
                $posted_page = current($stepCodes);
            }
            break;
        } else {
            if (!next($stepCodes)) {
                break;
            }
        }
    }
}
// custom hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/checkout/multistep/checkout.php']['checkoutStepPreProc'])) {
    $params = array(
            'stepCodes' => $stepCodes
    );
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/checkout/multistep/checkout.php']['checkoutStepPreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
require(current($stepCodes) . '.php');
