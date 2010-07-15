<?php
define('p2pdf_path',dirname(__FILE__).'/');
define('FPDF_FONTPATH',p2pdf_path.'fpdf/font/');
require(p2pdf_path.'fpdf/fpdf.php');
require(p2pdf_path.'htmlparser.inc');

//conversion pixel -> millimeter in 72 dpi
function px2mm($px){
    return $px*25.4/72;
}

function hex2dec($color)
{
    $red   = 100;
    $green = 100;
    $blue  = 100;
    $col=array();
    if( eregi( "[#]?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})", $color, $ret ) )
    {
        $red = hexdec( $ret[1] );
        $green = hexdec( $ret[2] );
        $blue = hexdec( $ret[3] );
    }
    $col['R']=$red;
    $col['G']=$green;
    $col['B']=$blue;
    return($col);
}

function real_path($url){
    $url_array = split("/",$url);
    $new_path = $_SERVER["DOCUMENT_ROOT"];
    if($url_array[0]="http" && ($url_array[2] == $_SERVER['HTTP_HOST'])){
        for($i=3; $i<count($url_array); $i++){
            $new_path .= "/".$url_array[$i];
        }
    }else $new_path = false;
    return $new_path;
}

class PDF extends FPDF
{
    var $titolo;
    var $author;
    var $header_text;
    var $footer_text;
    var $to_file;
    var $chapt=array();
    var $pages=array();
    var $debug = false;
    var $B=0;
    var $I=0;
    var $U=0;
    var $HREF='';
    var $PRE=false;

    function PDF($titolo, $header, $footer, $author){
        $this->titolo = $titolo;
        $this->author = $author;
        $this->header_text  = $header;
        $this->footer_text = $footer;
        $this->SetCreator("Script by Antonello Cicchese, http://www.antonellocicchese.com");
        $this->SetDisplayMode('real');
        $this->SetTitle($this->titolo);
        $this->SetAuthor($this->author);
        $this->FPDF("P","mm","A4");
        $this->CurOrientation = "P";
    }

    function Header(){
        $this->SetFont('Arial','B',8);
        //Text color in gray
        $this->SetTextColor(128);
        $this->Cell(0,10,$this->header_text,1,1,'C');
        $this->Ln(10);
    }

