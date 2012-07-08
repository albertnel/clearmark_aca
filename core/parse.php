<?php
	global $templateAutoElements;
	$templateAutoElements = array("input","select");
	
/*function setOverlib($overlib,$temp="")
{
   if (!is_object($temp)) $temp = new tpParseObject();
   $temp->setParam("onmouseover","return overlib('".$overlib."');");
   $temp->setParam("onmouseout","return nd();");
   return $temp;
}*/

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
function blankObject()
{
   $temp = new tpParseObject();
   $temp->setTag("");
   $temp->setContent("");
   $temp->setEnd("");
   return $temp;
}

function setNewValue($value)
{
   return setNewParam("value",$value);
}

function setNewParam($parm,$value)
{
   $temp = new tpParseObject();
   $temp->setParam($parm,$value);
   return $temp;
}

function setNewAttrib($attrib)
{
   $temp = new tpParseObject();
   $temp->setAttrib($attrib);
   return $temp;
}

function tpParse($tagList,$html,$debug=false)
{
   if (!is_array($tagList))
   {
      return $html;
   }

   global $templateAutoElements;

   // Go through the list and find the id in the html
   $tagKeys = array_keys($tagList);
   $imax = sizeof($tagList);
   for ($i=0;$i<$imax;$i++)
   {
      $string = $html;
      $anchor = $tagKeys[$i];
      $tag = 'id="'.$anchor.'"';
      $value = "";
      $positions = "1";
      $pad = 0;

      //find the anchor(s)
      $current = split($tag,$html);
      $jmax = sizeof($current)-1;
      for($j=0;$j<$jmax;$j++)
      {
         // get the anchor's position relative to the whole output string
         $anchorPos = (strlen($current[$j]))+$pad;

         // Do this only once
         if ($j==0)
         {
            (is_object($tagList[$anchor])) ? $positions = "0" : $positions = "1";
         }

         // get the start/stop tags
         $startTag = tpFindEdges($anchorPos,$html);

         if ($positions == "1" && in_array($startTag[tagName],$templateAutoElements))
         {
            switch ($startTag[tagName])
            {
               case "select":
                  $tagList[$anchor] = setNewParam("selectedindex",$tagList[$anchor]);
               break;

               case "input":
                  $tagList[$anchor] = setNewValue($tagList[$anchor]);
               break;

               case "img":
                  $tagList[$anchor] = setNewParam("src",$tagList[$anchor]);
               break;
            }

            $positions = "0";
         }

         if (strtolower($startTag[tagName]) != "input" && strtolower($startTag[tagName]) != "img")
         {
            $closePoint = tpFindClosePos($startTag[tagName],$startTag[cPos],$html);
            $stopTag = tpFindEdges($closePoint+1,$html);

            // get the inner/outer positions
            if ($positions == "0")
            {
               $startPos = $startTag[oPos];
               $endPos = $stopTag[cPos];
            }
            else
            {
               $startPos = $startTag[cPos];
               $endPos = $stopTag[oPos];
            }

            // Do this only once, and only if we found a tag to replace
            if ($j==0)
            {
               // if object ? variable = object.result
               if ($positions == "0")
               {
                  $tag = substr($html,$startPos,$startTag[cPos]-$startPos);
                  $content = substr($html,$startTag[cPos],$stopTag[oPos]-$startTag[cPos]);
                  $end = substr($html,$stopTag[oPos],$endPos-$stopTag[oPos]);
                  $value = $tagList[$anchor]->result($tag,$content,$end);
               }
               else
               {
                  $value = $tagList[$anchor];
               }
            }
         }
         else
         {
            if ($positions == "0")
            {
               $startPos = $startTag[oPos];
               $endPos = $startTag[cPos];
               $tag = substr($html,$startPos,$startTag[cPos]-$startPos);
               $value = $tagList[$anchor]->result($tag,"","");
            }
         }

         #echo $anchor.": ".$anchorPos."<pre>".$value."</pre>\n";
         //replace positions with value or with table rows
         $html = substr($html,0,$startPos).$value.substr($html,$endPos);
         #echo $endPos.", ".$startPos.", ".strlen($value)."<br>";
         $pad = $startPos + strlen($value) - 1;
      }
   }

   return $html;
}


// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
function tpFindEdges($pos,$haystack){

   # get the opening strpos
   $preIdLoci = substr($haystack,0,$pos);
   $startLoci = strrpos($preIdLoci,"<");

   # get the closing strpos
   $work2 = substr($haystack,$pos,strlen($haystack));
   $A = strpos($work2,"/>");
   $B = strpos($work2,">");
   $cTagLoci = $B;
   if ($A < $B && $A != "") {
      $cTagLoci = $A + 1;
   }
   $endLoci = $cTagLoci + $pos + 1;

   # Find the tagname
   $temp = substr($haystack,$startLoci,$endLoci-$startLoci);
   $temp = str_replace("<","",$temp);
   $temp = strtolower(str_replace(">","",$temp));
   $temp2 = explode(" ",$temp);
   $tagName = $temp2[0];

  // # return tag data and strpos'
   return array("tagName" => $tagName, "oPos" => $startLoci, "cPos" => $endLoci, "tagData" => $temp);
   }

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
function tpFindClosePos($tagName,$innerStart,$haystack){

   #if ($tagName == "tr") debug_hard($haystack);

   global $fCPFound;
   if ($fCPFound == "") {
      $fCPFound = "0";
   }

   if ($innerStart > strlen($haystack)) {
      return False;
   }

   $findCloseLoci = substr($haystack,$innerStart,strlen($haystack));
   $A = strpos($findCloseLoci,"<".$tagName);
   $B = strpos($findCloseLoci,"</".$tagName);


   if(is_bool($A))
   {
      $A = "-1";
   }
   if(is_bool($B))
   {
      $B = "-1";
   }

   if ($A != "-1" && $A < $B) {
      $innerStart = $innerStart + $B + 1;
      $fCPFound++;
      return tpFindClosePos($tagName,$innerStart,$haystack);
   }else{
      if ($B == "-1") {
         return "-1";
      }else{
         if ($fCPFound == "0") {
            $innerClose = $innerStart + $B;
            return $innerClose;
         }else{
            $fCPFound--;
            $innerStart = $innerStart + $B;
            return tpFindClosePos($tagName,$innerStart,$haystack);
         }
      }
   }
}

?>
