<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$output = array();
$js_detail_page_triggers = array();
if ($this->ADMIN_USER) {
    $include_disabled_products = 1;
} else {
    $include_disabled_products = 0;
}
$product = mslib_fe::getProduct($this->get['products_id'], $this->get['categories_id'], '', $include_disabled_products);
if (!$product['products_id']) {
    if ($this->ROOTADMIN_USER || ($this->ADMIN_USER && $this->CATALOGADMIN_USER)) {
        $redirect_product = mslib_fe::getProduct($this->get['products_id'], '', '', 1);
        if ($redirect_product['products_id'] && $redirect_product['categories_id']) {
            if ($redirect_product['categories_id']) {
                // get all cats to generate multilevel fake url
                $level = 0;
                $cats = mslib_fe::Crumbar($redirect_product['categories_id']);
                $cats = array_reverse($cats);
                $where = '';
                if (count($cats) > 0) {
                    foreach ($cats as $cat) {
                        $where .= "categories_id[" . $level . "]=" . $cat['id'] . "&";
                        $level++;
                    }
                    $where = substr($where, 0, (strlen($where) - 1));
                    $where .= '&';
                    //
                    header("Location: " . $this->FULL_HTTP_URL . mslib_fe::typolink($this->conf['products_detail_page_pid'], '&' . $where . '&products_id=' . $this->get['products_id'] . '&tx_multishop_pi1[page_section]=products_detail'));
                    exit();
                }
                // get all cats to generate multilevel fake url eof
            }
        }
    }
    header('HTTP/1.0 404 Not Found');
    $output_array['http_header'] = 'HTTP/1.0 404 Not Found';
    // set custom 404 message
    $page = mslib_fe::getCMScontent('product_not_found_message', $GLOBALS['TSFE']->sys_language_uid);
    if ($page[0]['name']) {
        $content = '<div class="main-title"><h1>' . $page[0]['name'] . '</h1></div>';
    } else {
        $content = '<div class="main-title"><h1>' . $this->pi_getLL('the_requested_product_does_not_exist') . '</h1></div>';
    }
    if ($page[0]['content']) {
        $content .= $page[0]['content'];
    }
} else {
    if ($this->conf['imageWidth']) {
        $this->imageWidth = $this->conf['imageWidth'];
    }
    if (!$this->imageWidth) {
        $this->imageWidth = '300';
    }
    if ($this->conf['imageWidthExtraImages']) {
        $this->imageWidthExtraImages = $this->conf['imageWidthExtraImages'];
    }
    if (!$this->imageWidthExtraImages) {
        $this->imageWidthExtraImages = '50';
    }
    $qty = 1;
    if ($product['minimum_quantity'] > 0) {
        $qty = round($product['minimum_quantity'], 2);
    }
    if (!$this->conf['disableMetatags']) {
        // meta tags
        if ($product['products_meta_title']) {
            $this->ms['title'] = $product['products_meta_title'];
            $output_array['meta']['title'] = '<title>' . htmlspecialchars($this->ms['title']) . '</title>';
        } else {
            $this->ms['title'] = $product['products_name'];
            $output_array['meta']['title'] = '<title>' . htmlspecialchars($this->ms['title']) . $this->ms['MODULES']['PAGE_TITLE_DELIMETER'] . $this->ms['MODULES']['STORE_NAME'] . '</title>';
        }
        if ($product['products_meta_description']) {
            $this->ms['description'] = $product['products_meta_description'];
        } else {
            if ($product['products_shortdescription']) {
                $this->ms['description'] = $product['products_shortdescription'];
            } else {
                $this->ms['description'] = '';
            }
        }
        //Product information: '.$product['products_name'].'. Order now!
        if ($this->ms['description']) {
            $output_array['meta']['description'] = '<meta name="description" content="' . htmlspecialchars($this->ms['description']) . '" />';
        }
        if ($product['products_meta_keywords']) {
            $output_array['meta']['keywords'] = '<meta name="keywords" content="' . htmlspecialchars($product['products_meta_keywords']) . '" />';
        }
        // meta tags eof
    }
    // facebook image and open graph
    $where = '';
    if ($product['categories_id']) {
        // get all cats to generate multilevel fake url
        $level = 0;
        $cats = array();
        $cats = mslib_fe::Crumbar($product['categories_id']);
        $cats = array_reverse($cats);
        $where = '';
        if (count($cats) > 0) {
            foreach ($cats as $cat) {
                $where .= "categories_id[" . $level . "]=" . $cat['id'] . "&";
                $level++;
            }
            $where = substr($where, 0, (strlen($where) - 1));
        }
        // get all cats to generate multilevel fake url eof
    }
    $link = mslib_fe::typolink($this->conf['products_detail_page_pid'], $where . '&products_id=' . $product['products_id'] . '&tx_multishop_pi1[page_section]=products_detail');
    $productLink = $link;
    $imgUrl = '';
    $output_array['meta']['facebook'] = '';
    if ($product['products_image']) {
        $imgUrl = $this->FULL_HTTP_URL . mslib_befe::getImagePath($product['products_image'], 'products', '300');
        $output_array['meta']['facebook'] .= '<link rel="image_src" href="' . $imgUrl . '" />
		<meta property="og:image" content="' . $imgUrl . '" />';
    }
    $output_array['meta']['facebook'] .= '<meta property="og:title" content="' . htmlspecialchars($product['products_name']) . '" />
	<meta property="og:type" content="product" />
	' . ($product['products_date_added'] ? '<meta property="article:published_time" content="' . date("Y-m-d", $product['products_date_added']) . '" />' : '') . '
	' . ($product['products_date_modified'] ? '<meta property="article:modified_time" content="' . date("Y-m-d", $product['products_date_modified']) . '" />' : '') . '
	<meta property="og:url" content="' . $this->FULL_HTTP_URL . $link . '" />';
    // facebook image and open graph eof
    // putting the product vars in an array which will be marked and replaced in dynamic tmpl file
    // products pagination module
    if ($this->ms['MODULES']['PRODUCTS_DETAIL_PAGE_PAGINATION']) {
        // get previous / next record
        $pagination_items = mslib_fe::getNextPreviousProduct($product['products_id'], $product['categories_id']);
        $pagination .= '<div id="products_detail_pagination">';
        if ($pagination_items['previous_item']['link']) {
            $pagination .= '<div class="pagination_previous"><a href="' . $pagination_items['previous_item']['link'] . '">' . $this->pi_getLL('previous') . '</a></div>';
        } else {
            $pagination .= '<div class="pagination_previous_disabled"><span>' . $this->pi_getLL('previous') . '</span></div>';
        }
        if ($pagination_items['next_item']['link']) {
            $pagination .= '<div class="pagination_next"><a href="' . $pagination_items['next_item']['link'] . '">' . $this->pi_getLL('next') . '</a></div>';
        } else {
            $pagination .= '<div class="pagination_next_disabled"><span>' . $this->pi_getLL('next') . '</span></div>';
        }
        $pagination .= '</div>';
        $output['pagination'] = $pagination;
    }
    // products pagination module eof
    $output['products_name'] .= $product['products_name'];
    $output['products_name_marker'] = $product['products_name'];
    $output['admin_link'] = '';
    if ($this->ROOTADMIN_USER || ($this->ADMIN_USER && $this->CATALOGADMIN_USER)) {
        $output['admin_link'] = '<div class="admin_menu"><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_product&cid=' . $product['categories_id'] . '&pid=' . $product['products_id'] . '&action=edit_product', 1) . '" class="admin_menu_edit"><i class="fa fa-pencil"></i></a> <a href="' . mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=delete_product&cid=' . $product['categories_id'] . '&pid=' . $product['products_id'] . '&action=delete_product&cid=' . $product['categories_id'], 1) . '" class="admin_menu_remove" title="Remove"><i class="fa fa-trash-o"></i></a></div>';
        $output['products_name'] .= $output['admin_link'];
    }
    $final_price = mslib_fe::final_products_price($product);
    if ($product['tax_id']) {
        $tax = mslib_fe::getTaxById($product['tax_id']);
        if ($tax) {
            if ($product['staffel_price'] > 0) {
                $price_excl_vat = (mslib_fe::calculateStaffelPrice($product['staffel_price'], $qty) / $qty);
            } else {
                $price_excl_vat = $product['final_price'];
            }
            //$price_excl_vat=mslib_fe::amount2Cents($price_excl_vat);
        }
        if ($product['tax_id'] && $this->ms['MODULES']['SHOW_PRICES_WITH_AND_WITHOUT_VAT']) {
            if ($tax) {
                $sub_content .= '<div class="price_excluding_vat">' . $this->pi_getLL('excluding_vat') . ' ' . mslib_fe::amount2Cents($price_excl_vat) . '</div>';
            }
        }
    }
    $staffel_price_hid = '';
    if ($product['staffel_price'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
        $staffel_price_hid = '<input type="hidden" name="staffel_price" id="staffel_price" value="' . $product['staffel_price'] . '" readonly/>';
    }
    $output['products_price'] = '<div class="price_div">';
    if ($product['products_price'] <> $product['final_price']) {
        if (!$this->ms['MODULES']['DB_PRICES_INCLUDE_VAT'] && ($product['tax_rate'] && $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'])) {
            $old_price = $product['products_price'] * (1 + $product['tax_rate']);
        } else {
            $old_price = $product['products_price'];
        }
        if ($old_price) {
            $output['products_price'] .= '<div class="old_price">' . mslib_fe::amount2Cents($old_price) . '</div>';
            $output['old_price_with_container'] = '<div class="old_price">' . mslib_fe::amount2Cents($old_price) . '</div>';
        }
        $output['products_price'] .= '<input type="hidden" name="price_hid" id="price_default" value="' . $final_price . '"/>
		' . $staffel_price_hid . '
		<div class="specials_price">' . mslib_fe::amount2Cents($final_price) . '</div>';
    } else {
        $output['products_price'] .= '<input type="hidden" name="price_hid" id="price_default" value="' . $final_price . '"/>
	  	<input type="hidden" name="price" id="price" value="' . $final_price . '" readonly/>
		' . $staffel_price_hid . '
	  	<div class="specials_price">' . mslib_fe::amount2Cents($final_price) . '</div>';
    }
    $output['products_price'] .= $sub_content . '</div>';
    // staffel price table
    $output['products_staffel_price_table'] = '';
    if ($product['staffel_price'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE'] && $this->ms['MODULES']['STAFFEL_PRICE_MODULE']) {
        $staffels = explode(';', $product['staffel_price']);
        $staffel_table_content = '<div class="staffel_price_table_wrapper">';
        $staffel_table_content .= '<table width="100%" cellpadding="2" cellspacing="0">';
        $staffel_table_content .= '<tr>';
        $staffel_table_content .= '<th class="staffel_list_qty_header">' . $this->pi_getLL('qty') . '</th>';
        $staffel_table_content .= '<th class="staffel_list_price_header">' . $this->pi_getLL('price') . '</th>';
        $staffel_table_content .= '</tr>';
        foreach ($staffels as $staffel_data) {
            if (!isset($tr_type) || $tr_type == 'even') {
                $tr_type = 'odd';
            } else {
                $tr_type = 'even';
            }
            list($staffel_qty, $staffel_price) = explode(':', $staffel_data);
            if (strpos($staffel_qty, '99999') !== false) {
                list($qty_1, $qty_2) = explode('-', $staffel_qty);
                $staffel_qty = '> ' . $qty_1;
            }
            $staffel_table_content .= '<tr class="' . $tr_type . '">';
            $staffel_table_content .= '<td class="staffel_list_qty">' . str_replace('-', ' - ', $staffel_qty) . '</td>';
            $staffel_table_content .= '<td class="staffel_list_price">' . mslib_fe::amount2Cents($staffel_price) . '</td>';
            $staffel_table_content .= '</tr>';
        }
        $staffel_table_content .= '</table>';
        $staffel_table_content .= '</div>';
        $output['products_staffel_price_table'] = $staffel_table_content;
    }
    // show selectbox by products multiplication or show default input
    if ($this->get['tx_multishop_pi1']['cart_item']) {
        require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_cart.php');
        $mslib_cart = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
        $mslib_cart->init($this);
        $cart = $mslib_cart->getCart();
        //$cart = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
        $qty = $cart['products'][$this->get['tx_multishop_pi1']['cart_item']]['qty'];
    }
    $quantity_html = '';
    //if ($product['maximum_quantity']>0 || (is_numeric($product['products_multiplication']) && $product['products_multiplication']>0)) {
    /*if ($product['maximum_quantity']>0) {
        if ($product['maximum_quantity']>0) {
            $ending_number=$product['maximum_quantity'];
        }
        if ($product['minimum_quantity']>0) {
            $start_number=$product['minimum_quantity'];
        } else {
            if ($product['products_multiplication']) {
                $start_number=$product['products_multiplication'];
            }
        }
        if (!$start_number) {
            $start_number=1;
        }
        $quantity_html.='<select name="quantity" id="quantity">';
        $count=0;
        $steps=10;
        if ($product['maximum_quantity'] && $product['products_multiplication']>0) {
            $steps=floor($product['maximum_quantity']/$product['products_multiplication']);
        } else {
            if ($product['maximum_quantity'] && !$product['products_multiplication']) {
                $steps=($ending_number-$start_number)+1;
            }
        }
        $count=$start_number;
        for ($i=0; $i<$steps; $i++) {
            if ($product['products_multiplication']) {
                $item=$product['products_multiplication'];
            } else {
                if ($i) {
                    $item=1;
                }
            }
            $quantity_html.='<option value="'.$count.'"'.($qty==$count ? ' selected' : '').'>'.$count.'</option>';
            $count=($count+$item);
        }
        $quantity_html.='</select>';
    } else {
        $quantity_html.='<div class="quantity buttons_added" style=""><input type="button" value="-" class="qty_minus"><input type="text" name="quantity" size="5" id="quantity" value="'.$qty.'" /><input type="button" value="+" class="qty_plus"></div>';
    }*/
    $quantity_html = '<div class="quantity buttons_added"><input type="button" value="-" class="qty_minus"><input type="text" name="quantity" size="5" id="quantity" value="' . $qty . '" /><input type="button" value="+" class="qty_plus"></div>';
    // show selectbox by products multiplication or show default input eof
    $output['quantity'] = '
	<div class="quantity">
		<label>' . $this->pi_getLL('quantity') . '</label>
		' . $quantity_html . '
	</div>';
    $output['back_button'] = '<a href="#" onClick="history.back();return false;" class="back_button msFrontButton backState arrowLeft arrowPosLeft"><span>' . $this->pi_getLL('back') . '</span></a>';
    $product_qty = $product['products_quantity'];
    if ($this->ms['MODULES']['SHOW_STOCK_LEVEL_AS_BOOLEAN'] != 'no') {
        switch ($this->ms['MODULES']['SHOW_STOCK_LEVEL_AS_BOOLEAN']) {
            case 'yes_with_image':
                if ($product_qty) {
                    $product_qty = '<div class="products_stock"><span class="stock_label">' . $this->pi_getLL('stock') . ':</span><img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/images/icons/status_green.png" alt="' . htmlspecialchars($this->pi_getLL('in_stock')) . '" /></div>';
                } else {
                    $product_qty = '<div class="products_stock"><span class="stock_label">' . $this->pi_getLL('stock') . ':</span><img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/images/icons/status_red.png" alt="' . htmlspecialchars($this->pi_getLL('not_in_stock')) . '" /></div>';
                }
                break;
            case 'yes_without_image':
                if ($product_qty) {
                    $product_qty = '<div class="products_stock"><span class="stock_label">' . $this->pi_getLL('stock') . ':</span><span class="stock_value">' . $this->pi_getLL('admin_yes') . '</span></div>';
                } else {
                    $product_qty = '<div class="products_stock"><span class="stock_label">' . $this->pi_getLL('stock') . ':</span><span class="stock_value">' . $this->pi_getLL('admin_no') . '</span></div>';
                }
                break;
        }
    }
    $output['products_quantity'] = $product_qty;
    $output['products_category'] = 'Category: ' . $product['categories_name'];
    $output['customers_also_bought'] = mslib_fe::getProductRelativesBox($product, 'customers_also_bought');
    $output['PRODUCTS_SHORT_DESCRIPTION'] = $product['products_shortdescription'];
    $output['products_description'] = $product['products_description'];
    $output['products_extra_description'] = $product['products_extra_description'];
    $output['products_image'] = '<div class="image">';
    if ($product['products_image']) {
        $image = '<a id="thumb_0" rel="' . $this->conf['jQueryPopup_rel'] . '" class="' . $this->conf['jQueryPopup_rel'] . '" href="' . mslib_befe::getImagePath($product['products_image'], 'products', 'normal') . '"><img src="' . mslib_befe::getImagePath($product['products_image'], 'products', $this->imageWidth) . '" itemprop="image"></a>';
    } else {
        $image = '<div class="no_image"></div>';
    }
    $output['products_image'] .= $image . '</div>';
    $tmpoutput = '';
    for ($i = 1; $i < $this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $i++) {
        if ($product['products_image' . $i]) {
            $tmpoutput .= '<li><div class="listing_item">';
            $tmpoutput .= '<a id="thumb_' . $i . '" rel="' . $this->conf['jQueryPopup_rel'] . '" class="' . $this->conf['jQueryPopup_rel'] . '" href="' . mslib_befe::getImagePath($product['products_image' . $i], 'products', 'normal') . '"><img src="' . mslib_befe::getImagePath($product['products_image' . $i], 'products', $this->imageWidthExtraImages) . '"></a>';
            $tmpoutput .= '</div></li>';
        }
    }
    if ($tmpoutput) {
        $output['products_image_more'] .= '<div class="more_product_images"><ul>' . $tmpoutput . '</ul></div>';
    }
    // loading the attributes
    $output['product_attributes'] = mslib_fe::showAttributes($product['products_id'], $product['tax_rate']);
    // loading the attributes eof
    // add to basket
    if (($this->ROOTADMIN_USER || ($this->ADMIN_USER && $this->CATALOGADMIN_USER)) && !$product['products_status'] && !$this->ms['MODULES']['FLAT_DATABASE']) {
        $order_now_button .= '<input id="multishop_add_to_cart" class="disabled" name="Submit" type="button" value="' . htmlspecialchars($this->pi_getLL('disabled_product', 'disabled product')) . '" />';
    } else {
        if ($product['products_quantity'] < 1) {
            if ($this->ms['MODULES']['ALLOW_ORDER_OUT_OF_STOCK_PRODUCT']) {
                $order_now_button .= '<input id="multishop_add_to_cart" name="Submit" type="submit" value="' . htmlspecialchars($this->pi_getLL('add_to_basket')) . '" />';
            } else {
                $order_now_button .= '<input id="multishop_add_to_cart" class="disabled" name="Submit" type="button" value="' . htmlspecialchars($this->pi_getLL('disabled_product', 'disabled product')) . '" />';
            }
        } else {
            $order_now_button .= '<input id="multishop_add_to_cart" name="Submit" type="submit" value="' . htmlspecialchars($this->pi_getLL('add_to_basket')) . '" />';
        }
    }
    $output['add_to_cart_button'] .= '<span class="msFrontButton continueState arrowRight arrowPosLeft"><input name="categories_id" id="categories_id" type="hidden" value="' . $product['categories_id'] . '" /><input name="products_id" id="products_id" type="hidden" value="' . $product['products_id'] . '" />' . $order_now_button . '</span>';
    // add to basket eof
    // now parse all the objects in the tmpl file
    if ($this->conf['product_detail_tmpl_path']) {
        $template = $this->cObj->fileResource($this->conf['product_detail_tmpl_path']);
    } else {
        $template = $this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/products_detail.tmpl');
    }
    $markerArray['###CONTENT###'] = $output['content'];
    $markerArray['###PAGINATION###'] = $output['pagination'];
    $markerArray['###STOCK###'] = $output['products_quantity'];
    $markerArray['###PRODUCTS_QUANTITY###'] = number_format(round($product['products_quantity'], 2), 0, '', '.');
    if ($product['minimum_quantity'] == '0') {
        $product['minimum_quantity'] = 1;
    }
    $markerArray['###PRODUCTS_MINIMUM_QUANTITY###'] = number_format(round($product['minimum_quantity'], 2), 0, '', '.');
    $markerArray['###PRODUCTS_MAXIMUM_QUANTITY###'] = number_format(round($product['maximum_quantity'], 2), 0, '', '.');
    $markerArray['###PRODUCTS_MULTIPLICATION###'] = number_format(round($product['products_multiplication'], 2), 0, '', '.');
    $markerArray['###PRODUCTS_NAME###'] = $output['products_name'];
    if (strstr($template, '###PRODUCTS_RELATIVES_BY_CATEGORY###')) {
        $markerArray['###PRODUCTS_RELATIVES_BY_CATEGORY###'] = mslib_fe::getProductRelativesBox($product, 'categories_id');
    } else {
        $markerArray['###PRODUCTS_RELATIVES###'] = mslib_fe::getProductRelativesBox($product);
    }
    $markerArray['###PRODUCTS_SHORT_DESCRIPTION###'] = $output['PRODUCTS_SHORT_DESCRIPTION'];
    $markerArray['###PRODUCTS_DESCRIPTION###'] = $output['products_description'];
    $markerArray['###PRODUCTS_EXTRA_DESCRIPTION###'] = $output['products_extra_description'];
    if ($this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']) {
        for ($i = 1; $i <= $this->ms['MODULES']['PRODUCTS_DETAIL_NUMBER_OF_TABS']; $i++) {
            $markerArray['###PRODUCTS_DESCRIPTION_' . $i . '###'] = '';
            $markerArray['###PRODUCTS_DESCRIPTION_' . $i . '_TITLE###'] = '';
            if ($product['products_description_tab_content_' . $i]) {
                $markerArray['###PRODUCTS_DESCRIPTION_' . $i . '###'] = $product['products_description_tab_content_' . $i];
            }
            if ($product['products_description_tab_title_' . $i]) {
                $markerArray['###PRODUCTS_DESCRIPTION_' . $i . '_TITLE###'] = $product['products_description_tab_title_' . $i];
            }
        }
    }
    $markerArray['###PRODUCTS_CATEGORY###'] = $output['products_category'];
    $markerArray['###PRODUCTS_ATTRIBUTES###'] = $output['product_attributes'];
    $markerArray['###PRODUCTS_DELIVERY_TIME###'] = $product['delivery_time'];
    $markerArray['###PRODUCTS_MODEL###'] = $product['products_model'];
    $markerArray['###PRODUCTS_IMAGE###'] = $output['products_image'];
    $markerArray['###PRODUCTS_IMAGE_MORE###'] = $output['products_image_more'];
    $markerArray['###PRODUCTS_PRICE###'] = $output['products_price'];
    $markerArray['###PRODUCTS_PRICE_EXCLUDING_VAT###'] = '';
    if ($price_excl_vat != '') {
        $markerArray['###PRODUCTS_PRICE_EXCLUDING_VAT###'] = $price_excl_vat . ' ' . lcfirst($this->pi_getLL('excluding_vat'));
    }
    $markerArray['###PRODUCTS_STAFFEL_PRICE_TABLE###'] = $output['products_staffel_price_table'];
    $markerArray['###PRODUCTS_SKU###'] = $product['sku_code'];
    $markerArray['###PRODUCTS_EAN###'] = $product['ean_code'];
    $markerArray['###PRODUCTS_SPECIAL_PRICE###'] = $output['special_price'];
    $markerArray['###OTHER_CUSTOMERS_BOUGHT###'] = $output['customers_also_bought'];
    $markerArray['###HIDDEN_PRODUCT_ID###'] = '<input name="products_id" id="products_id" type="hidden" value="' . $product['products_id'] . '" />';
    // new
    $markerArray['###QUANTITY###'] = $output['quantity'];
    $markerArray['###OLD_PRICE###'] = mslib_fe::amount2Cents($old_price);
    $markerArray['###OLD_PRICE_WITH_CONTAINER###'] = $output['old_price_with_container'];
    $markerArray['###FINAL_PRICE###'] = mslib_fe::amount2Cents($final_price);
    $markerArray['###OLD_PRICE_PLAIN###'] = number_format($old_price, 2, ',', '.');
    $markerArray['###FINAL_PRICE_PLAIN###'] = number_format($final_price, 2, ',', '.');
    $markerArray['###OLD_PRICE_RAW###'] = number_format($old_price, 2, '.', '');
    $markerArray['###FINAL_PRICE_RAW###'] = number_format($final_price, 2, '.', '');
    $markerArray['###BACK_BUTTON###'] = $output['back_button'];
    $markerArray['###ADD_TO_CART_BUTTON###'] = $output['add_to_cart_button'];
    $markerArray['###PRODUCTS_META_DESCRIPTION###'] = $product['products_meta_description'];
    $markerArray['###PRODUCTS_META_KEYWORDS###'] = $product['products_meta_keywords'];
    $markerArray['###PRODUCTS_META_TITLE###'] = $product['products_meta_title'];
    $markerArray['###PRODUCTS_URL###'] = $product['products_url'];
    $markerArray['###PRODUCTS_ID###'] = $product['products_id'];
    $markerArray['###ORDER_UNIT_NAME###'] = $product['order_unit_name'];
    $markerArray['###MANUFACTURERS_NAME###'] = $product['manufacturers_name'];
    $markerArray['MANUFACTURERS_IMAGE'] = '';
    if ($product['manufacturers_image']) {
        $markerArray['###MANUFACTURERS_IMAGE###'] = '<img src="' . mslib_befe::getImagePath($product['manufacturers_image'], 'manufacturers', 'normal') . '">';
    }
    $markerArray['###MANUFACTURERS_IMAGE###'] = $product['manufacturers_image'];
    $markerArray['###MICRODATA_PRICE###'] = $final_price;
    $markerArray['###PRODUCTS_NAME_MARKER###'] = $output['products_name_marker'];
    $markerArray['###CATEGORIES_NAME###'] = $product['categories_name'];
    $where = '';
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
    $markerArray['###CATEGORIES_URL###'] = mslib_fe::typolink($this->conf['products_listing_page_pid'], $where . '&tx_multishop_pi1[page_section]=products_listing');
    $formats = array();
    $formats[] = '';
    $formats[] = '100';
    $formats[] = '200';
    $formats[] = '300';
    foreach ($formats as $format) {
        if ($format) {
            $markerArray['###PRODUCTS_IMAGE_URL_' . $format . '###'] = '';
            $markerArray['###FULL_PRODUCTS_IMAGE_URL_' . $format . '###'] = '';
        } else {
            $markerArray['###PRODUCTS_IMAGE_URL###'] = '';
            $markerArray['###FULL_PRODUCTS_IMAGE_URL###'] = '';
        }
        if ($product['products_image']) {
            if ($format) {
                $markerArray['###PRODUCTS_IMAGE_URL_' . $format . '###'] = mslib_befe::getImagePath($product['products_image'], 'products', $format);
                $markerArray['###FULL_PRODUCTS_IMAGE_URL_' . $format . '###'] = $this->FULL_HTTP_URL . mslib_befe::getImagePath($product['products_image'], 'products', $format);
            } else {
                $markerArray['###PRODUCTS_IMAGE_URL###'] = mslib_befe::getImagePath($product['products_image'], 'products', $this->imageWidth);
                $markerArray['###FULL_PRODUCTS_IMAGE_URL###'] = $this->FULL_HTTP_URL . mslib_befe::getImagePath($product['products_image'], 'products', $this->imageWidth);
            }
        }
    }
    $markerArray['###CANONICAL_URL###'] = $this->FULL_HTTP_URL.$productLink;
    $markerArray['###MANUFACTURERS_ADVICE_PRICE###'] = '';
    if ($product['manufacturers_advice_price']) {
        if (!$this->ms['MODULES']['DB_PRICES_INCLUDE_VAT'] && ($product['tax_rate'] && $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'])) {
            $amount = $product['manufacturers_advice_price'] * (1 + $product['tax_rate']);
        } else {
            $amount = $product['manufacturers_advice_price'];
        }
        $markerArray['###MANUFACTURERS_ADVICE_PRICE###'] = mslib_fe::amount2Cents($amount);
    }
    $js_detail_page_triggers[] = '
		var stepSize=parseFloat(\'' . ($product['products_multiplication'] != '0.00' ? $product['products_multiplication'] : 1) . '\');
		var minQty=parseFloat(\'' . ($product['minimum_quantity'] != '0.00' ? $product['minimum_quantity'] : '1') . '\');
		var maxQty=parseFloat(\'' . ($product['maximum_quantity'] != '0.00' ? $product['maximum_quantity'] : '0') . '\');
		if ($("#quantity").val() == "") {
			$("#quantity").val(\'' . ($product['minimum_quantity'] != '0.00' ? $product['minimum_quantity'] : '1') . '\');
		}
		$(".qty_minus").click(function() {
			var qty = parseFloat($("#quantity").val());
			var new_val = 0;
			if (qty > minQty) {
				new_val = parseFloat(qty - stepSize).toFixed(2).replace(\'.00\', \'\');

			}
			if (new_val==0) {
				new_val=minQty;
			}
			$("#quantity").val(new_val);
		});
		$(".qty_plus").click(function() {
			var qty = parseFloat($("#quantity").val());
			var new_val = 0;
			if (maxQty>0) {
				new_val=qty;
				if (qty < maxQty) {
					new_val = parseFloat(qty + stepSize).toFixed(2).replace(\'.00\', \'\');
				}
				if (new_val>maxQty) {
					new_val=maxQty;
				}
			} else {
				new_val = parseFloat(qty + stepSize).toFixed(2).replace(\'.00\', \'\');
			}
			$("#quantity").val(new_val);
		});
	';
    // shipping cost popup
    if ($this->ms['MODULES']['DISPLAY_SHIPPING_COSTS_ON_PRODUCTS_DETAIL_PAGE']) {
        $markerArray['###PRODUCTS_SPECIAL_PRICE###'] .= '<div class="shipping_cost_popup_link_wrapper"><a href="#" id="show_shipping_cost_table" class="btn btn-primary" data-toggle="modal" data-target="#shippingCostsModal"><span>' . $this->pi_getLL('shipping_costs') . '</span></a></div>
		<div class="modal" id="shippingCostsModal" tabindex="-1" role="dialog" aria-labelledby="shippingCostModalTitle" aria-hidden="true">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="shippingCostModalTitle">' . $this->pi_getLL('shipping_costs') . '</h4>
			  </div>
			  <div class="modal-body"></div>
			  <div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
			  </div>
			</div>
		  </div>
		</div>
		';
        $js_detail_page_triggers[] = '
			$(\'#shippingCostsModal\').modal({
				show:false,
				backdrop:false
			});
			$(\'#shippingCostsModal\').on(\'show.bs.modal\', function (event) {
				//event.preventDefault();
				var shippingCostsModalBox = $(this);
				if (shippingCostsModalBox.find(\'.modal-body\').html()==\'\') {
					shippingCostsModalBox.find(\'.modal-body\').html(\'<div class="text-center" id="loading_icon_wrapper"><img src="typo3conf/ext/multishop/templates/images/loading.gif" id="loading_icon" />&nbsp;Loading...</div>\');
					jQuery.ajax({
						url: \'' . mslib_fe::typolink('', 'type=2002&tx_multishop_pi1[page_section]=get_product_shippingcost_overview') . '\',
						data: \'tx_multishop_pi1[pid]=\' + $("#products_id").val() + \'&tx_multishop_pi1[qty]=\' + $("#quantity").val(),
						type: \'post\',
						dataType: \'json\',
						success: function (j) {
							if (j) {
								var shipping_cost_popup=\'<div class="product_shippingcost_popup_wrapper">\';
								shipping_cost_popup+=\'<div class="product_shippingcost_popup_header">' . $this->pi_getLL('product_shipping_and_handling_cost_overview') . '</div>\';
								shipping_cost_popup+=\'<div class="product_shippingcost_popup_table_wrapper">\';
								shipping_cost_popup+=\'<table id="product_shippingcost_popup_table" class="table table-striped">\';
								shipping_cost_popup+=\'<tr>\';
								shipping_cost_popup+=\'<td colspan="3" class="product_shippingcost_popup_table_product_name">\' + j.products_name + \'</td>\';
								shipping_cost_popup+=\'</tr>\';
								shipping_cost_popup+=\'<tr>\';
								shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_left_col">' . $this->pi_getLL('deliver_in') . '</td>\';
								shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_center_col">' . $this->pi_getLL('shipping_and_handling_cost_overview') . '</td>\';
								shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_right_col">' . $this->pi_getLL('deliver_by') . '</td>\';
								shipping_cost_popup+=\'</tr>\';
								$.each(j.shipping_costs_display, function(zone_id, shipping_cost_display) {
                                    $.each(shipping_cost_display, function(shipping_method, shipping_data) {
                                        $.each(shipping_data, function(country_iso_nr, shipping_cost) {
                                            shipping_cost_popup+=\'<tr>\';
                                            shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_left_col">\' + j.deliver_to[zone_id][shipping_method][country_iso_nr] + \'</td>\';
                                            shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_center_col">\' + shipping_cost + \'</td>\';
                                            shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_right_col">\' + j.deliver_by[zone_id][shipping_method][country_iso_nr] + \'</td>\';
                                            shipping_cost_popup+=\'</tr>\';
                                        });
                                    });
								});
								if (j.delivery_time!=\'e\') {
									shipping_cost_popup+=\'<tr>\';
									shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_left_col"><strong>' . $this->pi_getLL('admin_delivery_time') . '</strong></td>\';
									shipping_cost_popup+=\'<td class="product_shippingcost_popup_table_left_col" colspan="2">\' + j.delivery_time + \'</td>\';
									shipping_cost_popup+=\'</tr>\';
								}
								shipping_cost_popup+=\'</table>\';
								shipping_cost_popup+=\'</div>\';
								shipping_cost_popup+=\'</div>\';
								//modalBox.find(\'.modal-title\').html(' . $this->pi_getLL('product_shipping_and_handling_cost_overview') . ');
								shippingCostsModalBox.find(\'.modal-body\').empty();
								shippingCostsModalBox.find(\'.modal-body\').html(shipping_cost_popup);
								//msDialog("' . $this->pi_getLL('shipping_costs') . '", shipping_cost_popup, 650);
							}
						}
					});
				}
			});
		';
    }
    $plugins_extra_content = array();
    // custom hook that can be controlled by third-party plugin
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPagePostHook'])) {
        $params = array(
                'template' => $template,
                'markerArray' => &$markerArray,
                'product' => &$product,
                'output' => &$output,
                'output_array' => &$output_array,
                'plugins_extra_content' => &$plugins_extra_content,
                'js_detail_page_triggers' => &$js_detail_page_triggers
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/front_pages/products_detail.php']['productsDetailsPagePostHook'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    $markerArray['###PRODUCT_DETAILS_PLUGIN_EXTRA_CONTENT###'] = '';
    if (count($plugins_extra_content)) {
        $plugin_extra_content = implode("\n", $plugins_extra_content);
        $markerArray['###PRODUCT_DETAILS_PLUGIN_EXTRA_CONTENT###'] = $plugin_extra_content;
    }
    if (count($js_detail_page_triggers)) {
        $output_array['meta']['details_page_js'] = '
			<script type="text/javascript">
			jQuery(document).ready(function($) {
			    ' . implode("\n", $js_detail_page_triggers) . '
			    $(\'form#add_to_shopping_cart_form\').submit(function(e){
                    if ($(\'.attribute-value-radio\').length>0 || $(\'.attribute-value-checkbox\').length>0) {
                        var attribute_radio_data=[];
                        var attribute_checkbox_data=[];
                        var submit_form=true;
                        //
                        $(\'.required-warning-box\').hide();
                        $(\'.required-warning-box\').removeClass(\'alert\');
                        $(\'.required-warning-box\').removeClass(\'alert-danger\');
                        $(\'.required-warning-box\').empty();
                        //
                        $(\'.attribute-value-radio\').each(function(i, v){
                            if ($.inArray($(v).attr(\'rel\'), attribute_radio_data)===-1) {
                                attribute_radio_data.push($(v).attr(\'rel\'));
                            }
                        });
                        //
                        $.each(attribute_radio_data, function(x,y){
                            var attribute_class=\'.\'+y+\':checked\';
                            var warning_box_class=\'.\'+y.replace(\'attributes\', \'required-warning\');
                            if ($(attribute_class).val()==undefined) {
                                $(warning_box_class).addClass(\'alert alert-danger\');
                                $(warning_box_class).html(\'<strong>' . $this->pi_getLL('attribute_radio_option_warning') . '</strong>\');
                                $(warning_box_class).show();
                                submit_form=false;
                            }
                        });
                        //
                        $(\'.attribute-value-checkbox\').each(function(o, p){
                            if ($.inArray($(p).attr(\'rel\'), attribute_checkbox_data)===-1) {
                                attribute_checkbox_data.push($(p).attr(\'rel\'));
                            }
                        });
                        //
                        $.each(attribute_checkbox_data, function(l,m){
                            var attribute_class=\'.\'+m+\':checked\';
                            var warning_box_class=\'.\'+m.replace(\'attributes\', \'required-warning\');
                            if ($(attribute_class).val()==undefined) {
                                $(warning_box_class).addClass(\'alert alert-danger\');
                                $(warning_box_class).html(\'<strong>' . $this->pi_getLL('attribute_radio_option_warning') . '</strong>\');
                                $(warning_box_class).show();
                                submit_form=false;
                            }
                        });
                        if (submit_form) {
                            return true;
                        }
                        return false;
                    }
                });
			});
			</script>
		';
    }
    // custom hook that can be controlled by third-party plugin eof
    $content .= $output['top_content'] . '<form action="' . mslib_fe::typolink($this->conf['shoppingcart_page_pid'], '&tx_multishop_pi1[page_section]=shopping_cart&products_id=' . $product['products_id']) . '" method="post" name="shopping_cart" id="add_to_shopping_cart_form" enctype="multipart/form-data" autocomplete="off"><div id="products_detail">' . $this->cObj->substituteMarkerArray($template, $markerArray) . '</div><input name="tx_multishop_pi1[cart_item]" type="hidden" value="' . htmlspecialchars($this->get['tx_multishop_pi1']['cart_item']) . '" /></form>';
}
?>