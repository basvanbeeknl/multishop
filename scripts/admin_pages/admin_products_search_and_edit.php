<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
if (!is_numeric($this->get['cid'])) {
	$this->get['cid']=$this->categoriesStartingPoint;
}
$postMessageArray=array();
// now parse all the objects in the tmpl file
if ($this->conf['admin_products_search_and_edit_tmpl_path']) {
	$template=$this->cObj->fileResource($this->conf['admin_products_search_and_edit_tmpl_path']);
} else {
	$template=$this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'templates/admin_products_search_and_edit.tmpl');
}
// Extract the subparts from the template
$subparts=array();
$subparts['template']=$this->cObj->getSubpart($template, '###TEMPLATE###');
$subparts['results']=$this->cObj->getSubpart($subparts['template'], '###RESULTS###');
$subparts['products_item']=$this->cObj->getSubpart($subparts['results'], '###PRODUCTS_ITEM###');
$subparts['noresults']=$this->cObj->getSubpart($subparts['template'], '###NORESULTS###');
// temporary disable the flat mode if its enabled
if ($this->get['search'] and ($this->get['tx_multishop_pi1']['limit']!=$this->cookie['limit'])) {
	$this->cookie['limit']=$this->get['tx_multishop_pi1']['limit'];
	$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
	$GLOBALS['TSFE']->storeSessionData();
}
if ($this->cookie['limit']) {
	$this->get['tx_multishop_pi1']['limit']=$this->cookie['limit'];
} else {
	$this->get['tx_multishop_pi1']['limit']=10;
}
$this->ms['MODULES']['PRODUCTS_LISTING_LIMIT']=$this->get['tx_multishop_pi1']['limit'];
$prepending_content=$content;
$content='';
if ($this->get['keyword']) {
	$this->get['keyword']=trim($this->get['keyword']);
}
if (is_numeric($this->get['p'])) {
	$p=$this->get['p'];
}
if ($p>0) {
	$offset=(((($p)*$this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'])));
} else {
	$p=0;
	$offset=0;
}
if ($this->post['submit']) {
	if ($this->ms['MODULES']['FLAT_DATABASE']) {
		$updateFlatProductIds=array();
	}
	$data_update=array();
	foreach ($this->post['up']['regular_price'] as $pid=>$price) {
		if (is_numeric($pid)) {
			if (strstr($price, ",")) {
				$price=str_replace(",", ".", $price);
			}
			$data_update[$pid]['price']=$price;
			$updateArray=array();
			$updateArray['products_price']=$price;
			// if product is originally coming from products importer we have to define that the merchant changed it
			$filter=array();
			$filter[]='products_id='.$pid;
			if (mslib_befe::ifExists('1', 'tx_multishop_products', 'imported_product', $filter)) {
				// lock changed columns
				mslib_befe::updateImportedProductsLockedFields($pid, 'tx_multishop_products', $updateArray);
			}
			/*
			// if product is originally coming from products importer we have to define that the merchant changed it
			$str="select products_id from tx_multishop_products where imported_product=1 and lock_imported_product=0 and products_id='".$pid."'";
			$qry=$GLOBALS['TYPO3_DB']->sql_query($str);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0) {
				$updateArray['lock_imported_product']=1;
			}
			*/
			$query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products', 'products_id=\''.$pid.'\'', $updateArray);
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$updateFlatProductIds[]=$pid;
			}
		}
	}
	foreach ($this->post['up']['weight'] as $pid=>$weight) {
		$data_update[$pid]['weight']=$weight;
		$sql_upd="update tx_multishop_products set products_weight = '".$weight."' where products_id = ".$pid;
		$GLOBALS['TYPO3_DB']->sql_query($sql_upd);
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$updateFlatProductIds[]=$pid;
		}
	}
	foreach ($this->post['up']['stock'] as $pid=>$qty) {
		$data_update[$pid]['qty']=$qty;
		$sql_upd="update tx_multishop_products set products_quantity = '".$qty."' where products_id = ".$pid;
		$GLOBALS['TYPO3_DB']->sql_query($sql_upd);
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$updateFlatProductIds[]=$pid;
		}
	}
	foreach ($this->post['up']['special_price'] as $pid=>$price) {
		if (strstr($price, ",")) {
			$price=str_replace(",", ".", $price);
		}
		if ($price>0) {
			$sql_check="select products_id from tx_multishop_specials where products_id = ".$pid;
			$qry_check=$GLOBALS['TYPO3_DB']->sql_query($sql_check);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_check)>0 && $price>0) {
				$sql_upd="update tx_multishop_specials set specials_new_products_price = '".$price."', status = 1 where products_id = ".$pid;
				$GLOBALS['TYPO3_DB']->sql_query($sql_upd);
				if ($this->ms['MODULES']['FLAT_DATABASE']) {
					$updateFlatProductIds[]=$pid;
				}
			} else {
				if ($price>0) {
					$sql_ins="insert into tx_multishop_specials (products_id, status, specials_new_products_price, specials_date_added, news_item, home_item, scroll_item) values (".$pid.", 1, '".$price."', NOW(), 1, 1, 1)";
					$GLOBALS['TYPO3_DB']->sql_query($sql_ins);
					if ($this->ms['MODULES']['FLAT_DATABASE']) {
						$updateFlatProductIds[]=$pid;
					}
				}
			}
		} else {
			$query=$GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_specials', 'products_id=\''.addslashes($pid).'\'');
			$res=$GLOBALS['TYPO3_DB']->sql_query($query);
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$updateFlatProductIds[]=$pid;
			}
		}
	}
	if ($this->ms['MODULES']['FLAT_DATABASE']) {
		if (count($updateFlatProductIds)) {
			$ids=array_unique($updateFlatProductIds);
			foreach ($ids as $prodid) {
				// if the flat database module is enabled we have to sync the changes to the flat table
				mslib_befe::convertProductToFlat($prodid);
			}
		}
	}
	// custom page hook that can be controlled by third-party plugin
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditActionProductPostProc'])) {
		$params=array();
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditActionProductPostProc'] as $funcRef) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
		}
	}
	// custom page hook that can be controlled by third-party plugin eof
	if (count($this->post['selectedProducts'])) {
		switch ($this->post['tx_multishop_pi1']['action']) {
			case 'delete':
				foreach ($this->post['selectedProducts'] as $old_categories_id=>$array) {
					foreach ($array as $pid) {
						mslib_befe::deleteProduct($pid, $old_categories_id);
					}
				}
				break;
			case 'move':
				if (is_numeric($this->post['tx_multishop_pi1']['target_categories_id']) and mslib_befe::canContainProducts($this->post['tx_multishop_pi1']['target_categories_id'])) {
					foreach ($this->post['selectedProducts'] as $old_categories_id=>$array) {
						foreach ($array as $pid) {
							$filter=array();
							$filter[]='products_id='.$pid;
							if (mslib_befe::ifExists('1', 'tx_multishop_products', 'imported_product', $filter)) {
								// lock changed columns
								mslib_befe::updateImportedProductsLockedFields($pid, 'tx_multishop_products_to_categories', array('categories_id'=>$this->post['tx_multishop_pi1']['target_categories_id']));
							}
							mslib_befe::moveProduct($pid, $this->post['tx_multishop_pi1']['target_categories_id'], $old_categories_id);
						}
					}
				}
				break;
			case 'duplicate':
				foreach ($this->post['selectedProducts'] as $old_categories_id=>$array) {
					if ($this->post['tx_multishop_pi1']['target_categories_id']>0) {
						$target_cat_id=$this->post['tx_multishop_pi1']['target_categories_id'];
					} else {
						$target_cat_id=$old_categories_id;
					}
					foreach ($array as $pid) {
						mslib_befe::duplicateProduct($pid, $target_cat_id);
					}
				}
				break;
			default:
				// custom page hook that can be controlled by third-party plugin
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditActionProductIteratorProc'])) {
					$params=array(
						'action'=>&$this->post['tx_multishop_pi1']['action'],
						'postMessageArray'=>&$postMessageArray
					);
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditActionProductIteratorProc'] as $funcRef) {
						\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
					}
				}
				// custom page hook that can be controlled by third-party plugin eof
				break;
		}
	}
	// lets notify plugin that we have update action in products
	tx_mslib_catalog::productsUpdateNotifierForPlugin($this->post);
}
$fields=array();
$fields['products_name']=$this->pi_getLL('products_name');
$fields['products_model']=$this->pi_getLL('products_model');
$fields['products_description']=$this->pi_getLL('products_description');
$fields['products_price']=$this->pi_getLL('admin_price');
$fields['specials_price']=ucfirst($this->pi_getLL('admin_specials_price'));
$fields['products_id']=$this->pi_getLL('products_id');
$fields['categories_name']=$this->pi_getLL('admin_category');
$fields['products_quantity']=$this->pi_getLL('admin_stock');
$fields['products_weight']=$this->pi_getLL('admin_weight');
$fields['manufacturers_name']=$this->pi_getLL('manufacturer');
//asort($fields);
$searchby_selectbox='<select name="tx_multishop_pi1[search_by]" class="form-control">';
foreach ($fields as $key=>$label) {
	$searchby_selectbox.='<option value="'.$key.'"'.($this->get['tx_multishop_pi1']['search_by']==$key ? ' selected="selected"' : '').'>'.$label.'</option>'."\n";
}
$searchby_selectbox.='</select>';
//$search_category_selectbox=mslib_fe::tx_multishop_draw_pull_down_menu('cid', mslib_fe::tx_multishop_get_category_tree('', '', '', '', false, false, 'Root'), $this->get['cid'],'class="form-control"');
$search_category_selectbox='<input type="hidden" name="cid" class="categories_select2_top" id="msAdminSelect2Top" value="'.$this->get['cid'].'">';
$search_limit='<select name="tx_multishop_pi1[limit]" class="form-control">';
$limits=array();
$limits[]='10';
$limits[]='15';
$limits[]='20';
$limits[]='25';
$limits[]='30';
$limits[]='40';
$limits[]='50';
$limits[]='100';
$limits[]='150';
$limits[]='300';
$limits[]='500';
$limits[]='750';
foreach ($limits as $limit) {
	$search_limit.='<option value="'.$limit.'"'.($limit==$this->get['tx_multishop_pi1']['limit'] ? ' selected' : '').'>'.$limit.'</option>';
}
$search_limit.='</select>';
// product search
if ($this->ms['MODULES']['FLAT_DATABASE'] and !$this->ms['MODULES']['USE_FLAT_DATABASE_ALSO_IN_ADMIN_PRODUCTS_SEARCH_AND_EDIT']) {
	$this->ms['MODULES']['FLAT_DATABASE']=0;
}
$filter=array();
$having=array();
$match=array();
$orderby=array();
$where=array();
$select=array();
if (!$this->ms['MODULES']['FLAT_DATABASE']) {
	$select[]='p.products_status';
	$select[]='p.products_weight';
	$select[]='p.products_quantity';
	$select[]='s.specials_new_products_price';
}
//$filter[]='p.page_uid='.$this->shop_pid; is already inside the getProductsPageSet
if (isset($this->get['keyword']) and strlen($this->get['keyword'])>0) {
	switch ($this->get['tx_multishop_pi1']['search_by']) {
		case 'products_description':
			$prefix='pd.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_description like '%".addslashes($this->get['keyword'])."%')";
			break;
		case 'products_model':
			$prefix='p.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_model like '%".addslashes($this->get['keyword'])."%')";
			break;
		case 'products_weight':
			$prefix='p.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_weight like '".addslashes($this->get['keyword'])."%')";
			break;
		case 'products_quantity':
			$prefix='p.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_quantity like '".addslashes($this->get['keyword'])."%')";
			break;
		case 'products_price':
			$prefix='p.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_price like '".addslashes($this->get['keyword'])."%')";
			break;
		case 'categories_name':
			$prefix='cd.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."categories_name like '%".addslashes($this->get['keyword'])."%')";
			break;
		case 'specials_price':
			$prefix='s.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."specials_new_products_price like '".addslashes($this->get['keyword'])."%')";
			break;
		case 'products_id':
			$prefix='p.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_id like '".addslashes($this->get['keyword'])."%')";
			break;
		case 'products_name':
		default:
			$prefix='pd.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."products_name like '%".addslashes($this->get['keyword'])."%')";
			break;
		case 'manufacturers_name':
			$prefix='m.';
			if ($this->ms['MODULES']['FLAT_DATABASE']) {
				$prefix='pf.';
			}
			$filter[]="(".$prefix."manufacturers_name like '".addslashes($this->get['keyword'])."%')";
			break;
	}
}
switch ($this->get['tx_multishop_pi1']['order_by']) {
	case 'products_status':
		$order_by='p.products_status';
		break;
	case 'products_model':
		$prefix='p.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'products_model';
		break;
	case 'products_price':
		$prefix='p.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'products_price';
		break;
	case 'products_weight':
		$prefix='p.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'products_weight';
		break;
	case 'products_quantity':
		$prefix='p.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'products_quantity';
		break;
	case 'categories_name':
		$prefix='cd.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'categories_name';
		break;
	case 'specials_price':
		$prefix='s.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'specials_new_products_price';
		break;
	case 'products_name':
	default:
		$prefix='pd.';
		if ($this->ms['MODULES']['FLAT_DATABASE']) {
			$prefix='pf.';
		}
		$order_by=$prefix.'products_name';
		break;
}
switch ($this->get['tx_multishop_pi1']['order']) {
	case 'a':
		$order='asc';
		$order_link='d';
		break;
	case 'd':
	default:
		$order='desc';
		$order_link='a';
		break;
}
$orderby[]=$order_by.' '.$order;
if (is_numeric($this->get['manufacturers_id'])) {
	$prefix='p.';
	if ($this->ms['MODULES']['FLAT_DATABASE']) {
		$prefix='pf.';
	}
	$filter[]="(".$prefix."manufacturers_id='".addslashes($this->get['manufacturers_id'])."')";
}
if (is_numeric($this->get['cid']) and $this->get['cid']>0) {
	if ($this->ms['MODULES']['FLAT_DATABASE']) {
		$string='(';
		for ($i=0; $i<4; $i++) {
			if ($i>0) {
				$string.=" or ";
			}
			$string.="categories_id_".$i." = '".$this->get['cid']."'";
		}
		$string.=')';
		if ($string) {
			$filter[]=$string;
		}
	} else {
		$cats=mslib_fe::get_subcategory_ids($this->get['cid']);
		$cats[]=$this->get['cid'];
		$filter[]="p2c.categories_id IN (".implode(",", $cats).")";
	}
}
if (is_array($price_filter)) {
	if (!$this->ms['MODULES']['FLAT_DATABASE'] and (isset($price_filter[0]) and $price_filter[1])) {
		$having[]="(final_price >='".$price_filter[0]."' and final_price <='".$price_filter[1]."')";
	} elseif (isset($price_filter[0])) {
		$filter[]="price_filter=".$price_filter[0];
	}
} elseif ($price_filter) {
	$chars=array();
	$chars[]='>';
	$chars[]='<';
	foreach ($chars as $char) {
		if (strstr($price_filter, $char)) {
			$price_filter=str_replace($char, "", $price_filter);
			if ($char=='<') {
				$having[]="final_price <='".$price_filter."'";
			} elseif ($char=='>') {
				$having[]="final_price >='".$price_filter."'";
			}
		}
	}
}
if ($this->ms['MODULES']['FLAT_DATABASE'] and count($having)) {
	$filter[]=$having[0];
	unset($having);
}
if (isset($this->get['stock_from']) && !empty($this->get['stock_from']) && isset($this->get['stock_till']) && !empty($this->get['stock_till'])) {
    $prefix='p.';
    $filter[]="(".$prefix."products_quantity between ".$this->get['stock_from']." and ".$this->get['stock_till'].")";
}
$pageset=mslib_fe::getProductsPageSet($filter, $offset, $this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'], $orderby, $having, $select, $where, 0, array(), array(), 'admin_products_search');
$products=$pageset['products'];
$product_tax_rate_js=array();
if ($pageset['total_rows']>0) {
	$subpartArray=array();
	$subpartArray['###FORM_ACTION_PRICE_UPDATE_URL###']=mslib_fe::typolink($this->shop_pid.',2003', '&tx_multishop_pi1[page_section]=admin_products_search_and_edit&'.mslib_fe::tep_get_all_get_params(array(
			'tx_multishop_pi1[action]',
			'p',
			'Submit',
			'weergave',
			'clearcache'
		)));
	$query_string=mslib_fe::tep_get_all_get_params(array(
		'tx_multishop_pi1[action]',
		'tx_multishop_pi1[order_by]',
		'tx_multishop_pi1[order]',
		'p',
		'Submit',
		'weergave',
		'clearcache'
	));
	$key='products_name';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_PRODUCT_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_PRODUCT_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	//
	$key='products_model';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_MODEL_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_MODEL_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$key='products_status';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_VISIBLE_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_VISIBLE_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$key='categories_name';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_CATEGORY_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_CATEGORY_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$key='products_price';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_PRICE_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_PRICE_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$key='specials_price';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_SPECIAL_PRICE_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_SPECIAL_PRICE_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$key='products_quantity';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_STOCK_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_STOCK_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$key='products_weight';
	if ($this->get['tx_multishop_pi1']['order_by']==$key) {
		$final_order_link=$order_link;
	} else {
		$final_order_link='a';
	}
	$subpartArray['###FOOTER_SORTBY_WEIGHT_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###HEADER_SORTBY_WEIGHT_LINK###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_products_search_and_edit&tx_multishop_pi1[order_by]='.$key.'&tx_multishop_pi1[order]='.$final_order_link.'&'.$query_string);
	$subpartArray['###LABEL_HEADER_CELL_NUMBER###']=$this->pi_getLL('admin_nr');
	$subpartArray['###LABEL_HEADER_PRODUCT###']=$this->pi_getLL('admin_product');
	$subpartArray['###LABEL_HEADER_MODEL###']=$this->pi_getLL('admin_model');
	$subpartArray['###LABEL_HEADER_VISIBLE###']=$this->pi_getLL('admin_visible');
	$subpartArray['###LABEL_HEADER_CATEGORY###']=$this->pi_getLL('admin_category');
	$subpartArray['###LABEL_HEADER_PRICE###']=$this->pi_getLL('admin_price');
	$subpartArray['###LABEL_HEADER_SPECIAL_PRICE###']=$this->pi_getLL('admin_specials_price');
	$subpartArray['###LABEL_HEADER_STOCK###']=$this->pi_getLL('admin_stock');
	$subpartArray['###LABEL_HEADER_WEIGHT###']=$this->pi_getLL('admin_weight');
	$subpartArray['###LABEL_HEADER_ACTION###']=$this->pi_getLL('admin_action');
	$subpartArray['###LABEL_FOOTER_CELL_NUMBER###']=$this->pi_getLL('admin_nr');
	$subpartArray['###LABEL_FOOTER_PRODUCT###']=$this->pi_getLL('admin_product');
	$subpartArray['###LABEL_FOOTER_MODEL###']=$this->pi_getLL('admin_model');
	$subpartArray['###LABEL_FOOTER_VISIBLE###']=$this->pi_getLL('admin_visible');
	$subpartArray['###LABEL_FOOTER_CATEGORY###']=$this->pi_getLL('admin_category');
	$subpartArray['###LABEL_FOOTER_PRICE###']=$this->pi_getLL('admin_price');
	$subpartArray['###LABEL_FOOTER_SPECIAL_PRICE###']=$this->pi_getLL('admin_specials_price');
	$subpartArray['###LABEL_FOOTER_STOCK###']=$this->pi_getLL('admin_stock');
	$subpartArray['###LABEL_FOOTER_WEIGHT###']=$this->pi_getLL('admin_weight');
	$subpartArray['###LABEL_FOOTER_ACTION###']=$this->pi_getLL('admin_action');
	// custom page hook that can be controlled by third-party plugin
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditTmplPreProc'])) {
		$params=array(
			'subpartArray'=>&$subpartArray
		);
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditTmplPreProc'] as $funcRef) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
		}
	}
	// custom page hook that can be controlled by third-party plugin eof
	$s=0;
	$productsItem='';
	foreach ($products as $rs) {
		if ($switch=='odd') {
			$switch='even';
		} else {
			$switch='odd';
		}
		if ($rs['specials_new_products_price']==0 || empty($rs['specials_new_products_price'])) {
			$rs['specials_new_products_price']='';
		}
		$link_edit_cat=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=edit_category&cid='.$rs['categories_id'].'&action=edit_category');
		$link_edit_prod=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=edit_product&pid='.$rs['products_id'].'&cid='.$rs['categories_id'].'&action=edit_product');
		$link_delete_prod=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=delete_product&pid='.$rs['products_id'].'&action=delete_product');
		// view product link
		$where='';
		if ($rs['categories_id']) {
			// get all cats to generate multilevel fake url
			$level=0;
			$cats=mslib_fe::Crumbar($rs['categories_id']);
			$cats=array_reverse($cats);
			$where='';
			if (count($cats)>0) {
				foreach ($cats as $cat) {
					$where.="categories_id[".$level."]=".$cat['id']."&";
					$level++;
				}
				$where=substr($where, 0, (strlen($where)-1));
				$where.='&';
			}
			// get all cats to generate multilevel fake url eof
		}
		$product_detail_link=mslib_fe::typolink($this->conf['products_detail_page_pid'], '&'.$where.'&products_id='.$rs['products_id'].'&tx_multishop_pi1[page_section]=products_detail');
		// view product link eof
		$tmp_product_categories=mslib_fe::getProductToCategories($rs['products_id'], $rs['categories_id']);
		$product_categories=explode(',', $tmp_product_categories);
		$cat_crumbar='';
		foreach ($product_categories as $product_category) {
			$cat_crumbar.='<ul class="msAdminCategoriesCrum list-inline">';
			$cats=mslib_fe::Crumbar($product_category);
			$teller=0;
			$total=count($cats);
			for ($i=($total-1); $i>=0; $i--) {
				$teller++;
				// get all cats to generate multilevel fake url eof
				if ($total==$teller) {
					$class='lastItem';
				} else {
					$class='';
				}
				$cat_crumbar.='<li class="'.$class.'"><a href="'.mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=edit_category&cid='.$cats[$i]['id'].'&action=edit_category').'">'.$cats[$i]['name'].'</a></li>';
			}
			$cat_crumbar.='</ul>';
		}
		$status='';
		// fix for the flat table
		if (isset($rs['products_status'])) {
			if (!$rs['products_status']) {
				$status.='<span class="admin_status_red" alt="Disable"></span>';
				$status.='<a href="#" class="update_product_status" rel="'.$rs['products_id'].'"><span class="admin_status_green disabled" alt="Enabled"></span></a>';
			} else {
				$status.='<a href="#" class="update_product_status" rel="'.$rs['products_id'].'"><span class="admin_status_red disabled" alt="Disabled"></span></a>';
				$status.='<span class="admin_status_green" alt="Enable"></span>';
			}
		} else {
			$status.='<a href="#" class="update_product_status" rel="'.$rs['products_id'].'"><span class="admin_status_red disabled" alt="Disabled"></span></a>';
			$status.='<span class="admin_status_green" alt="Enable"></span>';
		}
		$product_tax_rate=0;
		$data=mslib_fe::getTaxRuleSet($rs['tax_id'], 0);
		$product_tax_rate=$data['total_tax_rate'];
        $product_tax_rate_js[]='product_tax_rate_js["'.$rs['products_id'].'"]="' . $data['total_tax_rate'] . '";';
		$product_tax=mslib_fe::taxDecimalCrop(($rs['products_price']*$product_tax_rate)/100);
		$product_price_display=mslib_fe::taxDecimalCrop($rs['products_price'], 2, false);
		$product_price_display_incl=mslib_fe::taxDecimalCrop($rs['products_price']+$product_tax, 2, false);
		$special_tax=mslib_fe::taxDecimalCrop(($rs['specials_new_products_price']*$product_tax_rate)/100);
		$special_price_display=mslib_fe::taxDecimalCrop($rs['specials_new_products_price'], 2, false);
		$special_price_display_incl=mslib_fe::taxDecimalCrop($rs['specials_new_products_price']+$special_tax, 2, false);
		$markerArray=array();
		$markerArray['ROW_TYPE']=$switch;
		$markerArray['CATEGORY_ID0']=$rs['categories_id'];
		$markerArray['CHECKBOX_COUNTER0']=$s;
		$markerArray['CHECKBOX_COUNTER1']=$s;
		$markerArray['CELL_NUMBER']=(($p*$this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'])+$s+1);
		$markerArray['PRODUCT_NAME']=($rs['products_name'] ? $rs['products_name'] : $this->pi_getLL('no_name'));
		$markerArray['PRODUCT_CATEGORIES_CRUMBAR']=$cat_crumbar;
		$markerArray['PRODUCT_MODEL']=$rs['products_model'];
		$markerArray['PRODUCT_STATUS']=$status;
		$markerArray['LINK_EDIT_CAT']=$link_edit_cat;
		$markerArray['CATEGORY_NAME']=$rs['categories_name'];
		$markerArray['VALUE_TAX_ID']=$rs['tax_id'];
		$markerArray['CURRENCY0']=mslib_fe::currency();
		$markerArray['CURRENCY1']=mslib_fe::currency();
		$markerArray['CURRENCY2']=mslib_fe::currency();
		$markerArray['CURRENCY3']=mslib_fe::currency();

		$markerArray['SUFFIX_PRICE_EXCL_VAT']=$this->pi_getLL('excluding_vat');
		$markerArray['SUFFIX_PRICE_INCL_VAT']=$this->pi_getLL('including_vat');
		$markerArray['SUFFIX_SPECIAL_PRICE_EXCL_VAT']=$this->pi_getLL('excluding_vat');
		$markerArray['SUFFIX_SPECIAL_PRICE_INCL_VAT']=$this->pi_getLL('including_vat');

		$markerArray['VALUE_PRICE_EXCL_VAT']=htmlspecialchars($product_price_display);
		$markerArray['VALUE_PRICE_INCL_VAT']=htmlspecialchars($product_price_display_incl);
		$markerArray['VALUE_ORIGINAL_PRICE']=$rs['products_price'];
		$markerArray['VALUE_SPECIAL_PRICE_EXCL_VAT']=htmlspecialchars($special_price_display);
		$markerArray['VALUE_SPECIAL_PRICE_INCL_VAT']=htmlspecialchars($special_price_display_incl);
		$markerArray['VALUE_ORIGINAL_SPECIAL_PRICE']=$rs['specials_new_products_price'];
		$markerArray['VALUE_PRODUCT_QUANTITY']=$rs['products_quantity'];
		$markerArray['VALUE_PRODUCT_WEIGHT']=$rs['products_weight'];
		$markerArray['PID0']=$rs['products_id'];
		$markerArray['PID1']=$rs['products_id'];
		$markerArray['PID2']=$rs['products_id'];
		$markerArray['PID3']=$rs['products_id'];
		$markerArray['PID4']=$rs['products_id'];
		$markerArray['PID5']=$rs['products_id'];
		$markerArray['PID6']=$rs['products_id'];
		$markerArray['PID7']=$rs['products_id'];
		$markerArray['PID8']=$rs['products_id'];
		$markerArray['EDIT_PRODUCT_LINK0']=$link_edit_prod;
		$markerArray['EDIT_PRODUCT_LINK1']=$link_edit_prod;
		$markerArray['PRODUCT_DETAIL_LINK']=$product_detail_link;
		$markerArray['DELETE_PRODUCT_LINK']=$link_delete_prod;
		// custom page hook that can be controlled by third-party plugin
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditTmplIteratorPreProc'])) {
			$params=array(
				'markerArray'=>&$markerArray,
				'rs'=>&$rs
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditTmplIteratorPreProc'] as $funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
			}
		}
		// custom page hook that can be controlled by third-party plugin eof
		$productsItem.=$this->cObj->substituteMarkerArray($subparts['products_item'], $markerArray, '###|###');
		$s++;
	}
	$actions=array();
	$actions['move']=$this->pi_getLL('move_selected_products_to').':';
	$actions['duplicate']=$this->pi_getLL('duplicate_selected_products_to').':';
	$actions['delete']=$this->pi_getLL('delete_selected_products');
	// custom page hook that can be controlled by third-party plugin
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditActionItemsPreProc'])) {
		$params=array(
			'actions'=>&$actions
		);
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_products_search_and_edit.php']['adminProductsSearchAndEditActionItemsPreProc'] as $funcRef) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
		}
	}
	// custom page hook that can be controlled by third-party plugin eof
	$action_selectbox.='<select name="tx_multishop_pi1[action]" id="products_search_action" class="form-control"><option value="">'.$this->pi_getLL('choose_action').'</option>';
	foreach ($actions as $key=>$value) {
		$action_selectbox.='<option value="'.$key.'">'.$value.'</option>';
	}
	$action_selectbox.='</select>';
	//$input_categories_selectbox=mslib_fe::tx_multishop_draw_pull_down_menu('tx_multishop_pi1[target_categories_id]', mslib_fe::tx_multishop_get_category_tree('', '', ''), '', 'class="form-control" id="target_categories_id"');
    $input_categories_selectbox='<div id="target_categories_id"><input type="hidden" name="tx_multishop_pi1[target_categories_id]" class="categories_select2" id="msAdminSelect2Bottom"></div>';
	$dlink="location.href = '/".mslib_fe::typolink('', 'tx_multishop_pi1[page_section]=admin_price_update_dl_xls')."'";
	if (isset($this->get['cid']) && $this->get['cid']>0) {
		$dlink="location.href = '/".mslib_fe::typolink('', 'tx_multishop_pi1[page_section]=admin_price_update_dl_xls&cid='.$this->get['cid'])."'";
	}
	$pagination='';
	$content='';
	$this->ms['MODULES']['PAGESET_LIMIT']=$this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'];
	// pagination
	if (!$this->ms['nopagenav'] and $pageset['total_rows']>$this->ms['MODULES']['PAGESET_LIMIT']) {
		require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop').'scripts/admin_pages/includes/admin_pagination.php');
		$pagination=$tmp;
	}
	// pagination eof
	$content='';
	$subpartArray['###PAGE_NUMBER###']=$this->get['p'];
	$subpartArray['###CATEGORY_ID1###']=$this->get['cid'];
	$subpartArray['###INPUT_ACTION_SELECTBOX###']=$action_selectbox;
	$subpartArray['###INPUT_CATEGORIES_SELECTBOX###']=$input_categories_selectbox;
	$subpartArray['###LABEL_ADMIN_SUBMIT###']=$this->pi_getLL('submit_form');
	$subpartArray['###LABEL_DOWNLOAD_AS_EXCEL_FILE###']=$this->pi_getLL('admin_download_as_excel_file');
	$subpartArray['###DOWNLOAD_AS_EXCEL_URL###']=$dlink;
	$subpartArray['###LABEL_UPDATE_MODIFIED_PRODUCTS###']=$this->pi_getLL('update_modified_products');
	$subpartArray['###FORM_UPLOAD_ACTION_URL###']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=admin_price_update_up_xls');
	$subpartArray['###CATEGORY_ID2###']=$this->get['cid'];
	$subpartArray['###PRODUCTS_PAGINATION###']=$pagination;
	$subpartArray['###LABEL_UPLOAD_EXCEL_FILE###']=$this->pi_getLL('admin_upload_excel_file');
	$subpartArray['###LABEL_ADMIN_UPLOAD###']=$this->pi_getLL('admin_upload');
	$subpartArray['###LABEL_BACK_TO_CATALOG###']=$this->pi_getLL('admin_close_and_go_back_to_catalog');
	$subpartArray['###BACK_TO_CATALOG_LINK###']=mslib_fe::typolink();
	$subpartArray['###LABEL_ADMIN_YES###']=$this->pi_getLL('admin_yes');
	$subpartArray['###LABEL_ADMIN_NO###']=$this->pi_getLL('admin_no');
	$subpartArray['###ADMIN_LABEL_ENABLE0###']=$this->pi_getLL('admin_label_enable');
	$subpartArray['###ADMIN_LABEL_DISABLE0###']=$this->pi_getLL('admin_label_disable');
	$subpartArray['###ADMIN_LABEL_ENABLE1###']=$this->pi_getLL('admin_label_enable');
	$subpartArray['###ADMIN_LABEL_DISABLE1###']=$this->pi_getLL('admin_label_disable');
	$subpartArray['###PRODUCTS_ITEM###']=$productsItem;
	$tmp_content_results=$this->cObj->substituteMarkerArrayCached($subparts['results'], array(), $subpartArray);
} else {
	$subpartArray=array();
	$subpartArray['###LABEL_BACK_TO_CATALOG###']=$this->pi_getLL('admin_close_and_go_back_to_catalog');
	$subpartArray['###BACK_TO_CATALOG_LINK###']=mslib_fe::typolink();
	$subpartArray['###LABEL_NO_RESULT###']=$this->pi_getLL('no_products_available');
	$tmp_content_noresults=$this->cObj->substituteMarkerArrayCached($subparts['noresults'], array(), $subpartArray);
}
$subpartArray=array();
$subpartArray['###POST_MESSAGE###']='';
if ($postMessageArray) {
	$postmessage='<div id="postMessage"><h3>System message</h3><ul>';
	foreach ($postMessageArray as $item) {
		$postmessage.='<li>'.$item.'</li>';
	}
	$postmessage.='</ul></div>';
	$subpartArray['###POST_MESSAGE###']=$postmessage;
}
$subpartArray['###SHOP_PID###']=$this->shop_pid;
$subpartArray['###UNFOLD_SEARCH_BOX###']='';
if ((isset($this->get['stock_from']) && !empty($this->get['stock_from'])) ||
    (isset($this->get['stock_till']) && !empty($this->get['stock_till']))) {
    $subpartArray['###UNFOLD_SEARCH_BOX###']=' in';
}
$subpartArray['###LABEL_STOCK_FROM###']=$this->pi_getLL('from');
$subpartArray['###LABEL_STOCK###']=$this->pi_getLL('stock');
$subpartArray['###VALUE_STOCK_FROM###']=$this->get['stock_from'];
$subpartArray['###LABEL_STOCK_TO###']=$this->pi_getLL('to');
$subpartArray['###VALUE_STOCK_TO###']=$this->get['stock_till'];

