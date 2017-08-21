<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
if ($this->ms['MODULES']['DELETE_PDF_PACKING_SLIP_AFTER_BEING_DOWNLOADED']) {
    $this->get['tx_multishop_pi1']['forceRecreate'] = 1;
}
$order = array();
if ($this->get['tx_multishop_pi1']['order_id'] && $this->ADMIN_USER) {
    $order_id = $this->get['tx_multishop_pi1']['order_id'];
    $order = mslib_fe::getOrder($order_id);
} elseif ($this->get['tx_multishop_pi1']['order_hash']) {
    $order_hash = $this->get['tx_multishop_pi1']['order_hash'];
    $order = mslib_fe::getOrder($order_hash, 'hash');
}
if (!count($order)) {
    exit();
}
$pdfFileName = 'packingslip_' . $order['hash'] . '.pdf';
$pdfFileName = 'packingslip_' . $order['hash'] . '.pdf';
$pdfFilePath = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/' . $pdfFileName;
if ($this->ms['MODULES']['DELETE_PDF_PACKING_SLIP_AFTER_BEING_DOWNLOADED'] && file_exists($pdfFilePath)) {
    unlink($pdfFilePath);
}
if (($this->get['tx_multishop_pi1']['forceRecreate'] || !file_exists($pdfFilePath)) && $order['hash']) {
    $orders_tax_data = $order['orders_tax_data'];
    if ($order['orders_id']) {
        // now parse all the objects in the tmpl file
        if ($this->conf['admin_packingslip_pdf_tmpl_path']) {
            $template = $this->cObj->fileResource($this->conf['admin_packingslip_pdf_tmpl_path']);
        } else {
            $template = $this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/admin_packingslip_pdf.tmpl');
        }
        $markerArray = array();
        $markerArray['###BARKODE_IMG_URL###'] = $this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid.',2002', 'tx_multishop_pi1[page_section]=generateBarkode&tx_multishop_pi1[orders_id]='.$order['orders_id'].'&tx_multishop_pi1[string]='.$order['orders_id'],1);
        $markerArray['###GENDER_SALUTATION###'] = mslib_fe::genderSalutation($order['billing_gender']);
        if ($this->ms['MODULES']['PACKINGSLIP_PDF_HEADER_IMAGE']) {
            $imageLocation = $this->ms['MODULES']['PACKINGSLIP_PDF_HEADER_IMAGE'];
            if (!file_exists($imageLocation) && file_exists($this->DOCUMENT_ROOT . $imageLocation)) {
                // relative filepath
                $imageLocation = $this->FULL_HTTP_URL . $imageLocation;
            }
            $markerArray['###PACKINGSLIP_HEADER_BACKGROUND_IMAGE###'] = ' <img src="' . $imageLocation . '" style="width: 100%"/>';
        } else {
            $markerArray['###PACKINGSLIP_HEADER_BACKGROUND_IMAGE###'] = '';
        }
        if ($this->ms['MODULES']['PACKINGSLIP_PDF_FOOTER_IMAGE']) {
            $imageLocation = $this->ms['MODULES']['PACKINGSLIP_PDF_FOOTER_IMAGE'];
            if (!file_exists($imageLocation) && file_exists($this->DOCUMENT_ROOT . $imageLocation)) {
                // relative filepath
                $imageLocation = $this->FULL_HTTP_URL . $imageLocation;
            }
            $markerArray['###PACKINGSLIP_FOOTER_BACKGROUND_IMAGE###'] = ' <img src="' . $imageLocation . '" style="width: 100%"/>';
        } else {
            $markerArray['###PACKINGSLIP_FOOTER_BACKGROUND_IMAGE###'] = '';
        }
        $markerArray['###LABEL_PACKINGSLIP_HEADER###'] = $this->pi_getLL('packing_list');
        if (!empty($order['delivery_company'])) {
            $markerArray['###DELIVERY_COMPANY###'] = '<strong>' . $order['delivery_company'] . '</strong><br/>';
        } else {
            $markerArray['###DELIVERY_COMPANY###'] = '';
        }
        if (!empty($order['delivery_department'])) {
            $markerArray['###DELIVERY_COMPANY###'] .= '<strong>' . $order['delivery_department'] . '</strong><br/>';
        }
        if (!empty($order['delivery_building'])) {
            $markerArray['###DELIVERY_BUILDING###'] = '<strong>' . $order['delivery_building'] . '</strong><br/>';
        } else {
            $markerArray['###DELIVERY_BUILDING###'] = '';
        }
        $markerArray['###DELIVERY_NAME###'] = $order['delivery_name'];
        $markerArray['###DELIVERY_BUILDING###'] = $order['delivery_building'];
        if (strpos($template, '###DELIVERY_BUILDING###') === false && $order['delivery_building'] != '') {
            $order['delivery_address'] = $order['delivery_building'] . '<br/>' . $order['delivery_address'];
        }
        $markerArray['###DELIVERY_ADDRESS###'] = $order['delivery_address'];
        $markerArray['###DELIVERY_ZIP###'] = $order['delivery_zip'];
        $markerArray['###DELIVERY_CITY###'] = mslib_befe::strtoupper($order['delivery_city']);
        $markerArray['###DELIVERY_COUNTRY###'] = '';
        if (mslib_befe::strtolower($order['billing_country']) != mslib_befe::strtolower($this->tta_shop_info['country'])) {
            // ONLY PRINT COUNTRY IF THE COUNTRY OF THE CUSTOMER IS DIFFERENT THAN FROM THE SHOP
            $markerArray['###DELIVERY_COUNTRY###'] = mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order['delivery_country']);
        }
        $markerArray['###LABEL_CUSTOMER_ID###'] = $this->pi_getLL('admin_customer_id');
        $markerArray['###CUSTOMER_ID###'] = $order['customer_id'];
        $markerArray['###LABEL_ORDER_ID###'] = $this->pi_getLL('orders_id');
        $markerArray['###ORDER_ID###'] = $order['orders_id'];
        $markerArray['###LABEL_ORDER_DATE###'] = $this->pi_getLL('admin_order_date');
        $markerArray['###ORDER_DATE###'] = strftime("%x", $order['crdate']);
        $markerArray['###LABEL_PACKINGSLIP_PAYMENT_METHOD###'] = '';
        $markerArray['###PACKINGSLIP_PAYMENT_METHOD###'] = '';
        if ($order['payment_method_label']) {
            $markerArray['###LABEL_PACKINGSLIP_PAYMENT_METHOD###'] = $this->pi_getLL('payment_method');
            $markerArray['###PACKINGSLIP_PAYMENT_METHOD###'] = $order['payment_method_label'];
        }
        $markerArray['###LABEL_PACKINGSLIP_SHIPPING_METHOD###'] = '';
        $markerArray['###PACKINGSLIP_SHIPPING_METHOD###'] = '';
        if ($order['shipping_method_label']) {
            $markerArray['###LABEL_PACKINGSLIP_SHIPPING_METHOD###'] = $this->pi_getLL('shipping_method');
            $markerArray['###PACKINGSLIP_SHIPPING_METHOD###'] = $order['shipping_method_label'];
        }
        $markerArray['###PACKINGSLIP_ORDER_DETAILS###'] = mslib_befe::printInvoiceOrderDetailsTable($order, 0, $prefix, 1, 'packingslip');
        $markerArray['###LABEL_YOUR_VAT_ID###'] = '';
        $markerArray['###YOUR_VAT_ID###'] = '';
        if ($order['billing_vat_id']) {
            $markerArray['###LABEL_YOUR_VAT_ID###'] = $this->pi_getLL('your_vat_id');
            $markerArray['###YOUR_VAT_ID###'] = $order['billing_vat_id'];
        }
        $markerArray['###BILLING_TELEPHONE###'] = $order['billing_telephone'];
        $markerArray['###BILLING_MOBILE###'] = $order['billing_mobile'];
        $markerArray['###DELIVERY_TELEPHONE###'] = $order['delivery_telephone'];
        $markerArray['###DELIVERY_MOBILE###'] = $order['delivery_mobile'];
        $markerArray['###CUSTOMER_COMMENTS###'] = $order['customer_comments'];
        // CMS HEADER
        $markerArray['###PACKINGSLIP_CONTENT_HEADER_MESSAGE###'] = '';
        $cmsKeys = array();
        if ($order['payment_method']) {
            $cmsKeys[] = 'pdf_packingslip_header_message_' . $order['payment_method'];
        }
        $cmsKeys[] = 'pdf_packingslip_header_message';
        foreach ($cmsKeys as $cmsKey) {
            $page = mslib_fe::getCMScontent($cmsKey, $GLOBALS['TSFE']->sys_language_uid);
            if (!empty($page[0]['content'])) {
                $markerArray['###PACKINGSLIP_CONTENT_HEADER_MESSAGE###'] = '<div class="content_header_message">
				<br/><br/><br/>
				' . $page[0]['content'] . '
				</div>';
            }
            if (is_array($page)) {
                break;
            }
        }
        // CMS FOOTER
        $markerArray['###PACKINGSLIP_CONTENT_FOOTER_MESSAGE###'] = '';
        $cmsKeys = array();
        if ($order['payment_method']) {
            $cmsKeys[] = 'pdf_packingslip_footer_message_' . $order['payment_method'];
        }
        $cmsKeys[] = 'pdf_packingslip_footer_message';
        foreach ($cmsKeys as $cmsKey) {
            $page = mslib_fe::getCMScontent($cmsKey, $GLOBALS['TSFE']->sys_language_uid);
            if (!empty($page[0]['content'])) {
                $markerArray['###PACKINGSLIP_CONTENT_FOOTER_MESSAGE###'] = '<div class="content_footer_message" style="page-break-before:auto">
				<br/><br/><br/>
				' . $page[0]['content'] . '
				</div>';
            }
            if (is_array($page)) {
                break;
            }
        }
        // MARKERS
        $array1 = array();
        $array2 = array();
        $array1[] = '###BILLING_FULL_NAME###';
        $array2[] = $order['billing_name'];
        $array1[] = '###FULL_NAME###';
        $array2[] = $order['billing_name'];
        $array1[] = '###BILLING_NAME###';
        $array2[] = $order['billing_name'];
        $array1[] = '###BILLING_FIRST_NAME###';
        $array2[] = $order['billing_first_name'];
        $array1[] = '###BILLING_LAST_NAME###';
        $array2[] = preg_replace('/\s+/', ' ', $order['billing_middle_name'] . ' ' . $order['billing_last_name']);
        $array1[] = '###BILLING_EMAIL###';
        $array2[] = $order['billing_email'];
        $array1[] = '###BILLING_TELEPHONE###';
        $array2[] = $order['billing_telephone'];
        $array1[] = '###BILLING_MOBILE###';
        $array2[] = $order['billing_mobile'];
        // full delivery name
        $array1[] = '###DELIVERY_NAME###';
        $array2[] = $order['delivery_name'];
        $array1[] = '###DELIVERY_FULL_NAME###';
        $array2[] = $order['delivery_name'];
        $array1[] = '###DELIVERY_FIRST_NAME###';
        $array2[] = $order['delivery_first_name'];
        $array1[] = '###DELIVERY_LAST_NAME###';
        $array2[] = preg_replace('/\s+/', ' ', $order['delivery_middle_name'] . ' ' . $order['delivery_last_name']);
        $array1[] = '###DELIVERY_EMAIL###';
        $array2[] = $order['delivery_email'];
        $array1[] = '###DELIVERY_TELEPHONE###';
        $array2[] = $order['delivery_telephone'];
        $array1[] = '###DELIVERY_MOBILE###';
        $array2[] = $order['delivery_mobile'];
        $array1[] = '###CUSTOMER_EMAIL###';
        $array2[] = $order['billing_email'];
        $time = $order['crdate'];
        $long_date = strftime($this->pi_getLL('full_date_format'), $time);
        $array1[] = '###ORDER_DATE_LONG###'; // ie woensdag 23 juni, 2010
        $array2[] = $long_date;
        // backwards compatibility
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
        $array1[] = '###ORDER_STATUS###';
        $array2[] = $order['orders_status'];
        $array1[] = '###TRACK_AND_TRACE_CODE###';
        $array2[] = $order['track_and_trace_code'];
        $array1[] = '###BILLING_BUILDING###';
        $array2[] = $order['billing_building'];
        $array1[] = '###BILLING_ADDRESS###';
        $array2[] = $billing_address;
        $array1[] = '###DELIVERY_BUILDING###';
        $array2[] = $order['delivery_building'];
        $array1[] = '###DELIVERY_ADDRESS###';
        $array2[] = $delivery_address;
        $array1[] = '###CUSTOMER_ID###';
        $array2[] = $order['customer_id'];
        $array1[] = '###ORDER_DETAILS###';
        $array2[] = $ORDER_DETAILS;
        $array1[] = '###SHIPPING_METHOD###';
        $array2[] = $order['shipping_method_label'];
        $array1[] = '###PAYMENT_METHOD###';
        $array2[] = $order['payment_method_label'];
        $array1[] = '###EXPECTED_DELIVERY_DATE###';
        if ($order['expected_delivery_date'] > 0) {
            $array2[] = strftime("%x", $order['expected_delivery_date']);
        } else {
            $array2[] = '';
        }
        $array1[] = '###CUSTOMER_COMMENTS###';
        $array2[] = $order['customer_comments'];
        $array1[] = '###PAYMENT_CONDITION###';
        if ($order['payment_condition']) {
            $array2[] = $order['payment_condition'] . ' ' . $this->pi_getLL('days');
        } else {
            $array2[] = '';
        }
        $array1[] = '###PAYMENT_DUE_DATE###';
        if ($order['payment_condition']) {
            $array2[] = strftime("%x", strtotime('+' . $order['payment_condition'] . ' day', $order['crdate']));
        } else {
            $array2[] = '';
        }
        $array1[] = '###GENDER_SALUTATION###';
        $array2[] = mslib_fe::genderSalutation($order['billing_gender']);
        //hook to let other plugins further manipulate the replacers
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['mailOrderReplacersPostProc'])) {
            $params = array(
                    'array1' => &$array1,
                    'array2' => &$array2,
                    'order' => &$order,
                    'mail_template' => $mail_template
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['mailOrderReplacersPostProc'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        if ($markerArray['###PACKINGSLIP_CONTENT_HEADER_MESSAGE###']) {
            $markerArray['###PACKINGSLIP_CONTENT_HEADER_MESSAGE###'] = str_replace($array1, $array2, $markerArray['###PACKINGSLIP_CONTENT_HEADER_MESSAGE###']);
        }
        if ($markerArray['###PACKINGSLIP_CONTENT_FOOTER_MESSAGE###']) {
            $markerArray['###PACKINGSLIP_CONTENT_FOOTER_MESSAGE###'] = str_replace($array1, $array2, $markerArray['###PACKINGSLIP_CONTENT_FOOTER_MESSAGE###']);
        }
        $markerArray['###LABEL_PACKINGSLIP_PAYMENT_CONDITION###'] = '';
        $markerArray['###PACKINGSLIP_PAYMENT_CONDITION###'] = '';
        if ($order['payment_condition']) {
            $markerArray['###LABEL_PACKINGSLIP_PAYMENT_CONDITION###'] = $this->pi_getLL('payment_condition');
            $markerArray['###PACKINGSLIP_PAYMENT_CONDITION###'] = $order['payment_condition'] . ' ' . $this->pi_getLL('days');
        }
        $markerArray['###STORE_URL###'] = $this->FULL_HTTP_URL;
        $markerArray['###STORE_DOMAIN###'] = $this->server['HTTP_HOST'];
        $markerArray['###STORE_TELEPHONE###'] = $this->tta_shop_info['phone'];
        $markerArray['###STORE_COMPANY###'] = $this->tta_shop_info['company'];
        $markerArray['###STORE_ADDRESS###'] = $this->tta_shop_info['address'];
        $markerArray['###STORE_ZIP###'] = $this->tta_shop_info['zip'];
        $markerArray['###STORE_CITY###'] = $this->tta_shop_info['city'];
        $markerArray['###STORE_COUNTRY###'] = $this->tta_shop_info['country'];
        $markerArray['###STORE_LOCALIZED_COUNTRY###'] = mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $this->tta_shop_info['country']);
        $markerArray['###STORE_BANK_NAME###'] = $this->tta_shop_info['tx_multishop_bank_name'];
        $markerArray['###STORE_BANK_IBAN###'] = $this->tta_shop_info['tx_multishop_iban'];
        $markerArray['###STORE_BANK_BIC###'] = $this->tta_shop_info['tx_multishop_bic'];
        $markerArray['###STORE_VAT_ID###'] = $this->tta_shop_info['tx_multishop_vat_id'];
        $markerArray['###STORE_COC_ID###'] = $this->tta_shop_info['tx_multishop_coc_id'];
        $markerArray['###STORE_ADMINISTRATION_EMAIL###'] = $this->tta_shop_info['email'];
        // MARKERS EOL
        //hook to let other plugins further manipulate the replacers
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_packingslip.php']['downloadPackingslipTemplateMarkerPreProc'])) {
            $params = array(
                    'cmsKeys' => &$cmsKeys,
                    'order' => &$order,
                    'markerArray' => &$markerArray
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/download_packingslip.php']['downloadPackingslipTemplateMarkerPreProc'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        $tmpcontent = $this->cObj->substituteMarkerArray($template, $markerArray);
        if ($this->ADMIN_USER && $this->get['tx_multishop_pi1']['debug']) {
            echo $tmpcontent;
            exit();
        }
        // debug html output
        include(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'res/dompdf/dompdf_config.inc.php');
        //include(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop').'res/dompdf/dompdf_config.custom.php');
        $content = $tmpcontent;
        $settings = array();
        if ($this->ms['MODULES']['PACKINGSLIP_PDF_PAGE_NUMBERING_SETTINGS']) {
            $settings = explode(',', $this->ms['MODULES']['PACKINGSLIP_PDF_PAGE_NUMBERING_SETTINGS']);
        } else {
            $settings[] = 'arial';
            $settings[] = 'bold';
            $settings[] = '11';
            $settings[] = '500';
            $settings[] = '795';
            $settings[] = '0:0:0';
        }
        if (!$settings[5]) {
            $settings[5] = '0:0:0';
        }
        $dompdf = new DOMPDF();
        $dompdf->set_paper('A4');
        $dompdf->load_html($content, 'UTF-8');
        $dompdf->render();
        // ADD PAGE NUMBER IN FOOTER
        $canvas = $dompdf->get_canvas();
        $font = Font_Metrics::get_font($settings[0], $settings[1]);
        $canvas->page_text($settings[3], $settings[4], $this->pi_getLL('page', 'page') . ' {PAGE_NUM} ' . $this->pi_getLL('of', 'of') . ' {PAGE_COUNT}', $font, $settings[2], explode(':', $settings[5]));
        // SAVE AS FILE
        if ($this->get['tx_multishop_pi1']['forceRecreate'] && file_exists($pdfFilePath)) {
            unlink($pdfFilePath);
        }
        file_put_contents($pdfFilePath, $dompdf->output(array('compress' => 0)));
    }
}
if (file_exists($pdfFilePath)) {
    header("Content-type:application/pdf");
    readfile($pdfFilePath);
}
exit();
?>