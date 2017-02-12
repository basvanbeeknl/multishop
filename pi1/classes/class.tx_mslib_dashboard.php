<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
/***************************************************************
 *  Copyright notice
 *  (c) 2010 BVB Media BV - Bas van Beek <bvbmedia@gmail.com>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 * Hint: use extdeveval to insert/update function index above.
 */
class tx_mslib_dashboard extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
    var $dashboardArray = array();
    var $widgetsArray = array();
    var $compiledWidgets = array();
    var $senabledWidgets = array();
    var $layouts = array(
            'layout1big1small' => 2,
            'layout1small1big' => 2,
            'layout2cols' => 2,
            'layout3cols' => 3,
            'layout4cols' => 4
    );
    function initLanguage($ms_locallang) {
        $this->pi_loadLL();
        //array_merge with new array first, so a value in locallang (or typoscript) can overwrite values from ../locallang_db
        $this->LOCAL_LANG = array_replace_recursive($this->LOCAL_LANG, is_array($ms_locallang) ? $ms_locallang : array());
        if ($this->altLLkey) {
            $this->LOCAL_LANG = array_replace_recursive($this->LOCAL_LANG, is_array($ms_locallang) ? $ms_locallang : array());
        }
    }
    function init($ref) {
        mslib_fe::init($ref);
    }
    function setSection($string) {
        $this->dashboardArray['section'] = $string;
    }
    function renderWidgets() {
        switch ($this->dashboardArray['section']) {
            case 'admin_home':
                $this->enabledWidgets['ordersPerMonth'] = 1;
                $this->enabledWidgets['google_chart_orders'] = 1;
                $this->enabledWidgets['google_chart_customers'] = 1;
                $this->enabledWidgets['google_chart_carts'] = 1;
                $this->enabledWidgets['customersPerMonth'] = 1;
                $this->enabledWidgets['turnoverPerProduct'] = 1;
                $this->enabledWidgets['turnoverPerYear'] = 1;
                $this->enabledWidgets['referrerToplist'] = 1;
                $this->enabledWidgets['searchKeywordsToplist'] = 1;
                $this->enabledWidgets['ordersLatest'] = 1;
                $this->enabledWidgets['turnoverPerMonth'] = 1;
                //$this->enabledWidgets['turnoverGraphCurrentWeek'] = 1;
                $this->enabledWidgets['turnoverThisWeekLastWeek'] = 1;
                $this->enabledWidgets['profitThisMonthLastMonth'] = 1;
                $this->enabledWidgets['turnoverMainCategoryThisMonthLastMonth'] = 1;
                // ORDERS TOTAL TABLES EOF
                break;
            case 'admin_edit_customer':
                $this->enabledWidgets['ordersPerMonth'] = 1;
                $this->enabledWidgets['google_chart_orders'] = 1;
                $this->enabledWidgets['google_chart_carts'] = 1;
                $this->enabledWidgets['turnoverPerMonth'] = 1;
                $this->enabledWidgets['turnoverPerYear'] = 1;
                $this->enabledWidgets['referrerToplist'] = 1;
                $this->enabledWidgets['searchKeywordsToplist'] = 1;
                $this->enabledWidgets['ordersLatest'] = 1;
                break;
            default:
                if (is_numeric($this->dashboardArray['section']) && $this->dashboardArray['section']>0) {
                    $widgetsData = mslib_befe::getRecords($this->dashboardArray['section'], 'tx_multishop_dashboard_portlets', 'dashboard_id', array('status=1 AND deleted=0'));
                    if (is_array($widgetsData) && count($widgetsData)) {
                        foreach ($widgetsData as $widgetData) {
                            $this->enabledWidgets[$widgetData['widget_key']] = 1;
                        }
                    }
                }
                break;
        }
        $this->compiledWidgets = array();
        //hook to let other plugins further manipulate the settings
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_dashboard.php']['renderWidgetsPreProc'])) {
            $params = array();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_dashboard.php']['renderWidgetsPreProc'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        // COMPILING
        foreach ($this->enabledWidgets as $widgetKey => $enabled) {
            if ($enabled) {
                $compiledWidget = tx_mslib_dashboard::compileWidget($widgetKey);
                if ($compiledWidget['additionalHeaderData']['content']) {
                    $GLOBALS['TSFE']->additionalHeaderData[$compiledWidget['additionalHeaderData']['key']] = $compiledWidget['additionalHeaderData']['content'];
                }
                $this->compiledWidgets[$widgetKey] = $compiledWidget;
            }
        }
    }
    function compileWidget($key) {
        $compiledWidget = array();
        switch ($key) {
            case 'google_chart_orders':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/google_chart_new_orders.php');
                break;
            case 'google_chart_customers':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/google_chart_new_customers.php');
                break;
            case 'google_chart_carts':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/google_chart_carts.php');
                break;
            case 'turnoverPerMonth':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/turn_over_per_month.php');
                break;
            case 'turnoverPerYear':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/turn_over_per_year.php');
                break;
            case 'referrerToplist':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/referrerToplist.php');
                break;
            case 'customersPerMonth':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/customersPerMonth.php');
                break;
            case 'searchKeywordsToplist':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/searchKeywordsToplist.php');
                break;
            case 'ordersLatest':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/ordersLatest.php');
                break;
            case 'ordersPerMonth':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/ordersPerMonth.php');
                break;
            case 'turnoverPerProduct':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/turn_over_per_product.php');
                break;
            /*
            case 'turnoverGraphCurrentWeek':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/turnoverGraphCurrentWeek.php');
                break;
            */
            case 'turnoverThisWeekLastWeek':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/turnoverThisWeekLastWeek.php');
                break;
            case 'profitThisMonthLastMonth':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/profitThisMonthLastMonth.php');
                break;
            case 'turnoverMainCategoryThisMonthLastMonth':
                require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('multishop') . 'scripts/admin_pages/includes/admin_home/turnoverMainCategoryThisMonthLastMonth.php');
                break;
            default:
                //hook to let other plugins further manipulate the settings
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_dashboard.php']['compileWidgetDefault'])) {
                    $params = array('key' => &$key, 'compiledWidget' => &$compiledWidget);
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_dashboard.php']['compileWidgetDefault'] as $funcRef) {
                        \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
                    }
                }
                break;
        }
        return $compiledWidget;
    }
    function displayDashboard() {
        $GLOBALS['TSFE']->additionalHeaderData[] = '
		<style>
		body { min-width: 520px; }
		.column { width: 170px; float: left; padding-bottom: 100px; }
		.portlet { margin: 0 1em 1em 0; }
		.portlet-header { margin: 0.3em; padding-bottom: 4px; padding-left: 0.2em; }
		.portlet-header .ui-icon { float: right; }
		.portlet-content { padding: 0.4em; }
		.ui-sortable-placeholder { border: 1px dotted black; visibility: visible !important; height: 50px !important; }
		.ui-sortable-placeholder * { visibility: hidden; }
		</style>
		<link href="' . $this->FULL_HTTP_URL_MS . 'templates/admin_multishop/css/admin_home.css" rel="stylesheet" type="text/css"/>
		<script type="text/javascript">
		makesortable = function() {
			var old_position;
			$(".column").sortable({
				connectWith: ".column",
				cancel: \'.state-disabled,select\',
				revert: true,
				scroll: true,
				tolerance: "pointer",
				start: function(event, ui) {
					//alert(ui.item.attr("title"))
					//old_position=ui.item;
					//var old_position=ui.item.parent().attr(\'id\');
				},
				stop: function(event, ui)  {
				    var widget_counter=0;
				    var widgets_list=[];
				    $(".widgetRow > div.column").each(function(rowIndex, rowElem) {
                        $(rowElem).find(".portlet").each(function(colIndex, colElem) {
                            if ($(colElem).attr(\'id\')!=undefined) {
                                widgets_list[widget_counter]=\'tx_multishop_pi1[widgets_sort][\' + rowIndex + \'][\' + colIndex + \']=\' + $(colElem).attr(\'id\');
                                widget_counter++;
                            }
                        });
				    });
				    if (widgets_list.length) {
				        jQuery.ajax({
                            type: \'POST\',
                            url: \''.mslib_fe::typolink($this->shop_pid . ',2002', '&tx_multishop_pi1[page_section]=admin_dashboards&tx_multishop_pi1[action]=save_widget_sort').'\',
                            cache :false,
                            dataType: \'json\',
                            data: widgets_list.join(\'&\'),
                            success:
                                function(d) {
                                    if (d.status==\'OK\') {
                                        location.reload();
                                    }
                                },
                            error:
                                function() {
            
                                }
                        });
				    }
					// after dropping replace the old one
					//alert(ui.item.attr("title"))
					//new_position=ui.item;
					//old_position.remove();
					//return false;
				},
				update: function(event, ui) {
				    
				    
					/*var cooked = {};
					var cookie_value = "";
					$(".widgetRow").each(function(index, domEle) {
						cooked[index] = {};

						var widgetRow_id = $(domEle).attr("id");
						if (widgetRow_id == undefined) {
							var widgetRow_id = $(domEle).attr("class");
							var widgetRow_array = widgetRow_id.split(" ");
							if (widgetRow_array[1].indexOf("layout") > -1) {
								cooked[index]["rclass"] = widgetRow_array[1];
							}
						} else {
							var widgetRow_array = widgetRow_id.split("_");
							cooked[index]["rclass"] = widgetRow_array[0];
						}

						cooked[index]["column"] = {};
						$(domEle).children().each(function(columnindex, columndata) {
							cooked[index]["column"][columnindex] = {};
							cooked[index]["column"][columnindex]["widget_key"] = {}
							$(columndata).children().each(function(widget_index, widget_data) {
								cooked[index]["column"][columnindex]["widget_key"][widget_index] = {};
								cooked[index]["column"][columnindex]["widget_key"][widget_index] = $(widget_data).attr("id");
							});
						});

						cookie_value = JSON.stringify(cooked);
					});

					$.cookie(\'widget_position\', cookie_value, { expires: 7, path: \'/\'});*/

					// refresh google charts, so they fit again nicely in the new target box
					//drawChartgoogle_chart_orders();
					//drawChartgoogle_chart_customers();
					//drawChartgoogle_chart_carts();
				}
			});
			$(".column").disableSelection();
		};
		jQuery(document).ready(function($) {
			$(".portlet").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
			.find(".portlet-header")
			.addClass("ui-widget-header ui-corner-all")
			.prepend("<span class=\'ui-icon ui-icon-minusthick\'></span>")
			.end().find(".portlet-content");

			$(".portlet-header .ui-icon").click(function() {
				$(this).toggleClass("ui-icon-minusthick").toggleClass("ui-icon-plusthick");
				$(this).parents(".portlet:first").find(".portlet-content").toggle();
			});
			makesortable();
			'.((isset($this->get['tx_multishop_pi1']['widget']) && !empty($this->get['tx_multishop_pi1']['widget'])) ? '
			$(\'html, body\').animate({
                scrollTop: $("#'.$this->get['tx_multishop_pi1']['widget'].'").offset().top
            }, 2000);
			' : '').'
		});
		</script>
		';
        $col = 0;
        $intCounter = 0;
        /*$headerData = '
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#addWidgetButton").click(function(e) {
				e.preventDefault();
				var rowType=$(this).prev().val();
				var html=\'<div class="widgetRow \'+rowType+\' ui-state-default" style="width:100%;clear:both;min-height:50px;">\';
				switch(rowType)
				{
					';
        if (is_array($layouts)) {
            foreach ($layouts as $layout => $cols) {
                $headerData .= '
                    case "' . $layout . '": var cols=\'' . $cols . '\'; for (i=0;i<cols;i++) { html+=\'<div class="column columnCol\'+(i+1)+\'">dummy</div>\'; }
                    break;
                    ';
            }
        }
        $headerData .= '
					default: html =\'<div class="column">dummy</div>\';
				}
				html+=\'</div>\';
				$(".column-wrapper").prepend(html);
				$(".column").sortable("refresh");
		//		$(".column").disableSelection();
				makesortable();
			});
		});
		</script>
		';
        $GLOBALS['TSFE']->additionalHeaderData[] = $headerData;*/
        $headerData = '';
        $pageLayout = array();
        if (!is_numeric($this->dashboardArray['section']) && isset($_COOKIE['widget_position']) && !empty($_COOKIE['widget_position'])) {
            $cookie_json_decode = json_decode($_COOKIE['widget_position']);
            if (is_array($cookie_json_decode)) {
                foreach ($cookie_json_decode as $row_index => $rows) {
                    $pageLayout[$row_index]['class'] = $rows->rclass;
                    if (count($rows->column) > 0) {
                        foreach ($rows->column as $column_index => $columns) {
                            $widgets = array();
                            if (count($columns->widget_key) > 0) {
                                foreach ($columns->widget_key as $wkey) {
                                    $widgets[] = $wkey;
                                }
                            }
                            $pageLayout[$row_index]['cols'][$column_index] = $widgets;
                        }
                    }
                }
            }
        } else {
            switch ($this->dashboardArray['section']) {
                case 'admin_home':
                case 'admin_edit_customer':
                    $pageLayout[] = array(
                            'class' => 'layout1col',
                            'cols' => array(
                                    0 => array('turnoverPerProduct')
                            )
                    );
                    $pageLayout[] = array(
                            'class' => 'layout1big1small',
                            'cols' => array(
                                    0 => array('ordersLatest'),
                                    1 => array(
                                            'google_chart_orders',
                                            'google_chart_customers',
                                            'google_chart_carts',
                                            'turnoverThisWeekLastWeek',
                                            'profitThisMonthLastMonth',
                                            'turnoverMainCategoryThisMonthLastMonth'
                                    )
                            )
                    );
                    $pageLayout[] = array(
                            'class' => 'layout2cols',
                            'cols' => array(
                                    0 => array(
                                            'searchKeywordsToplist',
                                            'referrerToplist'
                                    ),
                                    1 => array(
                                            'turnoverPerMonth',
                                            'ordersPerMonth',
                                            'customersPerMonth'
                                    )
                            )
                    );
                    /*$pageLayout[] = array(
                            'class' => 'layout1col',
                            'cols' => array(
                                    0 => array('turnoverMainCategoryThisMonthLastMonth')
                            )
                    );
                    $pageLayout[] = array(
                            'class' => 'layout1col',
                            'cols' => array(
                                    0 => array('turnoverGraphCurrentWeek')
                            )
                    );*/
                    /*$pageLayout[] = array(
                            'class' => 'layout1col',
                            'cols' => array(
                                    0 => array('turnoverThisWeekLastWeek')
                            )
                    );
                    $pageLayout[] = array(
                            'class' => 'layout1col',
                            'cols' => array(
                                    0 => array('profitThisMonthLastMonth')
                            )
                    );*/
                    break;
                default:
                    if (is_numeric($this->dashboardArray['section']) && $this->dashboardArray['section']>0) {
                        $dashboard=mslib_befe::getRecord($this->dashboardArray['section'], 'tx_multishop_dashboard', 'id', array('status=1 AND deleted=0'));
                        if (is_array($dashboard) && $dashboard['id']) {
                            $pageLayout=array();
                            $layoutClass=array();
                            if ($dashboard['dashboard_layout']) {
                                $layoutClass['class'] = $dashboard['dashboard_layout'];
                                $dashboard_widgets=mslib_befe::getRecords($dashboard['id'], 'tx_multishop_dashboard_portlets', 'dashboard_id', array('status=1 AND deleted=0'), '', 'colpos asc, sort_order asc');
                                $count_widget=count($dashboard_widgets);
                                if (is_array($dashboard_widgets) && $count_widget) {
                                    $cols_num=0;
                                    switch ($dashboard['dashboard_layout']) {
                                        case 'layout1big1small':
                                        case 'layout1small1big':
                                        case 'layout2cols':
                                            $cols_num=2;
                                            break;
                                        case 'layout1col':
                                            $cols_num=1;
                                            break;
                                        case 'layout3cols':
                                            $cols_num=3;
                                            break;
                                        case 'layout4cols':
                                            $cols_num=4;
                                            break;
                                    }
                                    $widget_percol=ceil($count_widget/$cols_num);
                                    $col_number=0;
                                    $col_counter=0;
                                    foreach ($dashboard_widgets as $dashboard_widget) {
                                        $colpos = $dashboard_widget['colpos'];
                                        if ($colpos>0) {
                                            $colpos -= 1;
                                        }
                                        if ($cols_num==1) {
                                            $colpos = 0;
                                        }
                                        $layoutClass['cols'][$colpos][] = $dashboard_widget['widget_key'];
                                        /*$col_counter++;
                                        if (($col_counter%$widget_percol)==0) {
                                            $col_number++;
                                        }*/
                                    }
                                    if ($cols_num>1) {
                                        for ($c=0; $c<$cols_num; $c++) {
                                            if (!isset($layoutClass['cols'][$c])) {
                                                $layoutClass['cols'][$c][]='emptyWidget';
                                            }
                                        }
                                    }
                                }
                                $pageLayout[]=$layoutClass;
                            }
                        }
                    }
                    break;

            }
        }
        //hook to let other plugins further manipulate the settings
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_dashboard.php']['displayDashboardPageLayout'])) {
            $params = array('pageLayout' => &$pageLayout);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/multishop/pi1/classes/class.tx_mslib_dashboard.php']['displayDashboardPageLayout'] as $funcRef) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
        $content .= '<div class="column-wrapper">';
        //shuffle($layouts);
        if (is_array($pageLayout)) {
            foreach ($pageLayout as $rowNr => $cols) {
                $content .= '<div class="widgetRow ' . $cols['class'] . '" id="' . $cols['class'] . '_' . $rowNr . '">';
                $colNr = 0;
                foreach ($cols['cols'] as $col) {
                    $colNr++;
                    $content .= '<div class="column columnCol' . ($colNr) . '" id="' . $cols['class'] . '_' . $rowNr . '_' . ($colNr - 1) . '">';
                    foreach ($col as $widget_key) {
                        $intCounter++;
                        if ($intCounter == 1) {
                            //$idName='intro';
                            $idName = 'widget' . $intCounter;
                        } else {
                            $idName = 'widget' . $intCounter;
                        }
                        if (isset($this->compiledWidgets[$widget_key]['content'])) {
                            $widget = $this->compiledWidgets[$widget_key];
                            $content .= '<div class="portlet' . ($widget['class'] ? ' ' . $widget['class'] : '') . '" rel="' . $intCounter . '" id="' . $widget_key . '">
                                <div class="portlet-header">
                                    <h3>' . ($widget['title'] ? $widget['title'] : 'Widget ' . $intCounter) . '</h3>
                                </div>
                                <div class="portlet-content">
                                    ' . $widget['content'] . '
                                </div>
					        </div>
					        ';
                        } else {
                            if ($widget_key=='emptyWidget') {
                                $content .= '<div>&nbsp;</div>';
                            }
                        }
                    }
                    $content .= '</div>';
                }
                $content .= '</div>';
            }
        }
        $content .= '</div>';
        return $content;
    }
}
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/multishop/pi1/classes/class.tx_mslib_dashboard.php"]) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/multishop/pi1/classes/class.tx_mslib_dashboard.php"]);
}
?>