$subpartArray['###PAGE_HEADER###']=$this->pi_getLL('products');
$subpartArray['###LABEL_SEARCH_KEYWORD###']=$this->pi_getLL('admin_search_for');
$subpartArray['###VALUE_SEARCH_KEYWORD###']=((isset($this->get['keyword'])) ? htmlspecialchars($this->get['keyword']) : '');
$subpartArray['###LABEL_SEARCH_BY###']=$this->pi_getLL('by');
$subpartArray['###SEARCH_BY_SELECTBOX###']=$searchby_selectbox;
$subpartArray['###LABEL_SEARCH_IN###']=$this->pi_getLL('in');
$subpartArray['###SEACRH_IN_CATEGORY_TREE_SELECTBOX###']=$search_category_selectbox;
$subpartArray['###LABEL_SEARCH_LIMIT###']=$this->pi_getLL('limit_number_of_records_to');
$subpartArray['###SEARCH_LIMIT###']=$search_limit;
$subpartArray['###LABEL_ADVANCED_SEARCH###']=$this->pi_getLL('advanced_search');
$subpartArray['###LABEL_SEARCH###']=$this->pi_getLL('search');
//
$subpartArray['###AJAX_UPDATE_PRODUCT_STATUS_URL###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=update_products_status');
$subpartArray['###AJAX_PRODUCT_CATEGORIES_FULL0###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_category_tree&tx_multishop_pi1[get_category_tree]=getFullTree&tx_multishop_pi1[includeDisabledCats]=1');
$subpartArray['###AJAX_PRODUCT_CATEGORIES_GET_VALUE0###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_category_tree&tx_multishop_pi1[get_category_tree]=getValues');
$subpartArray['###AJAX_PRODUCT_CATEGORIES_FULL1###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_category_tree&tx_multishop_pi1[get_category_tree]=getFullTree');
$subpartArray['###AJAX_PRODUCT_CATEGORIES_GET_VALUE1###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_category_tree&tx_multishop_pi1[get_category_tree]=getValues');
$subpartArray['###AJAX_GET_TAX_RULESET_URL0###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_tax_ruleset');
$subpartArray['###AJAX_GET_TAX_RULESET_URL1###']=mslib_fe::typolink($this->shop_pid.',2002', '&tx_multishop_pi1[page_section]=get_tax_ruleset');
//
$subpartArray['###RESULTS###']=$tmp_content_results;
$subpartArray['###NORESULTS###']=$tmp_content_noresults;

