<?php
if (is_array($categories) && count($categories)) {
    // load optional cms content and show the current category name
    if ($current['content']) {
        $content .= mslib_fe::htmlBox($current['categories_name'], $current['content'], 1);
    } elseif ($current['categories_name']) {
        $content .= '<div class="main-heading"><h1>' . $current['categories_name'] . '</h1></div>';
    }
    // load optional cms content and show the current category name eof
    $content .= '<ul id="category_listing">';
    $counter = 0;
    foreach ($categories as $category) {
        $counter++;
        if ($category['categories_image']) {
            $image = '<img src="' . mslib_befe::getImagePath($category['categories_image'], 'categories', 'normal') . '" alt="' . htmlspecialchars($category['categories_name']) . '">';
        } else {
            $image = '<div class="no_image"></div>';
        }
        // get all cats to generate multilevel fake url
        $level = 0;
        $cats = Crumbar($category['categories_id']);
        $cats = array_reverse($cats);
        $where = '';
        if (count($cats) > 0) {
            foreach ($cats as $item) {
                $where .= "categories_id[" . $level . "]=" . $item['id'] . "&";
                $level++;
            }
            $where = substr($where, 0, (strlen($where) - 1));
            //			$where.='&';
        }
        //		$where.='categories_id['.$level.']='.$category['categories_id'];
        // get all cats to generate multilevel fake url eof
        if ($category['categories_external_url']) {
            $link = $category['categories_external_url'];
        } else {
            $link = mslib_fe::typolink($this->conf['products_listing_page_pid'], '&' . $where . '&tx_multishop_pi1[page_section]=products_listing');
        }
        $content .= '<li class="item_' . $counter . '"';
        if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
            $content .= ' id="sortable_subcat_' . $category['categories_id'] . '" ';
        }
        $content .= '>
			<div class="image"><a href="' . $link . '" title="' . htmlspecialchars($category['categories_name']) . '" class="ajax_link">' . $image . '</a></div>
			<h2><a href="' . $link . '" class="ajax_link">' . $category['categories_name'] . '</a></h2>
			<div class="description">' . $category['categories_name'] . '</div>
			<div class="link_detail"><a href="' . $link . '" class="ajax_link">' . $this->pi_getLL('details') . '</a></div>
			';
        if ($this->ROOTADMIN_USER or ($this->ADMIN_USER and $this->CATALOGADMIN_USER)) {
            $content .= '<div class="admin_menu"><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=edit_category&cid=' . $category['categories_id'] . '&action=edit_category', 1) . '" class="admin_menu_edit"><i class="fa fa-pencil"></i></a><a href="' . mslib_fe::typolink($this->shop_pid . ',2003', 'tx_multishop_pi1[page_section]=delete_category&cid=' . $category['categories_id'] . '&action=delete_category', 1) . '" class="admin_menu_remove" title="Remove"><i class="fa fa-trash-o"></i></a></div>';
        }
        $content .= '
			</li>';
    }
    $content .= '</ul>';
    if ($current['content_footer']) {
        $content .= '<div class="msCategoriesFooterDescription">' . mslib_fe::htmlBox('', $current['content_footer'], 2) . '</div>';
    }
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
