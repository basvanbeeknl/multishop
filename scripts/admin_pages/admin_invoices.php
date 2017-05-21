<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$content = '';
if ($this->get['tx_multishop_pi1']['action']) {
    $this->post['tx_multishop_pi1']['action'] = $this->get['tx_multishop_pi1']['action'];
    $this->post['selected_invoices'] = $this->get['selected_invoices'];
}
$postErno = array();
switch ($this->post['tx_multishop_pi1']['action']) {
    case 'download_selected_invoices':
    case 'mail_selected_invoices_to_merchant':
        // send invoices by mail
        if (is_array($this->post['selected_invoices']) and count($this->post['selected_invoices'])) {
            $attachments = array();
            foreach ($this->post['selected_invoices'] as $invoice) {
                if (is_numeric($invoice)) {
                    $invoice = mslib_fe::getInvoice($invoice, 'id');
                    if ($invoice['id']) {
                        // invoice as attachment
                        $invoice_path = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . $invoice['invoice_id'] . '.pdf';
                        $invoice_data = mslib_fe::file_get_contents($this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=download_invoice&tx_multishop_pi1[hash]=' . $invoice['hash']));
                        // write temporary to disk
                        file_put_contents($invoice_path, $invoice_data);
                        $attachments[$invoice['invoice_id']] = $invoice_path;
                    } else {
                        $postErno[] = array(
                                'status' => 'error',
                                'message' => 'Failed to retrieve invoice record id ' . $invoice
                        );
                    }
                }
            }
            if (count($attachments)) {
                // combine all PDF files in 1 (needs GhostScript on the server: yum install ghostscript)
                $combinedPdfFile = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . time() . '_' . uniqid() . '.pdf';
                $prog = \TYPO3\CMS\Core\Utility\CommandUtility::exec('which gs');
                //hook to let other plugins further manipulate the settings
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_befe.php']['overrideGhostScripPath'])) {
                    $params = array(
                            'prog' => &$prog
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_befe.php']['overrideGhostScripPath'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                if ($prog && is_file($prog)) {
                    $cmd = $prog . ' -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=' . $combinedPdfFile . ' ' . implode(' ', $attachments);
                    \TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
                    switch ($this->post['tx_multishop_pi1']['action']) {
                        case 'download_selected_invoices':
                            if (file_exists($combinedPdfFile)) {
                                header("Content-type:application/pdf");
                                readfile($combinedPdfFile);
                                // delete temporary invoice from disk
                                unlink($combinedPdfFile);
                                foreach ($attachments as $attachment) {
                                    unlink($attachment);
                                }
                                exit();
                            }
                            break;
                        case 'mail_selected_invoices_to_merchant':
                            $user = array();
                            $user['name'] = $this->ms['MODULES']['STORE_NAME'];
                            $user['email'] = $this->ms['MODULES']['STORE_EMAIL'];
                            if (mslib_fe::mailUser($user, $this->ms['MODULES']['STORE_NAME'] . ' invoices', $this->ms['MODULES']['STORE_NAME'] . ' invoices', $this->ms['MODULES']['STORE_EMAIL'], $this->ms['MODULES']['STORE_NAME'], array($combinedPdfFile))) {
                                $postErno[] = array(
                                        'status' => 'info',
                                        'message' => 'The following invoices are mailed to ' . $user['email'] . ':<ul><li>' . implode('</li><li>', array_keys($attachments)) . '</li></ul>'
                                );
                            } else {
                                $postErno[] = array(
                                        'status' => 'error',
                                        'message' => 'Failed to mail invoices to: ' . $user['email']
                                );
                            }
                            break;
                    }
                    // delete temporary invoice from disk
                    unlink($combinedPdfFile);
                    foreach ($attachments as $attachment) {
                        unlink($attachment);
                    }
                } else {
                    echo 'gs binary cannot be found. This is needed for merging multiple PDF files as one file.';
                    exit();
                }
            }
        }
        break;
    case 'create_reversal_invoice':
        if (is_array($this->post['selected_invoices']) and count($this->post['selected_invoices'])) {
            foreach ($this->post['selected_invoices'] as $invoice) {
                if (is_numeric($invoice)) {
                    $invoice = mslib_fe::getInvoice($invoice, 'id');
                    if ($invoice['id'] and $invoice['reversal_invoice'] == 0) {
                        if (mslib_fe::generateReversalInvoice($invoice['id'])) {
                            $postErno[] = array(
                                    'status' => 'info',
                                    'message' => 'Invoice ' . $invoice['invoice_id'] . ' has been reversed.'
                            );
                        } else {
                            $postErno[] = array(
                                    'status' => 'error',
                                    'message' => 'Failed to reverse invoice ' . $invoice['invoice_id']
                            );
                        }
                    } else {
                        $postErno[] = array(
                                'status' => 'error',
                                'message' => 'Failed to reverse invoice ' . $invoice['invoice_id'] . ' because this invoice is already a credit invoice'
                        );
                    }
                }
            }
        }
        break;
    case 'update_selected_invoices_to_paid':
    case 'update_selected_invoices_to_not_paid':
        if (is_array($this->post['selected_invoices']) and count($this->post['selected_invoices'])) {
            foreach ($this->post['selected_invoices'] as $invoice) {
                if (is_numeric($invoice)) {
                    $invoice = mslib_fe::getInvoice($invoice, 'id');
                    if ($invoice['id']) {
                        $order = mslib_fe::getOrder($invoice['orders_id']);
                        if ($order['orders_id']) {
                            if ($this->post['tx_multishop_pi1']['action'] == 'update_selected_invoices_to_paid') {
                                if (mslib_fe::updateOrderStatusToPaid($order['orders_id'])) {
                                    $postErno[] = array(
                                            'status' => 'info',
                                            'message' => 'Invoice ' . $invoice['invoice_id'] . ' has been updated to paid.'
                                    );
                                } else {
                                    $postErno[] = array(
                                            'status' => 'error',
                                            'message' => 'Failed to update ' . $invoice['invoice_id'] . ' to paid.'
                                    );
                                }
                            } else {
                                $updateArray = array('paid' => 0);
                                $updateArray['orders_last_modified'] = time();
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders', 'orders_id=' . $order['orders_id'], $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                $updateArray = array('paid' => 0);
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_invoices', 'id=' . $invoice['id'], $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            }
                        } else {
                            // this invoice has no belonging order. This could be true in specific cases so just update the invoice to not paid.
                            if ($this->post['tx_multishop_pi1']['action'] == 'update_selected_invoices_to_paid') {
                                $updateArray = array('paid' => 1);
                            } else {
                                $updateArray = array('paid' => 0);
                            }
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_invoices', 'id=' . $invoice['id'], $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        }
                    }
                }
            }
        }
        break;
    default:
        // post processing by third party plugins
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_invoices.php']['adminInvoicesPostHookProc'])) {
            $params = array(
                    'content' => &$content,
                    'postErno' => &$postErno
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_invoices.php']['adminInvoicesPostHookProc'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        break;
}
if (count($postErno)) {
    $returnMarkup = '
	<div style="display:none" id="msAdminPostMessage">
	<table class="table table-striped table-bordered">
	<thead>
	<tr>
		<th class="text-center">Status</th>
		<th>Message</th>
	</tr>
	</thead>
	<tbody>
	';
    foreach ($postErno as $item) {
        switch ($item['status']) {
            case 'error':
                $item['status'] = '<span class="fa-stack text-danger"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-thumbs-down fa-stack-1x fa-inverse"></i></span>';
                break;
            case 'info':
                $item['status'] = '<span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-thumbs-up fa-stack-1x fa-inverse"></i></span>';
                break;
        }
        $returnMarkup .= '<tr><td class="text-center">' . $item['status'] . '</td><td>' . $item['message'] . '</td></tr>' . "\n";
    }
    $returnMarkup .= '</tbody></table></div>';
    $content .= $returnMarkup;
    $GLOBALS['TSFE']->additionalHeaderData[] = '<script type="text/javascript" data-ignore="1">
	jQuery(document).ready(function ($) {
		$.confirm({
			title: \'\',
			content: $(\'#msAdminPostMessage\').html()
		});
	});
	</script>
	';
}
// now parse all the objects in the tmpl file
if ($this->conf['admin_invoices_tmpl_path']) {
    $template = $this->cObj->fileResource($this->conf['admin_invoices_tmpl_path']);
} else {
    $template = $this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/admin_invoices.tmpl');
}
// Extract the subparts from the template
$subparts = array();
$subparts['template'] = $this->cObj->getSubpart($template, '###TEMPLATE###');
$subparts['invoices_results'] = $this->cObj->getSubpart($subparts['template'], '###RESULTS###');
$subparts['invoices_listing'] = $this->cObj->getSubpart($subparts['invoices_results'], '###INVOICES_LISTING###');
$subparts['invoices_noresults'] = $this->cObj->getSubpart($subparts['template'], '###NORESULTS###');
//
if ($this->get['Search'] and ($this->get['invoice_paid_status'] != $this->cookie['invoice_paid_status'])) {
    $this->cookie['invoice_paid_status'] = $this->get['invoice_paid_status'];
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
if ($this->get['Search'] and ($this->get['invoice_type'] != $this->cookie['invoice_type'])) {
    $this->cookie['invoice_type'] = $this->get['invoice_type'];
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
if ($this->get['Search'] and ($this->get['tx_multishop_pi1']['filter_by_paid_date'] != $this->cookie['filter_by_paid_date'])) {
    $this->cookie['filter_by_paid_date'] = $this->get['tx_multishop_pi1']['filter_by_paid_date'];
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
if ($this->get['Search'] and ($this->get['limit'] != $this->cookie['limit'])) {
    $this->cookie['limit'] = $this->get['limit'];
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
if ($this->cookie['limit']) {
    $this->get['limit'] = $this->cookie['limit'];
} else {
    $this->get['limit'] = 15;
}
$this->ms['MODULES']['ORDERS_LISTING_LIMIT'] = $this->get['limit'];
$option_search = array(
        "orders_id" => $this->pi_getLL('admin_order_id'),
        "invoice" => $this->pi_getLL('admin_invoice_number'),
        "customer_id" => $this->pi_getLL('admin_customer_id'),
        "billing_email" => $this->pi_getLL('admin_customer_email'),
        "delivery_name" => $this->pi_getLL('admin_customer_name'),
    //"crdate"=>$this->pi_getLL('admin_order_date'),
        "billing_zip" => $this->pi_getLL('admin_zip'),
        "billing_city" => $this->pi_getLL('admin_city'),
        "billing_address" => $this->pi_getLL('admin_address'),
        "billing_company" => $this->pi_getLL('admin_company'),
        "shipping_method" => $this->pi_getLL('admin_shipping_method')
    //"payment_method"=>$this->pi_getLL('admin_payment_method')
);
asort($option_search);
$type_search = $this->get['type_search'];
if ($_REQUEST['skeyword']) {
    //  using $_REQUEST cause TYPO3 converts "Command & Conquer" to "Conquer" (the & sign sucks ass)
    $this->get['skeyword'] = $_REQUEST['skeyword'];
    $this->get['skeyword'] = trim($this->get['skeyword']);
    $this->get['skeyword'] = $GLOBALS['TSFE']->csConvObj->utf8_encode($this->get['skeyword'], $GLOBALS['TSFE']->metaCharset);
    $this->get['skeyword'] = $GLOBALS['TSFE']->csConvObj->entities_to_utf8($this->get['skeyword'], true);
    $this->get['skeyword'] = mslib_fe::RemoveXSS($this->get['skeyword']);
}
if (is_numeric($this->get['p'])) {
    $p = $this->get['p'];
}
if ($p > 0) {
    $offset = (((($p) * $this->ms['MODULES']['ORDERS_LISTING_LIMIT'])));
} else {
    $p = 0;
    $offset = 0;
}
// orders search
$option_item = '<select name="type_search" class="invoice_select2"><option value="all">' . $this->pi_getLL('all') . '</option>';
foreach ($option_search as $key => $val) {
    $option_item .= '<option value="' . $key . '" ' . ($this->get['type_search'] == $key ? "selected" : "") . '>' . $val . '</option>';
}
$option_item .= '</select>';
//
$all_orders_status = mslib_fe::getAllOrderStatus($GLOBALS['TSFE']->sys_language_uid);
$orders_status_list = '<select name="orders_status_search" class="invoice_select2"><option value="0" ' . ((!$order_status_search_selected) ? 'selected' : '') . '>' . $this->pi_getLL('all_orders_status', 'All orders status') . '</option>';
if (is_array($all_orders_status)) {
    $order_status_search_selected = false;
    foreach ($all_orders_status as $row) {
        $orders_status_list .= '<option value="' . $row['id'] . '" ' . (($this->get['orders_status_search'] == $row['id']) ? 'selected' : '') . '>' . $row['name'] . '</option>' . "\n";
        if ($this->get['orders_status_search'] == $row['id']) {
            $order_status_search_selected = true;
        }
    }
}
$orders_status_list .= '</select>';
$groups = mslib_fe::getUserGroups($this->conf['fe_customer_pid']);
$customer_groups_input = '';
$customer_groups_input .= '<select id="groups" class="invoice_select2" name="usergroup">' . "\n";
$customer_groups_input .= '<option value="0">' . $this->pi_getLL('all') . ' ' . $this->pi_getLL('usergroup') . '</option>' . "\n";
if (is_array($groups) and count($groups)) {
    foreach ($groups as $group) {
        $customer_groups_input .= '<option value="' . $group['uid'] . '"' . ($this->get['usergroup'] == $group['uid'] ? ' selected="selected"' : '') . '>' . $group['title'] . '</option>' . "\n";
    }
}
$customer_groups_input .= '</select>' . "\n";
// payment method
$payment_methods = array();
$sql = $GLOBALS['TYPO3_DB']->SELECTquery('payment_method, payment_method_label', // SELECT ...
        'tx_multishop_orders', // FROM ...
        '', // WHERE...
        'payment_method', // GROUP BY...
        'payment_method_label', // ORDER BY...
        '' // LIMIT ...
);
$qry = $GLOBALS['TYPO3_DB']->sql_query($sql);
while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
    if (empty($row['payment_method_label'])) {
        $row['payment_method'] = 'nopm';
        $row['payment_method_label'] = 'Empty payment method';
    }
    $payment_methods[$row['payment_method']] = $row['payment_method_label'];
}
$payment_method_input = '';
$payment_method_input .= '<select id="payment_method" class="invoice_select2" name="payment_method">' . "\n";
$payment_method_input .= '<option value="all">' . $this->pi_getLL('all_payment_methods') . '</option>' . "\n";
if (is_array($payment_methods) and count($payment_methods)) {
    foreach ($payment_methods as $payment_method_code => $payment_method) {
        $payment_method_input .= '<option value="' . $payment_method_code . '"' . ($this->get['payment_method'] == $payment_method_code ? ' selected="selected"' : '') . '>' . $payment_method . '</option>' . "\n";
    }
}
$payment_method_input .= '</select>' . "\n";
// shipping method
$shipping_methods = array();
$sql = $GLOBALS['TYPO3_DB']->SELECTquery('shipping_method, shipping_method_label', // SELECT ...
        'tx_multishop_orders', // FROM ...
        '', // WHERE...
        'shipping_method', // GROUP BY...
        'shipping_method_label', // ORDER BY...
        '' // LIMIT ...
);
$qry = $GLOBALS['TYPO3_DB']->sql_query($sql);
while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
    if (empty($row['shipping_method_label'])) {
        $row['shipping_method'] = 'nosm';
        $row['shipping_method_label'] = 'Empty shipping method';
    }
    $shipping_methods[$row['shipping_method']] = $row['shipping_method_label'];
}
$shipping_method_input = '';
$shipping_method_input .= '<select id="shipping_method" class="invoice_select2" name="shipping_method">' . "\n";
$shipping_method_input .= '<option value="all">' . $this->pi_getLL('all_shipping_methods') . '</option>' . "\n";
if (is_array($shipping_methods) and count($shipping_methods)) {
    foreach ($shipping_methods as $shipping_method_code => $shipping_method) {
        $shipping_method_input .= '<option value="' . $shipping_method_code . '"' . ($this->get['shipping_method'] == $shipping_method_code ? ' selected="selected"' : '') . '>' . $shipping_method . '</option>' . "\n";
    }
}
$shipping_method_input .= '</select>' . "\n";
// billing countries
$order_countries = mslib_befe::getRecords('', 'tx_multishop_orders', '', array(), 'billing_country', 'billing_country asc');
$order_billing_country = array();
foreach ($order_countries as $order_country) {
    if (!empty($order_country['billing_country'])) {
        $cn_localized_name = htmlspecialchars(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order_country['billing_country']));
        $order_billing_country[] = '<option value="' . mslib_befe::strtolower($order_country['billing_country']) . '" ' . ((mslib_befe::strtolower($this->get['country']) == strtolower($order_country['billing_country'])) ? 'selected' : '') . '>' . $cn_localized_name . '</option>';
    }
}
ksort($order_billing_country);
$billing_countries_sb = '<select class="invoice_select2" name="country" id="country""><option value="">' . $this->pi_getLL('all_countries') . '</option>' . implode("\n", $order_billing_country) . '</select>';
$limit_selectbox = '<select name="limit" class="form-control">';
$limits = array();
$limits[] = '15';
$limits[] = '20';
$limits[] = '25';
$limits[] = '30';
$limits[] = '40';
$limits[] = '50';
$limits[] = '100';
$limits[] = '150';
$limits[] = '300';
$limits[] = '450';
$limits[] = '600';
$limits[] = '1500';
$limits[] = '3000';
if (!in_array($this->get['limit'], $limits)) {
    $limits[] = $this->get['limit'];
}
foreach ($limits as $limit) {
    $limit_selectbox .= '<option value="' . $limit . '"' . ($limit == $this->get['limit'] ? ' selected' : '') . '>' . $limit . '</option>';
}
$limit_selectbox .= '</select>';
$filter = array();
$from = array();
$having = array();
$match = array();
$orderby = array();
$where = array();
$orderby = array();
$select = array();
if ($this->get['skeyword']) {
    switch ($type_search) {
        case 'all':
            $option_fields = $option_search;
            unset($option_fields['all']);
            unset($option_fields['invoice']);
            unset($option_fields['crdate']);
            unset($option_fields['delivery_name']);
            //print_r($option_fields);
            $items = array();
            foreach ($option_fields as $fields => $label) {
                $items[] = 'o.' . $fields . " LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            }
            $items[] = "o.delivery_name LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            $items[] = "i.invoice_id LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            $filter[] = '(' . implode(" or ", $items) . ')';
            break;
        case 'orders_id':
            $filter[] = " o.orders_id='" . addslashes($this->get['skeyword']) . "'";
            break;
        case 'invoice':
            $filter[] = " i.invoice_id LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        case 'billing_email':
            $filter[] = " o.billing_email LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        case 'delivery_name':
            $filter[] = " o.delivery_name LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        case 'billing_zip':
            $filter[] = " o.billing_zip LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        case 'billing_city':
            $filter[] = " o.billing_city LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        /*case 'billing_country':
            $filter[]=" o.billing_country LIKE '%".addslashes($this->post['skeyword'])."%'";
            break;*/
        case 'billing_address':
            $filter[] = " o.billing_address LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        case 'billing_company':
            $filter[] = " o.billing_company LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        /*case 'shipping_method':
            $filter[]=" (o.shipping_method LIKE '%".addslashes($this->get['skeyword'])."%' or o.shipping_method_label LIKE '%".addslashes($this->get['skeyword'])."%')";
            break;
        case 'payment_method':
            $filter[]=" (o.payment_method LIKE '%".addslashes($this->get['skeyword'])."%' or o.payment_method_label LIKE '%".addslashes($this->get['skeyword'])."%')";
            break;*/
        case 'customer_id':
            $filter[] = " o.customer_id LIKE '%" . addslashes($this->get['skeyword']) . "%'";
            break;
        /*case 'crdate':
            $start_time=date("Y-m-d", strtotime($this->get['skeyword']))." 00:00:00";
            $till_time=date("Y-m-d", strtotime($this->get['skeyword']))." 23:59:59";
            $filter[]=" crdate BETWEEN '".addslashes($start_time)."' and '".addslashes($till_time)."'";
            $ors[]=" ($type_search >= $date_search) ";
            break;*/
    }
}
if (!empty($this->get['invoice_date_from']) && !empty($this->get['invoice_date_till'])) {
    list($from_date, $from_time) = explode(" ", $this->get['invoice_date_from']);
    list($fd, $fm, $fy) = explode('-', $from_date);
    list($till_date, $till_time) = explode(" ", $this->get['invoice_date_till']);
    list($td, $tm, $ty) = explode('-', $till_date);
    $start_time = strtotime($fy . '-' . $fm . '-' . $fd . ' ' . $from_time);
    $end_time = strtotime($ty . '-' . $tm . '-' . $td . ' ' . $till_time);
    if ($this->cookie['filter_by_paid_date']) {
        $filter[] = ' i.reversal_invoice=0';
        $column = 'o.orders_paid_timestamp';
    } else {
        $column = 'i.crdate';
    }
    $filter[] = $column . " BETWEEN '" . $start_time . "' and '" . $end_time . "'";
} else {
    if (!empty($this->post['invoice_date_from'])) {
        list($from_date, $from_time) = explode(" ", $this->post['invoice_date_from']);
        list($fd, $fm, $fy) = explode('/', $from_date);
        $start_time = strtotime($fy . '-' . $fm . '-' . $fd . ' ' . $from_time);
        if ($this->cookie['filter_by_paid_date']) {
            $filter[] = ' i.reversal_invoice=0';
            $column = 'o.orders_paid_timestamp';
        } else {
            $column = 'i.crdate';
        }
        $filter[] = $column . " >= '" . $start_time . "'";
    }
    if (!empty($this->post['invoice_date_till'])) {
        list($till_date, $till_time) = explode(" ", $this->post['invoice_date_till']);
        list($td, $tm, $ty) = explode('/', $till_date);
        $end_time = strtotime($ty . '-' . $tm . '-' . $td . ' ' . $till_time);
        if ($this->cookie['filter_by_paid_date']) {
            $filter[] = ' i.reversal_invoice=0';
            $column = 'o.orders_paid_timestamp';
        } else {
            $column = 'i.crdate';
        }
        $filter[] = $column . " <= '" . $end_time . "'";
    }
}
if (isset($this->get['usergroup']) && $this->get['usergroup'] > 0) {
    $filter[] = ' i.customer_id IN (SELECT uid from fe_users where ' . $GLOBALS['TYPO3_DB']->listQuery('usergroup', $this->get['usergroup'], 'fe_users') . ')';
}
if ($this->get['orders_status_search'] > 0) {
    $filter[] = "(o.status='" . addslashes($this->get['orders_status_search']) . "')";
}
if (isset($this->get['payment_method']) && $this->get['payment_method'] != 'all') {
    if ($this->get['payment_method'] == 'nopm') {
        $filter[] = "(o.payment_method is null)";
    } else {
        $filter[] = "(o.payment_method='" . addslashes($this->get['payment_method']) . "')";
    }
}
if (isset($this->get['shipping_method']) && $this->get['shipping_method'] != 'all') {
    if ($this->get['shipping_method'] == 'nosm') {
        $filter[] = "(o.shipping_method is null)";
    } else {
        $filter[] = "(o.shipping_method='" . addslashes($this->get['shipping_method']) . "')";
    }
}
if ($this->cookie['invoice_paid_status'] == 'paid') {
    $filter[] = "(i.paid='1')";
} else if ($this->cookie['invoice_paid_status'] == 'unpaid') {
    $filter[] = "(i.paid='0')";
}
if ($this->cookie['invoice_type'] == 'credit') {
    $filter[] = "(i.reversal_invoice='1')";
} else if ($this->cookie['invoice_type'] == 'debit') {
    $filter[] = "(i.reversal_invoice='0')";
}
if (isset($this->get['country']) && !empty($this->get['country'])) {
    $filter[] = "o.billing_country='" . addslashes($this->get['country']) . "'";
}
if (isset($this->get['order_customer']) && !empty($this->get['order_customer']) && $this->get['order_customer'] != 99999) {
    $filter[] = "o.customer_id='" . addslashes($this->get['order_customer']) . "'";
}
if (isset($this->get['order_territory']) && !empty($this->get['order_territory']) && $this->get['order_territory'] != 99999) {
    $filter[] = "o.billing_tr_iso_nr='" . addslashes($this->get['order_territory']) . "' or o.billing_tr_parent_iso_nr='" . addslashes($this->get['order_territory']) . "'";
}
if (isset($this->get['ordered_manufacturer']) && !empty($this->get['ordered_manufacturer']) && $this->get['ordered_manufacturer'] != 99999) {
    $filter[] = "o.orders_id in (select op.orders_id from tx_multishop_orders_products op where op.manufacturers_id='" . addslashes($this->get['ordered_manufacturer']) . "')";
}
if (isset($this->get['ordered_category']) && !empty($this->get['ordered_category']) && $this->get['ordered_category'] != 99999) {
    $filter[] = "o.orders_id in (select op.orders_id from tx_multishop_orders_products op where op.categories_id='" . addslashes($this->get['ordered_category']) . "')";
}
if (isset($this->get['ordered_product']) && !empty($this->get['ordered_product']) && $this->get['ordered_product'] != 99999) {
    $filter[] = "o.orders_id in (select op.orders_id from tx_multishop_orders_products op where op.products_id='" . addslashes($this->get['ordered_product']) . "')";
}
if (!$this->masterShop) {
    $filter[] = 'i.page_uid=' . $this->showCatalogFromPage;
}
//$orderby[]='orders_id desc';
$select[] = '*, i.hash';
$orderby[] = 'i.id desc';
$pageset = mslib_fe::getInvoicesPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
$invoices = $pageset['invoices'];
if ($pageset['total_rows'] > 0) {
    $this->ms['MODULES']['PAGESET_LIMIT'] = $this->ms['MODULES']['ORDERS_LISTING_LIMIT'];
    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/invoices/invoices_listing_table.php');
} else {
    $subpartArray = array();
    $subpartArray['###LABEL_NO_RESULTS###'] = $this->pi_getLL('no_invoices_found') . '.';
    $no_results = $this->cObj->substituteMarkerArrayCached($subparts['invoices_noresults'], array(), $subpartArray);
}
//
$subpartArray = array();
$subpartArray['###PAGE_ID###'] = $this->showCatalogFromPage;
$subpartArray['###SHOP_PID###'] = $this->shop_pid;
//
$subpartArray['###UNFOLD_SEARCH_BOX###'] = '';
if ((isset($this->get['type_search']) && !empty($this->get['type_search']) && $this->get['type_search'] != 'all') ||
        (isset($this->get['country']) && !empty($this->get['country'])) ||
        (isset($this->get['usergroup']) && $this->get['usergroup'] > 0) ||
        (isset($this->get['ordered_manufacturer']) && !empty($this->get['ordered_manufacturer'])) ||
        (isset($this->get['order_customer']) && !empty($this->get['order_customer'])) ||
        (isset($this->get['order_territory']) && !empty($this->get['order_territory'])) ||
        (isset($this->get['ordered_category']) && !empty($this->get['ordered_category'])) ||
        (isset($this->get['ordered_product']) && !empty($this->get['ordered_product'])) ||
        (isset($this->get['orders_status_search']) && $this->get['orders_status_search'] > 0) ||
        (isset($this->get['payment_method']) && !empty($this->get['payment_method']) && $this->get['payment_method'] != 'all') ||
        (isset($this->get['shipping_method']) && !empty($this->get['shipping_method']) && $this->get['shipping_method'] != 'all') ||
        (isset($this->get['invoice_date_from']) && !empty($this->get['invoice_date_from'])) ||
        (isset($this->get['invoice_date_till']) && !empty($this->get['invoice_date_till'])) ||
        (isset($this->get['invoice_type']) && !empty($this->get['invoice_type'])) ||
        (isset($this->get['invoice_paid_status']) && !empty($this->get['invoice_paid_status']))
) {
    $subpartArray['###UNFOLD_SEARCH_BOX###'] = ' in';
}
$subpartArray['###LABEL_KEYWORD###'] = ucfirst($this->pi_getLL('keyword'));
$subpartArray['###VALUE_KEYWORD###'] = ($this->get['skeyword'] ? $this->get['skeyword'] : "");
$subpartArray['###LABEL_SEARCH_ON###'] = $this->pi_getLL('search_for');
$subpartArray['###OPTION_ITEM_SELECTBOX###'] = $option_item;
$subpartArray['###LABEL_USERGROUP###'] = $this->pi_getLL('usergroup');
$subpartArray['###USERGROUP_SELECTBOX###'] = $customer_groups_input;
$subpartArray['###LABEL_PAYMENT_METHOD###'] = $this->pi_getLL('payment_method');
$subpartArray['###PAYMENT_METHOD_SELECTBOX###'] = $payment_method_input;
$subpartArray['###LABEL_SHIPPING_METHOD###'] = $this->pi_getLL('shipping_method');
$subpartArray['###SHIPPING_METHOD_SELECTBOX###'] = $shipping_method_input;
$subpartArray['###LABEL_ORDER_STATUS###'] = $this->pi_getLL('order_status');
$subpartArray['###INVOICES_STATUS_LIST_SELECTBOX###'] = $orders_status_list;
$subpartArray['###VALUE_SEARCH###'] = htmlspecialchars($this->pi_getLL('search'));
$subpartArray['###LABEL_FILTER_BY_DATE###'] = $this->pi_getLL('filter_by_date');
$subpartArray['###LABEL_DATE_FROM###'] = $this->pi_getLL('from');
$subpartArray['###LABEL_DATE###'] = $this->pi_getLL('date');
$subpartArray['###VALUE_DATE_FROM###'] = $this->get['invoice_date_from'];
$subpartArray['###LABEL_DATE_TO###'] = $this->pi_getLL('to');
$subpartArray['###VALUE_DATE_TO###'] = $this->get['invoice_date_till'];
$subpartArray['###LABEL_FILTER_BY_PAID_INVOICES_ONLY###'] = $this->pi_getLL('show_paid_invoices_only');
$subpartArray['###FILTER_BY_PAID_INVOICES_ONLY_CHECKED###'] = ($this->cookie['paid_invoices_only'] ? ' checked' : '');
$subpartArray['###LABEL_FILTER_BY_PAID_DATE_ONLY###'] = $this->pi_getLL('filter_by_paid_date');
$subpartArray['###FILTER_BY_PAID_DATE_ONLY_CHECKED###'] = ($this->cookie['filter_by_paid_date'] ? ' checked' : '');
$subpartArray['###EXCLUDING_VAT_LABEL###'] = htmlspecialchars($this->pi_getLL('excluding_vat'));
$subpartArray['###EXCLUDING_VAT_CHECKED###'] = ($this->get['tx_multishop_pi1']['excluding_vat'] ? ' checked' : '');
$subpartArray['###LABEL_RESULTS_LIMIT_SELECTBOX###'] = $this->pi_getLL('limit_number_of_records_to');
$subpartArray['###RESULTS_LIMIT_SELECTBOX###'] = $limit_selectbox;
$subpartArray['###RESULTS###'] = $invoices_results;
$subpartArray['###NORESULTS###'] = $no_results;
$subpartArray['###ADMIN_LABEL_TABS_INVOICES###'] = $this->pi_getLL('admin_invoices');
$subpartArray['###LABEL_COUNTRIES_SELECTBOX###'] = $this->pi_getLL('countries');
$subpartArray['###COUNTRIES_SELECTBOX###'] = $billing_countries_sb;
$subpartArray['###LABEL_ORDERED_MANUFACTURER###'] = $this->pi_getLL('admin_ordered_manufacturer');
$subpartArray['###LABEL_ORDERED_CATEGORY###'] = $this->pi_getLL('admin_ordered_category');
$subpartArray['###LABEL_ORDERED_PRODUCT###'] = $this->pi_getLL('admin_ordered_product');
$subpartArray['###VALUE_ORDERED_MANUFACTURER###'] = $this->get['ordered_manufacturer'];
$subpartArray['###VALUE_ORDERED_CATEGORY###'] = $this->get['ordered_category'];
$subpartArray['###VALUE_ORDERED_PRODUCT###'] = $this->get['ordered_product'];
$subpartArray['###LABEL_USERS###'] = $this->pi_getLL('customer');
$subpartArray['###VALUE_ORDER_CUSTOMER###'] = $this->get['order_customer'];
$subpartArray['###LABEL_TERRITORIES###'] = $this->pi_getLL('territory');
$subpartArray['###VALUE_ORDER_TERRITORY###'] = $this->get['order_territory'];
$subpartArray['###LABEL_ADVANCED_SEARCH###'] = $this->pi_getLL('advanced_search');
$subpartArray['###LABEL_RESET_ADVANCED_SEARCH_FILTER###'] = $this->pi_getLL('reset_advanced_search_filter');
$subpartArray['###DATE_TIME_JS_FORMAT0###'] = $this->pi_getLL('locale_date_format_js');
$subpartArray['###DATE_TIME_JS_FORMAT1###'] = $this->pi_getLL('locale_date_format_js');
// paid status
$paid_status_sb = '<select name="invoice_paid_status" id="invoice_paid_status" class="invoice_select2">
<option value="">' . $this->pi_getLL('all') . '</option>
<option value="paid"' . ($this->get['invoice_paid_status'] == 'paid' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('show_paid_invoices_only') . '</option>
<option value="unpaid"' . ($this->get['invoice_paid_status'] == 'unpaid' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('show_unpaid_invoices_only') . '</option>
</select>';
$subpartArray['###LABEL_INVOICES_PAID_STATUS###'] = $this->pi_getLL('invoice_paid_status');
$subpartArray['###INVOICES_PAID_STATUS_SELECTBOX###'] = $paid_status_sb;
// invoice type
$invoice_type_sb = '<select name="invoice_type" id="invoice_type" class="invoice_select2">
<option value="">' . $this->pi_getLL('all') . '</option>
<option value="credit"' . ($this->get['invoice_type'] == 'credit' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('show_credit_invoices_only') . '</option>
<option value="debit"' . ($this->get['invoice_type'] == 'debit' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('show_debit_invoices_only') . '</option>
</select>';
$subpartArray['###LABEL_INVOICES_TYPE###'] = $this->pi_getLL('invoice_type');
$subpartArray['###INVOICES_TYPE_SELECTBOX###'] = $invoice_type_sb;
// Instantiate admin interface object
$objRef = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:multishop/pi1/classes/class.tx_mslib_admin_interface.php:&tx_mslib_admin_interface');
$objRef->init($this);
$objRef->setInterfaceKey('admin_invoices');
// Header buttons
$headerButtons = array();
// Set header buttons through interface class so other plugins can adjust it
$objRef->setHeaderButtons($headerButtons);
// Get header buttons through interface class so we can render them
$subpartArray['###INTERFACE_HEADER_BUTTONS###'] = $objRef->renderHeaderButtons();
$subpartArray['###BACK_BUTTON###'] = '<hr><div class="clearfix"><a class="btn btn-success msAdminBackToCatalog" href="' . mslib_fe::typolink() . '"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span> ' . $this->pi_getLL('admin_close_and_go_back_to_catalog') . '</a></div></div></div>';
// custom page hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_invoices.php']['adminInvoicesTmplPreProc'])) {
    $params = array(
            'subpartArray' => &$subpartArray
    );
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_invoices.php']['adminInvoicesTmplPreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
$content .= $this->cObj->substituteMarkerArrayCached($subparts['template'], array(), $subpartArray);
$content = '<div class="panel panel-default">' . mslib_fe::shadowBox($content) . '</div>';
$GLOBALS['TSFE']->additionalHeaderData[] = '
<script>
	jQuery(document).ready(function($) {
	    $(document).on("click", "#reset-advanced-search", function(e){
			location.href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_invoices') . '";
			
		});
		' . ($this->get['tx_multishop_pi1']['action'] != 'mail_selected_invoices_to_merchants' ? '$("#msadmin_invoices_mailto").hide();' : '') . '
		$(document).on("click", ".update_to_paid", function(e){
			e.preventDefault();
			var link=$(this).attr("href");
			var order_id=$(this).attr("data-order-id");
			var invoice_nr=$(this).attr("data-invoice-nr");
			var invoice_id=$(this).attr("data-invoice-id");
			var tthis=$(this).parent();
			jQuery.ajax({
				type: "POST",
				url: "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=admin_ajax_edit_order&tx_multishop_pi1[admin_ajax_edit_order]=get_order_payment_methods') . '",
				dataType: \'json\',
				data: "tx_multishop_pi1[order_id]=" + order_id,
				success: function(d) {
					var tmp_confirm_content =\'' . addslashes(sprintf($this->pi_getLL('admin_label_are_you_sure_that_invoice_x_has_been_paid'), '%invoice_nr%')) . '\';
					var confirm_content = \'<div><h3 class="panel-title">\' + tmp_confirm_content . replace(\'%invoice_nr%\', invoice_nr) + \'</h3></div><div class="form-group" id="popup_order_wrapper_listing">\' + d.payment_method_date_purchased + \'</div>\';
					var confirm_box=jQuery.confirm({
						title: \'\',
						content: confirm_content,
						columnClass: \'col-md-6 col-md-offset-4 \',
						confirm: function(){
							var payment_id=this.$b.find("#payment_method_sb_listing").val();
							var date_paid=this.$b.find("#orders_paid_timestamp").val();
							var send_paid_letter=this.$b.find("#send_payment_received_email").prop("checked") ? 1 : 0;
							//
							jQuery.ajax({
								type: "POST",
								url: "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=admin_ajax_edit_order&tx_multishop_pi1[admin_ajax_edit_order]=update_invoice_paid_status_save_popup_value') . '",
								dataType: \'json\',
								data: "tx_multishop_pi1[payment_id]=" + payment_id + "&tx_multishop_pi1[date_paid]=" + date_paid + "&tx_multishop_pi1[order_id]=" + order_id + "&tx_multishop_pi1[invoice_nr]=" + invoice_nr + "&tx_multishop_pi1[invoice_id]=" + invoice_id + "&tx_multishop_pi1[send_paid_letter]=" + send_paid_letter + "&tx_multishop_pi1[action]=update_selected_invoices_to_paid",
								success: function(d) {
									if (d.status=="OK") {
										var return_string = \'<a href="#" class="update_to_unpaid" data-order-id="\' + order_id + \'" data-invoice-nr="\' + invoice_nr + \'" data-invoice-id="\' + invoice_id + \'"><span class="admin_status_red disabled" alt="' . $this->pi_getLL('admin_label_disable') . '"></span></a><span class="admin_status_green" alt="' . $this->pi_getLL('admin_label_enable') . '"></span>\';
									    tthis.html(return_string);
									}
								}
							});
							//window.location =link;
						},
						cancel: function(){},
						confirmButton: \'' . $this->pi_getLL('yes') . '\',
						cancelButton: \'' . $this->pi_getLL('no') . '\',
						backgroundDismiss: false
					});
					confirm_box.$b.find("#orders_paid_timestamp_visual").datepicker({
						dateFormat: "' . $this->pi_getLL('locale_date_format_js', 'yy/mm/dd') . '",
						altField: "#orders_paid_timestamp",
						altFormat: "yy-mm-dd",
						changeMonth: true,
						changeYear: true,
						showOtherMonths: true,
						yearRange: "' . (date("Y") - 15) . ':' . (date("Y") + 2) . '"
					});
				}
			});
		});
		$(document).on("click", ".update_to_unpaid", function(e){
			e.preventDefault();
			var link=$(this).attr("href");
			var order_id=$(this).attr("data-order-id");
			var invoice_nr=$(this).attr("data-invoice-nr");
			var invoice_id=$(this).attr("data-invoice-id");
			var tthis=$(this).parent();
			var tmp_confirm_content =\'' . addslashes(sprintf($this->pi_getLL('admin_label_are_you_sure_that_invoice_x_has_not_been_paid'), '%invoice_nr%')) . '\';
			var confirm_content=\'<div class="confirm_to_unpaid_status">\' + tmp_confirm_content.replace(\'%invoice_nr%\', invoice_nr) + \'</div>\';
			//
			$.confirm({
				title: \'\',
				content: confirm_content,
				columnClass: \'col-md-6 col-md-offset-4 \',
				confirm: function(){
					jQuery.ajax({
						type: "POST",
						url: "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=admin_ajax_edit_order&tx_multishop_pi1[admin_ajax_edit_order]=update_invoice_paid_status_save_popup_value') . '",
						dataType: \'json\',
						data: "tx_multishop_pi1[order_id]=" + order_id + "&tx_multishop_pi1[invoice_id]=" + invoice_id + "&tx_multishop_pi1[invoice_nr]=" + invoice_nr + "&tx_multishop_pi1[action]=update_selected_invoice_to_not_paid",
						success: function(d) {
							if (d.status=="OK") {
								var return_string = \'<span class="admin_status_red" alt="' . $this->pi_getLL('admin_label_disable') . '"></span><a href="#" class="update_to_paid" data-order-id="\' + order_id + \'" data-invoice-nr="\' + invoice_nr + \'" data-invoice-id="\' + invoice_id + \'"><span class="admin_status_green disabled" alt="' . $this->pi_getLL('admin_label_enable') . '"></span></a>\';
								tthis.html(return_string);
							}
						}
					});
				},
				cancel: function(){},
				confirmButton: \'Yes\',
    			cancelButton: \'NO\'
			});
		});
		var ordered_select2 = function (selector, ajax_url) {
			$(selector).select2({
				placeholder: "' . $this->pi_getLL('all') . '",
				minimumInputLength: 0,
				query: function(query) {
					$.ajax(ajax_url, {
						data: {
							q: query.term
						},
						dataType: "json"
					}).done(function(data) {
						query.callback({results: data});
					});
				},
				initSelection: function(element, callback) {
					var id=$(element).val();
					if (id!=="") {
						$.ajax(ajax_url, {
							data: {
								preselected_id: id
							},
							dataType: "json"
						}).done(function(data) {
							callback(data);
						});
					}
				},
				formatResult: function(data){
					if (data.text === undefined) {
						$.each(data, function(i,val){
							return val.text;
						});
					} else {
						return data.text;
					}
				},
				formatSelection: function(data){
					if (data.text === undefined) {
						return data[0].text;
					} else {
						return data.text;
					}
				},
				dropdownCssClass: "orderedProductsDropDownCss",
				escapeMarkup: function (m) { return m; }
			});
		}
		ordered_select2(".ordered_manufacturer", "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_ordered_manufacturers') . '");
		ordered_select2(".ordered_category", "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_ordered_categories') . '");
		ordered_select2(".ordered_product", "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_ordered_products') . '");
		ordered_select2(".order_customer", "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_order_customers') . '");
		ordered_select2(".order_territory", "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_order_territories') . '");
	});
</script>
';
?>