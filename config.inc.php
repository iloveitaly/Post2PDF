<?php 
/* 
    Default action once the plugin was activated:
    1 to enable automatic exportation for all posts except those with the exclude tag
    0 to disable it and let export only post with the export tag placed inside
*/

$default_post_action = 1; 

/* 
    Enable or disable category exportation 
    0 to disable
    1 to enable
*/

$export_category = 1;

$exclude_tag = "<!--post2pdf_exclude-->";
$export_tag = "<!--post2pdf_export-->";
$html_text = "convert this post to pdf.";
$html_post_code = "<span class=\"post2pdf_span\" style=\"border: 1px solid gray; width: 160px; text-align: left; \"><a href=\"##SITEURL##/wp-content/plugins/post2pdf/generate.php?post=##GLOBALID##\" rel=\"nofollow\"><img src=\"##SITEURL##/wp-content/plugins/post2pdf/icon/pdf.png\" width=\"16px\" height=\"16px\" />".$html_text."</a></span>";
$html_category_code = "&nbsp;<a href='##SITEURL##/wp-content/plugins/post2pdf/generate.php?category=##CATEGORYID##&amp;name=##CATEGORYNAME##' rel='nofollow' title='Export category ##CATEGORYNAME## as pdf'>(<img src='##SITEURL##/wp-content/plugins/post2pdf/icon/pdf.png' width='10px' height='10px'/>)</a>";

?>