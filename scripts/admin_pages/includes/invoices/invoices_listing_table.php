<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$counter = 0;
$totalAmount = 0;
$invoiceItem = '';
foreach ($invoices as $invoice) {
    $grandTotalColumnName = 'grand_total';
    if (isset($this->get['tx_multishop_pi1']['excluding_vat'])) {
        $grandTotalColumnName = 'grand_total_excluding_vat';
    }
    $cb_ctr++;
    $master_shop_col = '';
    if ($this->masterShop) {
        $master_shop_col = '<td class="cellName">' . mslib_fe::getShopNameByPageUid($invoice['page_uid']) . '</td>';
    }
    if ($invoice['reversal_invoice']) {
        $totalAmount = $totalAmount - $invoice[$grandTotalColumnName];
    } else {
        $totalAmount = $totalAmount + $invoice[$grandTotalColumnName];
    }
    $paid_status = '';
    /** PLAN TO REMOVED **/
    /*
     if (!$invoice['paid']) {
        $paid_status.='<span class="admin_status_red" alt="'.$this->pi_getLL('has_not_been_paid').'" title="'.$this->pi_getLL('has_not_been_paid').'"></span>';
        $paid_status.='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]='.$this->ms['page'].'&tx_multishop_pi1[action]=update_selected_invoices_to_paid&selected_invoices[]='.$invoice['id']).'" onclick="return confirm(\''.sprintf($this->pi_getLL('admin_label_are_you_sure_that_invoice_x_has_been_paid'), $invoice['invoice_id']).'\')"><span class="admin_status_green disabled" alt="'.$this->pi_getLL('change_to_paid').'" title="'.$this->pi_getLL('change_to_paid').'"></span></a>';
    } else {
        $paid_status.='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]='.$this->ms['page'].'&tx_multishop_pi1[action]=update_selected_invoices_to_not_paid&selected_invoices[]='.$invoice['id']).'" onclick="return confirm(\''.sprintf($this->pi_getLL('admin_label_are_you_sure_that_invoice_x_has_not_been_paid'), $invoice['invoice_id']).'\')"><span class="admin_status_red disabled" alt="'.$this->pi_getLL('change_to_not_paid').'" title="'.$this->pi_getLL('change_to_not_paid').'"></span></a>';
        $paid_status.='<span class="admin_status_green" alt="'.$this->pi_getLL('has_been_paid').'" title="'.$this->pi_getLL('has_been_paid').'"></span>';
    }
    */
    /** PLAN TO REMOVED **/
    if (!$invoice['paid']) {
        $paid_status .= '<span class="admin_status_red" alt="' . $this->pi_getLL('has_not_been_paid') . '" title="' . $this->pi_getLL('has_not_been_paid') . '"></span>';
        $paid_status .= '<a href="#" class="update_to_paid" data-order-id="' . $invoice['orders_id'] . '" data-invoice-id="' . $invoice['id'] . '" data-invoice-nr="' . $invoice['invoice_id'] . '"><span class="admin_status_green disabled" alt="' . $this->pi_getLL('change_to_paid') . '" title="' . $this->pi_getLL('change_to_paid') . '"></span></a>';
    } else {
        $paidToolTipInfoArray = array();
        if ($invoice['payment_method_label']) {
            $paidToolTipInfoArray[] = $this->pi_getLL('payment_method') . ': ' . $invoice['payment_method_label'];
        }
        if ($invoice['orders_paid_timestamp']) {
            $paidToolTipInfoArray[] = $this->pi_getLL('date_paid') . ': ' . strftime("%x", $invoice['orders_paid_timestamp']);
        }
        $paidToolTipInfo = '';
        if (count($paidToolTipInfoArray)) {
            $paidToolTipInfo = implode('<br/>', $paidToolTipInfoArray);
        }
        $paid_status .= '<a href="#" class="update_to_unpaid" data-order-id="' . $invoice['orders_id'] . '" data-invoice-id="' . $invoice['id'] . '" data-invoice-nr="' . $invoice['invoice_id'] . '"><span class="admin_status_red disabled" alt="' . $this->pi_getLL('change_to_not_paid') . '" title="' . $this->pi_getLL('change_to_not_paid') . '"></span></a>';
        $paid_status .= '<span class="admin_status_green" data-toggle="tooltip" title="' . htmlspecialchars($paidToolTipInfo) . '"></span>';
    }
    //
    $actionButtons = array();
    //$actionButtons['email']='<a href="#" data-dialog-title="Are you sure?" data-dialog-body="Are you sure?" class="disabled msBtnConfirm btn btn-sm btn-default"><i class="fa fa-phone-square"></i> '.ucfirst($this->pi_getLL('','e-mail')).'</a> ';
    //$actionButtons['credit']='<a href="#" data-dialog-title="Are you sure?" data-dialog-body="Are you sure?" class="disabled msBtnConfirm btn btn-sm btn-default'.($invoice['reversal_invoice']?' disabled':'').'"><i class="fa fa fa-refresh"></i> '.$this->pi_getLL('','Credit').'</a>';
    //
    $action_button = '';
    if (count($actionButtons)) {
        $action_button .= '<div class="btn-group">';
        foreach ($actionButtons as $actionButton) {
            $action_button .= $actionButton;
        }
        $action_button .= '</div>';
    }
    //
    $markerArray = array();
    $markerArray['ORDER_URL'] = mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_order&orders_id=' . $invoice['orders_id'] . '&action=edit_order');
    $markerArray['INVOICE_CTR'] = $cb_ctr;
    $markerArray['INVOICES_URL'] = $markerArray['ORDER_URL'];
    $markerArray['DOWNLOAD_INVOICES_URL'] = mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=download_invoice&tx_multishop_pi1[hash]=' . $invoice['hash']);
    $markerArray['INVOICES_INTERNAL_ID'] = $invoice['id'];
    $markerArray['INVOICES_ID'] = $invoice['invoice_id'];
    $markerArray['INVOICES_ORDER_ID'] = $invoice['orders_id'];
    $markerArray['MASTER_SHOP'] = $master_shop_col;
    $customer_edit_link = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_customer&tx_multishop_pi1[cid]=' . $invoice['customer_id'] . '&action=edit_customer', 1);
    $customerNameArray = array();
    if ($invoice['ordered_by']) {
        $customerNameArray[] = $invoice['ordered_by'];
    }
    if (!count($customerNameArray)) {
        $customerNameArray[] = $invoice['username'];
    }
    $link_name = implode('<br/>', $customerNameArray);
    $markerArray['INVOICES_CUSTOMER_NAME'] = '<a href="' . $customer_edit_link . '">' . $link_name . '</a>';
    $markerArray['INVOICES_ORDER_DATE'] = strftime('%x', $invoice['crdate']);
    $markerArray['INVOICES_PAYMENT_METHOD'] = $invoice['payment_method_label'];
    $markerArray['INVOICES_PAYMENT_CONDITION'] = $invoice['payment_condition'];
    //$markerArray['INVOICES_AMOUNT']=mslib_fe::amount2Cents(($invoice['reversal_invoice'] ? '-' : '').$invoice['amount'], 0);
    //$markerArray['INVOICES_AMOUNT']=mslib_fe::amount2Cents(($invoice['reversal_invoice'] ? '-' : '').$invoice['grand_total'], 0);
    if (strpos($invoice[$grandTotalColumnName], '-') !== false && $invoice['reversal_invoice']) {
        $invoice_grand_total = str_replace('-', '', $invoice[$grandTotalColumnName]);
        $markerArray['INVOICES_AMOUNT'] = mslib_fe::amount2Cents($invoice_grand_total, 0);
    } else {
        $markerArray['INVOICES_AMOUNT'] = mslib_fe::amount2Cents(($invoice['reversal_invoice'] ? '-' : '') . $invoice[$grandTotalColumnName], 0);
    }
    $markerArray['INVOICES_DATE_LAST_SENT'] = ($invoice['date_mail_last_sent'] > 0 ? strftime('%x', $invoice['date_mail_last_sent']) : '');
    $markerArray['INVOICES_PAID_STATUS'] = $paid_status;
    $markerArray['INVOICES_ACTION'] = $action_button;
    $markerArray['CUSTOM_MARKER_0_BODY'] = '';
    $markerArray['CUSTOM_MARKER_1_BODY'] = '';
    // custom page hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/invoices/invoices_listing_table.php']['adminInvoicesListingTmplIteratorPreProc'])) {
        $params = array(
                'markerArray' => &$markerArray,
                'invoice' => &$invoice
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/invoices/invoices_listing_table.php']['adminInvoicesListingTmplIteratorPreProc'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    // custom page hook that can be controlled by third-party plugin eof
    $invoiceItem .= $this->cObj->substituteMarkerArray($subparts['invoices_listing'], $markerArray, '###|###');
}
// pagination
if (!$this->ms['nopagenav'] and $pageset['total_rows'] > $this->ms['MODULES']['ORDERS_LISTING_LIMIT']) {
    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_pagination.php');
    $pagination_listing = $tmp;
}
// pagination eof
$actions = array();
$actions['download_selected_invoices'] = $this->pi_getLL('download_selected_invoices', 'Download selected invoices');
$actions['mail_selected_invoices_to_merchant'] = $this->pi_getLL('mail_selected_invoices_to_merchant', 'Mail selected invoices to merchant');
$actions['update_selected_invoices_to_paid'] = $this->pi_getLL('update_selected_invoices_to_paid');
$actions['update_selected_invoices_to_not_paid'] = $this->pi_getLL('update_selected_invoices_to_not_paid');
$actions['create_reversal_invoice'] = $this->pi_getLL('create_reversal_invoice_for_selected_invoices');
// extra action
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_invoices.php']['adminInvoicesActionSelectboxProc'])) {
    $params = array('actions' => &$actions);
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_invoices.php']['adminInvoicesActionSelectboxProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
$form_fields_listing_block = '
<div class="form-group"><div class="input-group"><select name="tx_multishop_pi1[action]" id="selected_invoices_action" class="form-control">
<option value="">' . $this->pi_getLL('choose_action') . '</option>
';
foreach ($actions as $key => $value) {
    $form_fields_listing_block .= '<option value="' . $key . '">' . $value . '</option>';
}
$form_fields_listing_block .= '
	</select>
	<span class="input-group-btn">
	<input name="tx_multishop_pi1[mailto]" type="text" value="' . $this->ms['MODULES']['STORE_EMAIL'] . '" id="msadmin_invoices_mailto" />
	<input class="btn btn-success" type="submit" name="submit" value="' . $this->pi_getLL('submit_form') . '" ></input>
	</span>
	</div>
	</div>';
$master_shop = '';
if ($this->masterShop) {
    $master_shop = '<th width="75">' . $this->pi_getLL('store') . '</th>';
}
$subpartArray = array();
$subpartArray['###HEADER_INVOICES_NUMBER###'] = $this->pi_getLL('invoice_number');
$subpartArray['###HEADER_INVOICES_ORDER_ID###'] = $this->pi_getLL('orders_id');
$subpartArray['###HEADER_MASTER_SHOP###'] = $master_shop;
$subpartArray['###HEADER_INVOICES_CUSTOMER###'] = $this->pi_getLL('customers');
$subpartArray['###HEADER_INVOICES_ORDER_DATE###'] = $this->pi_getLL('order_date');
$subpartArray['###HEADER_INVOICES_PAYMENT_METHOD###'] = $this->pi_getLL('payment_method');
$subpartArray['###HEADER_INVOICES_PAYMENT_CONDITION###'] = $this->pi_getLL('payment_condition');
$subpartArray['###HEADER_INVOICES_AMOUNT###'] = $this->pi_getLL('amount');
$subpartArray['###HEADER_INVOICES_DATE_LAST_SENT###'] = $this->pi_getLL('date_last_sent');
$subpartArray['###HEADER_INVOICES_PAID_STATUS###'] = $this->pi_getLL('admin_paid');
$subpartArray['###HEADER_INVOICES_ACTION###'] = $this->pi_getLL('action');
//
$subpartArray['###PAGINATION###'] = $pagination_listing;
$subpartArray['###INVOICES_LISTING###'] = $invoiceItem;
$subpartArray['###FORM_FIELDS_LISTING_ACTION_BLOCK###'] = $form_fields_listing_block;
//
$subpartArray['###FOOTER_INVOICES_NUMBER###'] = $this->pi_getLL('invoice_number');
$subpartArray['###FOOTER_INVOICES_ORDER_ID###'] = $this->pi_getLL('orders_id');
$subpartArray['###FOOTER_MASTER_SHOP###'] = $master_shop;
$subpartArray['###FOOTER_INVOICES_CUSTOMER###'] = $this->pi_getLL('customers');
$subpartArray['###FOOTER_INVOICES_ORDER_DATE###'] = $this->pi_getLL('order_date');
$subpartArray['###FOOTER_INVOICES_PAYMENT_METHOD###'] = $this->pi_getLL('payment_method');
$subpartArray['###FOOTER_INVOICES_PAYMENT_CONDITION###'] = $this->pi_getLL('payment_condition');
$subpartArray['###FOOTER_INVOICES_AMOUNT###'] = mslib_fe::amount2Cents($totalAmount, 0);
$subpartArray['###FOOTER_INVOICES_DATE_LAST_SENT###'] = $this->pi_getLL('date_last_sent');
$subpartArray['###FOOTER_INVOICES_PAID_STATUS###'] = $this->pi_getLL('admin_paid');
$subpartArray['###FOOTER_INVOICES_ACTION###'] = $this->pi_getLL('action');
$subpartArray['###CUSTOM_MARKER_0_HEADER###'] = '';
$subpartArray['###CUSTOM_MARKER_0_FOOTER###'] = '';
$subpartArray['###CUSTOM_MARKER_1_HEADER###'] = '';
$subpartArray['###CUSTOM_MARKER_1_FOOTER###'] = '';
$subpartArray['###SHOP_PID2###'] = $this->shop_pid;
$subpartArray['###FORM_POST_ACTION_URL###'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_invoices');
// custom page hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/invoices/invoices_listing_table.php']['adminInvoicesListingTmplPreProc'])) {
    $params = array(
            'subpartArray' => &$subpartArray,
            'invoice' => &$invoice
    );
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/invoices/invoices_listing_table.php']['adminInvoicesListingTmplPreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
$invoices_results = $this->cObj->substituteMarkerArrayCached($subparts['invoices_results'], array(), $subpartArray);
?>