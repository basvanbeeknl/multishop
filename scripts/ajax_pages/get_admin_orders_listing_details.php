<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
if ($this->ADMIN_USER) {
    $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] = (int)$this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'];
    $jsonData = array();
    $customer_currency = 0;
    if (is_numeric($this->post['tx_multishop_pi1']['orders_id'])) {
        $str = "SELECT *, o.crdate, o.status, osd.name as orders_status from tx_multishop_orders o left join tx_multishop_orders_status os on o.status=os.id left join tx_multishop_orders_status_description osd on (os.id=osd.orders_status_id AND o.language_id=osd.language_id) where o.orders_id='" . $this->post['tx_multishop_pi1']['orders_id'] . "'";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $order = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
        if ($order['orders_id']) {
            $orders_tax_data = unserialize($order['orders_tax_data']);

            $str2 = "SELECT * from tx_multishop_orders_products where orders_id='" . addslashes($order['orders_id']) . "' order by sort_order asc";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            while (($row_products = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2))) {
                $row_products['attributes'] = array();
                $str3 = "SELECT * from tx_multishop_orders_products_attributes where orders_products_id='" . addslashes($row_products['orders_products_id']) . "' order by orders_products_attributes_id asc";
                $qry3 = $GLOBALS['TYPO3_DB']->sql_query($str3);
                while ($row_products_attributes = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry3)) {
                    $row_products_attributes['attributes_tax_data'] = unserialize($row_products_attributes['attributes_tax_data']);
                    $row_products['attributes'][] = $row_products_attributes;
                }
                $order['products'][] = $row_products;
            }
            $jsonData['content'] = '';
            $jsonData_content = '';
            if (count($order['products'])) {
                // address details:
                $jsonData_content .= '
				<div class="msAdminTooltipOrderDetailsAddressWrapper">
					<div class="row">
					<div class="col-md-4">
					<div class="msAdminTooltipBillingAddressDetails">
						<h3>' . $this->pi_getLL('billing_details') . '</h3>
';
                if ($order['billing_company']) {
                    $jsonData_content .= $order['billing_company'] . '<br />';
                }
                $address_data = array();
                $address_data = $order;
                $address_data['building'] = $order['billing_building'];
                $address_data['address'] = $order['billing_address'];
                $address_data['zip'] = $order['billing_zip'];
                $address_data['city'] = $order['billing_city'];
                $address_data['country'] = $order['billing_country'];
                $billing_address_value = mslib_befe::customerAddressFormat($address_data);
                $customer_edit_link = mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_customer&tx_multishop_pi1[cid]=' . $order['customer_id'] . '&action=edit_customer', 1);
                $jsonData_content .= '<a href="' . $customer_edit_link . '">' . $order['billing_name'] . '</a><br />
				' . $billing_address_value . '<br /><br />';
                if ($order['billing_email']) {
                    $jsonData_content .= $this->pi_getLL('email') . ': <a href="mailto:' . $order['billing_email'] . '">' . $order['billing_email'] . '</a><br />';
                }
                if ($order['billing_telephone']) {
                    $jsonData_content .= $this->pi_getLL('telephone') . ': ' . $order['billing_telephone'] . '<br />';
                }
                if ($order['billing_mobile']) {
                    $jsonData_content .= $this->pi_getLL('mobile') . ': ' . $order['billing_mobile'] . '<br />';
                }
                if ($order['billing_fax']) {
                    $jsonData_content .= $this->pi_getLL('fax') . ': ' . $order['billing_fax'] . '<br />';
                }
                /*
                if ($order['customer_comments']) {
                    $jsonData_content.='<div class="customer_comments">'.$this->pi_getLL('customer_comments').': '.$order['customer_comments'].'</div>';
                }
                */
                $jsonData_content .= '
					</div>
					</div>
					<div class="col-md-4">
					<div class="msAdminTooltipDeliveryAddressDetails">
						<h3>' . $this->pi_getLL('delivery_details') . '</h3>
';
                if ($order['delivery_company']) {
                    $jsonData_content .= $order['delivery_company'] . '<br />';
                }
                $address_data = array();
                $address_data = $order;
                $address_data['building'] = $order['delivery_building'];
                $address_data['address'] = $order['delivery_address'];
                $address_data['zip'] = $order['delivery_zip'];
                $address_data['city'] = $order['delivery_city'];
                $address_data['country'] = $order['delivery_country'];
                $delivery_address_value = mslib_befe::customerAddressFormat($address_data, 'delivery');
                $jsonData_content .= $order['delivery_name'] . '<br />
						' . $delivery_address_value . '<br /><br />';
                if ($order['delivery_email']) {
                    $jsonData_content .= $this->pi_getLL('email') . ': <a href="mailto:' . $order['delivery_email'] . '">' . $order['delivery_email'] . '</a><br />';
                }
                if ($order['delivery_telephone']) {
                    $jsonData_content .= $this->pi_getLL('telephone') . ': ' . $order['delivery_telephone'] . '<br />';
                }
                if ($order['delivery_mobile']) {
                    $jsonData_content .= $this->pi_getLL('mobile') . ': ' . $order['delivery_mobile'] . '<br />';
                }
                if ($order['delivery_fax']) {
                    $jsonData_content .= $this->pi_getLL('fax') . ': ' . $order['delivery_fax'] . '<br />';
                }
                /*
                if ($order['order_memo']) {
                    $jsonData_content.='<div class="order_memo">';
                    $jsonData_content.=$this->pi_getLL('order_memo').': '.$order['order_memo'];
                    $jsonData_content.=($order['memo_crdate']>0 ? '<span class="memo_last_modified">'.$this->pi_getLL('order_memo_last_modified').': '.strftime("%a. %x %X", $order['memo_crdate']).'</span>' : '');
                    $jsonData_content.='</div>';
                }
                */
                $jsonData_content .= '
					</div>
				</div>
				<div class="col-md-4">
';
                if ($order['customer_comments']) {
                    $jsonData_content .= '<div class="customer_comments">' . mslib_befe::bootstrapPanel($this->pi_getLL('customer_comments'), nl2br($order['customer_comments']), 'info') . '</div>';
                }
                if ($order['order_memo']) {
                    $tmpFooter = '';
                    if ($order['memo_crdate']) {
                        $tmpFooter .= '<span class="memo_last_modified">' . $this->pi_getLL('order_memo_last_modified') . ': ' . strftime("%a. %x %X", $order['memo_crdate']) . '</span>';
                    }
                    $jsonData_content .= '<div class="order_memo">' . mslib_befe::bootstrapPanel($this->pi_getLL('order_memo'), $order['order_memo'], 'default', $tmpFooter) . '</div>';
                    $tmpContent = '';
                }
                $jsonData_content .= '
					</div>
				</div>
				';
                $jsonData_content .= '
				<table width="100%" class="table table-bordered">
				<thead>
				<tr>
				<th class="cellFixed cellNoWrap">' . $this->pi_getLL('products_id') . '</th>
				<th class="cellFixed cellNoWrap">' . $this->pi_getLL('qty') . '</th>
				' . ($this->ms['MODULES']['SHOW_QTY_DELIVERED'] > 0 ? '<th class="cellFixed cellNoWrap">' . $this->pi_getLL('order_product_qty_delivered') . '</th>' : '') . '
				<th class="cellFluid">' . $this->pi_getLL('products_name') . '</th>
				<th class="cellFixed cellNoWrap">' . $this->pi_getLL('price') . '</th>
				' . ($this->ms['MODULES']['ENABLE_DISCOUNT_ON_EDIT_ORDER_PRODUCT'] > 0 ? '<th>' . $this->pi_getLL('discount') . '</th>' : '') . '
				<th class="cellFixed cellNoWrap">' . $this->pi_getLL('total_price') . '</th>
				</tr>
				</thead>
				<tbody>
				';
                foreach ($order['products'] as $product) {
                    $order_products_tax_data = unserialize($product['products_tax_data']);
                    if (!$tr_subtype or $tr_subtype == 'even') {
                        $tr_subtype = 'odd';
                    } else {
                        $tr_subtype = 'even';
                    }
                    $where = '';
                    if (!$product['categories_id']) {
                        // fix fold old orders that did not have categories id in orders_products table
                        $tmpProduct = mslib_fe::getProduct($product['products_id']);
                        $product['categories_id'] = $tmpProduct;
                    }
                    if ($product['categories_id']) {
                        // get all cats to generate multilevel fake url
                        $level = 0;
                        $cats = mslib_fe::Crumbar($product['categories_id']);
                        $cats = array_reverse($cats);
                        $where = '';
                        if (count($cats) > 0) {
                            foreach ($cats as $cat) {
                                $where .= "categories_id[" . $level . "]=" . $cat['id'] . "&";
                                $level++;
                            }
                            $where = substr($where, 0, (strlen($where) - 1));
                        }
                        // get all cats to generate multilevel fake url eof
                        $productLink = mslib_fe::typolink($this->conf['products_detail_page_pid'], '&' . $where . '&products_id=' . $product['products_id'] . '&tx_multishop_pi1[page_section]=products_detail');
                    } else {
                        $productLink = '';
                    }
                    $productsName = '<a href="' . $productLink . '" target="_blank">' . $product['products_name'] . '</a>';
                    if ($product['products_description']) {
                        $productsName .= '<br/>' . nl2br($product['products_description']);
                    }
                    $jsonData_content .= '<tr class="' . $tr_subtype . '">
					<td class="cellFixed cellNoWrap text-right"><a href="' . $productLink . '" target="_blank">' . $product['products_id'] . '</a></td>
					<td class="cellFixed cellNoWrap text-right">' . round($product['qty'], 13) . '</td>
					' . ($this->ms['MODULES']['SHOW_QTY_DELIVERED'] > 0 ? '<td class="cellFixed cellNoWrap text-right">' . round($product['qty_delivered'], 13) . '</td>' : '') . '
					<td class="cellFluid">' . $productsName . '</td>';
                    $normal_price = $product['final_price'];
                    if ($this->ms['MODULES']['ENABLE_DISCOUNT_ON_EDIT_ORDER_PRODUCT']) {
                        $normal_price = $product['final_price'] + $product['discount_amount'];
                    }
                    if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                        $jsonData_content .= '<td class="cellFixed cellNoWrap text-right">' . mslib_fe::amount2Cents(($normal_price +$order_products_tax_data['total_tax']), $customer_currency, 1, 0) . '</td>';
                    } else {
                        $jsonData_content .= '<td class="cellFixed cellNoWrap text-right">' . mslib_fe::amount2Cents($normal_price, $customer_currency, 1, 0) . '</td>';
                    }
                    if ($this->ms['MODULES']['ENABLE_DISCOUNT_ON_EDIT_ORDER_PRODUCT']) {
                        $jsonData_content .= '<td class="cellFixed cellNoWrap text-right">' . mslib_fe::amount2Cents($product['discount_amount'], $customer_currency, 1, 0) . '</td>';
                    }
                    if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                        $jsonData_content .= '<td class="cellFixed cellNoWrap text-right">' . mslib_fe::amount2Cents($product['qty'] * ($product['final_price'] +$order_products_tax_data['total_tax']), $customer_currency, 1, 0) . '</td>';
                    } else {
                        $jsonData_content .= '<td class="cellFixed cellNoWrap text-right">' . mslib_fe::amount2Cents($product['qty'] * $product['final_price'], $customer_currency, 1, 0) . '</td>';
                    }
                    $jsonData_content .= '</tr>';
                    if (count($product['attributes'])) {
                        foreach ($product['attributes'] as $attributes) {
                            $jsonData_content .= '<tr class="' . $tr_subtype . '">
							<td class="text-right">&nbsp;</td>
							<td class="text-right">&nbsp;</td>
							 ' . ($this->ms['MODULES']['SHOW_QTY_DELIVERED'] > 0 ? '<td class="cellFixed cellNoWrap text-right"></td>' : '') . '
							<td>' . $attributes['products_options'] . ': ' . $attributes['products_options_values'] . '</td>';
                            if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                                $jsonData_content .= '<td class="text-right noWrap">' . ($attributes['price_prefix'] == '-' ? '- ' : '') . mslib_fe::amount2Cents(($attributes['price_prefix'] . $attributes['options_values_price']) + $attributes['attributes_tax_data']['tax'], $customer_currency, 1, 0) . '</td>';
                            } else {
                                $jsonData_content .= '<td class="text-right noWrap">' . ($attributes['price_prefix'] == '-' ? '- ' : '') . mslib_fe::amount2Cents($attributes['options_values_price'], $customer_currency, 1, 0) . '</td>';
                            }
                            if ($this->ms['MODULES']['ENABLE_DISCOUNT_ON_EDIT_ORDER_PRODUCT']) {
                                $jsonData_content .= '<td class="text-right noWrap">&nbsp;</td>';
                            }
                            if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                                $jsonData_content .= '<td class="text-right noWrap">' . ($attributes['price_prefix'] == '-' ? '- ' : '') . mslib_fe::amount2Cents($product['qty'] * (($attributes['price_prefix'] . $attributes['options_values_price']) + $attributes['attributes_tax_data']['tax']), $customer_currency, 1, 0) . '</td>';
                            } else {
                                $jsonData_content .= '<td class="text-right noWrap">' . ($attributes['price_prefix'] == '-' ? '- ' : '') . mslib_fe::amount2Cents($product['qty'] * $attributes['options_values_price'], $customer_currency, 1, 0) . '</td>';
                            }
                            $jsonData_content .= '</tr>';
                        }
                    }
                }
                $colspan = 4;
                if ($this->ms['MODULES']['ENABLE_DISCOUNT_ON_EDIT_ORDER_PRODUCT']) {
                    $colspan += 1;
                }
                if ($this->ms['MODULES']['SHOW_QTY_DELIVERED']) {
                    $colspan += 1;
                }
                $jsonData_content .= '
                <tr class="removeTableCellBorder msAdminSubtotalRow">
                    <td colspan="' . $colspan . '">&nbsp;</td>
                </tr>';
                if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . $colspan . '" class="text-right">' . $this->pi_getLL('sub_total') . '</td>
						<td class="text-right">' . mslib_fe::amount2Cents($orders_tax_data['sub_total'], $customer_currency, 1, 0) . '</td>
					</tr>';
                } else {
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . $colspan . '" class="text-right">' . $this->pi_getLL('sub_total') . '</td>
						<td class="text-right">' . mslib_fe::amount2Cents($orders_tax_data['sub_total_excluding_vat'], $customer_currency, 1, 0) . '</td>
					</tr>';
                }
                if ($order['shipping_method_label']) {
                    if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                        $jsonData_content .= '
						<tr class="removeTableCellBorder msAdminSubtotalRow">
							<td colspan="' . $colspan . '" class="text-right">' . htmlspecialchars($order['shipping_method_label']) . '</td>
							<td class="text-right">' . mslib_fe::amount2Cents($order['shipping_method_costs'] + $orders_tax_data['shipping_tax'], $customer_currency, 1, 0) . '</td>
						</tr>';
                    } else {
                        $jsonData_content .= '
						<tr class="removeTableCellBorder msAdminSubtotalRow">
							<td colspan="' . $colspan . '" class="text-right">' . htmlspecialchars($order['shipping_method_label']) . '</td>
							<td class="text-right">' . mslib_fe::amount2Cents($order['shipping_method_costs'], $customer_currency, 1, 0) . '</td>
						</tr>';
                    }
                }
                if ($order['payment_method_label']) {
                    if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                        $jsonData_content .= '
						<tr class="removeTableCellBorder msAdminSubtotalRow">
							<td colspan="' . $colspan . '" class="text-right">' . htmlspecialchars($order['payment_method_label']) . '</td>
							<td class="text-right">' . mslib_fe::amount2Cents($order['payment_method_costs'] + $orders_tax_data['payment_tax'], 0) . '</td>
						</tr>';
                    } else {
                        $jsonData_content .= '
						<tr class="removeTableCellBorder msAdminSubtotalRow">
							<td colspan="' . $colspan . '" class="text-right">' . htmlspecialchars($order['payment_method_label']) . '</td>
							<td class="text-right">' . mslib_fe::amount2Cents($order['payment_method_costs'], 0) . '</td>
						</tr>';
                    }
                }
                /*if (!$order['payment_method_label']) {
                    $jsonData_content.='
                    <tr class="removeTableCellBorder msAdminSubtotalRow">
                        <td colspan="3" class="text-right">'.$this->pi_getLL('vat').'</td>
                        <td class="text-right">'.mslib_fe::amount2Cents($order['subtotal_tax'],1,0).'</td>
                    </tr>';
                }*/
                if ($order['discount'] > 0) {
                    $coupon_code = '';
                    if (!empty($order['coupon_code'])) {
                        $coupon_code = ' (code: ' . $order['coupon_code'] . ')';
                    }
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . $colspan . '" class="text-right">' . htmlspecialchars($this->pi_getLL('discount')) . $coupon_code . '</td>
						<td class="text-right">' . mslib_fe::amount2Cents($order['discount'], $customer_currency, 1, 0) . '</td>
					</tr>
					';
                }
                if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] > 0) {
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . ($colspan + 1) . '"><hr></td>
					</tr>';
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . $colspan . '" class="text-right"><strong>' . (!$orders_tax_data['total_orders_tax'] ? ucfirst($this->pi_getLL('total_excl_vat')) : ucfirst($this->pi_getLL('total'))) . '</strong></td>
						<td class="text-right"><strong>' . mslib_fe::amount2Cents($order['grand_total'], $customer_currency, 1, 0) . '</strong></td>
					</tr>';
                    //if ($order['payment_method_label']) {
                    $jsonData_content .= '
						<tr class="removeTableCellBorder msAdminSubtotalRow">
							<td colspan="' . $colspan . '" class="text-right">' . $this->pi_getLL('included_vat_amount') . '</td>
							<td class="text-right">' . mslib_fe::amount2Cents($orders_tax_data['total_orders_tax'], $customer_currency, 1, 0) . '</td>
						</tr>';
                    //}
                } else {
                    //if ($order['payment_method_label']) {
                    $jsonData_content .= '
						<tr class="removeTableCellBorder msAdminSubtotalRow">
							<td colspan="' . $colspan . '" class="text-right">' . $this->pi_getLL('vat') . '</td>
							<td class="text-right">' . mslib_fe::amount2Cents($orders_tax_data['total_orders_tax'], $customer_currency, 1, 0) . '</td>
						</tr>';
                    //}
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . ($colspan + 1) . '"><hr></td>
					</tr>';
                    $jsonData_content .= '
					<tr class="removeTableCellBorder msAdminSubtotalRow">
						<td colspan="' . $colspan . '" class="text-right"><strong>' . ucfirst($this->pi_getLL('total')) . '</strong></td>
						<td class="text-right"><strong>' . mslib_fe::amount2Cents($order['grand_total'], $customer_currency, 1, 0) . '</strong></td>
					</tr>';
                }
                $jsonData_content .= '</tbody></table>
				';
                $extraDetails = array();
                if ($order['cruser_id']) {
                    $user = mslib_fe::getUser($order['cruser_id']);
                    if ($user['username']) {
                        $customer_edit_link = mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_customer&tx_multishop_pi1[cid]=' . $user['uid'] . '&action=edit_customer');
                        $extraDetails['right'][] = $this->pi_getLL('ordered_by') . ': <strong><a href="' . $customer_edit_link . '">' . $user['username'] . '</a></strong><br />';
                    }
                }
                if ($order['ip_address']) {
                    $extraDetails['right'][] = $this->pi_getLL('ip_address', 'IP address') . ': <strong>' . $order['ip_address'] . '</strong><br />';
                }
                if ($order['http_host']) {
                    $extraDetails['right'][] = $this->pi_getLL('order_on', 'Besteld op') . ': <strong>' . $order['http_host'] . '</strong><br />';
                }
                if ($order['http_referer']) {
                    $domain = parse_url($order['http_referer']);
                    if ($domain['host']) {
                        $extraDetails['left'][] = $this->pi_getLL('referrer', 'Referrer') . ': <strong><a href="' . $order['http_referer'] . '" target="_blank" rel="noreferrer">' . $domain['host'] . '</a></strong>';
                    }
                }
                if (count($extraDetails)) {
                    $jsonData_content .= '<hr><div class="row">';
                    $jsonData_content .= '<div id="adminOrderDetailsFooter" class="col-md-6">';
                    if (is_array($extraDetails['left']) && count($extraDetails['left'])) {
                        $jsonData_content .= implode("", $extraDetails['left']);
                    }
                    $jsonData_content .= '</div><div class="col-md-6 text-right">';
                    if (is_array($extraDetails['right']) && count($extraDetails['right'])) {
                        $jsonData_content .= implode("", $extraDetails['right']);
                    }
                    $jsonData_content .= '</div>';
                }
                $jsonData_content .= '</div>';
            }
            if (!empty($jsonData_content)) {
                $jsonData['title'] = '<h3 class="popover-title">' . $this->pi_getLL('admin_label_cms_marker_order_number') . ': ' . $order['orders_id'] . ' <a href="' . mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_order&orders_id=' . $order['orders_id'] . '&action=edit_order') . '" class="btn btn-sm btn-success">' . $this->pi_getLL('go_to_order_details') . '</a> <a href="' . mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_order&orders_id=' . $order['orders_id'] . '&action=edit_order&tx_multishop_pi1[new_order]=true') . '" class="btn btn-sm btn-primary" target="_blank">' . $this->pi_getLL('admin_label_create_order') . '</a></h3>';
                $jsonData['content'] = '<div class="popover-content">' . $jsonData_content . '</div>';
            }
        } else {
            $jsonData_content = 'No data.';
            $jsonData['content'] = $jsonData_content;
        }
    }
    echo json_encode($jsonData, ENT_NOQUOTES);
}
exit();
