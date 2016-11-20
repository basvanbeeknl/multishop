<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
// custom hook that can be controlled by third-party plugin eof
if (is_numeric($this->get['products_id'])) {
    //last visited
    //$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
    require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_cart.php');
    $mslib_cart = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
    $mslib_cart->init($this);
    $cart = $mslib_cart->getCart();
    $cart['last_visited'][$this->get['products_id']] = $this->get['products_id'];
    //$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
    //$GLOBALS['TSFE']->fe_user->storeSessionData();
    tx_mslib_cart::storeCart($cart);
    //last visited eof
    if (isset($this->get['clear_list'])) {
        //$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
        require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_cart.php');
        $mslib_cart = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
        $mslib_cart->init($this);
        $cart = $mslib_cart->getCart();
        $cart['last_visited'] = array();
        $cart['last_visited'][$this->get['products_id']] = $this->get['products_id'];
        //$GLOBALS['TSFE']->fe_user->setKey('ses', $this->cart_page_uid, $cart);
        //$GLOBALS['TSFE']->fe_user->storeSessionData();
        tx_mslib_cart::storeCart($cart);
    }
    unset($cart['last_visited'][$this->get['products_id']]);
}
if (($this->ms['MODULES']['CACHE_FRONT_END'] && !$this->ms['MODULES']['CACHE_TIME_OUT_PRODUCTS_DETAIL_PAGES']) || (isset($this->get['tx_multishop_pi1']['cart_item']) && !empty($this->get['tx_multishop_pi1']['cart_item']))) {
    $this->ms['MODULES']['CACHE_FRONT_END'] = 0;
}
if ($this->ms['MODULES']['CACHE_FRONT_END']) {
    $options = array(
            'caching' => true,
            'cacheDir' => $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/cache/',
            'lifeTime' => $this->ms['MODULES']['CACHE_TIME_OUT_PRODUCTS_DETAIL_PAGES']
    );
    $Cache_Lite = new Cache_Lite($options);
    $string = md5($this->cObj->data['uid'] . '_' . $this->HTTP_HOST . '_' . $this->server['REQUEST_URI'] . $this->server['QUERY_STRING']);
}
if (!$this->ms['MODULES']['CACHE_FRONT_END'] || !$output_array = $Cache_Lite->get($string)) {
    $output_array = array();
    // custom hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPageJSHook'])) {
        $params = array('output_array' => &$output_array);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPageJSHook'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    // canonical link tag
    $relation_cat = mslib_fe::getProductToCategoriesArray($this->get['products_id']);
    $count_relation_cat = count($relation_cat);
    if ($count_relation_cat > 1 || (isset($this->get['manufacturers_id']) && $this->get['manufacturers_id'] > 0)) {
        if ($count_relation_cat > 1) {
            $primary_cat = $relation_cat[0];
            if ($this->ms['MODULES']['ENABLE_DEFAULT_CRUMPATH']) {
                $product_path = mslib_befe::getRecord($this->get['products_id'], 'tx_multishop_products_to_categories', 'products_id', array('is_deepest=1 and default_path=1'));
                if (is_array($product_path) && count($product_path)) {
                    $primary_cat = $product_path['node_id'];
                }
            }
        } else {
            $product_path = mslib_befe::getRecord($this->get['products_id'], 'tx_multishop_products_to_categories', 'products_id', array('is_deepest=1'));
            if (is_array($product_path) && count($product_path)) {
                $primary_cat = $product_path['node_id'];
            }
        }
        // get all cats to generate multilevel fake url
        $level = 0;
        $cats = mslib_fe::Crumbar($primary_cat);
        $cats = array_reverse($cats);
        $where = '';
        if (count($cats) > 0) {
            foreach ($cats as $cat) {
                $where .= "categories_id[" . $level . "]=" . $cat['id'] . "&";
                $level++;
            }
            $where = substr($where, 0, (strlen($where) - 1));
            $where .= '&';
        }
        // get all cats to generate multilevel fake url eof
        $canonical_link = $this->FULL_HTTP_URL . mslib_fe::typolink($this->conf['products_detail_page_pid'], $where . '&products_id=' . $this->get['products_id'] . '&tx_multishop_pi1[page_section]=products_detail');
        $output_array['meta']['canonical_url'] = '<link rel="canonical" href="' . $canonical_link . '" />';
        $output_array['canonical_url'] = $canonical_link;
    }
    if (strstr($this->ms['MODULES']['PRODUCTS_DETAIL_TYPE'], "/")) {
        require($this->DOCUMENT_ROOT . $this->ms['MODULES']['PRODUCTS_DETAIL_TYPE'] . '.php');
    } elseif ($this->ms['MODULES']['PRODUCTS_DETAIL_TYPE']) {
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/includes/products_detail/' . $this->ms['MODULES']['PRODUCTS_DETAIL_TYPE'] . '.php');
    } else {
        require_once('includes/products_detail/default.php');
    }
    if (isset($product['search_engines_allow_indexing'])) {
        if (!$product['search_engines_allow_indexing']) {
            $output_array['meta']['noindex'] = '<meta name="robots" content="noindex, nofollow" />';
        } else {
            if ($product['categories_id']) {
                $no_index = false;
                $level = 0;
                $cats = array();
                $cats = mslib_fe::Crumbar($product['categories_id']);
                if (count($cats) > 0) {
                    foreach ($cats as $cat) {
                        if ($level > 0) {
                            if (!$cat['search_engines_allow_indexing']) {
                                $no_index = true;
                                break;
                            }
                        }
                        $level++;
                    }
                }
                if ($no_index) {
                    $output_array['meta']['noindex'] = '<meta name="robots" content="noindex, nofollow" />';
                }
            }
        }
    }
    if ($this->ms['MODULES']['CACHE_FRONT_END']) {
        $output_array['content'] = $content;
        $Cache_Lite->save(serialize($output_array));
    }
} elseif ($output_array) {
    $output_array = unserialize($output_array);
    if ($output_array['http_header']) {
        if (!is_array($output_array['http_header'])) {
            header($output_array['http_header']);
        } else {
            foreach ($output_array['http_header'] as $http_header) {
                header($http_header);
            }
        }
    }
    $content .= $output_array['content'];
}
// custom hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPagePostJSHook'])) {
    $params = array('output_array' => &$output_array);
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPagePostJSHook'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
$output_array['meta']['attributesJS'] = '<script type="text/javascript" data-ignore="1">
jQuery(document).ready(function($){
    if ($(".attributeDate").length>0) {
        jQuery(".attributeDate").datepicker({
            dateFormat: "' . $this->pi_getLL('locale_date_format_js', 'm/d/Y') . '",
            changeMonth: true,
            changeYear: true,
            showOtherMonths: true,
        });
    }
    if ($(".attributeDateOfBirth").length>0) {
        jQuery(".attributeDateOfBirth").datepicker({
            dateFormat: "' . $this->pi_getLL('locale_date_format_js', 'm/d/Y') . '",
            changeMonth: true,
            changeYear: true,
            showOtherMonths: true,
            yearRange: "' . (date("Y") - 110) . ':' . date("Y") . '"
        });
    }
    if ($(".attributeDateTime").length>0) {
        jQuery(".attributeDateTime").datetimepicker({
            dateFormat: "' . $this->pi_getLL('locale_date_format_js', 'm/d/Y') . '",
            changeMonth: true,
            changeYear: true,
            showOtherMonths: true,
        });
    }
    ' . (isset($output_array['attributeDateCustomJS']) && !empty($output_array['attributeDateCustomJS']) ? $output_array['attributeDateCustomJS'] : '') . '
});
</script>';
if (is_array($output_array['meta']) && count($output_array['meta'])) {
    $GLOBALS['TSFE']->additionalHeaderData = array_merge($GLOBALS['TSFE']->additionalHeaderData, $output_array['meta']);
}
unset($output_array);
?>