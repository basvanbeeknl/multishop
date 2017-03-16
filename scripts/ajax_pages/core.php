<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
$this->ms['page'] = $this->get['tx_multishop_pi1']['page_section'];
switch ($this->ms['page']) {
    case 'get_users':
        $return_data = array();
        $users = mslib_fe::getUsers($this->conf['fe_customer_usergroup'], 'name');
        $counter = 0;
        if ($this->get['q']) {
            foreach ($users as $user) {
                if (strpos($user['name'], $this->get['q']) !== false) {
                    $return_data[$counter]['text'] = $user['name'];
                    $return_data[$counter]['id'] = $user['uid'];
                    $counter++;
                }
            }
        } else if ($this->get['preselected_id']) {
            $preselected_users = explode(',', $this->get['preselected_id']);
            foreach ($users as $user) {
                foreach ($preselected_users as $preselected_user) {
                    if ($user['uid'] == $preselected_user) {
                        $return_data[$counter]['text'] = $user['name'];
                        $return_data[$counter]['id'] = $user['uid'];
                        $counter++;
                    }
                }
            }
        } else {
            foreach ($users as $user) {
                $return_data[$counter]['text'] = $user['name'];
                $return_data[$counter]['id'] = $user['uid'];
                $counter++;
            }
        }
        echo json_encode($return_data);
        exit();
        break;
    case 'get_shoppingcart_shippingmethod_overview':
        $return_data = array();
        $country_cn_iso_nr = $this->post['tx_multishop_pi1']['country_id'];
        //
        //$cart=$GLOBALS['TSFE']->fe_user->getKey('ses', $this->cart_page_uid);
        require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_cart.php');
        $mslib_cart = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_cart');
        $mslib_cart->init($this);
        $cart = $mslib_cart->getCart();
        $products = $cart['products'];
        $pids = array();
        foreach ($products as $product) {
            $pids[] = $product['products_id'];
        }
        $product_mappings = mslib_fe::getProductMappedMethods($pids, 'shipping', $country_cn_iso_nr);
        //
        $shipping_methods = mslib_fe::loadShippingMethods(0, $country_cn_iso_nr, true, true);
        if (!count($product_mappings)) {
            $product_mappings = $shipping_methods;
        }
        $return_data['shipping_methods'] = array();
        foreach ($shipping_methods as $shipping_method) {
            if (isset($product_mappings[$shipping_method['code']])) {
                $return_data['shipping_methods'][] = $shipping_method;
            }
        }
        echo json_encode($return_data);
        exit();
        break;
    case 'get_shoppingcart_shippingcost_overview':
        if ($this->ms['MODULES']['FORCE_CHECKOUT_SHOW_PRICES_INCLUDING_VAT'] == '1') {
            $this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT'] = 1;
        }
        $return_data = array();
        if ($this->tta_user_info['default']['country']) {
            $iso_customer = mslib_fe::getCountryByName($this->tta_user_info['default']['country']);
        } else {
            $iso_customer = $this->tta_shop_info;
        }
        if (!$iso_customer['cn_iso_nr']) {
            // fall back (had issue with admin notification)
            $iso_customer = mslib_fe::getCountryByName($this->tta_shop_info['country']);
        }
        $delivery_country_id = $this->post['tx_multishop_pi1']['country_id'];
        $shipping_method_id = $this->post['tx_multishop_pi1']['shipping_method'];
        $shipping_cost_data = mslib_fe::getShoppingcartShippingCostsOverview($iso_customer['cn_iso_nr'], $delivery_country_id, $shipping_method_id);
        $count_cart_incl_vat = 0;
        if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
            $count_cart_incl_vat = 1;
        }
        //
        $return_data['shipping_cost'] = 0;
        $return_data['shipping_costs_display'] = mslib_fe::amount2Cents(0);
        $return_data['shipping_method']['deliver_by'] = '';
        $return_data['shopping_cart_total_price'] = mslib_fe::amount2Cents(mslib_fe::countCartTotalPrice(1, $count_cart_incl_vat, $iso_customer['cn_iso_nr']));
        //
        foreach ($shipping_cost_data as $shipping_code => $shipping_cost) {
            if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
                $count_cart_incl_vat = 1;
                $return_data['shipping_cost'] = $shipping_cost['shipping_costs_including_vat'];
                $return_data['shipping_costs_display'] = mslib_fe::amount2Cents($shipping_cost['shipping_costs_including_vat']);
            } else {
                $return_data['shipping_cost'] = $shipping_cost['shipping_costs'];
                $return_data['shipping_costs_display'] = mslib_fe::amount2Cents($shipping_cost['shipping_costs']);
            }
            $return_data['shipping_method'] = $shipping_cost;
            $return_data['shopping_cart_total_price'] = mslib_fe::amount2Cents(mslib_fe::countCartTotalPrice(1, $count_cart_incl_vat, $iso_customer['cn_iso_nr']) + $return_data['shipping_cost']);
        }
        echo json_encode($return_data);
        exit();
        break;
    case 'get_product_shippingcost_overview':
        if (is_numeric($this->post['tx_multishop_pi1']['pid'])) {
            $return_data = array();
            $product_data = mslib_fe::getProduct($this->post['tx_multishop_pi1']['pid']);
            if (!$this->post['tx_multishop_pi1']['qty']) {
                $this->post['tx_multishop_pi1']['qty'] = 1;
            }
            $return_data['delivery_time'] = 'e';
            if (!empty($product_data['delivery_time'])) {
                $return_data['delivery_time'] = trim($product_data['delivery_time']);
            }
            $str2 = "SELECT * from static_countries sc, tx_multishop_countries_to_zones c2z, tx_multishop_shipping_countries c where c.page_uid='" . $this->showCatalogFromPage . "' and sc.cn_iso_nr=c.cn_iso_nr and c2z.cn_iso_nr=sc.cn_iso_nr group by c.cn_iso_nr order by c2z.zone_id asc, sc.cn_short_" . $this->lang . " asc";
            //$str2="SELECT * from static_countries c, tx_multishop_countries_to_zones c2z where c2z.cn_iso_nr=c.cn_iso_nr order by c.cn_short_en";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $enabled_countries = array();
            while (($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2)) != false) {
                $shipping_cost_data = array();
                $shipping_cost_data = mslib_fe::getProductShippingCostsOverview($row2['cn_iso_nr'], $this->post['tx_multishop_pi1']['pid'], $this->post['tx_multishop_pi1']['qty']);
                foreach ($shipping_cost_data as $shipping_code => $shipping_cost) {
                    if ($this->ms['MODULES']['SHOW_PRICES_INCLUDING_VAT']) {
                        $return_data['shipping_cost'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = $shipping_cost['shipping_costs_including_vat'];
                        $return_data['shipping_costs_display'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = mslib_fe::amount2Cents($shipping_cost['shipping_costs_including_vat']);
                    } else {
                        $return_data['shipping_cost'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = $shipping_cost['shipping_costs'];
                        $return_data['shipping_costs_display'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = mslib_fe::amount2Cents($shipping_cost['shipping_costs']);
                    }
                    $return_data['deliver_to'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = htmlspecialchars(mslib_fe::getTranslatedCountryNameByEnglishName($this->lang, $row2['cn_short_en']));
                    $return_data['deliver_by'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = $shipping_cost['deliver_by'];
                    $return_data['shipping_method'][$row2['zone_id']][$shipping_code][$row2['cn_iso_nr']] = $shipping_cost;
                    $return_data['products_name'] = $shipping_cost['product_name'];
                }
            }
            echo json_encode($return_data);
        }
        exit();
        break;
    case 'get_images_for_crop':
        switch ($this->get['tx_multishop_pi1']['crop_section']) {
            case 'manufacturers':
                $image_type = 'manufacturers';
                $image_size = 'enlarged';
                $image_size_format = 'normal';
                $image_format_key = 'manufacturer_image_formats';
                $crop_table_name = 'tx_multishop_manufacturers_crop_image_coordinate';
                break;
            case 'categories':
                $image_type = 'categories';
                $image_size = 'enlarged';
                $image_size_format = 'normal';
                $image_format_key = 'category_image_formats';
                $crop_table_name = 'tx_multishop_categories_crop_image_coordinate';
                break;
            case 'products':
            default:
                $image_type = 'products';
                $image_size = (!isset($this->post['size']) ? 300 : $this->post['size']);
                $image_size_format = (!isset($this->post['size']) ? 300 : $this->post['size']);
                $image_format_key = 'product_image_formats';
                $crop_table_name = 'tx_multishop_product_crop_image_coordinate';
                break;
        }
        $return_data = array();
        $image_name = $this->post['imagename'];
        if (!empty($image_name)) {
            $return_data['image_name'] = $image_name;
            $return_data['image_size'] = $image_size;
            $return_data['images'][$image_size] = mslib_befe::getImagePath($image_name, $image_type, 'original') . '?' . time();
            $return_data['images']['300'] = mslib_befe::getImagePath($image_name, $image_type, 'original') . '?' . time();
            $return_data['images']['normal'] = mslib_befe::getImagePath($image_name, $image_type, 'normal') . '?' . time();
            $return_data['images']['50'] = mslib_befe::getImagePath($image_name, $image_type, '50') . '?' . time();
            $image_truesize = getimagesize(mslib_befe::getImagePath($image_name, $image_type, 'original'));
            $return_data['truesize'][$image_size] = array(
                    $image_truesize[0],
                    $image_truesize[1]
            );
            $return_data['truesize'][300] = array(
                    $image_truesize[0],
                    $image_truesize[1]
            );
            //
            $return_data['aspectratio'][$image_size] = $this->ms[$image_format_key][$image_size_format]['width'] / $this->ms[$image_format_key][$image_size_format]['height'];
            $return_data['aspectratio'][300] = $this->ms[$image_format_key][300]['width'] / $this->ms[$image_format_key][300]['height'];
            // max width
            $max_width = $this->ms[$image_format_key][$image_size_format]['width']; //($this->ms[$image_format_key][$image_size_format]['width']>640 ? 640 : $this->ms[$image_format_key][$image_size_format]['width']);
            $max_height = $this->ms[$image_format_key][$image_size_format]['height']; //($this->ms[$image_format_key][$image_size_format]['height']>480 ? 480 : $this->ms[$image_format_key][$image_size_format]['height']);
            // jcrop settings
            $return_data['minsize'][$image_size] = array(
                    $max_width,
                    $max_height
            );
            $return_data['minsize'][300] = array(
                    $this->ms[$image_format_key][300]['width'],
                    $this->ms[$image_format_key][300]['height']
            );
            //
            $return_data['setselect'][$image_size] = array(
                    0,
                    0,
                    $max_width,
                    $max_height
            );
            $return_data['setselect'][300] = array(
                    0,
                    0,
                    $this->ms[$image_format_key][300]['width'],
                    $this->ms[$image_format_key][300]['height']
            );
            // check if there any crop record
            $image_data = mslib_befe::getRecord($image_name, $crop_table_name, 'image_filename', array('image_size=\'' . $image_size . '\''));
            $return_data['disable_crop_button'] = "";
            if (is_array($image_data) && isset($image_data['id']) && $image_data['id'] > 0) {
                $return_data['images'][$image_size] = mslib_befe::getImagePath($image_name, $image_type, ($image_size == 'enlarged' ? 'normal' : $image_size)) . '?t=' . time();
                $return_data['disable_crop_button'] = "disabled";
            }
            // check if all image are unresized
            $crop_all_checked = 0;
            if ($this->post['cropall'] == 'init' || $this->post['cropall'] > 0) {
                $image_size_array = array();
                $image_size_array[] = 50;
                $image_size_array[] = 100;
                $image_size_array[] = 200;
                $image_size_array[] = 300;
                $image_size_array[] = 'enlarged';
                foreach ($image_size_array as $image_size) {
                    $tmp_image_data = mslib_befe::getRecord($image_name, $crop_table_name, 'image_filename', array('image_size=\'' . $image_size . '\''));
                    if (!is_array($tmp_image_data)) {
                        $crop_all_checked += 1;
                    }
                }
                if ($crop_all_checked == 5) {
                    $return_data['crop_all_checked'] = true;
                } else {
                    $return_data['crop_all_checked'] = false;
                }
            }
            if ($image_type == 'products') {
                $image_size_array = array();
                $image_size_array[] = 50;
                $image_size_array[] = 100;
                $image_size_array[] = 200;
                $image_size_array[] = 300;
                $image_size_array[] = 'enlarged';
                $return_data['cropped_image']['thumblist_50'] = false;
                $return_data['cropped_image']['thumblist_100'] = false;
                $return_data['cropped_image']['thumblist_200'] = false;
                $return_data['cropped_image']['thumblist_300'] = false;
                $return_data['cropped_image']['thumblist_enlarged'] = false;
                foreach ($image_size_array as $image_size) {
                    $tmp_image_data = mslib_befe::getRecord($image_name, 'tx_multishop_product_crop_image_coordinate', 'image_filename', array('image_size=\'' . $image_size . '\''));
                    if (is_array($tmp_image_data)) {
                        $return_data['cropped_image']['thumblist_' . $image_size] = true;
                    }
                }
            }
            $return_data['status'] = 'OK';
        } else {
            $return_data['status'] = 'NOTOK';
        }
        echo json_encode($return_data);
        exit();
        break;
    case 'crop_product_image':
        switch ($this->get['tx_multishop_pi1']['crop_section']) {
            case 'manufacturers':
                $image_type = 'manufacturers';
                $image_size = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_size_format = 'normal';
                $image_format_key = 'manufacturer_image_formats';
                $crop_table_name = 'tx_multishop_manufacturers_crop_image_coordinate';
                $mid = (isset($this->post['mid']) ? $this->post['mid'] : 0);
                break;
            case 'categories':
                $image_type = 'categories';
                $image_size = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_size_format = 'normal';
                $image_format_key = 'category_image_formats';
                $crop_table_name = 'tx_multishop_categories_crop_image_coordinate';
                $cid = (isset($this->post['cid']) ? $this->post['cid'] : 0);
                break;
            case 'products':
            default:
                $image_type = 'products';
                $image_size = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_size_format = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_format_key = 'product_image_formats';
                $crop_table_name = 'tx_multishop_product_crop_image_coordinate';
                $pid = (isset($this->post['pid']) ? $this->post['pid'] : 0);
                break;
        }
        $return_data = array();
        $return_data['disable_crop_button'] = "";
        $image_name = $this->post['tx_multishop_pi1']['jCropImageName'];
        $image_size_array = array();
        if (!empty($image_name)) {
            $return_data['image_name'] = $image_name;
            $return_data['image_size'] = $image_size;
            $return_data['images'][$image_size] = mslib_befe::getImagePath($image_name, $image_type, ($image_size == 'enlarged' ? 'normal' : $image_size)) . '?' . time();
            $image_truesize = getimagesize(mslib_befe::getImagePath($image_name, $image_type, 'original'));
            $return_data['truesize'][$image_size] = array(
                    $image_truesize[0],
                    $image_truesize[1]
            );
            $return_data['aspectratio'][$image_size] = $this->ms[$image_format_key][$image_size_format]['width'] / $this->ms[$image_format_key][$image_size_format]['height'];
            $return_data['minsize'][$image_size] = array(
                    $this->ms[$image_format_key][$image_size_format]['width'],
                    $this->ms[$image_format_key][$image_size_format]['height']
            );
            $return_data['setselect'][$image_size] = array(
                    0,
                    0,
                    $this->ms[$image_format_key][$image_size_format]['width'],
                    $this->ms[$image_format_key][$image_size_format]['height']
            );
            $return_data['status'] = 'OK';
        } else {
            $return_data['status'] = 'NOTOK';
        }
        if ($this->post['cropall'] > 0) {
            $image_size_array[] = 50;
            $image_size_array[] = 100;
            $image_size_array[] = 200;
            $image_size_array[] = 300;
            $image_size_array[] = 'enlarged';
        } else {
            $image_size_array[] = $this->post['tx_multishop_pi1']['jCropImageSize'];
        }
        foreach ($image_size_array as $image_size) {
            if ($this->post['tx_multishop_pi1']['jCropX'] || $this->post['tx_multishop_pi1']['jCropY'] || $this->post['tx_multishop_pi1']['jCropW'] || $this->post['tx_multishop_pi1']['jCropH']) {
                $return_data['disable_crop_button'] = "disabled";
                $src_image_size = ($image_size == 'enlarged' ? 'normal' : $image_size);
                $src = $this->DOCUMENT_ROOT . mslib_befe::getImagePath($image_name, $image_type, ($image_size == 'enlarged' ? 'normal' : $image_size));
                $src_original = $this->DOCUMENT_ROOT . mslib_befe::getImagePath($image_name, $image_type, 'original');
                // backup original
                copy($src, $src . '-ori-' . $image_size);
                mslib_befe::cropImage($src, $src_original, $image_size, $this->post['tx_multishop_pi1']['jCropX'], $this->post['tx_multishop_pi1']['jCropY'], $this->post['tx_multishop_pi1']['jCropW'], $this->post['tx_multishop_pi1']['jCropH'], $image_type);
                // save to database for the coordinate
                $insertArray = array();
                if ($image_type == 'manufacturers') {
                    $insertArray['manufacturers_id'] = $mid;
                } else if ($image_type == 'categories') {
                    $insertArray['categories_id'] = $cid;
                } else {
                    $insertArray['products_id'] = $pid;
                }
                $insertArray['image_filename'] = $image_name;
                $insertArray['image_size'] = $image_size;
                $insertArray['coordinate_x'] = $this->post['tx_multishop_pi1']['jCropX'];
                $insertArray['coordinate_y'] = $this->post['tx_multishop_pi1']['jCropY'];
                $insertArray['coordinate_w'] = $this->post['tx_multishop_pi1']['jCropW'];
                $insertArray['coordinate_h'] = $this->post['tx_multishop_pi1']['jCropH'];
                $query = $GLOBALS['TYPO3_DB']->INSERTquery($crop_table_name, $insertArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
        }
        if ($image_type == 'products') {
            $image_size_array = array();
            $image_size_array[] = 50;
            $image_size_array[] = 100;
            $image_size_array[] = 200;
            $image_size_array[] = 300;
            $image_size_array[] = 'enlarged';
            $return_data['cropped_image']['thumblist_50'] = false;
            $return_data['cropped_image']['thumblist_100'] = false;
            $return_data['cropped_image']['thumblist_200'] = false;
            $return_data['cropped_image']['thumblist_300'] = false;
            $return_data['cropped_image']['thumblist_enlarged'] = false;
            foreach ($image_size_array as $image_size) {
                $tmp_image_data = mslib_befe::getRecord($image_name, 'tx_multishop_product_crop_image_coordinate', 'image_filename', array('image_size=\'' . $image_size . '\''));
                if (is_array($tmp_image_data)) {
                    $return_data['cropped_image']['thumblist_' . $image_size] = true;
                }
            }
        }
        echo json_encode($return_data);
        exit();
        break;
    case 'restore_crop_image':
        switch ($this->get['tx_multishop_pi1']['crop_section']) {
            case 'manufacturers':
                $image_type = 'manufacturers';
                $image_size = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_size_format = 'normal';
                $image_format_key = 'manufacturer_image_formats';
                $crop_table_name = 'tx_multishop_manufacturers_crop_image_coordinate';
                $mid = (isset($this->post['mid']) ? $this->post['mid'] : 0);
                break;
            case 'categories':
                $image_type = 'categories';
                $image_size = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_size_format = 'normal';
                $image_format_key = 'category_image_formats';
                $crop_table_name = 'tx_multishop_categories_crop_image_coordinate';
                $cid = (isset($this->post['cid']) ? $this->post['cid'] : 0);
                break;
            case 'products':
            default:
                $image_type = 'products';
                $image_size = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_size_format = $this->post['tx_multishop_pi1']['jCropImageSize'];
                $image_format_key = 'product_image_formats';
                $crop_table_name = 'tx_multishop_product_crop_image_coordinate';
                $pid = (isset($this->post['pid']) ? $this->post['pid'] : 0);
                break;
        }
        $return_data = array();
        $return_data['disable_crop_button'] = "";
        $image_name = $this->post['tx_multishop_pi1']['jCropImageName'];
        if (!empty($image_name)) {
            $return_data['image_name'] = $image_name;
            $return_data['image_size'] = $image_size;
            $return_data['images'][$image_size] = mslib_befe::getImagePath($image_name, $image_type, 'original') . '?' . time();
            $return_data['images']['300'] = mslib_befe::getImagePath($image_name, $image_type, 'original') . '?' . time();
            $image_truesize = getimagesize(mslib_befe::getImagePath($image_name, $image_type, 'original'));
            $return_data['truesize'][$image_size] = array(
                    $image_truesize[0],
                    $image_truesize[1]
            );
            $return_data['truesize'][300] = array(
                    $image_truesize[0],
                    $image_truesize[1]
            );
            //
            $return_data['aspectratio'][$image_size] = $this->ms[$image_format_key][$image_size_format]['width'] / $this->ms[$image_format_key][$image_size_format]['height'];
            $return_data['aspectratio'][300] = $this->ms[$image_format_key][300]['width'] / $this->ms[$image_format_key][300]['height'];
            //
            $return_data['minsize'][$image_size] = array(
                    $this->ms[$image_format_key][$image_size_format]['width'],
                    $this->ms[$image_format_key][$image_size_format]['height']
            );
            $return_data['minsize'][300] = array(
                    $this->ms[$image_format_key][300]['width'],
                    $this->ms[$image_format_key][300]['height']
            );
            //
            $return_data['setselect'][$image_size] = array(
                    0,
                    0,
                    $this->ms[$image_format_key][$image_size_format]['width'],
                    $this->ms[$image_format_key][$image_size_format]['height']
            );
            $return_data['setselect'][300] = array(
                    0,
                    0,
                    $this->ms[$image_format_key][300]['width'],
                    $this->ms[$image_format_key][300]['height']
            );
            $return_data['status'] = 'OK';
        } else {
            $return_data['status'] = 'NOTOK';
        }
        $return_data['disable_crop_button'] = "";
        $src_image_size = ($image_size == 'enlarged' ? 'normal' : $image_size);
        $src = $this->DOCUMENT_ROOT . mslib_befe::getImagePath($image_name, $image_type, $src_image_size);
        // backup original
        @unlink($src);
        copy($src . '-ori-' . $image_size, $src);
        // delete coordinate
        if ($image_type == 'products' && $pid > 0) {
            $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery($crop_table_name, 'image_filename=\'' . $image_name . '\' and image_size=\'' . $image_size . '\' and products_id=\'' . $pid . '\'');
        } else if ($image_type == 'categories' && $cid > 0) {
            $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery($crop_table_name, 'image_filename=\'' . $image_name . '\' and image_size=\'' . $image_size . '\' and categories_id=\'' . $cid . '\'');
        } else if ($image_type == 'manufacturers' && $mid > 0) {
            $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery($crop_table_name, 'image_filename=\'' . $image_name . '\' and image_size=\'' . $image_size . '\' and manufacturers_id=\'' . $mid . '\'');
        } else {
            $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery($crop_table_name, 'image_filename=\'' . $image_name . '\' and image_size=\'' . $image_size . '\'');
        }
        // check if all image are unresized
        if ($image_type == 'products') {
            $crop_all_checked = 0;
            $image_size_array = array();
            $image_size_array[] = 50;
            $image_size_array[] = 100;
            $image_size_array[] = 200;
            $image_size_array[] = 300;
            $image_size_array[] = 'enlarged';
            $return_data['cropped_image']['thumblist_50'] = false;
            $return_data['cropped_image']['thumblist_100'] = false;
            $return_data['cropped_image']['thumblist_200'] = false;
            $return_data['cropped_image']['thumblist_300'] = false;
            $return_data['cropped_image']['thumblist_enlarged'] = false;
            foreach ($image_size_array as $image_size) {
                $tmp_image_data = mslib_befe::getRecord($image_name, 'tx_multishop_product_crop_image_coordinate', 'image_filename', array('image_size=\'' . $image_size . '\''));
                if (!is_array($tmp_image_data)) {
                    $crop_all_checked += 1;
                } else {
                    $return_data['cropped_image']['thumblist_' . $image_size] = true;
                }
            }
            if ($crop_all_checked == 5) {
                $return_data['crop_all_checked'] = true;
            } else {
                $return_data['crop_all_checked'] = false;
            }
        }
        echo json_encode($return_data);
        exit();
        break;
    case 'get_category_tree':
        $page_uid = $this->showCatalogFromPage;
        if (is_numeric($this->get['tx_multishop_pi1']['page_uid'])) {
            $page_uid = $this->get['tx_multishop_pi1']['page_uid'];
        }
        $include_disabled_cats = 0;
        if (isset($this->get['tx_multishop_pi1']['includeDisabledCats']) && $this->get['tx_multishop_pi1']['includeDisabledCats'] > 0) {
            $include_disabled_cats = 1;
        }
        $return_data = array();
        $tmp_return_data = array();
        $tmp_return_data_sort = array();
        switch ($this->get['tx_multishop_pi1']['get_category_tree']) {
            case 'getValues':
                $tmp_preselecteds = array();
                if (isset($this->get['preselected_id'])) {
                    if (strpos($this->get['preselected_id'], ',') !== false) {
                        $tmp_preselecteds = explode(',', $this->get['preselected_id']);
                    } else {
                        if (is_numeric($this->get['preselected_id'])) {
                            $tmp_preselecteds[] = $this->get['preselected_id'];
                        }
                    }
                }
                if (isset($this->post['preselected_id'])) {
                    if (strpos($this->post['preselected_id'], ',') !== false) {
                        $tmp_preselecteds = explode(',', $this->post['preselected_id']);
                    } else {
                        if (is_numeric($this->post['preselected_id'])) {
                            $tmp_preselecteds[] = $this->post['preselected_id'];
                        }
                    }
                }
                if (is_array($tmp_preselecteds) && count($tmp_preselecteds)) {
                    foreach ($tmp_preselecteds as $preselected_id) {
                        $preselected_id = trim($preselected_id);
                        $cats = mslib_fe::Crumbar($preselected_id, '', array(), $page_uid);
                        $cats = array_reverse($cats);
                        $catpath = array();
                        $level = 0;
                        $where = '';
                        foreach ($cats as $cat) {
                            $where .= "categories_id[" . $level . "]=" . $cat['id'] . "&";
                            $catpath[] = $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                            $level++;
                        }
                        if (count($catpath) > 0) {
                            $cat_link = mslib_fe::typolink($this->conf['products_listing_page_pid'], $where . '&tx_multishop_pi1[page_section]=products_listing');
                            if (isset($this->get['tx_multishop_pi1']['calledFrom']) && $this->get['tx_multishop_pi1']['calledFrom'] == 'edit_product') {
                                $tmp_return_data[$preselected_id] = '<a href="' . $cat_link . '" target="_blank" class="innerLink">' . implode(' > ', $catpath) . '</a>';
                            } else {
                                $tmp_return_data[$preselected_id] = implode(' > ', $catpath);
                            }
                            $tmp_return_data_sort[$preselected_id] = implode(' > ', $catpath);;
                        }
                    }
                    if (is_array($tmp_return_data_sort) && count($tmp_return_data_sort)) {
                        natsort($tmp_return_data_sort);
                        $tmp_return_data_list = array();
                        $tmp_return_data_list = $tmp_return_data;
                        // return data
                        $tmp_return_data = array();
                        foreach ($tmp_return_data_sort as $tmp_preselected_id => $tmp_path) {
                            $tmp_return_data[$tmp_preselected_id] = $tmp_return_data_list[$tmp_preselected_id];
                        }
                    }
                }
                if (!count($tmp_preselecteds) || (count($tmp_preselecteds) === 1 && !$tmp_preselecteds[0]) || !count($tmp_return_data)) {
                    $return_data[] = array(
                            'id' => 0,
                            'text' => $this->pi_getLL('admin_main_category')
                    );
                }
                break;
            case 'getTree':
            default:
                $categoriesStartingPoint = 0;
                if (is_numeric($this->categoriesStartingPoint)) {
                    $categoriesStartingPoint = $this->categoriesStartingPoint;
                }
                if (isset($this->get['q']) && !empty($this->get['q']) && strlen($this->get['q']) >= 2) {
                    $keyword = trim($this->get['q']);
                    $categories_tree = array();
                    mslib_fe::getSubcatsArray($categories_tree, $keyword, $categoriesStartingPoint, $page_uid, $include_disabled_cats);
                    //print_r($categories_tree);
                    foreach ($categories_tree as $category_tree) {
                        $cats = mslib_fe::Crumbar($category_tree['id'], '', array(), $page_uid);
                        $cats = array_reverse($cats);
                        $catpath = array();
                        foreach ($cats as $cat) {
                            $catpath[] = $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                        }
                        // fetch subcat if any
                        $subcategories_tree = array();
                        mslib_fe::getSubcatsArray($subcategories_tree, '', $category_tree['id'], $page_uid, $include_disabled_cats);
                        if (count($subcategories_tree)) {
                            foreach ($subcategories_tree[$category_tree['id']] as $subcategory_tree_0) {
                                $tmp_return_data[$subcategory_tree_0['id']] = implode(' > ', $catpath) . ' > ' . $subcategory_tree_0['name'] . (!$subcategory_tree_0['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                if (is_array($subcategories_tree[$subcategory_tree_0['id']])) {
                                    mslib_fe::build_categories_path($tmp_return_data, $subcategory_tree_0['id'], $tmp_return_data[$subcategory_tree_0['id']], $subcategories_tree);
                                }
                            }
                        } else {
                            $tmp_return_data[$category_tree['id']] = implode(' > ', $catpath);
                        }
                    }
                } else {
                    $categories_tree = array();
                    mslib_fe::getSubcatsArray($categories_tree, '', $categoriesStartingPoint, $page_uid, $include_disabled_cats);
                    //level 0
                    foreach ($categories_tree[$categoriesStartingPoint] as $category_tree_0) {
                        $tmp_return_data[$category_tree_0['id']] = $category_tree_0['name'] . (!$category_tree_0['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                        if (is_array($categories_tree[$category_tree_0['id']])) {
                            mslib_fe::build_categories_path($tmp_return_data, $category_tree_0['id'], $tmp_return_data[$category_tree_0['id']], $categories_tree);
                        }
                    }
                    $return_data[] = array(
                            'id' => 0,
                            'text' => $this->pi_getLL('admin_main_category')
                    );
                }
                break;
            case'getFullTree':
                $categoriesStartingPoint = 0;
                if (is_numeric($this->categoriesStartingPoint)) {
                    $categoriesStartingPoint = $this->categoriesStartingPoint;
                }
                $skip_ids = array();
                if (isset($this->get['skip_ids']) && !empty($this->get['skip_ids'])) {
                    $skip_ids = explode(',', $this->get['skip_ids']);
                }
                if (isset($this->get['q']) && !empty($this->get['q']) && strlen($this->get['q']) >= 2) {
                    $keyword = trim($this->get['q']);
                    $categories_tree = array();
                    mslib_fe::getSubcatsArray($categories_tree, $keyword, $categoriesStartingPoint, '', $include_disabled_cats);
                    //print_r($categories_tree);
                    foreach ($categories_tree as $category_tree) {
                        if (count($skip_ids) > 0) {
                            if (!in_array($category_tree['id'], $skip_ids)) {
                                $cats = mslib_fe::Crumbar($category_tree['id'], '', array(), $page_uid);
                                $cats = array_reverse($cats);
                                $catpath = array();
                                foreach ($cats as $cat_idx => $cat) {
                                    if (!in_array($cat['id'], $skip_ids)) {
                                        if (isset($tmp_return_data[$cats[$cat_idx - 1]['id']])) {
                                            $tmp_return_data[$cat['id']] = $tmp_return_data[$cats[$cat_idx - 1]['id']] . ' > ' . $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                        } else {
                                            $tmp_return_data[$cat['id']] = $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                        }
                                        $catpath[] = $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                    }
                                }
                                // fetch subcat if any
                                $subcategories_tree = array();
                                mslib_fe::getSubcatsArray($subcategories_tree, '', $category_tree['id'], '', $include_disabled_cats);
                                if (count($subcategories_tree)) {
                                    foreach ($subcategories_tree[$category_tree['id']] as $subcategory_tree_0) {
                                        if (!in_array($subcategory_tree_0['id'], $skip_ids)) {
                                            $tmp_return_data[$subcategory_tree_0['id']] = implode(' > ', $catpath) . ' > ' . $subcategory_tree_0['name'] . (!$subcategory_tree_0['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                            if (is_array($subcategories_tree[$subcategory_tree_0['id']])) {
                                                mslib_fe::build_categories_path($tmp_return_data, $subcategory_tree_0['id'], $tmp_return_data[$subcategory_tree_0['id']], $subcategories_tree, true);
                                            }
                                        }
                                    }
                                } else {
                                    $tmp_return_data[$category_tree['id']] = implode(' > ', $catpath);
                                }
                            }
                        } else {
                            $cats = mslib_fe::Crumbar($category_tree['id'], '', array(), $page_uid);
                            $cats = array_reverse($cats);
                            $catpath = array();
                            foreach ($cats as $cat_idx => $cat) {
                                if (isset($tmp_return_data[$cats[$cat_idx - 1]['id']])) {
                                    $tmp_return_data[$cat['id']] = $tmp_return_data[$cats[$cat_idx - 1]['id']] . ' > ' . $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                } else {
                                    $tmp_return_data[$cat['id']] = $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                }
                                $catpath[] = $cat['name'] . (!$cat['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                            }
                            // fetch subcat if any
                            $subcategories_tree = array();
                            mslib_fe::getSubcatsArray($subcategories_tree, '', $category_tree['id'], $page_uid, $include_disabled_cats);
                            if (count($subcategories_tree)) {
                                foreach ($subcategories_tree[$category_tree['id']] as $subcategory_tree_0) {
                                    $tmp_return_data[$subcategory_tree_0['id']] = implode(' > ', $catpath) . ' > ' . $subcategory_tree_0['name'] . (!$subcategory_tree_0['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                                    if (is_array($subcategories_tree[$subcategory_tree_0['id']])) {
                                        mslib_fe::build_categories_path($tmp_return_data, $subcategory_tree_0['id'], $tmp_return_data[$subcategory_tree_0['id']], $subcategories_tree, true);
                                    }
                                }
                            } else {
                                $tmp_return_data[$category_tree['id']] = implode(' > ', $catpath);
                            }
                        }
                    }
                } else {
                    $categories_tree = array();
                    mslib_fe::getSubcatsArray($categories_tree, '', $categoriesStartingPoint, $page_uid, $include_disabled_cats);
                    //level 0
                    foreach ($categories_tree[$categoriesStartingPoint] as $category_tree_0) {
                        if (!in_array($category_tree_0['id'], $skip_ids)) {
                            $tmp_return_data[$category_tree_0['id']] = $category_tree_0['name'] . (!$category_tree_0['status'] ? ' (' . $this->pi_getLL('disabled') . ')' : '');
                            if (is_array($categories_tree[$category_tree_0['id']])) {
                                mslib_fe::build_categories_path($tmp_return_data, $category_tree_0['id'], $tmp_return_data[$category_tree_0['id']], $categories_tree, true);
                            }
                        }
                    }
                }
                if (!isset($this->get['no_maincat'])) {
                    $return_data[] = array(
                            'id' => 0,
                            'text' => $this->pi_getLL('admin_main_category')
                    );
                }
                break;
        }
        //natsort($tmp_return_data);
        $categories_results_limit = 0; // 0 = unlimited
        if (!$this->get['q'] || (isset($this->get['q']) && !empty($this->get['q']) && strlen($this->get['q']) < 2)) {
            $categories_results_limit = 15;
        }
        $category_counter = 0;
        foreach ($tmp_return_data as $tree_id => $tree_path) {
            $return_data[] = array(
                    'id' => $tree_id,
                    'text' => $tree_path
            );
            $category_counter++;
            if ($categories_results_limit > 0) {
                if ($category_counter >= $categories_results_limit) {
                    break;
                }
            }
        }
        $json_data = mslib_befe::array2json($return_data);
        echo $json_data;
        exit();
        break;
    case 'sort_specials_sections':
        if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
            $no = 1;
            foreach ($this->post['specialssections'] as $special_id) {
                if (is_numeric($special_id)) {
                    $where = "specials_id = " . $special_id . " and name='" . addslashes($this->get['tx_multishop_pi1']['sort_specials_sections']) . "'";
                    $updateArray = array(
                            'sort_order' => $no
                    );
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_specials_sections', $where, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    $no++;
                }
            }
        }
        exit();
        break;
    case 'delete_options_group':
        if ($this->ADMIN_USER) {
            if (isset($this->post['tx_multishop_pi1']['group_id']) && $this->post['tx_multishop_pi1']['group_id'] > 0) {
                $group_id = $this->post['tx_multishop_pi1']['group_id'];
                $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_attributes_options_groups', 'attributes_options_groups_id=' . $group_id);
                if ($qry) {
                    $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_attributes_options_groups_to_products_options', 'attributes_options_groups_id=' . $group_id);
                    $data = array();
                    $data['result'] = 'OK';
                    echo json_encode($data);
                    exit();
                }
            }
        }
        exit();
        break;
    case 'admin_categories_sorting':
        if ($this->ADMIN_USER) {
            $no = 1;
            foreach ($this->post['categories_id'] as $catid) {
                if (is_numeric($catid)) {
                    $where = "categories_id = " . $catid;
                    $updateArray = array(
                            'sort_order' => $no
                    );
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', $where, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    $no++;
                }
            }
        }
        exit();
        break;
    // attributes options and values editors
    case 'admin_ajax_attributes_options_values':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_ajax_attributes_options_values.php');
        }
        break;
    // attributes options values related to products
    case 'admin_ajax_product_attributes':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_ajax_product_attributes.php');
        }
        exit();
        break;
    case 'admin_ajax_edit_order':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_ajax_edit_order.php');
        }
        exit();
        break;
    case 'get_order_territories':
        $where = array();
        $skip_db = false;
        $limit = 50;
        if (isset($this->get['q']) && !empty($this->get['q'])) {
            if (!is_numeric($this->get['q'])) {
                $where[] = 'o.billing_tr_name_en like \'%' . addslashes($this->get['q']) . '%\'';
            } else {
                $where[] = '(o.billing_tr_name_en like \'%' . addslashes($this->get['q']) . '%\' or o.billing_tr_iso_nr = \'' . addslashes($this->get['q']) . '\' or o.billing_tr_parent_iso_nr = \'' . addslashes($this->get['q']) . '\')';
            }
            $limit = '';
        } else if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
            $where[] = 'o.billing_tr_iso_nr = \'' . addslashes($this->get['preselected_id']) . '\' or o.billing_tr_parent_iso_nr = \'' . addslashes($this->get['preselected_id']) . '\'';
        }
        $where[] = 'o.page_uid=' . $this->showCatalogFromPage;
        $str = $GLOBALS ['TYPO3_DB']->SELECTquery('o.billing_tr_iso_nr, o.billing_tr_name_en, o.billing_tr_parent_iso_nr', // SELECT ...
                'tx_multishop_orders o', // FROM ...
                implode(' and ', $where), // WHERE...
                'o.billing_tr_iso_nr', // GROUP BY...
                'o.billing_tr_name_en asc', // ORDER BY...
                $limit // LIMIT ...
        );
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $data = array();
        /*$data[]=array(
			'id'=>'99999',
			'text'=>$this->pi_getLL('all')
		);*/
        $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
        $territories = array();
        if ($num_rows) {
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                if (!isset($this->get['preselected_id']) || ($this->get['preselected_id'] == $row['billing_tr_parent_iso_nr'])) {
                    $str_parent = $GLOBALS ['TYPO3_DB']->SELECTquery('st.tr_name_en', // SELECT ...
                            'static_territories st', // FROM ...
                            'st.tr_iso_nr=' . $row['billing_tr_parent_iso_nr'], // WHERE...
                            '', // GROUP BY...
                            '', // ORDER BY...
                            $limit // LIMIT ...
                    );
                    $qry_parent = $GLOBALS['TYPO3_DB']->sql_query($str_parent);
                    $row_parent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_parent);
                    $territories[$row['billing_tr_parent_iso_nr']]['name'] = $row_parent['tr_name_en'];
                }
                $territories[$row['billing_tr_parent_iso_nr']]['children'][] = array(
                        'id' => $row['billing_tr_iso_nr'],
                        'text' => $row['billing_tr_name_en']
                );
            }
            if (count($territories)) {
                foreach ($territories as $parent_id => $territory) {
                    if (isset($territory['name']) && !empty($territory['name'])) {
                        $data[] = array(
                                'id' => $parent_id,
                                'text' => $territory['name'],
                                'children' => $territory['children']
                        );
                    } else {
                        $data = $territory['children'];
                    }
                }
            }
        }
        $content = json_encode($data);
        echo $content;
        exit();
        break;
    case 'get_order_customers':
        $where = array();
        $skip_db = false;
        $limit = 50;
        if (isset($this->get['q']) && !empty($this->get['q'])) {
            if (!is_numeric($this->get['q'])) {
                $where[] = '(o.billing_name like \'%' . addslashes($this->get['q']) . '%\' or o.billing_company like \'%' . addslashes($this->get['q']) . '%\')';
            } else {
                $where[] = '(o.billing_name like \'%' . addslashes($this->get['q']) . '%\' or o.billing_company like \'%' . addslashes($this->get['q']) . '%\' or o.customer_id = \'' . addslashes($this->get['q']) . '\')';
            }
            $limit = '';
        } else if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
            $where[] = 'o.customer_id = \'' . addslashes($this->get['preselected_id']) . '\'';
        }
        $where[] = 'o.page_uid=' . $this->showCatalogFromPage;
        $str = $GLOBALS ['TYPO3_DB']->SELECTquery('o.customer_id, o.billing_company, o.billing_name', // SELECT ...
                'tx_multishop_orders o', // FROM ...
                implode(' and ', $where), // WHERE...
                'o.customer_id', // GROUP BY...
                'o.billing_name asc', // ORDER BY...
                $limit // LIMIT ...
        );
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $data = array();
        /*$data[]=array(
			'id'=>'99999',
			'text'=>$this->pi_getLL('all')
		);*/
        $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
        if ($num_rows) {
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                $customer_names = array();
                $customer_name = '';
                if (!empty($row['billing_company'])) {
                    $customer_names[] = $row['billing_company'];
                }
                if (!empty($row['billing_name'])) {
                    $customer_names[] = $row['billing_name'];
                }
                $customer_name = implode(' | ', $customer_names);
                if (!empty($customer_name)) {
                    $data[] = array(
                            'id' => $row['customer_id'],
                            'text' => $customer_name
                    );
                }
            }
        }
        $content = json_encode($data);
        echo $content;
        exit();
        break;
    case 'get_ordered_manufacturers':
        $where = array();
        $skip_db = false;
        $limit = 50;
        if (isset($this->get['q']) && !empty($this->get['q'])) {
            if (!is_numeric($this->get['q'])) {
                $where[] = 'm.manufacturers_name like \'%' . addslashes($this->get['q']) . '%\'';
            } else {
                $where[] = '(m.manufacturers_name like \'%' . addslashes($this->get['q']) . '%\' or op.manufacturers_id = \'' . addslashes($this->get['q']) . '\')';
            }
            $limit = '';
        } else if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
            $where[] = 'op.manufacturers_id = \'' . addslashes($this->get['preselected_id']) . '\'';
        }
        $where[] = 'o.page_uid=' . $this->showCatalogFromPage;
        $where[] = 'm.status=1';
        $where[] = 'm.manufacturers_id=op.manufacturers_id';
        $where[] = 'o.orders_id=op.orders_id';
        $str = $GLOBALS ['TYPO3_DB']->SELECTquery('op.*, m.manufacturers_name', // SELECT ...
                'tx_multishop_orders_products op, tx_multishop_orders o, tx_multishop_manufacturers m', // FROM ...
                implode(' and ', $where), // WHERE...
                'm.manufacturers_id', // GROUP BY...
                'm.manufacturers_name asc', // ORDER BY...
                $limit // LIMIT ...
        );
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $data = array();
        /*$data[]=array(
			'id'=>'99999',
			'text'=>$this->pi_getLL('all')
		);*/
        $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
        if ($num_rows) {
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                if (!empty($row['manufacturers_name'])) {
                    $data[] = array(
                            'id' => $row['manufacturers_id'],
                            'text' => $row['manufacturers_name']
                    );
                }
            }
        }
        $content = json_encode($data);
        echo $content;
        exit();
        break;
    case 'get_ordered_categories':
        $where = array();
        $skip_db = false;
        $limit = 50;
        if (isset($this->get['q']) && !empty($this->get['q'])) {
            if (!is_numeric($this->get['q'])) {
                $where[] = 'cd.categories_name like \'%' . addslashes($this->get['q']) . '%\'';
            } else {
                $where[] = '(cd.categories_name like \'%' . addslashes($this->get['q']) . '%\' or p2c.node_id = \'' . addslashes($this->get['q']) . '\')';
            }
            $limit = '';
        } else if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
            $where[] = 'p2c.node_id = \'' . addslashes($this->get['preselected_id']) . '\'';
        }
        $where[] = 'o.page_uid=' . $this->showCatalogFromPage;
        $where[] = 'cd.language_id=' . $this->sys_language_uid;
        $where[] = 'c.status=1';
        $where[] = 'c.categories_id=cd.categories_id';
        $where[] = 'c.categories_id=p2c.node_id';
        $where[] = 'p2c.categories_id=op.categories_id';
        $where[] = 'o.orders_id=op.orders_id';
        $str = $GLOBALS ['TYPO3_DB']->SELECTquery('op.*, cd.categories_name', // SELECT ...
                'tx_multishop_orders_products op, tx_multishop_products_to_categories p2c, tx_multishop_orders o, tx_multishop_categories c, tx_multishop_categories_description cd', // FROM ...
                implode(' and ', $where), // WHERE.
                'op.categories_id', // GROUP BY...
                'cd.categories_name asc', // ORDER BY...
                $limit // LIMIT ...
        );
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $data = array();
        if (!isset($this->get['preselected_id'])) {
            $data[] = array(
                    'id' => '99999',
                    'text' => $this->pi_getLL('all')
            );
        }
        $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
        if ($num_rows) {
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                $catpath = array();
                if ($row['categories_id']) {
                    // get all cats to generate multilevel fake url
                    $level = 0;
                    $cats = array();
                    $cats = mslib_fe::Crumbar($row['categories_id']);
                    $cats = array_reverse($cats);
                    if (count($cats) > 0) {
                        foreach ($cats as $cat) {
                            $catpath[] = $cat['name'];
                        }
                    }
                    // get all cats to generate multilevel fake url eof
                }
                if (count($catpath)) {
                    $data[] = array(
                            'id' => $row['categories_id'],
                            'text' => implode(' > ', $catpath)
                    );
                }
            }
        }
        $content = json_encode($data);
        echo $content;
        exit();
        break;
    case 'get_ordered_products':
        $where = array();
        $skip_db = false;
        $limit = 50;
        if (isset($this->get['q']) && !empty($this->get['q'])) {
            if (!is_numeric($this->get['q'])) {
                $where[] = 'op.products_name like \'%' . addslashes($this->get['q']) . '%\'';
            } else {
                $where[] = '(op.products_name like \'%' . addslashes($this->get['q']) . '%\' or op.products_id = \'' . addslashes($this->get['q']) . '\')';
            }
            $limit = '';
        } else if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
            $where[] = 'op.products_id = \'' . addslashes($this->get['preselected_id']) . '\'';
        }
        $where[] = 'o.page_uid=' . $this->showCatalogFromPage;
        $where[] = 'o.orders_id=op.orders_id';
        $str = $GLOBALS ['TYPO3_DB']->SELECTquery('op.*', // SELECT ...
                'tx_multishop_orders_products op, tx_multishop_orders o', // FROM ...
                implode(' and ', $where), // WHERE.
                'op.products_id', // GROUP BY...
                'op.products_name asc', // ORDER BY...
                $limit // LIMIT ...
        );
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $data = array();
        if (!isset($this->get['preselected_id'])) {
            $data[] = array(
                    'id' => '99999',
                    'text' => $this->pi_getLL('all')
            );
        }
        $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($qry);
        if ($num_rows) {
            while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                if (!empty($row['products_name'])) {
                    $data[] = array(
                            'id' => $row['products_id'],
                            'text' => $row['products_name']
                    );
                }
            }
        }
        $content = json_encode($data);
        echo $content;
        exit();
        break;
    case 'downloadCategoryTree':
        if ($this->ADMIN_USER) {
            $multishop_category_array = array();
            $query2 = $GLOBALS['TYPO3_DB']->SELECTquery('cd.categories_name, c.categories_id, c.parent_id', // SELECT ...
                    'tx_multishop_categories c, tx_multishop_categories_description cd', // FROM ...
                    'c.parent_id =0 and c.status=1 and c.categories_id=cd.categories_id', // WHERE...
                    '', // GROUP BY...
                    'cd.categories_name', // ORDER BY...
                    '' // LIMIT ...
            );
            $res2 = $GLOBALS['TYPO3_DB']->sql_query($query2);
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($res2) > 0) {
                while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
                    $query3 = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                            'tx_multishop_categories c, tx_multishop_categories_description cd', // FROM ...
                            'c.parent_id=\'' . $row2['categories_id'] . '\' and c.status=1 and c.categories_id=cd.categories_id', // WHERE...
                            '', // GROUP BY...
                            'cd.categories_name', // ORDER BY...
                            '' // LIMIT ...
                    );
                    $res3 = $GLOBALS['TYPO3_DB']->sql_query($query3);
                    if ($GLOBALS['TYPO3_DB']->sql_num_rows($res3) > 0) {
                        while (($row3 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3))) {
                            $multishop_category_array[] = array(
                                    'categoryTree' => $row2['categories_name'] . ' / ' . $row3['categories_name'],
                                    'mainCatID' => $row2['categories_id'],
                                    'mainCatName' => $row2['categories_name'],
                                    'subCatID' => $row3['categories_id'],
                                    'subCatName' => $row3['categories_name']
                            );
                        }
                    }
                }
                $xml_string = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml_cs($multishop_category_array);
                echo $xml_string;
                exit();
            }
        }
        exit();
        break;
    case 'getAdminCustomersListingDetails':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_admin_customers_listing_details.php');
        }
        exit();
        break;
    case 'getAdminOrdersListingDetails':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_admin_orders_listing_details.php');
        }
        exit();
        break;
    case 'getExistingCustomers':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_admin_existing_customers.php');
        }
        exit();
        break;
    case 'getExistingCustomersInfo':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_admin_existing_customers_info.php');
        }
        exit();
        break;
    case 'getExistingOrders':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_admin_existing_orders.php');
        }
        exit();
        break;
    case 'getExistingInvoice':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_admin_existing_invoices.php');
        }
        exit();
        break;
    case 'getProductsList':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_products_list.php');
        }
        exit();
        break;
    case 'getManufacturersList':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_manufacturers_list.php');
        }
        exit();
        break;
    case 'retrieveAdminNotificationMessage':
        if ($this->ADMIN_USER) {
            $startTime = (time() - (60));
            $str = "SELECT id, title, message, customer_id, crdate from tx_multishop_notification where unread=1 and crdate > " . $startTime . " limit 2";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages = array();
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                $row['crdate'] = strftime("%x %X", $row['crdate']);
                $messages[] = $row;
                // update status to read
                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_notification', 'id=' . $row['id'], array('unread' => '0'));
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
            echo json_encode($messages, ENT_NOQUOTES);
        }
        exit();
        break;
    case 'admin_update_orders_status':
        if ($this->ADMIN_USER) {
            $returnOutput = '';
            if (is_numeric($this->post['tx_multishop_pi1']['orders_id']) and is_numeric($this->post['tx_multishop_pi1']['orders_status_id'])) {
                // hook
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminUpdateOrdersStatus'])) {
                    $params = array(
                            'orders_id' => &$this->post['tx_multishop_pi1']['orders_id'],
                            'orders_status_id' => $this->post['tx_multishop_pi1']['orders_status_id'],
                            'returnOutput' => &$returnOutput
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminUpdateOrdersStatus'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                // hook eof
                mslib_befe::updateOrderStatus($this->post['tx_multishop_pi1']['orders_id'], $this->post['tx_multishop_pi1']['orders_status_id'], 1);
                echo $returnOutput;
            }
        }
        exit();
        break;
    case 'admin_update_order_product_status':
        if ($this->ADMIN_USER) {
            if (is_numeric($this->post['tx_multishop_pi1']['orders_id']) and is_numeric($this->post['tx_multishop_pi1']['order_product_id']) and is_numeric($this->post['tx_multishop_pi1']['orders_status_id'])) {
                mslib_befe::updateOrderProductStatus($this->post['tx_multishop_pi1']['orders_id'], $this->post['tx_multishop_pi1']['order_product_id'], $this->post['tx_multishop_pi1']['orders_status_id']);
            }
        }
        exit();
        break;
    case 'update_currency':
        // change selected currency + exchange rate and save it in temporary session
        if ($this->post['tx_multishop_pi1']['selected_currency']) {
            $this->cookie['selected_currency'] = $this->post['tx_multishop_pi1']['selected_currency'];
            $use_google = true;
            $converted_rate = 1;
            // hook
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['updateCurrency'])) {
                $params = array(
                        'use_google' => &$use_google,
                        'converted_rate' => &$converted_rate,
                        'from_Currency' => $this->ms['MODULES']['CURRENCY_ARRAY']['cu_iso_3'],
                        'to_Currency' => $this->cookie['selected_currency']
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['updateCurrency'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            // hook eof
            if ($use_google) {
                $converted_rate = mslib_fe::currencyConverter($this->ms['MODULES']['CURRENCY_ARRAY']['cu_iso_3'], $this->cookie['selected_currency'], 1);
            }
            $this->cookie['currency_rate'] = $converted_rate;
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_multishop_cookie', $this->cookie);
            $GLOBALS['TSFE']->storeSessionData();
        }
        exit();
        break;
    case 'generateBarkode':
//		if ($this->ADMIN_USER)
//		{
        if ($this->get['tx_multishop_pi1']['string']) {
            // hook
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['generateBarkode'])) {
                $params = array(
                        'this' => &$this,
                        'get' => &$this->get,
                );
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['generateBarkode'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            }
            // hook oef
            require($this->DOCUMENT_ROOT_MS . 'res/barcode-coder/php-barcode-2.0.1.php');
            $font = $this->DOCUMENT_ROOT_MS . 'res/barcode-coder/code39.ttf';
            // download a ttf font here for example : http://www.dafont.com/fr/nottke.font
            //$font     = './NOTTB___.TTF';
            // - -
            $canvas_width = 200;
            $canvas_height = 75;
            $fontSize = 9; // GD1 in px ; GD2 in point
            $marge = 10; // between barcode and hri in pixel
            $x = 100; // barcode center
            $y = 40; // barcode center
            $height = 50; // barcode height in 1D ; module size in 2D
            $width = 1; // barcode width in 1D ; not use in 2D
            $angle = 0; // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation
            $code = $this->get['tx_multishop_pi1']['string']; // barcode, of course ;)
            $type = 'code39';
            /*
				 *    standard 2 of 5 (std25)
				 *    interleaved 2 of 5 (int25)
				 *    ean 8 (ean8)
				 *    ean 13 (ean13)
				 *    code 11 (code11)
				 *    code 39 (code39)
				 *    code 93 (code93)
				 *    code 128 (code128)
				 *    codabar (codabar)
				 *    msi (msi)
				 *    datamatrix (datamatrix)
			*/
            // -------------------------------------------------- //
            //                    USEFUL
            // -------------------------------------------------- //
            function drawCross($im, $color, $x, $y) {
                imageline($im, $x - 10, $y, $x + 10, $y, $color);
                imageline($im, $x, $y - 10, $x, $y + 10, $color);
            }

            // -------------------------------------------------- //
            //            ALLOCATE GD RESOURCE
            // -------------------------------------------------- //
            $im = imagecreatetruecolor($canvas_width, $canvas_height);
            $black = ImageColorAllocate($im, 0x00, 0x00, 0x00);
            $white = ImageColorAllocate($im, 0xff, 0xff, 0xff);
            $red = ImageColorAllocate($im, 0xff, 0x00, 0x00);
            $blue = ImageColorAllocate($im, 0x00, 0x00, 0xff);
            imagefilledrectangle($im, 0, 0, 300, 300, $white);
            // -------------------------------------------------- //
            //                      BARCODE
            // -------------------------------------------------- //
            $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code' => $code), $width, $height);
            // -------------------------------------------------- //
            //                        HRI
            // -------------------------------------------------- //
            /*
							if ( isset($font) ){
							$box = imagettfbbox($fontSize, 0, $font, $data['hri']);
							$len = $box[2] - $box[0];
							Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
							imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
							}
			*/
            // -------------------------------------------------- //
            //                     ROTATE
            // -------------------------------------------------- //
            // Beware ! the rotate function should be use only with right angle
            // Remove the comment below to see a non right rotation
            /** /
             * $rot = imagerotate($im, 45, $white);
             * imagedestroy($im);
             * $im     = imagecreatetruecolor(900, 300);
             * $black  = ImageColorAllocate($im,0x00,0x00,0x00);
             * $white  = ImageColorAllocate($im,0xff,0xff,0xff);
             * $red    = ImageColorAllocate($im,0xff,0x00,0x00);
             * $blue   = ImageColorAllocate($im,0x00,0x00,0xff);
             * imagefilledrectangle($im, 0, 0, 900, 300, $white);
             * // Barcode rotation : 90�
             * $angle = 90;
             * $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
             * Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
             * imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
             * imagettftext($im, 10, 0, 60, 290, $black, $font, 'BARCODE ROTATION : 90�');
             * // barcode rotation : 135
             * $angle = 135;
             * Barcode::gd($im, $black, $x+300, $y, $angle, $type, array('code'=>$code), $width, $height);
             * Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
             * imagettftext($im, $fontSize, $angle, $x + 300 + $xt, $y + $yt, $blue, $font, $data['hri']);
             * imagettftext($im, 10, 0, 360, 290, $black, $font, 'BARCODE ROTATION : 135�');
             * // last one : image rotation
             * imagecopy($im, $rot, 580, -50, 0, 0, 300, 300);
             * imagerectangle($im, 0, 0, 299, 299, $black);
             * imagerectangle($im, 299, 0, 599, 299, $black);
             * imagerectangle($im, 599, 0, 899, 299, $black);
             * imagettftext($im, 10, 0, 690, 290, $black, $font, 'IMAGE ROTATION');
             * /**/
            // -------------------------------------------------- //
            //                    MIDDLE AXE
            // -------------------------------------------------- //
//				imageline($im, $x, 0, $x, 250, $red);
//				imageline($im, 0, $y, 250, $y, $red);
            // -------------------------------------------------- //
            //                  BARCODE BOUNDARIES
            // -------------------------------------------------- //
//				for($i=1; $i<5; $i++){
//				drawCross($im, $blue, $data['p'.$i]['x'], $data['p'.$i]['y']);
//				}
            // -------------------------------------------------- //
            //                    GENERATE
            // -------------------------------------------------- //
            header('Content-type: image/gif');
            imagegif($im);
            imagedestroy($im);
            exit();
        }
//		}
        break;
    case 'psp':
        if ($_REQUEST['tx_multishop_pi1']['payment_lib']) {
            $mslib_payment = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('mslib_payment');
            $mslib_payment->init($this);
//			$payment_methods=$mslib_payment->getInstalledPaymentMethods($this);
            if ($mslib_payment->setPaymentMethod($_REQUEST['tx_multishop_pi1']['payment_lib'])) {
                // psp installed and is activated
                $extkey = 'multishop_' . $_REQUEST['tx_multishop_pi1']['payment_lib'];
                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extkey)) {
                    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extkey) . 'class.multishop_payment_method.php');
                    $paymentMethod = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_multishop_payment_method');
                    $paymentMethod->init($this);
                    $paymentMethod->paymentNotificationHandler();
                }
            } else {
//				error_log("no");
            }
        }
        exit();
        break;
    case 'admin_panel':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_panel.php');
        exit();
        break;
    case 'get_method_costs':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_method_costs.php');
        exit();
        break;
    case 'get_country_payment_methods':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_country_payment_methods.php');
        exit();
        break;
    case 'confirm_create_account':
        if ($this->get['tx_multishop_pi1']['hash']) {
            // hook
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['confirm_create_account'])) {
                $params = array('content' => &$content);
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['confirm_create_account'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            } else {
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/confirm_create_account.php');
            }
        }
        exit();
        break;
    case 'download_invoice':
        if ($this->get['tx_multishop_pi1']['hash']) {
            if (strstr($this->ms['MODULES']['DOWNLOAD_INVOICE_TYPE'], "..")) {
                die('error in DOWNLOAD_INVOICE_TYPE value');
            } else {
                if (strstr($this->ms['MODULES']['DOWNLOAD_INVOICE_TYPE'], "/")) {
                    // relative mode
                    require($this->DOCUMENT_ROOT . $this->ms['MODULES']['DOWNLOAD_INVOICE_TYPE'] . '.php');
                } else {
                    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/download_invoice_b2c.php');
                }
            }
        }
        exit();
        break;
    case 'download_packingslip':
        if ($this->get['tx_multishop_pi1']['order_id'] || $this->get['tx_multishop_pi1']['order_hash']) {
            if (strstr($this->ms['MODULES']['DOWNLOAD_PACKINGSLIP_TYPE'], "..")) {
                die('error in DOWNLOAD_INVOICE_TYPE value');
            } else {
                if (strstr($this->ms['MODULES']['DOWNLOAD_PACKINGSLIP_TYPE'], "/")) {
                    // relative mode
                    require($this->DOCUMENT_ROOT . $this->ms['MODULES']['DOWNLOAD_PACKINGSLIP_TYPE'] . '.php');
                } else {
                    require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/download_packingslip.php');
                }
            }
        }
        exit();
        break;
    case 'download_product_feed':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/download_product_feed.php');
        exit();
        break;
    case 'download_orders_export':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/download_orders_export.php');
        exit();
        break;
    case 'download_invoices_export':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/download_invoices_export.php');
        exit();
        break;
    case 'download_customers_export':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/download_customers_export.php');
        exit();
        break;
    case 'admin_ajax_upload':
        if ($this->ADMIN_USER) {
            if (isset($_SERVER["CONTENT_LENGTH"])) {
                switch ($this->get['file_type']) {
                    case 'fe_user_image':
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
                                    //$filename=mslib_fe::rewritenamein($this->get['products_name']).'.'.$ext;
                                    $name = md5(time());
                                    $filename = $name . '.' . $ext;
                                    $targetFolder = $this->DOCUMENT_ROOT . 'uploads/pics/';
                                    $target = $targetFolder . $filename;
                                    if (file_exists($target)) {
                                        do {
                                            $filename = $name . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                                            $target = $targetFolder . $filename;
                                            $i++;
                                        } while (file_exists($target));
                                    }
                                    if (copy($temp_file, $target)) {
                                        //$filename=mslib_befe::resizeProductImage($target,$filename,$this->DOCUMENT_ROOT.\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey),1);
                                        copy($temp_file, $target);
                                        if (is_numeric($this->get['tx_multishop_pi1']['uid'])) {
                                            $updateArray = array(
                                                    'image' => $filename
                                            );
                                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('fe_users', 'uid=' . $this->get['tx_multishop_pi1']['uid'], $updateArray);
                                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                        }
                                        $result = array();
                                        $result['success'] = true;
                                        $result['error'] = false;
                                        $result['filename'] = $filename;
                                        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
                                        exit();
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
        exit();
        break;
    case 'admin_upload_redactor':
        if ($this->ADMIN_USER) {
            $continueUpload = 0;
            $filename = '';
            switch ($this->get['tx_multishop_pi1']['redactorType']) {
                case 'imageGetJson':
                    $fileUploadPathRelative = 'uploads/tx_multishop/images/cmsimages';
                    $fileUploadPathAbsolute = $this->DOCUMENT_ROOT . $fileUploadPathRelative;
                    if (is_dir($fileUploadPathAbsolute)) {
                        $items = \TYPO3\CMS\Core\Utility\GeneralUtility::getAllFilesAndFoldersInPath(array(), $fileUploadPathAbsolute . '/');
                        if (count($items)) {
                            $array = array();
                            foreach ($items as $item) {
                                $path_parts = pathinfo($item);
                                $file = array();
                                $file['title'] = $path_parts['filename'];
                                $file['thumb'] = str_replace($this->DOCUMENT_ROOT, '', $item);
                                $file['image'] = $file['thumb'];
                                $file['folder'] = str_replace($fileUploadPathAbsolute, '', $path_parts['dirname']);
                                $array[] = $file;
                            }
                            echo htmlspecialchars(json_encode($array), ENT_NOQUOTES);
                        }
                    }
                    exit();
                    break;
                case 'clipboardUploadUrl':
                    if ($this->post['contentType'] and $this->post['data']) {
                        switch ($this->post['contentType']) {
                            case 'image/png':
                            case 'image/jpg':
                            case 'image/gif':
                            case 'image/jpeg':
                            case 'image/pjpeg':
                                $fileUploadPathRelative = 'uploads/tx_multishop/images/cmsimages';
                                $fileUploadPathAbsolute = $this->DOCUMENT_ROOT . $fileUploadPathRelative;
                                $temp_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . uniqid();
                                file_put_contents($temp_file, base64_decode($this->post['data']));
                                if (file_exists($temp_file)) {
                                    $size = getimagesize($temp_file);
                                    if ($size[0] > 5 and $size[1] > 5) {
                                        $imgtype = mslib_befe::exif_imagetype($temp_file);
                                        if ($imgtype) {
                                            // valid image
                                            $ext = image_type_to_extension($imgtype, false);
                                            if ($ext) {
                                                $continueUpload = 1;
                                            }
                                        }
                                    }
                                }
                                break;
                        }
                    }
                    break;
                case 'imageUpload':
                    $_FILES['file']['type'] = strtolower($_FILES['file']['type']);
                    switch ($_FILES['file']['type']) {
                        case 'image/png':
                        case 'image/jpg':
                        case 'image/gif':
                        case 'image/jpeg':
                        case 'image/pjpeg':
                            $fileUploadPathRelative = 'uploads/tx_multishop/images/cmsimages';
                            $fileUploadPathAbsolute = $this->DOCUMENT_ROOT . $fileUploadPathRelative;
                            $temp_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . uniqid();
                            move_uploaded_file($_FILES['file']['tmp_name'], $temp_file);
                            $size = getimagesize($temp_file);
                            if ($size[0] > 5 and $size[1] > 5) {
                                $imgtype = mslib_befe::exif_imagetype($temp_file);
                                if ($imgtype) {
                                    // valid image
                                    $ext = image_type_to_extension($imgtype, false);
                                    if ($ext) {
                                        $continueUpload = 1;
                                    }
                                }
                            }
                            break;
                    }
                    break;
                case 'fileUpload':
                    $fileUploadPathRelative = 'uploads/tx_multishop/images/cmsfiles';
                    $fileUploadPathAbsolute = $this->DOCUMENT_ROOT . $fileUploadPathRelative;
                    $temp_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/tmp/' . uniqid();
                    move_uploaded_file($_FILES['file']['tmp_name'], $temp_file);
                    $filename = $_FILES["file"]["name"];
                    $path_parts = pathinfo($_FILES["file"]["name"]);
                    $ext = $path_parts['extension'];
                    if ($ext) {
                        $continueUpload = 1;
                    }
                    break;
            }
            if ($continueUpload) {
                if (!$this->get['tx_multishop_pi1']['title']) {
                    $this->get['tx_multishop_pi1']['title'] = uniqid();
                }
                $i = 0;
                $filename = mslib_fe::rewritenamein($this->get['tx_multishop_pi1']['title']) . '.' . $ext;
                $target = $fileUploadPathAbsolute . '/' . $filename;
                if (file_exists($target)) {
                    do {
                        $filename = mslib_fe::rewritenamein($this->get['tx_multishop_pi1']['title']) . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                        $target = $fileUploadPathAbsolute . '/' . $filename;
                        $i++;
                    } while (file_exists($target));
                }
                if (copy($temp_file, $target)) {
                    $fileLocation = $this->FULL_HTTP_URL . $fileUploadPathRelative . '/' . $filename;
                    $result = array(
                            'url' => $fileLocation,
                            'name' => $filename
                    );
                    echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
                    exit();
                }
            }
        }
        exit();
        break;
    case 'admin_upload_product_images':
        if ($this->ADMIN_USER) {
            if (isset($_SERVER["CONTENT_LENGTH"])) {
                switch ($this->get['file_type']) {
                    case 'categories_image':
                        $tmp_filename = $this->get['categories_name'];
                        if (!$this->ms['MODULES']['ADMIN_AUTORENAME_UPLOADED_IMAGES']) {
                            if (isset($this->get['qqfile']) && !empty($this->get['qqfile'])) {
                                $tmp_arr = explode('.', $this->get['qqfile']);
                                $tmp_arr_count = count($tmp_arr);
                                unset($tmp_arr[$tmp_arr_count - 1]);
                                $tmp_filename = implode('.', $tmp_arr);
                            }
                        }
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
                                    if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['categories']['original'] . '/' . $folder)) {
                                        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['categories']['original'] . '/' . $folder);
                                    }
                                    $folder .= '/';
                                    $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['categories']['original'] . '/' . $folder . $filename;
                                    if (file_exists($target)) {
                                        do {
                                            $filename = mslib_fe::rewritenamein($tmp_filename) . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                                            $folder_name = mslib_befe::getImagePrefixFolder($filename);
                                            $array = explode(".", $filename);
                                            $folder = $folder_name;
                                            if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['categories']['original'] . '/' . $folder)) {
                                                \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['categories']['original'] . '/' . $folder);
                                            }
                                            $folder .= '/';
                                            $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['categories']['original'] . '/' . $folder . $filename;
                                            $i++;
                                        } while (file_exists($target));
                                    }
                                    if (copy($temp_file, $target)) {
                                        $filename = mslib_befe::resizeCategoryImage($target, $filename, $this->DOCUMENT_ROOT . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey), 1);
//												error_log('bass'.print_r($this->ds,1));
                                        $fileLocation = $this->FULL_HTTP_URL . mslib_befe::getImagePath($filename, 'categories', 'normal');
//										error_log($fileLocation);
                                        $result = array();
                                        $result['success'] = true;
                                        $result['error'] = false;
                                        $result['filename'] = $filename;
                                        $result['fileLocation'] = $fileLocation;
                                        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
                                        exit();
                                    }
                                }
                            }
                        }
                        break;
                    case 'manufacturers_images':
                        $tmp_filename = $this->get['manufacturers_name'];
                        if (!$this->ms['MODULES']['ADMIN_AUTORENAME_UPLOADED_IMAGES']) {
                            if (isset($this->get['qqfile']) && !empty($this->get['qqfile'])) {
                                $tmp_arr = explode('.', $this->get['qqfile']);
                                $tmp_arr_count = count($tmp_arr);
                                unset($tmp_arr[$tmp_arr_count - 1]);
                                $tmp_filename = implode('.', $tmp_arr);
                            }
                        }
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
                                    if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder)) {
                                        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder);
                                    }
                                    $folder .= '/';
                                    $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder . $filename;
                                    if (file_exists($target)) {
                                        do {
                                            $filename = mslib_fe::rewritenamein($tmp_filename) . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                                            $folder_name = mslib_befe::getImagePrefixFolder($filename);
                                            $array = explode(".", $filename);
                                            $folder = $folder_name;
                                            if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder)) {
                                                \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder);
                                            }
                                            $folder .= '/';
                                            $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder . $filename;
                                            $i++;
                                        } while (file_exists($target));
                                    }
                                    if (copy($temp_file, $target)) {
                                        $filename = mslib_befe::resizeManufacturerImage($target, $filename, $this->DOCUMENT_ROOT . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey), 1);
                                        $fileLocation = $this->FULL_HTTP_URL . mslib_befe::getImagePath($filename, 'manufacturers', 'normal');
                                        $result = array();
                                        $result['success'] = true;
                                        $result['error'] = false;
                                        $result['filename'] = $filename;
                                        $result['fileLocation'] = $fileLocation;
                                        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
                                        exit();
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        for ($x = 0; $x < $this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $x++) {
                            $tmp_filename = $this->get['products_name'];
                            if (!$this->ms['MODULES']['ADMIN_AUTORENAME_UPLOADED_IMAGES']) {
                                if (isset($this->get['qqfile']) && !empty($this->get['qqfile'])) {
                                    $tmp_arr = explode('.', $this->get['qqfile']);
                                    $tmp_arr_count = count($tmp_arr);
                                    unset($tmp_arr[$tmp_arr_count - 1]);
                                    $tmp_filename = implode('.', $tmp_arr);
                                }
                            }
                            // hidden filename that is retrieved from the ajax upload
                            $i = $x;
                            if ($i == 0) {
                                $i = '';
                            }
                            $field = 'products_image' . $i;
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
                                            // hook for adding new items to details fieldset
                                            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminUploadProductImagesDefaultFileNameProc'])) {
                                                $params = array(
                                                        'tmp_filename' => &$tmp_filename,
                                                        'ext' => &$ext
                                                );
                                                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminUploadProductImagesDefaultFileNameProc'] as $funcRef) {
                                                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                                                }
                                                // hook oef
                                            }
                                            $i = 0;
                                            $filename = mslib_fe::rewritenamein($tmp_filename) . '.' . $ext;
                                            $folder = mslib_befe::getImagePrefixFolder($filename);
                                            $array = explode(".", $filename);
                                            if (isset($this->get['old_image']) && !empty($this->get['old_image'])) {
                                                $orFilter = array();
                                                for ($i = 0; $i < $this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $i++) {
                                                    $s = '';
                                                    if ($i > 0) {
                                                        $s = $i;
                                                    }
                                                    $orFilter[] = 'products_image' . $s . '=\'' . addslashes($this->get['old_image']) . '\'';
                                                }
                                                $filter = array();
                                                $filter[] = '(' . implode(' OR ', $orFilter) . ')';
                                                $count = mslib_befe::getCount('', 'tx_multishop_products', '', $filter);
                                                if ($count < 2) {
                                                    // Only delete the file is we have found 1 product using it
                                                    mslib_befe::deleteProductImage($this->get['old_image']);
                                                }
                                            }
                                            if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['products']['original'] . '/' . $folder)) {
                                                \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['products']['original'] . '/' . $folder);
                                            }
                                            $folder .= '/';
                                            $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['products']['original'] . '/' . $folder . $filename;
                                            if (file_exists($target)) {
                                                do {
                                                    $filename = mslib_fe::rewritenamein($tmp_filename) . ($i > 0 ? '-' . $i : '') . '.' . $ext;
                                                    $folder_name = mslib_befe::getImagePrefixFolder($filename);
                                                    $array = explode(".", $filename);
                                                    $folder = $folder_name;
                                                    if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['products']['original'] . '/' . $folder)) {
                                                        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['products']['original'] . '/' . $folder);
                                                    }
                                                    $folder .= '/';
                                                    $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['products']['original'] . '/' . $folder . $filename;
                                                    $i++;
                                                } while (file_exists($target));
                                            }
                                            if (copy($temp_file, $target)) {
                                                $filename = mslib_befe::resizeProductImage($target, $filename, $this->DOCUMENT_ROOT . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey), 1);
                                                $fileLocation = $this->FULL_HTTP_URL . mslib_befe::getImagePath($filename, 'products', '50');
                                                $result = array();
                                                $result['success'] = true;
                                                $result['error'] = false;
                                                $result['filename'] = $filename;
                                                $result['fileLocation'] = $fileLocation . '?' . time();
                                                echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
                                                exit();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
        exit();
        break;
    case 'delete_products_images':
        if ($this->ADMIN_USER) {
            $return_data = array();
            $pid = $this->post['pid'];
            $img_counter = $this->post['image_counter'];
            $image_array_key = "products_image" . $img_counter;
            $image_filename = $this->post['image_filename'];
            $orFilter = array();
            for ($i = 0; $i < $this->ms['MODULES']['NUMBER_OF_PRODUCT_IMAGES']; $i++) {
                $s = '';
                if ($i > 0) {
                    $s = $i;
                }
                $orFilter[] = 'products_image' . $s . '=\'' . addslashes($image_filename) . '\'';
            }
            $filter = array();
            $filter[] = '(' . implode(' OR ', $orFilter) . ')';
            $count = mslib_befe::getCount('', 'tx_multishop_products', '', $filter);
            if ($count < 2) {
                // Only delete the file is we have found 1 product using it
                mslib_befe::deleteProductImage($image_filename);
            }
            if (is_numeric($pid) && $pid > 0) {
                $updateArray = array();
                $updateArray[$image_array_key] = '';
                if ($image_array_key == 'products_image') {
                    $updateArray['contains_image'] = 0;
                }
                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products', 'products_id=\'' . $pid . '\'', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
            if ($this->ms['MODULES']['ADMIN_CROP_PRODUCT_IMAGES']) {
                if (is_numeric($pid) && $pid > 0) {
                    $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_product_crop_image_coordinate', 'image_filename=\'' . addslashes($image_filename) . '\' and products_id=\'' . $pid . '\'');
                } else {
                    $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_product_crop_image_coordinate', 'image_filename=\'' . addslashes($image_filename) . '\'');
                }
            }
            $return_data['image_counter'] = $img_counter;
            $json = json_encode($return_data);
            echo $json;
            exit();
        }
        break;
    case 'delete_categories_images':
        if ($this->ADMIN_USER) {
            $return_data = array();
            $image_filename = $this->post['image_filename'];
            $cid = 0;
            if ($this->post['cid'] > 0) {
                $cid = $this->post['cid'];
            }
            $filter = array();
            $filter[] = 'categories_image=\'' . addslashes($image_filename) . '\'';
            $count = mslib_befe::getCount('', 'tx_multishop_categories', '', $filter);
            if ($count < 2) {
                // Only delete the file is we have found 1 category using it
                mslib_befe::deleteCategoryImage($image_filename);
            }
            if ($cid > 0) {
                $updateArray = array();
                $updateArray['categories_image'] = '';
                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', 'categories_id=\'' . $cid . '\'', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
            if ($this->ms['MODULES']['ADMIN_CROP_CATEGORIES_IMAGES']) {
                if (is_numeric($cid) && $cid > 0) {
                    $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_categories_crop_image_coordinate', 'image_filename=\'' . addslashes($image_filename) . '\' and categories_id=\'' . $pid . '\'');
                } else {
                    $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_categories_crop_image_coordinate', 'image_filename=\'' . addslashes($image_filename) . '\'');
                }
            }
            $json = json_encode($return_data);
            echo $json;
            exit();
        }
        break;
    case 'delete_manufacturers_images':
        if ($this->ADMIN_USER) {
            $return_data = array();
            $image_filename = $this->post['image_filename'];
            $mid = 0;
            if ($this->post['mid'] > 0) {
                $mid = $this->post['mid'];
            }
            $filter = array();
            $filter[] = 'manufacturers_image=\'' . addslashes($image_filename) . '\'';
            $count = mslib_befe::getCount('', 'tx_multishop_manufacturers', '', $filter);
            if ($count < 2) {
                // Only delete the file is we have found 1 category using it
                mslib_befe::deleteManufacturerImage($image_filename);
            }
            if ($mid > 0) {
                $updateArray = array();
                $updateArray['manufacturers_image'] = '';
                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_manufacturers', 'manufacturers_id=\'' . $mid . '\'', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
            if ($this->ms['MODULES']['ADMIN_CROP_MANUFACTURERS_IMAGES']) {
                if (is_numeric($cid) && $cid > 0) {
                    $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_manufacturers_crop_image_coordinate', 'image_filename=\'' . addslashes($image_filename) . '\' and manufacturers_id=\'' . $pid . '\'');
                } else {
                    $qry = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_multishop_manufacturers_crop_image_coordinate', 'image_filename=\'' . addslashes($image_filename) . '\'');
                }
            }
            $json = json_encode($return_data);
            echo $json;
            exit();
        }
        break;
    case 'update_products_status':
        if ($this->ADMIN_USER) {
            if (is_numeric($this->post['products_id'])) {
                $str = "select products_id,products_status from tx_multishop_products where products_id='" . $this->post['products_id'] . "'";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
                if ($row['products_id']) {
                    switch ($row['products_status']) {
                        case '0':
                            mslib_befe::enableProduct($row['products_id']);
                            $new_value = 1;
                            break;
                        case '1':
                            mslib_befe::disableProduct($row['products_id']);
                            $new_value = 0;
                            break;
                    }
                    $item = array();
                    $item['html'] = $new_value;
                    $json = mslib_befe::array2json($item);
                    echo $json;
                }
            }
        }
        exit();
        break;
    // products attributes groups
    case 'update_attributes_options_groups_sortable':
        // this is the AJAX server for changing the sort order of the product attributes
        if ($this->ADMIN_USER) {
            switch ($this->get['tx_multishop_pi1']['type']) {
                case 'options_groups':
                    if (is_array($this->post['options_groups']) and count($this->post['options_groups'])) {
                        $no = 1;
                        foreach ($this->post['options_groups'] as $prod_id) {
                            if (is_numeric($prod_id)) {
                                $where = "attributes_options_groups_id = " . $prod_id;
                                $updateArray = array(
                                        'sort_order' => $no
                                );
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_attributes_options_groups', $where, $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                                $no++;
                            }
                        }
                    }
                    break;
            }
        }
        exit();
        break;
    // products attributes groups eol
    case 'update_customer_order_details':
        if ($this->ADMIN_USER and is_numeric($this->get['orders_id'])) {
            $order = mslib_fe::getOrder($this->get['orders_id']);
            if ($order['orders_id'] and !$order['is_locked']) {
                $details_type = $this->get['details_type'];
                $orders_id = $this->get['orders_id'];
                //
                $name = '';
                $erno = array();
                $this->ms['MODULES']['DISABLE_EDIT_ORDER_CUSTOMER_DETAILS_VALIDATION'] = (int)$this->ms['MODULES']['DISABLE_EDIT_ORDER_CUSTOMER_DETAILS_VALIDATION'];
                if ($details_type == 'billing_details') {
                    if (!$this->ms['MODULES']['DISABLE_EDIT_ORDER_CUSTOMER_DETAILS_VALIDATION']) {
                        // validate essential info
                        if (!$this->post['tx_multishop_pi1']['billing_email']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_email_address_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_street_name']) {
                            $erno[] = '<li>' . 'No street name has been specified' . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_address_number']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_address_number_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_first_name']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_first_name_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_last_name']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_last_name_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_zip']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_zip_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_city']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_city_has_been_specified') . '</li>';
                        }
                        if ($this->ms['MODULES']['CHECKOUT_REQUIRED_COMPANY'] && !$this->post['tx_multishop_pi1']['billing_company']) {
                            $erno[] = '<li>' . $this->pi_getLL('company_is_required') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['billing_country']) {
                            $erno[] = '<li>' . $this->pi_getLL('country_is_required') . '</li>';
                        }
                    }
                    // build name
                    if (isset($this->post['tx_multishop_pi1']['billing_first_name'])) {
                        $name = $this->post['tx_multishop_pi1']['billing_first_name'];
                    }
                    if (isset($this->post['tx_multishop_pi1']['billing_middle_name'])) {
                        $name .= ' ' . $this->post['tx_multishop_pi1']['billing_middle_name'];
                    }
                    if (isset($this->post['tx_multishop_pi1']['billing_last_name'])) {
                        $name .= ' ' . $this->post['tx_multishop_pi1']['billing_last_name'];
                    }
                    $this->post['tx_multishop_pi1']['billing_name'] = '';
                    if ($name) {
                        $this->post['tx_multishop_pi1']['billing_name'] = $name;
                    }
                } else if ($details_type == 'delivery_details') {
                    if (!$this->ms['MODULES']['DISABLE_EDIT_ORDER_CUSTOMER_DETAILS_VALIDATION']) {
                        // validate essential info
                        if (!$this->post['tx_multishop_pi1']['delivery_email']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_email_address_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_street_name']) {
                            $erno[] = '<li>' . 'No street name has been specified' . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_address_number']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_address_number_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_first_name']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_first_name_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_last_name']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_last_name_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_zip']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_zip_has_been_specified') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_city']) {
                            $erno[] = '<li>' . $this->pi_getLL('no_city_has_been_specified') . '</li>';
                        }
                        if ($this->ms['MODULES']['CHECKOUT_REQUIRED_COMPANY'] && !$this->post['tx_multishop_pi1']['delivery_company']) {
                            $erno[] = '<li>' . $this->pi_getLL('company_is_required') . '</li>';
                        }
                        if (!$this->post['tx_multishop_pi1']['delivery_country']) {
                            $erno[] = '<li>' . $this->pi_getLL('country_is_required') . '</li>';
                        }
                    }
                    // build delivery name
                    if (isset($this->post['tx_multishop_pi1']['delivery_first_name'])) {
                        $name = $this->post['tx_multishop_pi1']['delivery_first_name'];
                    }
                    if (isset($this->post['tx_multishop_pi1']['delivery_middle_name'])) {
                        $name .= ' ' . $this->post['tx_multishop_pi1']['delivery_middle_name'];
                    }
                    if (isset($this->post['tx_multishop_pi1']['delivery_last_name'])) {
                        $name .= ' ' . $this->post['tx_multishop_pi1']['delivery_last_name'];
                    }
                    $this->post['tx_multishop_pi1']['delivery_name'] = '';
                    if ($name) {
                        $this->post['tx_multishop_pi1']['delivery_name'] = $name;
                    }
                }
                //
                $keys = array();
                $keys[] = 'company';
                $keys[] = 'first_name';
                $keys[] = 'middle_name';
                $keys[] = 'last_name';
                $keys[] = 'name';
                $keys[] = 'street_name';
                $keys[] = 'address_number';
                $keys[] = 'address_ext';
                $keys[] = 'building';
                $keys[] = 'zip';
                $keys[] = 'city';
                $keys[] = 'region';
                $keys[] = 'country';
                $keys[] = 'email';
                $keys[] = 'telephone';
                $keys[] = 'mobile';
                $keys[] = 'fax';
                if ($this->ms['MODULES']['SHOW_DEPARTMENT_INPUT_FIELD_IN_ADMIN_EDIT_CUSTOMER']) {
                    $keys[] = 'department';
                }
                $return_data = array();
                $updateArray = array();
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminEditOrdersCustomerDetailsPreProc'])) {
                    $params = array(
                            'details_type' => $details_type,
                            'erno' => &$erno
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminEditOrdersCustomerDetailsPreProc'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                    // hook oef
                }
                if (!count($erno)) {
                    switch ($details_type) {
                        case "delivery_details":
                            $keys[] = 'gender';
                            foreach ($keys as $key) {
                                $string = 'delivery_' . $key;
                                if (isset($this->post['tx_multishop_pi1'][$string])) {
                                    $updateArray[$string] = $this->post['tx_multishop_pi1'][$string];
                                }
                            }
                            $updateArray['delivery_address'] = preg_replace('/ +/', ' ', $updateArray['delivery_street_name'] . ' ' . $updateArray['delivery_address_number'] . ' ' . $updateArray['delivery_address_ext']);
                            break;
                        case "billing_details":
                            $keys[] = 'gender';
                            $keys[] = 'vat_id';
                            $keys[] = 'coc_id';
                            foreach ($keys as $key) {
                                $string = 'billing_' . $key;
                                if (isset($this->post['tx_multishop_pi1'][$string])) {
                                    $updateArray[$string] = $this->post['tx_multishop_pi1'][$string];
                                }
                            }
                            $updateArray['billing_address'] = preg_replace('/ +/', ' ', $updateArray['billing_street_name'] . ' ' . $updateArray['billing_address_number'] . ' ' . $updateArray['billing_address_ext']);
                            break;
                    }
                    if (count($updateArray)) {
                        // hook for adding new items to details fieldset
                        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminEditOrdersCustomerDetails'])) {
                            $params = array(
                                    'details_type' => $details_type,
                                    'updateArray' => &$updateArray
                            );
                            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['adminEditOrdersCustomerDetails'] as $funcRef) {
                                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                            }
                            // hook oef
                        }
                        $updateArray['orders_last_modified'] = time();
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders', 'orders_id=\'' . $orders_id . '\'', $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        $return_data['status'] = 'OK';
                        $return_data['reason'] = '';
                    }
                } else {
                    $erno_str = '<div class="erno_message well text-danger"><ul>' . implode("\n", $erno) . '</ul></div>';
                    $return_data['status'] = 'NOTOK';
                    $return_data['reason'] = $erno_str;
                }
                echo json_encode($return_data);
                exit();
            }
        }
        exit();
        break;
    case 'update_multishop':
        if ($this->ADMIN_USER) {
            $item = array();
            $item['html'] = mslib_befe::RunMultishopUpdate();
            $json = mslib_befe::array2json($item);
            echo $json;
        }
        exit();
        break;
    case 'admin_panel_ajax_search':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_panel_ajax_search.php');
        }
        exit();
        break;
    case 'ajax_products_search':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/ajax_products_search.php');
        exit();
        break;
    case 'ajax_attributes_option_value_search':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/ajax_attributes_option_value_search.php');
        exit();
        break;
    case 'ajax_products_attributes_search':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/ajax_products_attributes_search.php');
        exit();
        break;
    case 'ajax_products_staffelprice_search':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/ajax_products_staffelprice_search.php');
        exit();
        break;
    case 'getSpecialSections':
        if ($this->ADMIN_USER) {
            $content = '';
            $sections = array();
            $str = "SELECT pi_flexform from tt_content where hidden=0 and deleted=0 and list_type='multishop_pi1' and pi_flexform like '%section_code%'";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                $array = mslib_fe::xml2array($row['pi_flexform']);
                if (is_array($array) && count($array)) {
                    //if (is_array($array) && count($array) && $array['T3FlexForms']['data']['sheet'][0]['language']['field'][0]['value']=='specials'){
                    if ($array['T3FlexForms']['data']['sheet'][4]['language']['field'][0]['value'] == 'specials_section') {
                        $code = $array['T3FlexForms']['data']['sheet'][4]['language']['field'][3]['value'];
                        if ($code) {
                            $sections[$code] = $code;
                        }
                    }
                }
            }
            if (count($sections)) {
                asort($sections);
                $content .= '
					<label for="specials_portleds" class="control-label col-md-2">' . $this->pi_getLL('admin_show_in_section') . '</label>
					<div class="col-md-10">
					<div class="label_value_container">
					<div class="twocols_ul">
				';
                $i = 0;
                foreach ($sections as $section) {
                    $str = "SELECT ss.name from tx_multishop_specials s, tx_multishop_specials_sections ss where s.products_id='" . $this->post['products_id'] . "' and s.status=1 and s.specials_id=ss.specials_id and ss.name='" . addslashes($section) . "'";
                    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                    $rows = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
                    $content .= '
					<div class="checkbox checkbox-success"><input id="specials_sections_' . $i . '" name="specials_sections[]" type="checkbox" value="' . htmlspecialchars($section) . '" ' . ($rows ? 'checked' : '') . ' /><label for="specials_sections_' . $i . '">' . htmlspecialchars($section) . '</label></div>
					';
                    $i++;
                }
                $content .= '
				</div>
				</div>
				</div>
				';
                echo $content;
            }
        }
        exit();
        break;
    case 'get_usergroups':
        if ($this->ADMIN_USER) {
            $filter = array();
            // exclude admin usergroups
            $filter[] = 'uid NOT IN (' . implode(',', $this->excluded_userGroups) . ')';
            $filter[] = 'deleted=0 and hidden=0';
            $limit = 50;
            if (isset($this->get['q']) && !empty($this->get['q'])) {
                $filter[] = 'title like \'%' . addslashes($this->get['q']) . '%\'';
                $limit = '';
            }
            if (isset($this->get['preselected_id']) && !empty($this->get['preselected_id'])) {
                $filter[] = 'uid in (' . $this->get['preselected_id'] . ')';
            }
            $str = $GLOBALS['TYPO3_DB']->SELECTquery('*', // SELECT ...
                    'fe_groups', // FROM ...
                    implode(' AND ', $filter), // WHERE...
                    '', // GROUP BY...
                    'title', // ORDER BY...
                    $limit // LIMIT ...
            );
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $return_data = array();
            //$return_data[]=array('id' => '', 'text' => $this->pi_getLL('all'));
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
                    if (is_numeric($row['uid']) && $row['uid'] > 0) {
                        $return_data[] = array('id' => $row['uid'], 'text' => $row['title']);
                    }
                }
            }
            echo json_encode($return_data);
        }
        exit();
        break;
    case 'admin_ajax_product_relatives':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_ajax_product_relatives.php');
        }
        exit();
        break;
    case 'product_relatives_save':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/product_relatives_save.php');
        }
        exit();
        break;
    case 'admin_shipping_costs_ajax':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/admin_shipping_costs_ajax.php');
        }
        exit();
        break;
    case 'admin_ajax':
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/admin_ajax.php');
        }
        break;
    case 'captcha':
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/captcha.php');
        exit();
        break;
    case "products_to_basket":
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/products_to_basket.php');
        exit();
        break;
    case "remove_from_basket":
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/remove_from_basket.php');
        exit();
        break;
    case "get_staffel_price":
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_staffel_price.php');
        exit();
        break;
    case "get_tax_ruleset":
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/tax_ruleset.php');
        exit();
        break;
    case "copy_duplicate_product":
        if ($this->ADMIN_USER) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/copy_duplicate_product.php');
        }
        exit();
        break;
    case "get_discount":
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/get_discount.php');
        exit();
        break;
    case "cronjob":
        if ($this->get['tx_multishop_pi1']['encryption_key'] and ($this->get['tx_multishop_pi1']['encryption_key'] == $this->ms['MODULES']['MULTISHOP_ENCRYPTION_KEY'])) {
            require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/cronjob.php');
        }
        exit();
        break;
    case "ultrasearch_server":
        if (strstr($this->ms['MODULES']['ULTRASEARCH_SERVER_TYPE'], "..")) {
            die('error in ULTRASEARCH_SERVER_TYPE value');
        } else {
            if (strstr($this->ms['MODULES']['ULTRASEARCH_SERVER_TYPE'], "/")) {
                // relative mode
                require($this->DOCUMENT_ROOT . $this->ms['MODULES']['ULTRASEARCH_SERVER_TYPE'] . '.php');
            } else {
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/ajax_pages/includes/ultrasearch_server/default.php');
            }
        }
        exit();
        break;
    case 'method_sortables':
        if ($this->ADMIN_USER) {
            $key = 'multishop_shipping_method';
            if (is_array($this->post[$key]) and count($this->post[$key])) {
                $no = 1;
                foreach ($this->post[$key] as $prod_id) {
                    if (is_numeric($prod_id)) {
                        $where = "id = " . $prod_id;
                        $updateArray = array(
                                'sort_order' => $no
                        );
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods', $where, $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        $no++;
                    }
                }
            }
            $key = 'multishop_payment_method';
            if (is_array($this->post[$key]) and count($this->post[$key])) {
                $no = 1;
                foreach ($this->post[$key] as $prod_id) {
                    if (is_numeric($prod_id)) {
                        $where = "id = " . $prod_id;
                        $updateArray = array(
                                'sort_order' => $no
                        );
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_payment_methods', $where, $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        $no++;
                    }
                }
            }
        }
        exit();
        break;
    case 'zone_method_sortables':
        if ($this->ADMIN_USER) {
            $key = 'shipping_zone_';
            if (is_array($this->post[$key]) and count($this->post[$key])) {
                $no = 1;
                foreach ($this->post[$key] as $zone_id => $smid) {
                    foreach ($smid as $shipping_id) {
                        if (is_numeric($shipping_id)) {
                            $where = "zone_id = '" . $zone_id . "' and shipping_method_id = '" . $shipping_id . "'";
                            $updateArray = array(
                                    'sort_order' => $no
                            );
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_shipping_methods_to_zones', $where, $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            $no++;
                        }
                    }
                }
            }
            $key = 'payment_zone_';
            if (is_array($this->post[$key]) and count($this->post[$key])) {
                $no = 1;
                foreach ($this->post[$key] as $zone_id => $pmid) {
                    foreach ($pmid as $payment_id) {
                        if (is_numeric($payment_id)) {
                            $where = "zone_id = '" . $zone_id . "' and payment_method_id = '" . $payment_id . "'";
                            $updateArray = array(
                                    'sort_order' => $no
                            );
                            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_payment_methods_to_zones', $where, $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            $no++;
                        }
                    }
                }
            }
        }
        exit();
        break;
    case 'product':
        if ($this->ADMIN_USER) {
            // custom page hook that can be controlled by third-party plugin
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['ajaxSortingProducts'])) {
                $params = array();
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['ajaxSortingProducts'] as $funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                }
            } else {
                $cat_id = mslib_fe::RemoveXSS(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('catid'));
                $getPost = $this->post['productlisting'];
                $sort_type = $this->ms['MODULES']['PRODUCTS_LISTING_SORT_ORDER_OPTION'];
                if ($sort_type == 'desc') {
                    $no = time();
                } else {
                    $no = 1;
                }
                foreach ($getPost as $prod_id) {
                    if (is_numeric($prod_id) and is_numeric($cat_id)) {
                        $where = 'categories_id = ' . $cat_id . ' and products_id = ' . $prod_id;
                        $updateArray = array(
                                'sort_order' => $no
                        );
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products_to_categories', $where, $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_products', "products_id = $prod_id", $updateArray);
                        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        if ($this->ms['MODULES']['FLAT_DATABASE']) {
                            // if the flat database module is enabled we have to sync the changes to the flat table
                            mslib_befe::convertProductToFlat($prod_id);
                        }
                        if ($sort_type == 'desc') {
                            $no--;
                        } else {
                            $no++;
                        }
                    }
                }
            }
        }
        exit();
        break;
    case 'manufacturers':
        if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
            $getPost = $this->post['sortable_manufacturer'];
            $no = 1;
            foreach ($getPost as $man_id) {
                if (is_numeric($man_id)) {
                    $where = "manufacturers_id = $man_id";
                    $updateArray = array(
                            'sort_order' => $no
                    );
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_manufacturers', $where, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    $no++;
                }
            }
        }
        exit();
        break;
    case 'menu':
        if ($this->ADMIN_USER) {
            $getPost = $this->post['sortable_maincat'];
            $no = 1;
            foreach ($getPost as $cat_id) {
                if (is_numeric($cat_id)) {
                    $where = "categories_id = $cat_id";
                    $updateArray = array(
                            'sort_order' => $no
                    );
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', $where, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    $no++;
                }
            }
        }
        exit();
        break;
    case 'subcatlisting':
        if ($this->ADMIN_USER) {
            $getPost = $this->post['sortable_subcat'];
            $no = 1;
            foreach ($getPost as $cat_id) {
                if (is_numeric($cat_id)) {
                    $where = "categories_id = $cat_id";
                    $updateArray = array(
                            'sort_order' => $no
                    );
                    // FOR PROJECTS WHERE YOU WANT TO GROUP BY COLUMN NUMBER
                    if ($this->post['tx_multishop_pi1']['col']) {
                        $col = str_replace('msCol', '', $this->post['tx_multishop_pi1']['col']);
                        if (is_numeric($col)) {
                            $updateArray['col_position'] = $col;
                        }
                    }
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_categories', $where, $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                    $no++;
                }
            }
        }
        exit();
        break;
    case 'get_micro_download':
        // this script is for downloading a paid micro download
        if (is_numeric($this->get['orders_id']) and $this->get['code']) {
            $str = "SELECT file_locked, file_downloaded, file_remote_location, file_number_of_downloads, orders_products_id, file_label, file_location, products_name from tx_multishop_orders_products where orders_id=" . $this->get['orders_id'] . " and file_download_code='" . addslashes($this->get['code']) . "'";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0) {
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
                if (($row['file_locked'] && !$this->ADMIN_USER) || ($row['file_locked'] && $this->ADMIN_USER && !isset($this->get['tx_multishop_pi1']['from_interface']))) {
                    echo 'Sorry, but the maximum number of downloads has been exceeded.';
                    exit();
                } else {
                    $body_data = '';
                    // custom page hook that can be controlled by third-party plugin
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['get_micro_downloadPreProc'])) {
                        $params = array(
                                'row' => &$row
                        );
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/ajax_pages/core.php']['get_micro_downloadPreProc'] as $funcRef) {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                        }
                    }
                    // custom page hook that can be controlled by third-party plugin eof
                    // download action is valid. lets proceed
                    if (!$row['file_location'] && $row['file_remote_location']) {
                        // file is stored on remote location. lets download it and send it to the browser
                        $body_data = mslib_fe::file_get_contents($row['file_remote_location']);
                        if (!$row['file_label']) {
                            $row['file_label'] = basename($row['file_remote_location']);
                        }
                        if (!$row['file_label']) {
                            $row['file_label'] = $row['products_name'];
                        }
                    } elseif ($row['file_location'] and file_exists($row['file_location'])) {
                        $body_data = mslib_fe::file_get_contents($row['file_location']);
                        if (!$row['file_label']) {
                            $row['file_label'] = $row['products_name'];
                        }
                    }
                    if ($body_data) {
                        if (!isset($this->get['tx_multishop_pi1']['from_interface'])) {
                            $query = "update tx_multishop_orders_products set file_downloaded=(file_downloaded+1) where orders_products_id='" . $row['orders_products_id'] . "'";
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            $row['file_downloaded']++;
                            if ($row['file_downloaded'] >= $row['file_number_of_downloads']) {
                                // maximum allowed downloads exceeded. lets lock it.
                                $updateArray = array(
                                        'file_locked' => '1'
                                );
                                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders_products', 'orders_products_id=' . addslashes($row['orders_products_id']), $updateArray);
                                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                            }
                            // log the download request for statistic purposes
                            $updateArray = array();
                            $updateArray['orders_id'] = $this->get['orders_id'];
                            $updateArray['orders_products_id'] = $row['orders_products_id'];
                            $updateArray['ip_address'] = $this->REMOTE_ADDR;
                            $updateArray['date_of_download'] = time();
                            $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_orders_products_downloads', $updateArray);
                            $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                        }
                        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                        header("Cache-Control: no-cache, must-revalidate");
                        header("Pragma: no-cache");
                        header("Content-type: application/x-msexcel");
                        header("Content-Disposition: attachment; filename=\"" . basename($row['file_label']) . "\"");
                        header("Content-Description: TYPO3 Multishop Generated Data");
                        echo $body_data;
                        exit();
                    }
                }
            }
        }
        exit();
        break;
    case 'get_micro_download_by_admin':
        // this script is for downloading a micro download by the admin user
        if ($this->ADMIN_USER) {
            if (is_numeric($this->get['language_id']) and is_numeric($this->get['products_id'])) {
                $str = "SELECT file_label, file_location from tx_multishop_products_description where language_id='" . $this->get['language_id'] . "' and products_id='" . $this->get['products_id'] . "'";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0) {
                    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
                    if ($row['file_location'] and file_exists($row['file_location'])) {
                        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                        header("Cache-Control: no-cache, must-revalidate");
                        header("Pragma: no-cache");
                        header("Content-type: application/x-msexcel");
                        header("Content-Disposition: attachment; filename=\"" . basename($row['file_label']) . "\"");
                        header("Content-Description: TYPO3 Multishop Generated Data");
                        @readfile($row['file_location']);
                        exit();
                    }
                }
            }
        }
        exit();
        break;
    // psp thank you or error pages eof
    case 'sitemap_generator':
        if (isset($this->get['tx_multishop_pi1']['encryptionKey']) && !empty($this->get['tx_multishop_pi1']['encryptionKey'])) {
            if ($this->get['tx_multishop_pi1']['encryptionKey'] == $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) {
                set_time_limit(7200);
                ignore_user_abort(true);
                $content = '';
                $log_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/sitemap_tmp.txt';
                $sitemap_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/sitemap_' . mslib_fe::rewritenamein($this->HTTP_HOST) . '.txt';
                $sitemap_file_web_path = 'uploads/tx_multishop/sitemap_' . mslib_fe::rewritenamein($this->HTTP_HOST) . '.txt';
                $max_pages = 2;
                $prefix_domain = $this->FULL_HTTP_URL;
                @unlink($log_file);
                $log_xml_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/sitemap_xml.txt';
                $sitemap_xml_file = $this->DOCUMENT_ROOT . 'uploads/tx_multishop/sitemap_' . mslib_fe::rewritenamein($this->HTTP_HOST) . '.xml';
                $sitemap_xml_file_web_path = 'uploads/tx_multishop/sitemap_' . mslib_fe::rewritenamein($this->HTTP_HOST) . '.xml';
                $prefix_domain = $this->FULL_HTTP_URL;
                @unlink($log_xml_file);
                $tmpContent = '';
                $tmpContent .= '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
                $tmpContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                file_put_contents($log_xml_file, $tmpContent, FILE_APPEND | LOCK_EX);
                $link = $prefix_domain . mslib_fe::typolink($this->shop_pid);
                // TXT
                $tmpContent = $link . "\n";
                file_put_contents($log_file, $tmpContent, FILE_APPEND | LOCK_EX);
                // XML
                $tmpContent = '<url>' . "\n";
                $tmpContent .= "\t" . '<loc>' . $link . '</loc>' . "\n";
                $tmpContent .= "\t" . '<lastmod>' . date('c') . '</lastmod>' . "\n";
                $tmpContent .= "\t" . '<changefreq>daily</changefreq>' . "\n";
                $tmpContent .= "\t" . '<priority>0.5</priority>' . "\n";
                $tmpContent .= '</url>' . "\n";
                file_put_contents($log_xml_file, $tmpContent, FILE_APPEND | LOCK_EX);
                $tmpContent = '';
                if (!$this->get['skip_categories']) {
                    $qry = $GLOBALS['TYPO3_DB']->sql_query("SELECT * from tx_multishop_categories c, tx_multishop_categories_description cd where c.categories_id=cd.categories_id and c.status=1 and c.page_uid='" . $this->showCatalogFromPage . "'");
                    while (($categories = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                        $level = 0;
                        $cats = mslib_fe::Crumbar($categories['categories_id']);
                        $cats = array_reverse($cats);
                        $where = '';
                        if (count($cats) > 0) {
                            foreach ($cats as $item) {
                                $where .= "categories_id[" . $level . "]=" . $item['id'] . "&";
                                $level++;
                            }
                            $where = substr($where, 0, (strlen($where) - 1));
                            $where .= '&';
                        }
                        $link = $prefix_domain . mslib_fe::typolink($this->conf['products_listing_page_pid'], '' . $where . '&tx_multishop_pi1[page_section]=products_listing');
                        // TXT
                        $tmpContent = $link . "\n";
                        file_put_contents($log_file, $tmpContent, FILE_APPEND | LOCK_EX);
                        // XML
                        $tmpContent = '<url>' . "\n";
                        $tmpContent .= "\t" . '<loc>' . $link . '</loc>' . "\n";
                        if ($categories['last_modified']) {
                            $tmpContent .= "\t" . '<lastmod>' . ($categories['last_modified'] > 0 ? date('c', $categories['last_modified']) : '') . '</lastmod>' . "\n";
                        }
                        $tmpContent .= "\t" . '<changefreq>daily</changefreq>' . "\n";
                        $tmpContent .= "\t" . '<priority>0.5</priority>' . "\n";
                        $tmpContent .= '</url>' . "\n";
                        file_put_contents($log_xml_file, $tmpContent, FILE_APPEND | LOCK_EX);
                    }
                }
                // lets create the products sitemap
                if (!$this->get['skip_products']) {
                    $filterProducts = array();
                    $filterProducts[] = 'products_status=1';
                    $filterProducts[] = 'page_uid=' . $this->showCatalogFromPage;
                    // hook
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_sitemap_generator.php']['sitemapGeneratorProductsQueryFilter'])) {
                        $params = array(
                                'filterProducts' => &$filterProducts
                        );
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/admin_sitemap_generator.php']['sitemapGeneratorProductsQueryFilter'] as $funcRef) {
                            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                        }
                    }
                    // hook eof
                    $qry = $GLOBALS['TYPO3_DB']->sql_query("SELECT products_id from tx_multishop_products where " . implode(" and ", $filterProducts));
                    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                        $product = mslib_fe::getProduct($row['products_id']);
                        $where = '';
                        if ($product['categories_id']) {
                            // get all cats to generate multilevel fake url
                            $level = 0;
                            $cats = mslib_fe::Crumbar($product['categories_id']);
                            $cats = array_reverse($cats);
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
                        $link = '';
                        if (!empty($where)) {
                            $link = $prefix_domain . mslib_fe::typolink($this->conf['products_detail_page_pid'], '&' . $where . '&products_id=' . $product['products_id'] . '&tx_multishop_pi1[page_section]=products_detail');
                            // TXT
                            $tmpContent = $link . "\n";
                            file_put_contents($log_file, $tmpContent, FILE_APPEND | LOCK_EX);
                            // XML
                            $tmpContent = '<url>' . "\n";
                            $tmpContent .= "\t" . '<loc>' . $link . '</loc>' . "\n";
                            if ($product['products_last_modified']) {
                                $tmpContent .= "\t" . '<lastmod>' . ($product['products_last_modified'] > 0 ? date('c', $product['products_last_modified']) : '') . '</lastmod>' . "\n";
                            }
                            $tmpContent .= "\t" . '<changefreq>daily</changefreq>' . "\n";
                            $tmpContent .= "\t" . '<priority>0.5</priority>' . "\n";
                            $tmpContent .= '</url>' . "\n";
                            file_put_contents($log_xml_file, $tmpContent, FILE_APPEND | LOCK_EX);
                        }
                    }
                }
                if (!$this->get['skip_manufacturers']) {
                    // MANUFACTURERS
                    $qry = $GLOBALS['TYPO3_DB']->sql_query("SELECT manufacturers_id from tx_multishop_manufacturers m where m.status=1");
                    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
                        $link = $prefix_domain . mslib_fe::typolink($this->conf['search_page_pid'], '&tx_multishop_pi1[page_section]=manufacturers_products_listing&manufacturers_id=' . $row['manufacturers_id']);
                        if ($link) {
                            // TXT
                            $tmpContent = $link . "\n";
                            file_put_contents($log_file, $tmpContent, FILE_APPEND | LOCK_EX);
                            // XML
                            $tmpContent = '<url>' . "\n";
                            $tmpContent .= "\t" . '<loc>' . $link . '</loc>' . "\n";
                            if ($row['last_modified']) {
                                $tmpContent .= "\t" . '<lastmod>' . ($row['last_modified'] > 0 ? date('c', $row['last_modified']) : '') . '</lastmod>' . "\n";
                            }
                            $tmpContent .= "\t" . '<changefreq>daily</changefreq>' . "\n";
                            $tmpContent .= "\t" . '<priority>0.5</priority>' . "\n";
                            $tmpContent .= '</url>' . "\n";
                            file_put_contents($log_xml_file, $tmpContent, FILE_APPEND | LOCK_EX);
                        }
                    }
                }
                $tmpContent = '</urlset>';
                file_put_contents($log_xml_file, $tmpContent, FILE_APPEND | LOCK_EX);
                $tmpContent = '';
                @unlink($sitemap_file);
                @copy($log_file, $sitemap_file);
                @unlink($sitemap_xml_file);
                @copy($log_xml_file, $sitemap_xml_file);
                $content .= '<div class="main-heading"><h1>' . $this->pi_getLL('admin_label_sitemap_creator') . '</h1></div>';
                $content .= '<p>' . $this->pi_getLL('admin_label_your_sitemap_has_been_created') . '</p>' . $this->pi_getLL('admin_label_you_can_download_it_here') . ':<br/>
				TXT: <a href="' . $sitemap_file_web_path . '" target="_blank">' . $sitemap_file_web_path . '</a><br/>
				XML: <a href="' . $sitemap_xml_file_web_path . '" target="_blank">' . $sitemap_xml_file_web_path . '</a><br/>
				';
            } else {
                $content .= $this->pi_getLL('encryption_key_not_match');
                die();
            }
        } else {
            $content .= $this->pi_getLL('encryption_key_not_match');
            die();
        }
        break;
    case 'custom_page':
        // custom page hook that can be controlled by third-party plugin
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customAjaxPage'])) {
            $params = array(
                    'content' => &$content
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.mslib_fe.php']['customAjaxPage'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
            if ($this->get['tx_multishop_pi1']['output'] == 'json') {
                echo $content;
                exit(0);
            }
        }
        // custom page hook that can be controlled by third-party plugin eof
        break;
    default:
        // load by TypoScript
        if ($this->ms['page'] && $this->conf['ajax_pages.'][$this->ms['page']]) {
            $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->ms['page']) . $this->conf['ajax_pages.'][$this->ms['page']] . '.php';
            if (file_exists($path)) {
                require($path);
            }
        }
        break;
}
?>
