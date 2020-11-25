<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$content = '';
switch ($this->get['tx_multishop_pi1']['admin_ajax_attributes_options_values']) {
    case 'add_new_options':
        $options_data = array();
        $sql_chk = $GLOBALS['TYPO3_DB']->SELECTquery('products_options_id', // SELECT ...
                'tx_multishop_products_options', // FROM ...
                "products_options_name = '" . addslashes($this->post['new_option_name']) . "' and language_id = '" . $this->sys_language_uid . "'", // WHERE...
                '', // GROUP BY...
                '', // ORDER BY...
                '' // LIMIT ...
        );
        $qry_chk = $GLOBALS['TYPO3_DB']->sql_query($sql_chk);
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_chk) > 0) {
            $options_data['status'] = 'NOTOK';
            $options_data['reason'] = $this->pi_getLL('admin_label_error_option_name_exist');
        } else {
            $new_option_name = $this->post['new_option_name'];
            $required = 0;
            $hide_in_cart = 0;
            $hide_in_details_page = 0;
            $listtype = 'select';
            if (isset($this->post['required']) && $this->post['required'] > 0) {
                $required = 1;
            }
            if (isset($this->post['hide_in_details_page']) && $this->post['hide_in_details_page'] > 0) {
                $hide_in_details_page = 1;
            }
            if (isset($this->post['hide_in_cart']) && $this->post['hide_in_cart'] > 0) {
                $hide_in_cart = 1;
            }
            if (isset($this->post['listtype']) && !empty($this->post['listtype'])) {
                $listtype = $this->post['listtype'];
            }
            $sql_chk = $GLOBALS['TYPO3_DB']->SELECTquery('products_options_id', // SELECT ...
                    'tx_multishop_products_options', // FROM ...
                    '', // WHERE...
                    '', // GROUP BY...
                    'products_options_id desc', // ORDER BY...
                    '1' // LIMIT ...
            );
            $qry_chk = $GLOBALS['TYPO3_DB']->sql_query($sql_chk);
            $rs_chk = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_chk);
            $max_optid = $rs_chk['products_options_id'] + 1;
            // use microtime as the default sorting
            $tmp_mtime = explode(" ", microtime());
            $mtime = array_sum($tmp_mtime);
            // prep for insertion
            $insertArray = array();
            $insertArray['products_options_id'] = $max_optid;
            $insertArray['language_id'] = $this->sys_language_uid;
            $insertArray['products_options_name'] = $new_option_name;
            $insertArray['listtype'] = $listtype;
            $insertArray['hide'] = $hide_in_details_page;
            $insertArray['hide_in_cart'] = $hide_in_cart;
            $insertArray['required'] = $required;
            $insertArray['attributes_values'] = '0';
            $insertArray['sort_order'] = 99999;
            $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options', $insertArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            $pa_option = $max_optid;
            if ($this->ms['MODULES']['ENABLE_ATTRIBUTES_OPTIONS_GROUP']) {
                if (isset($this->post['options_groups'][0]) && !empty($this->post['options_groups'][0])) {
                    $updateArray = array();
                    $updateArray['attributes_options_groups_id'] = $this->post['options_groups'][0];
                    $updateArray['products_options_id'] = $max_optid;
                    $str = $GLOBALS['TYPO3_DB']->SELECTquery('1', // SELECT ...
                            'tx_multishop_attributes_options_groups_to_products_options', // FROM ...
                            'products_options_id=\'' . $max_optid . '\' and attributes_options_groups_id=\'' . $this->post['options_groups'][0] . '\'', // WHERE...
                            '', // GROUP BY...
                            '', // ORDER BY...
                            '' // LIMIT ...
                    );
                    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                    if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
                        $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_attributes_options_groups_to_products_options', $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    } else {
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_attributes_options_groups_to_products_options', 'products_options_id=\'' . $max_optid . '\'', $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    }
                }
            }
            if ($max_optid > 0) {
                $options_data['status'] = 'OK';
                $options_data['option_name'] = $new_option_name;
                $options_data['option_id'] = $max_optid;
                $options_data['required'] = $required;
                $options_data['hide_in_details_page'] = $hide_in_details_page;
                $options_data['hide_in_cart'] = $hide_in_cart;
                $selects = array();
                $selects['select'] = $this->pi_getLL('admin_label_option_type_selectbox');
                $selects['select_multiple'] = $this->pi_getLL('admin_label_option_type_selectbox_multiple');
                $selects['radio'] = $this->pi_getLL('admin_label_option_type_radio');
                $selects['checkbox'] = $this->pi_getLL('admin_label_option_type_checkbox');
                $selects['input'] = $this->pi_getLL('admin_label_option_type_text_input');
                $selects['textarea'] = $this->pi_getLL('admin_label_option_type_textarea');
                $selects['hidden_field'] = $this->pi_getLL('admin_label_option_type_hidden_field');
                $selects['file'] = $this->pi_getLL('admin_label_option_type_file_input');
                $selects['divider'] = $this->pi_getLL('admin_label_option_type_divider');
                $list_type = '<select name="listtype[' . $max_optid . ']" class="form-control">';
                foreach ($selects as $key => $value) {
                    $list_type .= '<option value="' . $key . '"' . ($key == $listtype ? ' selected' : '') . '>' . htmlspecialchars($value) . '</option>';
                }
                $list_type .= '</select>';
                $options_data['listtype'] = $list_type;
                if ($this->ms['MODULES']['ENABLE_ATTRIBUTES_OPTIONS_GROUP']) {
                    $options_group = mslib_fe::buildAttributesOptionsGroupSelectBox($max_optid, 'class="form-control"');
                    if (!empty($options_group)) {
                        $options_group = '<div class="form-group"><label class="col-md-2 control-label">' . $this->pi_getLL('admin_label_options_group') . ': </label><div class="col-md-4">' . $options_group . '</div></div>';
                    } else {
                        $options_group = '<div class="form-group"><label class="col-md-2 control-label">' . $this->pi_getLL('admin_label_options_group') . ': </label><div class="col-md-4">' . $this->pi_getLL('admin_label_no_groups_defined') . '</div></div>';
                    }
                    $options_data['options_groups'] = $options_group;
                } else {
                    $options_data['options_groups'] = '';
                }
            }
        }
        $json_data = mslib_befe::array2json($options_data);
        echo $json_data;
        exit();
        break;
    case 'get_option_data':
        $options_data = array();
        foreach ($this->languages as $key => $language) {
            $str = $GLOBALS['TYPO3_DB']->SELECTquery('products_options_name, products_options_descriptions', // SELECT ...
                    'tx_multishop_products_options', // FROM ...
                    'products_options_id=\'' . (int)$this->post['option_id'] . '\' and language_id=\'' . $key . '\'', // WHERE...
                    '', // GROUP BY...
                    '', // ORDER BY...
                    '' // LIMIT ...
            );
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
            $option_name = '';
            $option_desc = '';
            if ($row['products_options_name']) {
                $option_name = htmlspecialchars($row['products_options_name']);
            }
            if ($row['products_options_descriptions']) {
                $option_desc = htmlspecialchars($row['products_options_descriptions']);
            }
            if ($key == 0) {
                $options_data['options_title'] = $option_name;
            }
            $options_data['options'][$key]['lang_title'] = $this->languages[$key]['title'];
            $options_data['options'][$key]['options_name'] = $option_name;
            $options_data['options'][$key]['options_desc'] = $option_desc;
        }
        $json_data = mslib_befe::array2json($options_data);
        echo $json_data;
        exit();
        break;
    case 'update_options_data':
        // update options name
        if (is_array($this->post['option_names']) and count($this->post['option_names'])) {
            foreach ($this->post['option_names'] as $products_options_id => $array) {
                foreach ($array as $language_id => $value) {
                    $updateArray = array();
                    $updateArray['language_id'] = $language_id;
                    $updateArray['products_options_id'] = $products_options_id;
                    $updateArray['products_options_name'] = $value;
                    $str = $GLOBALS['TYPO3_DB']->SELECTquery('1', // SELECT ...
                            'tx_multishop_products_options', // FROM ...
                            'products_options_id=\'' . $products_options_id . '\' and language_id=\'' . $language_id . '\'', // WHERE...
                            '', // GROUP BY...
                            '', // ORDER BY...
                            '' // LIMIT ...
                    );
                    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                    if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0) {
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options', 'products_options_id=\'' . $products_options_id . '\' and language_id=\'' . $language_id . '\'', $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    } else {
                        $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options', $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    }
                }
            }
        }
        // update options description
        foreach ($this->post['option_desc'] as $opt_id => $langs_id) {
            foreach ($langs_id as $lang_id => $opt_desc) {
                $str2 = $GLOBALS['TYPO3_DB']->SELECTquery('products_options_id, products_options_descriptions, language_id', // SELECT ...
                        "tx_multishop_products_options po", // FROM ...
                        "po.products_options_id='" . $opt_id . "' and language_id='" . $lang_id . "'", // WHERE...
                        '', // GROUP BY...
                        '', // ORDER BY...
                        '' // LIMIT ...
                );
                $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry2);
                if ($num_rows > 0) {
                    $updateArray = array();
                    $updateArray['products_options_descriptions'] = ($opt_desc ? $opt_desc : '');
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options', 'products_options_id=\'' . $opt_id . '\' and language_id = ' . $lang_id, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                } else {
                    $str2 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                            "tx_multishop_products_options po", // FROM ...
                            "po.products_options_id='" . $opt_id . "' and language_id='0'", // WHERE...
                            '', // GROUP BY...
                            '', // ORDER BY...
                            '' // LIMIT ...
                    );
                    $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                    $rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2);
                    $rs['description'] = (string)$rs['description'];
                    // insert new lang desc
                    $insertArray = array();
                    $insertArray['products_options_id'] = $opt_id;
                    $insertArray['language_id'] = $lang_id;
                    $insertArray['products_options_name'] = $rs['products_options_name'];
                    $insertArray['listtype'] = $rs['listtype'];
                    $insertArray['description'] = ($rs['description'] ? $rs['description'] : '');
                    $insertArray['sort_order'] = $rs['sort_order'];
                    $insertArray['price_group_id'] = ($rs['price_group_id'] > 0 ? $rs['price_group_id'] : 0);
                    $insertArray['hide'] = ($rs['hide'] ? $rs['hide'] : 0);
                    $insertArray['attributes_values'] = $rs['attributes_values'];
                    $insertArray['hide_in_cart'] = ($rs['hide_in_cart'] ? $rs['hide_in_cart'] : 0);
                    $insertArray['required'] = ($rs['required'] ? $rs['required'] : 0);
                    $insertArray['products_options_descriptions'] = ($opt_desc ? $opt_desc : '');
                    $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options', $insertArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                }
            }
        }
        break;
    case 'get_option_values_data':
        $pov2po_id = $this->post['relation_id'];
        $return_data = array();
        $str2 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                'tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_options_values pov', // FROM ...
                'povp.products_options_values_to_products_options_id=\'' . $pov2po_id . '\' and povp.products_options_values_id=pov.products_options_values_id and pov.language_id=\'0\'', // WHERE...
                '', // GROUP BY...
                'povp.sort_order', // ORDER BY...
                '' // LIMIT ...
        );
        $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
        while (($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2)) != false) {
            $option_name = mslib_fe::getRealNameOptions($row2['products_options_id']);
            $return_data['options_name'] = $option_name;
            $return_data['options_id'] = $row2['products_options_id'];
            $return_data['options_values_id'] = $row2['products_options_values_id'];
            $return_data['options_values_name'] = htmlspecialchars($row2['products_options_values_name']);
            // options values
            $str_valgroup = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                    'tx_multishop_attributes_options_values_groups_to_options_values', // FROM ...
                    'products_options_values_id=\'' . $row2['products_options_values_id'] . '\'', // WHERE...
                    '', // GROUP BY...
                    '', // ORDER BY...
                    '' // LIMIT ...
            );
            $qry_valgroup = $GLOBALS['TYPO3_DB']->sql_query($str_valgroup);
            $row_valgroup = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_valgroup);
            $return_data['options_values_group_id'] = htmlspecialchars($row_valgroup['attributes_options_values_groups_id']);
            $lang_counter = 0;
            foreach ($this->languages as $key => $language) {
                // options values
                $str3 = $GLOBALS['TYPO3_DB']->SELECTquery('products_options_values_name, group_dropdown_label', // SELECT ...
                        'tx_multishop_products_options_values pov', // FROM ...
                        'pov.products_options_values_id=\'' . $row2['products_options_values_id'] . '\' and pov.language_id=\'' . $key . '\'', // WHERE...
                        '', // GROUP BY...
                        '', // ORDER BY...
                        '' // LIMIT ...
                );
                $qry3 = $GLOBALS['TYPO3_DB']->sql_query($str3);
                $row3 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry3);
                $value = '';
                $dropdown_label = '';
                if ($row3['products_options_values_name']) {
                    $value = htmlspecialchars($row3['products_options_values_name']);
                }
                if ($row3['group_dropdown_label']) {
                    $dropdown_label = htmlspecialchars($row3['group_dropdown_label']);
                }
                $return_data['results'][$key]['lang_title'] = $this->languages[$key]['title'];
                $return_data['results'][$key]['lang_id'] = $key;
                $return_data['results'][$key]['lang_values'] = $value;
                $return_data['results'][$key]['lang_dropdown_label'] = $dropdown_label;
                // options values description
                $str4 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                        'tx_multishop_products_options_values_to_products_options_desc pov2pod', // FROM ...
                        'pov2pod.products_options_values_to_products_options_id=\'' . $pov2po_id . '\' and pov2pod.language_id=\'' . $key . '\'', // WHERE...
                        '', // GROUP BY...
                        '', // ORDER BY...
                        '' // LIMIT ...
                );
                $qry4 = $GLOBALS['TYPO3_DB']->sql_query($str4);
                $row4 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry4);
                $description = '';
                if ($row4['description']) {
                    $description = htmlspecialchars($row4['description']);
                }
                $return_data['results'][$key]['lang_description_pov2po_id'] = $pov2po_id;
                $return_data['results'][$key]['lang_description'] = $description;
            }
        }
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
    case 'update_options_values_data':
        // save/update values
        if (is_array($this->post['option_values']) and count($this->post['option_values'])) {
            $pov2po_id = $this->post['data_id'];
            $attribute_options = mslib_befe::getRecord($pov2po_id, 'tx_multishop_products_options_values_to_products_options', 'products_options_values_to_products_options_id');
            $option_id = 0;
            if (is_array($attribute_options) && isset($attribute_options['products_options_id'])) {
                $option_id = $attribute_options['products_options_id'];
            }
            foreach ($this->post['option_values'] as $products_options_values_id => $array) {
                // check if the current value also have relation with other attribute options
                $insert_new = false;
                if ($option_id > 0) {
                    $attribute_records = mslib_befe::getRecords($products_options_values_id, 'tx_multishop_products_options_values_to_products_options', 'products_options_values_id', array('products_options_id!=' . $option_id), '', '', '', array('products_options_id'));
                    if (is_array($attribute_records) && count($attribute_records)) {
                        $insert_new = true;
                    }
                }
                $group_id = 0;
                $query = $GLOBALS['TYPO3_DB']->DELETEquery('tx_multishop_attributes_options_values_groups_to_options_values', 'products_options_values_id=\'' . $products_options_values_id . '\'');
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                if (isset($this->post['option_values_group'][$products_options_values_id])) {
                    $group_id = $this->post['option_values_group'][$products_options_values_id];
                    $insertArray = array();
                    $insertArray['products_options_values_id'] = $products_options_values_id;
                    $insertArray['attributes_options_values_groups_id'] = $group_id;
                    $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_attributes_options_values_groups_to_options_values', $insertArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                }
                foreach ($array as $language_id => $value) {
                    if ($insert_new) {
                        $value_record = mslib_befe::getRecord($products_options_values_id, 'tx_multishop_products_options_values', 'products_options_values_id');
                        if (is_array($value_record) && isset($value_record['products_options_values_name'])) {
                            // only insert if the db value is different than the post'ed value
                            if ($value_record['products_options_values_name'] != $value) {
                                $insertArray = array();
                                $insertArray['language_id'] = $language_id;
                                $insertArray['products_options_values_name'] = $value;
                                $insertArray['group_dropdown_label'] = $this->post['option_values_dropdown_title'][$products_options_values_id][$language_id];
                                $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values', $insertArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                $new_products_options_values_id = $GLOBALS['TYPO3_DB']->sql_insert_id();
                                // update the pov2po table record
                                $updateArray = array();
                                $updateArray['products_options_values_id'] = $new_products_options_values_id;
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values_to_products_options', 'products_options_values_to_products_options_id=\'' . $pov2po_id . '\'', $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                // update the product_attributes
                                $updateArray = array();
                                $updateArray['options_values_id'] = $new_products_options_values_id;
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_attributes', 'options_id=\'' . $option_id . '\' and options_values_id=\'' . $products_options_values_id . '\'', $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            }
                        }
                    } else {
                        $updateArray = array();
                        $updateArray['language_id'] = $language_id;
                        $updateArray['products_options_values_name'] = $value;
                        $updateArray['group_dropdown_label'] = $this->post['option_values_dropdown_title'][$products_options_values_id][$language_id];
                        $str = "select 1 from tx_multishop_products_options_values where products_options_values_id='" . $products_options_values_id . "' and language_id='" . $language_id . "'";
                        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                        if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0) {
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values', 'products_options_values_id=\'' . $products_options_values_id . '\' and language_id=\'' . $language_id . '\'', $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        } else {
                            $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values', $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        }
                    }
                }
            }
        }
        // save/update values descriptio
        if (is_array($this->post['ov_desc']) and count($this->post['ov_desc'])) {
            foreach ($this->post['ov_desc'] as $pov2po_id => $langs_id) {
                foreach ($langs_id as $lang_id => $pov2po_desc) {
                    $updateArray = array();
                    $str2 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                            'tx_multishop_products_options_values_to_products_options_desc pov2pod', // FROM ...
                            'pov2pod.products_options_values_to_products_options_id=\'' . $pov2po_id . '\' and language_id=\'' . $lang_id . '\'', // WHERE...
                            '', // GROUP BY...
                            '', // ORDER BY...
                            '' // LIMIT ...
                    );
                    $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                    if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry2)) {
                        $updateArray['description'] = $pov2po_desc;
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values_to_products_options_desc', 'products_options_values_to_products_options_id=\'' . $pov2po_id . '\' and language_id = ' . $lang_id, $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    } else {
                        $updateArray['products_options_values_to_products_options_id'] = $pov2po_id;
                        $updateArray['language_id'] = $lang_id;
                        $updateArray['description'] = $pov2po_desc;
                        $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values_to_products_options_desc', $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    }
                }
            }
        }
        break;
    case 'get_attributes_values':
        $where = array();
        $where[] = "optval.language_id = '" . $this->sys_language_uid . "'";
        if (isset($this->get['option_id']) && is_numeric($this->get['option_id'])) {
            $where[] = "(optval2opt.products_options_id = '" . $this->get['option_id'] . "')";
        }
        $from = array();
        $from =
        $skip_db = false;
        if (isset($this->get['q']) && !empty($this->get['q'])) {
            $where[] = "optval.products_options_values_name like '%" . addslashes($this->get['q']) . "%'";
        } else if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
            if (is_numeric($this->get['preselected_id'])) {
                $where[] = "optval2opt.products_options_values_id = '" . $this->get['preselected_id'] . "'";
            } else {
                if (strpos($this->get['preselected_id'], ',') !== false) {
                    $where[] = "optval.products_options_values_id in (" . $this->get['preselected_id'] . ")";
                } else {
                    $where[] = "optval.products_options_values_name like '%" . addslashes($this->get['preselected_id']) . "%'";
                }
            }
        }
        $str = $GLOBALS ['TYPO3_DB']->SELECTquery('optval.*', // SELECT ...
                'tx_multishop_products_options_values as optval left join tx_multishop_products_options_values_to_products_options as optval2opt on optval2opt.products_options_values_id = optval.products_options_values_id', // FROM ...
                implode(' and ', $where), // WHERE...
                'optval.products_options_values_id', // GROUP BY...
                'optval.products_options_values_name asc', // ORDER BY...
                '' // LIMIT ...
        );
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $data = array();
        $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
        if ($num_rows) {
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                $data[] = array(
                        'id' => $row['products_options_values_id'],
                        'text' => $row['products_options_values_name']
                );
            }
        } else {
            if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
                $data[] = array(
                        'id' => $this->get['preselected_id'],
                        'text' => $this->get['preselected_id']
                );
            } else {
                $data[] = array(
                        'id' => $this->get['q'],
                        'text' => $this->get['q']
                );
            }
        }
        $json_data = mslib_befe::array2json($data);
        echo $json_data;
        exit();
        break;
    case 'save_options_values_data':
        $json_data = array();
        $optid = $this->post['optid'];
        if ($this->post['is_manual'] == 1) {
            $insertArray = array();
            $insertArray['language_id'] = 0;
            $insertArray['products_options_values_name'] = $this->post['new_values'];
            $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values', $insertArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            $values_id = $GLOBALS['TYPO3_DB']->sql_insert_id();
            $json_data['values_id'] = $values_id;
            $json_data['values_name'] = $this->post['new_values'];
            // new relations
            list($usec, $sec) = explode(" ", microtime());
            $sort_order = ((float)$usec + (float)$sec);
            $insertArray = array();
            $insertArray['products_options_id'] = $optid;
            $insertArray['products_options_values_id'] = $values_id;
            $insertArray['sort_order'] = $sort_order;
            $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values_to_products_options', $insertArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            $pov2po_id = $GLOBALS['TYPO3_DB']->sql_insert_id();
            $json_data['pov2po_id'] = $pov2po_id;
        } else {
            $values_id = $this->post['new_values'];
            $json_data['values_id'] = $values_id;
            $json_data['values_name'] = mslib_fe::getNameOptions($values_id);
            // pov2po_id
            $str = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                    'tx_multishop_products_options_values_to_products_options pov2po', // FROM ...
                    'pov2po.products_options_id=\'' . $optid . '\' and pov2po.products_options_values_id=\'' . $values_id . '\'', // WHERE...
                    '', // GROUP BY...
                    '', // ORDER BY...
                    '' // LIMIT ...
            );
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
                $json_data['pov2po_id'] = $row['products_options_values_to_products_options_id'];
            } else {
                // new relations
                list($usec, $sec) = explode(" ", microtime());
                $sort_order = ((float)$usec + (float)$sec);
                $insertArray = array();
                $insertArray['products_options_id'] = $optid;
                $insertArray['products_options_values_id'] = $values_id;
                $insertArray['sort_order'] = $sort_order;
                $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values_to_products_options', $insertArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                $pov2po_id = $GLOBALS['TYPO3_DB']->sql_insert_id();
                $json_data['pov2po_id'] = $pov2po_id;
            }
        }
        $json_data = mslib_befe::array2json($json_data);
        echo $json_data;
        exit();
        break;
    /*case 'save_options_description':
        foreach ($this->post['opt_desc'] as $opt_id=>$langs_id) {
            foreach ($langs_id as $lang_id=>$opt_desc) {
                $str2=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_id, products_options_descriptions, language_id', // SELECT ...
                    "tx_multishop_products_options po", // FROM ...
                    "po.products_options_id='".$opt_id."' and language_id='".$lang_id."'", // WHERE...
                    '', // GROUP BY...
                    '', // ORDER BY...
                    '' // LIMIT ...
                );
                $qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
                $num_rows=$GLOBALS['TYPO3_DB']->sql_num_rows($qry2);
                if ($num_rows>0) {
                    $updateArray=array();
                    $updateArray['products_options_descriptions']=$opt_desc;
                    $query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options', 'products_options_id=\''.$opt_id.'\' and language_id = '.$lang_id, $updateArray);
                    $res=$GLOBALS['TYPO3_DB']->sql_query($query);
                } else {
                    $str2=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_id, products_options_descriptions, language_id', // SELECT ...
                        "tx_multishop_products_options po", // FROM ...
                        "po.products_options_id='".$opt_id."' and language_id='0'", // WHERE...
                        '', // GROUP BY...
                        '', // ORDER BY...
                        '' // LIMIT ...
                    );
                    $qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
                    $rs=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2);
                    // insert new lang desc
                    $insertArray=array();
                    $insertArray['products_options_id']=$opt_id;
                    $insertArray['language_id']=$lang_id;
                    $insertArray['listtype']=$rs['listtype'];
                    $insertArray['description']=$rs['description'];
                    $insertArray['sort_order']=$rs['sort_order'];
                    $insertArray['price_group_id']=$rs['price_group_id'];
                    $insertArray['hide']=$rs['hide'];
                    $insertArray['attributes_values']=$rs['attributes_values'];
                    $insertArray['hide_in_cart']=$rs['hide_in_cart'];
                    $insertArray['required']=$rs['required'];
                    $insertArray['products_options_descriptions']=$opt_desc;
                    $query=$GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options', $insertArray);
                    $res=$GLOBALS['TYPO3_DB']->sql_query($query);
                }
            }
        }
        exit();
        break;
    case 'fetch_options_values_description':
        $pov2po_id=$this->post['data_id'];
        $return_data=array();
        $str=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
            'tx_multishop_products_options_values_to_products_options pov2po', // FROM ...
            'pov2po.products_options_values_to_products_options_id=\''.$pov2po_id.'\'', // WHERE...
            '', // GROUP BY...
            '', // ORDER BY...
            '' // LIMIT ...
        );
        $qry=$GLOBALS['TYPO3_DB']->sql_query($str);
        $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
        $option_name=mslib_fe::getRealNameOptions($row['products_options_id']);
        $return_data['options_name']=$option_name;
        $option_value_name=mslib_fe::getNameOptions($row['products_options_values_id']);
        $return_data['options_values_name']=$option_value_name;
        $counter=0;
        foreach ($this->languages as $key=>$language) {
            $str2=$GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                'tx_multishop_products_options_values_to_products_options_desc pov2pod', // FROM ...
                'pov2pod.products_options_values_to_products_options_id=\''.$pov2po_id.'\' and language_id=\''.$key.'\'', // WHERE...
                '', // GROUP BY...
                '', // ORDER BY...
                '' // LIMIT ...
            );
            $qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry2)) {
                while (($row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2))!=false) {
                    $return_data['results'][$counter]['pov2po_id']=$pov2po_id;
                    $return_data['results'][$counter]['lang_title']=$this->languages[$row2['language_id']]['title'];
                    $return_data['results'][$counter]['lang_id']=$row2['language_id'];
                    $return_data['results'][$counter]['description']=htmlspecialchars($row2['description']);
                }
            } else {
                $return_data['results'][$counter]['pov2po_id']=$pov2po_id;
                $return_data['results'][$counter]['lang_title']=$this->languages[$key]['title'];
                $return_data['results'][$counter]['lang_id']=$key;
                $return_data['results'][$counter]['description']='';
            }
            $counter++;
        }
        $json_data=mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;*/
    case 'save_options_values_description':
        foreach ($this->post['ov_desc'] as $pov2po_id => $langs_id) {
            foreach ($langs_id as $lang_id => $pov2po_desc) {
                $updateArray = array();
                $str2 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                        'tx_multishop_products_options_values_to_products_options_desc pov2pod', // FROM ...
                        'pov2pod.products_options_values_to_products_options_id=\'' . $pov2po_id . '\' and language_id=\'' . $lang_id . '\'', // WHERE...
                        '', // GROUP BY...
                        '', // ORDER BY...
                        '' // LIMIT ...
                );
                $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry2)) {
                    $updateArray['description'] = $pov2po_desc;
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values_to_products_options_desc', 'products_options_values_to_products_options_id=\'' . $pov2po_id . '\' and language_id = ' . $lang_id, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                } else {
                    $updateArray['products_options_values_to_products_options_id'] = $pov2po_id;
                    $updateArray['language_id'] = $lang_id;
                    $updateArray['description'] = $pov2po_desc;
                    $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_products_options_values_to_products_options_desc', $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                }
            }
        }
        exit();
        break;
    case 'fetch_attributes':
        $sort_by = 'povp.sort_order asc';
        if (isset($this->get['tx_multishop_pi1']['sort_by']) && !empty($this->get['tx_multishop_pi1']['sort_by'])) {
            switch ($this->get['tx_multishop_pi1']['sort_by']) {
                case 'id_asc':
                    $sort_by = 'pov.products_options_values_id asc';
                    break;
                case 'id_desc':
                    $sort_by = 'pov.products_options_values_id desc';
                    break;
                case 'alpha_asc':
                    $sort_by = 'pov.products_options_values_name asc';
                    break;
                case 'alpha_desc':
                    $sort_by = 'pov.products_options_values_name desc';
                    break;
                case 'alpha_nat_asc':
                    $sort_by = 'pov.products_options_values_name REGEXP \'^\d*[^\da-z&\.\\\' \-\\"\!\@\#\$\%\^\*\(\)\;\:\\,\?\/\~\`\|\_\-]\' asc, pov.products_options_values_name+0 asc, pov.products_options_values_name asc';
                    break;
                case 'alpha_nat_desc':
                    $sort_by = 'pov.products_options_values_name REGEXP \'^\d*[^\da-z&\.\\\' \-\\"\!\@\#\$\%\^\*\(\)\;\:\\,\?\/\~\`\|\_\-]\' desc, pov.products_options_values_name+0 desc, pov.products_options_values_name desc';
                    break;
            }
        }
        $option_id = $this->post['data_id'];
        $return_data = array();
        $str2 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                'tx_multishop_products_options_values_to_products_options povp, tx_multishop_products_options_values pov', // FROM ...
                'povp.products_options_id=\'' . $option_id . '\' and povp.products_options_values_id=pov.products_options_values_id and pov.language_id=\'0\'', // WHERE...
                '', // GROUP BY...
                $sort_by, // ORDER BY...
                '' // LIMIT ...
        );
        $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
        $counter = 0;
        while (($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2)) != false) {
            $value = htmlspecialchars($row2['products_options_values_name']);
            $return_data['results'][$counter]['values_id'] = $row2['products_options_values_id'];
            $return_data['results'][$counter]['values_name'] = htmlspecialchars($row2['products_options_values_name']);
            $return_data['results'][$counter]['values_image'] = 'disabled';
            if ($this->ms['MODULES']['ENABLE_ATTRIBUTE_VALUE_IMAGES']) {
                $return_data['results'][$counter]['values_image'] = htmlspecialchars($row2['products_options_values_image']);
                if (!empty($row2['products_options_values_image'])) {
                    $return_data['results'][$counter]['values_image_display'] = '<img class="values_image' . $row2['products_options_values_to_products_options_id'] . '" src="' . mslib_befe::getImagePath($row2['products_options_values_image'], 'attribute_values', 'small') . '" width="50px">
					<a class="values_image' . $row2['products_options_values_to_products_options_id'] . '" id="delete_attribute_values_image" href="#" rel="' . $row2['products_options_values_to_products_options_id'] . '"><img src="' . $this->FULL_HTTP_URL_MS . 'templates/images/icons/delete2.png" border="0" alt="' . $this->pi_getLL('admin_delete_image') . '"></a>';
                } else {
                    $return_data['results'][$counter]['values_image_display'] = '';
                }
            }
            $return_data['results'][$counter]['pov2po_id'] = htmlspecialchars($row2['products_options_values_to_products_options_id']);
            $counter++;
        }
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
    case 'delete_values_image':
        /*$str2=$GLOBALS['TYPO3_DB']->SELECTquery('products_options_values_image', // SELECT ...
            'tx_multishop_products_options_values_to_products_options povp', // FROM ...
            'povp.products_options_values_to_products_options_id=\''.$this->post['pov2po'].'\'', // WHERE...
            '', // GROUP BY...
            '', // ORDER BY...
            '' // LIMIT ...
        );
        $qry2=$GLOBALS['TYPO3_DB']->sql_query($str2);
        $row2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2);
        mslib_befe::deleteAttributeValuesImage($row2['products_options_values_image']);*/
        $updateArray = array();
        $updateArray['products_options_values_image'] = '';
        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values_to_products_options', 'products_options_values_to_products_options_id=' . $this->post['pov2po'], $updateArray);
        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
        $return_data = array();
        $return_data['target_delete'] = '.values_image' . $this->post['pov2po'];
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
    case 'upload_attribute_values_image':
        $tmp_filename = $this->get['attribute_values_name'];
        if (!$this->ms['MODULES']['ADMIN_AUTORENAME_UPLOADED_IMAGES']) {
            if (isset($this->get['qqfile']) && !empty($this->get['qqfile'])) {
                $tmp_arr = explode('.', $this->get['qqfile']);
                $tmp_arr_count = count($tmp_arr);
                unset($tmp_arr[$tmp_arr_count - 1]);
                $tmp_filename = implode('.', $tmp_arr);
            }
        }
        // hidden filename that is retrieved from the ajax upload
        $i = $this->get['pov2po_id'];
        $field = 'attribute_values_image' . $i;
        if ($this->get['file_type'] == $field) {
            $temp_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . uniqid();
            if (isset($_FILES['qqfile'])) {
                move_uploaded_file($_FILES['qqfile']['tmp_name'], $temp_file);
            } else {
                $input = fopen("php://input", "r");
                $temp = tmpfile();
                $realSize = stream_copy_to_stream($input, $temp);
                fclose($input);
                $target = fopen($temp_file, "w");
                fseek($temp, 0, SEEK_SET);
                stream_copy_to_stream($temp, $target);
                fclose($target);
            }
            $size = getimagesize($temp_file);
            if ($size[0] > 5 and $size[1] > 5) {
                $imgtype = mslib_befe::exif_imagetype($temp_file);
                if ($imgtype) {
                    // valid image
                    $ext = image_type_to_extension($imgtype, false);
                    if ($ext) {
                        $i = 0;
                        $filename = mslib_fe::rewritenamein($tmp_filename) . '.' . $ext;
                        $folder = mslib_befe::getImagePrefixFolder($filename);
                        $array = explode(".", $filename);
                        if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder)) {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder);
                        }
                        $folder .= '/';
                        $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder . $filename;
                        if (file_exists($target)) {
                            do {
                                $filename = mslib_fe::rewritenamein($tmp_filename) . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                                $folder_name = mslib_befe::getImagePrefixFolder($filename);
                                $array = explode(".", $filename);
                                $folder = $folder_name;
                                if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder)) {
                                    \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder);
                                }
                                $folder .= '/';
                                $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder . $filename;
                                $i++;
                            } while (file_exists($target));
                        }
                        if (copy($temp_file, $target)) {
                            $filename = mslib_befe::resizeProductAttributeValuesImage($target, $filename, $this->DOCUMENT_ROOT . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey), 1);
                            $result = array();
                            $result['success'] = true;
                            $result['error'] = false;
                            $result['filename'] = $filename;
                            $result['target_after'] = '#ajax_attribute_values_image' . $this->get['pov2po_id'];
                            $result['target_delete'] = '.values_image' . $this->get['pov2po_id'];
                            $result['image_display'] = '<img class="values_image' . $this->get['pov2po_id'] . '" src="' . mslib_befe::getImagePath($filename, 'attribute_values', 'small') . '" width="50px">
							<a class="values_image' . $this->get['pov2po_id'] . '" id="delete_attribute_values_image" href="#" rel="' . $this->get['pov2po_id'] . '"><img src="' . $this->FULL_HTTP_URL_MS . 'templates/images/icons/delete2.png" border="0" alt="' . $this->pi_getLL('admin_delete_image') . '"></a>';
                            $updateArray = array();
                            $updateArray['products_options_values_image'] = $filename;
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values_to_products_options', 'products_options_values_to_products_options_id=' . $this->get['pov2po_id'], $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            echo json_encode($result);
                            exit();
                        }
                    }
                }
            }
        }
        break;
    case 'delete_product_attribute_value_images':
        list($image_id, $image_fn) = explode(':', $this->post['image']);
        $product_id = 0;
        $option_id = 0;
        $value_id = 0;
        if (strpos($image_id, '_') !== false) {
            list($product_id, $option_id, $value_id) = explode('_', $image_id);
        }
        if ($product_id > 0 && $option_id > 0 && $value_id > 0) {
            $updateArray = array();
            $updateArray['attribute_image'] = '';
            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_attributes', 'products_id=' . $product_id . ' and options_id=' . $option_id . ' and options_values_id=' . $value_id . ' and attribute_image=\'' . $image_fn . '\' and page_uid=\'' . $this->showCatalogFromPage . '\'', $updateArray);
            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
        }
        mslib_befe::deleteAttributeValuesImage($image_fn);
        $return_data = array();
        $return_data['target_delete_id'] = $image_id;
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
    case 'admin_upload_product_attribute_value_images':
        $tmp_filename = $this->get['file_type'];
        if (!$this->ms['MODULES']['ADMIN_AUTORENAME_UPLOADED_IMAGES']) {
            if (isset($this->get['qqfile']) && !empty($this->get['qqfile'])) {
                $tmp_arr = explode('.', $this->get['qqfile']);
                $tmp_arr_count = count($tmp_arr);
                unset($tmp_arr[$tmp_arr_count - 1]);
                $tmp_filename = implode('.', $tmp_arr);
            }
        }
        // hidden filename that is retrieved from the ajax upload
        $i = $this->get['attribute_value_image'];
        $field = 'attribute_value_image' . $i;
        if ($this->get['file_type'] == $field) {
            $temp_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . uniqid();
            if (isset($_FILES['qqfile'])) {
                move_uploaded_file($_FILES['qqfile']['tmp_name'], $temp_file);
            } else {
                $input = fopen("php://input", "r");
                $temp = tmpfile();
                $realSize = stream_copy_to_stream($input, $temp);
                fclose($input);
                $target = fopen($temp_file, "w");
                fseek($temp, 0, SEEK_SET);
                stream_copy_to_stream($temp, $target);
                fclose($target);
            }
            $size = getimagesize($temp_file);
            if ($size[0] > 5 and $size[1] > 5) {
                $imgtype = mslib_befe::exif_imagetype($temp_file);
                if ($imgtype) {
                    // valid image
                    $ext = image_type_to_extension($imgtype, false);
                    if ($ext) {
                        $i = 0;
                        $filename = mslib_fe::rewritenamein($tmp_filename) . '.' . $ext;
                        $folder = mslib_befe::getImagePrefixFolder($filename);
                        $array = explode(".", $filename);
                        if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder)) {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder);
                        }
                        $folder .= '/';
                        $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder . $filename;
                        if (file_exists($target)) {
                            do {
                                $filename = mslib_fe::rewritenamein($tmp_filename) . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                                $folder_name = mslib_befe::getImagePrefixFolder($filename);
                                $array = explode(".", $filename);
                                $folder = $folder_name;
                                if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder)) {
                                    \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder);
                                }
                                $folder .= '/';
                                $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['attribute_values']['original'] . '/' . $folder . $filename;
                                $i++;
                            } while (file_exists($target));
                        }
                        if (copy($temp_file, $target)) {
                            $filename = mslib_befe::resizeProductAttributeValuesImage($target, $filename, $this->DOCUMENT_ROOT . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey), 1);
                            $result = array();
                            $result['success'] = true;
                            $result['error'] = false;
                            $result['filename'] = $filename;
                            $result['fileLocation'] = mslib_befe::getImagePath($filename, 'attribute_values', 'original');
                            $result['target_after'] = '#ajax_attribute_value_image' . $this->get['attribute_value_image'];
                            $result['target_delete'] = '.product_values_image' . $this->get['attribute_value_image'];
                            $result['image_display'] = '<img class="values_image' . $this->get['attribute_value_image'] . '" src="' . mslib_befe::getImagePath($filename, 'attribute_values', 'small') . '" width="50px">
							<a class="values_image' . $this->get['attribute_value_image'] . '" id="delete_attribute_values_image" href="#" rel="' . $this->get['attribute_value_image'] . '"><img src="' . $this->FULL_HTTP_URL_MS . 'templates/images/icons/delete2.png" border="0" alt="' . $this->pi_getLL('admin_delete_image') . '"></a>';
                            /*$updateArray=array();
                            $updateArray['attribute_image']=$filename;
                            $query=$GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_attributes', 'products_attributes_id='.$this->get['attribute_value_image'], $updateArray);
                            $res=$GLOBALS['TYPO3_DB']->sql_query($query);*/
                            echo json_encode($result);
                            exit();
                        }
                    }
                }
            }
        }
        break;
    case 'update_attributes_sortable':
        switch ($this->get['tx_multishop_pi1']['type']) {
            case 'options':
                if (is_array($this->post['options']) and count($this->post['options'])) {
                    $no = 1;
                    foreach ($this->post['options'] as $prod_id) {
                        if (is_numeric($prod_id)) {
                            // global level
                            $where = "products_options_id = " . $prod_id;
                            $updateArray = array(
                                    'sort_order' => $no
                            );
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options', $where, $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            // products level
                            $where = "options_id = " . $prod_id . " and page_uid='" . $this->showCatalogFromPage . "'";
                            $updateArray = array();
                            $updateArray = array(
                                    'sort_order_option_name' => $no
                            );
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_attributes', $where, $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            $no++;
                        }
                    }
                }
                break;
            case 'option_values':
                if (is_array($this->post['option_values']) and count($this->post['option_values'])) {
                    if (is_numeric($this->post['products_options_id'])) {
                        $no = 1;
                        foreach ($this->post['option_values'] as $prod_id) {
                            if (is_numeric($prod_id)) {
                                $where = "products_options_id='" . $this->post['products_options_id'] . "' and products_options_values_id = " . $prod_id;
                                $updateArray = array(
                                        'sort_order' => $no
                                );
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_options_values_to_products_options', $where, $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                // products level
                                $where = "options_id = " . $this->post['products_options_id'] . " and options_values_id=" . $prod_id . " and page_uid=" . $this->showCatalogFromPage;
                                $updateArray = array();
                                $updateArray = array(
                                        'sort_order_option_value' => $no
                                );
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_attributes', $where, $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                $no++;
                            }
                        }
                    }
                }
                break;
        }
        exit();
        break;
    case 'delete_attributes':
        $option_id = 0;
        $option_value_id = 0;
        list($option_id, $option_value_id) = explode(':', $this->post['data_id']);
        $return_data = array();
        $return_data['option_id'] = $option_id;
        $return_data['option_value_id'] = $option_value_id;
        $return_data['option_name'] = mslib_fe::getRealNameOptions($option_id);
        $return_data['option_value_name'] = mslib_fe::getNameOptions($option_value_id);
        $return_data['data_id'] = $this->post['data_id'];
        $return_data['delete_status'] = 'notok';
        $have_entries_in_pa_table = false;
        if ($option_value_id > 0) {
            $str = "select products_id from tx_multishop_products_attributes where options_id='" . $option_id . "' and options_values_id=" . $option_value_id;
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $total_product = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
            if ($total_product > 0) {
                $ctr = 0;
                $return_data['products'] = array();
                while ($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                    $product = mslib_fe::getProduct($rs['products_id'], '', '', 1);
                    if (!empty($product['products_name'])) {
                        $return_data['products'][$ctr]['name'] = $product['products_name'];
                        $return_data['products'][$ctr]['link'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_product&pid=' . $rs['products_id'] . '&cid=' . $product['categories_id'] . '&action=edit_product');
                        $ctr++;
                    } else {
                        $have_entries_in_pa_table = true;
                        $total_product--;
                    }
                }
            }
            if (!$total_product && $have_entries_in_pa_table) {
                $this->get['force_delete'] = 1;
            }
            if (isset($this->get['force_delete']) && $this->get['force_delete'] == 1) {
                if (!$total_product) {
                    $str = "delete from tx_multishop_products_attributes where options_id = " . $option_id . " and options_values_id = " . $option_value_id;
                    $GLOBALS['TYPO3_DB']->sql_query($str);
                }
                $str = "delete from tx_multishop_products_options_values_to_products_options where products_options_id = " . $option_id . " and products_options_values_id = " . $option_value_id;
                $GLOBALS['TYPO3_DB']->sql_query($str);
                $return_data['delete_status'] = 'ok';
                $return_data['delete_id'] = '.option_values_' . $option_id . '_' . $option_value_id;
            }
            $return_data['products_used'] = $total_product;
        } else {
            $str = "select products_id from tx_multishop_products_attributes where options_id='" . $option_id . "'";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $total_product = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
            if ($total_product > 0) {
                $ctr = 0;
                $return_data['products'] = array();
                while ($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                    $product = mslib_fe::getProduct($rs['products_id'], '', '', 1);
                    if (!empty($product['products_name'])) {
                        $return_data['products'][$ctr]['name'] = $product['products_name'];
                        $return_data['products'][$ctr]['link'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_product&pid=' . $rs['products_id'] . '&cid=' . $product['categories_id'] . '&action=edit_product');
                        $ctr++;
                    } else {
                        $have_entries_in_pa_table = true;
                        $total_product--;
                    }
                }
            }
            if (!$total_product && $have_entries_in_pa_table) {
                $this->get['force_delete'] = 1;
            }
            if (isset($this->get['force_delete']) && $this->get['force_delete'] == 1) {
                if (!$total_product) {
                    $str = "delete from tx_multishop_products_attributes where options_id = " . $option_id;
                    $GLOBALS['TYPO3_DB']->sql_query($str);
                }
                $str = "delete from tx_multishop_products_options where products_options_id = " . $option_id;
                $GLOBALS['TYPO3_DB']->sql_query($str);
                $str = "delete from tx_multishop_products_options_values_to_products_options where products_options_id = " . $option_id;
                $GLOBALS['TYPO3_DB']->sql_query($str);
                $return_data['delete_status'] = 'ok';
                $return_data['delete_id'] = '#options_' . $option_id;
            }
            $return_data['products_used'] = $total_product;
        }
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
    case 'delete_options_values':
        $option_id = 0;
        $option_value_id = 0;
        list($option_id, $option_value_id) = explode(':', $this->post['data_id']);
        $return_data = array();
        $return_data['option_id'] = $option_id;
        $return_data['option_value_id'] = $option_value_id;
        $return_data['option_name'] = mslib_fe::getRealNameOptions($option_id);
        $return_data['option_value_name'] = mslib_fe::getNameOptions($option_value_id);
        $return_data['data_id'] = $this->post['data_id'];
        $return_data['delete_status'] = 'notok';
        $have_entries_in_pa_table = false;
        if ($option_value_id > 0) {
            $str = "select products_id from tx_multishop_products_attributes where options_id='" . $option_id . "' and options_values_id=" . $option_value_id;
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $total_product = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
            if ($total_product > 0) {
                $ctr = 0;
                $return_data['products'] = array();
                while ($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                    $product = mslib_fe::getProduct($rs['products_id'], '', '', 1);
                    if (!empty($product['products_name'])) {
                        $return_data['products'][$ctr]['name'] = $product['products_name'];
                        $return_data['products'][$ctr]['link'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_product&pid=' . $rs['products_id'] . '&cid=' . $product['categories_id'] . '&action=edit_product');
                        $ctr++;
                    } else {
                        $have_entries_in_pa_table = true;
                        $total_product--;
                    }
                }
            }
            if (!$total_product && $have_entries_in_pa_table) {
                $this->get['force_delete'] = 1;
            }
            if (isset($this->get['force_delete']) && $this->get['force_delete'] == 1) {
                //if (!$total_product) {
                $str = "delete from tx_multishop_products_attributes where options_id = " . $option_id . " and options_values_id = " . $option_value_id;
                $GLOBALS['TYPO3_DB']->sql_query($str);
                //}
                $str = "delete from tx_multishop_products_options_values_to_products_options where products_options_id = " . $option_id . " and products_options_values_id = " . $option_value_id;
                $GLOBALS['TYPO3_DB']->sql_query($str);
                $return_data['delete_status'] = 'ok';
                $return_data['delete_id'] = '.option_values_' . $option_id . '_' . $option_value_id;
            }
            $return_data['products_used'] = $total_product;
        }
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
}
exit();
?>
