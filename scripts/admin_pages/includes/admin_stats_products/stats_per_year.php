<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$search_start_time = '';
$search_end_time = '';
$filter = array();
$data_query = array();
if ($this->get['stats_year_sb'] > 0) {
    if ($this->get['stats_year_sb'] != $this->cookie['stats_year_sb']) {
        $this->cookie['stats_year_sb'] = $this->get['stats_year_sb'];
    }
} else {
    $this->cookie['stats_year_sb'] = date("Y");
}
if (!empty($this->get['order_date_from']) && !empty($this->get['order_date_till'])) {
    list($from_date, $from_time) = explode(" ", $this->get['order_date_from']);
    list($fd, $fm, $fy) = explode('/', $from_date);
    list($till_date, $till_time) = explode(" ", $this->get['order_date_till']);
    list($td, $tm, $ty) = explode('/', $till_date);
    $search_start_time = strtotime($fy . '-' . $fm . '-' . $fd . ' ' . $from_time);
    $search_end_time = strtotime($ty . '-' . $tm . '-' . $td . ' ' . $till_time);
    $data_query['where'][] = "o.crdate BETWEEN '" . $search_start_time . "' and '" . $search_end_time . "'";
} else {
    if ($this->cookie['stats_year_sb'] && $this->cookie['stats_year_sb']!=date('Y')) {
        $data_query['where'][] = 'o.crdate BETWEEN ' . strtotime(date($this->cookie['stats_year_sb'].'-01-01 00:00:00')) . ' and ' . strtotime(date($this->cookie['stats_year_sb'].'-12-31 23:59:59'));
    } else {
        $data_query['where'][] = 'o.crdate BETWEEN ' . strtotime(date('Y-01-01 00:00:00')) . ' and ' . time();
    }
}
if ($this->get['orders_status_search'] > 0) {
    $data_query['where'][] = "(o.status='" . $this->get['orders_status_search'] . "')";
}
if (isset($this->get['payment_method']) && $this->get['payment_method'] != 'all') {
    if ($this->get['payment_method'] == 'nopm') {
        $data_query['where'][] = "(o.payment_method is null)";
    } else {
        $data_query['where'][] = "(o.payment_method='" . addslashes($this->get['payment_method']) . "')";
    }
}
if (isset($this->get['shipping_method']) && $this->get['shipping_method'] != 'all') {
    if ($this->get['shipping_method'] == 'nosm') {
        $data_query['where'][] = "(o.shipping_method is null)";
    } else {
        $data_query['where'][] = "(o.shipping_method='" . addslashes($this->get['shipping_method']) . "')";
    }
}
if (isset($this->get['usergroup']) && $this->get['usergroup'] > 0) {
    $data_query['where'][] = ' o.customer_id IN (SELECT uid from fe_users where ' . $GLOBALS['TYPO3_DB']->listQuery('usergroup', $this->get['usergroup'], 'fe_users') . ')';
}
if (isset($this->get['country']) && !empty($this->get['country'])) {
    $data_query['where'][] = "o.billing_country='" . addslashes($this->get['country']) . "'";
}
if ($this->get['payment_status'] == 'paid_only') {
    $data_query['where'][] = "(o.paid='1')";
} else {
    if ($this->get['payment_status'] == 'unpaid_only') {
        $data_query['where'][] = "(o.paid='0')";
    }
}
if (!$this->masterShop) {
    $data_query['where'][] = 'o.page_uid=' . $this->shop_pid;
}
// search processor eol
$filter = array();
if (is_array($data_query['where']) && count($data_query['where'])) {
    $filter[] = '(' . implode(' AND ', $data_query['where']) . ')';
}
$filter[] = 'o.orders_id=op.orders_id';
$str = $GLOBALS['TYPO3_DB']->SELECTquery('sum(op.qty) as total, op.products_name, op.products_id, op.categories_id', // SELECT ...
        'tx_multishop_orders o, tx_multishop_orders_products op', // FROM ...
        implode(' AND ', $filter), // WHERE...
        'op.products_name having total > 0', // GROUP BY...
        'total desc', // ORDER BY...
        '' // LIMIT ...
);
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
    $content .= '
	<table width="100%" cellspacing="0" cellpadding="0" border="0" class="table table-striped table-bordered">
		<thead>
		<tr class="' . $tr_type . '">
			<th valign="top" class="text-right">Qty</td>
			<th valign="top">Product</td>
		</tr>
		</thead><tbody>
	';
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        if ($row['categories_id']) {
            // get all cats to generate multilevel fake url
            $level = 0;
            $cats = mslib_fe::Crumbar($row['categories_id']);
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
            $productLink = mslib_fe::typolink($this->conf['products_detail_page_pid'], '&' . $where . '&products_id=' . $row['products_id'] . '&tx_multishop_pi1[page_section]=products_detail');
        } else {
            $productLink = '';
        }
        $content .= '<tr>';
        $content .= '<td class="text-right">' . number_format(round($row['total'], 1), 0, '', '.') . '</td>';
        $content .= '<td><a href="' . $productLink . '" target="_blank">' . htmlspecialchars($row['products_name']) . '</a></td>';
        $content .= '</tr>';
    }
    $content .= '</tbody></table>';
}
$content = '<div class="panel panel-default"><div class="panel-body">' . mslib_fe::shadowBox($content) . '</div></div>';
?>