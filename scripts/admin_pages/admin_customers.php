<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$content = '';
$this->cObj->data['header'] = 'Customers';
if ($GLOBALS['TSFE']->fe_user->user['uid'] and $this->get['login_as_customer'] && is_numeric($this->get['customer_id'])) {
    $user = mslib_fe::getUser($this->get['customer_id']);
    if ($user['uid']) {
        mslib_befe::loginAsUser($user['uid'], 'admin_customers');
    }
}
$postErno = array();
if ($this->post && isset($this->post['tx_multishop_pi1']['action']) && !empty($this->post['tx_multishop_pi1']['action'])) {
    $redirectAfterPostProc = 1;
    switch ($this->post['tx_multishop_pi1']['action']) {
        case 'delete_selected_customers':
            if (is_array($this->post['selected_customers']) and count($this->post['selected_customers'])) {
                foreach ($this->post['selected_customers'] as $customer_id) {
                    if (is_numeric($customer_id)) {
                        mslib_befe::deleteCustomer($customer_id);
                    }
                }
            }
            break;
        default:
            // post processing by third party plugins
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_customers.php']['adminCustomersPostHookProc'])) {
                $params = array();
                $params['content'] =& $content;
                $params['redirectAfterPostProc'] =& $redirectAfterPostProc;
                $params['postErno'] =& $postErno;
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_customers.php']['adminCustomersPostHookProc'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            break;
    }
    if ($redirectAfterPostProc) {
        header('Location: ' . $this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_customers'));
        exit();
    }
}
if (is_numeric($this->get['disable']) and is_numeric($this->get['customer_id'])) {
    if ($this->get['disable']) {
        mslib_befe::disableCustomer($this->get['customer_id']);
    } else {
        mslib_befe::enableCustomer($this->get['customer_id']);
    }
} else {
    if (is_numeric($this->get['delete']) and is_numeric($this->get['customer_id'])) {
        mslib_befe::deleteCustomer($this->get['customer_id']);
    }
}
$this->hideHeader = 1;
if ($this->get['Search'] and ($this->get['limit'] != $this->cookie['limit'])) {
    $this->cookie['limit'] = $this->get['limit'];
    $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
    $GLOBALS['TSFE']->storeSessionData();
}
if ($this->cookie['limit']) {
    $this->get['limit'] = $this->cookie['limit'];
} else {
    $this->get['limit'] = 10;
}
$this->ms['MODULES']['PAGESET_LIMIT'] = $this->get['limit'];
$this->searchKeywords = array();
if ($this->get['tx_multishop_pi1']['searchByChar']) {
    switch ($this->get['tx_multishop_pi1']['searchByChar']) {
        case '0-9':
            for ($i = 0; $i < 10; $i++) {
                $this->searchKeywords[] = $i;
            }
            break;
        case '#':
            $this->searchKeywords[] = '#';
            break;
        case 'all':
            break;
        default:
            $this->searchKeywords[] = $this->get['tx_multishop_pi1']['searchByChar'];
            break;
    }
    $this->searchMode = 'keyword%';
} elseif ($this->get['tx_multishop_pi1']['keyword']) {
    //  using $_REQUEST cause TYPO3 converts "Command & Conquer" to "Conquer" (the & sign sucks ass)
    $this->get['tx_multishop_pi1']['keyword'] = trim($this->get['tx_multishop_pi1']['keyword']);
    $this->get['tx_multishop_pi1']['keyword'] = $GLOBALS['TSFE']->csConvObj->utf8_encode($this->get['tx_multishop_pi1']['keyword'], $GLOBALS['TSFE']->metaCharset);
    $this->get['tx_multishop_pi1']['keyword'] = $GLOBALS['TSFE']->csConvObj->entities_to_utf8($this->get['tx_multishop_pi1']['keyword'], true);
    $this->get['tx_multishop_pi1']['keyword'] = mslib_fe::RemoveXSS($this->get['tx_multishop_pi1']['keyword']);
    $this->searchKeywords[] = $this->get['tx_multishop_pi1']['keyword'];
    $this->searchMode = '%keyword%';
}
if (is_numeric($this->get['p'])) {
    $p = $this->get['p'];
}
if ($p > 0) {
    $offset = (((($p) * $this->ms['MODULES']['PAGESET_LIMIT'])));
} else {
    $p = 0;
    $offset = 0;
}
$user = $GLOBALS['TSFE']->fe_user->user;
$option_search = array(
        "f.company" => $this->pi_getLL('admin_company'),
        "f.name" => $this->pi_getLL('admin_customer_name'),
        "f.username" => $this->pi_getLL('username'),
        "f.email" => $this->pi_getLL('admin_customer_email'),
        "f.uid" => $this->pi_getLL('admin_customer_id'),
        "f.city" => $this->pi_getLL('admin_city'),
    //"f.country"=>ucfirst(strtolower($this->pi_getLL('admin_countries'))),
        "f.zip" => $this->pi_getLL('admin_zip'),
        "f.telephone" => $this->pi_getLL('telephone')
);
asort($option_search);
$option_item = '';
foreach ($option_search as $key => $val) {
    $option_item .= '<option value="' . $key . '" ' . ($this->get['tx_multishop_pi1']['search_by'] == $key ? "selected" : "") . '>' . $val . '</option>';
}
$groups = mslib_fe::getUserGroups($this->conf['fe_customer_pid']);
$customer_groups_input = '';
$customer_groups_input .= '<select id="groups" class="invoice_select2" name="usergroup">' . "\n";
$customer_groups_input .= '<option value="0">' . $this->pi_getLL('all') . '</option>' . "\n";
if (is_array($groups) and count($groups)) {
    foreach ($groups as $group) {
        $customer_groups_input .= '<option value="' . $group['uid'] . '"' . ($this->get['usergroup'] == $group['uid'] ? ' selected="selected"' : '') . '>' . $group['title'] . '</option>' . "\n";
    }
}
$customer_groups_input .= '</select>' . "\n";
$searchCharNav = '<div id="msAdminSearchByCharNav" class="no-mb"><ul class="pagination">';
$chars = array();
$chars = array(
        '0-9',
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z',
        '#',
        'all'
);
foreach ($chars as $char) {
    $searchCharNav .= '<li><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[searchByChar]=' . $char . '&tx_multishop_pi1[page_section]=admin_customers') . '">' . mslib_befe::strtoupper($char) . '</a></li>';
}
$searchCharNav .= '</ul></div>';
$user_countries = mslib_befe::getRecords('', 'fe_users f', '', array(), 'f.country', 'f.country asc');
$fe_user_country = array();
foreach ($user_countries as $user_country) {
    if (!empty($user_country['country'])) {
        $cn_localized_name = htmlspecialchars(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $user_country['country']));
        $fe_user_country[$cn_localized_name] = $fe_user_countries[] = '<option value="' . mslib_befe::strtolower($user_country['country']) . '" ' . ((mslib_befe::strtolower($this->get['country']) == mslib_befe::strtolower($user_country['country'])) ? 'selected' : '') . '>' . $cn_localized_name . '</option>';
    }
}
ksort($fe_user_country);
$user_countries_sb = '<select class="invoice_select2" name="country" id="country""><option value="">' . $this->pi_getLL('all') . '</option>' . implode("\n", $fe_user_country) . '</select>';
$formTopSearch = '
<form id="form1" name="form1" method="get" action="index.php">
<div class="panel panel-default no-mb">
    <div class="panel-heading">
            <div class="form-inline form-collapse">
                <div class="input-group">
                    <input class="form-control" type="text" name="tx_multishop_pi1[keyword]" id="advance-skeyword" value="' . htmlspecialchars($this->get['tx_multishop_pi1']['keyword']) . '" placeholder="' . htmlspecialchars($this->pi_getLL('keyword')) . '" />
                    <i class="fa fa-search 2x form-control-inputsearch"></i>
                    <span class="input-group-btn">
                        <input type="submit" name="Search" id="advanceSearchSubmit" value="' . htmlspecialchars($this->pi_getLL('search')) . '" class="btn btn-success" />
                    </span>
                </div>
                <a role="button" data-toggle="collapse" href="#msAdminInterfaceSearch" class="advanceSearch">' . htmlspecialchars($this->pi_getLL('advanced_search')) . '</a>
            </div>
            <div class="form-inline pull-right">
