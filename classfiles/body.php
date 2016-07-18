<?php

error_reporting(E_ALL & ~E_NOTICE);

require_once("articlemeta.php");

class Body extends GetArticleMeta{
	 
	 private $latex_filepath;
	 private $existed_xml_path;
	 private $xmlfile;
	 private $doi;
	 private $fdoi;
	 
	  function __construct($latex,$xml,$doi,$fdoi){
      $this->latex_filepath = $latex;
      $this->existed_xml_path=$xml;
      $this->doi=$doi;
      $this->fdoi=$fdoi;
      }
	
	  function getBody(){	    
	    
	    $cnt=$this->checkForRegex("end{frontmatter}","%back-start",file_get_contents($this->latex_filepath));
	    			
			//BEFORE CONVERTING TO XML********************************************************
			
			//replace string
			$cnt=str_replace("\\newpage","",$cnt);
			$cnt=str_replace("\\hline","",$cnt);
			$cnt=str_replace("enumerate*","enumerate",$cnt);
			$cnt=str_replace("itemize*","itemize",$cnt);
			$cnt=str_replace("\\cite{","**citeref{",$cnt);
			$cnt=str_replace("\\ref{","**refref{",$cnt);
			$cnt=str_replace("\\label{","**labelref{",$cnt);
			
					
			//changing label to outside of equation (\begin{equation} \label{})
			$cnt=preg_replace_callback("/(\\\\begin{equation})\\s*\\*\\*labelref{(.*?)}/is",function($m){return "**labeleqnref{".$m[2]."}\n".$m[1];},$cnt);	
			
			//figure pre manipulation
			$cnt=preg_replace_callback("/\\\\begin{figure}.*?(includegraphics.*?)caption{(.*?)}.*?end{figure}/s",function($m){
				$figcount=substr_count($m[1],"includegraphics");
				return "[figcap]".$m[2]."[/figcap($figcount)]";},$cnt);	
				
			//table pre manipulation
			$cnt=$this->tablePreProcess($cnt);	
					
						
		    file_put_contents("tempbody.tex",$cnt);
		    shell_exec("/home/vidhya/.cabal/bin/pandoc tempbody.tex --mathml -o temp.xml");
		    
		    //AFTER CONVERTING TO XML*******************************************************
		    $this->xmlfile=file_get_contents("temp.xml");
		    $this->sectionProcess();
		    
		    //Merging front-back xml
		    $existed=file_get_contents($this->existed_xml_path);
		    $existed=preg_replace_callback("/\\\\textbf({(((?>[^{}]+)|(?1))*)})/s",function($m){
				return "<bold>".$m[2]."</bold>";
				},$existed);  //change front matter \textbf to bold
			
			$existed=preg_replace_callback("/\\\\textit({(((?>[^{}]+)|(?1))*)})/s",function($m){
				return "<italic>".$m[2]."</italic>";
				},$existed);  //change front matter \textit to italic
				
			$this->xmlfile=str_replace("<body/>","<body>\n".$this->xmlfile."</body>",$existed);
			
			//replace for bold,italic etc
			$this->xmlfile=str_replace("<strong>","<bold>",$this->xmlfile);
			$this->xmlfile=str_replace("</strong>","</bold>",$this->xmlfile);
			$this->xmlfile=str_replace("<em>","<italic>",$this->xmlfile);
			$this->xmlfile=str_replace("</em>","</italic>",$this->xmlfile);
			$this->xmlfile=str_replace("<blockquote>","<disp-quote>",$this->xmlfile);
			$this->xmlfile=str_replace("</blockquote>","</disp-quote>",$this->xmlfile);
			$this->xmlfile=str_replace("<br />","",$this->xmlfile);
			
			//replace for math
			$this->xmlfile=str_replace("<semantics>","",$this->xmlfile);
			$this->xmlfile=str_replace("</semantics>","",$this->xmlfile);
			$this->xmlfile=preg_replace_callback("/<annotation.*?annotation>/s",function($m){return "";},$this->xmlfile);
			$this->xmlfile=preg_replace_callback("/(xmlns)(=[\"|']http:\\/\\/www\\.w3\\.org\\/1998\\/Math\\/MathML[\"|'])/s",function($m){return $m[1].":mml".$m[2];},$this->xmlfile);	
			
			//replace for list
			$this->xmlfile=str_replace("<li>","<list-item>",$this->xmlfile);
			$this->xmlfile=str_replace("</li>","</list-item>",$this->xmlfile);
			$this->xmlfile=str_replace("<ol>","<list list-type=\"order\">",$this->xmlfile);
			$this->xmlfile=str_replace("</ol>","</list>",$this->xmlfile);
			$this->xmlfile=str_replace("<ul>","<list list-type=\"unorder\">",$this->xmlfile);
			$this->xmlfile=str_replace("</ul>","</list>",$this->xmlfile);
			
			//CALLING FUNCTION*****************************************************************************
			
			$this->matchRef($this->getBibrFromExistedXML());   //bib-function
			$this->mathProcess();   //math-function
			$this->figureProcess();
			$this->tableProcess();
			$this->idProcess();
			file_put_contents("temp.xml",$this->xmlfile);
		}	
		
