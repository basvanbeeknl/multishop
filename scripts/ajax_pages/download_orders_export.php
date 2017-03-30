<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
if ($this->get['orders_export_hash']) {
    set_time_limit(86400);
    ignore_user_abort(true);
    $orders_export = mslib_fe::getOrdersExportWizard($this->get['orders_export_hash'], 'code');
    $lifetime = 7200;
    if ($this->ADMIN_USER) {
        $lifetime = 0;
    }
    $options = array(
            'caching' => true,
            'cacheDir' => $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/cache/',
            'lifeTime' => $lifetime
    );
    $Cache_Lite = new Cache_Lite($options);
    $string = 'productfeed_' . $this->shop_pid . '_' . serialize($orders_export) . '-' . md5($this->cObj->data['uid'] . '_' . $this->server['REQUEST_URI'] . $this->server['QUERY_STRING']);
    if ($this->ADMIN_USER and $this->get['clear_cache']) {
        if ($Cache_Lite->get($string)) {
            $Cache_Lite->remove($string);
        }
    }
    if (!$content = $Cache_Lite->get($string)) {
        $fields = unserialize($orders_export['fields']);
        $post_data = unserialize($orders_export['post_data']);
        switch ($post_data['delimeter_type']) {
            case '\t':
                $post_data['delimeter_type'] = "\t";
                break;
            case '':
                $post_data['delimeter_type'] = ';';
                break;

        }
        $fields_values = $post_data['fields_values'];
        $records = array();
        // orders record
        $filter = array();
        $from = array();
        $having = array();
        $match = array();
        $orderby = array();
        $where = array();
        $orderby = array();
        $select = array();
        if (!empty($post_data['orders_date_from']) && !empty($post_data['orders_date_till'])) {
            $start_time = strtotime($post_data['orders_date_from']);
            $end_time = strtotime($post_data['orders_date_till']);
            $column = 'o.crdate';
            $filter[] = $column . " BETWEEN '" . $start_time . "' and '" . $end_time . "'";
        }
        if (!empty($post_data['start_duration'])) {
            $start_duration = strtotime($post_data['start_duration']);
            if (!empty($post_data['end_duration'])) {
                $end_duration = strtotime($post_data['end_duration'], $start_duration);
            } else {
                $end_duration = time();
            }
            $column = 'o.crdate';
            $filter[] = $column . " BETWEEN '" . $start_duration . "' and '" . $end_duration . "'";
        }
        if ($post_data['order_status'] !== 'all') {
            $filter[] = "(o.status='" . $post_data['order_status'] . "')";
        }
        if ($post_data['payment_status'] == 'paid') {
            $filter[] = "(o.paid='1')";
        } else if ($post_data['payment_status'] == 'unpaid') {
            $filter[] = "(o.paid='0')";
        }
        if (!$this->masterShop) {
            $filter[] = 'o.page_uid=' . $this->shop_pid;
        }
        $select[] = 'o.*, osd.name as orders_status';
        switch ($post_data['order_by']) {
            case 'billing_name':
                $order_by = 'o.billing_name';
                break;
            case 'crdate':
                $order_by = 'o.crdate';
                break;
            case 'grand_total':
                $order_by = 'o.grand_total';
                break;
            case 'shipping_method_label':
                $order_by = 'o.shipping_method_label';
                break;
            case 'payment_method_label':
                $order_by = 'o.payment_method_label';
                break;
            case 'status_last_modified':
                $order_by = 'o.status_last_modified';
                break;
            case 'orders_id':
            default:
                $order_by = 'o.orders_id';
                break;
        }
        switch ($post_data['sort_direction']) {
            case 'asc':
                $order = 'asc';
                break;
            case 'desc':
            default:
                $order = 'desc';
                break;
        }
        $orderby[] = $order_by . ' ' . $order;
        if ($post_data['order_type'] == 'by_phone') {
            $filter[] = 'o.by_phone=1';
        }
        if ($this->get['format'] == 'excel') {
            $ox_limit = 65000;
        } else {
            $ox_limit = 500000;
        }
        $pageset = mslib_fe::getOrdersPageSet($filter, $offset, $ox_limit, $orderby, $having, $select, $where, $from);
        //print_r($pageset);
        //die();
        $records = $pageset['orders'];
        // load all products
        $excelRows = array();
        $excelHeaderCols = array();
        foreach ($fields as $counter => $field) {
            if ($field != 'order_products' && $field != 'turnover_per_category_incl_vat' && $field != 'turnover_per_category_excl_vat' && $field != 'turnover_per_main_category_incl_vat' && $field != 'turnover_per_main_category_excl_vat' && $field != 'bought_products_per_main_category') {
                $excelHeaderCols[$field] = $field;
            } else {
                switch ($field) {
                    case 'order_products':
                        $max_cols_num = ($post_data['maximum_number_of_order_products'] ? $post_data['maximum_number_of_order_products'] : 25);
                        for ($i = 0; $i < $max_cols_num; $i++) {
                            $excelHeaderCols['product_id' . $i] = 'product_id' . $i;
                            $excelHeaderCols['product_name' . $i] = 'product_name' . $i;
                            $excelHeaderCols['product_qty' . $i] = 'product_qty' . $i;
                            $excelHeaderCols['product_final_price_excl_tax' . $i] = 'product_final_price_excl_tax' . $i;
                            $excelHeaderCols['product_final_price_incl_tax' . $i] = 'product_final_price_incl_tax' . $i;
                            $excelHeaderCols['product_price_total_excl_tax' . $i] = 'product_final_price_total_excl_tax' . $i;
                            $excelHeaderCols['product_price_total_incl_tax' . $i] = 'product_final_price_total_incl_tax' . $i;
                            $excelHeaderCols['product_tax_rate' . $i] = 'product_tax_rate' . $i;
                            //hook to let other plugins further manipulate the replacers
                            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_orders_export.php']['exportOrdersHeaderOrderProductsPostProc'])) {
                                $params = array(
                                        'excelHeaderCols' => &$excelHeaderCols,
                                        'i' => &$i
                                );
                                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_orders_export.php']['exportOrdersHeaderOrderProductsPostProc'] as $funcRef) {
                                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                                }
                            }
                        }
                        break;
                    case 'turnover_per_category_incl_vat':
                        $categories_data_incl_vat = array();
                        foreach ($records as $record) {
                            $order_tmp = mslib_fe::getOrder($record['orders_id']);
                            foreach ($order_tmp['products'] as $product) {
                                $category_name = $product['categories_name'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                if ($product['categories_id'] > 0) {
                                    $categories_data_incl_vat[$category_name] = $product['categories_id'];
                                } else {
                                    $categories_data_incl_vat[$category_name] = $this->pi_getLL('unknown');
                                }
                            }
                        }
                        if (is_array($categories_data_incl_vat) && count($categories_data_incl_vat)) {
                            foreach ($categories_data_incl_vat as $category_name => $category_id) {
                                $excelHeaderCols['categories_id_' . $category_name . '_incl_vat'] = sprintf($this->pi_getLL('turnover_per_category_incl_vat'), $category_name);
                            }
                        }
                        break;
                    case 'turnover_per_category_excl_vat':
                        $categories_data_excl_vat = array();
                        foreach ($records as $record) {
                            $order_tmp = mslib_fe::getOrder($record['orders_id']);
                            foreach ($order_tmp['products'] as $product) {
                                $category_name = $product['categories_name'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                if ($product['categories_id'] > 0) {
                                    $categories_data_excl_vat[$category_name] = $product['categories_id'];
                                } else {
                                    $categories_data_excl_vat[$category_name] = $this->pi_getLL('unknown');
                                }
                            }
                        }
                        if (is_array($categories_data_excl_vat) && count($categories_data_excl_vat)) {
                            foreach ($categories_data_excl_vat as $category_name => $category_id) {
                                $excelHeaderCols['categories_id_' . $category_name . '_excl_vat'] = sprintf($this->pi_getLL('turnover_per_category_excl_vat'), $category_name);
                            }
                        }
                        break;
                    case 'turnover_per_main_category_incl_vat':
                        $main_categories_data_incl_vat = array();
                        foreach ($records as $record) {
                            $order_tmp = mslib_fe::getOrder($record['orders_id']);
                            foreach ($order_tmp['products'] as $product) {
                                $category_name = $product['categories_name_0'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                if ($product['categories_id_0'] > 0) {
                                    $main_categories_data_incl_vat[$category_name] = $product['categories_id_0'];
                                } else {
                                    $main_categories_data_incl_vat[$category_name] = $this->pi_getLL('unknown');
                                }
                            }
                        }
                        if (is_array($main_categories_data_incl_vat) && count($main_categories_data_incl_vat)) {
                            foreach ($main_categories_data_incl_vat as $category_name => $category_id) {
                                $excelHeaderCols['main_categories_id_' . $category_name . '_incl_vat'] = sprintf($this->pi_getLL('turnover_per_main_category_incl_vat'), $category_name);
                            }
                        }
                        break;
                    case 'turnover_per_main_category_excl_vat':
                        $main_categories_data_excl_vat = array();
                        foreach ($records as $record) {
                            $order_tmp = mslib_fe::getOrder($record['orders_id']);
                            foreach ($order_tmp['products'] as $product) {
                                $category_name = $product['categories_name_0'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                if ($product['categories_id_0'] > 0) {
                                    $main_categories_data_excl_vat[$category_name] = $product['categories_id_0'];
                                } else {
                                    $main_categories_data_excl_vat[$category_name] = $this->pi_getLL('unknown');
                                }
                            }
                        }
                        if (is_array($main_categories_data_excl_vat) && count($main_categories_data_excl_vat)) {
                            foreach ($main_categories_data_excl_vat as $category_name => $category_id) {
                                $excelHeaderCols['main_categories_id_' . $category_name . '_excl_vat'] = sprintf($this->pi_getLL('turnover_per_main_category_excl_vat'), $category_name);
                            }
                        }
                        break;
                    case 'bought_products_per_main_category':
                        $main_categories_data_bought_products = array();
                        foreach ($records as $record) {
                            $order_tmp = mslib_fe::getOrder($record['orders_id']);
                            foreach ($order_tmp['products'] as $product) {
                                $category_name = $product['categories_name_0'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                if ($product['categories_id_0'] > 0) {
                                    $main_categories_data_bought_products[$category_name] = $product['categories_id_0'];
                                } else {
                                    $main_categories_data_bought_products[$category_name] = $this->pi_getLL('unknown');
                                }
                            }
                        }
                        if (is_array($main_categories_data_bought_products) && count($main_categories_data_bought_products)) {
                            foreach ($main_categories_data_bought_products as $category_name => $category_id) {
                                $excelHeaderCols['bought_products_main_categories_id_' . $category_name] = sprintf($this->pi_getLL('bought_products_per_main_category'), $category_name);
                            }
                        }
                        break;
                }
            }
        }
        if ($this->get['format'] == 'excel') {
            $excelRows[] = $excelHeaderCols;
        } else {
            $excelRows[] = implode($post_data['delimeter_type'], $excelHeaderCols);
        }
        foreach ($records as $row) {
            $order_tax_data = unserialize($row['orders_tax_data']);
            $order_tmp = mslib_fe::getOrder($row['orders_id']);
            $excelCols = array();
            $total = count($fields);
            $count = 0;
            foreach ($fields as $counter => $field) {
                $count++;
                $tmpcontent = '';
                switch ($field) {
                    case 'orders_id':
                        $excelCols[] = $row['orders_id'];
                        break;
                    case 'customer_id':
                        $excelCols[] = $row['customer_id'];
                        break;
                    case 'orders_status':
                        $excelCols[] = $row['orders_status'];
                        break;
                    case 'customer_billing_email':
                        $excelCols[] = $row['billing_email'];
                        break;
                    case 'customer_billing_telephone':
                        $excelCols[] = $row['billing_telephone'];
                        break;
                    case 'customer_billing_name':
                        $excelCols[] = $row['billing_name'];
                        break;
                    case 'customer_billing_address':
                        $excelCols[] = $row['billing_address'];
                        break;
                    case 'customer_billing_city':
                        $excelCols[] = $row['billing_city'];
                        break;
                    case 'customer_billing_zip':
                        $excelCols[] = $row['billing_zip'];
                        break;
                    case 'customer_billing_country':
                        $excelCols[] = $row['billing_country'];
                        break;
                    case 'customer_delivery_email':
                        $excelCols[] = $row['delivery_email'];
                        break;
                    case 'customer_delivery_telephone':
                        $excelCols[] = $row['delivery_telephone'];
                        break;
                    case 'customer_delivery_name':
                        $excelCols[] = $row['delivery_name'];
                        break;
                    case 'customer_delivery_address':
                        $excelCols[] = $row['delivery_address'];
                        break;
                    case 'customer_delivery_city':
                        $excelCols[] = $row['delivery_city'];
                        break;
                    case 'customer_delivery_zip':
                        $excelCols[] = $row['delivery_zip'];
                        break;
                    case 'customer_delivery_country':
                        $excelCols[] = $row['delivery_country'];
                        break;
                    case 'orders_grand_total_excl_vat':
                        $excelCols[] = number_format($order_tax_data['grand_total'] - $order_tax_data['total_orders_tax'], 2, ',', '.');
                        break;
                    case 'orders_grand_total_incl_vat':
                        $excelCols[] = number_format($order_tax_data['grand_total'], 2, ',', '.');
                        break;
                    case 'payment_status':
                        $excelCols[] = ($row['paid']) ? $this->pi_getLL('paid') : $this->pi_getLL('unpaid');
                        break;
                    case 'shipping_method':
                        $excelCols[] = $row['shipping_method_label'];
                        break;
                    case 'shipping_cost_excl_vat':
                        $excelCols[] = number_format($row['shipping_method_costs'], 2, ',', '.');
                        break;
                    case 'shipping_cost_incl_vat':
                        $excelCols[] = number_format($row['shipping_method_costs'] + $order_tmp['orders_tax_data']['shipping_tax'], 2, ',', '.');
                        break;
                    case 'shipping_cost_vat_rate':
                        $excelCols[] = ($order_tmp['orders_tax_data']['shipping_total_tax_rate'] * 100) . '%';
                        break;
                    case 'payment_method':
                        $excelCols[] = $row['payment_method_label'];
                        break;
                    case 'payment_cost_excl_vat':
                        $excelCols[] = number_format($row['payment_method_cost'], 2, ',', '.');
                        break;
                    case 'payment_cost_incl_vat':
                        $excelCols[] = number_format($row['payment_method_cost'] + $order_tmp['orders_tax_data']['payment_tax'], 2, ',', '.');
                        break;
                    case 'payment_cost_vat_rate':
                        $excelCols[] = ($order_tmp['orders_tax_data']['payment_total_tax_rate'] * 100) . '%';
                        break;
                    case 'order_products':
                        $max_cols_num = ($post_data['maximum_number_of_order_products'] ? $post_data['maximum_number_of_order_products'] : 25);
                        $order_products = $order_tmp['products'];
                        $prod_ctr = 0;
                        foreach ($order_products as $product_tmp) {
                            if ($prod_ctr >= $max_cols_num) {
                                break;
                            }
                            $excelCols[] = $product_tmp['products_id'];
                            if (!empty($product_tmp['products_model'])) {
                                $excelCols[] = $product_tmp['products_name'] . ' (' . $product_tmp['products_model'] . ')';
                            } else {
                                $excelCols[] = $product_tmp['products_name'];
                            }
                            $excelCols[] = $product_tmp['qty'];
                            $excelCols[] = number_format($product_tmp['final_price'], 2, ',', '.');
                            $excelCols[] = number_format($product_tmp['final_price'] + $product_tmp['products_tax_data']['total_tax'], 2, ',', '.');
                            $excelCols[] = number_format($product_tmp['final_price'] * $product_tmp['qty'], 2, ',', '.');
                            $excelCols[] = number_format(($product_tmp['final_price'] + $product_tmp['products_tax_data']['total_tax']) * $product_tmp['qty'], 2, ',', '.');
                            $excelCols[] = $product_tmp['products_tax'] . '%';
                            //hook to let other plugins further manipulate the replacers
                            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_orders_export.php']['exportOrdersBodyOrderProductsPostProc'])) {
                                $params = array(
                                        'excelCols' => &$excelCols,
                                        'product_tmp' => &$product_tmp,
                                        'prod_ctr' => &$prod_ctr
                                );
                                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_orders_export.php']['exportOrdersBodyOrderProductsPostProc'] as $funcRef) {
                                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                                }
                            }
                            $prod_ctr++;
                        }
                        if ($prod_ctr < $max_cols_num) {
                            for ($x = $prod_ctr; $x < $max_cols_num; $x++) {
                                $excelCols[] = '';
                                $excelCols[] = '';
                                $excelCols[] = '';
                                $excelCols[] = '';
                                $excelCols[] = '';
                                $excelCols[] = '';
                                $excelCols[] = '';
                                $excelCols[] = '';
                                //hook to let other plugins further manipulate the replacers
                                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_orders_export.php']['exportOrdersBodyOrderProductsAppendPostProc'])) {
                                    $params = array(
                                            'excelCols' => &$excelCols,
                                            'product_tmp' => &$product_tmp,
                                            'prod_ctr' => &$prod_ctr
                                    );
                                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_orders_export.php']['exportOrdersBodyOrderProductsAppendPostProc'] as $funcRef) {
                                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                                    }
                                }
                            }
                        }
                        break;
                    case 'order_total_vat':
                        $excelCols[] = number_format($order_tax_data['total_orders_tax'], 2, ',', '.');
                        break;
                    case 'order_date':
                        $excelCols[] = ($row['crdate'] > 0 ? strftime('%x', $row['crdate']) : '');
                        break;
                    case 'order_company_name':
                        $excelCols[] = $row['billing_company'];
                        break;
                    case 'order_vat_id':
                        $excelCols[] = $row['billing_vat_id'];
                        break;
                    case 'order_customer_currency':
                        $excelCols[] = $row['customer_currency'];
                        break;
                    case 'order_customer_currency_rate':
                        $excelCols[] = $row['currency_rate'];
                        break;
                    case 'order_customer_language_id':
                        $excelCols[] = $row['language_id'];
                        break;
                    case 'order_track_and_trace_code':
                        $excelCols[] = $row['track_and_trace_code'];
                        break;
                    case 'order_orders_paid_timestamp':
                        $excelCols[] = strftime('%x', $row['orders_paid_timestamp']);
                        break;
                    case 'order_status_last_modified':
                        $excelCols[] = strftime('%x', $row['status_last_modified']);
                        break;
                    case 'order_orders_last_modified':
                        $excelCols[] = strftime('%x', $row['orders_last_modified']);
                        break;
                    case 'order_expected_delivery_date':
                        $excelCols[] = strftime('%x', $row['expected_delivery_date']);
                        break;
                    case 'order_by_phone':
                        $excelCols[] = ($row['by_phone'] > 0 ? $this->pi_getLL('yes') : $this->pi_getLL('no'));
                        break;
                    case 'turnover_per_category_incl_vat':
                        $order_products = $order_tmp['products'];
                        $categories_data_amount_incl_vat = array();
                        if (is_array($categories_data_incl_vat) && count($categories_data_incl_vat) > 0) {
                            foreach ($order_products as $product_tmp) {
                                $category_name = $product_tmp['categories_name'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                $categories_data_amount_incl_vat[$order_tmp['orders_id']][$category_name] += ($product_tmp['final_price'] + $product_tmp['products_tax_data']['total_tax']) * $product_tmp['qty'];
                                // fetch attributes
                                $str_opa = "SELECT * from tx_multishop_orders_products_attributes where orders_products_id='" . $product_tmp['orders_products_id'] . "' order by orders_products_attributes_id asc";
                                $qry_opa = $GLOBALS['TYPO3_DB']->sql_query($str_opa);
                                while (($order_product_attributes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_opa)) != false) {
                                    $options_attributes_tax_data = unserialize($order_product_attributes['attributes_tax_data']);
                                    $categories_data_amount_incl_vat[$order_tmp['orders_id']][$category_name] += (($order_product_attributes['price_prefix'] . $order_product_attributes['options_values_price']) + $options_attributes_tax_data['tax']) * $product_tmp['qty'];
                                }
                            }
                        }
                        foreach ($categories_data_incl_vat as $categories_index_main => $categories_id) {
                            if (isset($categories_data_amount_incl_vat[$order_tmp['orders_id']][$categories_index_main])) {
                                $excelCols[] = number_format($categories_data_amount_incl_vat[$order_tmp['orders_id']][$categories_index_main], 2, ',', '.');
                            } else {
                                $excelCols[] = number_format(0, 2, ',', '.');
                            }
                        }
                        break;
                    case 'turnover_per_category_excl_vat':
                        $order_products = $order_tmp['products'];
                        $categories_data_amount_excl_vat = array();
                        if (is_array($categories_data_excl_vat) && count($categories_data_excl_vat) > 0) {
                            foreach ($order_products as $product_tmp) {
                                $category_name = $product_tmp['categories_name'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                $categories_data_amount_excl_vat[$order_tmp['orders_id']][$category_name] += $product_tmp['final_price'] * $product_tmp['qty'];
                                // fetch attributes
                                $str_opa = "SELECT * from tx_multishop_orders_products_attributes where orders_products_id='" . $product_tmp['orders_products_id'] . "' order by orders_products_attributes_id asc";
                                $qry_opa = $GLOBALS['TYPO3_DB']->sql_query($str_opa);
                                while (($order_product_attributes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_opa)) != false) {
                                    $categories_data_amount_excl_vat[$order_tmp['orders_id']][$category_name] += (($order_product_attributes['price_prefix'] . $order_product_attributes['options_values_price'])) * $product_tmp['qty'];
                                }
                            }
                        }
                        foreach ($categories_data_excl_vat as $categories_index_main => $categories_id) {
                            if (isset($categories_data_amount_excl_vat[$order_tmp['orders_id']][$categories_index_main])) {
                                $excelCols[] = number_format($categories_data_amount_excl_vat[$order_tmp['orders_id']][$categories_index_main], 2, ',', '.');
                            } else {
                                $excelCols[] = number_format(0, 2, ',', '.');
                            }
                        }
                        break;
                    case 'turnover_per_main_category_incl_vat':
                        $order_products = $order_tmp['products'];
                        $main_categories_data_amount_incl_vat = array();
                        if (is_array($main_categories_data_incl_vat) && count($main_categories_data_incl_vat) > 0) {
                            foreach ($order_products as $product_tmp) {
                                $category_name = $product_tmp['categories_name_0'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                $main_categories_data_amount_incl_vat[$order_tmp['orders_id']][$category_name] += ($product_tmp['final_price'] + $product_tmp['products_tax_data']['total_tax']) * $product_tmp['qty'];
                                // fetch attributes
                                $str_opa = "SELECT * from tx_multishop_orders_products_attributes where orders_products_id='" . $product_tmp['orders_products_id'] . "' order by orders_products_attributes_id asc";
                                $qry_opa = $GLOBALS['TYPO3_DB']->sql_query($str_opa);
                                while (($order_product_attributes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_opa)) != false) {
                                    $options_attributes_tax_data = unserialize($order_product_attributes['attributes_tax_data']);
                                    $main_categories_data_amount_incl_vat[$order_tmp['orders_id']][$category_name] += (($order_product_attributes['price_prefix'] . $order_product_attributes['options_values_price']) + $options_attributes_tax_data['tax']) * $product_tmp['qty'];
                                }
                            }
                        }
                        foreach ($main_categories_data_incl_vat as $categories_index_main => $categories_id) {
                            if (isset($main_categories_data_amount_incl_vat[$order_tmp['orders_id']][$categories_index_main])) {
                                $excelCols[] = number_format($main_categories_data_amount_incl_vat[$order_tmp['orders_id']][$categories_index_main], 2, ',', '.');
                            } else {
                                $excelCols[] = number_format(0, 2, ',', '.');
                            }
                        }
                        break;
                    case 'turnover_per_main_category_excl_vat':
                        $order_products = $order_tmp['products'];
                        $main_categories_data_amount_excl_vat = array();
                        if (is_array($main_categories_data_excl_vat) && count($main_categories_data_excl_vat) > 0) {
                            foreach ($order_products as $product_tmp) {
                                $category_name = $product_tmp['categories_name_0'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                $main_categories_data_amount_excl_vat[$order_tmp['orders_id']][$category_name] += $product_tmp['final_price'] * $product_tmp['qty'];
                                // fetch attributes
                                $str_opa = "SELECT * from tx_multishop_orders_products_attributes where orders_products_id='" . $product_tmp['orders_products_id'] . "' order by orders_products_attributes_id asc";
                                $qry_opa = $GLOBALS['TYPO3_DB']->sql_query($str_opa);
                                while (($order_product_attributes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_opa)) != false) {
                                    $main_categories_data_amount_excl_vat[$order_tmp['orders_id']][$category_name] += (($order_product_attributes['price_prefix'] . $order_product_attributes['options_values_price'])) * $product_tmp['qty'];
                                }
                            }
                        }
                        foreach ($main_categories_data_excl_vat as $categories_index_main => $categories_id) {
                            if (isset($main_categories_data_amount_excl_vat[$order_tmp['orders_id']][$categories_index_main])) {
                                $excelCols[] = number_format($main_categories_data_amount_excl_vat[$order_tmp['orders_id']][$categories_index_main], 2, ',', '.');
                            } else {
                                $excelCols[] = number_format(0, 2, ',', '.');
                            }
                        }
                        break;
                    case 'bought_products_per_main_category':
                        $order_products = $order_tmp['products'];
                        $main_categories_data_bought_products_amount = array();
                        if (is_array($main_categories_data_bought_products) && count($main_categories_data_bought_products) > 0) {
                            foreach ($order_products as $product_tmp) {
                                $category_name = $product_tmp['categories_name_0'];
                                if (!$category_name) {
                                    $category_name = $this->pi_getLL('unknown');
                                }
                                $main_categories_data_bought_products_amount[$order_tmp['orders_id']][$category_name] += $product_tmp['qty'];
                            }
                        }
                        foreach ($main_categories_data_bought_products as $categories_index_main => $categories_id) {
                            if (isset($main_categories_data_bought_products_amount[$order_tmp['orders_id']][$categories_index_main])) {
                                $excelCols[] = $main_categories_data_bought_products_amount[$order_tmp['orders_id']][$categories_index_main];
                            } else {
                                $excelCols[] = '0';
                            }
                        }
                        break;
                }
            }
            // new rows
            if ($this->get['format'] == 'excel') {
                $excelRows[] = $excelCols;
            } else {
                $excelRows[] = implode($post_data['delimeter_type'], $excelCols);
            }
        }
        if ($this->get['format'] == 'excel') {
            $paths = array();
            $paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('phpexcel_service') . 'Classes/Service/PHPExcel.php';
            $paths[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('phpexcel_service') . 'Classes/PHPExcel.php';
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    break;
                }
            }
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getSheet(0)->setTitle('Orders Export');
            $objPHPExcel->getActiveSheet()->fromArray($excelRows);
            $ExcelWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="orders_export_' . $this->get['orders_export_hash'] . '.xlsx"');
            $ExcelWriter->save('php://output');
            exit();
        } else {
            $content = implode("\n", $excelRows);
        }
        $Cache_Lite->save($content);
    }
    header("Content-Type: text/plain");
    echo $content;
    exit();
}
exit();
?>