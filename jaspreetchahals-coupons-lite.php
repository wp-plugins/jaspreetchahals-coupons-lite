<?php
    /*
    Plugin Name: JaspreetChahal's Coupons Lite
    Plugin URI: http://jaspreetchahal.org/wordpress-jc-coupon-plugin-lite
    Description: JC Coupon Lite plugin provides easy to use coupon management to be included in your posts and pages or even in side bars. There are heaps of options to create a coupon with multiple coupon themes. 
    Version: 1.6
    Author: Jaspreet Chahal
    Author URI: http://jaspreetchahal.org
    */
    global $jcorgcr_plugin_version;
    $jcorgcr_plugin_version = "1.6";
    global $jcorgcr_db_version;
    $jcorgcr_db_version = "1.7";
    global $jcorgcrZCSWF;
    $jcorgcrZCSWF = plugin_dir_url(__FILE__);
    register_activation_hook(__FILE__,'jcorgcr_couponactivate');
    register_activation_hook(__FILE__,'jcorgcr_dbinstall');
    register_activation_hook(__FILE__,'jcorgcr_db_install_data');
    register_deactivation_hook(__FILE__,'jcorgcr_coupondeactivate');
    function jcorgcr_couponactivate() {
        // because I am using wordpress 3.3.2 so it doesn't make sense to support old versions
        /*global $wp_version;
        if(version_compare($wp_version,'3.3.2',"<")) {
        deactivate_plugins(__FILE__);
        die("This plugin needs your wordpress to be at least 3.3.2.");
        } */   

        // set options if they already don't exists
        if(get_option("jcorgcr_default_height") == "") { 
            add_option("jcorgcr_default_height","140"); 
            add_option("jcorgcr_default_width","430"); 
            add_option("jcorgcr_default_theme_color","blue"); 
            add_option("jcorgcr_default_notification_email",""); 
            add_option("jcorgcr_default_failure_limit","5"); 
            add_option("jcorgcr_default_send_expiry_notification","Yes"); 
            add_option("jcorgcr_default_coupon_category","1"); 
            add_option("jcorgcr_obfuscate","No"); 
            add_option("disply_jcorgcr_url","No"); 
        }   
        jcorgcrGetMsg(false,"activate");
    }

    function jcorgcr_coupondeactivate() {
        jcorgcrGetMsg(false,"deactivate");
    }

    function jcorgcr_dbinstall() {
        global $wpdb;
        global $jcorgcr_db_version;

        $table_name = $wpdb->prefix . "jcorgcr_categories";
        $table_name_coupons = $wpdb->prefix . "jcorgcr_coupons";

        $sqlcats = "CREATE TABLE $table_name (
        `id` INT (5) UNSIGNED NOT NULL AUTO_INCREMENT,
        `category` CHAR(80) NOT NULL,
        `created_on` INT (11) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE = MYISAM ;

        );";
        $sqlcoupons = "CREATE TABLE `$table_name_coupons` (
        `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
        `coupon_type` enum('Load URL','Copy And Load URL','Show with Copy Option','Always Show Coupon') NOT NULL DEFAULT 'Copy And Load URL',
        `coupon` char(20) NOT NULL,
        `coupon_layout` enum('Short','All Options','Short Wide','Square') NOT NULL DEFAULT 'All Options',
        `coupon_theme` enum('Green','LightGreen','DullBlue','Blue','Red','Grey','Purple','Orange') NOT NULL DEFAULT 'Grey',
        `category_id` int(3) NOT NULL,
        `description` char(255) DEFAULT NULL,
        `destination_url` char(255) NOT NULL,
        `expiry_type` enum('Date','Text') NOT NULL DEFAULT 'Date',
        `savings` char(20) NOT NULL,
        `notification_email` char(100) NOT NULL,
        `expiry` char(20) NOT NULL,
        `name` char(80) NOT NULL,
        `title` char(80) NOT NULL,
        `number_of_votes` int(3) NOT NULL DEFAULT 2,
        `yeses` int(4) NOT NULL  DEFAULT 0,
        `nos` int(4) NOT NULL  DEFAULT 0,
        `width` int(4) NOT NULL DEFAULT '600',
        `height` int(4) NOT NULL DEFAULT '200',
        `save_background` char(20) NOT NULL DEFAULT 'black',
        `shortcode` char(150) NOT NULL,
        `created_on` int(11) NOT NULL,
        `last_modified_on` int(11) DEFAULT '0',
        `last_notification_sent` int(11) DEFAULT '0',
        `imported_on` int(11) DEFAULT '0',
        `admin_id` int(8) NOT NULL,
        `display_tse` enum('Yes','No') NOT NULL DEFAULT 'No',
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8
        ";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sqlcats);
        dbDelta($sqlcoupons);

        add_option("jcorgcr_db_version", $jcorgcr_db_version);
    }

    function jcorgcr_db_install_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . "jcorgcr_categories";
        $category_exists = $wpdb->get_var("select count(*) from $table_name where category='Default'");
        if($category_exists == 0) {
            $category = "Default";
            $rows_affected = $wpdb->insert( $table_name, 
                array( 'created_on' => current_time('timestamp'), 
                    'category' => $category) 
            );
        }
    }

    // check if db update is required
    function jcorgcr_checkdbupdate() {
        global $jcorgcr_db_version;
        if(get_option("jcorgcr_db_version") != $jcorgcr_db_version) {
            jcorgcr_dbinstall();
        }
        update_option("jcorgcr_db_version",$jcorgcr_db_version);
    }
    add_action("plugins_loaded",'jcorgcr_checkdbupdate');

    // shortcode installation
    function jcorgcrShortCodeHandler($atts) {
        global $wpdb;
        extract(shortcode_atts(array(
            "slug"=>"",
            "id"=>""
            ),$atts));

        if(intval($id) != 0) {
            $coupon = $wpdb->get_row($wpdb->prepare("select * from $wpdb->prefix" . "jcorgcr_coupons where id=%d",intval($id)));
            if(is_object($coupon)) { 
                return jcorgcrFullyLoadedTpl(true,$coupon);
            }
            else {
                return "";
            }
        }
    }
    add_shortcode('jcorgcrcoupon','jcorgcrShortCodeHandler');


    /*add_action('','jcorgcr_email_notifications');
    function jcorgcr_email_notifications() {
    // check for expired coupons and coupons that have received maximum votest
    }*/
    add_action("admin_menu","jcorgcr_create_menu");
    function jcorgcr_create_menu(){
        //create new top-level menu
        if(is_admin()){
            add_menu_page('JCORGCR Plugin Settings', 'JC WP-Coupons Lite','administrator', __FILE__, 'jcorgcr_settings_page',plugins_url('/images/jcorgcr.png', __FILE__));
            add_submenu_page( __FILE__, 'Manage Categories', 'Manage Categories','administrator', __FILE__.'_categories_settings', 'jcorgcr_settings_categories');
            add_submenu_page( __FILE__, 'JC Coupon Add-Edit', 'Add Coupon','administrator', __FILE__.'_add', 'jcorgcr_add_coupon');
            add_submenu_page( __FILE__, 'Manage Coupons', 'Manage Coupons','administrator', __FILE__.'_manage', 'jcorgcr_manage_coupons');    
            add_submenu_page( __FILE__, 'Import coupons from CSV', 'Import Coupons','administrator', __FILE__.'_import', 'jcorgcr_import');
        }
    }

    function ieversion() {
        preg_match('/MSIE ([0-9]\.[0-9])/',$_SERVER['HTTP_USER_AGENT'],$reg);
        if(!isset($reg[1])) {
            return -1;
        } else {
            return floatval($reg[1]);
        }
    }
    add_action("admin_init","jcorgcr_registration_settings");
    function jcorgcr_registration_settings() {
        // register our settings
        register_setting("jcorgcr-setting-general","jcorgcr_default_height","jcorgcr_validate_height");
        register_setting("jcorgcr-setting-general","jcorgcr_default_width","jcorgcr_validate_width");
        register_setting("jcorgcr-setting-general","jcorgcr_default_theme_color");
        register_setting("jcorgcr-setting-general","jcorgcr_default_notification_email","jcorgcr_validate_options");
        register_setting("jcorgcr-setting-general","jcorgcr_default_failure_limit");
        register_setting("jcorgcr-setting-general","jcorgcr_default_send_expiry_notification");
        register_setting("jcorgcr-setting-general","jcorgcr_default_coupon_category");
        register_setting("jcorgcr-setting-general","disply_jcorgcr_url");    

        /*register_setting('jcorgcr-setting-general', 'jcorgcr-setting-general', 'plugin_options_validate' );
        add_settings_section('main_settings_section', 'Main Settings', '', __FILE__);
        add_settings_field('jcorgcr_default_height', 'Default Height', 'jcorgcr_default_height', __FILE__, 'main_settings_section');
        */

        wp_enqueue_script('jcorgcr_jqueryui',"https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js");
        wp_enqueue_script('jcorgcr_admin_script',plugins_url("/jaspreetchahals-coupons-lite/js/jcorgcr_admin.min.js"));
        wp_enqueue_script('jcorgcr_admin_hc',plugins_url("/jaspreetchahals-coupons-lite/js/jquery.hc.min.js",dirname(__FILE__)));
        wp_enqueue_style('jcorgcr_admin_css',plugins_url("/jaspreetchahals-coupons-lite/css/jcorgcr.min.css",dirname(__FILE__)));
        wp_enqueue_style('jcorgcr_jqueryui',plugins_url("/jaspreetchahals-coupons-lite/css/ui-lightness/jquery-ui-1.8.20.custom.css",dirname(__FILE__)));
        wp_enqueue_script('thickbox');
        wp_enqueue_script('media-upload');
        wp_enqueue_style('thickbox');



        if(ieversion() < 9 && ieversion()>0){
            wp_enqueue_style('jcorgcr_ie_comp',plugins_url("/jaspreetchahals-coupons-litecss/jcorg_iecomp.min.css",dirname(__FILE__)));        
        }

    }

    function jcorgcr_validate_height($input) {
        if(intval($input) < 160 || intval($input) > 250 || !is_numeric($input)) { 
            add_settings_error("jcorgcr_default_height","112211","Height should be in between 160 and 250",'error');
            return 180;
        }
        return $input;
    }
    function jcorgcr_validate_width($input) {
        if(intval($input) < 430 || intval($input) > 750 || !is_numeric($input)) { 
            add_settings_error("jcorgcr_default_width","112211","Width should be in between 400 and 750",'error');
            return 430;
        }
        return $input;
    }

    function jcorgcr_validate_options($input) {
        if(is_email($input) == false) { 
            add_settings_error("jcorgcr_default_send_expiry_notification","112211","Invalid Email ID",'error');
        }
        return $input;
    }
    /*add_action('wp_head','jcorgcr_process_iel9_comp');

    function jcorgcr_process_iel9_comp() {
    echo '<!--[if lt IE 8 ]>
    <link href="'.plugins_url("/jcorgcouponslite/css/jcorg_iecomp.css").'" >
    <![endif]-->';
    }*/

    function jcorgcr_eu_scripts() {
        wp_enqueue_script( 'jquery');
        wp_enqueue_script('jcorgcr_eu_script',plugins_url("/jaspreetchahals-coupons-lite/js/jcorgcr_wp.min.js"));
        wp_enqueue_script('jcorgcrzs',plugins_url("/jaspreetchahals-coupons-lite/js/jquery.zclip.js"));
        wp_enqueue_style('jcorgcr_css',plugins_url("/jaspreetchahals-coupons-lite/css/jcorgcr.min.css"));
        if(ieversion() < 9 && ieversion()>0){
            wp_enqueue_style('jcorgcr_ie_comp',plugins_url("/jaspreetchahals-coupons-lite/css/jcorg_iecomp.min.css"));        
        }
    }

    add_action('wp_enqueue_scripts','jcorgcr_eu_scripts');


    /*function jcorgcr_add_media_button($context) {
    $wp_jcorgcr_media_button_image = plugin_dir_url(__FILE__).'images/jcorgcr.png';
    $wp_jcorgcr_media_button = ' %s<a href="'.esc_url("media-upload.php?height=300px&amp;width=500px&amp;type=jcorgcouponslite&amp;TB_iframe=true").'" class="thickbox"  title="Insert JC Coupons"><img src="'.$wp_jcorgcr_media_button_image.'"></a>';
    return sprintf($context, $wp_jcorgcr_media_button);
    }*/


    function jcorgcr_settings_categories() {
        _jcorgcr_e();
    ?>    
    <h2>Categories Management</h2>
    <h3>This option is only available in Pro version</h3>
    <?php
    }

    function jcorgcr_settings_page() {
        global $wpdb;
        jcorgcrGetMsg(true);
        _jcorgcr_e();
    ?> 
    <h2>Jaspreet Chahal's Coupon Revealer General settings</h2>
    <form id="jcorg_general_settings" method="post" action="options.php">
        <?php settings_fields("jcorgcr-setting-general"); ?>
        <?php 
            $errors = get_settings_errors("",true);
            $errmsgs = array();
            $msgs = "";
            if(count($errors) >0) {
                foreach ($errors as $error) {
                    if($error["type"] == "error")
                        $errmsgs[] = $error["message"];
                    else if($error["type"] == "updated")
                        $msgs = $error["message"];
                }

                if(!class_exists("JCORGCRUTIL")) {
                    require dirname(__FILE__).DIRECTORY_SEPARATOR."includes/jcorgcrutil.php";
                }
                echo JCORGCRUTIL::makeErrorsHtml($errmsgs,'warning1');
                if(strlen($msgs) > 0) {
                    echo "<div class='jcorgcrsuccess' style='width:90%'>$msgs</div>";
                }
            }
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Default Height</th>
                <td><input type="text" name="jcorgcr_default_height"
                    value="<?php echo get_option("jcorgcr_default_height"); ?>" style="padding:5px"/> Should be number. Max value: 250</td>
            </tr>
            <tr valign="top">
                <th scope="row">Default Width</th>
                <td><input type="text" name="jcorgcr_default_width"
                    value="<?php echo get_option("jcorgcr_default_width"); ?>"  style="padding:5px"/></td>
            </tr>
            <tr valign="top">
                <th scope="row">Default Theme Color</th>
                <td>
                    <?php $colors = array('Green','LightGreen','DullBlue','Blue','Red','Grey','Purple','Orange'); sort($colors);?>
                    <select name="jcorgcr_default_theme_color">
                        <?php 
                            foreach ($colors as $color) {
                                $selected = "";
                                if(strtolower(get_option('jcorgcr_default_theme_color')) == strtolower($color)) {
                                    $selected = "selected";
                                }
                            ?>    
                            <option value="<?php _e($color)?>" <?php echo $selected ?>><?php _e($color)?></option>

                            <?php
                            }
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Default notification email</th>
                <td><input type="text" name="jcorgcr_default_notification_email"
                    value="<?php echo get_option('jcorgcr_default_notification_email'); ?>"  style="padding:5px"/><em>(this option is only available in Pro version)</em></td>
            </tr>
            <tr valign="top">
                <th scope="row">Default Notification failure limit</th>
                <td><input type="number" name="jcorgcr_default_failure_limit" min="0"size="2" maxlength="2" style="width: 40px;padding:5px"
                    value="<?php echo get_option('jcorgcr_default_failure_limit'); ?>" /> Maximum number of votes to alert you that coupon does not work <em>(this option is only available in Pro version)</em></td>
            </tr>
            <tr valign="top">
                <th scope="row">Send Expiry Notification</th>
                <td><input type="checkbox" name="jcorgcr_default_send_expiry_notification" <?php if(get_option('jcorgcr_default_send_expiry_notification') == "Yes" || get_option('jcorgcr_default_send_expiry_notification') == "") echo "checked='checked'";?>
                    value="Yes" 
                    /> Send an Alert when coupon expires <em>(this option is only available in Pro version)</em></td>
            </tr>
            <tr valign="top">
                <th scope="row">Link back to author site.</th>
                <td><input type="checkbox" name="disply_jcorgcr_url" <?php if(get_option('disply_jcorgcr_url') == "Yes") echo "checked='checked'";?>
                    value="Yes" 
                    /> <em>(Inserts a very small link to author's website http://jaspreetchahal.org)</em></td>
            </tr>
            <tr valign="top">
                <th scope="row">Default Coupon Category</th>
                <td>
                    <select name="jcorgcr_default_coupon_category">
                        <?php 

                            $jcorgcr_categories = $wpdb->get_results("select * from ".$wpdb->prefix."jcorgcr_categories");
                            foreach ($jcorgcr_categories as $row) {
                                $selected = "";
                                if(get_option('jcorgcr_default_coupon_category') == $row->id) {
                                    $selected = "selected";
                                }
                            ?> 
                            <option value="<?php _e($row->id)?>" <?php echo $selected ?>><?php _e($row->category)?></option>

                            <?php

                            }
                        ?>    
                    </select><em>(Lite ships with Default category only. Category management is available in Pro version)</em>


                </td>
            </tr>
            <!--<tr valign="top">
            <th scope="row">Obfuscate target ULR</th>
            <td><input type="checkbox" name="jcorgcr_obfuscate" 
            value="Yes" <?php checked(get_option('jcorgcr_obfuscate'),"Yes",true)?> /> Display local URL on hover, this option is handy if you don't want to show affiliate link when user hovers on Coupon. Coming Soon..</td>
            </tr>-->
        </table>
        <p class="submit">
            <input type="submit" class="button-primary"
                value="Save Changes" />
        </p>
    </form>
    <?php 
    }
    function jcorgcr_add_coupon(){
        _jcorgcr_e();
        global $wpdb;
        $couponid = 0;
        if(isset($_GET["mod"])){
            $couponid = intval($_GET["mod"]);            
        }
    ?>    
    <div id="icon-options-general" class="icon32"></div><h1 >Jaspreet Chahal's Coupons plugin Lite - Add Coupon</h1>
    <div id="jcorgcr-add-coupon-frm-msg"></div>
    <!-- Coupon Markup-->
    <div id="jcorgcr-add-coupon-frm-preview">


    </div>    
    <!-- Coupon Markup Ends Here-->
    <div class="form-wrap">
        <div id="jcorgcr-add-coupon-frm-msg"></div>
        <form id="jcorgcr-add-coupon-frm" onsubmit="return false">
            <?php wp_nonce_field('jcorgcr_nonce_catch');?>
            <!-- Free flow layout -->
            <?php 
                if($couponid > 0) {
                    $coupon = $wpdb->get_row($wpdb->prepare("select * from $wpdb->prefix" . "jcorgcr_coupons where id=%d",intval($couponid)));            
                }    
                //echo ieversion();            
            ?>    
            <div id="col-container">
                <!-- column A -->

                <div style="float:left;width:33%">
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Coupon Theme *</label>
                        <select id="coupon-type" name="coupon_layout" style="width:80%;" onchange="JcorgUtil.enableExtraOptions(this.value)">
                            <option value="All Options" <?php if(is_object($coupon) && $coupon->coupon_layout == "All Options") echo "selected"?>>Fully Loaded</option>                            
                        </select>
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Expiry Type *</label>
                        <select name="expiry_type" style="width:80%;" onchange="JcorgUtil.switchDateControl(this.value)">
                            <option value="Date" <?php if(is_object($coupon) && $coupon->expiry_type == "Date") echo "selected"?>>Date</option>
                            <option value="Text" <?php if(is_object($coupon) && $coupon->coupon_layout == "Text") echo "selected"?>>Custom Message</option>
                        </select>
                    </div>
                    <div  id="expiry_type_ctrl" style="margin: 10px;" >
                        <?php if(is_object($coupon) == false || (is_object($coupon) && $coupon->expiry_type == "Date")) {?>
                            <label style="font-weight: bold;">Pick Expiry Date *</label>
                            <input type="text" name="expiry" id="jcorgcr_date_field"  style="width:80%;" onchange="JcorgUtil.updateAllOptions('expiryd',this.value)" readonly="readonly" value="<?php if(is_object($coupon)) echo date("d/m/Y",$coupon->expiry) ?>">
                            <?php }else { ?>
                            <label style="font-weight: bold;color:red">Enter Custom Expiry Text *</label>
                            <input type="text" maxlength="25" id="jcorgcr_date_field" name="expiry" style="width:80%;"   onkeyup="JcorgUtil.updateAllOptions('expiry',this.value)" value="<?php if(is_object($coupon)) echo stripslashes($coupon->expiry) ?>">
                            <?php } ?>
                    </div>
                    <div  style="margin: 10px;" >
                        <label style="font-weight: bold;">Category *</label>
                        <select name="category_id"  style="width:80%;">
                            <?php                                 
                                $jcorgcr_categories = $wpdb->get_results("select * from ".$wpdb->prefix."jcorgcr_categories");
                                foreach ($jcorgcr_categories as $row) {
                                    $selected = "";
                                    if(is_object($coupon) && $coupon->category_id == $row->category_id) $selected =  "selected";
                                ?> 
                                <option value="<?php _e($row->id)?>" <?php echo $selected?> ><?php _e($row->category)?></option>
                                <?php                                    
                                }
                            ?>    
                        </select>
                    </div>
                    <div  style="margin: 10px;"> 
                        <label style="font-weight: bold;">Expiry/Not working alert email</label>
                        <input type="text" name="notification_email" style="width:80%;" value="<?php if(!is_object($coupon) ) {_e(get_option('jcorgcr_default_notification_email'));} else { echo stripslashes($coupon->notification_email);} ?>"><br>
                        <em>Works in Pro Version</em>
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Number of votes for `Not Working` before notification email is sent</label>
                        <input type="number" maxlength="3"  name="number_of_votes" style="width:10%;" value="<?php if(is_object($coupon)) echo stripslashes($coupon->number_of_votes); else {echo get_option('jcorgcr_default_failure_limit');}?>"><br>
                        <em>Works in Pro Version</em>
                    </div>
                    <!--<div  style="margin: 10px;">
                    <input type="checkbox" name="ajax" > <strong>Use AJAX to get Coupon Code after Click</strong>
                    </div>-->
                </div>

                <!-- column B -->
                <div style="float:left;width:33%">

                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Coupon Code/Promotional Code *</label>
                        <input type="text" name="coupon" style="width:80%;" maxlength="12" value="<?php if(is_object($coupon)) echo stripslashes($coupon->coupon)?>" onkeyup="JcorgUtil.updateAllOptions('couponcode',this.value)" onblur="jQuery('#jcorgcr-lbl-couponcode-top').hide().fadeIn();jQuery('#jcorgcr-short-coupon-top').hide().fadeIn();" onfocus="jQuery('#jcorgcr-lbl-couponcode-top').show().fadeOut();jQuery('#jcorgcr-short-coupon-top').show().fadeOut();">
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Name - your reference (internal use) *</label>
                        <input type="text" name="name" style="width:80%;" value="<?php if(is_object($coupon)) echo esc_attr(stripcslashes($coupon->name))?>">
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Savings *</label>
                        <input type="text" style="width:80%;" name="savings_text" id="savings_text" value="<?php if(is_object($coupon)) echo stripslashes($coupon->savings)?>" maxlength="10"  onkeyup="JcorgUtil.updateAllOptions('savings',this.value)">
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Title *</label>
                        <input type="text" name="title" style="width:80%;" maxlength="60" value="<?php if(is_object($coupon)) echo stripslashes($coupon->title)?>" onkeyup="JcorgUtil.updateAllOptions('title',this.value)">
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Description</label>
                        <textarea type="text" name="description" rows="6" style="width:80%;" onkeyup="JcorgUtil.updateAllOptions('description',this.value)"><?php if(is_object($coupon)) echo stripslashes($coupon->description)?></textarea>
                    </div>
                </div>
                <!-- column C -->
                <div style="float:left;width:34%">
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Theme Color</label>
                        <div id="jcorgcr-colors">
                            <input type="radio" id="Green" value="Green" name="coupon_theme" onclick="JcorgUtil.changeTheme('green')" <?php if((is_object($coupon) && $coupon->coupon_theme =="Green") || (!is_object($coupon) && get_option("jcorgcr_default_theme_color") == "Green")) echo " checked='checked' " ?>/><label for="Green"  style="float: left;width:10%;background:#2a660b;color:white; box-shadow:#EEE 1px 2px 3px">&nbsp;</label>
                            <input type="radio" id="Blue" value="Blue" name="coupon_theme"  onclick="JcorgUtil.changeTheme('blue')" <?php if(is_object($coupon) && $coupon->coupon_theme =="Blue" || (!is_object($coupon) && get_option("jcorgcr_default_theme_color") == "Blue")) echo " checked='checked' " ?>/><label for="Blue" style="float: left;width:10%;background:#3b679e;color:white; box-shadow:#EEE 1px 2px 3px">&nbsp;</label>
                            <input type="radio" id="Red" value="Red" name="coupon_theme"  onclick="JcorgUtil.changeTheme('red')"  <?php if(is_object($coupon) && $coupon->coupon_theme =="Red" || (!is_object($coupon) && get_option("jcorgcr_default_theme_color") == "Red")) echo " checked='checked' " ?>/><label for="Red" style="float: left;width:10%;background:#a90329;color:white; box-shadow:#EEE 1px 2px 3px">&nbsp;</label>
                            <input type="radio" id="Grey" value="Grey" name="coupon_theme"  onclick="JcorgUtil.changeTheme('grey')"  <?php if(is_object($coupon) && $coupon->coupon_theme =="Grey" || (!is_object($coupon) && get_option("jcorgcr_default_theme_color") == "Grey")) echo " checked='checked' "; ?>/><label for="Grey" style="float: left;width:10%;background:#4c4c4c;color:white; box-shadow:#EEE 1px 2px 3px">&nbsp;</label>
                            <div class="clear"></div>
                            <em>More Color themes are available in Pro Version</em>
                        </div>
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Destination URL *</label>
                        <input type="text" name="destination_url" style="width:80%;" maxlength="255" value="<?php if(is_object($coupon)) echo stripslashes($coupon->destination_url)?>">
                    </div>
                    <div style="margin: 10px;">
                        <label style="font-weight: bold;">Width</label>
                        <div id="jcorgcr-slider-width" style="width:70%;margin:5px"></div>
                        <input type="text" name="width" id="jcorgcr-width"  style="width:80%;" maxlength="4" min="0" readonly="readonly" value="<?php if(is_object($coupon)) echo stripslashes($coupon->width); else  echo get_option("jcorgcr_default_width");?>">
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Height</label>
                        <div id="jcorgcr-slider-height" style="width:70%;margin:5px"></div>
                        <input type="text" name="height" id="jcorgcr-height"   style="width:80%;" maxlength="4" min="0" readonly="readonly" value="<?php if(is_object($coupon)) echo stripslashes($coupon->height); else  echo get_option("jcorgcr_default_height");?>">
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Coupon Type</label>
                        <select id="coupon-type" name="coupon_type" style="width:80%;" onchange="JcorgUtil.changeWrapperBehaviour(this.value)">
                            <option value="Copy And Load URL" <?php if(is_object($coupon) && $coupon->coupon_type=="Copy And Load URL") echo "selected"?>>Hide Coupon, On Click Show coupon and Goto Destination URL</option>
                            <option value="Load URL" <?php if(is_object($coupon) && $coupon->coupon_type=="Load URL") echo "selected"?>>On Click, Goto Destination URL</option>
                            <option value="Show with Copy Option" <?php if(is_object($coupon) && $coupon->coupon_type=="Show with Copy Option") echo "selected"?>>Show Coupon with Copy option</option>
                        </select>
                    </div>
                    <div  style="margin: 10px;">
                        <label style="font-weight: bold;">Savings Background</label>
                        <input type="hidden" name="savings_background" id="savings-background" value="<?php if(is_object($coupon)) echo stripslashes($coupon->save_background); else echo 'red'?>">
                        <div style="width:100%">
                            <div style="float: left;width:36px;height:36px; background-size:100% 100%;cursor: pointer; " id="jcorgcr-sb-1" class="jcorgcr-all-options-save-background-blackstar jcorgcr-all-options-save-background-detect" onclick="JcorgUtil.chooseSavingsBack('blackstar','1')" >&nbsp;</div>
                            <div style="float: left;width:36px;height:36px; background-size:100% 100%;cursor: pointer; " id="jcorgcr-sb-2" class="jcorgcr-all-options-save-background-black jcorgcr-all-options-save-background-detect" onclick="JcorgUtil.chooseSavingsBack('black','2')" >&nbsp;</div>
                            <div style="float: left;width:36px;height:36px; background-size:100% 100%;cursor: pointer; " id="jcorgcr-sb-4" class="jcorgcr-all-options-save-background-bluestar jcorgcr-all-options-save-background-detect" onclick="JcorgUtil.chooseSavingsBack('bluestar','4')" >&nbsp;</div>
                            <div style="float: left;width:36px;height:36px; background-size:100% 100%;cursor: pointer; " id="jcorgcr-sb-6" class="<?php if(!isset($_POST['savings_background'])) { echo 'jcorgcr-all-saving-back-select';}?> jcorgcr-all-options-save-background-red jcorgcr-all-options-save-background-detect" onclick="JcorgUtil.chooseSavingsBack('red','6')" >&nbsp;</div>
                            <div style="float: left;width:36px;height:36px; background-size:100% 100%;cursor: pointer; " id="jcorgcr-sb-7" class="jcorgcr-all-options-save-background-redstar jcorgcr-all-options-save-background-detect" onclick="JcorgUtil.chooseSavingsBack('redstar','7')" >&nbsp;</div>
                            <div class="clear"></div><em>More options are available in Pro Version</em>
                        </div>                        
                    </div>
                </div>
            </div>
            <br><br>
            <p align="center">
                <a onclick="JcorgUtil.createCoupon('<?php  echo plugin_dir_url(__FILE__)  ?>','jcorgcr-add-coupon-frm','<?php echo $couponid?>')" href="javascript:void(0)" class="button-primary" style="font-size: 19px !important;padding:10px !important;box-shadow:#3b679e 5px 5px 5px">Save <?php if($couponid > 0) {echo " changes to ";}?>Coupon</a>
            </p><br>
            <div align="center">
                <h1>Preview</h1>
                <table class="widefat" style="width:90%; text-align: left;" cellpadding="0" cellspacing="0">
                    <tr><td style="padding: 35px"><h2  style="margin-top: 40px;color:#666; text-transform: uppercase;">(Style 1: Fully Loaded)</h2>
                        <?php echo jcorgcrFullyLoadedTpl(true,$coupon)?>
                        <br><h2 style="color:#C00">More themes are available in Pro version</h2></td></tr>

                </table>
            </div>
            <input type="hidden" name="coupon_id" value="<?php echo $couponid ?>">
        </form>
    </div>
    <script type="text/javascript">
        jQuery("#jcorgcr_date_field").datepicker({dateFormat:'dd/mm/yy'});
        jQuery("#jcorgcr-colors").buttonset();
        jQuery("#jcorgcr-slider-height").slider({max:250,min:140,value:<?php if(is_object($coupon)) echo $coupon->height; else echo '160'?>,slide:function(event,ui){
            jQuery("#jcorgcr-height").val(ui.value);        
            jQuery(".jcorgcoupon-container-outer-aop").css("height",ui.value+"px");
            }
        });
        jQuery("#jcorgcr-slider-width").slider({max:1000,min:400,value:<?php if(is_object($coupon)) echo $coupon->width; else echo '430'?>,slide:function(event,ui){
            jQuery("#jcorgcr-width").val(ui.value);
            jQuery(".jcorgcoupon-container-outer-aop").css("width",(ui.value-20)+"px");

        }});
    </script>

    <?php


    }
    function jcorgGetLink($coupon,$retlink = false) {
        $perm = get_permalink(get_option("jcorgcr_intermediate_page_id"));
        if(strpos($perm,"?")) {
            $perm.='&id='.$coupon->id;
        }
        else {
            $perm.='?id='.$coupon->id;
        }

        $howtoredirect = 'window.open("'.$coupon->destination_url.'");';
        return $howtoredirect;
    }
    function jcorgcrFullyLoadedTpl($inclids = true,$coupon=null) {
        global $jcorgcrZCSWF,$wpdb;  
        $width=430;
        $height=160;
        $id="";
        $dest_url="";
        $hide_wrapper = "";    
        $theme = strtolower(get_option("jcorgcr_default_theme_color"))?strtolower(get_option("jcorgcr_default_theme_color")):"grey";
        $couponcode = '';
        $expiry = 'EXPIRY MESSAGE/DATE goes here';
        $save = 'SAVE MSG';
        $description = 'DESCRIPTION GOES HERE';
        $title = "TITLE GOES HERE";
        $wrapper_title = "Show Coupon";
        $coupon_border_override="";
        $save_background = "red";
        $wrapper_type ="";
        $clip_script = "";
        $ttid = "";
        if(is_object($coupon)) {
            $width = $coupon->width?$coupon->width:$width;        
            $height = $coupon->height?$coupon->height:$height;
            $id = $coupon->id;        
            $ttid = $id.uniqid("jcorg_");
            $dest_url = $coupon->destination_url;        
            $theme = strtolower($coupon->coupon_theme);        
            $theme = $theme?$theme:(strtolower(get_option("jcorgcr_default_theme_color"))?strtolower(get_option("jcorgcr_default_theme_color")):"grey");
            $couponcode = $coupon->coupon;        
            $expiry = "";
            if($coupon->expiry_type=="Date") 
                $expiry="Expires on ".date('d/m/Y',$coupon->expiry);        
            else 
                $expiry=$coupon->expiry; 

            $save = $coupon->savings;        
            $description = $coupon->description;        
            $title = $coupon->title;        
            $script_include = "";

            if($coupon->coupon_type == "Show with Copy Option") {
                $hide_wrapper = ' style="display:none" ';
                $coupon_border_override=' style=" border:4px #e0e9ff dotted !important"';
                $script_include = "";
            }
            else if($coupon->coupon_type == "Load URL") {
                $wrapper_title = 'Activate offer ';
            }
            $wrapper_type = $coupon->coupon_type;
            $save_background = $coupon->save_background;
            if(!isset($_GET["mod"])) {
                $clip_script = '<script type="text/javascript">';
                $ele_id = "jcorgcr-lbl-couponcode-top$ttid";
                if($coupon->coupon_type == "Show with Copy Option") {
                    $ele_id = "jcorgcr-scissors-$ttid";
                }

                $clip_script .= "
                jQuery(document).ready(function(){
                jQuery('div#$ele_id').click(function(){
                ".jcorgGetLink($coupon);                    
                if($coupon->coupon_type == "Copy And Load URL") {
                    $clip_script.='jQuery("#jcorgcr-lbl-couponcode-top'.$ttid.'").hide();';
                }
                $clip_script.="  
                });

                jQuery('#jcorgcr-scissors-$ttid').zclip({
                path:'".plugin_dir_url(__FILE__)."/js/ZeroClipboard.swf',
                copy:document.getElementById('jcorgcr-lbl-couponcode$ttid').innerHTML,
                afterCopy:function(){
                ";

                $clip_script .= "
                alert('Coupon has been copied to your clipboard')
                }                                 
                });
                });";

                $clip_script .= '</script>';                   
            }
        }
        if(isset($_GET["mod"])) $ttid = "";
        $container_id='id="jcorgcr-all-options-container'.$ttid.'"';
        $preview_id='id="jcorgcoupon-preview'.$ttid.'"';
        $wrapper_id='id="jcorgcr-lbl-couponcode-top'.$ttid.'"';
        $wrapper_a_id='id="jcorgcr-lbl-couponcode-top-a'.$ttid.'"';
        $couponcoderep='id="jcorgcr-lbl-couponcode'.$ttid.'"';
        $titleid='id="jcorgcr-lbl-title'.$ttid.'"';
        $descriptionid='id="jcorgcr-lbl-description'.$ttid.'"';
        $savings_id='id="jcorgcr-lbl-savings'.$ttid.'"';
        $expiry_id='id="jcorgcr-lbl-expiry'.$ttid.'"';
        $url = plugin_dir_url(__FILE__);
        $nonce = wp_create_nonce('jcorgcr_nonce_catch');
        $fullyloadedhtml = '
        <div '.$container_id.' class="jcorgcoupon-container-outer">
        <div style="width:'.$width.'px;height:'.$height.'px;" 
        class="jcorgcr-all-options-container jcorgcoupon-container-outer-aop  jcorg-'.$theme.'-coupon-theme-background jcorg-grey-coupon-theme-background-detect " 
        '.$preview_id.'>
        <div class="jcorgcr-all-options-sub-container">
        <div class="jcorgcr-all-options-left-container jcorgcr-all-options-left-top-container  jcorg-'.$theme.'-coupon-theme-background jcorg-grey-coupon-theme-background-detect " 
        '.$wrapper_id.' '.$hide_wrapper.'>
        <div class="jcorgcr-all-options-top-layer"><a '.$wrapper_a_id.' class="jcorgcr-all-options-top-layer-a" style="text-decoration:none">'.$wrapper_title.'</a></div>

        </div>
        <div class="jcorgcr-all-options-left-container" '.$coupon_border_override.'>
        <div class="jcorgcr-all-options-does-it-work">

        <div style="margin:5px">Does this code worked for you?</div>
        <div>
        <a href="javascript:void(0)" class="jcorgcr-coupon-yes" onClick="JcorgWp.feedback(\'chalpiya\',\''.$id.'\',\''.$nonce.'\',\''.$url.'\')">Yes</a>
        <a href="javascript:void(0)" class="jcorgcr-coupon-no" onClick="JcorgWp.feedback(\'nahichaliya\',\''.$id.'\',\''.$nonce.'\',\''.$url.'\')">No</a>
        </div>
        </div>
        <div '.$couponcoderep.' class="jcorgcr-all-options-coupon-container">'.$couponcode.'</div>
        <div style="width:100%;height:40%; " id="jcorgcr-scissors-'.$ttid.'">
        <a href="javascript:void(0)" class="jcorgcr-all-options-scissors">
        &nbsp;
        </a>
        </div>
        </div>

        <div class="jcorgcr-all-options-right-container">
        <!-- title-->
        <div class="jcorgcr-all-options-title  jcorg-'.$theme.'-coupon-theme-background jcorg-grey-coupon-theme-background-detect "  '.$titleid.'>
        '.$title.'
        </div>
        <div class="jcorgcr-all-options-description"  '.$descriptionid.'>
        '.$description.'
        </div>
        <div class="jcorgcr-all-options-save-expiry-container">
        <div class="jcorgcr-all-options-save-background-'.$save_background.'  jcorgcr-all-options-save">
        <div class="jcorgcr-all-options-save-inner " '.$savings_id.'>
        '.$save.'
        </div>
        </div>

        <div class="jcorgcr-all-options-expiry">
        <div class="jcorgcr-all-options-expiry-inner" '.$expiry_id.'>'.$expiry.'</div>
        </div>
        '.$clip_script.'
        </div>
        </div>
        </div>
        </div>
        </div>
        ';
        return $fullyloadedhtml;
    }

    function jcorgcr_manage_coupons() {
        _jcorgcr_e();
        // display coupon table
        global $wpdb;
    ?>    
    <div id="icon-options-general" class="icon32"></div><h1 >Jaspreet Chahal's Coupons plugin - Manage Coupons</h1><br>
    <form id="jcorgcr-manage-coupon" method="post">
        <label style="font-weight: bold;">Category *</label>
        <select name="category_id"  style="width:80%;">
            <option value="" >All</option>
            <?php                                 
                $jcorgcr_categories = $wpdb->get_results("select * from ".$wpdb->prefix."jcorgcr_categories");
                foreach ($jcorgcr_categories as $row) {
                ?> 
                <option value="<?php _e($row->id)?>" ><?php _e($row->category)?></option>
                <?php                                    
                }
            ?>    
        </select>
        <a href="javascript:void(0)" class="button-primary" id="jcorgcr-search-coupons" >Get Coupons</a>
    </form>

    <br>
    <table style="width: 98% !important;" cellpadding="3" cellspacing="3" class="widefat">
        <thead >
            <tr>
                <th width="10%">Slug</th>
                <th width="8%">Coupon Theme</th>
                <th width="8%">Coupon code</th>
                <th width="15%">Title</th>
                <th width="11%">Saving Message</th>
                <th width="8%">Expiry</th>
                <th width="8%">Type</th>
                <th width="7%">Short Code</th>
                <th width="7%">Work yes/no</th>
                <th width="18%">Actions</th>                      
            </tr>
        </thead>
        <tbody id="jcorgcr-coupons-list">
        </tbody>
        <tfoot>
            <tr>
                <th width="10%">Slug</th>
                <th width="8%">Coupon Theme</th>
                <th width="8%">Coupon code</th>
                <th width="15%">Title</th>
                <th width="11%">Saving Message</th>
                <th width="8%">Expiry</th>
                <th width="8%">Type</th>
                <th width="7%">Short Code</th>
                <th width="7%">Work yes/no</th>
                <th width="18%">Actions</th>                      
            </tr>
        </tfoot>


    </table><br><br>
    <a href="javascript:void(0)" class="button-secondary" id="jcorgcr-load-more" style=" font-size: 16px !important;">Load More</a>
    <script type="text/javascript">

        var destTable = jQuery("#jcorgcr-coupons-list");
        jQuery("#jcorgcr-load-more").click(function() {
            Jcorgcr.loadManageCoupons('<?php  echo wp_create_nonce('jcorgcr_nonce_catch')  ?>','<?php  echo plugin_dir_url(__FILE__)  ?>',jcorgcr_coupons_srt);                  
        });
        jQuery("#jcorgcr-search-coupons").click(function() {
            jcorgcr_coupons_pointer = 0;
            Jcorgcr.loadManageCoupons('<?php  echo wp_create_nonce('jcorgcr_nonce_catch')  ?>','<?php  echo plugin_dir_url(__FILE__)  ?>',jcorgcr_coupons_srt,'noappend');                  
        });
        Jcorgcr.loadManageCoupons('<?php  echo wp_create_nonce('jcorgcr_nonce_catch')  ?>','<?php  echo plugin_dir_url(__FILE__)  ?>',jcorgcr_coupons_srt);                  
    </script>
    <?php
    }


    function jcorgcr_import() {                                                                                                                                                                                                                             _jcorgcr_e();  
    ?> 
    <div id="icon-tools" class="icon32"></div><h1 >Jaspreet Chahal's Coupon Importer</h1><br>
    <div class="jcorgcrinfo" style="width:90%"><strong>Please note that this is 3 Step wizard. </strong>
        <ol style="padding-left:20px">
            <li>Upload your CSV File. </li>
            <li>System checks the CSV and if CSV is Ok, it will give you an option to proceed.</li>
            <li>Make sure that you reviewed step 2. ebfore hitting "Start Import" button</li>
        </ol>
    </div>
    <div class="jcorgcrwarning" style="width:90%;margin-top: 10px;">
        <strong>Checklist:</strong><br>
        <ol style="padding-left: 20px;">
            <li>Your CSV is ANSI encoded. When using Microsoft Excel do not save for CSV (Mac)</li>
            <li>Check the order of your columns</li>
            <li>Make sure that your CSV is nor more than 1 MB and 500 lines long. It can take more but that functionality hasn't been tested.</li>
            <li>Make sure that date in CSV are in format YYYY-MM-DD.</li>
            <li>Make sure that data in CSV is in correct format. NO COMMAS in any column value. Read documentation if unsure.</li>
        </ol>
    </div>

    <h1>This option is available in Pro Version</h1>
    <?php      

    }

    function _jcorgcr_e() {
    ?>    
    <style type="text/css"> .jcorgcr_donation_uses li {float:left; margin-left:20px;font-weight: bold;} </style> <div style="padding: 10px; background: #f1f1f1;border:1px #EEE solid; border-radius:15px;width:98%"> <h2>If you like this Plugin, please consider donating Or atleast keep the transparent link with font size 2px just below the coupon. Users will not notice that link.</h2> You can choose your own amount. Developing this awesome plugin took a lot of effort and time; months and months of continuous voluntary unpaid work. If you like this plugin or if you are using it for commercial websites, please consider a donation to the author to help support future updates and development. <div class="jcorgcr_donation_uses"> <span style="font-weight:bold">Main uses of Donations</span><ol ><li>Web Hosting Fees</li><li>Cable Internet Fees</li><li>Time/Value Reimbursement</li><li>Motivation for Continuous Improvements</li></ol> </div> <br class="clear"> <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=MHMQ6E37TYW3N"><img src="https://www.paypalobjects.com/en_AU/i/btn/btn_donateCC_LG.gif" /></a> <br><br><strong>For help please visit </strong><br> <a href="http://jaspreetchahal.org/wordpress-jc-coupon-plugin-lite">http://jaspreetchahal.org/wordpress-jc-coupon-plugin-lite</a> <br><strong><a href="http://jaspreetchahal.org/wordpress-coupon-plugin-jc-coupon-revealer-pro/" style="color:#C00">Consider purchasing Pro Version, Click here to find more</a></strong> <br> </div>

    <?php

    }
    function jcorgcrGetMsg($echo = false,$action="") { 
        /*
        * YOU CAN REMOVE THE LINES BELOW IF YOU DON"T WANT THIS PLUGIN TO CONTACT MY SITE... 
        * 
        */

        global $jcorgcr_plugin_version;
        $isset = $echo?"yes":"no";
        $launch = "http://jaspreetchahal.org/messages.php?isset=$isset&_mas=lite&_ver=".$jcorgcr_plugin_version."&action=".$action;
        $ch = curl_init($launch);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $data = curl_exec($ch);
        curl_close($ch);
        if($echo) {
            echo $data;
        }
}