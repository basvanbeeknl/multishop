<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if ($this->ADMIN_USER) {
	$shipping_id=$_REQUEST['shippingid'];
	$zones=$_REQUEST['zone'];
	$based=$_REQUEST['based'];
	$basedold=$_REQUEST['basedold'];
	$content="";
	$str_tid="SELECT id, tax_id from tx_multishop_shipping_methods where id='".$shipping_id."'";
	$qry_tid=$GLOBALS['TYPO3_DB']->sql_query($str_tid);
	$row_tid=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_tid);
	$str="SELECT * from tx_multishop_zones order by name";
	$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
	$zones=array();
	while (($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry))!=false) {
		$zones[]=$row;
	}
	if ($based=='flat' && $basedold=='flat') {
		foreach ($zones as $zone) {
			$content.='<div class="panel panel-default">';
			$content.='<div class="panel-heading"><h3>Zone: '.$zone['name'];
			$str2="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.zone_id='".$zone['id']."' and c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
			$qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
			$content.=' (';
			$tmpcontent='';
			while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2))!=false) {
				$tmpcontent.=$row2['cn_iso_2'].',';
			}
			$tmpcontent=substr($tmpcontent, 0, strlen($tmpcontent)-1);
			$content.=$tmpcontent.')';
			$content.='</h3></div>';
			$str3="SELECT * from tx_multishop_shipping_methods_costs where shipping_method_id='".$shipping_id."' and zone_id='".$zone['id']."'";
			$qry3=$GLOBALS['TYPO3_DB']->sql_query($str3);
			$row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry3);
			$zone_pid=$shipping_id.":".$zone['id'];
			$data=mslib_fe::getTaxRuleSet($row_tid['tax_id'], 0);
			$sc_tax_rate=$data['total_tax_rate'];
			$sc_tax=mslib_fe::taxDecimalCrop(($row3['price']*$sc_tax_rate)/100);
			$sc_price_display=mslib_fe::taxDecimalCrop($row3['price'], 2, false);
			$sc_price_display_incl=mslib_fe::taxDecimalCrop($row3['price']+$sc_tax, 2, false);
			$freeshippingcosts_above=false;
			$free_shippingcosts=0;
			$fsc_price_display=0;
			$fsc_price_display_incl=0;
			if (!empty($row3['override_shippingcosts'])) {
				$freeshippingcosts_above=true;
				$free_shippingcosts_tmp=explode(',', $row3['override_shippingcosts']);
				list($free_shippingcosts,)=explode(':', $free_shippingcosts_tmp[1]);
				$fsc_tax=mslib_fe::taxDecimalCrop(($free_shippingcosts*$sc_tax_rate)/100);
				$fsc_price_display=mslib_fe::taxDecimalCrop($free_shippingcosts, 2, false);
				$fsc_price_display_incl=mslib_fe::taxDecimalCrop($free_shippingcosts+$fsc_tax, 2, false);
			}
			$content.='
				<div class="panel-body">
				<div class="form-group">
					<label id="'.$zone_pid.'_NivLevel'.$i.'" class="control-label col-md-4">Level 1 :</label>
					<div class="col-md-8">
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay" value="'.htmlspecialchars($sc_price_display).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">Excl. VAT</span></div></div>
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay" value="'.htmlspecialchars($sc_price_display_incl).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">Incl. VAT</span></div></div>
						<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="'.$zone_pid.'" class="priceInputReal" value="'.$row3['price'].'"></div>
					</div>
				</div>
				<hr>
				<div class="form-group">
					<label id="'.$zone_pid.'_NivLevel'.$i.'" class="control-label col-md-4"><div class="checkbox"><input type="checkbox" id="freeshippingcostsabove['.$zone_pid.']" name="freeshippingcostsabove['.$zone_pid.']" value="1"'.($freeshippingcosts_above ? ' checked="checked"' : '').' /><label for="freeshippingcostsabove['.$zone_pid.']">'.$this->pi_getLL('free_shippingcosts_for_order_amount_above').'</label></div></label>
					<div class="col-md-8">
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay" value="'.htmlspecialchars($fsc_price_display).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay" value="'.htmlspecialchars($fsc_price_display_incl).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
						<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="freeshippingcostsabove_value['.$zone_pid.']" class="priceInputReal" value="'.$free_shippingcosts.'"></div>
					</div>
				</div>';
			$content.='</div></div>';
		}
	} else {
		if ($based=="weight" && $basedold=="weight") {
			foreach ($zones as $zone) { // start for weight based
				$content.='<div class="panel panel-default">';
				$content.='<div class="panel-heading"><h3>Zone: '.$zone['name'];
				$str2="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.zone_id='".$zone['id']."' and c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
				$qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
				$content.=' (';
				$tmpcontent='';
				while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2))!=false) {
					$tmpcontent.=$row2['cn_iso_2'].',';
				}
				$tmpcontent=substr($tmpcontent, 0, strlen($tmpcontent)-1);
				$content.=$tmpcontent.')';
				$content.='</h3></div>';
				$str3="SELECT * from tx_multishop_shipping_methods_costs where shipping_method_id='".$shipping_id."' and zone_id='".$zone['id']."'";
				$qry3=$GLOBALS['TYPO3_DB']->sql_query($str3);
				$row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry3);
				$content.='<div class="panel-body">';
				$zone_pid=$row['id'].$zone['id'];
				$shipping_cost=array();
				if (isset($row3['price']) && !empty($row3['price'])) {
					$shipping_cost=explode(",", $row3['price']);
				}
				$count_sc=count($shipping_cost);
				if ($count_sc>0) {
					for ($i=1; $i<=$count_sc; $i++) {
						$nextVal=$i+1;
						$numKey=$i;
						$end_weight=101;
						$zone_price=explode(":", $shipping_cost[$numKey-1]);
						$sc_tax_rate=0;
						$data=mslib_fe::getTaxRuleSet($row['tax_id'], 0);
						$sc_tax_rate=$data['total_tax_rate'];
						$sc_tax=mslib_fe::taxDecimalCrop(($zone_price[1]*$sc_tax_rate)/100);
						$sc_price_display=mslib_fe::taxDecimalCrop($zone_price[1], 2, false);
						$sc_price_display_incl=mslib_fe::taxDecimalCrop($zone_price[1]+$sc_tax, 2, false);
						// custom hook that can be controlled by third-party plugin
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_shipping_cost.php']['weightConversion'])) {
							$params=array(
								'zone_price'=>&$zone_price,
								'end_weight'=>&$end_weight
							);
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_shipping_cost.php']['weightConversion'] as $funcRef) {
								\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
							}
						}
						$weight_next=$i>1 ? $weight_old : '0';
						$zone_price_display=$zone_price[0]==$end_weight ? 'End' : $zone_price[0];
						$content.='
					<div id="'.$zone_pid.'_Row_'.$i.'" class="form-group">
						<label id="'.$zone_pid.'_Label_'.$i.'" class="form-inline control-label col-md-4 firstLabel">Level '.$i.':
						<span id="'.$zone_pid.'_BeginWeightLevel'.$i.'">'.$weight_next.' '.$this->pi_getLL('admin_shipping_kg').'</span>
						<span id="'.$zone_pid.'_TotLevel'.$i.'"> '.$this->pi_getLL('up_to_and_including').' </span>
						<select class="form-control" name="'.$shipping_id.":".$zone['id'].'[]" id="'.$zone_pid.'_EndWeightLevel'.$i.'" onchange="UpdateWeightPrice('.$nextVal.', '.$zone_pid.', this.value); ">
							'.mslib_befe::createSelectboxWeightsList($zone_price[0], $zone_price[0]).'
						</select>
						<div class="col-md-8">
							<div id="'.$zone_pid.'_PriceLevel'.$i.'">
								<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay '.$zone_pid.'_priceInput'.$i.'" value="'.htmlspecialchars($sc_price_display).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">Excl. VAT</span></div></div>
								<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay '.$zone_pid.'_priceInput'.$i.'" value="'.htmlspecialchars($sc_price_display_incl).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">Incl. VAT</span></div></div>
								<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="'.$zone_pid.'_Price[]" class="priceInputReal" id="'.$zone_pid.'_Price'.$i.'" value="'.$zone_price[1].'" class="'.$zone_pid.'_priceInput'.$i.'"></div>
							</div>
						</div>
					</div>';
						$weight_old=$zone_price[0];
					}
				}
				if ($count_sc<10) {
					if ($count_sc>0) {
						$sc_row=$count_sc;
					} else {
						$sc_row=1;
					}
					$row_counter=$sc_row;
					for ($i=$sc_row; $i<=10; $i++) {
						$nextVal=$i+1;
						if ($row_counter==1) {
							$content.='<div id="'.$zone_pid.'_Row_'.$i.'" class="form-group">';
						} else {
							$content.='<div id="'.$zone_pid.'_Row_'.$i.'" class="form-group" style="display:none">';
						}
						$content.='
							<label id="'.$zone_pid.'_Label_'.$i.'" class="form-inline control-label col-md-4 firstLabel">Level '.$i.':
							<span id="'.$zone_pid.'_BeginWeightLevel'.$i.'" >0 '.$this->pi_getLL('admin_shipping_kg').'</span>
							<span id="'.$zone_pid.'_TotLevel'.$i.'" > '.$this->pi_getLL('up_to_and_including').' </span>
							';
						$disabled='';
						if ($row_counter==1) {
							$content.='<select class="form-control" name="'.$shipping_id.":".$zone['id'].'[]" id="'.$zone_pid.'_EndWeightLevel'.$i.'" onchange="UpdateWeightPrice('.$nextVal.', '.$zone_pid.', this.value); ">
									'.mslib_befe::createSelectboxWeightsList().'
									</select>';
						} else {
							$disabled=' disabled="disabled"';
							$content.='<select class="form-control" name="'.$shipping_id.":".$zone['id'].'[]" id="'.$zone_pid.'_EndWeightLevel'.$i.'" onchange="UpdateWeightPrice('.$nextVal.', '.$zone_pid.', this.value); "></select>';
						}
						$content.='</label>
							<div class="col-md-8 asdf123">
								<div id="'.$zone_pid.'_PriceLevel'.$i.'">
									<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay '.$zone_pid.'_priceInput'.$i.'" value="" rel="'.$shipping_id.'"'.$disabled.' autocomplete="off"><span class="input-group-addon">Excl. VAT</span></div></div>
									<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay '.$zone_pid.'_priceInput'.$i.'" value="" rel="'.$shipping_id.'"'.$disabled.' autocomplete="off"><span class="input-group-addon">Incl. VAT</span></div></div>
									<div class="msAttributesField hidden"><input type="hidden" style="text-align:right;display:none;" size="3" name="'.$zone_pid.'_Price[]" class="priceInputReal" id="'.$zone_pid.'_Price'.$i.'" value="" class="'.$zone_pid.'_priceInput'.$i.'"'.$disabled.' /></div>
								</div>
							</div>
						</div>';
						$row_counter++;
					}
				}
				$data=mslib_fe::getTaxRuleSet($row_tid['tax_id'], 0);
				$sc_tax_rate=$data['total_tax_rate'];
				$freeshippingcosts_above=false;
				$free_shippingcosts=0;
				$fsc_price_display=0;
				$fsc_price_display_incl=0;
				if (!empty($row3['override_shippingcosts'])) {
					$freeshippingcosts_above=true;
					list($free_shippingcosts,)=explode(':', $row3['override_shippingcosts']);
					$fsc_tax=mslib_fe::taxDecimalCrop(($free_shippingcosts*$sc_tax_rate)/100);
					$fsc_price_display=mslib_fe::taxDecimalCrop($free_shippingcosts, 2, false);
					$fsc_price_display_incl=mslib_fe::taxDecimalCrop($free_shippingcosts+$fsc_tax, 2, false);
				}
				$content.='<hr>
					<div class="form-group">
						<label id="'.$zone_pid.'_NivLevel'.$i.'" class="control-label col-md-4 secondLabel"><div class="checkbox checkbox-success"><input type="checkbox" name="freeshippingcostsabove['.$zone_pid.']" id="freeshippingcostsabove['.$zone_pid.']" value="1"'.($freeshippingcosts_above ? ' checked="checked"' : '').' /><label for="freeshippingcostsabove['.$zone_pid.']">'.$this->pi_getLL('free_shippingcosts_for_order_amount_above').'</label></div></label>
						<div class="col-md-8">
								<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay" value="'.htmlspecialchars($fsc_price_display).'" rel="'.$row['tax_id'].'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
								<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay" value="'.htmlspecialchars($fsc_price_display_incl).'" rel="'.$row['tax_id'].'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
								<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="freeshippingcostsabove_value['.$zone_pid.']" class="priceInputReal" value="'.$free_shippingcosts.'"></div>
						</div>
					</div>
				';
				$content.='</div>';
				$content.='<script type="text/javascript"></script>';
				$content.='</div>';
				//break;
			} //end for weight base
		}
	} //end else
	// new for weight
	if ($based=="weight" && ($basedold=="flat" || $basedold=="" || $basedold!="weight")) {
		foreach ($zones as $zone) { // start for weight based
			$content.='<div class="panel panel-default">';
			$content.='<div class="panel-heading"><h3>Zone: '.$zone['name'];
			$str2="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.zone_id='".$zone['id']."' and c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
			$qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
			$content.=' (';
			$tmpcontent='';
			while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2))!=false) {
				$tmpcontent.=$row2['cn_iso_2'].',';
			}
			$tmpcontent=substr($tmpcontent, 0, strlen($tmpcontent)-1);
			$content.=$tmpcontent.')';
			$content.='</h3></div>';
			$content.='<div class="panel-body">';
			$zone_pid=$shipping_id.$zone['id'];
			$row_counter=1;
			for ($i=1; $i<=10; $i++) {
				$nextVal=$i+1;
				if ($row_counter==1) {
					$content.='<div id="'.$zone_pid.'_Row_'.$i.'" class="form-group">';
				} else {
					$content.='<div id="'.$zone_pid.'_Row_'.$i.'" class="form-group" style="display:none">';
				}
				$content.='
						<label id="'.$zone_pid.'_Label_'.$i.'" class="form-inline control-label col-md-4 firstLabel">Level '.$i.':
						<span id="'.$zone_pid.'_BeginWeightLevel'.$i.'" >0 '.$this->pi_getLL('admin_shipping_kg').'</span>
						<span id="'.$zone_pid.'_TotLevel'.$i.'" > '.$this->pi_getLL('up_to_and_including').' </span>
						';
				$disabled='';
				if ($row_counter==1) {
					$content.='<select class="form-control" name="'.$shipping_id.":".$zone['id'].'[]" id="'.$zone_pid.'_EndWeightLevel'.$i.'" onchange="UpdateWeightPrice('.$nextVal.', '.$zone_pid.', this.value); ">
								'.mslib_befe::createSelectboxWeightsList().'
								</select>';
				} else {
					$disabled=' disabled="disabled"';
					$content.='<select class="form-control" name="'.$shipping_id.":".$zone['id'].'[]" id="'.$zone_pid.'_EndWeightLevel'.$i.'" onchange="UpdateWeightPrice('.$nextVal.', '.$zone_pid.', this.value); "></select>';
				}
				$content.='</label>
							<div class="col-md-8">
								<div id="'.$zone_pid.'_PriceLevel'.$i.'">
									<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay '.$zone_pid.'_priceInput'.$i.'" value="" rel="'.$shipping_id.'"'.$disabled.' autocomplete="off"><span class="input-group-addon">Excl. VAT</span></div></div>
									<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay '.$zone_pid.'_priceInput'.$i.'" value="" rel="'.$shipping_id.'"'.$disabled.' autocomplete="off"><span class="input-group-addon">Incl. VAT</span></div></div>
									<div class="msAttributesField hidden"><input type="hidden" style="text-align:right; display=none;" size="3" name="'.$zone_pid.'_Price[]" class="priceInputReal" id="'.$zone_pid.'_Price'.$i.'" value="" class="'.$zone_pid.'_priceInput'.$i.'"'.$disabled.' /></di>
								</div>
							</div>
						</div>
					</div>';
				$row_counter++;
			}
			$data=mslib_fe::getTaxRuleSet($row_tid['tax_id'], 0);
			$sc_tax_rate=$data['total_tax_rate'];
			$freeshippingcosts_above=false;
			$free_shippingcosts=0;
			$fsc_price_display=0;
			$fsc_price_display_incl=0;
			if (!empty($row3['override_shippingcosts'])) {
				$freeshippingcosts_above=true;
				list($free_shippingcosts,)=explode(':', $row3['override_shippingcosts']);
				$fsc_tax=mslib_fe::taxDecimalCrop(($free_shippingcosts*$sc_tax_rate)/100);
				$fsc_price_display=mslib_fe::taxDecimalCrop($free_shippingcosts, 2, false);
				$fsc_price_display_incl=mslib_fe::taxDecimalCrop($free_shippingcosts+$fsc_tax, 2, false);
			}
			$content.='<hr>
					<div class="form-group">
						<label id="'.$zone_pid.'_NivLevel'.$i.'" class="control-label col-md-4 secondLabel"><div class="checkbox checkbox-success"><input type="checkbox" id="freeshippingcostsabove['.$zone_pid.']" name="freeshippingcostsabove['.$zone_pid.']" value="1"'.($freeshippingcosts_above ? ' checked="checked"' : '').' /><label for="freeshippingcostsabove['.$zone_pid.']">'.$this->pi_getLL('free_shippingcosts_for_order_amount_above').'</label></div></label>
						<div class="col-md-8">
							<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay" value="'.htmlspecialchars($fsc_price_display).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
							<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" name="display_name" id="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay" value="'.htmlspecialchars($fsc_price_display_incl).'" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
							<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="freeshippingcostsabove_value['.$zone_pid.']" class="priceInputReal" value="'.$free_shippingcosts.'"></div>
						</div>
					</div>
				';
			$content.='</div>';
			$content.='<script type="text/javascript"></script>';
			$content.='</div>';
			//break;
		} //end for weight base
	} else {
		if ($based=='flat' && ($basedold=='weight' || $basedold=='' || $basedold!="flat")) { // new for flat
			foreach ($zones as $zone) { // start for weight based
				$content.='<div class="panel panel-default">';
				$content.='<div class="panel-heading"><h3>Zone: '.$zone['name'];
				$str2="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.zone_id='".$zone['id']."' and c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
				$qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
				$content.=' (';
				$tmpcontent='';
				while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2))!=false) {
					$tmpcontent.=$row2['cn_iso_2'].',';
				}
				$tmpcontent=substr($tmpcontent, 0, strlen($tmpcontent)-1);
				$content.=$tmpcontent.')';
				$content.='</h3></div><div class="panel-body">';
				$str3="SELECT * from tx_multishop_shipping_methods_costs where shipping_method_id='".$row['id']."' and zone_id='".$zone['id']."'";
				$qry3=$GLOBALS['TYPO3_DB']->sql_query($str3);
				$row3=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry3);
				$zone_pid=$shipping_id.":".$zone['id'];
				$content.='
				<div class="form-group">
					<label id="'.$zone_pid.'_NivLevel'.$i.'" class="control-label col-md-4">Level 1 :</label>
					<div class="col-md-8">
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay" value="" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">Excl. VAT</span></div></div>
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay" value="" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">Incl. VAT</span></div></div>
						<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="'.$zone_pid.'" class="priceInputReal" value=""></div>
					</div>
				</div>
				<hr>
				<div class="form-group">
					<div id="'.$zone_pid.'_NivLevel'.$i.'" class="control-label col-md-4"><div class="checkbox checkbox-success"><input type="checkbox" name="freeshippingcostsabove['.$zone_pid.']" id="freeshippingcostsabove['.$zone_pid.']" value="1"'.($freeshippingcosts_above ? ' checked="checked"' : '').' /><label for="freeshippingcostsabove['.$zone_pid.']">Free shippingcosts for order amount above</label></div></div>
					<div class="col-md-8">
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceExcludingVat priceInputDisplay" value="" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('excluding_vat').'</span></div></div>
						<div class="msAttributesField"><div class="input-group"><span class="input-group-addon">'.mslib_fe::currency().'</span><input type="text" id="display_name" name="display_name" class="form-control msProductsPriceIncludingVat priceInputDisplay" value="" rel="'.$shipping_id.'" autocomplete="off"><span class="input-group-addon">'.$this->pi_getLL('including_vat').'</span></div></div>
						<div class="msAttributesField hidden"><input type="hidden" style="text-align:right" size="3" name="freeshippingcostsabove_value['.$zone_pid.']" class="priceInputReal" value=""></div>
					</div>
				</div>';
				$content.='</div></div>';
			}
		}
	}
	// hook to process custom shipping type ajax request
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/admin_shipping_costs_ajax.php']['processCustomShippingTypeAjaxRequest'])) {
		$params=array(
			'zones'=>&$zones,
			'content'=>&$content
		);
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/admin_shipping_costs_ajax.php']['processCustomShippingTypeAjaxRequest'] as $funcRef) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
		}
	}
	// hook to process custom shipping type ajax request eof
	$content.='
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			function productPrice(to_include_vat, o, type) {
				var original_val = o.val();
				var current_value 	= parseFloat(o.val());
				//
				if (original_val.indexOf(",")!=-1 && original_val.indexOf(".")!=-1) {
					var thousand=original_val.split(".");
					if (thousand[1].indexOf(",")!=-1) {
						var hundreds = thousand[1].split(",");
						original_val = thousand[0] + hundreds[0] + "." + hundreds[1];
						current_value = parseFloat(original_val);
						//
						o.val(original_val);
					} else {
						thousand=original_val.split(",");
						if (thousand[1].indexOf(".")!=-1) {
							var hundreds = thousand[1].split(".");
							original_val = thousand[0] + hundreds[0] + "." + hundreds[1];
							current_value = parseFloat(original_val);
							//
							o.val(original_val);
						}
					}
				}
				//
				var tax_id 			= o.attr("rel");
				if (current_value > 0) {
					if (to_include_vat) {
						jQuery.getJSON("'.mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_tax_ruleset').'", { current_price: current_value, to_tax_include: true, tax_group_id: tax_id }, function(json) {
							if (json && json.price_including_tax) {
								var incl_tax_crop = decimalCrop(json.price_including_tax);
								o.parent().next().first().children().val(incl_tax_crop);
							} else {
								o.parent().next().first().children().val(current_value);
							}
						});
						// update the hidden excl vat
						o.parent().next().next().first().children().val(current_value);
					} else {
						jQuery.getJSON("'.mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_tax_ruleset').'", { current_price: current_value, to_tax_include: false, tax_group_id: tax_id }, function(json) {
							if (json && json.price_excluding_tax) {
								var excl_tax_crop = decimalCrop(json.price_excluding_tax);
								// update the excl. vat
								o.parent().prev().first().children().val(excl_tax_crop);
								// update the hidden excl vat
								o.parent().next().first().children().val(json.price_excluding_tax);
							} else {
								// update the excl. vat
								o.parent().prev().first().children().val(current_value);
								// update the hidden excl vat
								o.parent().next().first().children().val(current_value);
							}
						});
					}
				} else {
					if (to_include_vat) {
						// update the incl. vat
						o.parent().next().first().children().val(0);

						// update the hidden excl vat
						o.parent().next().next().first().children().val(0);

					} else {
						// update the excl. vat
						o.parent().prev().first().children().val(0);

						// update the hidden excl vat
						o.parent().next().first().children().val(0);
					}
				}
			}
			function decimalCrop(float) {
				var numbers = float.toString().split(".");
				var prime 	= numbers[0];
				if (numbers[1] > 0 && numbers[1] != "undefined") {
					var decimal = new String(numbers[1]);
				} else {
					var decimal = "00";
				}
				var number = prime + "." + decimal.substr(0, 2);
				return number;
			}
			jQuery(document).on("keyup", ".msProductsPriceExcludingVat", function(e) {
				if (e.keyCode!=9) {
					priceEditRealtimeCalc(true, jQuery(this));
				}
			});
			jQuery("document").on("keyup", ".msProductsPriceIncludingVat", function(e) {
				if (e.keyCode!=9) {
					priceEditRealtimeCalc(false, jQuery(this));
				}
			});
		});
	</script>';
	echo $content;
	exit();
}
?>