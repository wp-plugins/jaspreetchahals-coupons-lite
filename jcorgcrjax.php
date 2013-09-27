<?php
  if(!defined('WP_PLUGIN_DIR')) {      
    include realpath("../../../")."/wp-config.php";
  }
  include "includes/jcorgcrutil.php";
  session_start();
  //$path_to_includes = preg_replace('/wp-content$/', 'wp-includes', WP_CONTENT_DIR);
  //include ($path_to_includes.'/class-phpmailer.php');
  
  
  $jcorgcrget = (object) $_GET;
  $jcorgcrpost = (object) $_POST;
  global $wpdb;
  
  $errors = array();
  
  switch (strtolower($jcorgcrget->req_type)) {
     case "ac":            
            $wpdb->insert("jcorgrc_cetegories",array("category"=>$jcorgcrpost->category));
       break;
     case "feed":
            JCORGCRUTIL::checkQueryStringNonce();
            
            $yes_or_no = ($_GET['kiddan'] == "chalpiya")?"yeses = (yeses + 1)":"nos = (nos+1) ";
            $coupon_id = intval($_GET['idkiaa']);
            // just a basic check to see if user have already voted
            if(!isset($_SESSION["jcorgcr_already_voted"])) {
                $_SESSION["jcorgcr_already_voted"] = array();
            }
            if(in_array($coupon_id,$_SESSION["jcorgcr_already_voted"]) == FALSE || true) {
                $_SESSION["jcorgcr_already_voted"][]=$coupon_id;
                
                if($coupon_id > 0) {
                    $wpdb->query($wpdb->prepare(" update $wpdb->prefix". "jcorgcr_coupons set $yes_or_no where id = %d",$coupon_id));  
                    $coupon = $wpdb->get_row($wpdb->prepare("select * from $wpdb->prefix" . "jcorgcr_coupons where id=%d",intval($id)));
                    echo "Thank you for your Feedback.";           
                }
            }
            else {
                echo "You've already voted for this Coupon.";die(0);
            }
            
       break;
       
     case "actcp":
            JCORGCRUTIL::checkQueryStringNonce();
            $coupon_id = intval($_GET["i"]);
            $what = $_GET["t"];
            switch ($what) {
               case "reset":
                        if($coupon_id > 0) {
                            $wpdb->query($wpdb->prepare(" update $wpdb->prefix". "jcorgcr_coupons set yeses=0,nos=0 where id = %d",$coupon_id));  
                            echo "Yes's: 0<br>No's: 0";
                            die(0);
                        }                    
                 break;
               case "kill":
                        if($coupon_id > 0) {
                            $wpdb->query($wpdb->prepare(" delete from $wpdb->prefix". "jcorgcr_coupons where id = %d",$coupon_id));  
                            die(0);
                        }                    
                 break;
            }
       break;
     case "mcp":
            JCORGCRUTIL::checkQueryStringNonce();
           $start = intval($_GET["s"]);
           $limit = 20;
           $page = $start * $limit;
           $category_incl = "";
           if(intval($_GET["category_id"]) > 0) {
              $category_incl = $wpdb->prepare(" where category_id = %d",intval($_GET["category_id"])); 
           }
           $coupons = $wpdb->get_results("select * from $wpdb->prefix". "jcorgcr_coupons $category_incl  ORDER BY id DESC LIMIT $page,$limit");
           $nonce = wp_create_nonce('jcorgcr_nonce_catch');
           $ppath = plugin_dir_url(__FILE__);
           if(count($coupons) > 0) {
               foreach ($coupons as $coupon) {
                    echo "<tr id='jcorgcr-coupon-row-$coupon->id'>
                            <td>".stripcslashes($coupon->name)."</td>
                            <td>".stripcslashes($coupon->coupon_theme)."</td>
                            <td>".stripcslashes($coupon->coupon)."</td>
                            <td>".stripcslashes($coupon->title)."</td>
                            <td>".stripcslashes($coupon->savings)."</td>
                            <td>".(($coupon->expiry_type=="Date")?date("m/d/Y",$coupon->expiry):$coupon->expiry)."</td>
                            <td>".stripcslashes($coupon->coupon_type)."</td>
                            <td>".stripcslashes($coupon->shortcode)."</td>
                            <td><span id='jcorgcr-coupon-yn-$coupon->id'>Yes's: ".stripcslashes($coupon->yeses)."<br>No's: ".stripcslashes($coupon->nos)."</span></td>
                            <td>
                            <a href='javascript:void(0)' class='button ' style='margin:2px !important;float:left' onclick=\"Jcorgcr.actCP('$ppath','reset','$coupon->id','$nonce')\">Reset Yes/No</a> 
                            <a href='?page=jaspreetchahals-coupons-lite/jaspreetchahals-coupons-lite.php_add&mod=$coupon->id&_wponce=$nonce' class='button ' style='margin:2px !important;float:left'>Modify</a> 
                            <a href='javascript:void(0)' class='button ' style='margin:2px !important;float:left' onclick=\"Jcorgcr.actCP('$ppath','clone','$coupon->id','$nonce')\">Duplicate</a> 
                            <a href='javascript:void(0)' class='button ' style='margin:2px !important;float:left' onclick=\"Jcorgcr.actCP('$ppath','kill','$coupon->id','$nonce')\">Delete</a> 
                            </td>                      
                        </tr>";
               }
           }
           else {
               echo "<tr><td colspan='10' style='padding:15px; border:1px #CC0000 solid;background: #FFCACA; font-size:16px; font-size:bold;color:#CC0000'>That's all you got in your JC coupon store. No records found!</td></tr>";
           } 
       break;
     case "acp":
            check_admin_referer('jcorgcr_nonce_catch');
            $wp_error = new WP_Error();
            
            if(count($_POST) > 0) {
               if(strlen(trim($jcorgcrpost->expiry))==0) 
                   $wp_error->add('jcorgcr_add_coupon',"Required field Expiry Date/Text is missing.");
               if(strlen(trim($jcorgcrpost->coupon))==0) 
                   $wp_error->add('jcorgcr_add_coupon',"Required field 'Coupon Code' is missing.");
               if(strlen(trim($jcorgcrpost->name))==0) 
                   $wp_error->add('jcorgcr_add_coupon',"Required field 'Coupon Name' is missing.");
               if(strlen(trim($jcorgcrpost->savings_text))==0) 
                   $wp_error->add('jcorgcr_add_coupon',"Required field 'Savings Text' is missing.");               
               if(strlen(trim($jcorgcrpost->destination_url))==0) 
                   $wp_error->add('jcorgcr_add_coupon',"Required field 'Destination URL' is missing.");
               $errors = $wp_error->get_error_messages('jcorgcr_add_coupon');
               if(count($errors) == 0) {
                   //$sql = $wpdb->prepare();
                   global $user_ID;
                   if(isset($jcorgcrpost->coupon_id) && intval($jcorgcrpost->coupon_id) <=0) {
                        $success = $wpdb->insert($wpdb->prefix . "jcorgcr_coupons",array(
                                                                        "coupon_type"=>strip_tags($jcorgcrpost->coupon_type),
                                                                        "coupon"=>strip_tags($jcorgcrpost->coupon),
                                                                        "coupon_layout"=>strip_tags($jcorgcrpost->coupon_layout),
                                                                        "coupon_theme"=>strip_tags($jcorgcrpost->coupon_theme),
                                                                        "category_id"=>strip_tags($jcorgcrpost->category_id),
                                                                        "description"=>strip_tags($jcorgcrpost->description),
                                                                        "destination_url"=>strip_tags($jcorgcrpost->destination_url),
                                                                        "expiry_type"=>strip_tags($jcorgcrpost->expiry_type),
                                                                        "savings"=>strip_tags($jcorgcrpost->savings_text),
                                                                        "notification_email"=>strip_tags($jcorgcrpost->notification_email),
                                                                        "expiry"=>(($jcorgcrpost->expiry_type=="Date")?strtotime(str_replace("/","-",$jcorgcrpost->expiry),time()):strip_tags($jcorgcrpost->expiry)),
                                                                        "yeses"=>0,
                                                                        "nos"=>0,
                                                                        "name"=>strip_tags($jcorgcrpost->name),
                                                                        "title"=>strip_tags($jcorgcrpost->title),
                                                                        "number_of_votes"=>strip_tags($jcorgcrpost->number_of_votes),
                                                                        "width"=>strip_tags($jcorgcrpost->width),
                                                                        "height"=>strip_tags($jcorgcrpost->height),
                                                                        "display_tse"=>isset($jcorgcrpost->display_tse)?"Yes":"No",
                                                                        "save_background"=>strip_tags($jcorgcrpost->savings_background),
                                                                        "shortcode"=>"[jcorgcrcoupon slug='' id='']",
                                                                        "created_on"=>time(),
                                                                        "last_modified_on"=>time(),
                                                                        "imported_on"=>0,
                                                                        "admin_id"=>$user_ID

                                                                        ));
                   }
                   else if(intval($jcorgcrpost->coupon_id) > 0) {
                       $success = $wpdb->update($wpdb->prefix . "jcorgcr_coupons",array(
                                                                        "coupon_type"=>strip_tags($jcorgcrpost->coupon_type),
                                                                        "coupon"=>strip_tags($jcorgcrpost->coupon),
                                                                        "coupon_layout"=>strip_tags($jcorgcrpost->coupon_layout),
                                                                        "coupon_theme"=>strip_tags($jcorgcrpost->coupon_theme),
                                                                        "category_id"=>strip_tags($jcorgcrpost->category_id),
                                                                        "description"=>strip_tags($jcorgcrpost->description),
                                                                        "destination_url"=>strip_tags($jcorgcrpost->destination_url),
                                                                        "expiry_type"=>strip_tags($jcorgcrpost->expiry_type),
                                                                        "savings"=>strip_tags($jcorgcrpost->savings_text),
                                                                        "display_tse"=>isset($jcorgcrpost->display_tse)?"Yes":"No",
                                                                        "notification_email"=>strip_tags($jcorgcrpost->notification_email),
                                                                        "expiry"=>(($jcorgcrpost->expiry_type=="Date")?strtotime(str_replace("/","-",$jcorgcrpost->expiry),time()):strip_tags($jcorgcrpost->expiry)),
                                                                        "yeses"=>0,
                                                                        "nos"=>0,
                                                                        "name"=>strip_tags($jcorgcrpost->name),
                                                                        "title"=>strip_tags($jcorgcrpost->title),
                                                                        "number_of_votes"=>strip_tags($jcorgcrpost->number_of_votes),
                                                                        "width"=>strip_tags($jcorgcrpost->width),
                                                                        "height"=>strip_tags($jcorgcrpost->height),
                                                                        "save_background"=>strip_tags($jcorgcrpost->savings_background),
                                                                        "last_modified_on"=>time()                                                                        
                                                                        ),array("id"=>intval($jcorgcrpost->coupon_id)));
                   }
                   if($success !== false){
                       $insert_id = (intval($jcorgcrpost->coupon_id) > 0)?intval($jcorgcrpost->coupon_id):$wpdb->insert_id;
                       if($insert_id > 0) {
                           $wpdb->update($wpdb->prefix . "jcorgcr_coupons",array("shortcode"=>"[jcorgcrcoupon slug='".preg_replace("/\W/","",$jcorgcrpost->name)."' id='$insert_id']"),array("id"=>$insert_id));
                       }
                       echo "<div class='jcorgcrsuccess'>Action Successful. <br><br><strong>Shortcode:</strong> [jcorgcrcoupon slug='".preg_replace("/\W/","",$jcorgcrpost->name)."' id='$insert_id']<br><br><a href='?page=jaspreetchahals-coupons-lite/jaspreetchahals-coupons-lite.php_manage'>Manage Coupons</a> |  <a href='?page=jaspreetchahals-coupons-lite/jaspreetchahals-coupons-lite.php_add'>Add Another Coupon</a></div>";
                   }
               }
            }  
       break; 
       case "categorydelkillfinal":
            JCORGCRUTIL::checkQueryStringNonce();
            $old_id = intval($_POST["cid"]);
            $new_id = intval($_POST["jcorgcr_categories"]);
            if($old_id != $new_id && $new_id > 0) {
                $query = $wpdb->prepare( 
                        "DELETE FROM ".$wpdb->prefix."jcorgcr_categories
                         WHERE id = %d
                        "
                    ,$old_id );
                $wpdb->query( 
                    $query
                );
                try{$wpdb->update($wpdb->prefix."jcorgcr_coupons",array("category_id"=>$new_id),array("category_id"=>$old_id));}catch(Exception $e){}
                
            }
            _e("<div class='jcorgcrsuccess'>Category Successfully Deleted</div>");
       break;
       case "killcatpre":
            JCORGCRUTIL::checkQueryStringNonce();
            // check if this category is linked to any coupons
           $already_linked = $wpdb->get_var($wpdb->prepare("select count(*) from ".$wpdb->prefix."jcorgcr_coupons where category_id=%d",intval($_GET["id"])));
           if($already_linked >= 0) {
       ?> 
       <h4>This category is linked to few coupons. Please select New Category for those Coupons below</h4>
       <div id="jcorgcr-category-delete-msg"></div>
       <form id="jcorgcr-category-delete" onsubmit="return false">
            <table class="form-table" style="width:350px">
                <tr valign="top">
                <th scope="row">Category Name</th>
                <td><select name="jcorgcr_categories">
                <?php 
                        
                        $jcorgcr_categories = $wpdb->get_results("select * from ".$wpdb->prefix."jcorgcr_categories");
                        foreach ($jcorgcr_categories as $row) {
                            if($row->id != intval($_GET["id"])) {
                            ?> 
                            <option value="<?php _e($row->id)?>" <?php echo $selected ?>><?php _e($row->category)?></option>
                            
                            <?php
                            }
                        }
                            ?>    
                </select>
                <input type="hidden" name="cid" value="<?php _e(intval($_GET["id"]))?>">
                </td>
                </tr>
                </table>
                <p class="submit">
                <input type="button" class="button-primary"
                value="Assign new Category and Delete" onclick="Jcorgcr.categoryDelete('jcorgcr-category-delete','<?php  echo plugin_dir_url(__FILE__)  ?>','<?php echo wp_create_nonce('jcorgcr_nonce_catch')?>')"/>
            </p>
            </form>
       <?php
           }
           else {
               $wpdb->query( 
                        $wpdb->prepare( 
                            "DELETE FROM ".$wpdb->prefix."jcorgcr_categories
                             WHERE id = %d
                            "
                        ,$_GET["id"] )
                    );
                    _e("<div class='jcorgcrsuccess'>Category Successfully Deleted</div>");
           }
       break;
     case "categoryselect":
        ?> 
        
        <select name="jcorgcr_categories">
        <?php 
                
                $jcorgcr_categories = $wpdb->get_results("select * from ".$wpdb->prefix."jcorgcr_categories");
                foreach ($jcorgcr_categories as $row) {
                    ?> 
                    <option value="<?php _e($row->id)?>"><?php _e($row->category)?></option>
                    
                    <?php
                    
                }
                    ?>    
        </select>
        
        <?php
        
     break;  
     case "categoryupdate":
      check_admin_referer('jcorgcr_nonce_catch');
       if(strlen($_POST["category"]) == 0  || intval($_POST["cid"]) ==0) {
           $errors[] = "Required field 'Category' is left empty.";
       }
       else {
            $wpdb->update($wpdb->prefix."jcorgcr_categories",array("category"=>$_POST["category"]),array("id"=>intval($_POST["cid"])));
            echo "<div class='jcorgcrsuccess'>Category Successfully updated</div>";
       }
       break;
     case "categoryadd":
        // nonce is posted 
       check_admin_referer('jcorgcr_nonce_catch');
       if(strlen($_POST["category"]) == 0) {
           $errors[] = "Required field 'Category' is left empty.";
       }
       else {
            $wpdb->insert($wpdb->prefix."jcorgcr_categories",array("category"=>$_POST["category"],"created_on"=>time()));
            echo "<div class='jcorgcrsuccess'>Category Successfully added</div>";
       }
       break;
     case "categoryupdatemarkup":
            JCORGCRUTIL::checkQueryStringNonce();
            $category_jcorg = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."jcorgcr_categories where id = %d",intval($_GET["id"])));
            ?>    
            <div id="jcorgcr-category-update-msg">
            </div>
            <form id="jcorgcr-category-update" onsubmit="return false">
            <?php
                 if(function_exists('wp_nonce_field')) wp_nonce_field("jcorgcr_nonce_catch");
             ?>
            <table class="form-table" style="width:350px">
                <tr valign="top">
                <th scope="row">Category Name</th>
                <td><input type="text" name="category"
                value="<?php echo $category_jcorg->category?>"  size="30" maxlength="80"/>
                <input type="hidden" name="cid" value="<?php _e(intval($_GET["id"]))?>">
                </td>
                </tr>
                </table>
                <p class="submit">
                <input type="button" class="button-primary"
                value="Update Category" onclick="Jcorgcr.categoryUpdate('jcorgcr-category-update','<?php  echo plugin_dir_url(__FILE__)  ?>','<?php echo wp_create_nonce('jcorgcr_nonce_catch')?>')"/>
            </p>
            </form>
            <?php
     break;
     case "categories":
            JCORGCRUTIL::checkQueryStringNonce();
            ?>   
            <div id="jcorgcr-dlg" title="">
                <div id="jcorgcr-dlgload">
                </div>
            </div> 
            <div class="jcorgcr-table" style="border:4px #e1e1e1 solid; border-radius: 10px;">
                <div class="jcorgcr-table-row " >
                    <div class="jcorgcr-table-cell jcorgcr-table-cellspan-3 jcorgcr-table-head">Category ID</div>
                    <div class="jcorgcr-table-cell jcorgcr-table-cellspan-6 jcorgcr-table-head">Category</div>
                    <div class="jcorgcr-table-cell jcorgcr-table-cellspan-4 jcorgcr-table-head">Actions</div>
                    <div class="jcorgcr-table-row-clear"></div>
                </div>
                <?php 
                
                $jcorgcr_categories = $wpdb->get_results("select * from ".$wpdb->prefix."jcorgcr_categories");
                foreach ($jcorgcr_categories as $row) {
                    ?>    
                    <div class="jcorgcr-table-row " >
                        <div class="jcorgcr-table-cell jcorgcr-table-cellspan-3 jcorgcr-table-head"><?php _e($row->id)?></div>
                        <div class="jcorgcr-table-cell jcorgcr-table-cellspan-6 jcorgcr-table-head" id="jcorgcrcat_<?php _e($row->id)?>"><?php _e($row->category)?></div>
                        <div class="jcorgcr-table-cell jcorgcr-table-cellspan-4 jcorgcr-table-head">
                        <a href="javascript:void(0)" style="font-weight: bold;" onclick="Jcorgcr.loadUpdate('<?php _e($row->id)?>','<?php  echo plugin_dir_url(__FILE__)  ?>','<?php     if ( function_exists('wp_nonce_field') ) _e(wp_create_nonce('jcorgcr_nonce_catch'));    ?>')">Update</a> &nbsp;
                        <?php if($row->id > 1) {
                            // default category should not be deleted
                            ?>
                        <a href="javascript:void(0)" style="font-weight: bold;"  onclick="Jcorgcr.loadDelete('<?php _e($row->id)?>','<?php  echo plugin_dir_url(__FILE__)  ?>','<?php     if ( function_exists('wp_nonce_field') ) _e(wp_create_nonce('jcorgcr_nonce_catch'));    ?>')">Delete</a> 
                        <?php } ?>
                        </div>
                        <div class="jcorgcr-table-row-clear"></div>
                    </div>
                    <?php                
                }
                
                ?>
                <div class="jcorgcr-table-row-clear"></div>
            </div>
            
            <?php
       break;  
  }
  
  if(count($errors) > 0) {
      echo JCORGCRUTIL::makeErrorsHtml($errors);
  }