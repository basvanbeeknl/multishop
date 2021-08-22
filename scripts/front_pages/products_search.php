<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
if ($_REQUEST['skeyword']) {
    //  using $_REQUEST cause TYPO3 converts "Command & Conquer" to "Conquer" (the & sign sucks ass)
    $this->get['skeyword'] = $_REQUEST['skeyword'];
    $this->get['skeyword'] = trim($this->get['skeyword']);
    $this->get['skeyword'] = $GLOBALS['TSFE']->csConvObj->utf8_encode($this->get['skeyword'], $GLOBALS['TSFE']->metaCharset);
    $this->get['skeyword'] = $GLOBALS['TSFE']->csConvObj->entities_to_utf8($this->get['skeyword'], true);
    $this->get['skeyword'] = mslib_fe::RemoveXSS($this->get['skeyword']);
}
if (is_numeric($this->get['p'])) {
    $p = $this->get['p'];
}
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
    //$string=md5($this->cObj->data['uid'].'_'.$this->HTTP_HOST.'_'.$this->server['REQUEST_URI'].$this->server['QUERY_STRING']);
    $string = md5(serialize($this->conf)) . $this->cObj->data['uid'] . '_' . $this->HTTP_HOST . '_' . $this->server['REQUEST_URI'] . $this->server['QUERY_STRING'];
}
if (!$this->ms['MODULES']['CACHE_FRONT_END'] or ($this->ms['MODULES']['CACHE_FRONT_END'] and !$output_array = $Cache_Lite->get($string))) {
    if ($p > 0) {
        $extrameta = ' (page ' . $p . ')';
    } else {
        $extrameta = '';
    }
    if (!$this->conf['disableMetatags']) {
        $output_array['meta']['title'] = '<title>' . ucfirst($this->pi_getLL('search_for')) . ' ' . htmlspecialchars($this->get['skeyword']) . $this->ms['MODULES']['PAGE_TITLE_DELIMETER'] . $this->ms['MODULES']['STORE_NAME'] . '</title>';
        $output_array['meta']['description'] = '<meta name="description" content="' . ucfirst($this->pi_getLL('search_for')) . ' ' . htmlspecialchars($this->get['skeyword']) . '." />';
    }
    $default_limit_page = $this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'];
    if ($this->get['tx_multishop_pi1']['limitsb']) {
        if ($this->get['tx_multishop_pi1']['limitsb'] and $this->get['tx_multishop_pi1']['limitsb'] != $this->cookie['limitsb']) {
            $this->cookie['limitsb'] = $this->get['tx_multishop_pi1']['limitsb'];
            $this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'] = $this->cookie['limitsb'];
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
            $GLOBALS['TSFE']->storeSessionData();
        }
    }
    if ($this->get['tx_multishop_pi1']['sortbysb']) {
        if ($this->get['tx_multishop_pi1']['sortbysb'] and $this->get['tx_multishop_pi1']['sortbysb'] != $this->cookie['sortbysb']) {
            $this->cookie['sortbysb'] = $this->get['tx_multishop_pi1']['sortbysb'];
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
            $GLOBALS['TSFE']->storeSessionData();
        }
    }
    if (isset($this->cookie['limitsb']) && isset($this->get['tx_multishop_pi1']['limitsb'])) {
        if ($this->ADMIN_USER) {
            $limit_per_page = 150;
        } else {
            $limit_per_page = $this->cookie['limitsb'];
        }
    } else {
        $limit_per_page = $this->ms['MODULES']['PRODUCTS_LISTING_LIMIT'];
    }
    if ($p > 0) {
        $offset = (((($p) * $limit_per_page)));
    } else {
        $p = 0;
        $offset = 0;
    }
    if (is_numeric($this->get['categories_id'])) {
        $parent_id = $this->get['categories_id'];
    } else {
        $parent_id = 0;
    }
    if ($this->get['price_filter']) {
        if (strstr($this->get['price_filter'], ">") or strstr($this->get['price_filter'], "<")) {
            $price_filter = $this->get['price_filter'];
        } elseif (strstr($this->get['price_filter'], "-")) {
            $array = explode("-", $this->get['price_filter']);
            if (count($array) == 2) {
                $price_filter = $array;
            }
        }
    }
    if ($this->get['skeyword'] || is_numeric($parent_id) || $price_filter) {
        $do_search = 1;
    }
    if ($do_search) {
        if ($this->get['skeyword']) {
            $title = $this->pi_getLL('search_for') . ': ' . htmlspecialchars($this->get['skeyword']);
        } else {
            $title = $this->pi_getLL('search');
        }
        $content .= '<div class="main-heading"><h1>' . $title . '</h1></div>';
        $extendsearch_content = '';
        //hook to let other plugins further manipulate the settings
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_search.php']['extendProductsSearch'])) {
            $params = array(
                    'extendsearch_content' => &$extendsearch_content,
                    'offset' => $offset,
                    'limit_per_page' => $limit_per_page,
                    'p' => $p
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_search.php']['extendProductsSearch'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        if (!empty($extendsearch_content)) {
            $content .= $extendsearch_content;
        }
        // product search
        $filter = array();
        $having = array();
        $match = array();
        $where = array();
        $orderby = array();
        $select = array();
        $extra_filter = array();
        $extra_join = array();
        if (is_numeric($this->get['manufacturers_id'])) {
            if ($this->ms['MODULES']['FLAT_DATABASE']) {
                $tbl = 'pf.';
            } else {
                $tbl = 'p.';
            }
            $filter[] = "(" . $tbl . "manufacturers_id='" . addslashes($this->get['manufacturers_id']) . "')";
        }
        if (strlen($this->get['skeyword']) > 1) {
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_ID']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_id', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                if (is_numeric($this->get['skeyword'])) {
                    $extra_filter[] = $tbl . "products_id ='" . addslashes($this->get['skeyword']) . "'";
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_VENDOR_CODE']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('vendor_code', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "vendor_code like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "vendor_code like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "vendor_code like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_EAN_CODE']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('ean_code', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "ean_code like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "ean_code like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "ean_code like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_MODEL']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_model', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "products_model like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "products_model like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "products_model like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_MANUFACTURERS_NAME']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('manufacturers_name', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'm.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "manufacturers_name like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "manufacturers_name like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "manufacturers_name like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_SKU_CODE']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('sku_code', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'p.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "sku_code like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "sku_code like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "sku_code like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_CATEGORIES_NAME']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('categories_name', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                    for ($i = 0; $i < 6; $i++) {
                        if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                            $extra_filter[] = $tbl . "categories_name_" . $i . " like '%" . addslashes($this->get['skeyword']) . "'";
                        } else {
                            if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                                $extra_filter[] = $tbl . "categories_name_" . $i . " like '" . addslashes($this->get['skeyword']) . "%'";
                            } else {
                                $extra_filter[] = $tbl . "categories_name_" . $i . " like '%" . addslashes($this->get['skeyword']) . "%'";
                            }
                        }
                    }
                } else {
                    $tbl = 'cd.';
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                        $extra_filter[] = $tbl . "categories_name like '%" . addslashes($this->get['skeyword']) . "'";
                    } else {
                        if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                            $extra_filter[] = $tbl . "categories_name like '" . addslashes($this->get['skeyword']) . "%'";
                        } else {
                            $extra_filter[] = $tbl . "categories_name like '%" . addslashes($this->get['skeyword']) . "%'";
                        }
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_NEGATIVE_KEYWORDS']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_negative_keywords', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'pd.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "products_negative_keywords like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "products_negative_keywords like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "products_negative_keywords like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_TITLE']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_meta_title', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'pd.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "products_meta_title like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "products_meta_title like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "products_meta_title like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_KEYWORDS']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_meta_keywords', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'pd.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "products_meta_keywords like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "products_meta_keywords like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "products_meta_keywords like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_DESCRIPTION']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_meta_description', $this->get['tx_multishop_pi1']['search_by']))) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'pd.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "products_meta_description like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "products_meta_description like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "products_meta_description like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                if ($this->ms['MODULES']['FLAT_DATABASE']) {
                    $tbl = 'pf.';
                } else {
                    $tbl = 'pd.';
                }
                if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                    $extra_filter[] = $tbl . "products_description like '%" . addslashes($this->get['skeyword']) . "'";
                } else {
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                        $extra_filter[] = $tbl . "products_description like '" . addslashes($this->get['skeyword']) . "%'";
                    } else {
                        $extra_filter[] = $tbl . "products_description like '%" . addslashes($this->get['skeyword']) . "%'";
                    }
                }
            }
            if ($this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS'] && $this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                for ($x = 1; $x <= $this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']; $x++) {
                    if ($this->ms['MODULES']['FLAT_DATABASE']) {
                        $tbl = 'pf.';
                    } else {
                        $tbl = 'pd.';
                    }
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                        $extra_filter[] = $tbl . "products_description_tab_title_" . $x . " like '%" . addslashes($this->get['skeyword']) . "'";
                        $extra_filter[] = $tbl . "products_description_tab_content_" . $x . " like '%" . addslashes($this->get['skeyword']) . "'";
                    } else {
                        if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                            $extra_filter[] = $tbl . "products_description_tab_title_" . $x . " like '" . addslashes($this->get['skeyword']) . "%'";
                            $extra_filter[] = $tbl . "products_description_tab_content_" . $x . " like '" . addslashes($this->get['skeyword']) . "%'";
                        } else {
                            $extra_filter[] = $tbl . "products_description_tab_title_" . $x . " like '%" . addslashes($this->get['skeyword']) . "%'";
                            $extra_filter[] = $tbl . "products_description_tab_content_" . $x . " like '%" . addslashes($this->get['skeyword']) . "%'";
                        }
                    }
                }
            }
            // attribute values
            $search_in_option_ids = array();
            if ((!is_array($this->get['tx_multishop_pi1']['search_by']) and $this->ms['MODULES']['SEARCH_ALSO_IN_ATTRIBUTE_OPTION_IDS'])) {
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
            } else {
                if (is_array($this->get['tx_multishop_pi1']['search_by'])) {
                    if ($this->ms['MODULES']['FLAT_DATABASE']) {
                        $tbl = 'pf.';
                    } else {
                        $tbl = 'p.';
                    }
                    foreach ($this->get['tx_multishop_pi1']['search_by'] as $search_by) {
                        if (strstr($search_by, 'attributes_options_id_')) {
                            $options_id = str_replace('attributes_options_id_', '', $search_by);
                            if (is_numeric($options_id)) {
                                $search_in_option_ids[] = $options_id;
                            }
                        }
                    }
                }
            }
            if (count($search_in_option_ids)) {
                foreach ($search_in_option_ids as $options_id) {
                    if (is_numeric($options_id)) {
                        $extra_filter[] = '(' . $tbl . 'products_id IN (SELECT DISTINCT(pa.products_id) FROM tx_multishop_products_attributes pa, tx_multishop_products_options po, tx_multishop_products_options_values pov,  tx_multishop_products_options_values_to_products_options povp where pa.options_id=\'' . $options_id . '\' and pa.page_uid=\'' . $this->showCatalogFromPage . '\' and pov.products_options_values_name like \'%' . addslashes($this->get['skeyword']) . '%\' and pov.language_id=0 and pov.products_options_values_id=povp.products_options_values_id and povp.products_options_values_id=pa.options_values_id and pa.options_id=povp.products_options_id and po.language_id=pov.language_id and po.products_options_id=povp.products_options_id and po.language_id=pov.language_id))';
                    }
                }
            }
            //hook to let other plugins further manipulate the settings
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_search.php']['extendProductsSearchExtraFilterHook'])) {
                $params = array(
                        'extra_filter' => &$extra_filter
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_search.php']['extendProductsSearchExtraFilterHook'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            if (!is_array($this->get['tx_multishop_pi1']['search_by']) or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_name', $this->get['tx_multishop_pi1']['search_by']))) {
                // only search in products name / description if the search_by parameter is empty or products_name is specified
                $array = explode(" ", $this->get['skeyword']);
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
                if (!$oldsearch && $this->ms['MODULES']['ENABLE_FULLTEXT_SEARCH_IN_PRODUCTS_SEARCH']) {
                    // do fulltext search
                    $tmpstr = addslashes(mslib_befe::ms_implode(', ', $array, '"', '+', true));
                    $fields = $tbl . "products_name";
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_DESCRIPTION']) {
                        $fields .= "," . $tbl . "products_description";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_KEYWORDS']) {
                        $fields .= "," . $tbl . "products_meta_keywords";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_META_DESCRIPTION']) {
                        $fields .= "," . $tbl . "products_meta_description";
                    }
                    /*
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_ID'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_id', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                        } else {
                            $tbl='p.';
                        }
                        $fields.=",".$tbl."products_id";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_VENDOR_CODE'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('vendor_code', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                        } else {
                            $tbl='p.';
                        }
                        $fields.=",".$tbl."vendor_code";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_EAN_CODE'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('ean_code', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                        } else {
                            $tbl='p.';
                        }
                        $fields.=",".$tbl."ean_code";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_MODEL'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_model', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                        } else {
                            $tbl='p.';
                        }
                        $fields.=",".$tbl."products_model";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_SKU_CODE'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('sku_code', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                        } else {
                            $tbl='p.';
                        }
                        $fields.=",".$tbl."sku_code";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_PRODUCTS_NEGATIVE_KEYWORDS'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('products_negative_keywords', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                        } else {
                            $tbl='pd.';
                        }
                        $fields.=",".$tbl."products_negative_keywords";
                    }
                    if ($this->ms['MODULES']['SEARCH_ALSO_IN_CATEGORIES_NAME'] or (is_array($this->get['tx_multishop_pi1']['search_by']) and in_array('categories_name', $this->get['tx_multishop_pi1']['search_by']))) {
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            $tbl='pf.';
                            for ($i=0; $i<6; $i++) {
                                $tbl='pf.';
                                $fields.=",".$tbl."categories_name_".$i;
                            }
                        } else {
                            $tbl='cd.';
                            $fields.=",".$tbl."categories_name";
                        }
                    }
                    */
                    $select[] = "MATCH (" . $fields . ") AGAINST ('" . $tmpstr . "' in boolean mode) AS score";
                    if ($this->ms['MODULES']['ENABLE_HYBRID_FULLTEXT_SEARCH_IN_PRODUCTS_SEARCH']) {
                        // we have to merge normal search with match, so buffer it in seperate array
                        $matchOrFilter = array();
                        $matchOrFilter[] = "MATCH (" . $fields . ") AGAINST ('" . $tmpstr . "' in boolean mode)";
                    } else {
                        $where[] = "MATCH (" . $fields . ") AGAINST ('" . $tmpstr . "' in boolean mode)";
                    }
                    $orderby[] = 'score desc';
                }
                $keywordFilter = array();
                if ($oldsearch || $this->ms['MODULES']['ENABLE_HYBRID_FULLTEXT_SEARCH_IN_PRODUCTS_SEARCH']) {
                    // mix full text search with standard search
                    if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == '%keyword') {
                        // do normal indexed search
                        $keywordFilter[] = "(" . $tbl . "products_name like '%" . addslashes($this->get['skeyword']) . "' " . (count($extra_filter) ? ' OR ' . implode(' OR ', $extra_filter) : '') . ")";
                    } else {
                        if ($this->ms['MODULES']['REGULAR_SEARCH_MODE'] == 'keyword%') {
                            // do normal indexed search
                            $keywordFilter[] = "(" . $tbl . "products_name like '" . addslashes($this->get['skeyword']) . "%' " . (count($extra_filter) ? ' OR ' . implode(' OR ', $extra_filter) : '') . ")";
                        } else {
                            // do normal indexed search
                            $keywordFilter[] = "(" . $tbl . "products_name like '%" . addslashes($this->get['skeyword']) . "%' " . (count($extra_filter) ? ' OR ' . implode(' OR ', $extra_filter) : '') . ")";
                        }
                    }
                    if (is_array($matchOrFilter) && count($matchOrFilter)) {
                        $filter[] = '((' . implode(' AND ', $matchOrFilter) . ') OR (' . implode(' AND ', $keywordFilter) . '))';
                    } else {
                        $filter[] = '(' . implode(' AND ', $keywordFilter) . ')';
                    }
                }
            } else {
                $filter[] = '(' . implode(' OR ', $extra_filter) . ')';
            }
        }
        if (is_numeric($parent_id) and $parent_id > 0) {
            if ($this->ms['MODULES']['FLAT_DATABASE']) {
                $string = '(';
                for ($i = 0; $i < 4; $i++) {
                    if ($i > 0) {
                        $string .= " or ";
                    }
                    $string .= "categories_id_" . $i . " = '" . $parent_id . "'";
                }
                $string .= ')';
                if ($string) {
                    $filter[] = $string;
                }
                //
            } else {
                $cats = mslib_fe::get_subcategory_ids($parent_id);
                $cats[] = $parent_id;
                $filter[] = "p2c.categories_id IN (" . implode(",", $cats) . ")";
            }
        }
        if (is_array($price_filter)) {
            if (!$this->ms['MODULES']['FLAT_DATABASE'] and (isset($price_filter[0]) and $price_filter[1])) {
                $having[] = "(final_price >='" . $price_filter[0] . "' and final_price <='" . $price_filter[1] . "')";
            } elseif (isset($price_filter[0])) {
                $filter[] = "price_filter=" . $price_filter[0];
            }
        } elseif ($price_filter) {
            $chars = array();
            $chars[] = '>';
            $chars[] = '<';
            foreach ($chars as $char) {
                if (strstr($price_filter, $char)) {
                    $price_filter = str_replace($char, "", $price_filter);
                    if ($char == '<') {
                        $having[] = "final_price <='" . $price_filter . "'";
                    } elseif ($char == '>') {
                        $having[] = "final_price >='" . $price_filter . "'";
                    }
                }
            }
        }
        if ($this->ms['MODULES']['FLAT_DATABASE'] and count($having)) {
            $filter[] = $having[0];
            unset($having);
        }
        if (isset($this->cookie['sortbysb']) && !empty($this->cookie['sortbysb']) && isset($this->get['tx_multishop_pi1']['sortbysb']) && !empty($this->get['tx_multishop_pi1']['sortbysb'])) {
            if ($this->ms['MODULES']['FLAT_DATABASE']) {
                $tbl = 'pf.';
            } else {
                $tbl = 'p.';
            }
            switch ($this->cookie['sortbysb']) {
                case 'manufacturers_asc':
                    $select[] = '(select m.manufacturers_name from tx_multishop_manufacturers m where ' . $tbl . 'manufacturers_id=m.manufacturers_id) as product_manufacturers_name';
                    $orderby[] = "product_manufacturers_name asc";
                    break;
                case 'manufacturers_desc':
                    $select[] = '(select m.manufacturers_name from tx_multishop_manufacturers m where ' . $tbl . 'manufacturers_id=m.manufacturers_id) as product_manufacturers_name';
                    $orderby[] = "product_manufacturers_name desc";
                    break;
                case 'best_selling_asc':
                    $select[] = '(select SUM(op.qty) from tx_multishop_orders_products op where ' . $tbl . 'products_id=op.products_id) as order_total_qty';
                    $orderby[] = "order_total_qty asc";
                    break;
                case 'best_selling_desc':
                    $select[] = '(select SUM(op.qty) from tx_multishop_orders_products op where ' . $tbl . 'products_id=op.products_id) as order_total_qty';
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
            }
        }
        //$this->msDebug=1;
        $pageset = mslib_fe::getProductsPageSet($filter, $offset, $limit_per_page, $orderby, $having, $select, $where, 0, array(), array(), 'products_search', '', 0, 1, $extra_join);
        //echo $this->msDebugInfo;
        //die();
        $products = $pageset['products'];
        if ($pageset['total_rows'] > 0) {
            if ($this->get['skeyword']) {
                // send notification message to admin
                if ($GLOBALS['TSFE']->fe_user->user['username']) {
                    $customer_name = $GLOBALS['TSFE']->fe_user->user['username'];
                } else {
                    $customer_name = $this->pi_getLL('customer');
                }
                mslib_befe::storeNotificationMessage($this->pi_getLL('customer_action'), sprintf($this->pi_getLL('customer_searched_for_keywordx'), $customer_name, $this->get['skeyword']));
                // store keyword with positive results
                mslib_befe::storeProductsKeywordSearch($this->get['skeyword']);
            }
            if (!$p and $this->ms['MODULES']['DISPLAY_SPECIALS_ABOVE_PRODUCTS_LISTING']) {
                $content .= mslib_fe::SpecialsBox($this->ms['page']); // specials module
            }
            if (strstr($this->ms['MODULES']['PRODUCTS_LISTING_TYPE'], "..")) {
                die('error in PRODUCTS_LISTING_TYPE value');
            } else {
                if (strstr($this->ms['MODULES']['PRODUCTS_LISTING_TYPE'], "/")) {
                    require($this->DOCUMENT_ROOT . $this->ms['MODULES']['PRODUCTS_LISTING_TYPE'] . '.php');
                } else {
                    if (!$this->ms['MODULES']['PRODUCTS_LISTING_TYPE']) {
                        $this->ms['MODULES']['PRODUCTS_LISTING_TYPE'] = 'default';
                    }
                    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/includes/products_listing/' . $this->ms['MODULES']['PRODUCTS_LISTING_TYPE'] . '.php');
                }
            }
            // pagination
            if (!$this->hidePagination and $pageset['total_rows'] > $limit_per_page) {
                if (!isset($this->ms['MODULES']['PRODUCTS_LISTING_PAGINATION_TYPE']) || $this->ms['MODULES']['PRODUCTS_LISTING_PAGINATION_TYPE'] == 'default') {
                    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/includes/products_listing_pagination.php');
                } else {
                    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/front_pages/includes/products_listing_pagination_with_number.php');
                }
            }
            // pagination eof
        } else {
            // add PRODUCTS_SEARCH_FALLBACK_SEARCH module code here
            if (!$p and $this->get['skeyword']) {
                // send notification message to admin
                if ($GLOBALS['TSFE']->fe_user->user['username']) {
                    $customer_name = $GLOBALS['TSFE']->fe_user->user['username'];
                } else {
                    $customer_name = 'Customer';
                }
                $message = $customer_name . ' searched for: ' . $this->get['skeyword'];
                mslib_befe::storeNotificationMessage('Customer action', $message);
            }
            // store keyword with negative results
            if ($this->get['skeyword']) {
                mslib_befe::storeProductsKeywordSearch($this->get['skeyword'], 1);
            }
            $output_array['http_header'] = 'HTTP/1.0 404 Not Found';
            $notfound_content = '<div class="main-heading"><h2>' . $this->pi_getLL('no_products_found_heading') . '</h2></div>' . "\n";
            $notfound_content .= '<p>' . $this->pi_getLL('no_products_found_description') . '</p>' . "\n";
        }
    }
    //hook to let other plugins further manipulate the settings
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_search.php']['extendProductsSearchPostHook'])) {
        $params = array(
                'content' => &$content,
                'output_array' => &$output_array,
                'products' => $products,
                'pageset' => $pageset,
                'notfound_content' => &$notfound_content,
                'extendsearch_content' => &$extendsearch_content
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_search.php']['extendProductsSearchPostHook'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    if (isset($output_array['http_header']) && $output_array['http_header'] == 'HTTP/1.0 404 Not Found') {
        $content .= $notfound_content;
    }
    if ($this->ms['MODULES']['CACHE_FRONT_END']) {
        $output_array['content'] = $content;
        $Cache_Lite->save(serialize($output_array));
    }
} elseif ($output_array) {
    $output_array = unserialize($output_array);
    if ($output_array['http_header']) {
        if (!is_array($output_array['http_header'])) {
            header($output_array['http_header']);
        } else {
            foreach ($output_array['http_header'] as $http_header) {
                header($http_header);
            }
        }
    }
    $content .= $output_array['content'];
}
if (is_array($output_array['meta']) && count($output_array['meta'])) {
    $GLOBALS['TSFE']->additionalHeaderData = array_merge($GLOBALS['TSFE']->additionalHeaderData, $output_array['meta']);
}
unset($output_array);
