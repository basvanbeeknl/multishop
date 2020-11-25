<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
// now parse all the objects in the tmpl file
if ($this->conf['admin_edit_manufacturer_tmpl_path']) {
    $template = $this->cObj->fileResource($this->conf['admin_edit_manufacturer_tmpl_path']);
} else {
    $template = $this->cObj->fileResource(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'templates/admin_edit_manufacturer.tmpl');
}
// Extract the subparts from the template
$subparts = array();
$subparts['template'] = $this->cObj->getSubpart($template, '###TEMPLATE###');
$subparts['manufacturers_images'] = $this->cObj->getSubpart($subparts['template'], '###MANUFACTURER_IMAGES###');
$subparts['manufacturers_content'] = $this->cObj->getSubpart($subparts['template'], '###MANUFACTURERS_CONTENT###');
$subparts['manufacturers_meta'] = $this->cObj->getSubpart($subparts['template'], '###MANUFACTURERS_META###');
$subpartArray = array();
$subpartArray['###POSTFORM_ERROR_ALERT###'] = '';
if ($this->get['manufacturers_id']) {
    $_REQUEST['manufacturers_id'] = $this->get['manufacturers_id'];
}
$GLOBALS['TSFE']->additionalHeaderData[] = '
<script type="text/javascript">
window.onload = function(){
  var text_input = document.getElementById (\'manufacturers_name\');
  text_input.focus ();
  text_input.select ();
}
</script>';
$update_manufacturers_image = '';
// hidden filename that is retrieved from the ajax upload
if ($this->post['ajax_manufacturers_image']) {
    $update_manufacturers_image = $this->post['ajax_manufacturers_image'];
}
if ($this->post and is_array($_FILES) and count($_FILES)) {
    if ($this->post['manufacturers_name']) {
        $this->post['manufacturers_name'] = trim($this->post['manufacturers_name']);
    }
    if (is_array($_FILES) and count($_FILES)) {
        $file = $_FILES['manufacturers_image'];
        if ($file['tmp_name']) {
            $size = getimagesize($file['tmp_name']);
            if ($size[0] > 5 and $size[1] > 5) {
                $imgtype = mslib_befe::exif_imagetype($file['tmp_name']);
                if ($imgtype) {
                    // valid image
                    $ext = image_type_to_extension($imgtype, false);
                    $i = 0;
                    $filename = mslib_fe::rewritenamein($this->post['manufacturers_name'][0]) . '.' . $ext;
                    $folder = mslib_befe::getImagePrefixFolder($filename);
                    if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder)) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder);
                    }
                    $folder .= '/';
                    $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder . $filename;
                    if (file_exists($target)) {
                        do {
                            $filename = mslib_fe::rewritenamein($this->post['manufacturers_name'][0]) . '-' . $i . '.' . $ext;
                            $folder = mslib_befe::getImagePrefixFolder($filename);
                            if (!is_dir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder)) {
                                \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder);
                            }
                            $folder .= '/';
                            $target = $this->DOCUMENT_ROOT . $this->ms['image_paths']['manufacturers']['original'] . '/' . $folder . $filename;
                            $i++;
                        } while (file_exists($target));
                    }
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $update_manufacturers_image = mslib_befe::resizeManufacturerImage($target, $filename, $this->DOCUMENT_ROOT . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey), 1);
                    }
                }
            }
        }
    }
}
if ($this->post) {
    $postErno = array();
    if ($this->post['manufacturers_name']) {
        $this->post['manufacturers_name'] = trim($this->post['manufacturers_name']);
    }
    $updateArray = array();
    $updateArray['manufacturers_name'] = $this->post['manufacturers_name'];
    $updateArray['status'] = $this->post['status'];
    if ($update_manufacturers_image) {
        $updateArray['manufacturers_image'] = $update_manufacturers_image;
    }
    if ($_REQUEST['action'] == 'add_manufacturer') {
        $updateArray['date_added'] = time();
        $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_manufacturers', $updateArray);
        if (!$res = $GLOBALS['TYPO3_DB']->sql_query($query)) {
            $postErno[] = array(
                    'status' => 'error',
                    'message' => $GLOBALS['TYPO3_DB']->sql_error()
            );
        } else {
            $manufacturers_id = $GLOBALS['TYPO3_DB']->sql_insert_id();
            if ($manufacturers_id) {
                $updateArray2 = array();
                $updateArray2['manufacturers_id'] = $manufacturers_id;
                $updateArray2['language_id'] = $this->sys_language_uid;
                $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_manufacturers_info', $updateArray2);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                $updateArray['manufacturers_id'] = $manufacturers_id;
            }
        }
        // custom hook that can be controlled by third-party plugin
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturer.php']['insertManufacturerPostHook'])) {
            $params = array(
                    'manufacturers_id' => $manufacturers_id
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturer.php']['insertManufacturerPostHook'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // custom hook that can be controlled by third-party plugin eof
    } else {
        if ($this->post['manufacturers_id']) {
            $updateArray['last_modified'] = time();
            $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_manufacturers', 'manufacturers_id=\'' . $this->post['manufacturers_id'] . '\'', $updateArray);
            if (!$res = $GLOBALS['TYPO3_DB']->sql_query($query)) {
                $postErno[] = array(
                        'status' => 'error',
                        'message' => $GLOBALS['TYPO3_DB']->sql_error()
                );
            } else {
                $manufacturers_id = $this->post['manufacturers_id'];
                // custom hook that can be controlled by third-party plugin
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturer.php']['updateManufacturerPostHook'])) {
                    $params = array(
                            'manufacturers_id' => $manufacturers_id
                    );
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturer.php']['updateManufacturerPostHook'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                // custom hook that can be controlled by third-party plugin eof
            }
        }
    }
    if ($manufacturers_id) {
        if ($this->ms['MODULES']['ADMIN_CROP_MANUFACTURERS_IMAGES']) {
            if ($update_manufacturers_image) {
                $image_filename = $update_manufacturers_image;
                $image_crop_data = mslib_befe::getRecord($image_filename, 'tx_multishop_manufacturers_crop_image_coordinate', 'image_filename', array('manufacturers_id=\'0\''));
                if (is_array($image_crop_data) && $image_crop_data['id'] > 0) {
                    $updateArray = array();
                    $updateArray['manufacturers_id'] = $manufacturers_id;
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_manufacturers_crop_image_coordinate', 'id=\'' . $image_crop_data['id'] . '\'', $updateArray);
                    $res = $GLOBALS['TYPO3_DB']->sql_query($query);
                }
            }
        }
        foreach ($this->post['content'] as $key => $value) {
            $str = "select 1 from tx_multishop_manufacturers_cms where manufacturers_id='" . $manufacturers_id . "' and language_id='" . $key . "'";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $updateArray = array();
            $updateArray['content'] = $this->post['content'][$key];
            if ($updateArray['content'] == "<p><br></p>\r\n") {
                $updateArray['content'] = '';
            }
            $updateArray['content_footer'] = $this->post['content_footer'][$key];
            if ($updateArray['content_footer'] == "<p><br></p>\r\n") {
                $updateArray['content_footer'] = '';
            }
            $updateArray['shortdescription'] = $this->post['shortdescription'][$key];
            $updateArray['meta_title'] = $this->post['meta_title'][$key];
            $updateArray['meta_keywords'] = $this->post['meta_keywords'][$key];
            $updateArray['meta_description'] = $this->post['meta_description'][$key];
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) > 0) {
                $query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_manufacturers_cms', 'manufacturers_id=\'' . $manufacturers_id . '\' and language_id=\'' . $key . '\'', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            } else {
                $updateArray['manufacturers_id'] = $manufacturers_id;
                $updateArray['language_id'] = $key;
                $query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_multishop_manufacturers_cms', $updateArray);
                $res = $GLOBALS['TYPO3_DB']->sql_query($query);
            }
        }
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturers.php']['adminEditManufacturersSaveHook'])) {
            $params = array(
                    'manufacturers_id' => $manufacturers_id
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturers.php']['adminEditManufacturersSaveHook'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        if (!count($postErno)) {
            // clear the multishop cache
            if ($this->ms['MODULES']['AUTOMATICALLY_CLEAR_MULTISHOP_CACHE_ON_CATALOG_CHANGES']) {
                mslib_befe::cacheLite('delete_all');
            }
            if (isset($this->post['SaveClose'])) {
                if (strpos($this->post['tx_multishop_pi1']['referrer'], 'action=edit_manufacturer') === false && strpos($this->post['tx_multishop_pi1']['referrer'], 'action=add_manufacturer') === false && $this->post['tx_multishop_pi1']['referrer']) {
                    header("Location: " . $this->post['tx_multishop_pi1']['referrer']);
                    exit();
                } else {
                    header("Location: " . $this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=admin_manufacturers', 1));
                    exit();
                }
            } else {
                if (isset($this->post['Submit'])) {
                    header("Location: " . $this->FULL_HTTP_URL . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_manufacturer&manufacturers_id=' . $manufacturers_id . '&action=edit_manufacturer', 1));
                    exit();
                }
            }
        }
    }
    if (count($postErno)) {
        $returnMarkup = '
	<div style="display:none" id="msAdminPostMessage">
	<table class="table table-striped table-bordered">
	<thead>
	<tr>
		<th class="text-center">Status</th>
		<th>Message</th>
	</tr>
	</thead>
	<tbody>
	';
        foreach ($postErno as $item) {
            switch ($item['status']) {
                case 'error':
                    $item['status'] = '<span class="fa-stack text-danger"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-thumbs-down fa-stack-1x fa-inverse"></i></span>';
                    break;
                case 'info':
                    $item['status'] = '<span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-thumbs-up fa-stack-1x fa-inverse"></i></span>';
                    break;
            }
            $returnMarkup .= '<tr><td class="text-center">' . $item['status'] . '</td><td>' . $item['message'] . '</td></tr>' . "\n";
        }
        $returnMarkup .= '</tbody></table></div>';
        $subpartArray['###POSTFORM_ERROR_ALERT###'] = $returnMarkup;
        $GLOBALS['TSFE']->additionalHeaderData[] = '<script type="text/javascript" data-ignore="1">
	jQuery(document).ready(function ($) {
		$.confirm({
			title: \'\',
			content: $(\'#msAdminPostMessage\').html()
		});
	});
	</script>
	';
    }
}
if ($_REQUEST['action'] == 'edit_manufacturer') {
    $str = "SELECT * from tx_multishop_manufacturers m where m.manufacturers_id='" . $_REQUEST['manufacturers_id'] . "'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $manufacturer = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry);
    $str = "SELECT * from tx_multishop_manufacturers_cms where manufacturers_id='" . $_REQUEST['manufacturers_id'] . "'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        $lngman[$row['language_id']] = $row;
    }
}
$manufacturersImage = '';
$manufacturersContent = '';
$manufacturersMeta = '';
if ($manufacturer['manufacturers_id'] or $_REQUEST['action'] == 'add_manufacturer') {
    if ($_REQUEST['action'] == 'edit_manufacturer' and $manufacturer['manufacturers_image']) {
        $tmpcontent .= '<img src="' . mslib_befe::getImagePath($manufacturer['manufacturers_image'], 'manufacturers', 'normal') . '">';
        $tmpcontent .= ' <a href="' . mslib_fe::typolink($this->shop_pid . ',2003',
                        '&tx_multishop_pi1[page_section]=edit_manufacturer&manufacturers_id=' . $_REQUEST['manufacturers_id'] . '&action=edit_manufacturer&delete_image=manufacturers_image') . '" onclick="return confirm(\'Are you sure?\')"><img src="' . $this->FULL_HTTP_URL_MS . 'templates/images/icons/delete2.png" border="0" alt="delete image"></a>';
        $markerArray = array();
        $markerArray['MANUFACTURER_IMAGES_SRC'] = mslib_befe::getImagePath($manufacturer['manufacturers_image'], 'manufacturers', 'normal');
        $markerArray['MANUFACTURER_IMAGES_DELETE_LINK'] = mslib_fe::typolink($this->shop_pid . ',2003', '&tx_multishop_pi1[page_section]=edit_manufacturer&manufacturers_id=' . $_REQUEST['manufacturers_id'] . '&action=edit_manufacturer&delete_image=manufacturers_image');
        $markerArray['FULL_HTTP_URL'] = $this->FULL_HTTP_URL_MS;
        $markerArray['ADMIN_LABEL_JS_ARE_YOU_SURE'] = $this->pi_getLL('admin_label_js_are_you_sure');
        $markerArray['MANUFACTURER_IMAGES_FILENAME'] = $manufacturer['manufacturers_image'];
        $markerArray['###MANUFACTURERS_IMAGE_CROP_BUTTON'] = '';
        if (isset($manufacturer['manufacturers_image']) && !empty($manufacturer['manufacturers_image'])) {
            $markerArray['MANUFACTURERS_IMAGE_CROP_BUTTON'] = '<a href="#" id="cropEditor" rel="' . $manufacturer['manufacturers_image'] . '"><span>crop</span></a>';
        }
        $manufacturersImage .= $this->cObj->substituteMarkerArray($subparts['manufacturers_images'], $markerArray, '###|###');
    }
    foreach ($this->languages as $key => $language) {
        $markerArray = array();
        $markerArray['IS_COLLAPSED'] = ($language['uid'] > 0 && $this->ms['MODULES']['FOLD_FOREIGN_LANGUAGE_INPUT_FIELDS']) ? ' collapsed' : '';
        $markerArray['IS_UNFOLD'] = ($language['uid'] === 0 || !$this->ms['MODULES']['FOLD_FOREIGN_LANGUAGE_INPUT_FIELDS']) ? ' in' : '';
        $markerArray['LANGUAGE_UID'] = $language['uid'];
        $markerArray['LABEL_MANUFACTURER_LANGUAGE'] = $this->pi_getLL('language');
        if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3 . 'sysext/cms/tslib/media/flags/flag_' . $language['flag'] . '.gif')) {
            $markerArray['MANUFACTURER_CONTENT_FLAG'] = '<img src="' . $this->FULL_HTTP_URL_TYPO3 . 'sysext/cms/tslib/media/flags/flag_' . $language['flag'] . '.gif"> ';
        } else {
            $markerArray['MANUFACTURER_CONTENT_FLAG'] = '';
        }
        $markerArray['MANUFACTURER_CONTENT_TITLE'] = $language['title'];
        $markerArray['LABEL_MANUFACTURER_SHORT_DESCRIPTION'] = $this->pi_getLL('admin_short_description');
        $markerArray['VALUE_MANUFACTURER_SHORT_DESCRIPTION'] = htmlspecialchars($lngman[$language['uid']]['shortdescription']);
        $markerArray['LABEL_MANUFACTURER_CONTENT'] = $this->pi_getLL('content');
        $markerArray['VALUE_MANUFACTURER_CONTENT'] = htmlspecialchars($lngman[$language['uid']]['content']);
        $markerArray['LABEL_MANUFACTURER_CONTENT_FOOTER'] = $this->pi_getLL('content') . ' ' . $this->pi_getLL('bottom');
        $markerArray['VALUE_MANUFACTURER_CONTENT_FOOTER'] = htmlspecialchars($lngman[$language['uid']]['content_footer']);
        $manufacturersContent .= $this->cObj->substituteMarkerArray($subparts['manufacturers_content'], $markerArray, '###|###');
        // manufacturers meta
        $markerArray = array();
        $markerArray['IS_COLLAPSED'] = ($language['uid'] > 0 && $this->ms['MODULES']['FOLD_FOREIGN_LANGUAGE_INPUT_FIELDS']) ? ' collapsed' : '';
        $markerArray['IS_UNFOLD'] = ($language['uid'] === 0 || !$this->ms['MODULES']['FOLD_FOREIGN_LANGUAGE_INPUT_FIELDS']) ? ' in' : '';
        $markerArray['LANGUAGE_UID'] = $language['uid'];
        $markerArray['LABEL_MANUFACTURER_META_LANGUAGE'] = $this->pi_getLL('language');
        $markerArray['MANUFACTURER_META_TITLE'] = $language['title'];
        if ($language['flag'] && file_exists($this->DOCUMENT_ROOT_TYPO3 . 'sysext/cms/tslib/media/flags/flag_' . $language['flag'] . '.gif')) {
            $markerArray['MANUFACTURER_META_FLAG'] = '<img src="' . $this->FULL_HTTP_URL_TYPO3 . 'sysext/cms/tslib/media/flags/flag_' . $language['flag'] . '.gif"> ';
        } else {
            $markerArray['MANUFACTURER_META_FLAG'] = '';
        }
        $markerArray['ADMIN_LABEL_INPUT_META_TITLE'] = $this->pi_getLL('admin_label_input_meta_title');
        $markerArray['ADMIN_LABEL_INPUT_META_KEYWORDS'] = $this->pi_getLL('admin_label_input_meta_keywords');
        $markerArray['ADMIN_LABEL_INPUT_META_DESCRIPTION'] = $this->pi_getLL('admin_label_input_meta_description');
        $markerArray['VALUE_MANUFACTURER_META_TITLE'] = htmlspecialchars($lngman[$language['uid']]['meta_title']);
        $markerArray['VALUE_MANUFACTURER_META_KEYWORDS'] = htmlspecialchars($lngman[$language['uid']]['meta_keywords']);
        $markerArray['VALUE_MANUFACTURER_META_DESCRIPTION'] = htmlspecialchars($lngman[$language['uid']]['meta_description']);
        $manufacturersMeta .= $this->cObj->substituteMarkerArray($subparts['manufacturers_meta'], $markerArray, '###|###');
    }
    if ($this->post['tx_multishop_pi1']['referrer']) {
        $subpartArray['###VALUE_REFERRER###'] = $this->post['tx_multishop_pi1']['referrer'];
    } else {
        $subpartArray['###VALUE_REFERRER###'] = $_SERVER['HTTP_REFERER'];
    }
    if ($_REQUEST['action'] == 'add_manufacturer') {
        $subpartArray['###MANUFACTURER_FORM_HEADING###'] = $this->pi_getLL('add_manufacturer');
    } else {
        if ($_REQUEST['action'] == 'edit_manufacturer') {
            $subpartArray['###MANUFACTURER_FORM_HEADING###'] = $this->pi_getLL('edit_manufacturer');
        }
    }
    if ($manufacturer['status'] or $_REQUEST['action'] == 'add_manufacturer') {
        $subpartArray['###MANUFACTURER_VISIBLE_CHECKED###'] = 'checked="checked"';
    } else {
        $subpartArray['###MANUFACTURER_VISIBLE_CHECKED###'] = '';
    }
    if (!$manufacturer['status'] and $_REQUEST['action'] == 'edit_manufacturer') {
        $subpartArray['###MANUFACTURER_NOT_VISIBLE_CHECKED###'] = 'checked="checked"';
    } else {
        $subpartArray['###MANUFACTURER_NOT_VISIBLE_CHECKED###'] = '';
    }
    $subpartArray['###MANUFACTURER_ID###'] = $manufacturer['manufacturers_id'];
    if ($_REQUEST['manufacturers_id']) {
        $subpartArray['###MANUFACTURER_EDIT_FORM_URL###'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=' . $_REQUEST['action'] . '&action=edit_manufacturer&manufacturers_id=' . $_REQUEST['manufacturers_id']);
    } else {
        $subpartArray['###MANUFACTURER_EDIT_FORM_URL###'] = mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=' . $_REQUEST['action'] . '&action=edit_manufacturer');
    }
    $subpartArray['###LABEL_MANUFACTURER_NAME###'] = $this->pi_getLL('admin_name');
    $subpartArray['###VALUE_MANUFACTURER_NAME###'] = htmlspecialchars($manufacturer['manufacturers_name']);
    $subpartArray['###LABEL_MANUFACTURER_IMAGE###'] = $this->pi_getLL('admin_image');
    $subpartArray['###MANUFACTURER_IMAGES_UPLOAD_URL###'] = mslib_fe::typolink($this->shop_pid . ',2002', '&tx_multishop_pi1[page_section]=admin_upload_product_images');
    $subpartArray['###MANUFACTURER_IMAGES_LABEL_CHOOSE_IMAGE###'] = addslashes(htmlspecialchars($this->pi_getLL('choose_image')));
    $subpartArray['###LABEL_MANUFACTURER_VISIBLE###'] = $this->pi_getLL('admin_visible');
    $subpartArray['###LABEL_MANUFACTURER_ADMIN_YES###'] = $this->pi_getLL('admin_yes');
    $subpartArray['###LABEL_MANUFACTURER_ADMIN_NO###'] = $this->pi_getLL('admin_no');
    $subpartArray['###LABEL_BUTTON_ADMIN_CANCEL###'] = $this->pi_getLL('admin_cancel');
    $subpartArray['###LABEL_BUTTON_ADMIN_SAVE###'] = ($this->get['action'] == 'edit_manufacturer') ? $this->pi_getLL('admin_update') : $this->pi_getLL('admin_save');
    $subpartArray['###LABEL_BUTTON_ADMIN_SAVE_CLOSE###'] = ($this->get['action'] == 'edit_manufacturer') ? $this->pi_getLL('admin_update_close') : $this->pi_getLL('admin_save_close');
    $subpartArray['###LINK_BUTTON_CANCEL###'] = $subpartArray['###VALUE_REFERRER###'];
    $subpartArray['###VALUE_FORM_MANUFACTURER_ACTION_URL###'] = $_REQUEST['action'];
    $subpartArray['###DELETE_IMAGES_MANUFACTURERS_ID###'] = $_REQUEST['manufacturers_id'];
    $subpartArray['###AJAX_URL_DELETE_MANUFACTURERS_IMAGE###'] = mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=delete_manufacturers_images');
    $subpartArray['###MANUFACTURERS_IMAGE_CROP_JS###'] = '';
    if ($this->ms['MODULES']['ADMIN_CROP_MANUFACTURERS_IMAGES']) {
        $subpartArray['###MANUFACTURERS_IMAGE_CROP_JS###'] = '
		var filenameLocationServer = responseJSON[\'fileLocation\'];
		// hide the qq-upload status
		$("#qq-upload-list-ul").hide();
		// display instantly uploaded image
		$(".image_action").empty();
		var new_image=\'<img src="\' + filenameLocationServer + \'" />\';
		new_image+=\'<div class="image_tools">\';
		new_image+=\'<a href="#" id="cropEditor" rel="\' + filenameServer + \'"><span>crop</span></a>\';
		new_image+=\'<a href="#" class="delete_manufacturers_images" rel="\' + filenameServer + \'"><img src="' . $this->FULL_HTTP_URL_MS . 'templates/images/icons/delete2.png" border="0" alt="' . $this->pi_getLL('admin_delete_image') . '"></a>\';
		new_image+=\'</div>\';
		$(".image_action").html(new_image);';
    }
    $subpartArray['###MANUFACTURER_IMAGES###'] = $manufacturersImage;
    $subpartArray['###MANUFACTURERS_CONTENT###'] = $manufacturersContent;
    $subpartArray['###MANUFACTURERS_META###'] = $manufacturersMeta;
    $subpartArray['###VALUE_REFERRER###'] = '';
    $subpartArray['###ADMIN_LABEL_JS_ARE_YOU_SURE###'] = $this->pi_getLL('admin_label_js_are_you_sure');
    $subpartArray['###ADMIN_LABEL_DROP_FILES_HERE_TO_UPLOAD###'] = $this->pi_getLL('admin_label_drop_files_here_to_upload');
    $subpartArray['###ADMIN_LABEL_TABS_DETAILS###'] = $this->pi_getLL('admin_label_tabs_details');
    $subpartArray['###ADMIN_LABEL_TABS_CONTENT###'] = $this->pi_getLL('admin_label_tabs_content');
    $subpartArray['###ADMIN_LABEL_TABS_META###'] = $this->pi_getLL('admin_label_tabs_meta');
    // plugin marker place holder
    $plugins_extra_tab = array();
    $plugins_extra_tab['tabs_header'] = array();
    $plugins_extra_tab['tabs_content'] = array();
    // crop images
    $js_extra = array();
    if ($this->ms['MODULES']['ADMIN_CROP_MANUFACTURERS_IMAGES']) {
        $jcrop_js = '
<script src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('multishop') . 'js/tapmodo-Jcrop-1902fbc/js/jquery.Jcrop.js"></script>
<script src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('multishop') . 'js/tapmodo-Jcrop-1902fbc/js/jquery.color.js"></script>
<link rel="stylesheet" href="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('multishop') . 'js/tapmodo-Jcrop-1902fbc/css/jquery.Jcrop.css" type="text/css" />';
        $jcrop_js .= '
<script type="text/javascript">
var jcrop_api;
var bounds, boundx, boundy, scaled;
function activate_jcrop_js(aspecratio, minsize, setselect, truesize) {
	jcrop_api=$(\'#cropbox\').Jcrop({
		onChange: updateCoords,
		onSelect: updateCoords,
		aspectRatio: aspecratio,
		minSize: minsize,
		setSelect: setselect,
		trueSize: truesize,
		boxWidth: 640,
		boxHeight: 480
	},function(){
		jcrop_api = this;
		bounds = jcrop_api.getBounds();
		boundx = bounds[0];
		boundy = bounds[1];
		scaled = jcrop_api.tellScaled();

		var new_scale_x2=minsize[0]==null?50:minsize[0];
		var new_scale_y2=minsize[1]==null?50:minsize[1];
		if (parseInt(minsize[0])>parseInt(scaled.x2)) {
			new_scale_x2=scaled.x2;
		}
		if (parseInt(minsize[1])>parseInt(scaled.y2)) {
			new_scale_y2=scaled.y2;
		}
		$("#default_minsize_settings").val(new_scale_x2 + "," + new_scale_y2);
		jcrop_api.setOptions({
			minSize: [new_scale_x2, new_scale_y2],
			setSelect: [0, 0, new_scale_x2, new_scale_y2],
		});
	});
}
function updateCoords(c) {
	$(\'#jCropX\').val(c.x);
	$(\'#jCropY\').val(c.y);
	$(\'#jCropW\').val(c.w);
	$(\'#jCropH\').val(c.h);
}
function cropEditorDialog(textTitle, textBody, imageName) {
    var cropWindow=\'<div class="modal" id="cropEditorWindow" tabindex="-1" role="dialog" aria-labelledby="cropEditorWindowTitle">\';
  	cropWindow+=\'<div class="modal-dialog modal-lg" role="document">\';
    cropWindow+=\'<div class="modal-content">\';
    cropWindow+=\'<div class="modal-header">\';
    cropWindow+=\'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>\';
    cropWindow+=\'<h4 class="modal-title" id="cropEditorWindowTitle">\' + textTitle + \'</h4>\';
    cropWindow+=\'</div>\';
    cropWindow+=\'<div class="modal-body">\' + textBody + \'</div>\';
    cropWindow+=\'<div class="modal-footer">\';
    cropWindow+=\'<button type="button" class="btn btn-default" data-dismiss="modal">' . $this->pi_getLL('close') . '</button>\';
    cropWindow+=\'</div>\';
    cropWindow+=\'</div>\';
	cropWindow+=\'</div>\';
	cropWindow+=\'</div>\';
	$(\'body\').append(cropWindow);
	$(\'#cropEditorWindow\').modal({
		show: true,
		backdrop: \'static\',
	});
	$(\'#cropEditorWindow\').on(\'hidden.bs.modal\', function (e) {
		$(\'#cropEditorWindow\').remove();
		href = "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_images_for_crop&tx_multishop_pi1[crop_section]=manufacturers') . '";
		jQuery.ajax({
			type:"POST",
			url:href,
			data: "imagename=" + imageName,
			dataType: "json",
			success: function(r) {
				//do something with the sorted data
				if (r.status=="OK") {
					var new_image=r.images["normal"];
					$(".image_action > img").prop("src", new_image);
				}
			}
		});
	});
}
jQuery(document).ready(function ($) {
	$(document).on(\'click\', "#cropEditor", function(e) {
		e.preventDefault();
		var image_name=$(this).attr("rel");
		href = "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=get_images_for_crop&tx_multishop_pi1[crop_section]=manufacturers') . '";
		jQuery.ajax({
			type:"POST",
			url:href,
			data: "imagename=" + image_name,
			dataType: "json",
			success: function(r) {
				//do something with the sorted data
				if (r.status=="OK") {
					var image_interface=\'<div id="crop_editor_wrapper">\';
					image_interface+=\'<div id="crop_main_window_editor" align="center"><img src="\' + r.images["enlarged"] + \'" id="cropbox" /></div>\';
					image_interface+=\'<div id="crop_thumb_image_button">\';
					image_interface+=\'<div id="crop_save_btn_wrapper""><span class="msBackendButton continueState"><input type="button" id="crop_save" value="crop & save" /></span></div>\';
					image_interface+=\'<div id="crop_restore_btn_wrapper" style="display:none"><span class="msBackendButton continueState"><input type="button" id="crop_restore" value="restore image" /></span></div>\';
					image_interface+=\'<div id="minsize_settings_btn_wrapper" style="display:none"><label for="remove_minsize"><input type="checkbox" id="remove_minsize" checked="checked" /> Lock minimal size of crop selection</label></div>\';
					image_interface+=\'<div id="aspectratio_settings_btn_wrapper" style="display:none"><label for="remove_aspectratio"><input type="checkbox" id="remove_aspectratio" checked="checked" /> Lock aspect ratio of crop selection</label></div>\';
					image_interface+=\'<input type="hidden" id="jCropImageName" name="tx_multishop_pi1[jCropImageName]" class="jcrop_coords" value="\' + image_name + \'" />\';
					image_interface+=\'<input type="hidden" id="jCropImageSize" name="tx_multishop_pi1[jCropImageSize]" class="jcrop_coords" value="enlarged" />\';
					image_interface+=\'<input type="hidden" id="jCropX" name="tx_multishop_pi1[jCropX]" class="jcrop_coords" value="" />\';
					image_interface+=\'<input type="hidden" id="jCropY" name="tx_multishop_pi1[jCropY]" class="jcrop_coords" value="" />\';
					image_interface+=\'<input type="hidden" id="jCropW" name="tx_multishop_pi1[jCropW]" class="jcrop_coords" value="" />\';
					image_interface+=\'<input type="hidden" id="jCropH" name="tx_multishop_pi1[jCropH]" class="jcrop_coords" value="" />\';
					image_interface+=\'<input type="hidden" id="default_minsize_settings" name="default_minsize_settings" class="jcrop_coords" value="\' + r.minsize["enlarged"] + \'" />\';
					image_interface+=\'<input type="hidden" id="default_aspectratio_settings" name="default_aspectratio_settings" class="jcrop_coords" value="\' + r.aspectratio["enlarged"] + \'" />\';
					image_interface+=\'</div>\';
					image_interface+=\'</div>\';
					cropEditorDialog("Crop image " + image_name + " [enlarged]", image_interface, image_name);
					// default for first time loading is enlarged
					if (r.disable_crop_button=="disabled") {
						$("#crop_save_btn_wrapper").hide();
						$("#crop_restore_btn_wrapper").show();
						$("#minsize_settings_btn_wrapper").hide();
						$("#aspectratio_settings_btn_wrapper").hide();
					} else {
						$("#crop_save_btn_wrapper").show();
						$("#crop_restore_btn_wrapper").hide();
						$("#minsize_settings_btn_wrapper").show();
						$("#remove_minsize").prop("checked", true);
						$("#aspectratio_settings_btn_wrapper").show();
						$("#remove_aspectratio").prop("checked", true);
						activate_jcrop_js(r.aspectratio["enlarged"], r.minsize["enlarged"], r.setselect["enlarged"], r.truesize["enlarged"]);
					}
				}
			}
		});
	});
	$(document).on(\'click\', "#crop_save", function(e) {
		e.preventDefault();
		href = "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=crop_product_image&tx_multishop_pi1[crop_section]=manufacturers') . '";
		jQuery.ajax({
			type:"POST",
			url:href,
			data: $(".jcrop_coords").serialize() + "&mid=' . (isset($this->get['manufacturers_id']) && $this->get['manufacturers_id'] > 0 ? $this->get['manufacturers_id'] : '') . '",
			dataType: "json",
			success: function(r) {
				//do something with the sorted data
				if (r.status=="OK") {
					var new_image=\'<img src="\' + r.images[$("#jCropImageSize").val()] + \'" id="cropbox"/>\';
					$("#jCropX").val("");
					$("#jCropY").val("");
					$("#jCropW").val("");
					$("#jCropH").val("");
					$("#crop_main_window_editor").empty();
					$("#crop_main_window_editor").html(new_image);
					if (r.disable_crop_button=="disabled") {
						$("#crop_save_btn_wrapper").hide();
						$("#crop_restore_btn_wrapper").show();
						$("#minsize_settings_btn_wrapper").hide();
						$("#aspectratio_settings_btn_wrapper").hide();
					} else {
						$("#crop_save_btn_wrapper").show();
						$("#crop_restore_btn_wrapper").hide();
						$("#minsize_settings_btn_wrapper").show();
						$("#remove_minsize").prop("checked", true);
						$("#aspectratio_settings_btn_wrapper").show();
						$("#remove_aspectratio").prop("checked", true);
						activate_jcrop_js(r.aspectratio[$("#jCropImageSize").val()], r.minsize[$("#jCropImageSize").val()], r.setselect[$("#jCropImageSize").val()], r.truesize[$("#jCropImageSize").val()]);
					}
				}
			}
		});
	});
	$(document).on(\'click\',"#crop_restore",function(e) {
		e.preventDefault();
		href = "' . mslib_fe::typolink($this->shop_pid . ',2002', 'tx_multishop_pi1[page_section]=restore_crop_image&tx_multishop_pi1[crop_section]=manufacturers') . '";
		jQuery.ajax({
			type:"POST",
			url:href,
			data: $(".jcrop_coords").serialize() + "mid=' . (isset($this->get['manufacturers_id']) && $this->get['manufacturers_id'] > 0 ? $this->get['manufacturers_id'] : '') . '",
			dataType: "json",
			success: function(r) {
				//do something with the sorted data
				if (r.status=="OK") {
					var new_image=\'<img src="\' + r.images[$("#jCropImageSize").val()] + \'" id="cropbox"/>\';
					$("#jCropX").val("");
					$("#jCropY").val("");
					$("#jCropW").val("");
					$("#jCropH").val("");
					$("#crop_main_window_editor").empty();
					$("#crop_main_window_editor").html(new_image);
					if (r.disable_crop_button=="disabled") {
						$("#crop_save_btn_wrapper").hide();
						$("#crop_restore_btn_wrapper").show();
						$("#minsize_settings_btn_wrapper").hide();
						$("#aspectratio_settings_btn_wrapper").hide();
					} else {
						$("#crop_save_btn_wrapper").show();
						$("#crop_restore_btn_wrapper").hide();
						$("#minsize_settings_btn_wrapper").show();
						$("#remove_minsize").prop("checked", true);
						$("#aspectratio_settings_btn_wrapper").show();
						$("#remove_aspectratio").prop("checked", true);
						activate_jcrop_js(r.aspectratio[$("#jCropImageSize").val()], r.minsize[$("#jCropImageSize").val()], r.setselect[$("#jCropImageSize").val()], r.truesize[$("#jCropImageSize").val()]);
					}
				}
			}
		});
	});
	$(document).on("change", "#remove_minsize", function(){
		jcrop_api.setOptions(this.checked? {
			minSize: $("#default_minsize_settings").val().split(",")
		}: {
			minSize: [0,0]
		});
		jcrop_api.focus();
	});
	$(document).on("change", "#remove_aspectratio", function(){
		jcrop_api.setOptions(this.checked? {
			aspectRatio: $("#default_aspectratio_settings").val()
		}: {
			aspectRatio: 0
		});
		jcrop_api.focus();
	});
});
</script>
';
        $js_extra[] = $jcrop_js;
    }
    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturers.php']['adminEditManufacturersPreProc'])) {
        $params = array(
                'js_extra' => &$js_extra,
                'subpartArray' => &$subpartArray,
                'manufacturer' => &$manufacturer,
                'plugins_extra_tab' => &$plugins_extra_tab
        );
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/scripts/admin_pages/includes/admin_edit_manufacturers.php']['adminEditManufacturersPreProc'] as $funcRef) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
        }
    }
    // add custom content to edit manufacturer
    if (!count($plugins_extra_tab['tabs_header']) && !count($plugins_extra_tab['tabs_content'])) {
        $subpartArray['###LABEL_EXTRA_PLUGIN_TABS###'] = '';
        $subpartArray['###CONTENT_EXTRA_PLUGIN_TABS###'] = '';
    } else {
        $subpartArray['###LABEL_EXTRA_PLUGIN_TABS###'] = implode("\n", $plugins_extra_tab['tabs_header']);
        $subpartArray['###CONTENT_EXTRA_PLUGIN_TABS###'] = implode("\n", $plugins_extra_tab['tabs_content']);
    }
    if (count($js_extra)) {
        $GLOBALS['TSFE']->additionalHeaderData['admin_edit_manufacturers_js'] = implode("\n", $js_extra);
    }
    // Instantiate admin interface object
    $objRef = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:multishop/pi1/classes/class.tx_mslib_admin_interface.php:&tx_mslib_admin_interface');
    $objRef->init($this);
    $objRef->setInterfaceKey('admin_edit_manufacturer');
    // Header buttons
    $headerButtons = array();
    $headingButton = array();
    $headingButton['btn_class'] = 'btn btn-success';
    $headingButton['fa_class'] = 'fa fa-check-circle';
    $headingButton['title'] = ($this->get['action'] == 'edit_manufacturer') ? $this->pi_getLL('update') : $this->pi_getLL('save');
    $headingButton['href'] = '#';
    $headingButton['attributes'] = 'onclick="$(\'#btnSave\').click(); return false;"';
    $headerButtons[] = $headingButton;
    $headingButton = array();
    $headingButton['btn_class'] = 'btn btn-success';
    $headingButton['fa_class'] = 'fa fa-check-circle';
    $headingButton['title'] = ($this->get['action'] == 'edit_manufacturer') ? $this->pi_getLL('admin_update_close') : $this->pi_getLL('admin_save_close');
    $headingButton['href'] = '#';
    $headingButton['attributes'] = 'onclick="$(\'#btnSaveClose\').click(); return false;"';
    $headerButtons[] = $headingButton;
    // Set header buttons through interface class so other plugins can adjust it
    $objRef->setHeaderButtons($headerButtons);
    // Get header buttons through interface class so we can render them
    $interfaceHeaderButtons = $objRef->renderHeaderButtons();
    // Get header buttons through interface class so we can render them
    $subpartArray['###INTERFACE_HEADER_BUTTONS###'] = $objRef->renderHeaderButtons();
    $content .= $this->cObj->substituteMarkerArrayCached($subparts['template'], array(), $subpartArray);
}
?>
