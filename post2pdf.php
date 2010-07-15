<?php
/*
Plugin Name: post2pdf
Plugin URI: http://www.antonellocicchese.com/post2pdf-wordpress-plugin/
Description: Convert posts and categories to pdf files, use &lt;!&#45;&#45;post2pdf_exclude&#45;&#45;&gt; to exclude the pdf export in a post or in a page.
Version: 0.5
Author: Antonello Cicchese
Author URI: http://www.antonellocicchese.com
Contributors: Andreas Vdovkin, Claudio Iannotta
*/

/*  Copyright 2006  Antonello Cicchese  (email : info@antonellocicchese.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details. You should have received
a copy of the GNU General Public License along with this program;
if not, write to the Free Software Foundation, Inc.,
59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
require_once("pdf_class.php");




function post_to_pdf($content){
 global $wp_query;
    include("config.inc.php");
    if(!eregi($exclude_tag,$content))
    {
       $siteurl = get_option("siteurl");
       $id = $wp_query->post->ID;
       $temp = eregi_replace("##SITEURL##",$siteurl, $html_post_code);        
       $temp = eregi_replace("##GLOBALID##",$id ,$temp);
       $check = eregi($export_tag, $content);
       if(!$default_post_action)
       {
         if($check)
         {
          $content = eregi_replace($export_tag, $temp, $content);   
         }
       }
       else
       {
           if(!$check)
           {
                $content = $content." ".$temp;           
            }
           else
           {
                $content = eregi_replace($export_tag, $temp, $content);
           }
       }
    }
    return $content;   
}

function archive_to_pdf($content, $category=null){
    include("config.inc.php");
    global $wpdb;
    if ($category && $export_category) {
        $siteurl = get_option("siteurl");
        $temp = eregi_replace("##CATEGORYID##", $category->cat_ID, $html_category_code);
        $temp = eregi_replace("##CATEGORYNAME##", $category->cat_name, $temp);
        $temp = eregi_replace("##SITEURL##",$siteurl,$temp);
        return $content." ".$temp;	
    }else return $content;
}


add_filter("the_content", "post_to_pdf");
add_filter("list_cats", "archive_to_pdf",10,2);

?>