    function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(128);
        $this->Cell(0,10,$this->footer_text.' - Page '.$this->PageNo(),1,0,'C');
    }

    function _page_title($titolo, $sub=""){
        $this->SetFont('Arial','B',20);
        $w=$this->GetStringWidth($titolo)+6;        
        $this->SetX((210-$w)/2);
        $this->SetTextColor(0,0,0);
        $this->Cell($w,9,$titolo,"B",1,'C');        
        $this->Ln(10);
        if($sub){
            $w2= $this->GetStringWidth($sub)+6;
            $this->SetX((210-$w2)/2);
            $this->SetFont('Arial','I',15);
            $this->MultiCell($w2,9,$this->_clear($sub),0,"C",0);
            $this->Ln(10);
        }
    }

    function _clear($text)
    {
        $text = nl2br($text);
        $text = str_replace("&egrave;", "e'", $text);
        $text = str_replace("&agrave;", "a'", $text);
        $text = str_replace("&igrave;", "i'", $text);
        $text = str_replace("&ugrave;", "u'", $text);
        $text = str_replace("&ograve;", "o'", $text);
        $text = str_replace("&trade;",'™',$text);
        $text = str_replace("&copy;",'©',$text);
        $text = str_replace("&euro;",'€',$text);
        return $text;
    }

    function ChapterTitle($num,$label)
    {
        $this->SetFont('Arial','',12);
        $this->SetFillColor(200,220,255);
        $this->Cell(0,6,"Chapter $num : $label",0,1,'L',1);
        $this->Ln(4);
    }

    function ChapterBody($txt)
    {
        $this->SetFont('Times','',12);
        //$this->MultiCell(0,5,$txt,0,"J",0);
	
		$this->WriteHTML(stripslashes($txt));
        $this->Ln();
        $this->SetFont('','I');
        $this->Cell(0,5,'');
    }

    function create_page($title="", $sub_title="", $content="", $is_chapter=false, $ch_num=0){
        $this->AddPage();
        if(!$is_chapter){
            $this->_page_title($title,$sub_title);
            $this->SetFont('Times','',12);
            $this->SetLeftMargin(20);
            $this->SetRightMargin(20);
            //$this->MultiCell(0,5,$this->_clear($content),0,"J",0);
			
            $this->WriteHTML(stripslashes($this->_clear($content)));
            }else{
                //$this->_page_title($title,$sub_title);
                $this->SetFont('Times','',12);
                $this->SetLeftMargin(20);
                $this->SetRightMargin(20);            
                $this->ChapterTitle($ch_num,$title);
				
                $this->ChapterBody($this->_clear($content));
            }
            $this->SetAutoPageBreak(true, 20);
        }

        function write_out($mode=""){
            if(!$mode) $this->Output($this->titolo.".pdf",'D');    
            else $this->Output($this->titolo.".pdf",$mode);
        }

        function _txtentities($html){
            $trans = get_html_translation_table(HTML_ENTITIES);
            $trans = array_flip($trans);
            return strtr($html, $trans);
        }
        function mySetTextColor($r,$g=0,$b=0){
            static $_r=0, $_g=0, $_b=0;
            if ($r==-1) 
                $this->SetTextColor($_r,$_g,$_b);
            else {
                  $this->SetTextColor($r,$g,$b);
                  $_r=$r;
                  $_g=$g;
                  $_b=$b;
            }
        }
        function WriteHTML2($html)
        {
            //HTML parser
            $html=str_replace("\n",' ',$html);
            $a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
            foreach($a as $i=>$e)
            {
                if($i%2==0)
                {
                    //Text
                    if($this->HREF)
                        $this->PutLink($this->HREF,$e);
                    else
                        $this->Write(5,$e);
                }
                else
                {
                    //Tag
                    if($e{0}=='/')
                        $this->CloseTag(strtoupper(substr($e,1)));
                    else
                    {
                        //Extract attributes
                        $a2=explode(' ',$e);
                        $tag=strtoupper(array_shift($a2));
                        $attr=array();
                        foreach($a2 as $v)
                            if(ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3))
                                $attr[strtoupper($a3[1])]=$a3[2];
                        $this->OpenTag($tag,$attr);
                    }
                }
            }
        }

        function OpenTag($tag,$attr)
        {
            //Opening tag
            if($tag=='B' or $tag=='I' or $tag=='U')
                $this->SetStyle($tag,true);
            if($tag=='A')
                $this->HREF=$attr['HREF'];
            if($tag=='BR')
                $this->Ln(2);
            if($tag=='P')
                $this->Ln(5);
            if($tag=='H1'){
                $this->Ln(5);
                $this->SetTextColor(150,0,0);
                $this->SetFontSize(22);
            }
            if($tag=='H2'){
                $this->Ln(5);
                $this->SetFontSize(18);
                $this->SetStyle('U',true);
             }
             if($tag=='H3'){
                $this->Ln(5);
                $this->SetFontSize(16);
                $this->SetStyle('U',true);
            }
            if($tag=='H4'){
                $this->Ln(5);
                $this->SetTextColor(102,0,0);
                $this->SetFontSize(14);
                if ($this->bi)
                $this->SetStyle('B',true);
            }
            if($tag =="PRE"){
                $this->SetFont('Courier','',11);
                $this->SetFontSize(11);
                $this->SetStyle('B',false);
                $this->SetStyle('I',false);
                $this->PRE=true;
            }
            if($tag =="CODE"){
                $this->SetFont('Courier','',11);
                $this->SetFontSize(11);
                $this->SetStyle('B',false);
                $this->SetStyle('I',false);
                $this->PRE=true;
            }
            if($tag =="BLOCKQUOTE"){
                $this->mySetTextColor(100,0,45);
                $this->Ln(3);
            }
            if($tag =="EM")  
                $this->SetStyle('I',true);
            if($tag =="IMG"){
                if(isset($attr['SRC']) ) {
                    $temp = real_path($attr['SRC']);                    
                    $this->SetX($this->GetX()+5);
                    $ext = substr(strrchr($attr['SRC'], "."), 1);              
               
                    if(($ext == 'jpg') or ($ext == 'jpeg') or ($ext == 'png')){
                            if(!@$this->Image($attr['SRC'], $this->GetX(), $this->GetY())){
                                if($temp) $attr['SRC']=$temp;
                                @$this->Image($attr['SRC'], $this->GetX(), $this->GetY());
                            }
                    }
                    if(!isset($attr['WIDTH']) or !isset($attr['HEIGHT'])){
                        $vett = @getimagesize($attr['SRC']);
                        $width = px2mm($vett[0]);
                        $height = px2mm($vett[1]);
                    }
                    else {
                        $width = px2mm($attr['WIDTH']);
                        $height = px2mm($attr['HEIGHT']);
                    }
                    $this->SetX($this->GetX()+$width);
                    $this->SetY($this->GetY()+$height);
                    //$this->Ln(px2mm($height)+2);
                }
            }
            if($tag =="UL")
                $this->Ln(5);
            if($tag =="LI"){
                $this->Ln(2);
                $this->SetTextColor(190,0,0);
                $this->Write(5,'    -- ');
                $this->mySetTextColor(-1);
            }
            if($tag =="BR")
                $this->Ln(2);
            if($tag =="HR")
                $this->PutLine();
            if($tag =="FONT"){
                if (isset($attr['COLOR']) and $attr['COLOR']!='') {
                    $coul=hex2dec($attr['COLOR']);
                    $this->mySetTextColor($coul['R'],$coul['G'],$coul['B']);
                    $this->issetcolor=true;
                }
                /* TODO: Correct bug */
                if (isset($attr['FACE']) and in_array(strtolower($attr['FACE']), $this->fontlist)) {
                    $this->SetFont(strtolower($attr['FACE']));
                    $this->issetfont=true;
                }
            }
        }

        function CloseTag($tag)
        {
            //Closing tag
            if($tag=='B' or $tag=='I' or $tag=='U')
                $this->SetStyle($tag,false);
            if($tag=='A')
                $this->HREF='';
            if($tag=='P')
                $this->Ln(5);
            if ($tag='H1' || $tag='H2' || $tag='H3' || $tag='H4'){
                $this->Ln(6);
                $this->SetFont('Times','',12);
                $this->SetFontSize(12);
                $this->SetStyle('U',false);
                $this->SetStyle('B',false);
                $this->mySetTextColor(-1);
            }
            if ($tag='PRE'){
                $this->SetFont('Times','',12);
                $this->SetFontSize(12);
                $this->PRE=false;
            }
            if($tag='CODE'){
                $this->SetFont('Times','',12);
                $this->SetFontSize(12);
                $this->PRE=false;
            }
            if ($tag='BLOCKQUOTE'){
                $this->mySetTextColor(0,0,0);
                $this->Ln(3);
            }
            if($tag=='UL')
                $this->Ln(5);
            if($tag=='FONT'){
                if ($this->issetcolor==true) {
                    $this->SetTextColor(0,0,0);
                }
                if ($this->issetfont) {
                    $this->SetFont('Times','',12);
                    $this->issetfont=false;
                }
            }
        }

        function SetStyle($tag,$enable)
        {
            //Modify style and select corresponding font
            $this->$tag+=($enable ? 1 : -1);
            $style='';
            foreach(array('B','I','U') as $s)
                if($this->$s>0)
                    $style.=$s;
            $this->SetFont('',$style);
        }

        function PutLink($URL,$txt)
        {
            //Put a hyperlink
            $this->SetTextColor(0,0,255);
            $this->SetStyle('U',true);
            $this->Write(5,$txt,$URL);
            $this->SetStyle('U',false);
            $this->SetTextColor(0);
        }

        function WriteTable($data,$w)
        {
            $this->SetLineWidth(.3);
            $this->SetFillColor(255,255,255);
            $this->SetTextColor(0);
            $this->SetFont('');
            foreach($data as $row)
            {
                $nb=0;
                for($i=0;$i<count($row);$i++)
                    $nb=max($nb,$this->NbLines($w[$i],trim($row[$i])));
                $h=5*$nb;
                $this->CheckPageBreak($h);
                for($i=0;$i<count($row);$i++)
                {
                    $x=$this->GetX();
                    $y=$this->GetY();
                    $this->Rect($x,$y,$w[$i],$h);
                    $this->MultiCell($w[$i],5,trim($row[$i]),0,'C');
                    //Put the position to the right of the cell
                    $this->SetXY($x+$w[$i],$y);                    
                }
                $this->Ln($h);

            }
        }

        function NbLines($w,$txt)
        {
            //Computes the number of lines a MultiCell of width w will take
            $cw=&$this->CurrentFont['cw'];
            if($w==0)
                $w=$this->w-$this->rMargin-$this->x;
            $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            $s=str_replace("\r",'',$txt);
            $nb=strlen($s);
            if($nb>0 and $s[$nb-1]=="\n")
                $nb--;
            $sep=-1;
            $i=0;
            $j=0;
            $l=0;
            $nl=1;
            while($i<$nb)
            {
                $c=$s[$i];
                if($c=="\n")
                {
                    $i++;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                    continue;
                }
                if($c==' ')
                    $sep=$i;
                $l+=$cw[$c];
                if($l>$wmax)
                {
                    if($sep==-1)
                    {
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i=$sep+1;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }

        function CheckPageBreak($h)
        {
            //If the height h would cause an overflow, add a new page immediately
            if($this->GetY()+$h>$this->PageBreakTrigger)
                $this->AddPage($this->CurOrientation);
        }

        function ReplaceHTML($html)
        {
            $html = str_replace( '<li>', "\n<br> - " , $html );
            $html = str_replace( '<LI>', "\n - " , $html );
            $html = str_replace( '</ul>', "\n\n" , $html );
            $html = str_replace( '<strong>', "<b>" , $html );
            $html = str_replace( '</strong>', "</b>" , $html );
            $html = str_replace( '&#160;', "\n" , $html );
            $html = str_replace( '&nbsp;', " " , $html );
            $html = str_replace( '&quot;', "\"" , $html );
            $html = str_replace( '&#39;', "'" , $html );
            return $html;
        }

        function ParseTable($Table)
        {
            $_var='';
            $htmlText = $Table;
            $parser = new HtmlParser ($htmlText);
            while ($parser->parse()) {
                if(strtolower($parser->iNodeName)=='table')
                {
                    if($parser->iNodeType == NODE_TYPE_ENDELEMENT)
                        $_var .='/::';
                    else
                        $_var .='::';
                }

                if(strtolower($parser->iNodeName)=='tr')
                {
                    if($parser->iNodeType == NODE_TYPE_ENDELEMENT)
                        $_var .='!-:'; //opening row
                    else
                        $_var .=':-!'; //closing row
                }
                if(strtolower($parser->iNodeName)=='td' && $parser->iNodeType == NODE_TYPE_ENDELEMENT)
                {
                    $_var .='#,#';
                }
                if ($parser->iNodeName=='Text' && isset($parser->iNodeValue))
                {
                    $_var .= $parser->iNodeValue;
                }
            }
            $elems = split(':-!',str_replace('/','',str_replace('::','',str_replace('!-:','',$_var)))); //opening row
            foreach($elems as $key=>$value)
            {
                if(trim($value)!='')
                {
                    $elems2 = split('#,#',$value);
                    array_pop($elems2);
                    $data[] = $elems2;
                }
            }
            return $data;
        }

        function WriteHTML($html)
        {
            $html = $this->ReplaceHTML($html);
            //Search for a table
            $start = strpos(strtolower($html),'<table');
            $end = strpos(strtolower($html),'</table');
            if($start!==false && $end!==false)
            {
                $this->WriteHTML2(substr($html,0,$start).'<BR>');

                $tableVar = substr($html,$start,$end-$start);
                $tableData = $this->ParseTable($tableVar);
                for($i=1;$i<=count($tableData[0]);$i++)
                {
                    if($this->CurOrientation=='L')
                        $w[] = abs(($this->GetStringWidth($tableData[0])+10)/(count($tableData[0])-1))+24;
                    else
                        $w[] = abs(($this->GetStringWidth($tableData[0])+10)/(count($tableData[0])-1))+5;
                }
                $this->WriteTable($tableData,$w);

                $this->WriteHTML2(substr($html,$end+8,strlen($html)-1).'<BR>');
            }
            else
            {
                $this->WriteHTML2($html);
            }
        }
        
    }

 function Test_PDF_Class(){
     $pdf=new PDF('AntonelloCicchese.com',"AntonelloCicchese.com","Copyright Antonello Cicchese info@antonellocicchese.com", "Antonello Cicchese");
     $pdf->create_page("TITOLO PRINCIPALE","Un testo prodotto da
     Antonello Cicchese
     versione 1.0");
     $pdf->create_page("Capitolo 1","","<strong>Testo della seconda</strong> pagina");
     $pdf->create_page("Capitolo 1","","<strong>Testo del 1</strong> capitoletto",true,1);
     $pdf->create_page("Capitolo 2","","Testo del 2 capitoletto",true,2);
     $pdf->create_page("Capitolo 3","","Testo del 3 capitoletto",true,3);
     $pdf->create_page("Capitolo 4","","Testo del 4 capitoletto",true,4);
     $pdf->create_page("Capitolo 5","","Testo del 5 capitoletto",true,5);
     $pdf->create_page("Conclusioni","Le conclusioni sono importanti!","Traggo ora le mie conclusioni");
     $pdf->write_out();
 }
?>