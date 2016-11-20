<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$str = "select id from tx_multishop_sessions limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_sessions` (
		  `id` int(11) auto_increment,
		  `customer_id` int(11) default '0',
		  `crdate` int(11) default '0',
		  `session_id` varchar(150) default '',
		  `page_uid` int(11) default '0',
		  `ip_address` varchar(150) default '',
		  `http_host` varchar(150) default '',
		  `query_string` text,
		  `http_user_agent` text,
		  `http_referer` text,
		  `url` text,
		  `segment_type` varchar(50) default '',
		  `segment_id` varchar(50) default '',
		  PRIMARY KEY (`id`),
		  KEY `customer_id` (`customer_id`),
		  KEY `crdate` (`crdate`),
		  KEY `page_uid` (`page_uid`),
		  KEY `session_id` (`session_id`),
		  KEY `ip_address` (`ip_address`),
		  KEY `http_host` (`http_host`),
		  KEY `segment_type` (`segment_type`),
		  KEY `segment_id` (`segment_id`)
		);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select icecat_mid from tx_multishop_manufacturers limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_manufacturers` DROP `icecat_mid`";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select coupon_code from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD `coupon_code` varchar(255) default '', ADD KEY `coupon_code` (`coupon_code`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    $str = "ALTER TABLE `tx_multishop_orders` ADD `coupon_discount_type` varchar(25) default 'percentage', ADD KEY `coupon_discount_type` (`coupon_discount_type`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    $str = "ALTER TABLE `tx_multishop_orders` ADD `coupon_discount_value` decimal(24,14) default '0.00000000000000', ADD KEY `coupon_discount_value` (`coupon_discount_value`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "SHOW COLUMNS FROM `tx_multishop_customers_groups_method_mappings` WHERE Field='id' and Extra like 'AUTO%'";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
    $str = "ALTER TABLE  `tx_multishop_customers_groups_method_mappings` CHANGE  `id`  `id` INT( 11 ) NOT NULL AUTO_INCREMENT";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// attributes values image
$str = "select products_options_values_image from tx_multishop_products_options_values_to_products_options limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_options_values_to_products_options` ADD `products_options_values_image` varchar(255) default '', ADD KEY `products_options_values_image` (`products_options_values_image`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select id from tx_multishop_invoices_export limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_invoices_export` (
			  `id` int(11) NULL AUTO_INCREMENT,
			  `name` varchar(75) NULL,
			  `page_uid` int(11) NULL DEFAULT '0',
			  `crdate` int(11) NULL DEFAULT '0',
			  `fields` text NULL,
			  `post_data` text NULL,
			  `code` varchar(150) NULL,
			  `status` tinyint(1) NULL DEFAULT '0',
			  PRIMARY KEY (`id`),
			  KEY `code` (`code`)
			);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// V3 COMPARE DATABASE EOL
// V4 BETA COMPARE DATABASE (MULTIPLE SHOPS DATABASE DESIGN)
$str = "select page_uid from tx_multishop_products_to_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_to_categories` ADD `page_uid` int(11) UNSIGNED default '0', ADD KEY `page_uid` (`page_uid`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    $str = "UPDATE tx_multishop_products_to_categories t1, tx_multishop_categories t2 SET t1.page_uid = t2.page_uid WHERE t1.categories_id=t2.categories_id";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select id from tx_multishop_categories_to_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_categories_to_categories` (
		  `id` int(11) auto_increment,
		  `categories_id` int(11) default '0',
		  `foreign_categories_id` int(11) default '0',
		  `page_uid` int(11) default '0',
		  `foreign_page_uid` int(11) default '0',
		  PRIMARY KEY (`id`),
		  KEY `categories_id` (`categories_id`),
		  KEY `foreign_categories_id` (`foreign_categories_id`),
		  KEY `page_uid` (`page_uid`),
		  KEY `foreign_page_uid` (`foreign_page_uid`)
		);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select layered_categories_id from tx_multishop_products_description limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_description` ADD `layered_categories_id` INT(11) NOT NULL DEFAULT '0', ADD INDEX (`layered_categories_id`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select page_uid from tx_multishop_products_description limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_description` ADD `page_uid` INT(11) NOT NULL DEFAULT '0', ADD INDEX (`page_uid`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select post_data from tx_multishop_orders_export limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE  `tx_multishop_orders_export` ADD `post_data` text default ''";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "show indexes from `tx_multishop_products_description` where Key_name='PRIMARY'";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry) == 2) {
    $str = "ALTER TABLE tx_multishop_products_description DROP PRIMARY KEY";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $str = "ALTER TABLE `tx_multishop_products_description` ADD PRIMARY KEY (`products_id`,`language_id`,`page_uid`,`layered_categories_id`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select payment_condition from tx_multishop_orders";
if (!$qry = $GLOBALS['TYPO3_DB']->sql_query($str)) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD `payment_condition` varchar(50) NULL DEFAULT '', ADD INDEX (`payment_condition`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select tx_multishop_payment_condition from fe_users";
if (!$qry = $GLOBALS['TYPO3_DB']->sql_query($str)) {
    $str = "ALTER TABLE `fe_users` ADD `tx_multishop_payment_condition` varchar(50) NULL DEFAULT '', ADD INDEX (`tx_multishop_payment_condition`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select tx_multishop_language from fe_users";
if (!$qry = $GLOBALS['TYPO3_DB']->sql_query($str)) {
    $str = "ALTER TABLE `fe_users` ADD `tx_multishop_language` varchar(2) NULL DEFAULT '', ADD INDEX (`tx_multishop_language`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select related_to from tx_multishop_products_to_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE  `tx_multishop_products_to_categories` ADD `related_to` INT(11) NOT NULL DEFAULT '0', ADD INDEX (`related_to`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select page_uid from tx_multishop_products_to_categories where page_uid='0' limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if ($qry && $this->showCatalogFromPage && $GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
    $str = "UPDATE tx_multishop_products_to_categories t1, tx_multishop_categories t2 SET t1.page_uid = t2.page_uid WHERE t1.page_uid=0 AND t1.categories_id=t2.categories_id";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    /*
    $str="UPDATE `tx_multishop_products_to_categories` SET page_uid='".$this->showCatalogFromPage."' where page_uid='0'";
    $qry=$GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[]=$str;
    */
}
$str = "select page_uid from tx_multishop_products_description where page_uid='0' limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if ($qry && $this->showCatalogFromPage && $GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
    $str = "UPDATE tx_multishop_products_description t1, tx_multishop_products t2 SET t1.page_uid = t2.page_uid WHERE t1.page_uid=0 AND t1.products_id=t2.products_id";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    /*
    $str="UPDATE `tx_multishop_products_description` SET page_uid='".$this->showCatalogFromPage."' where page_uid='0'";
    $qry=$GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[]=$str;
    */
}
$str = "select id from tx_multishop_product_crop_image_coordinate limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_product_crop_image_coordinate` (
		  `id` int(11) auto_increment,
		  `products_id` int(11) default '0',
		  `image_filename` varchar(255) default '',
		  `image_size` varchar(10) DEFAULT '',
		  `coordinate_x` int(11) default '0',
		  `coordinate_y` int(11) default '0',
		  `coordinate_w` int(11) default '0',
		  `coordinate_h` int(11) default '0',
		  PRIMARY KEY (`id`),
		  KEY `products_id` (`products_id`),
		  KEY `image_filename` (`image_filename`),
		  KEY `image_size` (`image_size`),
		  KEY `coordinate_x` (`coordinate_x`),
		  KEY `coordinate_y` (`coordinate_y`),
		  KEY `coordinate_w` (`coordinate_w`),
		  KEY `coordinate_h` (`coordinate_h`)
		);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select id from tx_multishop_categories_crop_image_coordinate limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_categories_crop_image_coordinate` (
		  `id` int(11) auto_increment,
		  `categories_id` int(11) default '0',
		  `image_filename` varchar(255) default '',
		  `image_size` varchar(10) DEFAULT '',
		  `coordinate_x` int(11) default '0',
		  `coordinate_y` int(11) default '0',
		  `coordinate_w` int(11) default '0',
		  `coordinate_h` int(11) default '0',
		  PRIMARY KEY (`id`),
		  KEY `categories_id` (`categories_id`),
		  KEY `image_filename` (`image_filename`),
		  KEY `image_size` (`image_size`),
		  KEY `coordinate_x` (`coordinate_x`),
		  KEY `coordinate_y` (`coordinate_y`),
		  KEY `coordinate_w` (`coordinate_w`),
		  KEY `coordinate_h` (`coordinate_h`)
		);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select id from tx_multishop_manufacturers_crop_image_coordinate limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_manufacturers_crop_image_coordinate` (
		  `id` int(11) auto_increment,
		  `manufacturers_id` int(11) default '0',
		  `image_filename` varchar(255) default '',
		  `image_size` varchar(10) DEFAULT '',
		  `coordinate_x` int(11) default '0',
		  `coordinate_y` int(11) default '0',
		  `coordinate_w` int(11) default '0',
		  `coordinate_h` int(11) default '0',
		  PRIMARY KEY (`id`),
		  KEY `manufacturers_id` (`manufacturers_id`),
		  KEY `image_filename` (`image_filename`),
		  KEY `image_size` (`image_size`),
		  KEY `coordinate_x` (`coordinate_x`),
		  KEY `coordinate_y` (`coordinate_y`),
		  KEY `coordinate_w` (`coordinate_w`),
		  KEY `coordinate_h` (`coordinate_h`)
		);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// cms table
$str = "describe `tx_multishop_cms`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'id') {
        if ($row['Type'] == 'int(3)') {
            $str2 = "ALTER TABLE  `tx_multishop_cms` CHANGE  `id`  `id` int(11)";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
// cms table
$str = "describe `tx_multishop_cms_description`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'id') {
        if ($row['Type'] == 'int(3)') {
            $str2 = "ALTER TABLE  `tx_multishop_cms_description` CHANGE  `id`  `id` int(11)";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
        if ($row['Key'] != 'PRI') {
            $str2 = "ALTER TABLE  `tx_multishop_cms_description` ADD PRIMARY KEY  (`id`, `language_id`)";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
// TYPO3 6 - NULL VALUES BUGFIX
$items = array();
$items[] = array(
        'table' => 'tx_multishop_products_description',
        'column' => 'products_meta_title',
        'columnDefinition' => 'varchar(254)',
        'allowNull' => 1
);
$items[] = array(
        'table' => 'tx_multishop_products_description',
        'column' => 'products_meta_keywords',
        'columnDefinition' => 'varchar(254)',
        'allowNull' => 1
);
$items[] = array(
        'table' => 'tx_multishop_products_description',
        'column' => 'products_meta_description',
        'columnDefinition' => 'varchar(254)',
        'allowNull' => 1
);
foreach ($items as $item) {
    $str = "describe `" . $item['table'] . "`";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        if ($row['Field'] == $item['column']) {
            if ($row['Null'] == 'NO' && $item['allowNull']) {
                $str2 = "ALTER TABLE  `" . $item['table'] . "` CHANGE `" . $item['column'] . "` `" . $item['column'] . "` " . $item['columnDefinition'] . " default NULL";
                $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                $messages[] = $str2;
            }
        }
    }
}
// TYPO3 6 - NULL VALUES BUGFIX EOL
$str = "select relation_types from tx_multishop_products_to_relative_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_to_relative_products` ADD `relation_types` varchar(15) NOT NULL DEFAULT 'cross-sell', ADD INDEX (`relation_types`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// add sort_order columns to orders_products
$str = "select sort_order from tx_multishop_orders_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders_products` ADD `sort_order` int(11) NOT NULL DEFAULT '0', ADD INDEX (`sort_order`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    // initiate the first value based on orders_products_id
    $str = "update tx_multishop_orders_products set sort_order=orders_products_id";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// add sort_order columns to shipping methods to zones
$str = "select sort_order from tx_multishop_shipping_methods_to_zones limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_shipping_methods_to_zones` ADD `sort_order` int(11) NOT NULL DEFAULT '0', ADD INDEX (`sort_order`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// add sort_order columns to payment methods to zones
$str = "select sort_order from tx_multishop_payment_methods_to_zones limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_payment_methods_to_zones` ADD `sort_order` int(11) NOT NULL DEFAULT '0', ADD INDEX (`sort_order`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
// add manufacturers_advice_price columns to products tabel
$str = "select manufacturers_advice_price from tx_multishop_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products` ADD `manufacturers_advice_price` decimal(24,14) NOT NULL DEFAULT '0.00000000000000', ADD INDEX (`manufacturers_advice_price`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select predefined_variables from tx_multishop_import_jobs limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_import_jobs` ADD `predefined_variables` text null default ''";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "describe `tx_multishop_import_jobs`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'type') {
        if ($row['Type'] == 'varchar(32)') {
            $str2 = "ALTER TABLE  `tx_multishop_import_jobs` CHANGE  `type`  `type` varchar(254)";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$str = "describe `tx_multishop_products`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'products_multiplication') {
        if ($row['Type'] == 'int(11)') {
            $str2 = "ALTER TABLE  `tx_multishop_products` CHANGE  `products_multiplication`  `products_multiplication` decimal(6,2) null default '0.00'";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$str = "select include_disabled from tx_multishop_product_feeds limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE  `tx_multishop_product_feeds` ADD include_disabled tinyint(1) NULL DEFAULT '0'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select date_mail_last_sent from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD date_mail_last_sent int(11) null default '0',ADD KEY `date_mail_last_sent` (`date_mail_last_sent`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select date_mail_last_sent from tx_multishop_invoices limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_invoices` ADD date_mail_last_sent int(11) null default '0',ADD KEY `date_mail_last_sent` (`date_mail_last_sent`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select default_status from tx_multishop_tax_rule_groups limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_tax_rule_groups` ADD default_status tinyint(1) null default '0',ADD KEY `default_status` (`default_status`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select payment_condition from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD payment_condition tinyint(3) null default '0',ADD KEY `payment_condition` (`payment_condition`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select billing_coc_id from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD billing_coc_id varchar(150) default null,ADD KEY `billing_coc_id` (`billing_coc_id`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select id from tx_multishop_customers_export limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "CREATE TABLE `tx_multishop_customers_export` (
			  `id` int(11) NULL AUTO_INCREMENT,
			  `name` varchar(75) NULL,
			  `page_uid` int(11) NULL DEFAULT '0',
			  `crdate` int(11) NULL DEFAULT '0',
			  `fields` text NULL,
			  `post_data` text NULL,
			  `code` varchar(150) NULL,
			  `status` tinyint(1) NULL DEFAULT '0',
			  PRIMARY KEY (`id`),
			  KEY `code` (`code`)
			);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "describe `tx_multishop_specials`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'specials_new_products_price') {
        if ($row['Type'] != 'decimal(24,14)') {
            $str2 = "ALTER TABLE  `tx_multishop_specials` CHANGE  `specials_new_products_price`  `specials_new_products_price` decimal(24,14) DEFAULT  '0.00000000000000'";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$required_indexes = array();
$required_indexes[] = 'crdate';
$indexes = array();
$table_name = 'tx_multishop_notification';
$str = "show indexes from `" . $table_name . "` ";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    $indexes[] = $rs['Key_name'];
}
foreach ($required_indexes as $required_index) {
    if (!in_array($required_index, $indexes)) {
        $str = "ALTER TABLE  `" . $table_name . "` ADD KEY `" . $required_index . "` (`" . $required_index . "`)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$required_indexes = array();
$required_indexes[] = 'endtime';
$indexes = array();
$table_name = 'tx_multishop_products';
$str = "show indexes from `" . $table_name . "` ";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    $indexes[] = $rs['Key_name'];
}
foreach ($required_indexes as $required_index) {
    if (!in_array($required_index, $indexes)) {
        $str = "ALTER TABLE `" . $table_name . "` ADD KEY `" . $required_index . "` (`" . $required_index . "`)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$str = "describe `tx_multishop_products`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'minimum_quantity') {
        if ($row['Type'] == 'int(11)') {
            $str2 = "ALTER TABLE  `tx_multishop_products` CHANGE  `minimum_quantity`  `minimum_quantity` decimal(6,2) null default '1.00'";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$str = "describe `tx_multishop_products`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'maximum_quantity') {
        if ($row['Type'] == 'int(11)') {
            $str2 = "ALTER TABLE  `tx_multishop_products` CHANGE  `maximum_quantity`  `maximum_quantity` decimal(6,2) null default null";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$required_indexes = array();
$required_indexes[] = 'products_id';
$required_indexes[] = 'sort_order';
$indexes = array();
$table_name = 'tx_multishop_products_to_categories';
$str = "show indexes from `" . $table_name . "` ";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    $indexes[] = $rs['Key_name'];
}
foreach ($required_indexes as $required_index) {
    if (!in_array($required_index, $indexes)) {
        $str = "ALTER TABLE  `" . $table_name . "` ADD KEY `" . $required_index . "` (`" . $required_index . "`)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$str = "select crumbar_identifier from tx_multishop_products_to_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_to_categories` ADD crumbar_identifier varchar(250) default '',ADD KEY `crumbar_identifier` (`crumbar_identifier`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select products_to_categories_id from tx_multishop_products_to_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    // drop the current primary
    $str = "ALTER TABLE tx_multishop_products_to_categories DROP PRIMARY KEY";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    // add new primary key col
    $str = "ALTER TABLE  `tx_multishop_products_to_categories` ADD  `products_to_categories_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    // add the node_id col
    $str = "select node_id from tx_multishop_products_to_categories limit 1";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    if (!$qry) {
        $str = "ALTER TABLE `tx_multishop_products_to_categories` ADD node_id int(11) default '0',ADD KEY `node_id` (`node_id`)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
        // is_deepest col
        $str = "select is_deepest from tx_multishop_products_to_categories limit 1";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        if (!$qry) {
            $str = "ALTER TABLE `tx_multishop_products_to_categories` ADD is_deepest tinyint(1) default '0',ADD KEY `is_deepest` (`is_deepest`)";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages[] = $str;
            // marker for old p2c relation for removing at the end
            $str = "select current_relation from tx_multishop_products_to_categories limit 1";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            if (!$qry) {
                $str = "ALTER TABLE `tx_multishop_products_to_categories` ADD current_relation tinyint(1) default '0',ADD KEY `current_relation` (`current_relation`)";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                $messages[] = $str;
                // set the current relation value to
                $str = "UPDATE `tx_multishop_products_to_categories` SET `current_relation`=1";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                // rebuild the p2c link
                tx_mslib_catalog::compareDatabaseAlterProductToCategoryLinking();
                // remove the entry
                $str = "DELETE FROM `tx_multishop_products_to_categories` WHERE `current_relation`=1";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                // remove the col
                $str = "ALTER TABLE `tx_multishop_products_to_categories` DROP `current_relation`";
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            }
        }
    }
} else {
    // method to fix the broken linking of product to categories
    $p2c_fix_msg = tx_mslib_catalog::compareDatabaseFixProductToCategoryLinking();
    if (!empty($p2c_fix_msg)) {
        $messages[] = $p2c_fix_msg;
    }
}
$str = "describe tx_multishop_cms";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'id') {
        if ($row['Extra'] != 'auto_increment') {
            $str = "DELETE FROM `tx_multishop_cms` WHERE `id`=0";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages[] = $str;
            $str = "ALTER TABLE  `tx_multishop_cms` CHANGE  `id`  `id` INT( 11 ) null auto_increment;";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages[] = $str;
        }
    }
}
$str = "describe tx_multishop_manufacturers";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'manufacturers_name') {
        if ($row['Type'] != 'varchar(64)') {
            $str = "ALTER TABLE  `tx_multishop_manufacturers` CHANGE  `manufacturers_name`  `manufacturers_name` VARCHAR(64);";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages[] = $str;
        }
    }
}
$str = "select page_uid from tx_multishop_cart_contents limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_cart_contents` ADD page_uid int(11) default '0',ADD KEY `page_uid` (`page_uid`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    // update page_uid based on customer_id
    $str = 'select id, customer_id from tx_multishop_cart_contents';
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $customer_page_uid = array();
    while ($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) {
        if ($rs['customer_id']) {
            $str2 = 'select page_uid from fe_users where uid=\'' . $rs['customer_id'] . '\'';
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $rs2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry2);
            $customer_page_uid[$rs['customer_id']] = $rs2['page_uid'];
            if ($rs2['page_uid'] > 0) {
                $str_update = 'update tx_multishop_cart_contents set page_uid=\'' . $rs2['page_uid'] . '\' where id=\'' . $rs['id'] . '\'';
                $GLOBALS['TYPO3_DB']->sql_query($str_update);
                $messages[] = $str_update;
            }
        }
    }
}
$str = "select search_engines_allow_indexing from tx_multishop_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products` ADD search_engines_allow_indexing tinyint(1) NOT NULL default '1', ADD KEY `search_engines_allow_indexing` (`search_engines_allow_indexing`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select override_shippingcosts from tx_multishop_shipping_methods_costs limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_shipping_methods_costs` ADD override_shippingcosts varchar(127) NOT NULL default '', ADD KEY `override_shippingcosts` (`override_shippingcosts`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select customer_comments from tx_multishop_orders_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders_products` ADD customer_comments text NOT NULL default ''";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select orders_last_modified from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD orders_last_modified int(11) NOT NULL default '0'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select track_and_trace_link from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD track_and_trace_link varchar(255) NOT NULL default ''";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "describe `tx_multishop_products_options_values_to_products_options_desc`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'description') {
        if ($row['Type'] == 'varchar(255)') {
            $str2 = "ALTER TABLE  `tx_multishop_products_options_values_to_products_options_desc` CHANGE  `description`  `description` text default ''";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$str = "select orders_paid_timestamp from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD orders_paid_timestamp int(11) NOT NULL default '0'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select credit_order from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if ($qry) {
    $str = "ALTER TABLE `tx_multishop_orders` CHANGE `credit_order` `debit_order` TINYINT(1) NULL DEFAULT '0';";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
} else {
    $str = "select debit_order from tx_multishop_orders limit 1";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    if (!$qry) {
        $str = "ALTER TABLE `tx_multishop_orders` ADD debit_order tinyint(1) default '0', ADD KEY `debit_order` (`debit_order`)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$str = "select credit_invoice from tx_multishop_invoices limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if ($qry) {
    $str = "ALTER TABLE `tx_multishop_invoices` CHANGE `credit_invoice` `debit_invoice` TINYINT(1) NULL DEFAULT '0';";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
} else {
    $str = "select debit_invoice from tx_multishop_invoices limit 1";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    if (!$qry) {
        $str = "ALTER TABLE `tx_multishop_invoices` ADD debit_invoice tinyint(1) default '0', ADD KEY `debit_invoice` (`debit_invoice`)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$str = "select page_uid from tx_multishop_products_attributes limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_attributes` ADD page_uid int(11) NOT NULL default '0'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    //
    $str = "update tx_multishop_products_attributes set page_uid=" . $this->showCatalogFromPage;
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$oldKeys = array();
$oldKeys[] = 'ADMIN_ORDER_PROPOSAL_MODULE';
$oldKeys[] = 'FIRSTNAME_AND_LASTNAME_UNREQUIRED_IN_ADMIN_CUSTOMER_PAGE';
$oldKeys[] = 'GOOGLE_ADWORDS_CONVERSION_CODE';
$oldKeys[] = 'GOOGLE_ANALYTICS_ACCOUNT';
foreach ($oldKeys as $key) {
    if (isset($settings['GLOBAL_MODULES'][$key])) {
        $str = "DELETE FROM `tx_multishop_configuration` where configuration_key='" . addslashes($key) . "';";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$str = "describe `tx_multishop_configuration`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'configuration_title') {
        if ($row['Type'] == 'varchar(64)') {
            $str2 = "ALTER TABLE  `tx_multishop_configuration` CHANGE  `configuration_title`  `configuration_title` varchar(150)";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$str = "describe `tx_multishop_configuration_group`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    if ($row['Field'] == 'configuration_title') {
        if ($row['Type'] == 'varchar(64)') {
            $str2 = "ALTER TABLE  `tx_multishop_configuration_group` CHANGE  `configuration_title`  `configuration_title` varchar(150)";
            $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
            $messages[] = $str2;
        }
    }
}
$str = "select related_to from tx_multishop_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_categories` ADD `related_to` INT(11) NOT NULL DEFAULT '0', ADD INDEX (`related_to`);";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
if ($this->ms['MODULES']['ENABLE_FULLTEXT_SEARCH_IN_PRODUCTS_SEARCH']) {
    // Products table
    $columns = array();
    $columns[] = 'products_model';
    $table_name = 'tx_multishop_products';
    foreach ($columns as $column) {
        $str = "show indexes from `" . $table_name . "` where Index_type='FULLTEXT' and Column_name ='" . $column . "'";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
            $str = "ALTER TABLE  `" . $table_name . "` ADD FULLTEXT `" . $column . "_full` (`" . $column . "`)";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages[] = $str;
        }
    }
    // Products description table
    $columns = array();
    $columns[] = 'products_name';
    $columns[] = 'products_description';
    $table_name = 'tx_multishop_products_description';
    foreach ($columns as $column) {
        $str = "show indexes from `" . $table_name . "` where Index_type='FULLTEXT' and Column_name ='" . $column . "'";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
            $str = "ALTER TABLE  `" . $table_name . "` ADD FULLTEXT `" . $column . "_full` (`" . $column . "`)";
            $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
            $messages[] = $str;
        }
    }
    $str = "show indexes from `" . $table_name . "` where Key_name='products_name_full_combined'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    if (!$GLOBALS['TYPO3_DB']->sql_num_rows($qry)) {
        // combined
        $str = "ALTER TABLE  `" . $table_name . "` ADD FULLTEXT `products_name_full_combined` (products_name,products_description)";
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        $messages[] = $str;
    }
}
$indexes = array();
$table_name = 'tx_multishop_orders';
$str = "show indexes from `" . $table_name . "` ";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    $indexes[] = $rs['Key_name'];
}
$required_index = 'combined';
if (!in_array($required_index, $indexes)) {
    $str = "ALTER TABLE  `" . $table_name . "` ADD KEY `" . $required_index . "` (`page_uid`,`deleted`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$required_index = 'combined_shipping_method';
if (!in_array($required_index, $indexes)) {
    $str = "ALTER TABLE  `" . $table_name . "` ADD KEY `" . $required_index . "` (`shipping_method`(25),`shipping_method_label`(25))";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$required_index = 'combined_payment_method';
if (!in_array($required_index, $indexes)) {
    $str = "ALTER TABLE  `" . $table_name . "` ADD KEY `" . $required_index . "` (`payment_method`(25),`payment_method_label`(25))";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select grand_total_excluding_vat from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD `grand_total_excluding_vat` decimal(24,14) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$sql_order = "select orders_id from tx_multishop_orders where grand_total >0 and (grand_total_excluding_vat is null or grand_total_excluding_vat=0)";
$qry_order = $GLOBALS['TYPO3_DB']->sql_query($sql_order);
if ($GLOBALS['TYPO3_DB']->sql_num_rows($qry_order)) {
    require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'pi1/classes/class.tx_mslib_order.php');
    $mslib_order = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mslib_order');
    $mslib_order->init($this);
    while ($rs_order = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry_order)) {
        $mslib_order->repairOrder($rs_order['orders_id']);
    }
    $messages[] = "grand_total_excluding_vat value in tx_multishop_orders table updated";
}
$str = "select delivery_state from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD `delivery_state` varchar(150) default '', ADD KEY `delivery_state` (`delivery_state`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select product_capital_price from tx_multishop_orders_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders_products` ADD `product_capital_price` decimal(24,14) default '0.00000000000000', ADD KEY `product_capital_price` (`product_capital_price`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `categories_external_url` from tx_multishop_categories_description limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_categories_description` ADD `categories_external_url` text default ''";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
    // copy existing categories_url to new column
    $str_copy = "update tx_multishop_categories_description cd set cd.categories_external_url=(select c.categories_url from tx_multishop_categories c where c.categories_id=cd.categories_id) where language_id=0";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str_copy);
}
$str = "select search_engines_allow_indexing from tx_multishop_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_categories` ADD search_engines_allow_indexing tinyint(1) NOT NULL default '1', ADD KEY `search_engines_allow_indexing` (`search_engines_allow_indexing`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$updateOrders = 0;
$types = array();
$types[] = 'billing';
$types[] = 'delivery';
$cols = array();
$cols[] = 'tr_iso_nr';
$cols[] = 'tr_name_en';
$cols[] = 'tr_parent_iso_nr';
$cols[] = 'tr_parent_name_en';
foreach ($types as $type) {
    foreach ($cols as $col) {
        $str = 'select ' . $type . '_' . $col . ' from tx_multishop_orders limit 1';
        $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
        if (!$qry) {
            $colType = '';
            switch ($col) {
                case 'tr_iso_nr':
                    $colType = 'int(11) default \'0\'';
                    break;
                case 'tr_name_en':
                    $colType = 'varchar(50) default \'\'';
                    break;
                case 'tr_parent_iso_nr':
                    $colType = 'int(11) default \'0\'';
                    break;
                case 'tr_parent_name_en':
                    $colType = 'varchar(50) default \'\'';
                    break;
            }
            if ($colType) {
                $str = 'ALTER TABLE `tx_multishop_orders` ADD ' . $type . '_' . $col . ' ' . $colType . ', ADD KEY `' . $type . '_' . $col . '` (`' . $type . '_' . $col . '`)';
                //error_log($str);
                $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
                $messages[] = $str;
            }
        }
    }
}
$filter = array();
$filter[] = 'billing_tr_iso_nr=0';
$count = mslib_befe::getCount('', 'tx_multishop_orders', '', $filter);
if ($count) {
    $str = 'SELECT o.orders_id, o.billing_country,o.delivery_country, o.billing_tr_iso_nr, o.delivery_tr_iso_nr from tx_multishop_orders o';
    $query = $GLOBALS['TYPO3_DB']->sql_query($str);
    $rows = $GLOBALS['TYPO3_DB']->sql_num_rows($query);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query)) != false) {
        if (!$row['billing_tr_iso_nr']) {
            $types = array();
            $types[] = 'billing';
            $types[] = 'delivery';
            foreach ($types as $type) {
                $str2 = 'select st.* from static_countries sc, static_territories st where sc.cn_short_en=\'' . addslashes($row[$type . '_country']) . '\' and st.tr_iso_nr=sc.cn_parent_tr_iso_nr';
                $query2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                $rows2 = $GLOBALS['TYPO3_DB']->sql_num_rows($query2);
                if ($rows2) {
                    $row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query2);
                    $updateArray = array();
                    $updateArray[$type . '_tr_iso_nr'] = $row2['tr_iso_nr'];
                    $updateArray[$type . '_tr_name_en'] = $row2['tr_name_en'];
                    $str2 = 'select * from static_territories where tr_iso_nr=' . $row2['tr_parent_iso_nr'];
                    $query2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                    $rows2 = $GLOBALS['TYPO3_DB']->sql_num_rows($query2);
                    if ($rows2) {
                        $row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($query2);
                        $updateArray[$type . '_tr_parent_iso_nr'] = $row2['tr_iso_nr'];
                        $updateArray[$type . '_tr_parent_name_en'] = $row2['tr_name_en'];
                    }
                    if (count($updateArray)) {
                        $query2 = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_multishop_orders', 'orders_id=' . $row['orders_id'], $updateArray);
                        $res2 = $GLOBALS['TYPO3_DB']->sql_query($query2);
                        $messages[] = $query2;
                    }
                }
            }
        }
    }
}
$table = 'tx_multishop_invoices';
$str = "describe `" . $table . "`";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
    switch ($row['Field']) {
        case 'discount':
        case 'amount':
            if ($row['Type'] == 'double(10,4)') {
                $str2 = "ALTER TABLE `" . $table . "` CHANGE `" . $row['Field'] . "`  `" . $row['Field'] . "` decimal(24,14) DEFAULT  '0.00000000000000'";
                $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                $messages[] = $str2;
            }
            break;
    }
}
$str = "select `discount_percentage` from tx_multishop_orders_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders_products` ADD `discount_percentage` decimal(8,2) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `discount_amount` from tx_multishop_orders_products limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders_products` ADD `discount_amount` decimal(24,14) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `cart_minimum_amount` from tx_multishop_payment_methods limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_payment_methods` ADD `cart_minimum_amount` decimal(24,14) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `cart_maximum_amount` from tx_multishop_payment_methods limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_payment_methods` ADD `cart_maximum_amount` decimal(24,14) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `cart_minimum_amount` from tx_multishop_shipping_methods limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_shipping_methods` ADD `cart_minimum_amount` decimal(24,14) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `cart_maximum_amount` from tx_multishop_shipping_methods limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_shipping_methods` ADD `cart_maximum_amount` decimal(24,14) default '0.00000000000000'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `delivery_birthday` from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD `delivery_birthday` int(11) default '0'";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `delivery_coc_id` from tx_multishop_orders limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_orders` ADD `delivery_coc_id` varchar(150) default ''";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `building` from fe_users limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `fe_users` ADD `building` varchar(150) default '', ADD KEY `building` (`building`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `region` from fe_users limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `fe_users` ADD `region` varchar(150) default '', ADD KEY `region` (`region`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `default_path` from tx_multishop_products_to_categories limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_products_to_categories` ADD `default_path` tinyint(1) default '0', ADD KEY `default_path` (`default_path`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
} else {
    $table = 'tx_multishop_products_to_categories';
    $str = "describe `" . $table . "`";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        switch ($row['Field']) {
            case 'default_path':
                if (!$row['Key']) {
                    $str2 = "ALTER TABLE  `tx_multishop_products_to_categories` ADD INDEX (  `default_path` ) ;";
                    $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                    $messages[] = $str2;
                }
                break;
        }
    }
}
$str = "select `page_uid` from tx_multishop_coupons limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tx_multishop_coupons` ADD `page_uid` int(11) default '0', ADD KEY `page_uid` (`page_uid`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
} else {
    $table = 'tx_multishop_coupons';
    $str = "describe `" . $table . "`";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($qry)) != false) {
        switch ($row['Field']) {
            case 'page_uid':
                if (!$row['Key']) {
                    $str2 = "ALTER TABLE  `tx_multishop_coupons` ADD INDEX (  `page_uid` ) ;";
                    $qry2 = $GLOBALS['TYPO3_DB']->sql_query($str2);
                    $messages[] = $str2;
                }
                break;
        }
    }
}
$str = "select `tx_multishop_bank_name` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_bank_name` varchar(127) default '', ADD KEY `tx_multishop_bank_name` (`tx_multishop_bank_name`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `tx_multishop_iban` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_iban` varchar(127) default '', ADD KEY `tx_multishop_iban` (`tx_multishop_iban`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `tx_multishop_bic` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_bic` varchar(127) default '', ADD KEY `tx_multishop_bic` (`tx_multishop_bic`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `tx_multishop_vat_id` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_vat_id` varchar(127) default '', ADD KEY `tx_multishop_vat_id` (`tx_multishop_vat_id`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `tx_multishop_vat_number` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_vat_number` varchar(127) default '', ADD KEY `tx_multishop_vat_number` (`tx_multishop_vat_number`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `tx_multishop_coc_id` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_coc_id` varchar(127) default '', ADD KEY `tx_multishop_coc_id` (`tx_multishop_coc_id`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
$str = "select `tx_multishop_paypal_account` from tt_address limit 1";
$qry = $GLOBALS['TYPO3_DB']->sql_query($str);
if (!$qry) {
    $str = "ALTER TABLE `tt_address` ADD `tx_multishop_paypal_account` varchar(127) default '', ADD KEY `tx_multishop_paypal_account` (`tx_multishop_paypal_account`)";
    $qry = $GLOBALS['TYPO3_DB']->sql_query($str);
    $messages[] = $str;
}
?>