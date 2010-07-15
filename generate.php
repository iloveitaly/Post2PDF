<?php
require_once("../../../wp-config.php");
require_once('config.inc.php');
require_once('pdf_class.php');

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
        $postLink = get_permalink($post->ID);
    
        if($auth->first_name || $auth->last_name) {
			$name = $auth->first_name." ".$auth->last_name;
		} else {
			$name = $auth->user_nicename;
		}

        /*Activate this line if you want email notification on download*/    
        //@mail($auth->user_email,"Download pdf ".$post->post_title,"Hello, post ".$post->post_title."\n has been downloaded from ".$p_link); 
        $pdf = new PDF(
			convert2iso($b_name." - ".$post->post_title),
			convert2iso("One time, non-exclusive print usage of this article granted to a single publication by Timeless Pearls Syndicate - ".$home),	// header for each page
			convert2iso("Post url: ".$postLink." - Copyright Timeless Pearls Syndicate"),																// footer for each page
			convert2iso($name)
		);
		
		// do some content processing
		//	* Remove all span tags (mostly contain font declerations which boch up the pdf layout)
		//	* Remove double spaces & double lines that don't matter in HTML but do in the PDF render
		
		$postContent = $post->post_content;
		$postContent = preg_replace('/<\/?span[^>]*>|<\/?div[^>]*>/', '', $postContent);
		$postContent = trim(str_replace(
			array(
				"\n\n",
				"  "
			),
			array(
				"\n",
				" "
			),
			$postContent
		));
		
		$postDate = new DateTime($post->post_date);

		$pdf->create_page(
			convert2iso($post->post_title."   ".$postDate->format("F jS, Y")),
			convert2iso($name." - Timeless Pearls Syndicate"),		// for author email: $auth->user_email
			convert2iso($postContent),
			$layout == 1 ? true : false, $layout == 1 ? 1 : 0
		);
		
        $pdf->write_out();
    } else {
		echo "Error: Unable to export this post.";
	}
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

/*
setlocale(LC_ALL, 'en_US.UTF8');

function clearUTF($s)
{
    $r = '';
    $s1 = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    for ($i = 0; $i < strlen($s1); $i++)
    {
        $ch1 = $s1[$i];
        $ch2 = mb_substr($s, $i, 1);

        $r .= $ch1=='?'?$ch2:$ch1;
    }
    return $r;
}
*/

function is_utf8($str) {
	return mb_detect_encoding($str, "auto") == "UTF-8";
}

function convert2iso($string) {
	// this converts all non-ascii characters to their ascii equivilents
	return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $string);
	
    if (is_utf8($string)) {
		return utf8_decode($string);
    } else {
		return $string;
    }
}
    
?>