		function getBibrFromExistedXML(){
			$dom = new DOMDocument();
            $dom->load($this->existed_xml_path);
            $xpath = new DOMXpath($dom);	

			$label=$xpath->query("//back/ref-list/ref/label");
			$xref=array();
			foreach($label as $labkey => $labval){$xref[]=$labval->textContent;}
			return $xref;
			}
				
			//*********************BIB-FUNCTIONS***************************
			
		function matchRef($xref){	
			  $x=1;		  		  
                foreach($xref as $xrefkey =>$xrefval){
	                 $this->xmlfile=str_replace($xrefval,$x++,$this->xmlfile);
	              }
	              $this->createBibr();
		  }
		  
		  function createBibr(){
			$doi_no=$this->doi;
			
			$this->xmlfile=preg_replace_callback("/\\*\\*citeref<span>(.*?)<\\/span>/s",function($m)use($doi_no){				
	                  $rid=explode(",",$m[1]);
	                  $rid_str="";
	                foreach($rid as $ridkey =>$ridval){		
		            $rid_str.="<xref ref-type='bibr' rid='bibr".$ridval."-$doi_no'>".$ridval."</xref>,";}
		            $rid_str="<sup>".rtrim($rid_str,",")."</sup>";	
	               return $rid_str;},$this->xmlfile);
               
			  
		    }
		    
		    //*********************BIB-FUNCTIONS-END************************
		    
		    //*********************SECTION-FUNCTIONS*************************** 
		    
		    function sectionProcess(){
				$level = 0;
				$array = explode("\n", $this->xmlfile);
				$r = '';
				foreach($array as $line)  {

				if (preg_match("/<h(\\d+).*?>(.*?)<\\/h\\d+>/is", $line, $m)) {
				$l = $m[1];
				if ($level == $l) $r .=  "</sec>\n<sec>\n<title>$m[2]</title>";
				else {
					While ($level >= $l) { $r .=  "</sec>\n"; $level--; }
					While ($level < $l) { $r .=  "<sec>\n<title>$m[2]</title>"; $level++; }
					}
				  }
				$r .=  $line."\n";   
				}
                 While ($level >= 1) {$r .= "</sec>\n"; $level--; }
				
				$r=preg_replace("/<h\\d+.*?<\\/h\\d+>/s","",$r);  //removing h tags
				$this->xmlfile=$r;
			}
		    
		    
		    //*********************SECTION-FUNCTIONS-END*************************** 
		    
		    //*********************ID-FUNCTIONS*************************** 
		       function idProcess(){
		       $dom = new DOMDocument();
               $dom->loadXML($this->xmlfile);
              	   
			   
			   //sec elements
			   $sec_count=1;
			   foreach($dom->getElementsByTagName('sec') as $sec){
				$sec->setAttribute("id","sec".$sec_count++."-".$this->doi);   
				}
				
				//list
				$list_count=1;
				foreach($dom->getElementsByTagName('list') as $li){
				$li->setAttribute("id","list".$list_count++."-".$this->doi);   
				}
				//table
				$tab_count=1;
				foreach($dom->getElementsByTagName('table-wrap') as $tw){
				$tabc=$tab_count++;
				$tw->setAttribute("id","table$tabc-".$this->doi);
				$tw->setAttribute("position", "float");
				$tw->getElementsByTagName("label")->item(0)->appendChild($dom->createTextNode("Table $tabc."));
				$graphic_table=$tw->getElementsByTagName("graphic")->item(0);
				$graphic_table->setAttribute("specific-use","table$tabc-".$this->doi);
				$graphic_table->setAttribute("xmlns:xlink","http://www.w3.org/1999/xlink");
				$graphic_table->setAttribute("xlink:href",$this->fdoi."table$tabc.tif");
				
			     }		
				   
		    $this->xmlfile=$dom->saveXML();	     
		   
		   
		   //crossref function		   
		   
		   //eqn-label		   
		   preg_match_all("/\\*\\*labeleqnref\\s*<span>(.*?)<\\/span>\\s*<disp-formula id=\"(.*?)\"/s",$this->xmlfile,$meql);
		   
		   
		   //overall ref
		   $this->xmlfile=preg_replace_callback("/\\*\\*refref\\s*<span>(.*?)<\\/span>/s",function($mref) use($meql){
			   		   
			       $to_return="refref";
			        
			        //eqn
				   for($j=0;$j<count($meql[0]);$j++){
					  
				     if($meql[1][$j]===$mref[1]){
						 
						 preg_match("/disp-formula(\d+)-/s",$meql[2][$j],$mdisp);
					   $to_return= "<xref ref-type=\"disp-formula\" rid=\"".$meql[2][$j]."\">".$mdisp[1]."</xref>";
					   break;}
					}
					
			      
			   	return $to_return;	   
			   },$this->xmlfile);
			   
			   $this->xmlfile=preg_replace("/\\*\\*label.*?ref.*?<\\/span> /s","",$this->xmlfile);    //deleting label ref once it is crossreferenced
		   
		   }
		     
