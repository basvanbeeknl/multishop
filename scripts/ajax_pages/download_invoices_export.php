<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
if ($this->get['invoices_export_hash']) {
    set_time_limit(86400);
    ignore_user_abort(true);
    $invoices_export = mslib_fe::getInvoicesExportWizard($this->get['invoices_export_hash'], 'code');
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
    $string = 'invoicesfeed_' . $this->shop_pid . '_' . serialize($invoices_export) . '-' . md5($this->cObj->data['uid'] . '_' . $this->server['REQUEST_URI'] . $this->server['QUERY_STRING']);
    if ($this->ADMIN_USER and $this->get['clear_cache']) {
        if ($Cache_Lite->get($string)) {
            $Cache_Lite->remove($string);
        }
    }
    if (!$content = $Cache_Lite->get($string)) {
        $fields = unserialize($invoices_export['fields']);
        $post_data = unserialize($invoices_export['post_data']);
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
            $start_duration = strtotime(date('Y-m-d 00:00:00', strtotime($post_data['start_duration'])));
            if (!empty($post_data['end_duration'])) {
                $end_duration = strtotime($post_data['end_duration'], $start_duration);
            } else {
                $end_duration = strtotime(date('Y-m-d 23:59:59', time()));
            }
            $column = 'i.crdate';
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
        $select[] = 'o.*, i.crdate as invoice_crdate, i.invoice_id, i.due_date, i.reference as invoice_reference, i.status as invoice_status, i.ordered_by, i.invoice_to, i.payment_condition, i.paid as invoice_paid, i.reversal_invoice';
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
        /*if ($post_data['order_type'] == 'by_phone') {
            $filter[] = 'o.by_phone=1';
        } else {
            $filter[] = 'o.by_phone=0';
        }
        if ($post_data['order_type'] == 'proposal') {
            $filter[] = 'o.is_proposal=1';
        } else {
            $filter[] = 'o.is_proposal=0';
        }*/
        $pageset = mslib_fe::getInvoicesPageSet($filter, $offset, 1000, $orderby, $having, $select, $where, $from);
        $records = $pageset['invoices'];
        // load all products
        $excelRows = array();
        $excelHeaderCols = array();
        foreach ($fields as $counter => $field) {
            if ($field != 'order_products') {
                $excelHeaderCols[$field] = $field;
            } else {
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
            $prefix = '';
            if ($row['reversal_invoice'] > 0) {
                $prefix = '-';
            }
            $excelCols = array();
            $total = count($fields);
            $count = 0;
            foreach ($fields as $counter => $field) {
                $count++;
                $tmpcontent = '';
                switch ($field) {
                    case 'orders_id':
                        $excelCols[$field] = $row['orders_id'];
                        break;
                    case 'order_date':
                        $excelCols[$field] = ($order_tmp['crdate'] > 0 ? strftime('%x', $order_tmp['crdate']) : '');
                        break;
                    case 'customer_id':
                        $excelCols[$field] = $row['customer_id'];
                        break;
                    case 'orders_status':
                        $excelCols[$field] = $row['orders_status'];
                        break;
                    case 'customer_billing_email':
                        $excelCols[$field] = $row['billing_email'];
                        break;
                    case 'customer_billing_company':
                        $excelCols[$field] = $row['billing_company'];
                        break;
                    case 'customer_billing_telephone':
                        $excelCols[$field] = $row['billing_telephone'];
                        break;
                    case 'customer_billing_name':
                        $excelCols[$field] = $row['billing_name'];
                        break;
                    case 'customer_billing_address':
                        $excelCols[$field] = $row['billing_address'];
                        break;
                    case 'customer_billing_city':
                        $excelCols[$field] = $row['billing_city'];
                        break;
                    case 'customer_billing_zip':
                        $excelCols[$field] = $row['billing_zip'];
                        break;
                    case 'customer_billing_country':
                        $excelCols[$field] = $row['billing_country'];
                        break;
                    case 'customer_delivery_email':
                        $excelCols[$field] = $row['delivery_email'];
                        break;
                    case 'customer_delivery_telephone':
                        $excelCols[$field] = $row['delivery_telephone'];
                        break;
                    case 'customer_delivery_name':
                        $excelCols[$field] = $row['delivery_name'];
                        break;
                    case 'customer_delivery_address':
                        $excelCols[$field] = $row['delivery_address'];
                        break;
                    case 'customer_delivery_company':
                        $excelCols[$field] = $row['delivery_company'];
                        break;
                    case 'customer_delivery_city':
                        $excelCols[$field] = $row['delivery_city'];
                        break;
                    case 'customer_delivery_zip':
                        $excelCols[$field] = $row['delivery_zip'];
                        break;
                    case 'customer_delivery_country':
                        $excelCols[$field] = $row['delivery_country'];
                        break;
                    case 'orders_grand_total_excl_vat':
                        $excelCols[$field] = $prefix . number_format($order_tax_data['grand_total'] - $order_tax_data['total_orders_tax'], 2, ',', '.');
                        break;
                    case 'orders_grand_total_incl_vat':
                        $excelCols[$field] = $prefix . number_format($order_tax_data['grand_total'], 2, ',', '.');
                        break;
                    case 'payment_status':
                        $excelCols[$field] = ($row['paid']) ? $this->pi_getLL('paid') : $this->pi_getLL('unpaid');
                        break;
                    case 'shipping_method':
                        $excelCols[$field] = $row['shipping_method_label'];
                        break;
                    case 'shipping_cost_excl_vat':
                        $excelCols[$field] = $prefix . number_format($row['shipping_method_costs'], 2, ',', '.');
                        break;
                    case 'shipping_cost_incl_vat':
                        $excelCols[$field] = $prefix . number_format($row['shipping_method_costs'] + $order_tmp['orders_tax_data']['shipping_tax'], 2, ',', '.');
                        break;
                    case 'shipping_cost_vat_rate':
                        $excelCols[$field] = ($order_tmp['orders_tax_data']['shipping_total_tax_rate'] * 100) . '%';
                        break;
                    case 'payment_method':
                        $excelCols[$field] = $row['payment_method_label'];
                        break;
                    case 'payment_cost_excl_vat':
                        $excelCols[$field] = $prefix . number_format($row['payment_method_cost'], 2, ',', '.');
                        break;
                    case 'payment_cost_incl_vat':
                        $excelCols[$field] = $prefix . number_format($row['payment_method_cost'] + $order_tmp['orders_tax_data']['payment_tax'], 2, ',', '.');
                        break;
                    case 'payment_cost_vat_rate':
                        $excelCols[$field] = ($order_tmp['orders_tax_data']['payment_total_tax_rate'] * 100) . '%';
                        break;
                    case 'order_products':
                        $max_cols_num = ($post_data['maximum_number_of_order_products'] ? $post_data['maximum_number_of_order_products'] : 25);
                        $order_products = $order_tmp['products'];
                        $prod_ctr = 0;
                        foreach ($order_products as $product_tmp) {
                            if ($prod_ctr >= $max_cols_num) {
                                break;
                            }
                            $excelCols['product_id' . $prod_ctr] = $product_tmp['products_id'];
                            if (!empty($product_tmp['products_model'])) {
                                $excelCols['product_name' . $prod_ctr] = $product_tmp['products_name'] . ' (' . $product_tmp['products_model'] . ')';
                            } else {
                                $excelCols['product_name' . $prod_ctr] = $product_tmp['products_name'];
                            }
                            $excelCols['product_qty' . $prod_ctr] = $product_tmp['qty'];
                            $excelCols['product_final_price_excl_tax' . $prod_ctr] = number_format($product_tmp['final_price'], 2, ',', '.');
                            $excelCols['product_final_price_incl_tax' . $prod_ctr] = number_format($product_tmp['final_price'] + $product_tmp['products_tax_data']['total_tax'], 2, ',', '.');
                            $excelCols['product_price_total_excl_tax' . $prod_ctr] = number_format($product_tmp['final_price'] * $product_tmp['qty'], 2, ',', '.');
                            $excelCols['product_price_total_incl_tax' . $prod_ctr] = number_format(($product_tmp['final_price'] + $product_tmp['products_tax_data']['total_tax']) * $product_tmp['qty'], 2, ',', '.');
                            $excelCols['product_tax_rate' . $prod_ctr] = $product_tmp['products_tax'] . '%';
                            $prod_ctr++;
                        }
                        if ($prod_ctr < $max_cols_num) {
                            for ($x = $prod_ctr; $x < $max_cols_num; $x++) {
                                $excelCols['product_id' . $x] = '';
                                $excelCols['product_name' . $x] = '';
                                $excelCols['product_qty' . $x] = '';
                                $excelCols['product_final_price_excl_tax' . $x] = '';
                                $excelCols['product_final_price_incl_tax' . $x] = '';
                                $excelCols['product_price_total_excl_tax' . $x] = '';
                                $excelCols['product_price_total_incl_tax' . $x] = '';
                                $excelCols['product_tax_rate' . $x] = '';
                            }
                        }
                        break;
                    case 'invoice_number':
                        $excelCols[$field] = $row['invoice_id'];
                        break;
                    case 'invoice_create_date':
                        $excelCols[$field] = date('d-m-Y', $row['invoice_crdate']);
                        break;
                    case 'invoice_due_date':
                        if (!empty($row['due_date'])) {
                            $excelCols[$field] = date('d-m-Y', $row['due_date']);
                        } else {
                            $excelCols[$field] = '';
                        }
                        break;
                    case 'invoice_reference':
                        $excelCols[$field] = $row['reference'];
                        break;
                    case 'invoice_status':
                        $excelCols[$field] = $row['invoice_status'];
                        break;
                    case 'invoice_ordered_by':
                        $excelCols[$field] = $row['ordered_by'];
                        break;
                    case 'invoice_to':
                        $excelCols[$field] = $row['invoice_to'];
                        break;
                    case 'invoice_payment_condition':
                        $excelCols[$field] = $row['payment_condition'];
                        break;
                    case 'invoice_paid_status':
                        $excelCols[$field] = $row['invoice_paid'];
                        break;
                    case 'invoice_reversal_status':
                        $excelCols[$field] = $row['reversal_invoice'];
                        break;
                    case 'invoice_reversal_related_id':
                        $excelCols[$field] = $row['reversal_related_id'];
                        break;
                    case 'order_total_vat':
                        $excelCols[$field] = $prefix . number_format($order_tax_data['total_orders_tax'], 2, ',', '.');
                        break;
                }
                //hook to let other plugins further manipulate the replacers
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_invoice_export.php']['downloadInvoiceExportFieldIteratorPostProc'])) {
                    $params = array(
                            'field' => &$field,
                            'excelCols' => &$excelCols,
                            'row' => &$row,
                            'counter' => $counter
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_invoice_export.php']['downloadInvoiceExportFieldIteratorPostProc'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
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
            $objPHPExcel->getSheet(0)->setTitle('Invoices Export');
            $objPHPExcel->getActiveSheet()->fromArray($excelRows);
            $ExcelWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="invoices_export_' . $this->get['invoices_export_hash'] . '.xlsx"');
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
