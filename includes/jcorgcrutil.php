<?php
  class JCORGCRUTIL {
        public static function makeErrorsHtml($errors,$type="error")
        {
            $class="jcorgcrerror";
            $title="Please correct the following errors";
            if($type=="warnings") {
                $class="jcorgcrerror";
                $title="Please review the following Warnings";
            }
            if($type=="warning1") {
                $class="jcorgcrwarning";
                $title="Please review the following Warnings";
            }
            $strCompiledHtmlList = "";
            if(is_array($errors) && count($errors)>0) {
                    $strCompiledHtmlList.="<div class='$class' style='width:90% !important'>
                                            <div class='jcorgcr-errors-title'>$title: </div><ol>";
                    foreach($errors as $error) {
                          $strCompiledHtmlList.="<li>".$error."</li>";
                    }
                    $strCompiledHtmlList.="</ol></div>";
            return $strCompiledHtmlList;
            }
        }
        public static function checkQueryStringNonce() {
            $nonce=$_REQUEST['_wpnonce'];
            if (! wp_verify_nonce($nonce, 'jcorgcr_nonce_catch') ) die("Invalid Request. Refresh your browser screen and try again.");            
        }
  }