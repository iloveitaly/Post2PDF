<?php
require_once("../../../wp-config.php");
require_once('config.inc.php');

// wp infos
$home = get_option('home'); //e.g. www.example.com
$siteurl = get_option('siteurl'); //e.g. www.example.com/wordpress - the fisical install directory
$b_name = get_option('blogname');
$adm_email = get_option('admin_email'); 

if(isset($_GET['post'])){
    $layout = 2; //Choices 1 for chapter or 2 for single page layout
    $id = $_GET['post'];
    $post = get_post($id);
    if(!eregi($exclude_tag,$post->post_content)){    

        $auth = get_userdata($post->post_author);
        $p_link = get_permalink($post->ID);
    
    
        if($auth->first_name || $auth->last_name) $name = $auth->first_name." ".$auth->last_name;
        else $name = $auth->user_nicename;
        
        /*Activate this line if you want email notification on download*/    
        //@mail($auth->user_email,"Download pdf ".$post->post_title,"Hello, post ".$post->post_title."\n has been downloaded from ".$p_link); 
        $pdf=new PDF(convert2iso($b_name." - ".$post->post_title), convert2iso($b_name." - ".$home), convert2iso("Post url: ".$p_link." - Copyright ".$name." ".$auth->user_email), convert2iso($name));
        
        /* Chapter page layout */        
        if ($layout == 1) $pdf->create_page(convert2iso($post->post_title."   ".$post->post_date), convert2iso($name." - ".$auth->user_email), convert2iso($post->post_content),true,1);
        /* Single page layout */
        else $pdf->create_page(convert2iso($post->post_title."   ".$post->post_date), convert2iso($name." - ".$auth->user_email), convert2iso($post->post_content));
        
        $pdf->write_out();
    }else{  echo "Error: Unable to export this post."; }
}

if(isset($_GET['category'])){
    $layout = 1; //Choices 1 for chapter or 2 for single page layout
    $myposts = get_posts('numberposts=10000&category='.$_GET['category']);
        
    $pdf=new PDF(convert2iso($b_name."-".$_GET['name']), convert2iso($b_name." - ".$home), convert2iso("Copyright ".$b_name." ".$adm_email), convert2iso($b_name));
    $count = 1; /* Chapter count */
    /* Coversheet page */
    $pdf->create_page(convert2iso($b_name), convert2iso($_GET['name']." archive"),"");
    foreach($myposts as $post){
        if(!eregi($exclude_tag,$post->post_content)){    
            $auth = get_userdata($post->post_author);
            $p_link = get_permalink($post->ID);

            if($auth->first_name || $auth->last_name) $name = $auth->first_name." ".$auth->last_name;
            else $name = $auth->user_nicename;            
            /* Chapter page layout */        
            if ($layout == 1) $pdf->create_page(convert2iso($post->post_title."   ".$post->post_date), convert2iso($name." - ".$auth->user_email), convert2iso($post->post_content),true,$count++);
            /* Single page layout */
            else $pdf->create_page(convert2iso($post->post_title."   ".$post->post_date), convert2iso($name." - ".$auth->user_email), convert2iso($post->post_content));
        }
    }
    $pdf->write_out();
    
    /*Activate this line if you want email notification on download*/    
    //@mail($auth->user_email,"Download category ".$_GET['name'],"Hello, category ".$_GET['name']."\n has been downloaded from ".$url);
}

/*function is_utf8($string) {
   // From http://w3.org/International/questions/qa-forms-utf-8.html
   return preg_match('%^(?:
         [\x09\x0A\x0D\x20-\x7E]            # ASCII
       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
   )*$%xs', $string);
}
*/
function is_utf8($str) {
if(function_exists("mb_detect_encoding"))
{
	if (mb_detect_encoding($str, "auto") == "UTF-8")
		return true;
	else return false;
}else
{
    $c=0; $b=0;
    $bits=0;
    $len=strlen($str);
    for($i=0; $i<$len; $i++){
        $c=ord($str[$i]);
        if($c > 128){
            if(($c >= 254)) return false;
            elseif($c >= 252) $bits=6;
            elseif($c >= 248) $bits=5;
            elseif($c >= 240) $bits=4;
            elseif($c >= 224) $bits=3;
            elseif($c >= 192) $bits=2;
            else return false;
            if(($i+$bits) > $len) return false;
            while($bits > 1){
                $i++;
                $b=ord($str[$i]);
                if($b < 128 || $b > 191) return false;
                $bits--;
            }
        }
    }
    return true;
	}
}
function convert2iso($string) {

    if (is_utf8($string)) {
		return utf8_decode($string);
    } else {
        return $string;
    }
	//return @utf8_decode($string);
}
    
?>
