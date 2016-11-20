<?php
header('Content-Type: application/json');
if ($this->ms['MODULES']['CACHE_FRONT_END'] and !$this->ms['MODULES']['CACHE_TIME_OUT_SEARCH_PAGES']) {
    $this->ms['MODULES']['CACHE_FRONT_END'] = 0;
}
if ($this->ms['MODULES']['CACHE_FRONT_END']) {
    $options = array(
            'caching' => true,
            'cacheDir' => $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/cache/',
            'lifeTime' => $this->ms['MODULES']['CACHE_TIME_OUT_SEARCH_PAGES']
    );
    $Cache_Lite = new Cache_Lite($options);
    $string = md5($this->cObj->data['uid'] . '_' . $this->server['REQUEST_URI'] . $this->server['QUERY_STRING'] . print_r($this->get, 1) . print_r($this->post, 1));
}
if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$content = $Cache_Lite->get($string))) {
    if ($this->post['locationHash']) {
        // convert the hash to post
        $locationHash = $this->post['locationHash'];
        parse_str(urldecode($this->post['locationHash']), $this->post);
    }
    if ($this->post['tx_multishop_pi1']['limitsb']) {
        if ($this->post['tx_multishop_pi1']['limitsb'] and $this->post['tx_multishop_pi1']['limitsb'] != $this->cookie['limitsb']) {
            $this->cookie['limitsb'] = $this->post['tx_multishop_pi1']['limitsb'];
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
            $GLOBALS['TSFE']->storeSessionData();
        }
    }
    if ($this->post['tx_multishop_pi1']['sortbysb']) {
        if ($this->post['tx_multishop_pi1']['sortbysb'] and $this->post['tx_multishop_pi1']['sortbysb'] != $this->cookie['sortbysb']) {
            $this->cookie['sortbysb'] = $this->post['tx_multishop_pi1']['sortbysb'];
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
            $GLOBALS['TSFE']->storeSessionData();
        }
    } else {
        $this->cookie['sortbysb'] = '';
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
        $GLOBALS['TSFE']->storeSessionData();
    }
    $select = array();
    $formFields = array();
    $ultrasearch_hash = base64_decode($this->get['tx_multishop_pi1']['ultrasearch_hash']);
    $fields = explode(";", $ultrasearch_hash);
    $count = 0;
    // counter query init array
    $totalCountFilter = array();
    $totalCountFrom = array();
    $totalCountWhere = array();
    foreach ($this->post['tx_multishop_pi1']['categories'] as $key => $val) {
        if ($val == 0) {
            unset($this->post['tx_multishop_pi1']['categories'][$key]);
        }
    }
    if (is_numeric($this->get['filterCategoriesFormByCategoriesIdGetParam'])) {
        $this->filterCategoriesFormByCategoriesIdGetParam = $this->get['filterCategoriesFormByCategoriesIdGetParam'];
    }
    //error_log('filterCategoriesFormByCategoriesIdGetParam : '.$this->get['ultrasearch_exclude_negative_filter_values']);
    $parent_id = $this->categoriesStartingPoint;
    if ($this->filterCategoriesFormByCategoriesIdGetParam) {
        if (isset($this->get['categories_id']) && is_numeric($this->get['categories_id'])) {
            $parent_id = $this->get['categories_id'];
        }
        if (isset($this->post['categories_id']) && is_numeric($this->post['categories_id'])) {
            $parent_id = $this->post['categories_id'];
        }
    }
    if (is_numeric($this->get['manufacturers_id'])) {
        if (!is_array($this->post['tx_multishop_pi1']['manufacturers'])) {
            $this->post['tx_multishop_pi1']['manufacturers'] = array();
        }
        if (!in_array($this->get['manufacturers_id'], $this->post['tx_multishop_pi1']['manufacturers'])) {
            $this->post['tx_multishop_pi1']['manufacturers'][] = $this->get['manufacturers_id'];
        }
    }
    $subcats_data = array();
    if (is_numeric($parent_id) and $parent_id > 0) {
        $categories_data = mslib_fe::getSubcatsOnly($parent_id);
        if (is_array($categories_data) and count($categories_data)) {
            foreach ($categories_data as $row) {
                //$subcats_data=mslib_fe::get_subcategory_ids($row['categories_id']);
                $subcats_data[] = $row['categories_id'];
            }
        }
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $string = '(';
            for ($i = 0; $i < 4; $i++) {
                if ($i > 0) {
                    $string .= " or ";
                }
                $string .= "pf.categories_id_" . $i . " = '" . $parent_id . "'";
            }
            $string .= ')';
            if ($string) {
                $totalCountFilter[] = $string;
            }
            //
        } else {
            /*
			$cats=mslib_fe::get_subcategory_ids($parent_id);
			$cats[]=$parent_id;
			if(is_array($this->post['categories_id_extra'])){
				$cats = array();
				foreach ($this->post['categories_id_extra'] as $key_id=>$catid){
					$cats_extra=mslib_fe::get_subcategory_ids($catid);
					$cats[]=$catid;
					$cats=array_merge($cats_extra, $cats);
				}
			}
			$totalCountFilter[]="p2c.is_deepest=1 AND p2c.categories_id IN (".implode(",",$cats).")";
			*/
            if ($this->post['categories_id_extra']) {
                $totalCountFilter[] = "p2c.node_id IN (" . addslashes(implode(",", $this->post['categories_id_extra'])) . ")";
            } else {
                if (!$this->filterCategoriesFormByCategoriesIdGetParam) {
                    if (is_array($subcats_data) && count($subcats_data)) {
                        $totalCountFilter[] = "p2c.node_id in (" . implode(',', $subcats_data) . ")";
                    } else {
                        $totalCountFilter[] = "p2c.node_id=" . addslashes($parent_id);
                    }
                }
            }
        }
    }
    if (is_array($this->post['tx_multishop_pi1']['categories']) && count($this->post['tx_multishop_pi1']['categories'])) {
        $subcats_data = array();
        foreach ($this->post['tx_multishop_pi1']['categories'] as $key => $val) {
            if ($val > 0) {
                $tmp_man_get_subscat = mslib_fe::get_subcategory_ids($val);
                foreach ($tmp_man_get_subscat as $tmp_subs_catid) {
                    $subcats_data[] = $tmp_subs_catid;
                }
                $subcats_data[] = $val;
            }
        }
    }
    if (is_numeric($this->post['min']) and is_numeric($this->post['max'])) {
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $tbl = 'pf.';
        } else {
            $tbl = 'p.';
        }
        $totalCountFilter[] = "(" . $tbl . ".final_price BETWEEN '" . addslashes($this->post['min']) . "' and '" . addslashes($this->post['max']) . "')";
    }
    if (strlen($this->post['tx_multishop_pi1']['q']) > 2) {
        $array = explode(" ", $this->post['tx_multishop_pi1']['q']);
        $total = count($array);
        $oldsearch = 0;
        if (!$this->ms['MODULES']['ENABLE_FULLTEXT_SEARCH_IN_PRODUCTS_SEARCH']) {
            $oldsearch = 1;
        } else {
            foreach ($array as $item) {
                if (strlen($item) < $this->ms['MODULES']['FULLTEXT_SEARCH_MIN_CHARS']) {
                    $oldsearch = 1;
                    break;
                }
            }
        }
        $search_in_option_ids = array();
        if ($this->ms['MODULES']['SEARCH_ALSO_IN_ATTRIBUTE_OPTION_IDS']) {
            $optionIds = explode(',', $this->ms['MODULES']['SEARCH_ALSO_IN_ATTRIBUTE_OPTION_IDS']);
            if (is_array($optionIds) and count($optionIds)) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                foreach ($optionIds as $optionId) {
                    if (is_numeric($optionId)) {
                        $search_in_option_ids[] = $optionId;
                    }
                }
            }
        }
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $tbl = 'pf.';
        } else {
            $tbl = 'pd.';
        }
        if ($oldsearch) {
            // do normal indexed search
            $innerFilter = array();
            if ($this->ms['MODULES']['FLAT_DATABASE']) {
                $tbl = 'pf.';
            } else {
                $tbl = 'pd.';
            }
            $innerFilter[] = "(" . $tbl . "products_name like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                $innerFilter[] = "(" . $tbl . "products_description like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_TITLE']) {
                $innerFilter[] = "(" . $tbl . "products_meta_title like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_KEYWORDS']) {
                $innerFilter[] = "(" . $tbl . "products_meta_keywords like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_DESCRIPTION']) {
                $innerFilter[] = "(" . $tbl . "products_meta_description like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_MANUFACTURERS_NAME']) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'm.';
                }
                $innerFilter[] = "(" . $tbl . "manufacturers_name like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if (count($search_in_option_ids)) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                foreach ($search_in_option_ids as $options_id) {
                    if (is_numeric($options_id)) {
                        $innerFilter[] = '(' . $tbl . 'products_id IN (SELECT DISTINCT(pa.products_id) FROM tx_multishop_products_attributes pa, tx_multishop_products_options po, tx_multishop_products_options_values pov,  tx_multishop_products_options_values_to_products_options povp where pa.options_id=\'' . $options_id . '\' and pa.page_uid=\'' . $this->showCatalogFromPage . '\' and pov.products_options_values_name like \'%' . addslashes($this->post['tx_multishop_pi1']['q']) . '%\' and pov.language_id=0 and pov.products_options_values_id=povp.products_options_values_id and povp.products_options_values_id=pa.options_values_id and pa.options_id=povp.products_options_id and po.language_id=pov.language_id and po.products_options_id=povp.products_options_id and po.language_id=pov.language_id))';
                    }
                }
            }
            $totalCountFilter[] = "(" . implode(" OR ", $innerFilter) . ")";
        } else {
            // do fulltext search
            // $tmpstr=addslashes(mslib_befe::ms_implode(', ', $array,'"','+',true));
            // $select[]	="MATCH (".$tbl."products_name) AGAINST ('".$tmpstr."' in boolean mode) AS score";
            $tmpstr = addslashes(mslib_befe::ms_implode(', ', $array, '"', '+', true));
            $ultra_fields = $tbl . "products_name";
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                $ultra_fields .= "," . $tbl . "products_description";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_TITLE']) {
                $ultra_fields .= "," . $tbl . "products_meta_title";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_KEYWORDS']) {
                $ultra_fields .= "," . $tbl . "products_meta_keywords";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_DESCRIPTION']) {
                $ultra_fields .= "," . $tbl . "products_meta_description";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_ID']) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                $ultra_fields .= "," . $tbl . "products_id";
            }
            $select[] = "MATCH (" . $ultra_fields . ") AGAINST ('" . $tmpstr . "' in boolean mode) AS score";
            $totalCountFilter[] = "MATCH (" . $ultra_fields . ") AGAINST ('" . $tmpstr . "' in boolean mode)";
        }
    }
    $totalCountSubFilter = array();
    if (is_array($this->post['tx_multishop_pi1']['categories']) && count($this->post['tx_multishop_pi1']['categories'])) {
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $string = '(';
            for ($i = 0; $i < 4; $i++) {
                if ($i > 0) {
                    $string .= " or ";
                }
                $string .= "pf.categories_id_" . $i . " IN (" . addslashes(implode(",", $this->post['tx_multishop_pi1']['categories'])) . ")";
            }
            $string .= ')';
            if ($string) {
                $totalCountSubFilter['categories'][] = $string;
            }
            //
        } else {
            /*
			$subs_id_data = array();
			foreach ($this->post['tx_multishop_pi1']['categories'] as $catsid) {
				$get_subscat = mslib_fe::get_subcategory_ids($catsid);
				$subs_id_data[] = $catsid;
				if (count($get_subcat)) {
					foreach ($get_subcat as $subcat_id_data) {
						$subs_id_data[] = $subcat_id_data;
					}
				}
			}
			$totalCountSubFilter['categories'][]="p2c.is_deepest=1 AND p2c.categories_id IN (".implode(",",$subs_id_data).")";
			*/
            $post_categories_id = array();
            foreach ($this->post['tx_multishop_pi1']['categories'] as $key => $val) {
                $tmp_man_get_subscat = mslib_fe::get_subcategory_ids($val);
                foreach ($tmp_man_get_subscat as $tmp_subs_catid) {
                    $post_categories_id[] = $tmp_subs_catid;
                }
                $post_categories_id[] = $val;
            }
            $totalCountSubFilter['categories'][] = "p2c.node_id IN (" . addslashes(implode(",", $post_categories_id)) . ")";
        }
    }
    if (is_array($this->post['tx_multishop_pi1']['manufacturers']) && count($this->post['tx_multishop_pi1']['manufacturers'])) {
        foreach ($this->post['tx_multishop_pi1']['manufacturers'] as $key => $val) {
            if ($val == 0) {
                unset($this->post['tx_multishop_pi1']['manufacturers'][$key]);
            }
        }
        // attributes
        if (!$this->ms['MODULES']['FLAT_DATABASE']) {
            $prefix = 'p';
        } else {
            $prefix = 'pf';
        }
        $totalCountSubFilter['manufacturers'][] = $prefix . ".manufacturers_id IN (" . addslashes(implode(",", $this->post['tx_multishop_pi1']['manufacturers'])) . ")";
    }
    if (is_array($this->post['tx_multishop_pi1']['options'])) {
        // attributes
        if (!$this->ms['MODULES']['FLAT_DATABASE']) {
            $prefix = 'p';
        } else {
            $prefix = 'pf';
        }
        foreach ($this->post['tx_multishop_pi1']['options'] as $option_id => $option_values_id) {
            foreach ($option_values_id as $ovi_key => $ovi_val) {
                if ($ovi_val == 0) {
                    unset($option_values_id[$ovi_key]);
                }
            }
            if (is_array($option_values_id) and count($option_values_id)) {
                $totalCountSubFilter['options'][$option_id][] = "(pa_" . $option_id . ".options_id=" . $option_id . " and pa_" . $option_id . ".options_values_id IN (" . addslashes(implode(",", $option_values_id)) . "))";
                $totalCountFrom['options'][$option_id] = 'tx_multishop_products_attributes pa_' . $option_id;
                $totalCountWhere['options'][$option_id] = 'pa_' . $option_id . '.products_id=' . $prefix . '.products_id and pa_' . $option_id . '.page_uid=\'' . $this->showCatalogFromPage . '\'';
            }
        }
    }
    // custom hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchPreHook'])) {
        $params = array(
                'select' => &$select,
                'totalCountFilter' => &$totalCountFilter,
                'totalCountSubFilter' => &$totalCountSubFilter,
                'totalCountFrom' => &$totalCountFrom,
                'totalCountWhere' => &$totalCountWhere,
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchPreHook'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    // counter query init eof
    foreach ($fields as $field) {
        // reset
        $key = '';
        if (strstr($field, ":")) {
            $array = explode(":", $field);
            $key = $array[0];
        } else {
            $key = $field;
        }
        $count++;
        $formField = array();
        switch ($key) {
            case 'submit':
                $formField['caption'] = '';
                $formField['name'] = "tx_multishop_pi1[submit]";
                $formField['id'] = "submit";
                $formField['caption'] = '';
                $formField['type'] = "submit";
                $formField['placeholder'] = "";
                $formField['value'] = ucfirst($this->pi_getLL('search'));
                break;
            case 'reset':
                $formField['caption'] = '';
                $formField['name'] = "tx_multishop_pi1[submit]";
                $formField['id'] = "usreset";
                $formField['caption'] = '';
                $formField['type'] = "reset";
                $formField['placeholder'] = "";
                $formField['value'] = ucfirst($this->pi_getLL('reset'));
                break;
            case 'input_keywords':
                $formField['name'] = "tx_multishop_pi1[q]";
                $formField['id'] = "skeyword";
                $formField['caption'] = ucfirst($this->pi_getLL('keyword'));
                $formField['type'] = "text";
                $formField['placeholder'] = $this->pi_getLL('type_your_keyword_here');
                $formField['value'] = $this->post['tx_multishop_pi1']['q'];
                break;
            case 'categories':
                $formField['caption'] = $this->pi_getLL('categories');
                $array = explode(":", $field);
                $list_type = $array[1];
                $parent_id = $this->categoriesStartingPoint;
                if ($this->filterCategoriesFormByCategoriesIdGetParam) {
                    if (isset($this->get['categories_id']) && is_numeric($this->get['categories_id'])) {
                        $parent_id = $this->get['categories_id'];
                    }
                } else {
                    if (isset($this->get['categories_id']) && is_numeric($this->get['categories_id'])) {
                        if (!is_array($this->post['tx_multishop_pi1']['categories'])) {
                            $this->post['tx_multishop_pi1']['categories'] = array();
                        }
                        if (!in_array($this->get['categories_id'], $this->post['tx_multishop_pi1']['categories'])) {
                            $this->post['tx_multishop_pi1']['categories'][] = $this->get['categories_id'];
                        }
                    }
                }
                $categories = mslib_fe::getSubcatsOnly($parent_id);
                if (!is_array($categories)) {
                    $count_select = count($formField['elements']);
                    unset($formField);
                    continue;
                } else {
                    $options = array();
                    $formFieldItem = array();
                    $count_select = count($formField['elements']);
                    switch ($list_type) {
                        case 'list':
                        case 'select':
                            $formField['type'] = "div";
                            $formField['class'] = "ui-dform-selectbox";
                            $formFieldItem[$count_select]['type'] = 'select';
                            $formFieldItem[$count_select]['name'] = 'tx_multishop_pi1[categories][]';
                            $formFieldItem[$count_select]['id'] = 'msFrontUltrasearchFormFieldCategoriesItem';
                            $formFieldItem[$count_select]['options'][0] = 'kies categories';
                            break;
                        case 'multiselect':
                        case 'list_multiple':
                        case 'select_multiple':
                            $formField['type'] = "div";
                            $formField['class'] = "ui-dform-selectbox-multiple";
                            $formFieldItem[$count_select]['type'] = 'select';
                            $formFieldItem[$count_select]['name'] = 'tx_multishop_pi1[categories][]';
                            $formFieldItem[$count_select]['id'] = 'msFrontUltrasearchFormFieldCategoriesItem';
                            $formFieldItem[$count_select]['multiple'] = 'multiple';
                            break;
                        case 'radio':
                            $formField['type'] = "div";
                            $formField['class'] = "ui-dform-radiobuttons";
                            break;
                        case 'checkbox':
                        default:
                            $formField['type'] = "div";
                            $formField['class'] = "ui-dform-checkboxes";
                            break;
                    }
                    $counter = 0;
                    if (is_array($categories) && count($categories)) {
                        foreach ($categories as $row) {
                            // count available records
                            $tmpFilter = $totalCountFilter;
                            $totalCountSubFilterTmp = $totalCountSubFilter;
                            unset($totalCountSubFilterTmp['categories']);
                            if (is_array($totalCountSubFilterTmp['options']) and count($totalCountSubFilterTmp['options'])) {
                                foreach ($totalCountSubFilterTmp['options'] as $key => $items) {
                                    foreach ($items as $item) {
                                        $tmpFilter[] = $item;
                                    }
                                }
                                unset($totalCountSubFilterTmp['options']);
                            }
                            if (is_array($totalCountSubFilterTmp) and count($totalCountSubFilterTmp)) {
                                foreach ($totalCountSubFilterTmp as $key => $items) {
                                    foreach ($items as $item) {
                                        $tmpFilter[] = $item;
                                    }
                                }
                            }
                            if ($this->ms['MODULES']['FLAT_DATABASE']) {
                                $string = '(';
                                for ($i = 0; $i < 4; $i++) {
                                    if ($i > 0) {
                                        $string .= " or ";
                                    }
                                    $string .= "pf.categories_id_" . $i . " IN (" . $row['categories_id'] . ")";
                                }
                                $string .= ')';
                                if ($string) {
                                    $tmpFilter[] = $string;
                                }
                            } else {
                                /*
								//$tmpFilter[]="p2c.categories_id = ".$row['categories_id'];
								$subcats_id = mslib_fe::get_subcategory_ids($row['categories_id']);
								if (count($subcats_id)) {
									$tmpFilter[]="p2c.is_deepest=1 AND p2c.categories_id IN (".$row['categories_id'].", ".implode(',', $subcats_id).")";
								} else {
									$tmpFilter[]="p2c.is_deepest=1 AND p2c.categories_id = ".$row['categories_id'];
								}
								*/
                                $subcats_id = mslib_fe::get_subcategory_ids($row['categories_id']);
                                $subcats_id[] = $row['categories_id'];
                                $tmpFilter[] = "p2c.node_id IN (" . implode(',', $subcats_id) . ")";
                            }
                            $totalCountFromFlat = array();
                            $totalCountWhereFlat = array();
                            if (is_array($totalCountFrom['options'])) {
                                $totalCountFromFlat = array_values($totalCountFrom['options']);
                            }
                            if (is_array($totalCountWhere['options'])) {
                                $totalCountWhereFlat = array_values($totalCountWhere['options']);
                            }
                            if (!$this->ms['MODULES']['FLAT_DATABASE']) {
                                $prefix = 'p';
                            } else {
                                $prefix = 'pf';
                            }
                            // GET PRODUCT COUNT FOR CATEGORY FORMITEM
                            //$this->msDebug=1;
                            $totalCount = mslib_fe::getProductsPageSet($tmpFilter, 0, 0, array(), array(), $select, $totalCountWhereFlat, 0, $totalCountFromFlat, array(), 'counter', 'count(DISTINCT(' . $prefix . '.products_id)) as total', 1);
                            //error_log($this->msDebugInfo);
                            //echo $this->msDebugInfo;
                            //die();
                            // count available records eof
                            if (!$totalCount && $this->get['ultrasearch_exclude_negative_filter_values']) {
                                unset($formFieldItem[$counter]);
                                continue;
                            }
                            switch ($list_type) {
                                case 'list':
                                case 'select':
                                case 'multiselect':
                                case 'list_multiple':
                                case 'select_multiple':
                                    //if ($totalCount > 0) {
                                    if (is_array($this->post['tx_multishop_pi1']['categories']) and in_array($row['categories_id'], $this->post['tx_multishop_pi1']['categories'])) {
                                        $formFieldItem[$count_select]['options'][$row['categories_id']]['selected'] = 'selected';
                                        $formFieldItem[$count_select]['options'][$row['categories_id']]['html'] = $row['categories_name'] . ' (' . number_format($totalCount, 0, '', '.') . ')';
                                    } else {
                                        $formFieldItem[$count_select]['options'][$row['categories_id']] = $row['categories_name'] . ' (' . number_format($totalCount, 0, '', '.') . ')';
                                    }
                                    //}
                                    break;
                                case 'radio':
                                    $formFieldItem[$counter]['type'] = 'div';
                                    $formFieldItem[$counter]['class'] = 'ui-dform-radiobuttons-wrapper';
                                    if (!$totalCount) {
                                        $formFieldItem[$counter]['class'] .= ' zero_results';
                                    }
                                    $row['categories_name'] = '<span class="title">' . $row['categories_name'] . '</span><span class="spanResults">(' . number_format($totalCount, 0, '', '.') . ')</span>';
                                    if (is_array($this->post['tx_multishop_pi1']['categories']) and in_array($row['categories_id'], $this->post['tx_multishop_pi1']['categories'])) {
                                        $formFieldItem[$counter]['elements']['checked'] = "checked";
                                    }
                                    $formFieldItem[$counter]['elements']['name'] = "tx_multishop_pi1[categories][]";
                                    $formFieldItem[$counter]['elements']['id'] = "msFrontUltrasearchFormFieldCategoriesItem" . $key . "Radiobutton" . $row['categories_id'];
                                    $formFieldItem[$counter]['elements']['caption'] = $row['categories_name'];
                                    $formFieldItem[$counter]['elements']['value'] = $row['categories_id'];
                                    $formFieldItem[$counter]['elements']['type'] = 'radio';
                                    $formFieldItem[$counter]['elements']['class'] = 'ui-dform-radiobutton';
                                    break;
                                case 'checkbox':
                                default:
                                    $formFieldItem[$counter]['type'] = 'div';
                                    $formFieldItem[$counter]['class'] = 'ui-dform-checkboxes-wrapper';
                                    if (!$totalCount) {
                                        $formFieldItem[$counter]['class'] .= ' zero_results';
                                    }
                                    //
                                    $formFieldItem[$counter]['elements'][0]['type'] = 'div';
                                    $formFieldItem[$counter]['elements'][0]['class'] = 'checkbox checkbox-success checkbox-inline';
                                    //
                                    $row['categories_name'] = '<span class="title">' . $row['categories_name'] . '</span><span class="spanResults">(' . number_format($totalCount, 0, '', '.') . ')</span>';
                                    if (is_array($this->post['tx_multishop_pi1']['categories']) and in_array($row['categories_id'], $this->post['tx_multishop_pi1']['categories'])) {
                                        $formFieldItem[$counter]['elements'][0]['elements']['checked'] = "checked";
                                    }
                                    $formFieldItem[$counter]['elements'][0]['elements']['name'] = "tx_multishop_pi1[categories][]";
                                    $formFieldItem[$counter]['elements'][0]['elements']['id'] = "msFrontUltrasearchFormFieldCategoriesItem" . $key . "Checkbox" . $row['categories_id'];
                                    $formFieldItem[$counter]['elements'][0]['elements']['caption'] = $row['categories_name'];
                                    $formFieldItem[$counter]['elements'][0]['elements']['value'] = $row['categories_id'];
                                    $formFieldItem[$counter]['elements'][0]['elements']['type'] = 'checkbox';
                                    $formFieldItem[$counter]['elements'][0]['elements']['class'] = 'ui-dform-checkbox';
                                    break;
                            }
                            $counter++;
                        }
                        if (!count($formFieldItem)) {
                            unset($formField);
                        } else {
                            $formField['elements'] = $formFieldItem;
                        }
                    }
                }
                if (!count($formField['elements'])) {
                    unset($formField);
                }
                break;
            case 'manufacturers':
                $formField['caption'] = $this->pi_getLL('manufacturers');
                if ($this->filterCategoriesFormByCategoriesIdGetParam) {
                    if ($this->ms['MODULES']['FLAT_DATABASE']) {
                        $str = "SELECT manufacturers_id from tx_multishop_products_flat where (";
                        $tmpfilter = array();
                        for ($i = 0; $i < 6; $i++) {
                            $tmpfilter[] = "categories_id_" . $i . "='" . addslashes($this->post['categories_id']) . "'";
                        }
                        $str .= implode(" or ", $tmpfilter) . ")";
                        $res = $GLOBALS['TYPO3_DB']->sql_query($str);
                        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
                            $default_query = 0;
                            $ids = array();
                            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                                $ids[] = $row['manufacturers_id'];
                            }
                            $str = "SELECT * from tx_multishop_manufacturers m where manufacturers_id IN (" . addslashes(implode(",", $ids)) . ") order by sort_order,manufacturers_name ";
                        } else {
                            $default_query = 1;
                        }
                    } else {
                        if ($parent_id > 0) {
                            /*
							$man_get_subscat = array();
							$man_catsubs_id_data = array();
							$man_get_subscat = mslib_fe::get_subcategory_ids($parent_id);
							$man_catsubs_id_data[] = $parent_id;
							if (count($man_get_subscat)) {
								foreach ($man_get_subscat as $man_subcat_id_data) {
									$man_catsubs_id_data[] = $man_subcat_id_data;
								}
							}
							$str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.is_deepest=1 AND p2c.categories_id IN (".implode(',', $man_catsubs_id_data).") group by m.manufacturers_id";
							*/
                            if (is_array($subcats_data) && count($subcats_data)) {
                                $str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.node_id in (" . implode(',', $subcats_data) . ") group by m.manufacturers_id";
                            } else {
                                $str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.node_id = " . addslashes($parent_id) . " group by m.manufacturers_id";
                            }
                        } else {
                            $default_query = 1;
                        }
                    }
                } else {
                    if (isset($this->post['tx_multishop_pi1']['categories']) && count($this->post['tx_multishop_pi1']['categories'])) {
                        $main_get_subscat = array();
                        foreach ($this->post['tx_multishop_pi1']['categories'] as $post_main_catid) {
                            $tmp_man_get_subscat = mslib_fe::get_subcategory_ids($post_main_catid);
                            foreach ($tmp_man_get_subscat as $tmp_subs_catid) {
                                $main_get_subscat[] = $tmp_subs_catid;
                            }
                        }
                        if (count($main_get_subscat)) {
                            $str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.node_id in (" . implode(',', $main_get_subscat) . ") group by m.manufacturers_id";
                        } else {
                            $str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.node_id in (" . implode(',', $this->post['tx_multishop_pi1']['categories']) . ") group by m.manufacturers_id";
                        }
                    } else if (isset($this->get['categories_id']) && $this->get['categories_id'] > 0) {
                        $main_get_subscat = mslib_fe::get_subcategory_ids($this->get['categories_id']);
                        if (count($main_get_subscat)) {
                            $str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.node_id in (" . implode(',', $main_get_subscat) . ") group by m.manufacturers_id";
                        } else {
                            $str = "select * from tx_multishop_manufacturers m, tx_multishop_products p, tx_multishop_products_to_categories p2c where p.manufacturers_id = m.manufacturers_id and p.products_id = p2c.products_id and p2c.node_id in (" . implode(',', $this->get['categories_id']) . ") group by m.manufacturers_id";
                        }
                    } else {
                        $default_query = 1;
                    }
                }
                if ($default_query) {
                    $str = "SELECT * from tx_multishop_manufacturers m order by sort_order,manufacturers_name ";
                }
                //$str="SELECT * from tx_multishop_manufacturers m order by sort_order,manufacturers_name ";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                $options = array();
                $formFieldItem = array();
                $count_select = count($formField['elements']);
                switch ($list_type) {
                    case 'list':
                    case 'select':
                        $formField['type'] = "div";
                        $formField['class'] = "ui-dform-selectbox";
                        $formFieldItem[$count_select]['type'] = 'select';
                        $formFieldItem[$count_select]['name'] = 'tx_multishop_pi1[manufacturers][]';
                        $formFieldItem[$count_select]['id'] = 'msFrontUltrasearchFormFieldManufacturersItem';
                        $formFieldItem[$count_select]['options'][0] = $this->pi_getLL('choose_manufacturers');
                        break;
                    case 'multiselect':
                    case 'list_multiple':
                    case 'select_multiple':
                        $formField['type'] = "div";
                        $formField['class'] = "ui-dform-selectbox-multiple";
                        $formFieldItem[$count_select]['type'] = 'select';
                        $formFieldItem[$count_select]['name'] = 'tx_multishop_pi1[manufacturers][]';
                        $formFieldItem[$count_select]['id'] = 'msFrontUltrasearchFormFieldManufacturersItem';
                        $formFieldItem[$count_select]['multiple'] = 'multiple';
                        break;
                    case 'radio':
                        $formField['type'] = "div";
                        $formField['class'] = "ui-dform-radiobuttons";
                        break;
                    case 'checkbox':
                    default:
                        $formField['type'] = "div";
                        $formField['class'] = "ui-dform-checkboxes";
                        break;
                }
                $counter = 0;
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                    // count available records
                    $tmpFilter = $totalCountFilter;
                    $totalCountSubFilterTmp = $totalCountSubFilter;
                    unset($totalCountSubFilterTmp['manufacturers']);
                    $from = array();
                    if (is_array($totalCountSubFilterTmp['options']) and count($totalCountSubFilterTmp['options'])) {
                        foreach ($totalCountSubFilterTmp['options'] as $key => $items) {
                            foreach ($items as $item) {
                                $tmpFilter[] = $item;
                            }
                        }
                        unset($totalCountSubFilterTmp['options']);
                    }
                    if (is_array($totalCountSubFilterTmp) and count($totalCountSubFilterTmp)) {
                        foreach ($totalCountSubFilterTmp as $key => $items) {
                            foreach ($items as $item) {
                                $tmpFilter[] = $item;
                            }
                        }
                    }
                    if ($this->ms['MODULES']['FLAT_DATABASE']) {
                        $prefix = 'pf';
                    } else {
                        $prefix = 'p';
                    }
                    $tmpFilter[] = $prefix . ".manufacturers_id IN (" . $row['manufacturers_id'] . ")";
                    $totalCountFromFlat = array();
                    $totalCountWhereFlat = array();
                    if (is_array($totalCountFrom['options'])) {
                        $totalCountFromFlat = array_values($totalCountFrom['options']);
                    }
                    if (is_array($totalCountWhere['options'])) {
                        $totalCountWhereFlat = array_values($totalCountWhere['options']);
                    }
                    if (!$this->ms['MODULES']['FLAT_DATABASE']) {
                        $prefix = 'p';
                    } else {
                        $prefix = 'pf';
                    }
                    //if (!$this->ms['MODULES']['FLAT_DATABASE'] && $this->filterCategoriesFormByCategoriesIdGetParam && $parent_id>0 && !$this->post['tx_multishop_pi1']['categories']) {
                    if (!$this->ms['MODULES']['FLAT_DATABASE']) {
                        if ($parent_id > 0) {
                            if (is_array($subcats_data) && count($subcats_data)) {
                                $tmpFilter[] = "p2c.node_id in (" . implode(',', $subcats_data) . ")";
                            } else {
                                $tmpFilter[] = "p2c.node_id=" . addslashes($parent_id);
                            }
                        } else {
                            if (isset($this->post['tx_multishop_pi1']['categories']) && count($this->post['tx_multishop_pi1']['categories'])) {
                                $main_get_subscat = array();
                                foreach ($this->post['tx_multishop_pi1']['categories'] as $post_main_catid) {
                                    $tmp_man_get_subscat = mslib_fe::get_subcategory_ids($post_main_catid);
                                    foreach ($tmp_man_get_subscat as $tmp_subs_catid) {
                                        $main_get_subscat[] = $tmp_subs_catid;
                                    }
                                }
                                if (count($main_get_subscat)) {
                                    $tmpFilter[] = "p2c.node_id in (" . implode(',', $main_get_subscat) . ")";
                                } else {
                                    $tmpFilter[] = "p2c.node_id in (" . implode(',', $this->post['tx_multishop_pi1']['categories']) . ")";
                                }
                            } else if (isset($this->get['categories_id']) && $this->get['categories_id'] > 0) {
                                $main_get_subscat = mslib_fe::get_subcategory_ids($this->get['categories_id']);
                                //$man_catsubs_id_data[] = $this->get['categories_id'];
                                if (count($main_get_subscat)) {
                                    $tmpFilter[] = "p2c.node_id in (" . implode(',', $main_get_subscat) . ")";
                                } else {
                                    $tmpFilter[] = "p2c.node_id in (" . $this->get['categories_id'] . ")";
                                }
                            }
                        }
                    }
                    //$this->msDebug=1;
                    $totalCount = mslib_fe::getProductsPageSet($tmpFilter, 0, 0, array(), array(), $select, $totalCountWhereFlat, 0, $totalCountFromFlat, array(), 'counter', 'count(DISTINCT(' . $prefix . '.products_id)) as total', 1);
                    //echo $this->msDebugInfo;
                    // count available records eof
                    if (!$totalCount && $this->get['ultrasearch_exclude_negative_filter_values']) {
                        unset($formFieldItem[$counter]);
                        continue;
                    }
                    switch ($list_type) {
                        case 'list':
                        case 'select':
                        case 'multiselect':
                        case 'list_multiple':
                        case 'select_multiple':
                            //if ($totalCount > 0) {
                            if (is_array($this->post['tx_multishop_pi1']['manufacturers']) and in_array($row['manufacturers_id'], $this->post['tx_multishop_pi1']['manufacturers'])) {
                                $formFieldItem[$count_select]['options'][$row['manufacturers_id']]['selected'] = 'selected';
                                $formFieldItem[$count_select]['options'][$row['manufacturers_id']]['html'] = $row['manufacturers_name'] . ' (' . number_format($totalCount, 0, '', '.') . ')';
                            } else {
                                $formFieldItem[$count_select]['options'][$row['manufacturers_id']] = $row['manufacturers_name'] . ' (' . number_format($totalCount, 0, '', '.') . ')';
                            }
                            //}
                            //$formFieldItem[$count]['elements']['options'][$counter]['html'] = $row['categories_name'] . ' ('.number_format($totalCount,0,'','.').')';
                            //$formFieldItem[$count]['options'][$counter]['elements']['caption'] = $row['categories_name'] . ' ('.number_format($totalCount,0,'','.').')';
                            break;
                        case 'radio':
                            $formFieldItem[$counter]['type'] = 'div';
                            $formFieldItem[$counter]['class'] = 'ui-dform-radiobuttons-wrapper';
                            if (!$totalCount) {
                                $formFieldItem[$counter]['class'] .= ' zero_results';
                            }
                            $row['manufacturers_name'] = '<span class="title">' . $row['manufacturers_name'] . '</span><span class="spanResults">(' . number_format($totalCount, 0, '', '.') . ')</span>';
                            if (is_array($this->post['tx_multishop_pi1']['manufacturers']) and in_array($row['manufacturers_id'], $this->post['tx_multishop_pi1']['manufacturers'])) {
                                $formFieldItem[$counter]['elements']['checked'] = "checked";
                            }
                            $formFieldItem[$counter]['elements']['name'] = "tx_multishop_pi1[manufacturers][]";
                            $formFieldItem[$counter]['elements']['id'] = "msFrontUltrasearchFormFieldManufacturersItem" . $key . "Radiobutton" . $row['manufacturers_id'];
                            $formFieldItem[$counter]['elements']['caption'] = $row['manufacturers_name'];
                            $formFieldItem[$counter]['elements']['value'] = $row['manufacturers_id'];
                            $formFieldItem[$counter]['elements']['type'] = 'radio';
                            $formFieldItem[$counter]['elements']['class'] = 'ui-dform-radiobutton';
                            break;
                        case 'checkbox':
                        default:
                            $formFieldItem[$counter]['type'] = 'div';
                            $formFieldItem[$counter]['class'] = 'ui-dform-checkboxes-wrapper';
                            if (!$totalCount) {
                                $formFieldItem[$counter]['class'] .= ' zero_results';
                            }
                            //
                            $formFieldItem[$counter]['elements'][0]['type'] = 'div';
                            $formFieldItem[$counter]['elements'][0]['class'] = 'checkbox checkbox-success checkbox-inline';
                            //
                            $row['manufacturers_name'] = '<span class="title">' . $row['manufacturers_name'] . '</span><span class="spanResults">(' . number_format($totalCount, 0, '', '.') . ')</span>';
                            if (is_array($this->post['tx_multishop_pi1']['manufacturers']) and in_array($row['manufacturers_id'], $this->post['tx_multishop_pi1']['manufacturers'])) {
                                $formFieldItem[$counter]['elements'][0]['elements']['checked'] = "checked";
                            }
                            $formFieldItem[$counter]['elements'][0]['elements']['name'] = "tx_multishop_pi1[manufacturers][]";
                            $formFieldItem[$counter]['elements'][0]['elements']['id'] = "msFrontUltrasearchFormFieldManufacturersItem" . $key . "Checkbox" . $row['manufacturers_id'];
                            $formFieldItem[$counter]['elements'][0]['elements']['caption'] = $row['manufacturers_name'];
                            $formFieldItem[$counter]['elements'][0]['elements']['value'] = $row['manufacturers_id'];
                            $formFieldItem[$counter]['elements'][0]['elements']['type'] = 'checkbox';
                            $formFieldItem[$counter]['elements'][0]['elements']['class'] = 'ui-dform-checkbox';
                            break;
                    }
                    $counter++;
                }
                if (!count($formFieldItem)) {
                    unset($formField);
                } else {
                    $formField['elements'] = $formFieldItem;
                }
                if (!count($formField['elements'])) {
                    unset($formField);
                }
                break;
            case 'productslisting_filter':
                $formField['caption'] = $this->pi_getLL('products_per_page', 'Products per page:');
                $options = array();
                $formFieldItem = array();
                $default_limit_page = $this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'];
                $count_select = count($formField['elements']);
                $formField['type'] = "div";
                $formField['class'] = "ui-dform-selectbox";
                $formFieldItem[$count_select]['type'] = 'select';
                $formFieldItem[$count_select]['name'] = 'tx_multishop_pi1[limitsb]';
                $formFieldItem[$count_select]['id'] = 'msFrontUltrasearchFormFieldListingLimitItem';
                $limit_options = array();
                $limit_options[] = 5;
                $limit_options[] = 10;
                $limit_options[] = 20;
                $limit_options[] = 30;
                $limit_options[] = 50;
                $limit_options[] = 100;
                if (!in_array($default_limit_page, $limit_options)) {
                    $formFieldItem[$count_select]['options'][$default_limit_page] = $default_limit_page;
                }
                foreach ($limit_options as $limit_option) {
                    if (isset($this->cookie['limitsb']) && $limit_option == $this->cookie['limitsb']) {
                        $formFieldItem[$count_select]['options'][$limit_option]['selected'] = 'selected';
                        $formFieldItem[$count_select]['options'][$limit_option]['html'] = $limit_option;
                    } else {
                        if ($limit_option == $default_limit_page) {
                            $formFieldItem[$count_select]['options'][$limit_option]['selected'] = 'selected';
                            $formFieldItem[$count_select]['options'][$limit_option]['html'] = $limit_option;
                        } else {
                            $formFieldItem[$count_select]['options'][$limit_option]['html'] = $limit_option;
                        }
                    }
                }
                if (!count($formFieldItem)) {
                    unset($formField);
                } else {
                    $formField['elements'] = $formFieldItem;
                }
                break;
            case 'sortby_filter':
                $array = explode(":", $field);
                if ($array[1]) {
                    $sortByTypes = explode(',', $array[1]);
                }
                if (!is_array($sortByTypes)) {
                    $sortByTypes = array();
                }
                $formField['caption'] = $this->pi_getLL('sort_by', 'Sort by:');
                $options = array();
                $formFieldItem = array();
                $count_select = count($formField['elements']);
                $formField['type'] = "div";
                $formField['class'] = "ui-dform-selectbox";
                $formFieldItem[$count_select]['type'] = 'select';
                $formFieldItem[$count_select]['name'] = 'tx_multishop_pi1[sortbysb]';
                $formFieldItem[$count_select]['id'] = 'msFrontUltrasearchFormFieldSortByItem';
                $formFieldItem[$count_select]['options'][0] = $this->pi_getLL('default');
                $sortby_options = array();
                // TypoScript example hiding the manufacturers filters:
                // lib.msUltrasearchForm.ultrasearch_fields=input_keywords;submit;sortby_filter:-manufacturers;categories:checkbox;
                if (!in_array('-manufacturers', $sortByTypes)) {
                    $sortby_options['manufacturers_asc'] = $this->pi_getLL('sortby_options_label_manufacturers_asc', 'Manufacturers (asc)');
                    $sortby_options['manufacturers_desc'] = $this->pi_getLL('sortby_options_label_manufacturers_desc', 'Manufacturers (desc)');
                }
                if (!in_array('-popular', $sortByTypes)) {
                    $sortby_options['best_selling_asc'] = $this->pi_getLL('sortby_options_label_bestselling_asc', 'Best selling (asc)');
                    $sortby_options['best_selling_desc'] = $this->pi_getLL('sortby_options_label_bestselling_desc', 'Best selling (desc)');
                }
                if (!in_array('-price', $sortByTypes)) {
                    $sortby_options['price_asc'] = $this->pi_getLL('sortby_options_label_price_asc', 'Price (asc)');
                    $sortby_options['price_desc'] = $this->pi_getLL('sortby_options_label_price_desc', 'Price (desc)');
                }
                if (!in_array('-new', $sortByTypes)) {
                    $sortby_options['new_asc'] = $this->pi_getLL('sortby_options_label_new_asc', 'New (asc)');
                    $sortby_options['new_desc'] = $this->pi_getLL('sortby_options_label_new_desc', 'New (desc)');
                }
                // custom hook that can be controlled by third-party plugin
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchSortByFilterTypes'])) {
                    $params = array(
                            'sortby_options' => &$sortby_options,
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchSortByFilterTypes'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                if (count($sortby_options)) {
                    foreach ($sortby_options as $sortby_key => $sortby_label) {
                        if (isset($this->cookie['sortbysb']) && $sortby_key == $this->cookie['sortbysb']) {
                            $formFieldItem[$count_select]['options'][$sortby_key]['selected'] = 'selected';
                            $formFieldItem[$count_select]['options'][$sortby_key]['html'] = $sortby_label;
                        } else {
                            $formFieldItem[$count_select]['options'][$sortby_key]['html'] = $sortby_label;
                        }
                    }
                }
                if (!count($formFieldItem)) {
                    unset($formField);
                } else {
                    $formField['elements'] = $formFieldItem;
                }
                break;
            case 'sort_filter':
            case 'price_filter':
                $formField['name'] = $key;
                $formField['id'] = $key;
                $formField['caption'] = $key;
                $formField['type'] = "text";
                $formField['placeholder'] = "";
                break;
            default:
                $count_attributes = count($formField['elements']);
                // attributes
                $array = explode(":", $field);
                if (is_numeric($array[0])) {
                    if (strstr($array[1], '{asc}')) {
                        $order_column = 'pov.products_options_values_name';
                        $order_by = 'asc';
                        $array[1] = str_replace('{asc}', '', $array[1]);
                    } else if (strstr($array[1], '{desc}')) {
                        $order_column = 'pov.products_options_values_name';
                        $order_by = 'desc';
                        $array[1] = str_replace('{desc}', '', $array[1]);
                    } else {
                        $order_column = 'povp.sort_order';
                        $order_by = 'asc';
                    }
                    $option_id = $array[0];
                    $list_type = $array[1];
                    $query = $GLOBALS['TYPO3_DB']->SELECTquery('*',         // SELECT ...
                            'tx_multishop_products_options',  // FROM ...
                            'products_options_id=\'' . $option_id . '\' and language_id=\'' . $this->sys_language_uid . '\'',    // WHERE.
                            '',            // GROUP BY...
                            '',    // ORDER BY...
                            ''            // LIMIT ...
                    );
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    //if tx_multishop_products_options is not empty/category options is not empty
                    if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
                        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                        $i = 0;
                        if (!$list_type) {
                            $list_type = 'list';
                        }
                        $order_column = 'sorting, pov.products_options_values_name';
                        if (!$order_by) {
                            $order_by = 'asc';
                        }
                        $formFieldItem = array();
                        switch ($list_type) {
                            case 'list':
                            case 'select':
                                $formField['type'] = "div";
                                $formField['class'] = "ui-dform-selectbox";
                                $formFieldItem[$count_attributes]['type'] = 'select';
                                $formFieldItem[$count_attributes]['name'] = "tx_multishop_pi1[options][" . $row['products_options_id'] . "][]";
                                $formFieldItem[$count_attributes]['id'] = 'msFrontUltrasearchFormFieldItemOption' . $option_id;
                                $formFieldItem[$count_attributes]['options'][0] = $this->pi_getLL('choose') . ' ' . $row['products_options_name'];
                                break;
                            case 'multiselect':
                            case 'list_multiple':
                            case 'select_multiple':
                                $formField['type'] = "div";
                                $formField['class'] = "ui-dform-selectbox-multiple";
                                $formFieldItem[$count_attributes]['type'] = 'select';
                                $formFieldItem[$count_attributes]['name'] = "tx_multishop_pi1[options][" . $row['products_options_id'] . "][]";
                                $formFieldItem[$count_attributes]['id'] = 'msFrontUltrasearchFormFieldItemOption' . $option_id;
                                $formFieldItem[$count_attributes]['multiple'] = 'multiple';
                                break;
                            case 'radio':
                                $formField['type'] = "div";
                                $formField['class'] = "ui-dform-radiobuttons";
                                break;
                            case 'checkbox':
                            default:
                                $formField['type'] = "div";
                                $formField['class'] = "ui-dform-checkboxes";
                                break;
                        }
                        if ($array[2]) {
                            // overriding title
                            $formField['caption'] = $array[2];
                        } else {
                            $formField['caption'] = $row['products_options_name'];
                        }
                        if ($this->filterCategoriesFormByCategoriesIdGetParam && $parent_id) {
                            /*
							$man_get_subscat = array();
							$man_catsubs_id_data = array();
							$man_get_subscat = mslib_fe::get_subcategory_ids($parent_id);
							$man_catsubs_id_data[] = $parent_id;
							if (count($man_get_subscat)) {
								foreach ($man_get_subscat as $man_subcat_id_data) {
									$man_catsubs_id_data[] = $man_subcat_id_data;
								}
							}
							$query_opt_2_values = $GLOBALS['TYPO3_DB']->SELECTquery(
								'DISTINCT(pov.products_options_values_id), CONVERT(SUBSTRING(pov.products_options_values_name, LOCATE(\'-\', pov.products_options_values_name) + 1), SIGNED INTEGER) as sorting, pov.products_options_values_name',         // SELECT ...
								'tx_multishop_products_options_values pov, tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_attributes pa, tx_multishop_products p, tx_multishop_products_to_categories p2c',     // FROM ...
								"pov.language_id='".$this->sys_language_uid."' and povp.products_options_id = " . $row['products_options_id']." and pa.options_id='".$row['products_options_id']."' and pa.options_values_id=pov.products_options_values_id and pa.products_id=p.products_id and p.page_uid='".$this->showCatalogFromPage."' and pov.products_options_values_id=povp.products_options_values_id and p.products_id = p2c.products_id and p2c.is_deepest=1 AND p2c.categories_id IN (".implode(',', $man_catsubs_id_data).")",    // WHERE.
								'',            // GROUP BY...
								$order_column." ".$order_by,    // ORDER BY...
								''            // LIMIT ...
							);
							*/
                            if (is_array($subcats_data) && count($subcats_data)) {
                                $query_opt_2_values = $GLOBALS['TYPO3_DB']->SELECTquery('DISTINCT(pov.products_options_values_id), CONVERT(SUBSTRING(pov.products_options_values_name, LOCATE(\'-\', pov.products_options_values_name) + 1), SIGNED INTEGER) as sorting, pov.products_options_values_name',         // SELECT ...
                                        'tx_multishop_products_options_values pov, tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_attributes pa, tx_multishop_products p, tx_multishop_products_to_categories p2c',     // FROM ...
                                        "pov.language_id='" . $this->sys_language_uid . "' and povp.products_options_id = " . $row['products_options_id'] . " and pa.options_id='" . $row['products_options_id'] . "' and pa.options_values_id=pov.products_options_values_id and pa.products_id=p.products_id and p.page_uid='" . $this->showCatalogFromPage . "' and pov.products_options_values_id=povp.products_options_values_id and p.products_id = p2c.products_id AND p2c.node_id in (" . implode(',', $subcats_data) . ")" . (is_array($this->post['tx_multishop_pi1']['manufacturers']) && count($this->post['tx_multishop_pi1']['manufacturers']) ? ' and p.manufacturers_id in (' . implode(',', $this->post['tx_multishop_pi1']['manufacturers']) . ')' : ''),    // WHERE.
                                        '',            // GROUP BY...
                                        $order_column . " " . $order_by,    // ORDER BY...
                                        ''            // LIMIT ...
                                );
                            } else {
                                $query_opt_2_values = $GLOBALS['TYPO3_DB']->SELECTquery('DISTINCT(pov.products_options_values_id), CONVERT(SUBSTRING(pov.products_options_values_name, LOCATE(\'-\', pov.products_options_values_name) + 1), SIGNED INTEGER) as sorting, pov.products_options_values_name',         // SELECT ...
                                        'tx_multishop_products_options_values pov, tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_attributes pa, tx_multishop_products p, tx_multishop_products_to_categories p2c',     // FROM ...
                                        "pov.language_id='" . $this->sys_language_uid . "' and povp.products_options_id = " . $row['products_options_id'] . " and pa.options_id='" . $row['products_options_id'] . "' and pa.options_values_id=pov.products_options_values_id and pa.products_id=p.products_id and p.page_uid='" . $this->showCatalogFromPage . "' and pov.products_options_values_id=povp.products_options_values_id and p.products_id = p2c.products_id AND p2c.node_id =" . addslashes($parent_id) . (is_array($this->post['tx_multishop_pi1']['manufacturers']) && count($this->post['tx_multishop_pi1']['manufacturers']) ? ' and p.manufacturers_id in (' . implode(',', $this->post['tx_multishop_pi1']['manufacturers']) . ')' : ''),    // WHERE.
                                        '',            // GROUP BY...
                                        $order_column . " " . $order_by,    // ORDER BY...
                                        ''            // LIMIT ...
                                );
                            }
                        } else {
                            $main_get_subscat = array();
                            if (isset($this->post['tx_multishop_pi1']['categories']) && count($this->post['tx_multishop_pi1']['categories'])) {
                                foreach ($this->post['tx_multishop_pi1']['categories'] as $post_main_catid) {
                                    $tmp_man_get_subscat = mslib_fe::get_subcategory_ids($post_main_catid);
                                    foreach ($tmp_man_get_subscat as $tmp_subs_catid) {
                                        $main_get_subscat[] = $tmp_subs_catid;
                                    }
                                }
                                if (!count($main_get_subscat)) {
                                    $main_get_subscat = implode(',', $this->post['tx_multishop_pi1']['categories']);
                                }
                            } else if (isset($this->get['categories_id']) && $this->get['categories_id'] > 0) {
                                $main_get_subscat = mslib_fe::get_subcategory_ids($this->get['categories_id']);
                                //$man_catsubs_id_data[] = $this->get['categories_id'];
                                if (!count($main_get_subscat)) {
                                    $main_get_subscat[] = $this->get['categories_id'];
                                }
                            }
                            if (count($main_get_subscat)) {
                                $query_opt_2_values = $GLOBALS['TYPO3_DB']->SELECTquery('DISTINCT(pov.products_options_values_id), CONVERT(SUBSTRING(pov.products_options_values_name, LOCATE(\'-\', pov.products_options_values_name) + 1), SIGNED INTEGER) as sorting, pov.products_options_values_name',         // SELECT ...
                                        'tx_multishop_products_options_values pov, tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_attributes pa, tx_multishop_products p, tx_multishop_products_to_categories p2c',     // FROM ...
                                        "pov.language_id='" . $this->sys_language_uid . "' and povp.products_options_id = " . $row['products_options_id'] . " and pa.options_id='" . $row['products_options_id'] . "' and pa.options_values_id=pov.products_options_values_id and pa.products_id=p.products_id and p.page_uid='" . $this->showCatalogFromPage . "' and pov.products_options_values_id=povp.products_options_values_id and p.products_id = p2c.products_id AND p2c.node_id in (" . implode(',', $main_get_subscat) . ")" . (is_array($this->post['tx_multishop_pi1']['manufacturers']) && count($this->post['tx_multishop_pi1']['manufacturers']) ? ' and p.manufacturers_id in (' . implode(',', $this->post['tx_multishop_pi1']['manufacturers']) . ')' : ''),    // WHERE.
                                        '',            // GROUP BY...
                                        $order_column . " " . $order_by,    // ORDER BY...
                                        ''            // LIMIT ...
                                );
                            } else {
                                $query_opt_2_values = $GLOBALS['TYPO3_DB']->SELECTquery('DISTINCT(pov.products_options_values_id), CONVERT(SUBSTRING(pov.products_options_values_name, LOCATE(\'-\', pov.products_options_values_name) + 1), SIGNED INTEGER) as sorting, pov.products_options_values_name',         // SELECT ...
                                        'tx_multishop_products_options_values pov, tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_attributes pa, tx_multishop_products p',     // FROM ...
                                        "pov.language_id='" . $this->sys_language_uid . "' and povp.products_options_id = " . $row['products_options_id'] . " and pa.options_id='" . $row['products_options_id'] . "' and pa.options_values_id=pov.products_options_values_id and pa.products_id=p.products_id and p.page_uid='" . $this->showCatalogFromPage . "' and pov.products_options_values_id=povp.products_options_values_id" . (is_array($this->post['tx_multishop_pi1']['manufacturers']) && count($this->post['tx_multishop_pi1']['manufacturers']) ? ' and p.manufacturers_id in (' . implode(',', $this->post['tx_multishop_pi1']['manufacturers']) . ')' : ''),    // WHERE.
                                        '',            // GROUP BY...
                                        $order_column . " " . $order_by,    // ORDER BY...
                                        ''            // LIMIT ...
                                );
                            }
                        }
                        $res_opt_2_values = $GLOBALS['TYPO3_DB']->sql_query($query_opt_2_values);
                        if (!$this->ms['MODULES']['FLAT_DATABASE']) {
                            $prefix = 'p';
                        } else {
                            $prefix = 'pf';
                        }
                        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res_opt_2_values) > 0) {
                            $counter = 0;
                            while ($row_opt_2_values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_opt_2_values)) {
                                // count available records
                                $tmpFilter = $totalCountFilter;
                                $totalCountSubFilterTmp = $totalCountSubFilter;
                                unset($totalCountSubFilterTmp['options'][$option_id]);
                                //error_log(print_r($totalCountSubFilter,1));
                                $from = array();
                                if (is_array($totalCountSubFilterTmp['options']) and count($totalCountSubFilterTmp['options'])) {
                                    foreach ($totalCountSubFilterTmp['options'] as $key => $items) {
                                        foreach ($items as $item) {
                                            $tmpFilter[] = $item;
                                        }
                                    }
                                    unset($totalCountSubFilterTmp['options']);
                                }
                                if (is_array($totalCountSubFilterTmp) and count($totalCountSubFilterTmp)) {
                                    foreach ($totalCountSubFilterTmp as $key => $items) {
                                        foreach ($items as $item) {
                                            $tmpFilter[] = $item;
                                        }
                                    }
                                }
                                $tmpFilter[] = "(pa_" . $option_id . ".options_id =" . $option_id . " and pa_" . $option_id . ".options_values_id IN (" . $row_opt_2_values['products_options_values_id'] . "))";
                                //error_log(print_r($tmpFilter,1));
                                $totalCountFromTmp = $totalCountFrom;
                                $totalCountWhereTmp = $totalCountWhere;
                                unset($totalCountFromTmp['options'][$option_id]);
                                unset($totalCountWhereTmp['options'][$option_id]);
                                $totalCountFromTmp['options'][$option_id] = 'tx_multishop_products_attributes pa_' . $option_id;
                                $totalCountWhereTmp['options'][$option_id] = 'pa_' . $option_id . '.products_id=' . $prefix . '.products_id and pa_' . $option_id . '.page_uid=\'' . $this->showCatalogFromPage . '\'';
                                $totalCountFromFlat = array();
                                $totalCountWhereFlat = array();
                                $totalCountFromFlat = array_values($totalCountFromTmp['options']);
                                $totalCountWhereFlat = array_values($totalCountWhereTmp['options']);
                                if (!$this->ms['MODULES']['FLAT_DATABASE'] && $this->filterCategoriesFormByCategoriesIdGetParam && $parent_id > 0 && !$this->post['tx_multishop_pi1']['categories']) {
                                    if (is_array($subcats_data) && count($subcats_data)) {
                                        $tmpFilter[] = "p2c.node_id in (" . implode(',', $subcats_data) . ")";
                                    } else {
                                        $tmpFilter[] = "p2c.node_id=" . addslashes($parent_id);
                                    }
                                }
                                // PRODUCT COUNT FOR ATTRIBUTE OPTION VALUE
                                //$this->msDebug=1;
                                $totalCount = mslib_fe::getProductsPageSet($tmpFilter, 0, 0, array(), array(), $select, $totalCountWhereFlat, 0, $totalCountFromFlat, array(), 'counter', 'count(DISTINCT(' . $prefix . '.products_id)) as total', 1);
                                //error_log(print_r($tmpFilter,1).$this->msDebugInfo);
                                //echo $this->msDebugInfo . "\n\n";
                                //die();
                                // count available records eof
                                if (!$totalCount && $this->get['ultrasearch_exclude_negative_filter_values']) {
                                    unset($formFieldItem[$counter]);
                                    continue;
                                }
                                switch ($list_type) {
                                    case 'list':
                                    case 'select':
                                    case 'multiselect':
                                    case 'list_multiple':
                                    case 'select_multiple':
                                        //if ($totalCount > 0) {
                                        if (is_array($this->post['tx_multishop_pi1']['options'][$option_id]) and in_array($row_opt_2_values['products_options_values_id'], $this->post['tx_multishop_pi1']['options'][$option_id])) {
                                            $formFieldItem[$count_attributes]['options'][$row_opt_2_values['products_options_values_id']]['selected'] = 'selected';
                                            $formFieldItem[$count_attributes]['options'][$row_opt_2_values['products_options_values_id']]['html'] = $row_opt_2_values['products_options_values_name'] . ' (' . number_format($totalCount, 0, '', '.') . ')';
                                        } else {
                                            $formFieldItem[$count_attributes]['options'][$row_opt_2_values['products_options_values_id']] = $row_opt_2_values['products_options_values_name'] . ' (' . number_format($totalCount, 0, '', '.') . ')';
                                        }
                                        //}
                                        //$formFieldItem[$count]['elements']['options'][$counter]['html'] = $row['categories_name'] . ' ('.number_format($totalCount,0,'','.').')';
                                        //$formFieldItem[$count]['options'][$counter]['elements']['caption'] = $row['categories_name'] . ' ('.number_format($totalCount,0,'','.').')';
                                        break;
                                    case 'radio':
                                        $formFieldItem[$counter]['type'] = 'div';
                                        $formFieldItem[$counter]['class'] = 'ui-dform-radiobuttons-wrapper';
                                        if (!$totalCount) {
                                            $formFieldItem[$counter]['class'] .= ' zero_results';
                                        }
                                        $row_opt_2_values['products_options_values_name'] = '<span class="title">' . $row_opt_2_values['products_options_values_name'] . '</span><span class="spanResults">(' . number_format($totalCount, 0, '', '.') . ')</span>';
                                        if (is_array($this->post['tx_multishop_pi1']['options'][$option_id]) and in_array($row_opt_2_values['products_options_values_id'], $this->post['tx_multishop_pi1']['options'][$option_id])) {
                                            $formFieldItem[$counter]['elements']['checked'] = "checked";
                                        }
                                        $formFieldItem[$counter]['elements']['name'] = "tx_multishop_pi1[options][" . $row['products_options_id'] . "][]";
                                        $formFieldItem[$counter]['elements']['id'] = "msFrontUltrasearchFormFieldItemOption" . $key . "Checkbox" . $row_opt_2_values['products_options_values_id'];
                                        $formFieldItem[$counter]['elements']['caption'] = $row_opt_2_values['products_options_values_name'];
                                        $formFieldItem[$counter]['elements']['value'] = $row_opt_2_values['products_options_values_id'];
                                        $formFieldItem[$counter]['elements']['type'] = 'radio';
                                        $formFieldItem[$counter]['elements']['class'] = 'ui-dform-radiobutton';
                                        break;
                                    case 'checkbox':
                                    default:
                                        $formFieldItem[$counter]['type'] = 'div';
                                        $formFieldItem[$counter]['class'] = 'ui-dform-checkboxes-wrapper';
                                        if (!$totalCount) {
                                            $formFieldItem[$counter]['class'] .= ' zero_results';
                                        }
                                        //
                                        $formFieldItem[$counter]['elements'][0]['type'] = 'div';
                                        $formFieldItem[$counter]['elements'][0]['class'] = 'checkbox checkbox-success checkbox-inline';
                                        //
                                        $row_opt_2_values['products_options_values_name'] = '<span class="title">' . $row_opt_2_values['products_options_values_name'] . '</span><span class="spanResults">(' . number_format($totalCount, 0, '', '.') . ')</span>';
                                        if (is_array($this->post['tx_multishop_pi1']['options'][$option_id]) and in_array($row_opt_2_values['products_options_values_id'], $this->post['tx_multishop_pi1']['options'][$option_id])) {
                                            $formFieldItem[$counter]['elements'][0]['elements']['checked'] = "checked";
                                        }
                                        $formFieldItem[$counter]['elements'][0]['elements']['name'] = "tx_multishop_pi1[options][" . $row['products_options_id'] . "][]";
                                        $formFieldItem[$counter]['elements'][0]['elements']['id'] = "msFrontUltrasearchFormFieldItemOption" . $key . "Checkbox" . $row_opt_2_values['products_options_values_id'];
                                        $formFieldItem[$counter]['elements'][0]['elements']['caption'] = $row_opt_2_values['products_options_values_name'];
                                        $formFieldItem[$counter]['elements'][0]['elements']['value'] = $row_opt_2_values['products_options_values_id'];
                                        $formFieldItem[$counter]['elements'][0]['elements']['type'] = 'checkbox';
                                        $formFieldItem[$counter]['elements'][0]['elements']['class'] = 'ui-dform-checkbox';
                                        break;
                                }
                                $counter++;
                            }
                            if (!count($formFieldItem)) {
                                unset($formField);
                            } else {
                                $formField['elements'] = $formFieldItem;
                            }
                        }
                    }
                    if (!count($formField['elements'])) {
                        unset($formField);
                    }
                    //end attributs options
                } else {
                    // custom hook that can be controlled by third-party plugin
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchTypeHook'])) {
                        $params = array(
                                'key' => $key,
                                'field' => $field,
                                'select' => $select,
                                'totalCountFilter' => $totalCountFilter,
                                'totalCountSubFilter' => $totalCountSubFilter,
                                'totalCountFrom' => $totalCountFrom,
                                'totalCountWhere' => $totalCountWhere,
                                'formField' => &$formField,
                        );
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchTypeHook'] as $funcRef) {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                        }
                    }
                }
                break;
        }
        if (isset($key) && !empty($key) && isset($formField)) {
            $formFields[] = array(
                    "type" => "container",
                    "class" => 'ui-dform-container-' . $key,
                    "html" => $formField
            );
        }
    }
    //error_log(print_r($formFields,1));
    $formField = array();
    $formField['name'] = "pageNum";
    $formField['id'] = "pageNum";
    $formField['type'] = "hidden";
    $formField['value'] = $this->post['pageNum'];
    $formFields[] = $formField;
    $this->post['page'] = $this->post['pageNum'];
    // now create webform
    $form = array();
    $form['action'] = 'index.html';
    $form['method'] = 'get';
    $form['html'] = $formFields;
    $data = array();
    $data['formFields'] = $form;
    //echo json_encode($data);
    //exit();
    // product search
    $filter = array();
    $having = array();
    $match = array();
    $orderby = array();
    $where = array();
    $orderby = array();
    $select_total_count = array();
    $select = array();
    if (strlen($this->post['tx_multishop_pi1']['q']) > 2) {
        $array = explode(" ", $this->post['tx_multishop_pi1']['q']);
        $total = count($array);
        $oldsearch = 0;
        if (!$this->ms['MODULES']['ENABLE_FULLTEXT_SEARCH_IN_PRODUCTS_SEARCH']) {
            $oldsearch = 1;
        } else {
            foreach ($array as $item) {
                if (strlen($item) < $this->ms['MODULES']['FULLTEXT_SEARCH_MIN_CHARS']) {
                    $oldsearch = 1;
                    break;
                }
            }
        }
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $tbl = 'pf.';
        } else {
            $tbl = 'pd.';
        }
        if ($oldsearch) {
            /*// do normal indexed search
			$search_in_pd = '';
			if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
				$search_in_pd = " or ".$tbl."products_description like '%".addslashes($this->post['tx_multishop_pi1']['q'])."%'";
			}
			$filter[]="(".$tbl."products_name like '".addslashes($this->post['tx_multishop_pi1']['q'])."%'".$search_in_pd.")";*/
            // do normal indexed search
            $innerFilter = array();
            if ($this->ms['MODULES']['FLAT_DATABASE']) {
                $tbl = 'pf.';
            } else {
                $tbl = 'pd.';
            }
            $innerFilter[] = "(" . $tbl . "products_name like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                $innerFilter[] = "(" . $tbl . "products_description like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_TITLE']) {
                $innerFilter[] = "(" . $tbl . "products_meta_title like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_KEYWORDS']) {
                $innerFilter[] = "(" . $tbl . "products_meta_keywords like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_DESCRIPTION']) {
                $innerFilter[] = "(" . $tbl . "products_meta_description like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_MANUFACTURERS_NAME']) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'm.';
                }
                $innerFilter[] = "(" . $tbl . "manufacturers_name like '%" . addslashes($this->post['tx_multishop_pi1']['q']) . "%')";
            }
            if (count($search_in_option_ids)) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                foreach ($search_in_option_ids as $options_id) {
                    if (is_numeric($options_id)) {
                        $innerFilter[] = '(' . $tbl . 'products_id IN (SELECT DISTINCT(pa.products_id) FROM tx_multishop_products_attributes pa, tx_multishop_products_options po, tx_multishop_products_options_values pov,  tx_multishop_products_options_values_to_products_options povp where pa.options_id=\'' . $options_id . '\' and pa.page_uid=\'' . $this->showCatalogFromPage . '\' and pov.products_options_values_name like \'%' . addslashes($this->post['tx_multishop_pi1']['q']) . '%\' and pov.language_id=0 and pov.products_options_values_id=povp.products_options_values_id and povp.products_options_values_id=pa.options_values_id and pa.options_id=povp.products_options_id and po.language_id=pov.language_id and po.products_options_id=povp.products_options_id and po.language_id=pov.language_id))';
                    }
                }
            }
            $filter[] = "(" . implode(" OR ", $innerFilter) . ")";
        } else {
            // do fulltext search
            // $tmpstr=addslashes(mslib_befe::ms_implode(', ', $array,'"','+',true));
            // $select[]	="MATCH (".$tbl."products_name) AGAINST ('".$tmpstr."' in boolean mode) AS score";
            $tmpstr = addslashes(mslib_befe::ms_implode(', ', $array, '"', '+', true));
            $fields = $tbl . "products_name";
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                $fields .= "," . $tbl . "products_description";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_TITLE']) {
                $ultra_fields .= "," . $tbl . "products_meta_title";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_KEYWORDS']) {
                $ultra_fields .= "," . $tbl . "products_meta_keywords";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_DESCRIPTION']) {
                $ultra_fields .= "," . $tbl . "products_meta_description";
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_ID']) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                $fields .= "," . $tbl . "products_id";
            }
            $select[] = "MATCH (" . $fields . ") AGAINST ('" . $tmpstr . "' in boolean mode) AS score";
            $filter[] = "MATCH (" . $fields . ") AGAINST ('" . $tmpstr . "' in boolean mode)";
            $orderby[] = 'score desc';
        }
    }
    //if (is_numeric($this->post['categories_id'])) {
    //	$parent_id=$this->post['categories_id'];
    //}
    if (is_numeric($parent_id) and $parent_id > 0) {
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $string = '(';
            for ($i = 0; $i < 4; $i++) {
                if ($i > 0) {
                    $string .= " or ";
                }
                $string .= "pf.categories_id_" . $i . " = '" . $parent_id . "'";
            }
            $string .= ')';
            if ($string) {
                $filter[] = $string;
            }
            //
        } else {
            /*
			$cats=mslib_fe::get_subcategory_ids($parent_id);
			$cats[]=$parent_id;
			if(is_array($this->post['categories_id_extra'])){
				$cats = array();
				foreach ($this->post['categories_id_extra'] as $key_id=>$catid){
					$cats_extra=mslib_fe::get_subcategory_ids($catid);
					$cats[]=$catid;
					$cats=array_merge($cats_extra, $cats);
				}
			}
			$filter[]="p2c.is_deepest=1 AND p2c.categories_id IN (".implode(",",$cats).")";
			*/
            if ($this->post['categories_id_extra']) {
                $filter[] = "p2c.node_id =" . addslashes(implode(",", $this->post['categories_id_extra']));
            } else {
                if (!is_array($this->post['tx_multishop_pi1']['categories']) || is_array($this->post['tx_multishop_pi1']['categories']) && !count($this->post['tx_multishop_pi1']['categories'])) {
                    if (is_array($subcats_data) && count($subcats_data)) {
                        $filter[] = "p2c.node_id in (" . implode(',', $subcats_data) . ")";
                    } else {
                        $filter[] = "p2c.node_id =" . addslashes($parent_id);
                    }
                }
            }
        }
    }
    if (is_numeric($this->post['min']) and is_numeric($this->post['max'])) {
        if (!$this->ms['MODULES']['FLAT_DATABASE']) {
            $having[] = "(final_price BETWEEN '" . addslashes($this->post['min']) . "' and '" . addslashes($this->post['max']) . "')";
        } else {
            $filter[] = "(pf.final_price BETWEEN '" . addslashes($this->post['min']) . "' and '" . addslashes($this->post['max']) . "')";
        }
    }
    $from = array();
    if (is_array($this->post['tx_multishop_pi1']['options']) and count($this->post['tx_multishop_pi1']['options'])) {
        // attributes
        if (!$this->ms['MODULES']['FLAT_DATABASE']) {
            $prefix = 'p.';
        } else {
            $prefix = 'pf.';
        }
        foreach ($this->post['tx_multishop_pi1']['options'] as $option_id => $option_values_id) {
            foreach ($option_values_id as $ovi_key => $ovi_val) {
                if ($ovi_val == 0) {
                    unset($option_values_id[$ovi_key]);
                }
            }
            if ($option_id == 'or') {
                if (is_array($option_values_id) and count($option_values_id)) {
                    foreach ($option_values_id as $key => $val) {
                        if ($key == 'range') {
                            foreach ($val as $option_id => $ranges) {
                                $from_value = $ranges[0];
                                $till_value = $ranges[1];
                                $options_name = "option" . $option_id;
                                $options_name = str_replace(array(
                                        ' ',
                                        '-'
                                ), '_', $options_name);
                                $options_name = str_replace(array(
                                        '(',
                                        ')',
                                        '[',
                                        ']',
                                        "'",
                                        '"',
                                        ':',
                                        ';',
                                        '/',
                                        "\\"
                                ), '', $options_name);
                                $options_name = str_replace('__', '_', $options_name);
                                $options_name = addslashes($options_name);
                                // this does not work nice with varchar. we need to convert the value to integer first
//								$between_field=$options_name.'_ov.products_options_values_name';
                                $between_field = 'CONVERT(SUBSTRING(' . $options_name . '_ov.products_options_values_name, LOCATE(\'-\', ' . $options_name . '_ov.products_options_values_name) + 1), SIGNED INTEGER)';
                                $subquery = 'SELECT ' . $options_name . '.products_id from tx_multishop_products_attributes ' . $options_name . ', tx_multishop_products_options_values ' . $options_name . '_ov where (' . $between_field . ' BETWEEN \'' . addslashes($from_value) . '\' AND \'' . addslashes($till_value) . '\' and ' . $options_name . '.options_id = "' . addslashes($option_id) . '" and ' . $options_name . '.page_uid="' . $this->showCatalogFromPage . '" and ' . $options_name . '.options_values_id=' . $options_name . '_ov.products_options_values_id) group by ' . $options_name . '.products_id';
                                $filter[] = $prefix . 'products_id IN (' . $subquery . ')';
                            }
                        } elseif (count($val)) {
                            $ors = implode(",", $val);
                            if ($ors) {
                                $options_name = "option" . $key;
                                $options_name = str_replace(array(
                                        ' ',
                                        '-'
                                ), '_', $options_name);
                                $options_name = str_replace(array(
                                        '(',
                                        ')',
                                        '[',
                                        ']',
                                        "'",
                                        '"',
                                        ':',
                                        ';',
                                        '/',
                                        "\\"
                                ), '', $options_name);
                                $options_name = str_replace('__', '_', $options_name);
                                $from[] = 'tx_multishop_products_attributes ' . $options_name;
                                $filter[] = "(" . $prefix . "products_id = " . $options_name . ".products_id and " . $options_name . ".page_uid=" . $this->showCatalogFromPage . " and " . $options_name . ".options_id = " . addslashes($key) . " and " . $options_name . ".options_values_id IN (" . $ors . "))";
                            }
                        }
                    }
                }
            } elseif (is_numeric($option_values_id) and $option_values_id) {
                if ($option_values_id > 0) {
                    $options_name = "option" . $option_id;
                    //echo $options_name;
                    $options_name = str_replace(array(
                            ' ',
                            '-'
                    ), '_', $options_name);
                    $options_name = str_replace(array(
                            '(',
                            ')',
                            '[',
                            ']',
                            "'",
                            '"',
                            ':',
                            ';',
                            '/',
                            "\\"
                    ), '', $options_name);
                    $options_name = str_replace('__', '_', $options_name);
                    $from[] = 'tx_multishop_products_attributes ' . $options_name;
                    $filter[] = "(" . $prefix . "products_id = $options_name.products_id and " . $options_name . ".page_uid=" . $this->showCatalogFromPage . " and " . $options_name . ".options_id = " . addslashes($option_id) . " and " . $options_name . ".options_values_id = " . addslashes($option_values_id) . ")";
                }
            } elseif (is_array($option_values_id) && count($option_values_id)) {
                $options_name = "option" . $option_id;
                //echo $options_name;
                $options_name = str_replace(array(
                        ' ',
                        '-'
                ), '_', $options_name);
                $options_name = str_replace(array(
                        '(',
                        ')',
                        '[',
                        ']',
                        "'",
                        '"',
                        ':',
                        ';',
                        '/',
                        "\\"
                ), '', $options_name);
                $options_name = str_replace('__', '_', $options_name);
                $from[] = 'tx_multishop_products_attributes ' . $options_name;
                $filter[] = "(" . $prefix . "products_id = $options_name.products_id and " . $options_name . ".page_uid=" . $this->showCatalogFromPage . " and " . $options_name . ".options_id = " . addslashes($option_id) . " and " . $options_name . ".options_values_id IN (" . addslashes(implode(",", $option_values_id)) . "))";
            }
        }
    }
    if (isset($this->cookie['limitsb']) && $this->cookie['limitsb'] > 0) {
        $limit = $this->cookie['limitsb'];
    } else {
        $limit = $this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'];
    }
    if ($this->post['page']) {
        $p = $this->post['page'];
        $offset = $limit * ($p - 1);
    } else {
        $offset = 0;
        $p = 1;
    }
    $results = array();
    $results_products = array();
    if (!$this->ms['MODULES']['FLAT_DATABASE']) {
        $prefix = 'p.';
    } else {
        $prefix = 'pf.';
    }
    if (!empty($this->post['tx_multishop_pi1']['manufacturers'])) {
        if (is_array($this->post['tx_multishop_pi1']['manufacturers'])) {
            foreach ($this->post['tx_multishop_pi1']['manufacturers'] as $key => $value) {
                $this->post['tx_multishop_pi1']['manufacturers'][$key] = addslashes($value);
            }
            $filter[] = $prefix . "manufacturers_id IN (" . addslashes(implode(",", $this->post['tx_multishop_pi1']['manufacturers'])) . ")";
        } else {
            if (strpos($this->post['tx_multishop_pi1']['manufacturers'], ',') === false) {
                $filter[] = $prefix . 'manufacturers_id=' . addslashes($this->post['tx_multishop_pi1']['manufacturers']);
            } else {
                $filter[] = $prefix . "manufacturers_id IN (" . addslashes($this->post['tx_multishop_pi1']['manufacturers']) . ")";
            }
        }
    }
    if (is_array($this->post['tx_multishop_pi1']['categories']) and count($this->post['tx_multishop_pi1']['categories'])) {
        $sub_filter = array();
        foreach ($this->post['tx_multishop_pi1']['categories'] as $categories_id) {
            if (is_numeric($categories_id)) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $string = '(';
                    for ($i = 0; $i < 4; $i++) {
                        if ($i > 0) {
                            $string .= " or ";
                        }
                        $string .= "pf.categories_id_" . $i . " = '" . $categories_id . "'";
                    }
                    $string .= ')';
                    if ($string) {
                        $sub_filter[] = $string;
                    }
                    //
                } else {
                    /*
					$cats=mslib_fe::get_subcategory_ids($categories_id);
					$cats[]=$categories_id;
					if(is_array($this->post['categories_id_extra'])){
						$cats = array();
						foreach ($this->post['categories_id_extra'] as $key_id=>$catid){
							$cats_extra=mslib_fe::get_subcategory_ids($catid);
							$cats[]=$catid;
							$cats=array_merge($cats_extra, $cats);
						}
					}
					$sub_filter[]="p2c.is_deepest=1 AND p2c.categories_id IN (".implode(",",$cats).")";
					*/
                    if ($this->post['categories_id_extra']) {
                        $sub_filter[] = "p2c.node_id=" . addslashes(implode(",", $this->post['categories_id_extra']));
                    } else {
                        if (is_array($subcats_data) && count($subcats_data)) {
                            $sub_filter[] = "p2c.node_id in (" . implode(',', $subcats_data) . ")";
                        } else {
                            $sub_filter[] = "p2c.node_id=" . addslashes($categories_id);
                        }
                    }
                }
            }
        }
        if (count($sub_filter)) {
            $filter[] = '(' . implode(" OR ", $sub_filter) . ')';
        }
    }
    if (is_array($this->post['sort_filter']) and count($this->post['sort_filter']) > 0) {
        $test_orderby = $this->post['sort_filter'][0];
    } else if ($this->post['sort_filter']) {
        $test_orderby = $this->post['sort_filter'];
    }
    if ($test_orderby) {
        switch ($test_orderby) {
            case 'products_name ASC':
            case 'products_name DESC':
                if ($test_orderby == 'products_name DESC') {
                    $sort = 'desc';
                } else {
                    $sort = 'asc';
                }
                if (!$this->ms['MODULES']['FLAT_DATABASE']) {
                    $prefix = 'p.';
                } else {
                    $prefix = 'pf.';
                }
                $orderby[] = $prefix . 'products_name ' . $sort;
                break;
            case 'final_price ASC':
            case 'final_price DESC':
                if ($test_orderby == 'final_price DESC') {
                    $sort = 'desc';
                } else {
                    $sort = 'asc';
                }
                $orderby[] = 'final_price ' . $sort;
                break;
        }
    }
    $extra_join = array();
    if (isset($this->cookie['sortbysb']) && !empty($this->cookie['sortbysb'])) {
        if ($this->ms['MODULES']['FLAT_DATABASE']) {
            $tbl = 'pf.';
            $tbl_m = 'pf.';
        } else {
            $tbl = 'p.';
            $tbl_m = 'm.';
        }
        switch ($this->cookie['sortbysb']) {
            case 'best_selling_asc':
                $select[] = 'SUM(op.qty) as order_total_qty';
                $extra_join[] = 'LEFT JOIN tx_multishop_orders_products op ON ' . $tbl . 'products_id=op.products_id';
                $orderby[] = "order_total_qty asc";
                break;
            case 'best_selling_desc':
                $select[] = 'SUM(op.qty) as order_total_qty';
                $extra_join[] = 'LEFT JOIN tx_multishop_orders_products op ON ' . $tbl . 'products_id=op.products_id';
                $orderby[] = "order_total_qty desc";
                break;
            case 'price_asc':
                $orderby[] = "final_price asc";
                break;
            case 'price_desc':
                $orderby[] = "final_price desc";
                break;
            case 'new_asc':
                $orderby[] = $tbl . "products_date_added desc";
                break;
            case 'new_desc':
                $orderby[] = $tbl . "products_date_added asc";
                break;
            case 'manufacturers_asc':
                $orderby[] = $tbl_m . "manufacturers_name asc";
                break;
            case 'manufacturers_desc':
                $orderby[] = $tbl_m . "manufacturers_name desc";
                break;
            default:
                // custom hook that can be controlled by third-party plugin
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchOrderByFilter'])) {
                    $params = array(
                            'from' => &$from,
                            'where' => &$where,
                            'filter' => &$filter,
                            'orderby' => &$orderby,
                            'select' => &$select,
                            'extra_join' => &$extra_join
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server.php']['ultrasearchOrderByFilter'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                break;
        }
    }
    //error_log(print_r($filter, 1));
    // GET PRODUCTS FOR LISTING
    //$this->msDebug=1;
    $pageset = mslib_fe::getProductsPageSet($filter, $offset, $limit, $orderby, $having, $select, $where, 0, $from, array(), 'ajax_products_search', $select_total_count, 0, 1, $extra_join);
    //error_log($this->msDebugInfo);
    //echo $this->msDebugInfo;
    //die();
    //	error_log($pageset['total_rows']);
    //	error_log($this->ms['MODULES']['PRODUCTS_LISTING_LIMIT']);
    if ($pageset['total_rows'] > 0) {
        $products = $pageset['products'];
        if (count($products)) {
            if ($this->post['tx_multishop_pi1']['q']) {
                mslib_befe::storeProductsKeywordSearch($this->post['tx_multishop_pi1']['q']);
            }
            $totpage = ceil($pageset['total_rows'] / $limit);
            foreach ($products as $index => $product) {
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
                        $where .= '&';
                    }
                    // get all cats to generate multilevel fake url eof
                }
                $temp_var_products = array();
                $link_detail = mslib_fe::typolink($this->conf['products_detail_page_pid'], '&' . $where . '&products_id=' . $product['products_id'] . '&tx_multishop_pi1[page_section]=products_detail');
                $catlink = mslib_fe::typolink($this->conf['products_listing_page_pid'], '&' . $where . '&tx_multishop_pi1[page_section]=products_listing');
                $final_price = mslib_fe::final_products_price($product);
                if ($product['tax_rate'] and $this->ms['MODULES']['SHOW_PRICES_WITH_AND_WITHOUT_VAT']) {
                    $price_excluding_vat = $this->pi_getLL('excluding_vat') . ' ' . mslib_fe::amount2Cents($product['final_price']);
                } else {
                    $price_excluding_vat = false;
                }
                if ($product['products_price'] <> $product['final_price']) {
                    if (!$this->ms['MODULES']['DB_PRICES_INCLUDE_VAT'] and ($product['tax_rate'] and $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'])) {
                        $old_price = $product['products_price'] * (1 + $product['tax_rate']);
                    } else {
                        $old_price = $product['products_price'];
                    }
                    $old_price = mslib_fe::amount2Cents($old_price);
                    $specials_price = mslib_fe::amount2Cents($final_price);
                    $price = false;
                } else {
                    $old_price = false;
                    $specials_price = false;
                    $price = mslib_fe::amount2Cents($final_price);
                }
                $temp_var_products['products_id'] = $product['products_id'];
                $temp_var_products['products_shortdescription'] = $product['products_shortdescription'];
                $temp_var_products['products_name'] = $product['products_name'];
                $temp_var_products['products_model'] = $product['products_model'];
                $temp_var_products['products_url'] = $product['products_url'];
                $temp_var_products['categories_name'] = $product['categories_name'];
                $temp_var_products['admin_edit_product_button'] = false;
                $temp_var_products['admin_edit_product'] = '';
                $temp_var_products['admin_delete_product'] = '';
                if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
                    $temp_var_products['admin_edit_product_button'] = true;
                    $temp_var_products['admin_edit_product'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_product&cid=' . $product['categories_id'] . '&pid=' . $product['products_id'] . '&action=edit_product', 1);
                    $temp_var_products['admin_delete_product'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=delete_product&cid=' . $product['categories_id'] . '&pid=' . $product['products_id'] . '&action=delete_product', 1);
                }
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    for ($s = 0; $s < 5; $s++) {
                        if ($product['categories_id_' . $s]) {
                            $temp_var_products['categories_name_' . $s] = $product['categories_name_' . $s];
                            // get all cats to generate multilevel fake url
                            $level = 0;
                            $cats = mslib_fe::Crumbar($product['categories_id_' . $s]);
                            $cats = array_reverse($cats);
                            $where = '';
                            if (count($cats) > 0) {
                                foreach ($cats as $cat) {
                                    $where .= "categories_id[" . $level . "]=" . $cat['id'] . "&";
                                    $level++;
                                }
                                $where = substr($where, 0, (strlen($where) - 1));
                                $where .= '&';
                            }
                            // get all cats to generate multilevel fake url eof
                            $temp_var_products['categories_link_' . $s] = mslib_fe::typolink($this->conf['products_listing_page_pid'], $where . '&tx_multishop_pi1[page_section]=products_listing');
                        }
                    }
                }
                $temp_var_products['link_detail'] = $link_detail;
                $temp_var_products['link_add_to_cart'] = mslib_fe::typolink($this->shop_pid, '&tx_multishop_pi1[page_section]=shopping_cart&tx_multishop_pi1[action]=add_to_cart&products_id=' . $product['products_id']);
                $temp_var_products['add_to_basket'] = $this->pi_getLL('add_to_basket');
                $temp_var_products['catlink'] = $catlink;
                if ($product['products_image']) {
                    $temp_var_products['products_image'] = mslib_befe::getImagePath($product['products_image'], 'products', '100');
                    $temp_var_products['products_image50'] = mslib_befe::getImagePath($product['products_image'], 'products', '50');
                    $temp_var_products['products_image200'] = mslib_befe::getImagePath($product['products_image'], 'products', '200');
                }
                if ($product['products_image1']) {
                    $temp_var_products['products_image1'] = mslib_befe::getImagePath($product['products_image1'], 'products', '100');
                    $temp_var_products['products_image150'] = mslib_befe::getImagePath($product['products_image1'], 'products', '50');
                    $temp_var_products['products_image1200'] = mslib_befe::getImagePath($product['products_image1'], 'products', '200');
                }
                $temp_var_products['manufacturers_name'] = $product['manufacturers_name'];
                $temp_var_products['price_excluding_vat'] = $price_excluding_vat;
                $temp_var_products['old_price'] = $old_price;
                $temp_var_products['special_price'] = $specials_price;
                $temp_var_products['price'] = $price;
                $temp_var_products['shipping_costs_popup'] = 0;
                if ($this->ms['MODULES']['DISPLAY_SHIPPING_COSTS_ON_PRODUCTS_LISTING_PAGE']) {
                    $temp_var_products['shipping_costs_popup'] = 1;
                }
                foreach ($product as $key => $val) {
                    if (strstr($key, "a_")) {
                        $temp_var_products[$key] = $val;
                    }
                }
                //hook to let other plugins further manipulate the query
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server/default.php']['ultrasearchProductsListingItemPostProc'])) {
                    $params = array(
                            'temp_var_products' => &$temp_var_products,
                            'product' => &$product
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/includes/ultrasearch_server/default.php']['ultrasearchProductsListingItemPostProc'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                $results_products[] = $temp_var_products;
            }
        } else {
            // no results
            if ($this->post['tx_multishop_pi1']['q']) {
                mslib_befe::storeProductsKeywordSearch($this->post['tx_multishop_pi1']['q'], '1');
            }
        }
    }
    // DEFAULT EMPTY
    $cmsDescriptionArray = array();
    $results['current_categories']['name'] = '';
    $results['categories_description']['header'] = '';
    $results['categories_description']['footer'] = '';
    if (isset($this->get['manufacturers_id']) && is_numeric($this->get['manufacturers_id'])) {
        $strCms = $GLOBALS ['TYPO3_DB']->SELECTquery('m.manufacturers_id, mc.content, mc.content_footer, m.manufacturers_name', // SELECT ...
                'tx_multishop_manufacturers m, tx_multishop_manufacturers_cms mc', // FROM ...
                "m.manufacturers_id='" . $this->get['manufacturers_id'] . "' AND m.status=1 and mc.language_id='" . $this->sys_language_uid . "' and m.manufacturers_id=mc.manufacturers_id", // WHERE.
                '', // GROUP BY...
                '', // ORDER BY...
                '' // LIMIT ...
        );
        $qryCms = $GLOBALS['TYPO3_DB']->sql_query($strCms);
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($qryCms)) {
            $rowCms = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qryCms);
            //$cmsDescriptionArray['header_title']=$rowCms['manufacturers_name'];
            $cmsDescriptionArray['content'] = $rowCms['content'];
            $cmsDescriptionArray['content_footer'] = $rowCms['content_footer'];
        }
    } elseif (isset($this->get['categories_id']) && is_numeric($this->get['categories_id'])) {
        $strCms = $GLOBALS ['TYPO3_DB']->SELECTquery('c.categories_id, cd.content, cd.content_footer, cd.categories_name', // SELECT ...
                'tx_multishop_categories c, tx_multishop_categories_description cd', // FROM ...
                "c.categories_id='" . $this->get['categories_id'] . "' AND c.status=1 and cd.language_id='" . $this->sys_language_uid . "' and c.page_uid='" . $this->showCatalogFromPage . "' and c.categories_id=cd.categories_id", // WHERE.
                '', // GROUP BY...
                '', // ORDER BY...
                '' // LIMIT ...
        );
        $qryCms = $GLOBALS['TYPO3_DB']->sql_query($strCms);
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($qryCms)) {
            $rowCms = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qryCms);
            //$cmsDescriptionArray['header_title']=$rowCms['categories_name'];
            $cmsDescriptionArray['content'] = $rowCms['content'];
            $cmsDescriptionArray['content_footer'] = $rowCms['content_footer'];
        }
    }
    //error_log(print_r($cmsDescriptionArray,1));
    if (count($cmsDescriptionArray)) {
        if (!$p || $p == 1) {
            if (isset($cmsDescriptionArray['content']) && !empty($cmsDescriptionArray['content'])) {
                $results['categories_description']['header'] = $cmsDescriptionArray['content'];
            }
            if (isset($cmsDescriptionArray['content_footer']) && !empty($cmsDescriptionArray['content_footer'])) {
                $results['categories_description']['footer'] = $cmsDescriptionArray['content_footer'];
            }
        }
        if ($cmsDescriptionArray['header_title']) {
            $results['current_categories']['name'] = $cmsDescriptionArray['header_title'];
        }
    }
    $results['products'] = $results_products;
    if ($this->ms['MODULES']['DISPLAY_SHIPPING_COSTS_ON_PRODUCTS_LISTING_PAGE']) {
        $results['labels']['shipping_costs'] = $this->pi_getLL('shipping_costs');
        $results['labels']['product_shipping_and_handling_cost_overview'] = $this->pi_getLL('product_shipping_and_handling_cost_overview');
        $results['labels']['deliver_to'] = $this->pi_getLL('deliver_to');
        $results['labels']['shipping_and_handling_cost_overview'] = $this->pi_getLL('shipping_and_handling_cost_overview');
        $results['labels']['deliver_by'] = $this->pi_getLL('deliver_by');
    }
    $results['total_rows'] = $pageset['total_rows'];
    $results['pagination']['offset'] = $offset;
    $results['pagination']['limit'] = $limit;
    $results['pagination']['totpage'] = $totpage;
    if ($p == 1) {
        $results['pagination']['prev'] = false;
        $results['pagination']['first'] = false;
    } else {
        $results['pagination']['prev'] = $p - 1;
    }
    if ($p == 0 || $p < 9) {
        $start_page_number = 1;
        if ($totpage <= 10) {
            $end_page_number = $totpage;
        } else {
            $end_page_number = 10;
        }
    } else if ($p >= 9) {
        $start_page_number = ($p - 5) + 1;
        $end_page_number = ($p + 4) + 1;
        if ($end_page_number > $totpage) {
            $end_page_number = $totpage;
        }
    }
    for ($x = $start_page_number; $x <= $end_page_number; $x++) {
        if ($p == $x) {
            $results['pagination']['page_number'][$x]['link'] = 0;
            $results['pagination']['page_number'][$x]['number'] = $x;
        } else {
            $results['pagination']['page_number'][$x]['link'] = $x;
            $results['pagination']['page_number'][$x]['number'] = $x;
        }
    }
    $results['pagination']['current_p'] = $p;
    if ($totpage == $p) {
        $results['pagination']['next'] = false;
        $results['pagination']['last'] = false;
    } else {
        $results['pagination']['next'] = $p + 1;
    }
    $results['pagination']['firstText'] = mslib_befe::strtoupper($this->pi_getLL('first'));
    $results['pagination']['first'] = mslib_befe::strtoupper($this->pi_getLL('first'));
    $results['pagination']['prevText'] = mslib_befe::strtoupper($this->pi_getLL('previous'));
    $results['pagination']['nextText'] = mslib_befe::strtoupper($this->pi_getLL('next'));
    $results['pagination']['last'] = mslib_befe::strtoupper($this->pi_getLL('last'));
    $results['pagination']['lastText'] = mslib_befe::strtoupper($this->pi_getLL('last'));
    $data['resultSet'] = $results;
    //$data['sql_dump']=$this->msDebugInfo;
    $content = json_encode($data);
    if ($this->ms['MODULES']['CACHE_FRONT_END']) {
        $Cache_Lite->save($content);
    }
}
echo $content;
exit();
?>