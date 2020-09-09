<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
$compiledWidget['key'] = 'turnoverPerMonth';
$compiledWidget['defaultCol'] = 1;
$compiledWidget['title'] = $this->pi_getLL('sales_volume_by_month');
$where = array();
$where[] = '(o.deleted=0)';
switch ($this->dashboardArray['section']) {
    case 'admin_home':
        break;
    case 'admin_edit_customer':
        if ($this->get['tx_multishop_pi1']['cid'] && is_numeric($this->get['tx_multishop_pi1']['cid'])) {
            $where[] = '(o.customer_id=' . $this->get['tx_multishop_pi1']['cid'] . ')';
        }
        break;
}
$str = $GLOBALS['TYPO3_DB']->SELECTquery('o.crdate', // SELECT ...
        'tx_multishop_orders o', // FROM ...
        '(' . implode(" AND ", $where) . ')', // WHERE...
        '', // GROUP BY...
        'orders_id asc', // ORDER BY...
        '1' // LIMIT ...
);
$qry_year = $GLOBALS['TYPO3_DB']->sql_query($str);
$row_year = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_year);
if ($row_year['crdate'] > 0) {
    $oldest_year = date("Y", $row_year['crdate']);
} else {
    $oldest_year = date("Y");
}
$current_year = date("Y");
$dates = array();
//$compiledWidget['content'].='<h2>'.htmlspecialchars($this->pi_getLL('sales_volume_by_month')).'</h2>';
for ($i = 3; $i >= 0; $i--) {
    //$time=strtotime("-".$i." month");
    $time = strtotime(date('Y-m-01') . ' -' . $i . ' MONTH');
//	$time=strtotime(date($selected_year.$i."-01")." 00:00:00");
    $dates[strftime("%B %Y", $time)] = date("Y-m", $time);
}
$compiledWidget['content'] .= '<table width="100%" class="table table-striped table-bordered" cellspacing="0" cellpadding="0" border="0" >';
$compiledWidget['content'] .= '<tr class="odd">';
foreach ($dates as $key => $value) {
    $compiledWidget['content'] .= '<td align="right">' . ucfirst($key) . '</td>';
}
$compiledWidget['content'] .= '<td align="right" nowrap>' . htmlspecialchars($this->pi_getLL('total')) . '</td>';
$compiledWidget['content'] .= '<td align="right" nowrap>' . htmlspecialchars($this->pi_getLL('cumulative')) . ' ' . date("Y") . '</td>';
$compiledWidget['content'] .= '</tr>';
$compiledWidget['content'] .= '<tr class="even">';
$total_amount = 0;
foreach ($dates as $key => $value) {
    $total_price = 0;
    $start_time = strtotime($value . "-01 00:00:00");
    //$end_time=strtotime($value."-31 23:59:59");
    $end_time = strtotime($value . "-01 23:59:59 +1 MONTH -1 DAY");
    $data_query['where'] = array();
    if ($this->cookie['paid_orders_only']) {
        $data_query['where'][] = '(o.paid=1)';
    } else {
        $data_query['where'][] = '(o.paid=1 or o.paid=0)';
    }
    $data_query['where'][] = '(o.deleted=0)';
    $data_query['where'][] = '(o.crdate BETWEEN ' . $start_time . ' and ' . $end_time . ')';
    switch ($this->dashboardArray['section']) {
        case 'admin_home':
            break;
        case 'admin_edit_customer':
            if ($this->get['tx_multishop_pi1']['cid'] && is_numeric($this->get['tx_multishop_pi1']['cid'])) {
                $data_query['where'][] = '(o.customer_id=' . $this->get['tx_multishop_pi1']['cid'] . ')';
            }
            break;
    }
    // hook
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_home/turn_over_per_month.php']['monthlyHomeStatsQueryHookPreProc'])) {
        $params = array(
                'data_query' => &$data_query
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_home/turn_over_per_month.php']['monthlyHomeStatsQueryHookPreProc'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    $str = $GLOBALS['TYPO3_DB']->SELECTquery('o.orders_id, o.grand_total', // SELECT ...
            'tx_multishop_orders o', // FROM ...
            '(' . implode(" AND ", $data_query['where']) . ')', // WHERE...
            '', // GROUP BY...
            '', // ORDER BY...
            '' // LIMIT ...
    );
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        $total_price = ($total_price + $row['grand_total']);
    }
    $compiledWidget['content'] .= '<td align="right">' . mslib_fe::amount2Cents($total_price, 0) . '</td>';
    $total_amount = $total_amount + $total_price;
    if (date("Y", $start_time) == date("Y")) {
        $total_amount_cumulative = $total_amount_cumulative + $total_price;
    }
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
$compiledWidget['content'] .= '<td align="right" nowrap>' . mslib_fe::amount2Cents($total_amount, 0) . '</td>';
$compiledWidget['content'] .= '<td align="right" nowrap>' . mslib_fe::amount2Cents(($total_amount_cumulative / $dayOfTheYear) * 365, 0) . '</td>';
$compiledWidget['content'] .= '</tr>';
if (!$tr_type or $tr_type == 'even') {
    $tr_type = 'odd';
} else {
    $tr_type = 'even';
}
$compiledWidget['content'] .= '
</table>';
