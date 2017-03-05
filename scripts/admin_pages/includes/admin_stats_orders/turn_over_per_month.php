<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
// hook
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_stats_orders/turn_over_per_month.php']['monthlyStatsOrdersPagePreProc'])) {
    $params = array();
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_stats_orders/turn_over_per_month.php']['monthlyStatsOrdersPagePreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
// billing countries
$additional_where = array();
if (!$this->masterShop) {
    $additional_where[] = 'page_uid=\'' . $this->shop_pid . '\'';
}
$order_countries = mslib_befe::getRecords('', 'tx_multishop_orders', '', $additional_where, 'billing_country', 'billing_country asc');
$order_billing_country = array();
foreach ($order_countries as $order_country) {
    $cn_localized_name = htmlspecialchars(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $order_country['billing_country']));
    if (!empty($cn_localized_name)) {
        $order_billing_country[] = '<option value="' . mslib_befe::strtolower($order_country['billing_country']) . '" ' . ((mslib_befe::strtolower($this->get['country']) == strtolower($order_country['billing_country'])) ? 'selected' : '') . '>' . $cn_localized_name . '</option>';
    }
}
ksort($order_billing_country);
$billing_countries_sb = '<select class="invoice_select2" name="country" id="country""><option value="">' . $this->pi_getLL('all_countries') . '</option>' . implode("\n", $order_billing_country) . '</select>';
$all_orders_status = mslib_fe::getAllOrderStatus($GLOBALS['TSFE']->sys_language_uid);
if ($this->post['Search'] and ($this->get['payment_status'] != $this->cookie['payment_status'])) {
    $this->cookie['payment_status'] = $this->get['payment_status'];
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
if ($this->get['stats_year_sb'] > 0) {
    if ($this->get['stats_year_sb'] != $this->cookie['stats_year_sb']) {
        $this->cookie['stats_year_sb'] = $this->get['stats_year_sb'];
    }
} else {
    $this->cookie['stats_year_sb'] = date("Y");
}
if ($this->get['Search']) {
    if ($this->get['paid_orders_only'] and $this->get['paid_orders_only'] != $this->cookie['paid_orders_only']) {
        $this->cookie['paid_orders_only'] = $this->get['paid_orders_only'];
    } else {
        $this->cookie['paid_orders_only'] = '';
    }
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
$sql_year = "select crdate from tx_multishop_orders where deleted=0".((!$this->masterShop) ? ' and page_uid=\''.$this->shop_pid.'\'' : '')." order by orders_id asc limit 1";
$qry_year = $GLOBALS['TYPO3_DB']->sql_query($sql_year);
$row_year = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_year);
if ($row_year['crdate'] > 0) {
    $oldest_year = date("Y", $row_year['crdate']);
} else {
    $oldest_year = date("Y");
}
$current_year = date("Y");
$year_select = '<select name="stats_year_sb" class="form-control" id="stats_year_sb"><option value="">' . $this->pi_getLL('choose') . '</option>';
if ($this->get['order_date_from']) {
    $this->cookie['stats_year_sb'] = '';
}
if ($oldest_year) {
    for ($y = $current_year; $y >= $oldest_year; $y--) {
        if ($this->cookie['stats_year_sb'] == $y) {
            $year_select .= '<option value="' . $y . '" selected="selected">' . $y . '</option>';
        } else {
            $year_select .= '<option value="' . $y . '">' . $y . '</option>';
        }
    }
} else {
    $year_select .= '<option value="' . $current_year . '" selected="selected">' . $current_year . '</option>';
}
$year_select .= '</select>';
$selected_year = 'Y-';
if ($this->cookie['stats_year_sb'] > 0) {
    $selected_year = $this->cookie['stats_year_sb'] . "-";
}
/*$order_status_sb='<h2>'.$this->pi_getLL('order_status').'</h2>';
$all_orders_status=mslib_fe::getAllOrderStatus($GLOBALS['TSFE']->sys_language_uid);
if (is_array($all_orders_status) and count($all_orders_status)) {
	if (is_array($all_orders_status) and count($all_orders_status)) {
		$order_status_sb.='<ul class="horizontal_list order_status_checkbox" id="admin_sales_stats_order_status">';
		foreach ($all_orders_status as $row) {
			$order_status_sb.='<li><input type="checkbox" name="tx_multishop_pi1[status][]" value="'.$row['id'].'" '.(in_array($row['id'], $this->get['tx_multishop_pi1']['status']) ? 'checked="checked"' : '').' class="admin_sales_stats_order_status" id="sales_stats_status_'.$row['id'].'" /><label for="sales_stats_status_'.$row['id'].'">'.$row['name'].'</label></li>';
		}
		$order_status_sb.='</ul>';
	}
}
if (isset($this->get['tx_multishop_pi1']['status']) && count($this->get['tx_multishop_pi1']['status'])>0) {
	$status_where='';
	$tmp=array();
	foreach ($this->get['tx_multishop_pi1']['status'] as $order_status) {
		$tmp[]='o.status='.$order_status;
	}
	if (count($tmp)) {
		$status_where='('.implode(' or ', $tmp).')';
	}
}
*/
// input for search
// usergroup
$groups = mslib_fe::getUserGroups($this->conf['fe_customer_pid']);
$customer_groups_input = '';
$customer_groups_input .= '<select id="groups" class="order_select2" name="usergroup">' . "\n";
$customer_groups_input .= '<option value="0">' . $this->pi_getLL('all') . ' ' . $this->pi_getLL('usergroup') . '</option>' . "\n";
if (is_array($groups) and count($groups)) {
    foreach ($groups as $group) {
        $customer_groups_input .= '<option value="' . $group['uid'] . '"' . ($this->get['usergroup'] == $group['uid'] ? ' selected="selected"' : '') . '>' . $group['title'] . '</option>' . "\n";
    }
}
$customer_groups_input .= '</select>' . "\n";
// usergroup eol
// payment status
$payment_status_select = '<select name="payment_status" id="payment_status" class="order_select2">
<option value="">' . $this->pi_getLL('select_orders_payment_status') . '</option>';
if ($this->get['payment_status'] == 'paid_only') {
    $payment_status_select .= '<option value="paid_only" selected="selected">' . $this->pi_getLL('show_paid_orders_only') . '</option>';
} else {
    $payment_status_select .= '<option value="paid_only">' . $this->pi_getLL('show_paid_orders_only') . '</option>';
}
if ($this->get['payment_status'] == 'unpaid_only') {
    $payment_status_select .= '<option value="unpaid_only" selected="selected">' . $this->pi_getLL('show_unpaid_orders_only') . '</option>';
} else {
    $payment_status_select .= '<option value="unpaid_only">' . $this->pi_getLL('show_unpaid_orders_only') . '</option>';
}
$payment_status_select .= '</select>';
// payment status eol
// order status
$orders_status_list = '<select name="orders_status_search" id="orders_status_search" class="order_select2"><option value="0" ' . ((!$order_status_search_selected) ? 'selected' : '') . '>' . $this->pi_getLL('all_orders_status', 'All orders status') . '</option>';
if (is_array($all_orders_status)) {
    $order_status_search_selected = false;
    if (is_array($all_orders_status) && count($all_orders_status)) {
        foreach ($all_orders_status as $row) {
            $orders_status_list .= '<option value="' . $row['id'] . '" ' . (($this->get['orders_status_search'] == $row['id']) ? 'selected' : '') . '>' . $row['name'] . '</option>' . "\n";
            if ($this->get['orders_status_search'] == $row['id']) {
                $order_status_search_selected = true;
            }
        }
    }
}
$orders_status_list .= '</select>';
// order status eol
// payment method
$payment_methods = array();
$sql = $GLOBALS['TYPO3_DB']->SELECTquery('payment_method, payment_method_label', // SELECT ...
        'tx_multishop_orders', // FROM ...
        ((!$this->masterShop) ? 'page_uid=\'' . $this->shop_pid . '\'' : ''), // WHERE...
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
    $payment_methods[$row['payment_method']] = $row['payment_method_label'] . ($row['payment_method'] != 'nopm' ? ' (code: ' . $row['payment_method'] . ')' : '');
}
$payment_method_input = '';
$payment_method_input .= '<select id="payment_method" class="order_select2" name="payment_method">' . "\n";
$payment_method_input .= '<option value="all">' . $this->pi_getLL('all_payment_methods') . '</option>' . "\n";
if (is_array($payment_methods) and count($payment_methods)) {
    foreach ($payment_methods as $payment_method_code => $payment_method) {
        $payment_method_input .= '<option value="' . $payment_method_code . '"' . ($this->get['payment_method'] == $payment_method_code ? ' selected="selected"' : '') . '>' . $payment_method . '</option>' . "\n";
    }
}
$payment_method_input .= '</select>' . "\n";
// payment method eol
// shipping method
$shipping_methods = array();
$sql = $GLOBALS['TYPO3_DB']->SELECTquery('shipping_method, shipping_method_label', // SELECT ...
        'tx_multishop_orders', // FROM ...
        ((!$this->masterShop) ? 'page_uid=\'' . $this->shop_pid . '\'' : ''), // WHERE...
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
    $shipping_methods[$row['shipping_method']] = $row['shipping_method_label'] . ($row['shipping_method'] != 'nosm' ? ' (code: ' . $row['shipping_method'] . ')' : '');
}
$shipping_method_input = '';
$shipping_method_input .= '<select id="shipping_method" class="order_select2" name="shipping_method">' . "\n";
$shipping_method_input .= '<option value="all">' . $this->pi_getLL('all_shipping_methods') . '</option>' . "\n";
if (is_array($shipping_methods) and count($shipping_methods)) {
    foreach ($shipping_methods as $shipping_method_code => $shipping_method) {
        $shipping_method_input .= '<option value="' . $shipping_method_code . '"' . ($this->get['shipping_method'] == $shipping_method_code ? ' selected="selected"' : '') . '>' . $shipping_method . '</option>' . "\n";
    }
}
$shipping_method_input .= '</select>' . "\n";
// shipping method eol
$content .= '<div class="order_stats_mode_wrapper">
<ul class="pagination horizontal_list">
	<li class="active"><span>' . htmlspecialchars($this->pi_getLL('stats_turnover_per_month', 'Turnover per month')) . '</span></li>
	<li><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_stats_orders&tx_multishop_pi1[stats_section]=turnoverPerYear') . '">' . htmlspecialchars($this->pi_getLL('stats_turnover_per_year', 'Turnover per year')) . '</a></li>
</ul>
</div>';
$content .= '
<form method="get" id="orders_stats_form">
<!--
<input name="id" type="hidden" value="' . $this->get['id'] . '" />
<div class="stat-years float_right">' . $year_select . '</div>
<input name="type" type="hidden" value="2003" />
<input name="Search" type="hidden" value="1" />
<input name="tx_multishop_pi1[page_section]" type="hidden" value="admin_stats_orders" />
<input name="tx_multishop_pi1[stats_section]" type="hidden" value="turnoverPerMonth" />
<div class="paid-orders"><input id="checkbox_paid_orders_only" name="paid_orders_only" type="checkbox" value="1" ' . ($this->cookie['paid_orders_only'] ? 'checked' : '') . ' /><label for="checkbox_paid_orders_only">' . $this->pi_getLL('show_paid_orders_only') . '</label></div>
-->

<div id="search-orders" class="well">
	<input name="id" type="hidden" value="' . $this->get['id'] . '" />
	<!-- <div class="stat-years float_right">' . $year_select . '</div> -->
	<input name="type" type="hidden" value="2003" />
	<input name="Search" type="hidden" value="1" />
	<input name="tx_multishop_pi1[page_section]" type="hidden" value="admin_stats_orders" />
	<input name="tx_multishop_pi1[stats_section]" type="hidden" value="turnoverPerMonth" />
	<div class="row formfield-container-wrapper">
		<div class="col-md-4 formfield-wrapper">
			<div class="form-group">
			<label for="groups">' . $this->pi_getLL('usergroup') . '</label>
			' . $customer_groups_input . '
			</div>
			<div class="form-group">
				<label for="country">' . $this->pi_getLL('countries') . '</label>
				' . $billing_countries_sb . '
			</div>
			<label>Date</label>
			<div class="form-group form-inline">
			<label for="order_date_from">' . $this->pi_getLL('from') . ':</label>
			<input type="text" class="form-control" name="order_date_from" id="order_date_from" value="' . $this->get['order_date_from'] . '">
			<label for="order_date_till" class="labelInbetween">' . $this->pi_getLL('to') . ':</label>
			<input type="text" class="form-control" name="order_date_till" id="order_date_till" value="' . $this->get['order_date_till'] . '">
			</div>
		</div>
		<div class="col-md-4 formfield-wrapper">
			<div class="form-group">
			<label for="payment_status">' . $this->pi_getLL('order_payment_status') . '</label>
			' . $payment_status_select . '
			</div>
			<div class="form-group">
			<label for="orders_status_search" class="labelInbetween">' . $this->pi_getLL('order_status') . '</label>
			' . $orders_status_list . '
			</div>
			<label>' . $this->pi_getLL('year') . '</label>
			<div class="form-group form-inline">
				' . $year_select . '
			</div>
		</div>
		<div class="col-md-4 formfield-wrapper">
			<div class="form-group">
			<label for="payment_method">' . $this->pi_getLL('payment_method') . '</label>
			' . $payment_method_input . '
			</div>
			<div class="form-group">
			<label for="shipping_method" class="labelInbetween">' . $this->pi_getLL('shipping_method') . '</label>
			' . $shipping_method_input . '
			</div>
			<div class="form-group">
				<div class="col-md-6">
					<div class="checkbox checkbox-success checkbox-inline">
						<input type="checkbox" id="filter_by_excluding_vat" name="tx_multishop_pi1[excluding_vat]" value="1"' . ($this->get['tx_multishop_pi1']['excluding_vat'] ? ' checked' : '') . '>
						<label for="filter_by_excluding_vat">' . htmlspecialchars($this->pi_getLL('excluding_vat')) . '</label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row formfield-container-wrapper">
		<div class="col-sm-12 formfield-wrapper">
			<input type="submit" name="Search" class="btn btn-success pull-right" value="' . htmlspecialchars($this->pi_getLL('search')) . '" />
		</div>
	</div>
</div>

</form>
<script type="text/javascript" language="JavaScript">
	jQuery(document).ready(function($) {
		$(document).on("click", ".admin_sales_stats_order_status", function() {
			var serial=$(".admin_sales_stats_order_status").serialize();
			if (serial!="") {
				location.href = "' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_stats_orders&tx_multishop_pi1[stats_section]=turnoverPerMonth') . '&" + serial;
			} else {
				location.href = "' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_stats_orders&tx_multishop_pi1[stats_section]=turnoverPerMonth') . '";
			}
		});
	});
</script>';
// search processor
$search_start_time = '';
$search_end_time = '';
$filter = array();
$data_query = array();
if (!empty($this->get['order_date_from']) && !empty($this->get['order_date_till'])) {
    list($from_date, $from_time) = explode(" ", $this->get['order_date_from']);
    list($fd, $fm, $fy) = explode('/', $from_date);
    list($till_date, $till_time) = explode(" ", $this->get['order_date_till']);
    list($td, $tm, $ty) = explode('/', $till_date);
    $search_start_time = strtotime($fy . '-' . $fm . '-' . $fd . ' ' . $from_time);
    $search_end_time = strtotime($ty . '-' . $tm . '-' . $td . ' ' . $till_time);
    $data_query['where'][] = "o.crdate BETWEEN '" . $search_start_time . "' and '" . $search_end_time . "'";
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
$grandTotalColumnName = 'grand_total';
if (isset($this->get['tx_multishop_pi1']['excluding_vat'])) {
    $grandTotalColumnName = 'grand_total_excluding_vat';
}
// hook
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_stats_orders/turn_over_per_month.php']['monthlyStatsOrdersQueryHookPreProc'])) {
    $params = array(
            'data_query' => &$data_query
    );
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_stats_orders/turn_over_per_month.php']['monthlyStatsOrdersQueryHookPreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
// search processor eol
$dates = array();
$content .= '<h3>' . htmlspecialchars($this->pi_getLL('sales_volume_by_month')) . '</h3>';
if (!empty($this->get['order_date_from']) && !empty($this->get['order_date_till'])) {
    $globalStartTime = $search_start_time;
    $globalEndTime = $search_end_time;
} else {
    $globalStartTime = strtotime(date($selected_year . '1' . "-01") . " 00:00:00");
    $globalEndTime = strtotime(date($selected_year . '12' . "-01") . " 00:00:00");
}
for ($i = 0; $i < 12; $i++) {
    $time = strtotime('+' . $i . ' month', $globalStartTime);
    if ($time <= $globalEndTime) {
        //$time=strtotime(date($selected_year.$i."-01")." 00:00:00");
        $dates[strftime("%B %Y", $time)] = date($selected_year . "m", $time);
    }
}
$content .= '<table class="table table-striped table-bordered" id="product_import_table">';
$content .= '<thead><tr>';
foreach ($dates as $key => $value) {
    $content .= '<th align="right">' . ucfirst($key) . '</th>';
}
$content .= '<th align="right" nowrap>' . htmlspecialchars($this->pi_getLL('total')) . '</th>';
$content .= '<th align="right" nowrap>' . htmlspecialchars($this->pi_getLL('cumulative')) . '</th>';
$content .= '</tr></thead><tbody>';
$content .= '<tr>';
$total_amount = 0;
// monthly order revenue
foreach ($dates as $key => $value) {
    $total_price = 0;
    $start_time = strtotime($value . "-01 00:00:00");
    $end_time = strtotime($value . "-01 23:59:59 +1 MONTH -1 DAY");
    $where = array();
    foreach ($data_query['where'] as $filter_data) {
        $where[] = $filter_data;
    }
    $where[] = '(o.deleted=0)';
    if (!empty($status_where)) {
        $where[] = $status_where;
    }
    $str = 'SELECT o.orders_id, o.' . $grandTotalColumnName . ' FROM tx_multishop_orders o WHERE (' . implode(' AND ', $where) . ') and (o.crdate BETWEEN ' . $start_time . ' and ' . $end_time . ')';
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        $total_price = ($total_price + $row[$grandTotalColumnName]);
    }
    $content .= '<td align="right">' . mslib_fe::amount2Cents($total_price, 0) . '</td>';
    $total_amount = $total_amount + $total_price;
}
if ($this->cookie['stats_year_sb'] == date("Y") || !$this->cookie['stats_year_sb']) {
    $month = date("m");
    $currentDay = date("d");
    $dayOfTheYear = date("z");
    $currentYear = 1;
    if ($month == 1) {
        $currentMonth = 1;
    }
} else {
    $month = 12;
    $dayOfTheYear = 365;
    $currentDay = 31;
    $currentYear = 0;
    $currentMonth = 0;
}
$content .= '<td align="right" nowrap>' . mslib_fe::amount2Cents($total_amount, 0) . '</td>';
$content .= '<td align="right" nowrap>' . mslib_fe::amount2Cents(($total_amount / $dayOfTheYear) * 365, 0) . '</td>';
$content .= '</tr>';
if (!$tr_type or $tr_type == 'even') {
    $tr_type = 'odd';
} else {
    $tr_type = 'even';
}
$content .= '
</tbody></table>';
// LAST MONTHS EOF
$content .= '<h3>' . htmlspecialchars($this->pi_getLL('average_order_amount_per_month', 'Average order amount per month')) . '</h3>';
$content .= '<table class="table table-striped table-bordered" id="product_import_table">';
$content .= '<thead><tr>';
foreach ($dates as $key => $value) {
    $content .= '<th align="right">' . ucfirst($key) . '</th>';
}
$content .= '<th align="right" nowrap>' . htmlspecialchars($this->pi_getLL('total')) . '</th>';
$content .= '</tr></thead><tbody>';
$content .= '<tr>';
$total_amount_avg = 0;
$total_orders_avg = 0;
// monthly average order
foreach ($dates as $key => $value) {
    $total_price_avrg = 0;
    $total_orders = 0;
    $start_time = strtotime($value . "-01 00:00:00");
    $end_time = strtotime($value . "-01 23:59:59 +1 MONTH -1 DAY");
    $where = array();
    foreach ($data_query['where'] as $filter_data) {
        $where[] = $filter_data;
    }
    $where[] = '(o.deleted=0)';
    if (!empty($status_where)) {
        $where[] = $status_where;
    }
    $str = 'SELECT o.orders_id, o.' . $grandTotalColumnName . '  FROM tx_multishop_orders o WHERE (' . implode(' AND ', $where) . ') and (o.crdate BETWEEN ' . $start_time . ' and ' . $end_time . ')';
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $total_orders = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
    $total_orders_avg += $total_orders;
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        $total_price_avrg = ($total_price_avrg + $row[$grandTotalColumnName]);
    }
    if ($total_price_avrg > 0 && $total_orders > 0) {
        $totalSum = $total_price_avrg / $total_orders;
    } else {
        $totalSum = 0;
    }
    $content .= '<td align="right">' . mslib_fe::amount2Cents($totalSum, 0) . '</td>';
    $total_amount_avg = $total_amount_avg + $total_price_avrg;
}
if ($this->cookie['stats_year_sb'] == date("Y") || !$this->cookie['stats_year_sb']) {
    $month = date("m");
    $currentDay = date("d");
    $dayOfTheYear = date("z");
    $currentYear = 1;
    if ($month == 1) {
        $currentMonth = 1;
    }
} else {
    $month = 12;
    $dayOfTheYear = 365;
    $currentDay = 31;
    $currentYear = 0;
    $currentMonth = 0;
}
if ($total_amount_avg > 0 && $total_orders_avg > 0) {
    $totalSum = $total_amount_avg / $total_orders_avg;
} else {
    $totalSum = 0;
}
$content .= '<td align="right" nowrap>' . mslib_fe::amount2Cents($totalSum, 0) . '</td>';
$content .= '</tr>';
if (!$tr_type or $tr_type == 'even') {
    $tr_type = 'odd';
} else {
    $tr_type = 'even';
}
$content .= '
</tbody></table>';
// LAST MONTHS EOF
/*
$tr_type='even';
$dates=array();
$content.='<h3>'.htmlspecialchars($this->pi_getLL('sales_volume_by_day')).'</h3>';
if ($currentMonth) {
	$endDay=date("d");
} else {
	$endDay=31;
}
if ($currentMonth) {
	$endDay=date("d");
} else {
	$endDay=31;
}
$dates=array();
for ($i=0; $i<100; $i++) {
	$time=strtotime('+'.$i.' day',$globalStartTime);
	if ($time <= $globalEndTime) {
		$dates[strftime("%a. %x", $time)]=$time;
		//$time=strtotime(date($selected_year.$i."-01")." 00:00:00");
		//$dates[strftime("%B %Y", $time)]=date($selected_year."m", $time);
	}
}
$content.='<table class="table table-striped table-bordered" id="product_import_table">
<thead><tr>
	<th width="200">'.htmlspecialchars($this->pi_getLL('day')).'</th>
	<th width="100" align="right">'.htmlspecialchars($this->pi_getLL('amount')).'</th>
	<th width="100" align="right">'.htmlspecialchars($this->pi_getLL('average', 'average')).'</th>
	<th>'.htmlspecialchars($this->pi_getLL('orders_id')).'</th>
</tr></thead><tbody>';
// daily stats
foreach ($dates as $key=>$value) {
	$total_daily_orders=0;
	if (!$tr_type or $tr_type=='even') {
		$tr_type='odd';
	} else {
		$tr_type='even';
	}
	$content.='<tr>';
	$content.='<td>'.$key.'</td>';
	$total_price=0;
	$start_time=strtotime(date("Y-m-d 00:00:00",$value));
	$end_time=strtotime(date("Y-m-d 23:59:59",$value));
	$where=array();
	foreach ($data_query['where'] as $filter_data) {
		$where[]=$filter_data;
	}
	$where[]='(o.deleted=0)';
	if (!empty($status_where)) {
		$where[]=$status_where;
	}
	$str="SELECT o.customer_id, o.orders_id, o.".$grandTotalColumnName."  FROM tx_multishop_orders o WHERE (".implode(" AND ", $where).") and (o.crdate BETWEEN ".$start_time." and ".$end_time.")";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$uids=array();
	$users=array();
	while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
		$total_price=($total_price+$row[$grandTotalColumnName]);
		$uids[]='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]=edit_order&orders_id='.$row['orders_id'].'&action=edit_order', 1).'">'.$row['orders_id'].'</a>';
		$total_daily_orders++;
	}
	if ($total_price>0 && $total_daily_orders>0) {
		$totalSum=$total_price/$total_daily_orders;
	} else {
		$totalSum=0;
	}
	$content.='<td align="right">'.mslib_fe::amount2Cents($total_price, 0).'</td>';
	$content.='<td align="right">'.mslib_fe::amount2Cents($totalSum, 0).'</td>';
	if (count($uids)) {
		$content.='<td>'.implode(", ", $uids).'</td>';
	} else {
		$content.='<td> </td>';
	}
	$content.='</tr>';
}
$content.='</tbody></table>';
*/
// LAST MONTHS EOF
$content .= '<div class="msAdminOrdersStatsButtonWrapper">';
$dlink_param['stats_year_sb'] = $this->get['stats_year_sb'];
$dlink_param['paid_orders_only'] = $this->get['paid_orders_only'];
$param_link = '';
$param_val_ctr = 0;
foreach ($dlink_param as $key => $val) {
    $param_link .= '&' . $key . '=' . $val;
    if (!empty($val)) {
        $param_val_ctr++;
    }
}
if ($param_val_ctr > 0) {
    $dlink = "location.href = '/" . mslib_fe::typolink('', 'tx_multishop_pi1[page_section]=admin_orders_stats_dl_xls' . $param_link) . "'";
} else {
    $dlink = "downloadOrdersExcelParam();";
}
$content .= '</div>';
$content .= '<hr><div class="clearfix">';
$content .= '<a class="btn btn-success msAdminBackToCatalog" href="' . mslib_fe::typolink() . '"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span> ' . $this->pi_getLL('admin_close_and_go_back_to_catalog') . '</a>';
$content .= '<button type="button" name="download" class="pull-right btn btn-success link_block" value="' . $this->pi_getLL('admin_download_as_excel_file') . '" onclick="' . $dlink . '"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-download fa-stack-1x"></i></span> ' . $this->pi_getLL('admin_download_as_excel_file') . '</button>';
$content .= '
</div>';
$headerData = '';
$headerData .= '
<script type="text/javascript">
function downloadOrdersExcelParam() {
	var href = "/' . mslib_fe::typolink('', 'tx_multishop_pi1[page_section]=admin_orders_stats_dl_xls') . '";
	var form_ser = jQuery("form").serializeArray();
	var form_param = "";
	jQuery.each(form_ser, function(i, v) {
		if (v.name == "stats_year_sb" ||
			v.name == "paid_orders_only") {
			if (form_param == "") {
				form_param += v.name + "=" + v.value;
			} else {
				form_param += "&" + v.name + "=" + v.value;
			}
		}
	});
	return location.href = href + "?" + form_param;
}
jQuery(document).ready(function ($) {
	$(\'#order_date_from\').datetimepicker({
		dateFormat: \'dd/mm/yy\',
		showSecond: true,
		timeFormat: \'HH:mm:ss\'
	});
	$(\'#order_date_till\').datetimepicker({
		dateFormat: \'dd/mm/yy\',
        showSecond: true,
        timeFormat: \'HH:mm:ss\',
		hour: 23,
        minute: 59,
        second: 59
	});
	$(".order_select2").select2();
	$(".invoice_select2").select2();
});
</script>';
$GLOBALS['TSFE']->additionalHeaderData[] = $headerData;
$headerData = '';
$content = '<div class="panel panel-default"><div class="panel-body">' . mslib_fe::shadowBox($content) . '</div></div>';
?>