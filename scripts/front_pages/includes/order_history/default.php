<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
if (mslib_fe::loggedin()) {
    switch ($this->get['tx_multishop_pi1']['page_section']) {
        case 'order_details':
            if (is_numeric($this->get['tx_multishop_pi1']['orders_id']) and $GLOBALS["TSFE"]->fe_user->user['uid']) {
                $order=mslib_fe::getOrder($this->get['tx_multishop_pi1']['orders_id']);
                if ($order['customer_id']==$GLOBALS["TSFE"]->fe_user->user['uid']) {
                    $content.='<h1>'.$this->pi_getLL('orders_id').': '.$order['orders_id'].'</h1>';
                    $content.=mslib_fe::printOrderDetailsTable($order, 'order_history_site');
                    $content.='
					<div id="bottom-navigation">
						<a href="'.mslib_fe::typolink('', '').'" class="msFrontButton prevState arrowLeft arrowPosLeft"><span>'.$this->pi_getLL('back').'</span></a>
						';
                    if ($this->ms['MODULES']['ENABLE_REORDER_FEATURE_IN_ACCOUNT_ORDER_HISTORY']) {
                        $content.='
						<div id="navigation">
							<a href="'.mslib_fe::typolink('', 'tx_multishop_pi1[re-order]=1&tx_multishop_pi1[orders_id]='.$order['orders_id']).'" class="msFrontButton continueState arrowRight arrowPosLeft"><input type="submit" value="'.htmlspecialchars($this->pi_getLL('re-order')).'" /></a>
						</div>
						';
                    }
                    $content.='
					</div>					
					';
                }
            }
            break;
        default:
            if ($this->ms['MODULES']['ENABLE_REORDER_FEATURE_IN_ACCOUNT_ORDER_HISTORY'] && is_numeric($this->get['tx_multishop_pi1']['orders_id']) and $this->get['tx_multishop_pi1']['re-order']) {
                $order=mslib_fe::getOrder($this->get['tx_multishop_pi1']['orders_id']);
                if ($order['customer_id']==$GLOBALS['TSFE']->fe_user->user['uid']) {
                    foreach ($order['products'] as $product) {
                        $this->post=array();
                        $this->post['products_id']=$product['products_id'];
                        $this->post['quantity']=number_format($product['qty']);
                        if (is_array($product['attributes']) and count($product['attributes'])) {
                            foreach ($product['attributes'] as $attribute) {
                                if ($attribute['products_options_values_id']) {
                                    $value=$attribute['products_options_values_id'];
                                } else {
                                    $value=$attribute['products_options_values'];
                                }
                                $this->post['attributes'][$attribute['products_options_id']][]=$value;
                            }
                        }
                        require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop').'pi1/classes/class.tx_mslib_cart.php');
                        $mslib_cart=\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
                        $mslib_cart->init($this);
                        $mslib_cart->updateCart();
                    }
                    header('Location: '.\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->FULL_HTTP_URL.mslib_fe::typolink($this->conf['shoppingcart_page_pid'], '&tx_multishop_pi1[page_section]=shopping_cart')));
                    exit();
                }
            }
            $this->ms['MODULES']['ORDERS_LISTING_LIMIT']=15;
            if (isset($this->get['limit']) && $this->get['limit']>0) {
                $this->ms['MODULES']['ORDERS_LISTING_LIMIT'] = $this->get['limit'];
            } else {
                $this->get['limit']=15;
            }
            if (is_numeric($this->get['p'])) {
                $p=$this->get['p'];
            }
            if ($p>0) {
                $offset=(((($p)*$this->ms['MODULES']['ORDERS_LISTING_LIMIT'])));
            } else {
                $p=0;
                $offset=0;
            }
            $tmp='';
            $filter=array();
            $from=array();
            $having=array();
            $match=array();
            $orderby=array();
            $where=array();
            $orderby=array();
            $select=array();
            $select[]='o.*, osd.name as orders_status';
            $orderby[]='o.orders_id desc';
            $filter[]='o.is_proposal=0';
            $filter[]='o.deleted=0';
            $filter[]='o.customer_id='.$GLOBALS['TSFE']->fe_user->user['uid'];
            $pageset=mslib_fe::getOrdersPageSet($filter, $offset, $this->get['limit'], $orderby, $having, $select, $where, $from);
            $tmporders=$pageset['orders'];
            if (!$this->hideHeader) {
                $tmp.='<h2>'.$this->pi_getLL('account_order_history').'</h2>';
            }
            if ($pageset['total_rows']>0) {
                $tmp.='<table id="account_orders_history_listing" class="table table-bordered table-striped">';
                $tmp.='
				<thead>
				<tr>
				<th class="cell_orders_id" nowrap>'.$this->pi_getLL('orders_id').'</th>
				<th class="cell_amount">'.$this->pi_getLL('amount').'</th>
				<th class="cell_date">'.$this->pi_getLL('order_date').'</th>
				';
                if ($this->ms['MODULES']['ADMIN_INVOICE_MODULE']) {
                    $tmp.='<th class="cell_invoice">'.$this->pi_getLL('invoice').'</th>';
                }
                //	$tmp.='<th class="cell_shipping_method">'.$this->pi_getLL('shipping_method').'</th>';
                //	$tmp.='<th class="cell_payment_method">'.$this->pi_getLL('payment_method').'</th>';
                $tmp.='<th class="cell_order_status">'.$this->pi_getLL('status').'</th>';
                if ($this->ms['MODULES']['ENABLE_REORDER_FEATURE_IN_ACCOUNT_ORDER_HISTORY']) {
                    $tmp.='<th class="cell_action">&nbsp;</th>';
                }
                $tmp.='</tr></thead><tbody>';
                $tr_type='even';
                foreach ($tmporders as $order) {
                    if (!$tr_type or $tr_type=='even') {
                        $tr_type='odd';
                    } else {
                        $tr_type='even';
                    }
                    $tmp.='<tr class="'.$tr_type.'">';
                    $tmp.='<td align="right" nowrap class="cell_orders_id">
					<a href="'.mslib_fe::typolink('', 'tx_multishop_pi1[page_section]=order_details&tx_multishop_pi1[orders_id]='.$order['orders_id']).'">'.$order['orders_id'].'</a></td>';
                    $tmp.='<td align="right" nowrap class="cell_amount">'.mslib_fe::amount2Cents(mslib_fe::getOrderTotalPrice($order['orders_id'])).'</td>';
                    $tmp.='<td align="center" nowrap class="cell_date">'.strftime("%x", $order['crdate']).'</td>';
                    if ($this->ms['MODULES']['ADMIN_INVOICE_MODULE']) {
                        $tmp.='<td align="center" nowrap class="cell_invoice">
						';
                        $invoice=mslib_fe::getInvoice($order['orders_id'], 'orders_id');
                        if ($invoice['id']) {
                            $tmp.='<a href="'.$this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid.',2002', 'tx_multishop_pi1[page_section]=download_invoice&tx_multishop_pi1[hash]='.$invoice['hash']).'" target="_blank" class="msfront_download_invoice" title="download invoice">'.$this->pi_getLL('download').'</a>';
                        }
                        $tmp.='
						</td>';
                    }
                    //		$tmp.='<td align="left" nowrap>'.$order['shipping_method_label'].'</td>';
                    //		$tmp.='<td align="left" nowrap>'.$order['payment_method_label'].'</td>';
                    $tmp.='<td align="left" nowrap class="cell_order_status">'.$order['orders_status'].'</td>';
                    if ($this->ms['MODULES']['ENABLE_REORDER_FEATURE_IN_ACCOUNT_ORDER_HISTORY']) {
                        $tmp.='<td align="center" nowrap class="cell_action">';
                        $tmp.='<a href="'.mslib_fe::typolink('', 'tx_multishop_pi1[re-order]=1&tx_multishop_pi1[orders_id]='.$order['orders_id']).'" class="msfront_reorder" title="'.htmlspecialchars($this->pi_getLL('re-order')).'">'.$this->pi_getLL('re-order').'</a>';
                        $tmp.='</td>';
                    }
                    $tmp.='</tr>';
                }
                $tmp.='</tbody></table>';
                $limit_per_page=$this->get['limit'];
                // pagination
                if ($pageset['total_rows']>$this->ms['MODULES']['ORDERS_LISTING_LIMIT']) {
                    $content.=$tmp;
                    if (!isset($this->ms['MODULES']['PRODUCTS_LISTING_PAGINATION_TYPE']) || $this->ms['MODULES']['PRODUCTS_LISTING_PAGINATION_TYPE']=='default') {
                        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop').'scripts/front_pages/includes/products_listing_pagination.php');
                    } else {
                        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop').'scripts/front_pages/includes/products_listing_pagination_with_number.php');
                    }
                    $tmp='';
                }
                // pagination eof
            } else {
                $tmp.=$this->pi_getLL('no_orders_found').'.';
            }
            $content.=$tmp;
            break;
    }
}
?>