		    //*********************ID-FUNCTIONS-END***************************  	    
		    
		    
		   
		   //*********************FIGURE-FUNCTIONS******************************
		   
		   function figureProcess(){
			   $label=0;
			   $tif=0;
			 $this->xmlfile=preg_replace_callback("/<p>\\[figcap\\](.*?)\\[\\/figcap\\((\\d+)\\)\\]<\\/p>/s",function($m) {
				        global $label;
				        global $tif;
				        $label++;
				        $tif++;
				        $figure="<fig id=\"fig$label-".$this->doi."\" position=\"float\" >\n<label>Figure $label.</label>\n<caption><p>$m[1]</p></caption>\n";
				        
				        for($i=1;$i<=$m[2];$i++){
						$figure.="<graphic xmlns:xlink=\"http://www.w3.org/1999/xlink\" xlink:href=\"".$this->fdoi."-fig$tif.tif\"/>";
						if($m[2]>1){$tif++;}							
							
					    }			

				         			
				        $figure.="\n</fig>";
				       
				        return $figure;},$this->xmlfile);		   
			   
		   }  
		   
		   
		   //*********************FIGURE-FUNCTIONS-END******************************
		   
		    
		    //*********************MATH-FUNCTIONS*************************** 
		   
		   function mathProcess(){
			   $dom = new DOMDocument();
               $dom->loadXML($this->xmlfile);
               
               //setting attribute for root element  (article)
               $dom->documentElement->setAttribute("dtd-version","1.1d1"); 
               $dom->documentElement->setAttribute("xml:lang","en");  
               $dom->documentElement->setAttribute("xmlns:xlink","http://www.w3.org/1999/xlink");  
               $dom->documentElement->setAttribute("article-type","research-article"); 
               $dom->documentElement->setAttribute("xmlns:mml","http://www.w3.org/1998/Math/MathML");
			   
			   //math elements
			   $math_display=0;
			   $math_inline=0;
			   foreach($dom->getElementsByTagName('math') as $math){
				   
				   //block-math
				   if($math->getAttribute("display")==="block"){					      
					   
					   $math_display++;
					   //attribute (display and id)
					    $math->setAttribute("id","math".$math_display."-".$this->doi); 
					    
					   //disp-formula  	   
					   $disp=$dom->createElement("disp-formula");
					   $disp->setAttribute("id","disp-formula".$math_display."-".$this->doi); 
					   
					   $alter=$disp->appendChild($dom->createElement("alternatives"));
					   $math->parentNode->insertBefore($disp, $math);
					   $alter->appendChild($math);
					   
					   //grapic
					   $graphic=$dom->createElement("graphic");
					   $graphic->setAttribute("xlink:href",$this->fdoi."-eq".$math_display.".tif");
					   $graphic->setAttribute("specific-use","disp-formula".$math_display."-".$this->doi); 
					   
					   $alter->appendChild($graphic);
					   
					   }
					   					   
				   else{
					   $math_inline++;
					   
					   //set attributes(id)
					   $math->setAttribute("id","mml-math".$math_inline."-".$this->doi); 
					   
					   //inline-forumula
					   $inline=$dom->createElement("inline-formula");
					   $inline->setAttribute("id","ilml".$math_inline."-".$this->doi); 
					   
					   $math->parentNode->insertBefore($inline, $math);
					   $inline->appendChild($math);
					   }//inline-math
				}	   
					   
			  $this->xmlfile=$dom->saveXML();
			  $this->xmlfile=str_replace("<m","<mml:m",$this->xmlfile);   //creating mml namespace for math elements
			  $this->xmlfile=str_replace("</m","</mml:m",$this->xmlfile);   //creating mml namespace for math elements closing tag
			  $this->xmlfile=str_replace("mml:mixed-citation","mixed-citation",$this->xmlfile);   //reflection of math element so change it back
		      $this->xmlfile=str_replace("mml:month","month",$this->xmlfile); 
		   }
		   
		   //*********************MATH-FUNCTIONS-END***************************
		   