<label for="limit" class="control-label">' . $this->pi_getLL('limit_number_of_records_to') . ':</label>
				<select name="limit" id="limit" class="form-control">';
$limits = array();
$limits[] = '10';
$limits[] = '15';
$limits[] = '20';
$limits[] = '25';
$limits[] = '30';
$limits[] = '40';
$limits[] = '50';
$limits[] = '100';
$limits[] = '150';
$limits[] = '200';
$limits[] = '250';
$limits[] = '300';
$limits[] = '350';
$limits[] = '400';
$limits[] = '450';
$limits[] = '500';
if (!in_array($this->get['limit'], $limits)) {
    $limits[] = $this->get['limit'];
}
foreach ($limits as $limit) {
    $formTopSearch .= '<option value="' . $limit . '"' . ($limit == $this->get['limit'] ? ' selected="selected"' : '') . '>' . $limit . '</option>';
}
//
$unfold_advanced_search_box = '';
if ((isset($this->get['tx_multishop_pi1']['search_by']) && !empty($this->get['tx_multishop_pi1']['search_by']) && $this->get['tx_multishop_pi1']['search_by'] != 'all') ||
        (isset($this->get['country']) && !empty($this->get['country'])) ||
        (isset($this->get['usergroup']) && $this->get['usergroup'] > 0) ||
        (isset($this->get['ordered_product']) && !empty($this->get['ordered_product'])) ||
        (isset($this->get['tx_multishop_pi1']['subscribed_newsletter']) && $this->get['tx_multishop_pi1']['subscribed_newsletter'] != 'all') ||
        (isset($this->get['crdate_from']) && !empty($this->get['crdate_from'])) ||
        (isset($this->get['crdate_till']) && !empty($this->get['crdate_till']))
) {
    $unfold_advanced_search_box = ' in';
}
$formTopSearch .= '
				</select>
            </div>
    </div>
    <div id="msAdminInterfaceSearch" class="panel-collapse collapse' . $unfold_advanced_search_box . '">
        <div class="panel-body">