// Instantiate admin interface object
$objRef = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:multishop/pi1/classes/class.tx_mslib_admin_interface.php:&tx_mslib_admin_interface');
$objRef->init($this);
$objRef->setInterfaceKey('admin_products');

// Header buttons
$headerButtons=array();
$headingButton=array();
$headingButton['btn_class']='btn btn-primary';
$headingButton['fa_class']='fa fa-plus-circle';
$headingButton['title']=$this->pi_getLL('admin_create_new_products_here');
$headingButton['href']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=add_product&action=add_product');
$headerButtons[]=$headingButton;
// Create category button
$headingButton['btn_class']='btn btn-primary';
$headingButton['fa_class']='fa fa-plus-circle';
$headingButton['title']=$this->pi_getLL('admin_add_new_category_to_the_catalog');
$headingButton['href']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=add_category&action=add_category');
$headerButtons[]=$headingButton;
// Create multiple categories button
$headingButton=array();
$headingButton['btn_class']='btn btn-primary';
$headingButton['fa_class']='fa fa-plus-circle';
$headingButton['title']=$this->pi_getLL('admin_add_new_multiple_category_to_the_catalog', 'Add new categories simultaneous');
$headingButton['href']=mslib_fe::typolink($this->shop_pid.',2003', 'tx_multishop_pi1[page_section]=add_multiple_category&action=add_multiple_category');
$headerButtons[]=$headingButton;

// Set header buttons through interface class so other plugins can adjust it
$objRef->setHeaderButtons($headerButtons);
// Get header buttons through interface class so we can render them
$interfaceHeaderButtons=$objRef->renderHeaderButtons();
// Get header buttons through interface class so we can render them
$subpartArray['###INTERFACE_HEADER_BUTTONS###']=$objRef->renderHeaderButtons();

$content.=$this->cObj->substituteMarkerArrayCached($subparts['template'], array(), $subpartArray);
$content=$prepending_content.'<div class="fullwidth_div">'.mslib_fe::shadowBox($content).'</div>';
$GLOBALS['TSFE']->additionalHeaderData[]='<script type="text/javascript" data-ignore="1">
var product_tax_rate_js=[];
'.implode("\n", $product_tax_rate_js).'
</script>
';
?>