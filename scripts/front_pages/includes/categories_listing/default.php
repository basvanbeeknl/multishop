<?php
$output = array();
// now parse all the objects in the tmpl file
if ($this->conf['categories_listing_tmpl_path']) {
    $template = $this->cObj->fileResource($this->conf['categories_listing_tmpl_path']);
} else {
    $template = $this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/categories_listing.tmpl');
}
// Extract the subparts from the template
$subparts = array();
$subparts['template'] = $this->cObj->getSubpart($template, '###TEMPLATE###');
$subparts['item'] = $this->cObj->getSubpart($subparts['template'], '###ITEM###');
// load optional cms content and show the current category name
if (is_array($current) && $current['content']) {
    //$output['categories_header_description']=mslib_fe::htmlBox($current['categories_name'], $current['content'], 1);
    $output['categories_header_description'] = '<div class="msCategoriesHeaderDescription">' . mslib_fe::htmlBox('', $current['content'], 1) . '</div>';
}
$output['categories_header'] = '';
if (is_array($current) && $current['categories_name']) {
    $output['categories_header'] = '<h1>' . trim($current['categories_name']) . '</h1>';
}
if ($current['categories_id'] == $this->conf['categoriesStartingPoint'] and $this->hideHeader) {
    $output['categories_header'] = '';
}
if (is_array($categories) && count($categories)) {
    // load optional cms content and show the current category name eof
    $counter = 0;
    $contentItem = '';
    foreach ($categories as $category) {
        $counter++;
        $output['categories_name'] = trim($category['categories_name']);
        if ($category['categories_image']) {
            $output['image'] = '<img src="' . mslib_befe::getImagePath($category['categories_image'], 'categories', 'normal') . '" alt="' . htmlspecialchars($category['categories_name']) . '">';
            $output['image_url'] = mslib_befe::getImagePath($category['categories_image'], 'categories', 'normal');
        } else {
            $output['image'] = '<div class="no_image"></div>';
            $output['image_url'] = '';
        }
        // get all cats to generate multilevel fake url
        $level = 0;
        $cats = mslib_fe::Crumbar($category['categories_id']);
        $cats = array_reverse($cats);
        $where = '';
        if (count($cats) > 0) {
            foreach ($cats as $item) {
                $where .= "categories_id[" . $level . "]=" . $item['id'] . "&";
                $level++;
            }
            $where = substr($where, 0, (strlen($where) - 1));
        }
        // get all cats to generate multilevel fake url eof
        if ($category['categories_external_url']) {
            if (!preg_match('/^(http|https):\/\//', $category['categories_external_url'])) {
                $category['categories_external_url'] = 'http://' . $category['categories_external_url'];
            }
            $link_parse_url = parse_url($category['categories_external_url']);
            if (isset($link_parse_url['host']) && !empty($link_parse_url['host'])) {
                if (strpos($this->FULL_HTTP_URL, $link_parse_url['host']) === false) {
                    $output['target'] = ' target="_blank"';
                } else {
                    $output['target'] = '';
                }
            } else {
                $output['target'] = '';
            }
            $output['link'] = $category['categories_external_url'];
        } else {
            $output['target'] = "";
            $output['link'] = mslib_fe::typolink($this->conf['products_listing_page_pid'], $where . '&tx_multishop_pi1[page_section]=products_listing');
        }
        $output['categories_counter'] = $counter;
        if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
            $output['categories_admin_sortable_id'] = ' id="sortable_subcat_' . $category['categories_id'] . '" ';
            $output['admin_icons'] = '<div class="admin_menu"><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_category&cid=' . $category['categories_id'] . '&action=edit_category', 1) . '" class="admin_menu_edit"><i class="fa fa-pencil"></i></a><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=delete_category&cid=' . $category['categories_id'] . '&action=delete_category', 1) . '" class="admin_menu_remove" title="Remove"><i class="fa fa-trash-o"></i></a></div>';
        }
        $markerArray = array();
        $markerArray['ADMIN_ICONS'] = $output['admin_icons'];
        $markerArray['CATEGORIES_ADMIN_SORTABLE_ID'] = $output['categories_admin_sortable_id'];
        $markerArray['CATEGORIES_LINK'] = $output['link'];
        $markerArray['CATEGORIES_COUNTER'] = $output['categories_counter'];
        $markerArray['CATEGORIES_NAME'] = $output['categories_name'];
        $markerArray['CATEGORIES_LINK_TARGET'] = $output['target'];
        $markerArray['CATEGORIES_IMAGE'] = $output['image'];
        $markerArray['CATEGORIES_IMAGE_URL'] = $output['image_url'];
        $markerArray['CATEGORIES_META_DESCRIPTION'] = $category['meta_description'];
        $markerArray['CATEGORIES_META_KEYWORDS'] = $category['meta_keywords'];
        $markerArray['CATEGORIES_META_TITLE'] = $category['meta_title'];
        // custom hook that can be controlled by third-party plugin
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/categories_listing.php']['categoriesListingRecordHook'])) {
            $params = array(
                    'markerArray' => &$markerArray,
                    'product' => &$current_product,
                    'output' => &$output,
                    'products_compare' => &$products_compare,
                    'output_array' => &$output_array
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/categories_listing.php']['categoriesListingRecordHook'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // custom hook that can be controlled by third-party plugin eof
        $contentItem .= $this->cObj->substituteMarkerArray($subparts['item'], $markerArray, '###|###');
    }
    if ($current['content_footer']) {
        $output['categories_footer_description'] = '<div class="msCategoriesFooterDescription">' . mslib_fe::htmlBox('', $current['content_footer'], 2) . '</div>';
    }
    // fill the row marker with the expanded rows
    $subpartArray['###CURRENT_CATEGORIES_NAME###'] = $output['categories_header'];
    $subpartArray['###CATEGORIES_HEADER_DESCRIPTION###'] = $output['categories_header_description'];
    $subpartArray['###CATEGORIES_FOOTER_DESCRIPTION###'] = $output['categories_footer_description'];
    $subpartArray['###ITEM###'] = $contentItem;
    // completed the template expansion by replacing the "item" marker in the template
    // custom hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/categories_listing.php']['categoriesListingPagePostHook'])) {
        $params = array(
                'subpartArray' => &$subpartArray,
                'current' => &$current,
                'output_array' => &$output_array
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/includes/categories_listing.php']['categoriesListingPagePostHook'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    // custom hook that can be controlled by third-party plugin eof
    $content .= $this->cObj->substituteMarkerArrayCached($subparts['template'], null, $subpartArray);
} else {
    header('HTTP/1.0 404 Not Found');
    // set custom 404 message
    $page = mslib_fe::getCMScontent('category_not_found_message', $GLOBALS['TSFE']->sys_language_uid);
    if ($page[0]['name']) {
        $content = '<div class="main-title"><h1>' . $page[0]['name'] . '</h1></div>';
    } else {
        $content = '<div class="main-title"><h1>' . $this->pi_getLL('the_requested_category_does_not_exist') . '</h1></div>';
    }
    if ($page[0]['content']) {
        $content .= $page[0]['content'];
    }
}
?>