<div id="search-orders" class="well no-mb">
	<div class="row formfield-container-wrapper">
		<input name="tx_multishop_pi1[do_search]" type="hidden" value="1" />
		<input name="id" type="hidden" value="' . $this->shop_pid . '" />
		<input name="type" type="hidden" value="2003" />
		<input name="tx_multishop_pi1[page_section]" type="hidden" value="admin_customers" />
		<div class="col-sm-4 formfield-wrapper">
			<div class="form-group">
				<label class="control-label" for="type_search">' . $this->pi_getLL('search_by') . '</label>
				<div class="form-inline">
					<select class="invoice_select2" name="tx_multishop_pi1[search_by]">
						<option value="all">' . $this->pi_getLL('all') . '</option>
						' . $option_item . '
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label" for="country">' . $this->pi_getLL('country') . '</label>
				' . $user_countries_sb . '
			</div>
		</div>
		<div class="col-sm-4 formfield-wrapper">
			<div class="form-group">
				<label class="control-label" for="type_search">' . $this->pi_getLL('usergroup') . '</label>
				<div class="form-inline">
				' . $customer_groups_input . '
				</div>
			</div>
			<div class="form-group">
				<label class="control-label" for="type_search">' . $this->pi_getLL('admin_ordered_product') . '</label>
				<div class="form-inline">
				<input type="hidden" class="ordered_product" name="ordered_product" value="' . $this->get['ordered_product'] . '" />
				</div>
			</div>
		</div>
		<div class="col-sm-4 formfield-wrapper">
			<label class="control-label" for="type_search">' . $this->pi_getLL('date') . '</label>
			<div class="form-group">
				<div class="form-inline">
					<label class="control-label" for="crdate_from_visual">' . $this->pi_getLL('from') . '</label>
					<input class="form-control" type="text" name="crdate_from_visual" id="crdate_from_visual" value="' . (!empty($this->get['crdate_from']) ? date($this->pi_getLL('locale_datetime_format'), strtotime($this->get['crdate_from'])) : '') . '">
					<label for="crdate_till_visual" class="labelInbetween">' . $this->pi_getLL('to') . '</label>
					<input class="form-control" type="text" name="crdate_till_visual" id="crdate_till_visual" value="' . (!empty($this->get['crdate_till']) ? date($this->pi_getLL('locale_datetime_format'), strtotime($this->get['crdate_till'])) : '') . '">
					<input type="hidden" name="crdate_from" id="crdate_from" value="' . (!empty($this->get['crdate_from']) ? date('Y-m-d H:i:s', strtotime($this->get['crdate_from'])) : '') . '">
                    <input type="hidden" name="crdate_till" id="crdate_till" value="' . (!empty($this->get['crdate_till']) ? date('Y-m-d H:i:s', strtotime($this->get['crdate_till'])) : '') . '">
				</div>
			</div>
			<div class="form-group">
				<label class="control-label" for="type_search">' . $this->pi_getLL('admin_customers_subscribed_newsletter') . '</label>
				<div class="form-inline">
                    <select name="tx_multishop_pi1[subscribed_newsletter]" class="invoice_select2">
                        <option value="all"' . (!isset($this->get['tx_multishop_pi1']['subscribed_newsletter']) || $this->get['tx_multishop_pi1']['subscribed_newsletter'] == 'all' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('not_applicable_short') . '</option>
                        <option value="y"' . ($this->get['tx_multishop_pi1']['subscribed_newsletter'] == 'y' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('yes') . '</option>
                        <option value="n"' . ($this->get['tx_multishop_pi1']['subscribed_newsletter'] == 'n' ? ' selected="selected"' : '') . '>' . $this->pi_getLL('no') . '</option>
                    </select>
				</div>
			</div>
			<div class="form-group">
				<div class="checkbox checkbox-success">
				<input type="checkbox" id="includeDeletedAccounts" name="tx_multishop_pi1[show_deleted_accounts]" value="1"' . ($this->get['tx_multishop_pi1']['show_deleted_accounts'] ? ' checked="checked"' : '') . ' />
				<label for="includeDeletedAccounts">' . $this->pi_getLL('show_deleted_accounts') . '</label>
				</div>
			</div>
		</div>
	</div>
	
	<div class="panel-footer clearfix">
        <div class="row formfield-container-wrapper">
            <div class="col-sm-12 formfield-wrapper">
                <div class="pull-right">
                <input type="submit" name="Search" class="btn btn-success" value="' . $this->pi_getLL('search') . '" />
                <button type="button" id="reset-advanced-search" class="btn btn-warning">' . $this->pi_getLL('reset_advanced_search_filter') . '</button>
                </div>
            </div>
        </div>
        
    </div>
        </div>
    </form>
    </div></div></div>
