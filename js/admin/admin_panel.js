function renderAdminMenu(json, type, includeDescinFooter,menuType) {
    var footerDesc = includeDescinFooter;
    var admin_content = '';
    if (menuType==undefined) {
        var menuType='dropdown';
    }
    if (type != 'newheader') {

        var total_tabs = 0;
        jQuery.each(json, function (_tablevel0_key, _tablevel0) {
            if (_tablevel0_key != '') {
                total_tabs++;
            }
        });

        var tab_counter = 0;
        jQuery.each(json, function (tablevel1_key, tablevel1) {
            tab_counter++;
            var has_sub_class='';
            has_sub_class=' dropdown ms_admin_has_subs mainmenu_parents';
            if (tablevel1.subs == null) {
                has_sub_class='';
            }
            var active_class='';
            if (tablevel1.active!=undefined && tablevel1.active==1) {
                active_class=' active';
            }
            switch(menuType) {
                case 'collapse':
                    admin_content += '<li role="presentation" class="panel panel-default ' + tablevel1_key + active_class + has_sub_class + '">';
                    break;
                case 'dropdown':
                    admin_content += '<li role="presentation" class="' + tablevel1_key + active_class + has_sub_class + '">';
                    break;
            }
            if (tablevel1.label == null && tablevel1.description) {
                admin_content += tablevel1.description;

            } else {
                if (tablevel1.subs == null) {
                    if (tablevel1.link != null) {
/*
                        switch(menuType) {
                            case 'collapse':
                                admin_content += '<a href="' + tablevel1.link + '"' + (tablevel1.link_params != undefined ? tablevel1.link_params : '') + ' role="button" data-toggle="collapse" data-parent="#accordion" href="#subs' + tablevel1_key + '" aria-expanded="false" aria-controls="subs' + tablevel1_key + '">';
                                break;
                            case 'dropdown':
                                admin_content += '<a href="' + tablevel1.link + '"' + (tablevel1.link_params != undefined ? tablevel1.link_params : '') + '>';
                                break;
                        }
*/
                        admin_content += '<a href="' + tablevel1.link + '"' + (tablevel1.link_params != undefined ? tablevel1.link_params : '') + ' class="admin_panel_menu">';
                    } else {
                        admin_content += '<span>';
                    }
                    if (tablevel1.class) {
                        admin_content += '<i class="' + tablevel1.class + '"></i>';
                    }
                    admin_content += tablevel1.label;
                    if (tablevel1.link != null) {
                        admin_content += '</a>';
                    } else {
                        admin_content += '</span>';
                    }
                } else {
                    var tablevel2_ctr = 0;
                    jQuery.each(tablevel1.subs, function (_tablevel2_key, _tablevel2) {
                        if (_tablevel2_key != '') {
                            tablevel2_ctr++;
                        }
                    });

                    total_tablevel2 = tablevel2_ctr;
                    counter_tablevel2 = 0;
                    switch(menuType) {
                        case 'collapse':
                            admin_content += '<a href="#subs' + tablevel1_key + '" id="subsA' + tablevel1_key + '" class="a_dropdown collapsed admin_panel_menu" role="button" data-toggle="collapse" data-parent="#tx_multishop_admin_header" data-link="' + (tablevel1.link != undefined ? tablevel1.link : '#') + '" aria-expanded="false" aria-controls="subs' + tablevel1_key + '">';
                            break;
                        case 'dropdown':
                            admin_content += '<a href="' + (tablevel1.link != undefined ? tablevel1.link : '#') + '" id="subsA' + tablevel1_key + '" class="a_dropdown admin_panel_menu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                            break;
                    }
                    if (tablevel1.class) {
                        admin_content += '<i class="' + tablevel1.class + '"></i>';
                    }
                    admin_content += tablevel1.label + '</a>';
                    switch(menuType) {
                        case 'collapse':
                            admin_content += '<ul id="subs' + tablevel1_key + '" class="panel-collapse collapse" role="tabpanel" aria-labelledby="subsA' + tablevel1_key + '">';
                            break;
                        case 'dropdown':
                            admin_content += '<ul class="dropdown-menu">';
                            break;
                    }
                    jQuery.each(tablevel1.subs, function (tablevel2_key, tablevel2) {
                        counter_tablevel2++;
                        var active_class='';
                        if (tablevel2.active!=undefined && tablevel2.active==1) {
                            active_class='active';
                        }
                        if (type == 'header' && (counter_tablevel2 == total_tablevel2)) {
                            tablevel2_params = 'dropdown_bottom';

                        } else if (type == 'footer' && (counter_tablevel2 == 1)) {
                            tablevel2_params = 'dropdown_top';

                        } else {
                            tablevel2_params = '';
                        }
                        if (tablevel2.divider) {
                            admin_content += '<li class="ms_admin_divider"></li>';
                            return true;
                        }
                        if (tablevel2.subs == null) {
                            if (tablevel2.link) {
                                admin_content += '<li class="' + tablevel2_params + ' ' + active_class + '"><a href="' + tablevel2.link + '"' + (tablevel2.link_params != undefined ? tablevel2.link_params : '') + ' class="admin_panel_menu">';
                                if (tablevel2.class) {
                                    admin_content += '<i class="' + tablevel2.class + '"></i>';
                                }
                                admin_content += tablevel2.label + '<span class="ms_admin_menu_item_description"></span></a></li>';
                            } else {
                                admin_content += '<li class="' + tablevel2_params + ' ' + active_class + '"><span>';
                                if (tablevel2.class) {
                                    admin_content += '<i class="' + tablevel2.class + '"></i>';
                                }
                                admin_content += tablevel2.label + '<span class="ms_admin_menu_item_description"></span></span></li>';
                            }

                        } else {
                            admin_content += '<li class="ms_admin_has_subs ' + active_class + '"><a href="' + (tablevel2.link != undefined ? tablevel2.link : '#') + '" class="a_dropdown admin_panel_menu">';
                            if (tablevel2.class) {
                                admin_content += '<i class="' + tablevel2.class + '"></i>';
                            }
                            admin_content += tablevel2.label + '</a>';
                            admin_content += '<ul class="dropdown-menu">';
                            var tablevel3_ctr = 0;
                            jQuery.each(tablevel2.subs, function (_tablevel3_key, _tablevel3) {
                                if (_tablevel3_key != '') {
                                    tablevel3_ctr++;
                                }
                            });

                            total_tablevel3 = tablevel3_ctr;
                            counter_tablevel3 = 0;

                            jQuery.each(tablevel2.subs, function (tablevel3_key, tablevel3) {
                                counter_tablevel3++;
                                var active_class='';
                                if (tablevel3.active!=undefined && tablevel3.active==1) {
                                    active_class='active';
                                }
                                if (type == 'header' && (counter_tablevel3 == total_tablevel3)) {
                                    tablevel3_params = 'dropdown_bottom';

                                } else if (type == 'footer' && (counter_tablevel3 == 1)) {
                                    tablevel3_params = 'dropdown_top';

                                } else {
                                    tablevel3_params = '';
                                }

                                if (tablevel3.subs == null) {
                                    admin_content += '<li class="' + tablevel3_key + ' ' + active_class + '">';
                                    if (tablevel3.link) {
                                        /*
                                         if (tablevel3.description != null) {
                                         admin_content += '<a href="' + tablevel3.link + '"' + (tablevel3.link_params != undefined ? tablevel3.link_params : '') + '>' + tablevel3.label + '<span class="ms_admin_menu_item_description">' + tablevel3.description + '</span></a>';
                                         } else {
                                         admin_content += '<a href="' + tablevel3.link + '"' + (tablevel3.link_params != undefined ? tablevel3.link_params : '') + '>' + tablevel3.label + '<span class="ms_admin_menu_item_description"></span></a>';
                                         }
                                         */
                                        admin_content += '<a href="' + tablevel3.link + '"' + (tablevel3.link_params != undefined ? tablevel3.link_params : '') + ' class="admin_panel_menu">';
                                        if (tablevel3.class) {
                                            admin_content += '<i class="' + tablevel3.class + '"></i>';
                                        }
                                        admin_content += tablevel3.label + '<span class="ms_admin_menu_item_description"></span></a>';
                                    } else {
                                        admin_content += '<span>';
                                        if (tablevel3.class) {
                                            admin_content += '<i class="' + tablevel3.class + '"></i>';
                                        }
                                        admin_content += tablevel3.label + '<span class="ms_admin_menu_item_description"></span></span>';
                                    }
                                    admin_content += '</li>';

                                } else {
                                    admin_content += '<li class="' + (tablevel3_key != '' ? tablevel3_key + ' ' : '') + 'ms_admin_has_subs' + ' ' + active_class + '">';

                                    if (tablevel3.link) {
                                        admin_content += '<a href="' + tablevel3.link + '"' + (tablevel3.link_params != undefined ? tablevel3.link_params : '') + ' class="a_dropdown admin_panel_menu">';
                                        if (tablevel3.class) {
                                            admin_content += '<i class="' + tablevel3.class + '"></i>';
                                        }
                                        admin_content += tablevel3.label + '<span class="ms_admin_menu_item_description"></span></a>';

                                    } else {
                                        admin_content += '<span>';
                                        if (tablevel3.class) {
                                            admin_content += '<i class="' + tablevel3.class + '"></i>';
                                        }
                                        admin_content += tablevel3.label + '<span class="ms_admin_menu_item_description"></span></span>';
                                    }

                                    admin_content += '<ul>';

                                    var tablevel4_ctr = 0;
                                    jQuery.each(tablevel3.subs, function (_tablevel4_key, _tablevel4) {
                                        if (_tablevel4_key != '') {
                                            tablevel4_ctr++;
                                        }
                                    });

                                    total_tablevel4 = tablevel4_ctr;
                                    counter_tablevel4 = 0;

                                    jQuery.each(tablevel3.subs, function (tablevel4_key, tablevel4) {
                                        counter_tablevel4++;
                                        var active_class='';
                                        if (tablevel4.active!=undefined && tablevel4.active==1) {
                                            active_class='active';
                                        }
                                        if (type == 'header' && (counter_tablevel4 == total_tablevel4)) {
                                            tablevel4_params = 'dropdown_bottom';

                                        } else if (type == 'footer' && (counter_tablevel4 == 1)) {
                                            tablevel4_params = 'dropdown_top';

                                        } else {
                                            tablevel4_params = '';
                                        }

                                        if (tablevel4.subs == null) {
                                            admin_content += '<li class="' + tablevel4_key + ' ' + active_class + '">';

                                            if (tablevel4.link) {
                                                admin_content += '<a href="' + tablevel4.link + '"' + (tablevel4.link_params != undefined ? tablevel4.link_params : '') + ' class="admin_panel_menu">';
                                                if (tablevel4.class) {
                                                    admin_content += '<i class="' + tablevel4.class + '"></i>';
                                                }
                                                admin_content += tablevel4.label + '<span class="ms_admin_menu_item_description"></span></a>';
                                            } else {
                                                admin_content += '<span>';
                                                if (tablevel4.class) {
                                                    admin_content += '<i class="' + tablevel4.class + '"></i>';
                                                }
                                                admin_content += tablevel4.label + '<span class="ms_admin_menu_item_description"></span></span>';
                                            }

                                            admin_content += '</li>';
                                        } else {

                                            admin_content += '<li class="ms_admin_has_subs ' + tablevel4_key + ' ' + active_class + '">';

                                            if (tablevel4.link) {
                                                admin_content += '<a href="' + tablevel4.link + '"' + (tablevel4.link_params != undefined ? tablevel4.link_params : '') + ' class="a_dropdown admin_panel_menu">';
                                                if (tablevel4.class) {
                                                    admin_content += '<i class="' + tablevel4.class + '"></i>';
                                                }
                                                admin_content += tablevel4.label + '<span class="ms_admin_menu_item_description"></span></a>';
                                            } else {
                                                admin_content += '<span>';
                                                if (tablevel4.class) {
                                                    admin_content += '<i class="' + tablevel4.class + '"></i>';
                                                }
                                                admin_content += tablevel4.label + '<span class="ms_admin_menu_item_description"></span></span>';
                                            }

                                            admin_content += '<ul>';

                                            var tablevel5_ctr = 0;
                                            jQuery.each(tablevel4.subs, function (_tablevel5_key, _tablevel5) {
                                                if (_tablevel5_key != '') {
                                                    tablevel5_ctr++;
                                                }
                                            });

                                            total_tablevel5 = tablevel5_ctr;
                                            counter_tablevel5 = 0;

                                            jQuery.each(tablevel4.subs, function (tablevel5_key, tablevel5) {
                                                counter_tablevel5++;
                                                var active_class='';
                                                if (tablevel5.active!=undefined && tablevel5.active==1) {
                                                    active_class='active';
                                                }
                                                if (type == 'header' && (counter_tablevel5 == total_tablevel5)) {
                                                    tablevel5_params = 'dropdown_bottom';

                                                } else if (type == 'footer' && (counter_tablevel5 == 1)) {
                                                    tablevel5_params = 'dropdown_top';

                                                } else {
                                                    tablevel5_params = '';
                                                }

                                                admin_content += '<li class="' + tablevel5_key + ' ' + active_class + '">';

                                                if (tablevel5.link) {
                                                    admin_content += '<a href="' + tablevel5.link + '"' + (tablevel5.link_params != undefined ? tablevel5.link_params : '') + ' class="a_dropdown admin_panel_menu">';
                                                    if (tablevel5.class) {
                                                        admin_content += '<i class="' + tablevel5.class + '"></i>';
                                                    }
                                                    admin_content += tablevel5.label + '<span class="ms_admin_menu_item_description"></span></a>';

                                                } else {
                                                    admin_content += '<span>';
                                                    if (tablevel5.class) {
                                                        admin_content += '<i class="' + tablevel5.class + '"></i>';
                                                    }
                                                    admin_content += tablevel5.label + '<span class="ms_admin_menu_item_description"></span></span>';
                                                }

                                                admin_content += '</li>';

                                            });

                                            admin_content += '</ul></li>';

                                        }

                                    });

                                    admin_content += '</ul></li>';
                                }
                            });
                            admin_content += '</ul></li>';
                        }
                    });
                    admin_content += '</ul>';
                }
            }
            switch(menuType) {
                case 'collapse':
                    admin_content += '</li>';
                    //admin_content += '</div>';
                    break;
                case 'dropdown':
                    admin_content += '</li>';
                    break;
            }
        });
    } else {
        var total_tabs = 0;
        jQuery.each(json, function (_tablevel0_key, _tablevel0) {
            if (_tablevel0_key != '') {
                total_tabs++;
            }
        });
        var tab_counter = 0;
        var newheader_tree='';
        jQuery.each(json, function (tablevel1_key, tablevel1) {
            tab_counter++;
            if (tablevel1.label == null && tablevel1.description) {
                admin_content += tablevel1.description;
            } else {
                if (tablevel1.subs == null) {
                    if (tablevel1.link != null) {
                        admin_content += '<a href="' + tablevel1.link + '"' + (tablevel1.link_params != undefined ? tablevel1.link_params : '') + ' class="admin_panel_menu">' + tablevel1.label + '</a>';
                    } else {
                        admin_content += tablevel1.label;
                    }
                } else {
                    var has_sub_class='';
                    has_sub_class=' dropdown ms_admin_has_subs mainmenu_parents';
                    var active_class='';
                    if (tablevel1.active!=undefined && tablevel1.active==1) {
                        active_class=' active';
                    }
                    newheader_tree += '<ul id="tx_multishop_admin_newheader">';
                    newheader_tree += '<li role="presentation" class="' + tablevel1_key + active_class + has_sub_class + '">';
                    if (tablevel1.link != null) {
                        newheader_tree += '<a href="' + tablevel1.link + '"' + (tablevel1.link_params != undefined ? tablevel1.link_params : '') + ' class="admin_panel_menu">' + tablevel1.label + '</a>';
                    } else {
                        newheader_tree += tablevel1.label;
                    }
                    // subs
                    newheader_tree += '<ul class="dropdown-menu">';
                    jQuery.each(tablevel1.subs, function (tablevel2_key, tablevel2) {
                        counter_tablevel2++;
                        var active_class='';
                        if (tablevel2.active!=undefined && tablevel2.active==1) {
                            active_class='active';
                        }
                        if (type == 'header' && (counter_tablevel2 == total_tablevel2)) {
                            tablevel2_params = 'dropdown_bottom';

                        } else if (type == 'footer' && (counter_tablevel2 == 1)) {
                            tablevel2_params = 'dropdown_top';

                        } else {
                            tablevel2_params = '';
                        }
                        if (tablevel2.divider) {
                            newheader_tree += '<li class="ms_admin_divider"></li>';
                            return true;
                        }
                        newheader_tree += '<li class="ms_admin_has_subs ' + active_class + '"><a href="' + (tablevel2.link != undefined ? tablevel2.link : '#') + '" class="a_dropdown admin_panel_menu">';
                        if (tablevel2.class) {
                            newheader_tree += '<i class="' + tablevel2.class + '"></i>';
                        }
                        newheader_tree += tablevel2.label + '</a>';
                    });
                    newheader_tree += '</ul>';

                    newheader_tree += '</li>';
                    newheader_tree += '</ul>';
                }
            }
        });
        if (newheader_tree!='') {
            admin_content += '<div class="newheader_dropdown_wrapper">' + newheader_tree + '</div>';
        }
    }
    return admin_content;
}