		    //*********************TABLE-FUNCTIONS******************************
		    	
			
			function tableProcess(){
				$this->xmlfile=preg_replace_callback("/<p>#\\[table-wrap\\]#.*?#\\[\\/table-wrap]#<\\/p>/s",function($m){
				$tab_clear=str_replace("#[","<",$m[0]);  //change #[ to <
				$tab_clear=str_replace("]#",">",$tab_clear);   //change ]# to >
				$tab_clear=str_replace("<p>","",$tab_clear); //remove <p>
				$tab_clear=str_replace("</p>","",$tab_clear); //remove </p>
				$tab_clear=str_replace("<caption>","<caption><p>",$tab_clear); //insert <p> in the caption
				$tab_clear=str_replace("</caption>","</p></caption>",$tab_clear); //insert </p> in the caption
				$tab_clear=str_replace("@#","\"",$tab_clear); //change @# to "
				return $tab_clear;},$this->xmlfile);
				
			}	
				
			  function tablePreProcess($cnt){
				
			$cnt=preg_replace_callback("/\\\\begin{[table|tabular]*}.*?\\\\caption{(.*?)}.*?begin{thead}(.*?)%\\\\end{thead}\\s*(.*?)\\\\end{tabular}.*?\\\\end{table}/s",function($m){
   	
	$tcap=$m[1];
	$thead=rtrim(trim($m[2]),"\\");
	$tbody=rtrim(trim($m[3]),"\\");
    
    //$table_str="[table($tcol_count)][tabcap]$tcap[tabcap]";
    
    //thead 
    $table_str_start="#[table-wrap]#\n#[label]##[/label]#\n#[caption]#$tcap#[/caption]#\n#[alternatives]#\n#[graphic]##[/graphic]#\n#[table]#\n";
    $thead_row=explode("\\\\",$thead);
     $thead_str="#[thead align=@#left@# valign=@#top@#]#\n";
    foreach($thead_row as $key => $val){
		
		//tr				
		$thead_str.="#[tr]#\n";	
			$th=explode("&",$val);
			$th=array_map("trim",$th);
			$th_str="";
			
			//th
		    foreach($th as $thkey => $thval){
				//multicolumn
				if(strpos($thval,"\\multicolumn")!==false){
					preg_match("/multicolumn{(\\d+)}{.*?}{(.*?)}$/s",$thval,$mat);
				    $th_str.="#[th colspan=@#".$mat[1]."@#]#".$mat[2]."#[/th]#\n";		
				continue;	
				}
				//multirow
				if(strpos($thval,"\\multirow")!==false){
					preg_match("/multirow{(\\d+)}{.*?}{(.*?)}$/s",$thval,$mat);
				    $th_str.="#[th rowspan=@#".$mat[1]."@#]#".$mat[2]."#[/th]#\n";	
				continue;	
				}
				$th_str.="#[th]#$thval#[/th]#\n";
											
			}$thead_str.=$th_str."#[/tr]#\n";	
			
	}$thead_str.="#[/thead]#\n";
	
	//tbody
	
    $tbody_row=explode("\\\\",$tbody);
     $tbody_str="#[tbody align=@#left@# valign=@#top@#]#\n";
    foreach($tbody_row as $key => $val){
		
		//tr				
		$tbody_str.="#[tr]#\n";	
			$td=explode("&",$val);
			$td=array_map("trim",$td);
			$td_str="";
			
			//th
		    foreach($td as $tdkey => $tdval){
				//multicolumn
				if(strpos($tdval,"\\multicolumn")!==false){
					preg_match("/multicolumn{(\\d+)}{.*?}{(.*?)}$/s",$tdval,$mat);
				    $td_str.="#[td colspan=@#".$mat[1]."@#]#".$mat[2]."#[/td]#\n";		
				continue;	
				}
				//multirow
				if(strpos($tdval,"\\multirow")!==false){
					preg_match("/multirow{(\\d+)}{.*?}{(.*?)}$/s",$tdval,$mat);
				    $td_str.="#[td rowspan=@#".$mat[1]."@#]#".$mat[2]."#[/td]#\n";	
				continue;	
				}
				$td_str.="#[td]#$tdval#[/td]#\n";
											
			}$tbody_str.=$td_str."#[/tr]#\n";	
			
	}$tbody_str.="#[/tbody]#\n";
	$table_str_end="#[/table]#\n#[/alternatives]#\n#[/table-wrap]#\n";
	$table_str=$table_str_start.$thead_str.$tbody_str.$table_str_end;
	
	return $table_str;
      },$cnt);	
				
			return $cnt;}	
				
		 //*********************TABLE-FUNCTIONS-END******************************		
				
				
				
			
			
	
}



?>