' . $searchCharNav . '
';
$filter = array();
$having = array();
$match = array();
$orderby = array();
$where = array();
$orderby = array();
$select = array();
if (strlen($this->get['tx_multishop_pi1']['keyword']) > 0) {
    switch ($this->get['tx_multishop_pi1']['search_by']) {
        case 'f.uid':
            $filter[] = "f.uid like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        case 'f.company':
            $filter[] = "f.company like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        case 'f.name':
            $filter[] = "f.name like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        case 'f.email':
            $filter[] = "f.email like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        case 'f.username':
            $filter[] = "f.username like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        case 'f.city':
            $filter[] = "f.city like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        /*case 'f.country':
            $filter[]="f.country like '".addslashes($this->get['tx_multishop_pi1']['keyword'])."%'";
            break;*/
        case 'f.zip':
            $filter[] = "f.zip like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        case 'f.telephone':
            $filter[] = "f.telephone like '" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            break;
        default:
            $option_fields = $option_search;
            $items = array();
            foreach ($option_fields as $fields => $label) {
                $items[] = $fields . " LIKE '%" . addslashes($this->get['tx_multishop_pi1']['keyword']) . "%'";
            }
            $filter[] = '(' . implode(" or ", $items) . ')';
            break;
    }
} else {
    if (count($this->searchKeywords)) {
        $keywordOr = array();
        foreach ($this->searchKeywords as $searchKeyword) {
            if ($searchKeyword) {
                switch ($this->searchMode) {
                    case 'keyword%':
                        $this->sqlKeyword = addslashes($searchKeyword) . '%';
                        break;
                    case '%keyword%':
                    default:
                        $this->sqlKeyword = '%' . addslashes($searchKeyword) . '%';
                        break;
                }
                if ($this->get['tx_multishop_pi1']['searchByChar']) {
                    $keywordOr[] = "f.company like '" . $this->sqlKeyword . "'";
                    $keywordOr[] = "(f.company ='' AND f.name like '" . $this->sqlKeyword . "')";
                } else {
                    $keywordOr[] = "f.company like '" . $this->sqlKeyword . "'";
                    $keywordOr[] = "f.name like '" . $this->sqlKeyword . "'";
                    $keywordOr[] = "f.email like '" . $this->sqlKeyword . "'";
                    $keywordOr[] = "f.username like '" . $this->sqlKeyword . "'";
                    $keywordOr[] = "f.city like '" . $this->sqlKeyword . "'";
                    //$keywordOr[]="f.country like '".$this->sqlKeyword."'";
                    $keywordOr[] = "f.zip like '" . $this->sqlKeyword . "'";
                    $keywordOr[] = "f.telephone like '" . $this->sqlKeyword . "'";
                }
            }
        }
        $filter[] = "(" . implode(" OR ", $keywordOr) . ")";
    }
}
switch ($this->get['tx_multishop_pi1']['order_by']) {
    case 'username':
        $order_by = 'f.username';
        break;
    case 'company':
        $order_by = 'f.company';
        break;
    case 'crdate':
        $order_by = 'f.crdate';
        break;
    case 'lastlogin':
        $order_by = 'f.lastlogin';
        break;
    case 'grand_total':
        $order_by = 'grand_total';
        break;
    case 'grand_total_this_year':
        $order_by = 'grand_total_this_year';
        break;
    case 'disable':
        $order_by = 'f.disable';
        break;
    case 'uid':
    default:
        $order_by = 'f.uid';
        break;
}
switch ($this->get['tx_multishop_pi1']['order']) {
    case 'a':
        $order = 'asc';
        $order_link = 'd';
        break;
    case 'd':
    default:
        $order = 'desc';
        $order_link = 'a';
        break;
}
$orderby[] = $order_by . ' ' . $order;
if (!$this->get['tx_multishop_pi1']['show_deleted_accounts']) {
    $filter[] = '(f.deleted=0)';
}
if (!$this->masterShop) {
    $filter[] = "f.page_uid='" . $this->shop_pid . "'";
}
$filter[] = "f.pid='" . $this->conf['fe_customer_pid'] . "'";
if (!empty($this->get['crdate_from']) && !empty($this->get['crdate_till'])) {
    $start_time = strtotime($this->get['crdate_from']);
    $end_time = strtotime($this->get['crdate_till']);
    $column = 'f.crdate';
    $filter[] = $column . " BETWEEN '" . $start_time . "' and '" . $end_time . "'";
} else {
    if (!empty($this->get['crdate_from'])) {
        $start_time = strtotime($this->get['crdate_from']);
        $column = 'f.crdate';
        $filter[] = $column . " >= '" . $start_time . "'";
    }
    if (!empty($this->get['crdate_till'])) {
        $end_time = strtotime($this->get['crdate_till']);
        $column = 'f.crdate';
        $filter[] = $column . " <= '" . $end_time . "'";
    }
}
if (isset($this->get['usergroup']) && $this->get['usergroup'] > 0) {
    $filter[] = $GLOBALS['TYPO3_DB']->listQuery('usergroup', $this->get['usergroup'], 'fe_users');
}
if (isset($this->get['country']) && !empty($this->get['country'])) {
    $filter[] = "f.country='" . $this->get['country'] . "'";
}
if (isset($this->get['ordered_product']) && !empty($this->get['ordered_product']) && $this->get['ordered_product'] != '99999') {
    $filter[] = "f.uid in (select o.customer_id from tx_multishop_orders o, tx_multishop_orders_products op where op.products_id='" . $this->get['ordered_product'] . "' and o.orders_id=op.orders_id)";
}
if (isset($this->get['tx_multishop_pi1']['subscribed_newsletter']) && $this->get['tx_multishop_pi1']['subscribed_newsletter'] != 'all') {
    switch ($this->get['tx_multishop_pi1']['subscribed_newsletter']) {
        case 'y':
            $filter[] = "f.tx_multishop_newsletter=1";
            break;
        case 'n':
            $filter[] = "f.tx_multishop_newsletter=0";
            break;
    }
}
if (!$this->masterShop) {
    $filter[] = $GLOBALS['TYPO3_DB']->listQuery('usergroup', $this->conf['fe_customer_usergroup'], 'fe_users');
}
// subquery to summarize grand total per customer
$select[] = '(select sum(grand_total) from tx_multishop_orders where deleted=0 and customer_id=f.uid) as grand_total';
// subquery to summarize grand total by year, per customer
$startTime = strtotime(date("Y-01-01 00:00:00"));
$endTime = strtotime(date("Y-12-31 23:59:59"));
$select[] = '(select sum(grand_total) from tx_multishop_orders where deleted=0 and customer_id=f.uid and crdate BETWEEN ' . $startTime . ' and ' . $endTime . ') as grand_total_this_year';
$pageset = mslib_fe::getCustomersPageSet($filter, $offset, $this->ms['MODULES']['PAGESET_LIMIT'], $orderby, $having, $select, $where);
$customers = $pageset['customers'];
if ($pageset['total_rows'] > 0 && isset($pageset['customers'])) {
    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_customers_listing.php');
    // pagination
    if (!$this->ms['nopagenav'] and $pageset['total_rows'] > $this->ms['MODULES']['PAGESET_LIMIT']) {
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_pagination.php');
        $content .= $tmp;
    }
    // pagination eof
} else {
    $content .= $this->pi_getLL('no_records_found');
}
$tmp = $content;
$content = '';
$tabs = array();
$tabs['CustomersListing'] = array(
        $this->pi_getLL('customers'),
        $tmp
);
$tmp = '';
$extra_selected_customers_action_js_filters = '';
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_customers.php']['adminCustomersExtraJSForSelectedActions'])) {
    $params = array('extra_selected_customers_action_js_filters' => $extra_selected_customers_action_js_filters);
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_customers.php']['adminCustomersExtraJSForSelectedActions'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
$GLOBALS['TSFE']->additionalHeaderData[] = '
<script type="text/javascript" data-ignore="1">
jQuery(document).ready(function($) {
    $(document).on("click", "#reset-advanced-search", function(e){
        location.href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_customers') . '";
    });
	jQuery(".tab_content").hide();
	jQuery("ul.tabs li:first").addClass("active").show();
	jQuery(".tab_content:first").show();
	jQuery("ul.tabs li").click(function() {
		jQuery("ul.tabs li").removeClass("active");
		jQuery(this).addClass("active");
		jQuery(".tab_content").hide();
		var activeTab = jQuery(this).find("a").attr("href");
		jQuery(activeTab).fadeIn(0);
		return false;
	});
    jQuery(\'#crdate_from_visual\').datetimepicker({
    	dateFormat: \'' . $this->pi_getLL('locale_date_format_js') . '\',
        showSecond: true,
		timeFormat: \'HH:mm:ss\',
        altField: "#crdate_from",
        altFormat: "yy-mm-dd",
        altFieldTimeOnly: false,
        altTimeFormat: "HH:mm:ss"
    });
	jQuery(\'#crdate_till_visual\').datetimepicker({
    	dateFormat: \'' . $this->pi_getLL('locale_date_format_js') . '\',
        showSecond: true,
		timeFormat: \'HH:mm:ss\',
		hour: 23,
        minute: 59,
        second: 59,
        altField: "#crdate_till",
        altFormat: "yy-mm-dd",
        altFieldTimeOnly: false,
        altTimeFormat: "HH:mm:ss"
    });
    $(document).on("change", "#crdate_from_visual", function(){
        if ($(this).val()==\'\') {
            $(\'#crdate_from\').val(\'\');
        }
    });
    $(document).on("change", "#crdate_till_visual", function(){
        if ($(this).val()==\'\') {
            $(\'#crdate_till\').val(\'\');
        }
    });
	jQuery(\'#check_all_1\').click(function() {
		//checkAllPrettyCheckboxes(this,jQuery(\'.msadmin_orders_listing\'));
		$(\'th > div.checkbox > input:checkbox\').prop(\'checked\', this.checked);
	});
	var originalLeave = $.fn.popover.Constructor.prototype.leave;
	$.fn.popover.Constructor.prototype.leave = function(obj){
	  var self = obj instanceof this.constructor ? obj : $(obj.currentTarget)[this.type](this.getDelegateOptions()).data(\'bs.\' + this.type)
	  var container, timeout;
	  originalLeave.call(this, obj);
	  if(obj.currentTarget) {
		container = $(obj.currentTarget).siblings(\'.popover\')
		timeout = self.timeout;
		container.one(\'mouseenter\', function(){
		  //We entered the actual popover – call off the dogs
		  clearTimeout(timeout);
		  //Let\'s monitor popover content instead
		  container.one(\'mouseleave\', function(){
			  $.fn.popover.Constructor.prototype.leave.call(self, self);
			  $(".popover-link").popover("hide");
		  });
		})
	  }
	};
	$(".popover-link").popover({
		placement: "bottom",
		html: true,
		trigger:"hover",
		delay: {show: 20, hide: 200}
	});
	var tooltip_is_shown=\'\';
	$(\'.popover-link\').on(\'show.bs.popover, mouseover\', function () {
		var customer_id=$(this).attr(\'rel\');
		var that=$(this);
		//if (tooltip_is_shown != customer_id) {
			tooltip_is_shown=customer_id;
			$.ajax({
				type:   "POST",
				url:    \'' . mslib_fe::typolink($this->shop_pid . ',2002', '&tx_multishop_pi1[page_section]=getAdminCustomersListingDetails&') . '\',
				data:   \'tx_multishop_pi1[customer_id]=\'+customer_id,
				dataType: "json",
				success: function(data) {
					if (data.html!="") {
						that.next().html(\'<div class="arrow"></div><h3 class="popover-title">Customers</h3><div class="popover-content">\' + data.html + \'</div>\');
						//that.next().popover("show");
						//$(that).popover(\'show\');
					} else {
						$(".popover").remove();
					}
					/*that.next().html(data.html);
					that.tooltip(\'show\', {
						position: \'down\',
						placement: \'auto\',
						html: true
					});*/
				}
			});
		//}
	});
	jQuery(document).on(\'submit\', \'#customers_listing\', function(){
		if (jQuery(\'#selected_customers_action\').val()==\'delete_selected_customers\') {
			if (confirm(\'' . htmlspecialchars($this->pi_getLL('are_you_sure')) . '?\')) {
				return true;
			}
			return false;
		}
		' . $extra_selected_customers_action_js_filters . '
	});
	$(".invoice_select2").select2();
	$(".ordered_product").select2({
		placeholder: "' . $this->pi_getLL('all') . '",
		minimumInputLength: 0,
		query: function(query) {
			$.ajax("' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_ordered_products') . '", {
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
				$.ajax("' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_ordered_products') . '", {
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
});
</script>
';
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
			content: $(\'#msAdminPostMessage\').html(),
			cancelButton: false // hides the cancel button.
		});
	});
	</script>
	';
}
// Instantiate admin interface object
$objRef = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:multishop/pi1/classes/class.tx_mslib_admin_interface.php:&tx_mslib_admin_interface');
$objRef->init($this);
$objRef->setInterfaceKey('admin_customers');
// Header buttons
$headerButtons = array();
$headingButton = array();
$headingButton['btn_class'] = 'btn btn-primary';
$headingButton['fa_class'] = 'fa fa-plus-circle';
$headingButton['title'] = $this->pi_getLL('admin_new_customer');
$headingButton['href'] = mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=add_customer&action=add_customer');
$headerButtons[] = $headingButton;
// Set header buttons through interface class so other plugins can adjust it
$objRef->setHeaderButtons($headerButtons);
// Get header buttons through interface class so we can render them
$interfaceHeaderButtons = $objRef->renderHeaderButtons();
foreach ($tabs as $key => $value) {
    $content .= '
		<div class="panel-heading">
			<h3>' . $value[0] . '</h3>
			 ' . $interfaceHeaderButtons . '
		</div>
		<div class="panel-body">
		<form id="form1" name="form1" method="get" action="index.php">
		' . $formTopSearch . '
		</form>
		' . $value[1] . '
	';
    break;
}
$content .= '<hr><div class="clearfix"><a class="btn btn-success msAdminBackToCatalog" href="' . mslib_fe::typolink() . '"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span> ' . $this->pi_getLL('admin_close_and_go_back_to_catalog') . '</a></div></div>';
$content = '<div class="panel panel-default">' . mslib_fe::shadowBox($content) . '</div>';
?>
