<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if ($this->post) {
	if (is_array($this->post['shipping_zone']) && count($this->post['shipping_zone'])) {
		$shipping_methods=mslib_fe::loadShippingMethods();
		$zones=mslib_fe::loadAllCountriesZones();
		foreach ($zones['zone_id'] as $zone_id) {
			foreach ($shipping_methods as $shipping_method) {
				if ($this->post['shipping_zone'][$zone_id][$shipping_method['id']]) {
					// add mapping
					$insertArray=array();
					$insertArray['zone_id']=$zone_id;
					$insertArray['shipping_method_id']=$shipping_method['id'];
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_shipping_methods_to_zones', $insertArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				} else {
					// delete mapping
					$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_shipping_methods_to_zones', 'zone_id=\''.$zone_id.'\' and shipping_method_id=\''.$shipping_method['id'].'\'');
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
		}
		header('Location: /'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_shipping_modules').'#admin_shipping_method_zone_mappings');
		exit();
	}
	if (is_array($this->post['checkbox']) && count($this->post['checkbox'])) {
		$shipping_methods=mslib_fe::loadShippingMethods();
		$payment_methods=mslib_fe::loadPaymentMethods();
		foreach ($shipping_methods as $shipping_method) {
			foreach ($payment_methods as $payment_method) {
				if ($this->post['checkbox'][$shipping_method['id']][$payment_method['id']]) {
					// add mapping
					$insertArray=array();
					$insertArray['shipping_method']=$shipping_method['id'];
					$insertArray['payment_method']=$payment_method['id'];
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_payment_shipping_mappings', $insertArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				} else {
					// delete mapping
					$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_payment_shipping_mappings', 'shipping_method=\''.$shipping_method['id'].'\' and payment_method=\''.$payment_method['id'].'\'');
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
		}
		header('Location: /'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_shipping_modules').'#admin_shipping_payment_mappings');
		exit();
	}
	if ($this->post['sub']=='update_shipping_method' && $this->post['shipping_method_id']) {
		// update shipping method
		$row=mslib_fe::getShippingMethod($this->post['shipping_method_id'], 's.id');
		if ($row['id']) {
			$data=unserialize($row['vars']);
			foreach ($this->post as $key=>$value) {
				$data[$key]=$this->post[$key];
			}
			// now update the baby
			$updateArray=array();
			$updateArray['page_uid']=$this->post['related_shop_pid'];
			$updateArray['handling_costs']=$this->post['handling_costs'];
			$updateArray['cart_minimum_amount']=$this->post['cart_minimum_amount'];
			$updateArray['cart_maximum_amount']=$this->post['cart_maximum_amount'];
			$updateArray['tax_id']=$this->post['tax_id'];
			$updateArray['vars']=serialize($data);
			$updateArray['enable_on_default']=$this->post['enable_on_default'];
			$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods', 'id=\''.$this->post['shipping_method_id'].'\'', $updateArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			foreach ($this->post['name'] as $key=>$value) {
				$updateArray=array();
				$updateArray['name']=$this->post['name'][$key];
				$updateArray['description']=$this->post['description'][$key];
				$str="select 1 from tx_multishop_shipping_methods_description where id='".$row['id']."' and language_id='".$key."'";
				$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
					$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods_description', 'id=\''.$row['id'].'\' and language_id=\''.$key.'\'', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				} else {
					$updateArray['id']=$row['id'];
					$updateArray['language_id']=$key;
					$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_shipping_methods_description', $updateArray);
					$res=$GLOBALS['TYPO3_DB']->sql_query($query);
				}
			}
			$this->ms['show_main']=1;
			header('Location: /'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_shipping_modules'));
			exit();
		}
	} else if ($this->post['sub']=='add_shipping_method' && $this->post['shipping_method_code']) {
		$erno=array();
		$check=mslib_fe::getShippingMethod($this->post['custom_code'], 's.code');
		if ($check['id']) {
			$erno[]='<li>Code already in use</li>';
		}
		if (!count($erno)) {
			// save shipping method
			$insertArray=array();
			$insertArray['code']=$this->post['custom_code'];
			$insertArray['handling_costs']=$this->post['handling_costs'];
			$insertArray['tax_id']=$this->post['tax_id'];
			$insertArray['date']=time();
			$insertArray['status']=1;
			$insertArray['page_uid']=$this->post['related_shop_pid'];
			$insertArray['provider']=$_REQUEST['shipping_method_code'];
			$insertArray['vars']=serialize($this->post);
			$insertArray['handling_costs']=$this->post['handling_costs'];
			$insertArray['cart_minimum_amount']=$this->post['cart_minimum_amount'];
			$insertArray['cart_maximum_amount']=$this->post['cart_maximum_amount'];
			$updateArray['enable_on_default']=$this->post['enable_on_default'];
			$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_shipping_methods', $insertArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			if ($res) {
				$id=$GLOBALS['TYPO3_DB']->sql_insert_id();
				foreach ($this->post['name'] as $key=>$value) {
					$updateArray=array();
					$updateArray['name']=$this->post['name'][$key];
					$updateArray['description']=$this->post['description'][$key];
					$str="select 1 from tx_multishop_shipping_methods_description where id='".$id."' and language_id='".$key."'";
					$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
						$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods_description', 'id=\''.$id.'\' and language_id=\''.$key.'\'', $updateArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					} else {
						$updateArray['id']=$id;
						$updateArray['language_id']=$key;
						$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_shipping_methods_description', $updateArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
					}
				}
				$this->ms['show_main']=1;
				header('Location: /'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_shipping_modules'));
				exit();
			}
		}
	}
}
$GLOBALS['TSFE']->additionalHeaderData['admin_shipping_methods_edit']='
<script type="text/javascript">
function mathRound(float) {
	//return float;
	return Math.round(float*100)/100;
}
jQuery(document).ready(function($) {
	$(document).on("click", "#add_shipping_method", function(e){
		e.preventDefault();
		$(\'#admin_shipping_methods_list\').slideToggle(\'slow\', function(){});
	});
	$(document).on("keyup", ".msHandlingCostExcludingVat", function(e) {
		if (e.keyCode!=9) {
			priceEditRealtimeCalc(true, this);
		}
	});
	$(document).on("change", "#tax_id", function() {
		jQuery(".msHandlingCostExcludingVat").each(function(i) {
			priceEditRealtimeCalc(true, this);
		});
	});
	$(document).on("keyup", ".msHandlingCostIncludingVat", function(e) {
		if (e.keyCode!=9) {
			priceEditRealtimeCalc(false, this);
		}
	});
	$("#add_shipping_form").submit(function(e) {
		if (!$("#name_0").val()) {
			e.preventDefault();
			$("#name_0").focus();
			alert("'.addslashes($this->pi_getLL('shipping_name_is_required')).'!");
		} else if (!$("#custom_code").val()) {
			e.preventDefault();
			$("#custom_code").focus();
			alert("'.addslashes($this->pi_getLL('code_is_required')).'!");
		} else {
			return true;
		}
	});
	$(document).on("change", "#handling_cost_type", function(){
		if ($(this).val()=="amount") {
			$("#handling_cost_amount_div").show();
			$("#handling_cost_amount_input").removeAttr("disabled");
			$("#handling_cost_percentage_div").hide();
			$("#handling_cost_percentage_input").attr("disabled", "disabled");
		} else if ($(this).val()=="percentage") {
			$("#handling_cost_amount_div").hide();
			$("#handling_cost_amount_input").attr("disabled", "disabled");
			$("#handling_cost_percentage_div").show();
			$("#handling_cost_percentage_input").removeAttr("disabled");
		}
	});
});
</script>';
$active_shop=mslib_fe::getActiveShop();
$shipping_methods=mslib_fe::loadAllShippingMethods();
if (($this->get['sub']=='add_shipping_method' && $this->get['shipping_method_code']) || ($this->post['sub']=='add_shipping_method' && $this->post['shipping_method_code'] && count($erno)>0)) {
	if (count($erno)>0 || $this->get) {
		if (is_array($erno) and count($erno)>0) {
			$content.='<div class="alert alert-danger">';
			$content.='<h3>'.$this->pi_getLL('the_following_errors_occurred').'</h3><ul>';
			foreach ($erno as $item) {
				$content.='<li>'.$item.'</li>';
			}
			$content.='</ul>';
			$content.='</div>';
		}
		$shipping_method=$shipping_methods[$this->get['shipping_method_code']];
		$tmpcontent.='<form id="add_payment_form" class="form-horizontal" action="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']).'" method="post">';
		foreach ($this->languages as $key=>$language) {
			$tmpcontent.='
			<div class="panel panel-default">
				<div class="panel-heading panel-heading-toggle'.(($language['uid']===0 || !empty($lngstatus[$language['uid']]['name'])) ? '' : ' collapsed').'" data-toggle="collapse" data-target="#msEditShippingModulesInputName_'.$language['uid'].'">
					<h3 class="panel-title">
						<a role="button" data-toggle="collapse" href="#msEditShippingModulesInputName_'.$language['uid'].'"><i class="fa fa-file-text-o"></i> '.$language['title'].'</a>
					</h3>
				</div>
				<div id="msEditShippingModulesInputName_'.$language['uid'].'" class="panel-collapse collapse'.(($language['uid']===0 || !empty($lngstatus[$language['uid']]['name'])) ? ' in' : '').'">
					<div class="panel-body">
						<div class="form-group">
							<label for="name" class="control-label col-md-2">'.$this->pi_getLL('admin_name').'</label>
							<div class="col-md-10">
								<input type="text" class="form-control text" name="name['.$language['uid'].']" id="name_'.$language['uid'].'" value="'.htmlspecialchars($this->post['name'][$language['uid']]).'" required="required">
							</div>
						</div>
						<div class="form-group">
							<label for="description" class="control-label col-md-2">'.$this->pi_getLL('admin_short_description').'</label>
							<div class="col-md-10">
								<textarea name="description['.$language['uid'].']" id="description['.$language['uid'].']" class="mceEditor" rows="4">'.htmlspecialchars($this->post['description'][$language['uid']]).'</textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
			';
		}
		$tmpcontent.='
		<div class="form-group">
			<label for="custom_code" class="control-label col-md-2">'.$this->pi_getLL('code').'</label>
			<div class="col-md-10">
				<input name="custom_code" id="custom_code" type="text" class="form-control" value="'.htmlspecialchars($this->post['custom_code']).'" required="required" />
			</div>
		</div>';
		if (count($active_shop)>1) {
			$tmpcontent.='
					<div class="form-group">
						<label for="related_shop_pid" class="control-label col-md-2">'.$this->pi_getLL('relate_shipping_to_shop', 'Relate this method to').'</label>
						<div class="col-md-10">
						<div class="radio radio-success radio-inline"><input name="related_shop_pid" id="related_shop_pid" type="radio" value="0" checked="checked"/><label for="related_shop_pid">'.$this->pi_getLL('relate_shipping_to_all_shop', 'All shop').'</label></div>';
			foreach ($active_shop as $pageinfo) {
                $pageTitle=$pageinfo['title'];
                if ($pageinfo['nav_title']) {
                    $pageTitle=$pageinfo['nav_title'];
                }
				$tmpcontent.='<div class="radio radio-success radio-inline"><input name="related_shop_pid" id="related_shop_pid'.$pageinfo['uid'].'" type="radio" value="'.$pageinfo['uid'].'"'.(($this->shop_pid==$pageinfo['uid']) ? ' checked="checked"' : '').' /><label for="related_shop_pid'.$pageinfo['uid'].'">'.$pageTitle.'</label></div>';
			}
			$tmpcontent.='
					</div></div>';
		} else {
			$tmpcontent.='<input type="hidden" name="related_shop_pid" value="'.$this->shop_pid.'">';
		}
		$percentage_cost=false;
		if (strpos($this->post['handling_costs'], '%')!==false) {
			$percentage_cost=true;
		}
		$tmpcontent.='
		<div class="form-group">
			<label class="control-label col-md-2">'.$this->pi_getLL('handling_costs_type').'</label>
			<div class="col-md-10">
			<div class="msAttribute">
				<select name="handling_costs_type" id="handling_cost_type" class="form-control">
					<option value="amount"'.(!$percentage_cost ? ' selected="selected"' : '').'>amount</option>
					<option value="percentage"'.($percentage_cost ? ' selected="selected"' : '').'>percentage</option>
				</select>
			</div>
			</div>
		</div>
		<div class="form-group" id="handling_cost_percentage_div"'.(!$percentage_cost ? ' style="display:none"' : '').'>
			<label class="control-label col-md-2">'.$this->pi_getLL('handling_costs').'</label>
			<div class="col-md-10">
			<div class="msAttribute">
				<input name="handling_costs" id="handling_cost_percentage_input" type="text" value="'.str_replace('%', '', $this->post['handling_costs']).'%"'.(!$percentage_cost ? ' disabled="disabled"' : '').' />
			</div>
			</div>
		</div>
		<div class="form-group" id="handling_cost_amount_div"'.($percentage_cost ? ' style="display:none"' : '').'>
			<label class="control-label col-md-2">'.$this->pi_getLL('handling_costs').'</label>
			<div class="col-md-10">
			<div class="msAttribute">
				<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msHandlingCostExcludingVat priceInputDisplay" value="'.str_replace('%', '', $this->post['handling_costs']).'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
				<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msHandlingCostIncludingVat priceInputDisplay" value="0.00" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
				<div class="msAttributesField hidden"><input name="handling_costs" id="handling_cost_amount_input" type="hidden" class="priceInputReal" value="'.str_replace('%', '', $this->post['handling_costs']).'" /></div>
			</div>
			</div>
		</div>
		<div class="form-group" id="min_amount_to_show">
			<label class="control-label col-md-2">'.$this->pi_getLL('cart_minimum_amount').'</label>
			<div class="col-md-10">
				<div class="msAttribute">
					<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msHandlingCostExcludingVat priceInputDisplay" value="0.00" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
					<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msHandlingCostIncludingVat priceInputDisplay" value="0.00" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
					<div class="msAttributesField hidden"><input name="cart_minimum_amount" type="hidden" class="priceInputReal" value="0.00" id="cart_minimum_amount" /></div>
				</div>
			</div>
		</div>
		<div class="form-group" id="max_amount_to_show">
			<label class="control-label col-md-2">'.$this->pi_getLL('cart_maximum_amount').'</label>
			<div class="col-md-10">
				<div class="msAttribute">
					<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msHandlingCostExcludingVat priceInputDisplay" value="0.00" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
					<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msHandlingCostIncludingVat priceInputDisplay" value="0.00" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
					<div class="msAttributesField hidden"><input name="cart_maximum_amount" type="hidden" class="priceInputReal" value="0.00" id="cart_maximum_amount" /></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label for="tax_id" class="control-label col-md-2">'.$this->pi_getLL('admin_vat_rate').'</label>
			<div class="col-md-10">
				<select name="tax_id" id="tax_id" class="form-control "><option value="0">'.$this->pi_getLL('admin_label_no_tax').'</option>';
        $str="SELECT trg.*, t.rate FROM `tx_multishop_tax_rule_groups` trg, `tx_multishop_tax_rules` tr, `tx_multishop_taxes` t where trg.rules_group_id=tr.rules_group_id and tr.tax_id=t.tax_id group by trg.rules_group_id order by trg.rules_group_id asc";
		$str="SELECT * FROM `tx_multishop_tax_rule_groups`";
		$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
        $tax_list_data=array();
		while (($tax_group=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
            $tax_list_data[]='product_tax_rate_list_js["'.$tax_group['rules_group_id'].'"]="'.round(number_format($tax_group['rate'], 2), 2).'"';
			if (!$this->get['shipping_method_id']) {
				$tmpcontent.='<option value="'.$tax_group['rules_group_id'].'" '.(($tax_group['default_status']) ? 'selected' : '').'>'.htmlspecialchars($tax_group['name']).'</option>';
			} else {
				$tmpcontent.='<option value="'.$tax_group['rules_group_id'].'" '.(($tax_group['rules_group_id']==$this->post['tax_id']) ? 'selected' : '').'>'.htmlspecialchars($tax_group['name']).'</option>';
			}
		}
        // js definition for tax
        $product_tax_rate_js=array();
        $product_tax_rate_js[]='var product_tax_rate_list_js=[];';
        if (count($tax_list_data)) {
            $product_tax_rate_js = $tax_list_data;
        }
        $GLOBALS['TSFE']->additionalHeaderData[]='
        <script type="text/javascript" data-ignore="1">
           var product_id="'.$this->get['pid'].'"
           var product_tax_rate_list_js=[]
           '.implode("\n", $product_tax_rate_js).'
        </script>
        ';
		$tmpcontent.='
				</select>
			</div>
		</div>';
		$tmpcontent.=mslib_fe::parseShippingMethodEditForm($shipping_method, $this->post);
		$tmpcontent.='
		<div class="form-group">
			<label class="control-label col-md-2">'.$this->pi_getLL('admin_label_method_is_enabled_on_default').'</label>
			<div class="col-md-10">
				<div class="radio radio-success radio-inline input_label_wrapper"><input type="radio" name="enable_on_default" value="1" id="enable_on_default_yes" checked="checked" /><label for="enable_on_default_yes">'.$this->pi_getLL('yes').'</label></div>
				<div class="radio radio-success radio-inline input_label_wrapper"><input type="radio" name="enable_on_default" value="0" id="enable_on_default_no" /><label for="enable_on_default_no">'.$this->pi_getLL('no').'</label></div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-md-10 col-md-offset-2">
				<input name="shipping_method_code" type="hidden" value="'.htmlspecialchars($this->get['shipping_method_code']).'" />
				<input name="sub" type="hidden" value="add_shipping_method" />
				<button name="Submit" class="btn btn-success" type="submit" value=""><i class="fa fa-save"></i> '.$this->pi_getLL('save').'</button>
			</div>
		</div>
		</form>';
		$content.=mslib_fe::returnBoxedHTML($shipping_method['name'], $tmpcontent);
		$tmpcontent='';
	}
} elseif ($this->get['edit']) {
	$row=mslib_fe::getShippingMethod($this->get['shipping_method_id'], 's.id');
	$str="SELECT * from tx_multishop_shipping_methods_description where id='".$row['id']."'";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$lngproduct=array();
	while (($tmprow=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
		$lngproduct[$tmprow['language_id']]=$tmprow;
	}
	$psp=$shipping_methods[$row['provider']];
	$inner_content=mslib_fe::parseShippingMethodEditForm($psp, unserialize($row['vars']), 1);
	$tmpcontent.='
	<div class="panel panel-default">
	<div class="panel-body">
	<form id="add_payment_form" class="form-horizontal" action="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']).'" method="post">
	<input name="sub" type="hidden" value="update_shipping_method" />
	<input name="shipping_method_id" type="hidden" value="'.$row['id'].'" />';
	foreach ($this->languages as $key=>$language) {
		$tmpcontent.='
			<div class="panel panel-default">
				<div class="panel-heading panel-heading-toggle'.(($language['uid']===0 || !empty($lngstatus[$language['uid']]['name'])) ? '' : ' collapsed').'" data-toggle="collapse" data-target="#msEditShippingModulesInputName_'.$language['uid'].'">
					<h3 class="panel-title">
						<a role="button" data-toggle="collapse" href="#msEditShippingModulesInputName_'.$language['uid'].'"><i class="fa fa-file-text-o"></i> '.$language['title'].'</a>
					</h3>
				</div>
				<div id="msEditShippingModulesInputName_'.$language['uid'].'" class="panel-collapse collapse'.(($language['uid']===0 || !empty($lngstatus[$language['uid']]['name'])) ? ' in' : '').'">
					<div class="panel-body">
						<div class="form-group">
							<label for="name" class="control-label col-md-2">'.$this->pi_getLL('admin_name').'</label>
							<div class="col-md-10">
								<input type="text" class="form-control text" name="name['.$language['uid'].']" id="name_'.$language['uid'].'" value="'.htmlspecialchars($lngproduct[$language['uid']]['name']).'" required="required">
							</div>
						</div>
						<div class="form-group">
							<label for="description" class="control-label col-md-2">'.$this->pi_getLL('admin_short_description').'</label>
							<div class="col-md-10">
								<textarea name="description['.$language['uid'].']" id="description['.$language['uid'].']" class="mceEditor" rows="4">'.htmlspecialchars($lngproduct[$language['uid']]['description']).'</textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
			';

	}
	$cost_tax_rate=0;
	$percentage_handling_cost=$row['handling_costs'];
	if (strpos($percentage_handling_cost, '%')===false) {
		$tmp_phc=explode('.', $percentage_handling_cost);
		if (isset($tmp_phc[1])>0) {
			$percentage_handling_cost=mslib_fe::taxDecimalCrop($percentage_handling_cost, 2, false).'%';
		} else {
			$percentage_handling_cost=$percentage_handling_cost.'%';
		}
	}
	$amount_handling_cost=str_replace('%', '', $row['handling_costs']);
	$data=mslib_fe::getTaxRuleSet($row['tax_id'], $amount_handling_cost);
	$cost_tax_rate=$data['total_tax_rate'];
	$cost_tax=mslib_fe::taxDecimalCrop(($amount_handling_cost*$cost_tax_rate)/100);
	$cost_excl_vat_display=mslib_fe::taxDecimalCrop($amount_handling_cost, 2, false);
	$cost_incl_vat_display=mslib_fe::taxDecimalCrop($amount_handling_cost+$cost_tax, 2, false);

	$cart_minimum_amount=$row['cart_minimum_amount'];
	$cart_minimum_amount_cost_tax=mslib_fe::taxDecimalCrop(($cart_minimum_amount*$cost_tax_rate)/100);
	$cart_minimum_amount_excl_vat_display=mslib_fe::taxDecimalCrop($cart_minimum_amount, 2, false);
	$cart_minimum_amount_incl_vat_display=mslib_fe::taxDecimalCrop($cart_minimum_amount+$cart_minimum_amount_cost_tax, 2, false);
	$cart_maximum_amount=$row['cart_maximum_amount'];
	$cart_maximum_amount_cost_tax=mslib_fe::taxDecimalCrop(($cart_maximum_amount*$cost_tax_rate)/100);
	$cart_maximum_amount_excl_vat_display=mslib_fe::taxDecimalCrop($cart_maximum_amount, 2, false);
	$cart_maximum_amount_incl_vat_display=mslib_fe::taxDecimalCrop($cart_maximum_amount+$cart_maximum_amount_cost_tax, 2, false);
	$tmpcontent.='
	<div class="form-group">
		<label class="control-label col-md-2">'.$this->pi_getLL('code').'</label>
		<div class="col-md-10">
		<p class="form-control-static">
		'.$row['code'].'
		</p>
		</div>
	</div>';
	if (count($active_shop)>1) {
		$tmpcontent.='
		<div class="form-group">
		<label for="related_shop_pid" class="control-label 23 col-md-2">'.$this->pi_getLL('relate_shipping_to_shop', 'Relate this method to').'</label>
		<div class="col-md-10">
		<div class="radio radio-success radio-inline"><input name="related_shop_pid" id="related_shop_pid" type="radio" value="0"'.(($row['page_uid']==0) ? ' checked="checked"' : '').' /><label for="related_shop_pid">'.$this->pi_getLL('relate_shipping_to_all_shop', 'All shop').'</label></div>';
		foreach ($active_shop as $pageinfo) {
            $pageTitle=$pageinfo['title'];
            if ($pageinfo['nav_title']) {
                $pageTitle=$pageinfo['nav_title'];
            }
			$tmpcontent.='<div class="radio radio-success radio-inline"><input name="related_shop_pid" id="related_shop_pid'.$pageinfo['uid'].'" type="radio" value="'.$pageinfo['uid'].'"'.(($row['page_uid']==$pageinfo['uid']) ? ' checked="checked"' : '').' /><label for="related_shop_pid'.$pageinfo['uid'].'">'.$pageTitle.'</label></div>';
		}
		$tmpcontent.='</div></div>';
	} else {
		$tmpcontent.='<input type="hidden" name="related_shop_pid" value="'.$row['page_uid'].'">';
	}
	$percentage_cost=false;
	if (strpos($row['handling_costs'], '%')!==false) {
		$percentage_cost=true;
	}
	$tmpcontent.='
	<div class="form-group">
		<label class="control-label col-md-2">'.$this->pi_getLL('handling_costs_type').'</label>
		<div class="col-md-10">
		<div class="msAttribute">
			<select name="handling_costs_type" id="handling_cost_type" class="form-control">
				<option value="amount"'.(!$percentage_cost ? ' selected="selected"' : '').'>amount</option>
				<option value="percentage"'.($percentage_cost ? ' selected="selected"' : '').'>percentage</option>
			</select>
		</div>
		</div>
	</div>
	<div class="form-group" id="handling_cost_percentage_div"'.(!$percentage_cost ? ' style="display:none"' : '').'>
		<label class="control-label col-md-2">'.$this->pi_getLL('handling_costs').'</label>
		<div class="col-md-10">
		<div class="msAttribute">
			<input name="handling_costs" id="handling_cost_percentage_input" type="text" class="form-control" value="'.$percentage_handling_cost.'"'.(!$percentage_cost ? ' disabled="disabled"' : '').' />
		</div>
		</div>
	</div>
	<div class="form-group" id="handling_cost_amount_div"'.($percentage_cost ? ' style="display:none"' : '').'>
		<label class="control-label col-md-2">'.$this->pi_getLL('handling_costs').'</label>
		<div class="col-md-10">
		<div class="msAttribute">
			<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msHandlingCostExcludingVat priceInputDisplay" value="'.$cost_excl_vat_display.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
			<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msHandlingCostIncludingVat priceInputDisplay" value="'.$cost_incl_vat_display.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
			<div class="msAttributesField hidden"><input name="handling_costs" type="hidden" id="handling_cost_amount_input" class="priceInputReal" value="'.$amount_handling_cost.'"'.($percentage_cost ? ' disabled="disabled"' : '').'/></div>
		</div>
		</div>
	</div>
	<div class="form-group" id="min_amount_to_show">
		<label class="control-label col-md-2">'.$this->pi_getLL('cart_minimum_amount').'</label>
		<div class="col-md-10">
			<div class="msAttribute">
				<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msHandlingCostExcludingVat priceInputDisplay" value="'.$cart_minimum_amount_excl_vat_display.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
				<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msHandlingCostIncludingVat priceInputDisplay" value="'.$cart_minimum_amount_incl_vat_display.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
				<div class="msAttributesField hidden"><input name="cart_minimum_amount" type="hidden" class="priceInputReal" value="'.$cart_minimum_amount.'" id="cart_minimum_amount" /></div>
			</div>
		</div>
	</div>
	<div class="form-group" id="max_amount_to_show">
		<label class="control-label col-md-2">'.$this->pi_getLL('cart_maximum_amount').'</label>
		<div class="col-md-10">
			<div class="msAttribute">
				<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msHandlingCostExcludingVat priceInputDisplay" value="'.$cart_maximum_amount_excl_vat_display.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
				<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msHandlingCostIncludingVat priceInputDisplay" value="'.$cart_maximum_amount_incl_vat_display.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
				<div class="msAttributesField hidden"><input name="cart_maximum_amount" type="hidden" class="priceInputReal" value="'.$cart_maximum_amount.'" id="cart_maximum_amount" /></div>
			</div>
		</div>
	</div>
	<div class="form-group">
	<label for="tax_id" class="control-label col-md-2">'.$this->pi_getLL('admin_vat_rate').'</label>
	<div class="col-md-10">
	<select name="tax_id" id="tax_id" class="form-control">
	<option value="0">'.$this->pi_getLL('admin_label_no_tax').'</option>';
    $str="SELECT trg.*, t.rate FROM `tx_multishop_tax_rule_groups` trg, `tx_multishop_tax_rules` tr, `tx_multishop_taxes` t where trg.rules_group_id=tr.rules_group_id and tr.tax_id=t.tax_id group by trg.rules_group_id order by trg.rules_group_id asc";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
    $tax_list_data=array();
	while (($tax_group=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
        $tax_list_data[]='product_tax_rate_list_js["'.$tax_group['rules_group_id'].'"]="'.round(number_format($tax_group['rate'], 2), 2).'"';
		if (!$this->get['shipping_method_id']) {
			$tmpcontent.='<option value="'.$tax_group['rules_group_id'].'" '.(($tax_group['default_status']) ? 'selected' : '').'>'.htmlspecialchars($tax_group['name']).'</option>';
		} else {
			$tmpcontent.='<option value="'.$tax_group['rules_group_id'].'" '.(($tax_group['rules_group_id']==$row['tax_id']) ? 'selected' : '').'>'.htmlspecialchars($tax_group['name']).'</option>';
		}
	}
    // js definition for tax
    $product_tax_rate_js=array();
    $product_tax_rate_js[]='var product_tax_rate_list_js=[];';
    if (count($tax_list_data)) {
        $product_tax_rate_js = $tax_list_data;
    }
    $GLOBALS['TSFE']->additionalHeaderData[]='
        <script type="text/javascript" data-ignore="1">
           var product_id="'.$this->get['pid'].'"
           var product_tax_rate_list_js=[]
           '.implode("\n", $product_tax_rate_js).'
        </script>
        ';
	$tmpcontent.='
	</select>
	</div>
	</div>
	'.$inner_content.'
	<div class="form-group">
			<label class="control-label col-md-2">'.$this->pi_getLL('admin_label_method_is_enabled_on_default').'</label>
			<div class="col-md-10">
			<div class="radio radio-success radio-inline"><input type="radio" name="enable_on_default" value="1" id="enable_on_default_yes"'.($row['enable_on_default']>0 ? ' checked="checked"' : '').' /><label for="enable_on_default_yes">'.$this->pi_getLL('yes').'</label></div>
			<div class="radio radio-success radio-inline"><input type="radio" name="enable_on_default" value="0" id="enable_on_default_no"'.(!$row['enable_on_default'] ? ' checked="checked"' : '').' /><label for="enable_on_default_no">'.$this->pi_getLL('no').'</label></div>
			</div>
		</div>
	<div class="form-group">
		<label for="" class="control-label col-md-2">&nbsp;</label>
		<div class="col-md-10">
		<button name="Submit" type="submit" class="btn btn-success" value=""><i class="fa fa-save"></i> '.$this->pi_getLL('save').'</button>
		</div>
	</div>
	</form>';
	$content.=$tmpcontent;
} else {
	$this->ms['show_main']=1;
}
if ($this->ms['show_main']) {
	$tmpcontent='';
	if (is_numeric($this->get['status']) and is_numeric($this->get['shipping_method_id'])) {
		$updateArray=array();
		$updateArray['status']=$this->get['status'];
		$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods', 'id=\''.$this->get['shipping_method_id'].'\'', $updateArray);
		$res=$GLOBALS['TYPO3_DB']->sql_query($query);
	} else {
		if (is_numeric($this->get['status']) and is_numeric($this->get['shipping_method_id'])) {
			$updateArray=array();
			$updateArray['status']=$this->get['status'];
			$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods', 'id=\''.$this->get['shipping_method_id'].'\'', $updateArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
		}
	}
	if (is_numeric($this->get['delete']) and is_numeric($this->get['shipping_method_id'])) {
		$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_shipping_methods', 'id=\''.$this->get['shipping_method_id'].'\'');
		$res=$GLOBALS['TYPO3_DB']->sql_query($query);
		$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_payment_shipping_mappings', 'shipping_method=\''.$this->get['shipping_method_id'].'\'');
		$res=$GLOBALS['TYPO3_DB']->sql_query($query);
		$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_products_method_mappings', 'type=\'shipping\' and method_id=\''.$this->get['shipping_method_id'].'\'');
		$res=$GLOBALS['TYPO3_DB']->sql_query($query);
	}
	if (isset($this->get['download']) && $this->get['download']=='shipping' && is_numeric($this->get['shipping_method_id'])) {
		$rowsData=array();
		$sql=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
			'tx_multishop_shipping_methods ', // FROM ...
			'id= \''.$this->get['shipping_method_id'].'\'', // WHERE...
			'', // GROUP BY...
			'', // ORDER BY...
			'' // LIMIT ...
		);
		$qry=$GLOBALS['TYPO3_DB']->sql_query($sql);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
			$data=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
			$serial_value=array();
			foreach ($data as $key_idx=>$key_val) {
				$rowsData[$this->get['shipping_method_id']]['general'][$key_idx]=$key_val;
			}
			$query_desc=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
				'tx_multishop_shipping_methods_description', // FROM ...
				'id=\''.$this->get['shipping_method_id'].'\'', // WHERE...
				'', // GROUP BY...
				'', // ORDER BY...
				'' // LIMIT ...
			);
			$res_desc=$GLOBALS['TYPO3_DB']->sql_query($query_desc);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_desc)>0) {
				while ($row_desc=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_desc)) {
					foreach ($row_desc as $col_desc_name=>$col_desc_val) {
						$rowsData[$this->get['shipping_method_id']]['description'][$row_desc['language_id']][$col_desc_name]=$col_desc_val;
					}
				}
			}
			$serial_data='';
			if (count($rowsData)>0) {
				$serial_data=serialize($rowsData);
			}
			$filename='multishop_shipping_method_'.date('YmdHis').'_'.$this->get['shipping_method_id'].'.txt';
			$filepath=$this->DOCUMENT_ROOT.'uploads/tx_multishop/'.$filename;
			file_put_contents($filepath, $serial_data);
			header("Content-disposition: attachment; filename={$filename}"); //Tell the filename to the browser
			header('Content-type: application/octet-stream'); //Stream as a binary file! So it would force browser to download
			readfile($filepath); //Read and stream the file
			@unlink($filepath);
			exit();
		}
	}
	if (isset($this->get['upload']) && $this->get['upload']=='shipping' && $_FILES) {
		if (!$_FILES['shipping_file']['error']) {
			$filename=$_FILES['shipping_file']['name'];
			$target=$this->DOCUMENT_ROOT.'/uploads/tx_multishop'.$filename;
			if (move_uploaded_file($_FILES['shipping_file']['tmp_name'], $target)) {
				$shipping_content=file_get_contents($target);
				$unserial_shipping_data=unserialize($shipping_content);
				if (is_array($unserial_shipping_data) && count($unserial_shipping_data)) {
					foreach ($unserial_shipping_data as $shipping_data) {
						$insertArray=array();
						if (is_array($shipping_data['general']) && count($shipping_data['general'])) {
							foreach ($shipping_data['general'] as $shipping_col=>$shipping_val) {
								if ($shipping_col!='id') {
									switch ($shipping_col) {
										case 'code':
											if (isset($this->post['new_code']) && !empty($this->post['new_code'])) {
												$insertArray['code']=$this->post['new_code'];
											} else {
												$insertArray['code']=$shipping_val;
											}
											break;
										case 'page_uid':
											$insertArray['page_uid']=$this->shop_pid;
											break;
										default:
											$insertArray[$shipping_col]=$shipping_val;
											break;
									}
								}
							}
						}
						$query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_shipping_methods', $insertArray);
						$res=$GLOBALS['TYPO3_DB']->sql_query($query);
						$shipping_id=$GLOBALS['TYPO3_DB']->sql_insert_id();
						if (is_array($shipping_data['description']) && count($shipping_data['description'])) {
							foreach ($shipping_data['description'] as $language_id=>$shipping_desc_data) {
								if (is_array($shipping_desc_data) && count($shipping_desc_data)) {
									$insertArrayDesc=array();
									foreach ($shipping_desc_data as $shipping_desc_col_name=>$shipping_desc_val) {
										switch ($shipping_desc_col_name) {
											case 'id':
												$insertArrayDesc['id']=$shipping_id;
												break;
											default:
												$insertArrayDesc[$shipping_desc_col_name]=$shipping_desc_val;
												break;
										}
									}
									$query_desc=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_shipping_methods_description', $insertArrayDesc);
									$GLOBALS['TYPO3_DB']->sql_query($query_desc);
								}
							}
						}
					}
				}
				@unlink($target);
			}
		}
		header('Location: '.$this->FULL_HTTP_URL.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']));
		exit();
	}
	// shipping method admin system
	$colspan=4;
	$str="SELECT *,d.name from tx_multishop_shipping_methods p, tx_multishop_shipping_methods_description d where d.language_id='".$this->sys_language_uid."' and (p.page_uid = '".$this->shop_pid."' or p.page_uid = '0') and p.id=d.id order by p.sort_order";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$tr_type='even';
	if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)>0) {
		$tmpcontent.='<table class="table table-striped table-bordered msadmin_border" id="admin_modules_listing">';
		$tmpcontent.='<thead><tr>';
		if (count($active_shop)>1) {
			$tmpcontent.='<th>'.$this->pi_getLL('shop', 'Shop').'</th>';
		}
		$tmpcontent.='<th>'.$this->pi_getLL('shipping_method').'</th>';
		$tmpcontent.='<th width="60">'.$this->pi_getLL('template').'</th>';
		$tmpcontent.='<th width="120">'.$this->pi_getLL('date_added').'</th>';
		$tmpcontent.='<th width="60">'.$this->pi_getLL('status').'</th>';
		$tmpcontent.='<th width="30">'.$this->pi_getLL('action').'</th>';
		$tmpcontent.='<th width="30">'.ucfirst($this->pi_getLL('download')).'</th>';
		$tmpcontent.='</tr></thead>';
		$tmpcontent.='<tbody class="sortable_content">';
		while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
			//		$tmpcontent.='<h3>'.$cat['name'].'</h3>';
			if (!$tr_type or $tr_type=='even') {
				$tr_type='odd';
			} else {
				$tr_type='even';
			}
			$tmpcontent.='<tr class="'.$tr_type.'" id="multishop_shipping_method_'.$row['id'].'">';
			if (count($active_shop)>1) {
				if ($row['page_uid']>0) {
					$tmpcontent.='<td><strong>'.mslib_fe::getShopNameByPageUid($row['page_uid']).'</strong></td>';
				} else {
					$tmpcontent.='<td><strong>All</strong></td>';
				}
			}
			$tmpcontent.='<td class="cellName"><strong><a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&shipping_method_id='.$row['id'].'&edit=1').'">'.$row['name'].'</a>
			</strong></td>
			<td>'.$row['provider'].'</td>
			<td class="cellDate">'.date("Y-m-d", $row['date']).'</td>
			<td class="cellStatus">';
			if (!$row['status']) {
				$tmpcontent.='<span class="admin_status_red" alt="Disable"></span>';
				$tmpcontent.='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&shipping_method_id='.$row['id'].'&status=1').'"><span class="admin_status_green disabled" alt="Enabled"></span></a>';
			} else {
				$tmpcontent.='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&shipping_method_id='.$row['id'].'&status=0').'"><span class="admin_status_red disabled" alt="Disabled"></span></a>';
				$tmpcontent.='<span class="admin_status_green" alt="Enable"></span>';
			}
			$tmpcontent.='</td>
			<td class="cellAction">
			<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&shipping_method_id='.$row['id'].'&delete=1').'" onclick="return confirm(\'Are you sure?\')" class="btn btn-danger btn-sm admin_menu_remove" alt="Remove"><i class="fa fa-trash-o"></i></a>
			</td>
			<td align="center">
				<a href="'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]='.$this->ms['page'].'&download=shipping&shipping_method_id='.$row['id']).'" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> '.ucfirst($this->pi_getLL('download_record')).'</a>
			</td>
			</tr>';
		}
		$tmpcontent.='</tbody></table>';
	} else {
		$tmpcontent.=$this->pi_getLL('currently_there_are_no_shipping_methods_defined').'.';
	}
	$tmpcontent.='<div class="clearfix"><div class="pull-right">
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#msAdminModalPopuAddShippingMethod"><i class="fa fa-plus"></i> '.$this->pi_getLL('add_shipping_method').'</button>
	</div></div>';
	$tmpcontent.='<fieldset id="scheduled_import_jobs_form"><h3 class="page-header">'.$this->pi_getLL('upload_record').'</h3>
			<form action="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&upload=shipping').'" method="post" enctype="multipart/form-data" name="upload_task" id="upload_task" class="form-horizontal blockSubmitForm">
				<div class="form-group">
					<label for="new_code" class="control-label col-md-2">'.$this->pi_getLL('code').'</label>
					<div class="col-md-10">
					<input class="form-control" name="new_code" type="text" value="" />
					</div>
				</div>
				<div class="form-group">
					<label for="upload_shipping_file" class="control-label col-md-2">'.$this->pi_getLL('file').'</label>
					<div class="col-md-10">
					<div class="input-group">
					<input type="file" name="shipping_file" class="form-control">
					<span class="input-group-btn">
						<input type="submit" name="upload_shipping_file" class="submit btn btn-success" id="upload_shipping_file" value="upload">
					</span>
					</div>
					</div>
				</div>
			</form>
		</fieldset>';

	$modalContent='<div class="row">';
	$innercount=0;
	$count=0;
	foreach ($shipping_methods as $code=>$item) {
		$innercount++;
		$count++;

		$panelTitle='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&sub=add_shipping_method&shipping_method_code='.$code).'">'.htmlspecialchars($item['name']).'</a>';
		$panelBody='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&sub=add_shipping_method&shipping_method_code='.$code).'">';
		if ($item['image'] && file_exists($this->DOCUMENT_ROOT_MS.'templates/images/shipping/'.$item['image'])) {
			$panelBody.='<span class="multishop_psp_image_wrapper"><span class="multishop_psp_image"><img src="'.$this->FULL_HTTP_URL_MS.'templates/images/shipping/'.$item['image'].'" alt="Add '.htmlspecialchars($item['name']).'"></span></span>';
		} else {
			$panelBody.=$item['name'];
		}
		$panelBody.='</a>';
		$panelFooter='<a href="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page'].'&sub=add_shipping_method&shipping_method_code='.$code).'" class="btn btn-block btn-success btn-sm"><i class="fa fa-plus"></i> '.$this->pi_getLL('add_shipping_method').'</a>';

		$modalContent.='
		<div class="col-md-3">
			<div class="panel panel-default">
				<div class="panel-body">
					'.$panelBody.'
				</div>
				<div class="panel-footer">
					'.$panelFooter.'
				</div>
			</div>
		</div>
		';

	}
	$modalContent.='</div>';
	// modal
	$tmpcontent.='
	<div class="modal" id="msAdminModalPopuAddShippingMethod" tabindex="-1" role="dialog" aria-labelledby="msAdminModalPopuAddShippingMethod" aria-hidden="true">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h3 class="modal-title" id="shippingCostModalTitle">'.$this->pi_getLL('add_payment_method').'</h3>
			  </div>
			  <div class="modal-body">'.$modalContent.'</div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-success" data-dismiss="modal">OK</button>
			  </div>
			</div>
		  </div>
		</div>
	';
	// modal eol

	//tabs array
	$tabs=array();
	// shipping methods tab
	$tabs[]=array(
		'label'=>ucfirst(mslib_befe::strtolower($this->pi_getLL('admin_shipping_methods'))),
		'id'=>'admin_shipping_methods',
		'content'=>mslib_fe::returnBoxedHTML(ucfirst(mslib_befe::strtolower($this->pi_getLL('admin_shipping_methods'))), $tmpcontent)
	);
	// shipping methods to zones mapping
	$tmpcontent='';
	// load all shipping methods
	$shipping_methods=mslib_fe::loadShippingMethods();

	$zones=mslib_fe::loadAllCountriesZones();
	if (count($zones['zone_id'])) {
		$colspan=4;
		$tr_type='even';
		if (count($shipping_methods)) {
			$tmpcontent.='<form method="post" action="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']).'">';
			$tmpcontent.='<table class="table table-striped table-bordered msadmin_border" id="admin_modules_listing">';
			$tmpcontent.='<thead><tr>';
			// zone header
			$zone_cols=array();
			foreach ($zones['zone_id'] as $zone_idx=>$zone_id) {
				$tmpcontent.='<th>'.$zones['zone_name'][$zone_idx].' ('.implode('<br/> ', $zones['countries'][$zone_id]).')</th>';
				$zone_cols[]=$zone_id;
			}
			$tmpcontent.='</tr></thead>';
			$tmpcontent.='<tr>';
			// shipping method rows
			foreach ($zone_cols as $zone_id) {
				// load all shipping methods
				//$shipping_method_zones=mslib_fe::loadZoneShippingMethods($zone_id);
				//$shipping_methods=array_merge($shipping_method_zones, $shipping_methods);
				$tmpcontent.='<td>';
				$tmpcontent.='<table class="sortable_column">';
				$shipping_methods_sorted=array();
				$unsorted_number=99;
				$sort_number=50;
				foreach ($shipping_methods as $shipping_method) {
					$sql_check="select id, sort_order from tx_multishop_shipping_methods_to_zones where zone_id = ".$zone_id." and shipping_method_id = ".$shipping_method['id'];
					$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_check)) {
						$rs_check=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_check);
						$shipping_method['checked']=true;
						if ($rs_check['sort_order']>0) {
							$shipping_methods_sorted[$rs_check['sort_order']]=$shipping_method;
						} else {
							$shipping_methods_sorted[$sort_number]=$shipping_method;
						}
						$sort_number++;
					} else {
						$shipping_method['checked']=false;
						$shipping_methods_sorted[$unsorted_number]=$shipping_method;
						$unsorted_number++;
					}
				}
				ksort($shipping_methods_sorted);
				foreach ($shipping_methods_sorted as $shipping_method) {
					$vars=unserialize($shipping_method['vars']);
					if ($shipping_method['checked']) {
						$tmpcontent.='<tr id="shipping_zone_['.$zone_id.']_'.$shipping_method['id'].'" class="row_sortable">';
						$tmpcontent.='<td><div class="checkbox checkbox-success"><input type="checkbox" checked name="shipping_zone['.$zone_id.']['.$shipping_method['id'].']" id="shipping_zone_'.$zone_id.'_'.$shipping_method['id'].'" onclick="this.form.submit()"><label for="shipping_zone_'.$zone_id.'_'.$shipping_method['id'].'">'.$vars['name'][0].'</label></div></td>';
					} else {
						$tmpcontent.='<tr class="row_unsortable">';
						$tmpcontent.='<td><div class="checkbox checkbox-success"><input type="checkbox" name="shipping_zone['.$zone_id.']['.$shipping_method['id'].']" id="shipping_zone_'.$zone_id.'_'.$shipping_method['id'].'" onclick="this.form.submit()"><label for="shipping_zone_'.$zone_id.'_'.$shipping_method['id'].'">'.$vars['name'][0].'</label></div></td>';
					}
					$tmpcontent.='</tr>';
				}
				$tmpcontent.='</table>';
				$tmpcontent.='</td>';
			}
			$tmpcontent.='</tr>';
			$tmpcontent.='</table>';
			$tmpcontent.='<input name="param" type="hidden" value="update_mapping" />';
			$tmpcontent.='</form>';
		} else {
			$tmpcontent.=$this->pi_getLL('admin_label_currently_no_shipping_method_defined');
		}
	} else {
		$tmpcontent.=$this->pi_getLL('admin_label_currently_no_shipping_method_defined');
	}
	$tabs[]=array(
		'label'=>ucfirst(mslib_befe::strtolower($this->pi_getLL('shipping_to_zone_mapping', 'Shipping to Zone Mappings'))),
		'id'=>'admin_shipping_method_zone_mappings',
		'content'=>mslib_fe::returnBoxedHTML($this->pi_getLL('shipping_to_zone_mapping', 'Shipping to Zone Mappings'), $tmpcontent)
	);
	// shipping to payment mappings
	$tmpcontent='';
	$payment_methods=mslib_fe::loadPaymentMethods();
	if (count($payment_methods)) {
		//$tmpcontent.='<div class="main-heading"><h2>'.$this->pi_getLL('shipping_to_payment_mapping').'</h2></div>';
		$colspan=4;
		$tr_type='even';
		if (count($shipping_methods)) {
			$tmpcontent.='<form method="post" action="'.mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]='.$this->ms['page']).'">';
			$tmpcontent.='<table class="table table-striped table-bordered msadmin_border" id="admin_modules_listing">';
			$tmpcontent.='<thead><tr><th>&nbsp;</th>';
			foreach ($payment_methods as $payment_method) {
				$tmpcontent.='<th>'.$payment_method['name'].'</th>';
			}
			$tmpcontent.='</tr></thead>';
			foreach ($shipping_methods as $row) {
				//		$content.='<h3>'.$cat['name'].'</h3>';
				if (!$tr_type or $tr_type=='even') {
					$tr_type='odd';
				} else {
					$tr_type='even';
				}
				$tmpcontent.='<tr class="'.$tr_type.'">
			<td class="cellName"><strong>'.$row['name'].'</strong></td>';
				foreach ($payment_methods as $payment_method) {
					$tmpcontent.='<td align="center">';
					$tmpcontent.='<div class="checkbox checkbox-success"><input name="checkbox['.$row['id'].']['.$payment_method['id'].']" type="checkbox" value="1" onclick="this.form.submit();" ';
					$str2="SELECT * from tx_multishop_payment_shipping_mappings where payment_method='".$payment_method['id']."' and shipping_method='".$row['id']."'";
					$qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
					if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry2)>0) {
						$tmpcontent.='checked';
					}
					$tmpcontent.=' /><label></label></div></td>';
				}
				$tmpcontent.='</tr>';
			}
			$tmpcontent.='</table>';
			$tmpcontent.='<input name="param" type="hidden" value="update_mapping" /></form>';
		} else {
			$tmpcontent.=$this->pi_getLL('admin_label_currently_no_shipping_method_defined');
		}
	} else {
		$tmpcontent.=$this->pi_getLL('admin_label_currently_no_shipping_method_defined');
	}
	$tabs[]=array(
		'label'=>ucfirst(mslib_befe::strtolower($this->pi_getLL('shipping_to_payment_mapping'))),
		'id'=>'admin_shipping_payment_mappings',
		'content'=>mslib_fe::returnBoxedHTML(ucfirst(mslib_befe::strtolower($this->pi_getLL('shipping_to_payment_mapping'))), $tmpcontent)
	);
	// render the tabs
	$tab_button='';
	$tab_content='';
	foreach ($tabs as $tab) {
		$tab_button.='<li role="presentation"><a href="#'.$tab['id'].'" aria-controls="profile" role="tab" data-toggle="tab">'.$tab['label'].'</a></li>';
		$tab_content.='<div id="'.$tab['id'].'" class="tab-pane">';
		$tab_content.=$tab['content'];
		$tab_content.='</div>';
	}
	$tabs_element='<div class="panel panel-default"><div class="panel-body"><div id="tab-container">';
	$tabs_element.='<ul class="nav nav-tabs" id="admin_orders" role="tablist">';
	$tabs_element.=$tab_button;
	$tabs_element.='</ul>';
	$tabs_element.='<div class="tab-content">';
	$tabs_element.=$tab_content;
	$tabs_element.='</div>';
	$tabs_element.='</div>'; // parent #tab_container
	// flush to render variable
	$content=$tabs_element;
	// shipping method admin system eof
}
$content.='<hr><div class="clearfix"><a class="btn btn-success msAdminBackToCatalog" href="'.mslib_fe::typolink().'"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-arrow-left fa-stack-1x"></i></span> '.$this->pi_getLL('admin_close_and_go_back_to_catalog').'</a></div></div></div>';
$content=''.mslib_fe::shadowBox($content).'';
// javascript for shipping methods
$GLOBALS['TSFE']->additionalHeaderData['admin_shipping_methods']='
<script type="text/javascript">
jQuery(document).ready(function($) {
	// sortables
	var result2	= jQuery("#admin_modules_listing tbody.sortable_content").sortable({
		cursor: "move",
		//axis: "y",
		update: function(e, ui) {
			href = "'.mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=method_sortables').'";
			jQuery(this).sortable("refresh");
			sorted = jQuery(this).sortable("serialize", "id");
			jQuery.ajax({
				type:   "POST",
				url:    href,
				data:   sorted,
				success: function(msg) {
					//do something with the sorted data
				}
			});
		}
	});
	jQuery(".sortable_column").sortable({
		cursor: "move",
		//axis: "y",
		items: "tr.row_sortable",
		update: function(e, ui) {
			href = "'.mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=zone_method_sortables').'";
			jQuery(this).sortable("refresh");
			sorted = jQuery(this).sortable("serialize", "id");
			jQuery.ajax({
				type:   "POST",
				url:    href,
				data:   sorted,
				success: function(msg) {
					//do something with the sorted data
				}
			});
		}
	});
	// sortables eof
	// tabs js
var url = document.location.toString();
if (url.match("#")) {
    $(".nav-tabs a[href=#"+url.split("#")[1]+"]").tab("show") ;
} else {
	$(".nav-tabs a:first").tab("show");
}

// Change hash for page-reload
$(".nav-tabs a").on("shown.bs.tab", function (e) {
	window.location.hash = e.target.hash;
	$("body,html,document").scrollTop(0);
})

});
</script>';
?>