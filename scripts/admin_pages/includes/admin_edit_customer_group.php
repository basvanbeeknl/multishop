<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$output = array();
// now parse all the objects in the tmpl file
if ($this->conf['admin_edit_customer_group_tmpl_path']) {
    $template = $this->cObj->fileResource($this->conf['admin_edit_customer_group_tmpl_path']);
} else {
    $template = $this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/admin_edit_customer_group.tmpl');
}
// Extract the subparts from the template
if ($this->post) {
    $insertArray = array();
    $insertArray['title'] = $this->post['group_name'];
    $insertArray['pid'] = $this->conf['fe_customer_pid'];
    $insertArray['tx_multishop_discount'] = $this->post['discount'];
    // custom page hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer_groups.php']['adminUpdateCustomerGroupPreProc'])) {
        $params = array(
                'insertArray' => &$insertArray,
                'customer_group_id' => &$this->post['customer_group_id']
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer_groups.php']['adminUpdateCustomerGroupPreProc'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    // custom page hook that can be controlled by third-party plugin eof
    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('fe_groups', 'uid=' . $this->post['customer_group_id'], $insertArray);
    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
    $users = mslib_fe::getUsers($this->conf['fe_customer_usergroup'], 'name');
    if (is_array($users) && count($users)) {
        $posted_users=explode(',', $this->post['tx_multishop_pi1']['users']);
        foreach ($users as $user) {
            // check if the user should be member or not
            if (in_array($user['uid'], $posted_users)) {
                $add_array = array();
                $remove_array = array();
                $add_array[] = $this->post['customer_group_id'];
                $group_string = mslib_fe::updateFeUserGroup($user['uid'], $add_array, $remove_array);
            } else {
                $add_array = array();
                $remove_array = array();
                $remove_array[] = $this->post['customer_group_id'];
                $group_string = mslib_fe::updateFeUserGroup($user['uid'], $add_array, $remove_array);
            }
        }
    }
    // customer shipping/payment method mapping
    if ($this->post['customer_group_id'] && $this->ms['MODULES']['CUSTOMER_EDIT_METHOD_FILTER']) {
        // shipping/payment methods
        $query = $GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_customers_groups_method_mappings', 'customers_groups_id=\'' . $this->post['customer_group_id'] . '\'');
        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
        if (is_array($this->post['payment_method']) and count($this->post['payment_method'])) {
            foreach ($this->post['payment_method'] as $payment_method_id => $value) {
                $updateArray = array();
                $updateArray['customers_groups_id'] = $this->post['customer_group_id'];
                $updateArray['method_id'] = $payment_method_id;
                $updateArray['type'] = 'payment';
                $updateArray['negate'] = $value;
                $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_customers_groups_method_mappings', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
        }
        if (is_array($this->post['shipping_method']) and count($this->post['shipping_method'])) {
            foreach ($this->post['shipping_method'] as $shipping_method_id => $value) {
                $updateArray = array();
                $updateArray['customers_groups_id'] = $this->post['customer_group_id'];
                $updateArray['method_id'] = $shipping_method_id;
                $updateArray['type'] = 'shipping';
                $updateArray['negate'] = $value;
                $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_customers_groups_method_mappings', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
        }
        // shipping/payment methods eof
    }
    // custom page hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer_groups.php']['adminUpdateCustomerGroupPostProc'])) {
        $params = array(
                'customer_group_id' => &$this->post['customer_group_id']
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer_groups.php']['adminUpdateCustomerGroupPostProc'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    if ($this->post['tx_multishop_pi1']['referrer']) {
        header("Location: " . $this->post['tx_multishop_pi1']['referrer']);
        exit();
    } else {
        header("Location: " . $this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_customer_groups', 1));
        exit();
    }
}
// customer to shipping/payment method mapping
$shipping_payment_method = '';
if ($this->ms['MODULES']['GROUP_EDIT_METHOD_FILTER']) {
    $payment_methods = mslib_fe::loadPaymentMethods();
    // loading shipping methods eof
    $shipping_methods = mslib_fe::loadShippingMethods();
    if (count($payment_methods) or count($shipping_methods)) {
        // the value is are the negate value
        // negate 1 mean the shipping/payment are excluded
        $shipping_payment_method .= '
						<div class="form-group div_products_mappings toggle_advanced_option" id="msEditProductInputPaymentMethod">
							<label class="control-label col-md-2">' . $this->pi_getLL('admin_mapped_methods') . '</label>
							<div class="col-md-10">
							<div class="innerbox_methods">
								<div class="innerbox_payment_methods">
									<p class="form-control-static"><strong>' . $this->pi_getLL('admin_payment_methods') . '</strong></p>
									';
        // load mapped ids
        $method_mappings = array();
        if ($this->get['customer_group_id']) {
            $method_mappings = mslib_befe::getMethodsByGroup($this->get['customer_group_id']);
        }
        $tr_type = '';
        if (is_array($payment_methods) && count($payment_methods)) {
            foreach ($payment_methods as $code => $item) {
                if (!$tr_type or $tr_type == 'even') {
                    $tr_type = 'odd';
                } else {
                    $tr_type = 'even';
                }
                $count++;
                $shipping_payment_method .= '<div class="form-group" id="multishop_payment_method_' . $item['id'] . '"><label class="control-label col-md-3">' . $item['name'] . '</label><div class="col-md-9">';
                if ($price_wrap) {
                    $tmpcontent .= $price_wrap;
                }
                $shipping_payment_method .= '<div class="checkbox checkbox-success checkbox-inline"><input name="payment_method[' . htmlspecialchars($item['id']) . ']" class="payment_method_cb" id="enable_payment_method_' . $item['id'] . '" type="checkbox" rel="' . $item['id'] . '" value="0"' . ((is_array($method_mappings['payment']) && in_array($item['id'], $method_mappings['payment']) && !$method_mappings['payment']['method_data'][$item['id']]['negate']) ? ' checked' : '') . ' /><label for="enable_payment_method_' . $item['id'] . '">' . $this->pi_getLL('enable') . '</label></div>';
                $shipping_payment_method .= '<div class="checkbox checkbox-success checkbox-inline"><input name="payment_method[' . htmlspecialchars($item['id']) . ']" class="payment_method_cb" id="disable_payment_method_' . $item['id'] . '" type="checkbox" rel="' . $item['id'] . '" value="1"' . ((is_array($method_mappings['payment']) && in_array($item['id'], $method_mappings['payment']) && $method_mappings['payment']['method_data'][$item['id']]['negate'] > 0) ? ' checked' : '') . ' /><label for="disable_payment_method_' . $item['id'] . '">' . $this->pi_getLL('disable') . '</label></div>';
                $shipping_payment_method .= '</div></div>';
            }
        }
        $shipping_payment_method .= '
								</div>
								<div class="innerbox_shipping_methods" id="msEditProductInputShippingMethod">
									<p class="form-control-static"><strong>' . $this->pi_getLL('admin_shipping_methods') . '</strong></p>
							 		';
        $count = 0;
        $tr_type = '';
        if (is_array($shipping_methods) && count($shipping_methods)) {
            foreach ($shipping_methods as $code => $item) {
                $count++;
                $shipping_payment_method .= '<div class="form-group" id="multishop_shipping_method"><label class="control-label col-md-3">' . $item['name'] . '</label><div class="col-md-9">';
                if ($price_wrap) {
                    $shipping_payment_method .= $price_wrap;
                }
                $shipping_payment_method .= '<div class="checkbox checkbox-success checkbox-inline"><input name="shipping_method[' . htmlspecialchars($item['id']) . ']" class="shipping_method_cb" id="enable_shipping_method_' . $item['id'] . '" type="checkbox" rel="' . $item['id'] . '" value="0"' . ((is_array($method_mappings['shipping']) && in_array($item['id'], $method_mappings['shipping']) && !$method_mappings['shipping']['method_data'][$item['id']]['negate']) ? ' checked' : '') . '  /><label for="enable_shipping_method_' . $item['id'] . '">' . $this->pi_getLL('enable') . '</label></div>';
                $shipping_payment_method .= '<div class="checkbox checkbox-success checkbox-inline"><input name="shipping_method[' . htmlspecialchars($item['id']) . ']" class="shipping_method_cb" id="disable_shipping_method_' . $item['id'] . '" type="checkbox" rel="' . $item['id'] . '" value="1"' . ((is_array($method_mappings['shipping']) && in_array($item['id'], $method_mappings['shipping']) && $method_mappings['shipping']['method_data'][$item['id']]['negate'] > 0) ? ' checked' : '') . '  /><label for="disable_shipping_method_' . $item['id'] . '">' . $this->pi_getLL('disable') . '</label></div>';
                $shipping_payment_method .= '</div></div>';
            }
        }
        $shipping_payment_method .= '
								</div>
							</div>
						</div></div>';
    }
}
$group = mslib_fe::getGroup($this->get['customer_group_id'], 'uid');
$group['tx_multishop_remaining_budget'] = round($group['tx_multishop_remaining_budget'], 13);
$subparts = array();
$subparts['template'] = $this->cObj->getSubpart($template, '###TEMPLATE###');
$subparts['members_option'] = $this->cObj->getSubpart($subparts['template'], '###MEMBERS_OPTION###');
// now lets load the users
$members_selected = array();
if (is_numeric($this->get['customer_group_id'])) {
    $users = mslib_fe::getUsers($this->get['customer_group_id'], 'name');
    if (is_array($users) && count($users)) {
        foreach ($users as $user) {
            $members_selected[] = $user['uid'];
        }
    }
}
$subpartArray = array();
$subpartArray['###ADMIN_LABEL_TABS_EDIT_CUSTOMER_GROUP###'] = $this->pi_getLL('edit_group');
$subpartArray['###LABEL_HEADING###'] = $this->pi_getLL('edit_group');
$subpartArray['###FORM_ACTION###'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_customer_group&action=' . $_REQUEST['action'] . '&customer_group_id=' . $_REQUEST['customer_group_id']);
$subpartArray['###CUSTOMER_GROUP_ID###'] = $_REQUEST['customer_group_id'];
$subpartArray['###FORM_INPUT_ACTION###'] = $_REQUEST['action'];
$subpartArray['###LABEL_NAME###'] = $this->pi_getLL('name');
$subpartArray['###VALUE_GROUP_NAME###'] = htmlspecialchars($group['title']);
$subpartArray['###LABEL_ADMIN_NO###'] = $this->pi_getLL('admin_no');
$subpartArray['###DISCOUNT_INPUT###'] = '';
if ($this->ms['MODULES']['ENABLE_FE_GROUP_DISCOUNT_PERCENTAGE']) {
    $subpartArray['###DISCOUNT_INPUT###'] = '<div class="form-group">
        <label class="control-label col-md-2">' . $this->pi_getLL('discount') . '</label>
        <div class="col-md-10">
            <div class="input-group"><input type="text" class="form-control" name="discount" size="2" maxlength="2" id="discount" value="' . htmlspecialchars($group['tx_multishop_discount']) . '" /><span class="input-group-addon width-auto">%</span></div>
        </div>
    </div>';
}
$subpartArray['###MEMBERS_SELECTED###'] = implode(',', $members_selected);
$subpartArray['###LABEL_MEMBERS###'] = 'MEMBERS';
$subpartArray['###LABEL_BUTTON_SAVE###'] = $this->pi_getLL('save');
$subpartArray['###VALUE_REFERRER###'] = '';
if ($this->post['tx_multishop_pi1']['referrer']) {
    $subpartArray['###VALUE_REFERRER###'] = $this->post['tx_multishop_pi1']['referrer'];
} else {
    $subpartArray['###VALUE_REFERRER###'] = $_SERVER['HTTP_REFERER'];
}
$subpartArray['###INPUT_EDIT_SHIPPING_AND_PAYMENT_METHOD###'] = $shipping_payment_method;
// plugin marker place holder
$plugins_extra_tab = array();
$js_extra = array();
$plugins_extra_tab['tabs_header'] = array();
$plugins_extra_tab['tabs_content'] = array();
// custom page hook that can be controlled by third-party plugin
if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer_groups.php']['adminEditCustomerGroupTmplPreProc'])) {
    $params = array(
            'subpartArray' => &$subpartArray,
            'group' => &$group,
            'plugins_extra_tab' => &$plugins_extra_tab,
            'js_extra' => &$js_extra
    );
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_customer_groups.php']['adminEditCustomerGroupTmplPreProc'] as $funcRef) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
    }
}
// custom page hook that can be controlled by third-party plugin eof
if (!count($plugins_extra_tab['tabs_header']) && !count($plugins_extra_tab['tabs_content'])) {
    $subpartArray['###LABEL_EXTRA_PLUGIN_TABS###'] = '';
    $subpartArray['###CONTENT_EXTRA_PLUGIN_TABS###'] = '';
} else {
    $subpartArray['###LABEL_EXTRA_PLUGIN_TABS###'] = implode("\n", $plugins_extra_tab['tabs_header']);
    $subpartArray['###CONTENT_EXTRA_PLUGIN_TABS###'] = implode("\n", $plugins_extra_tab['tabs_content']);
}
if (!count($js_extra['functions'])) {
    $subpartArray['###JS_FUNCTIONS_EXTRA###'] = '';
} else {
    $subpartArray['###JS_FUNCTIONS_EXTRA###'] = implode("\n", $js_extra['functions']);
}
if (!count($js_extra['triggers'])) {
    $subpartArray['###JS_TRIGGERS_EXTRA###'] = '';
} else {
    $subpartArray['###JS_TRIGGERS_EXTRA###'] = implode("\n", $js_extra['triggers']);
}
$subpartArray['###LABEL_BACK_TO_CATALOG###'] = $this->pi_getLL('admin_close_and_go_back_to_catalog');
$subpartArray['###BACK_TO_CATALOG_LINK###'] = mslib_fe::typolink();
$head = '';
$head .= '
<script type="text/javascript">
jQuery(document).ready(function($) {
	var usersSearchList=[];
	var usersList=[];
	var ajax_url="' . mslib_fe::typolink($this->shop_pid . ',2002', '&tx_multishop_pi1[page_section]=get_users') . '"
	$("#userIdSelect2").select2({
		placeholder: "' . $this->pi_getLL('select_members') . '",
		multiple: true,
		minimumInputLength: 2,
		query: function(query) {
			if (usersSearchList[query.term] !== undefined) {
				query.callback({results: usersSearchList[query.term]});
			} else {
				$.ajax(ajax_url, {
					data: {
						q: query.term
					},
					dataType: "json"
				}).done(function(data) {
					usersSearchList[query.term]=data;
					query.callback({results: data});
				});
			}
		},
		initSelection: function(element, callback) {
			var id=$(element).val();
			if (id!=="") {
				if (usersList[id] !== undefined) {
					callback(usersList[id]);
				} else {
					$.ajax(ajax_url, {
						data: {
							preselected_id: id
						},
						dataType: "json"
					}).done(function(data) {
						usersList[data.id]={id: data.id, text: data.text};
						callback(data);
					});
				}
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
		dropdownCssClass: "users_list_dropdown",
		escapeMarkup: function (m) { return m; }
	});
}); //end of first load
</script>';
$GLOBALS['TSFE']->additionalHeaderData[] = $head;
$content .= $this->cObj->substituteMarkerArrayCached($subparts['template'], array(), $subpartArray);
?>