<?php

class tpParseObject
{
   var $AttribList;
   var $ParamList;
   var $ValueList;

   var $CustomTag;
   var $CustomContent;
   var $CustomEnd;

   var $Tag;
   var $Contents;
   var $End;

   function tpParseObject()
   {
      $this->ParamList = array();
      $this->AttribList = array();
      $this->ValueList = array();

      $this->CustomTag = false;
      $this->Tag = "";

      $this->CustomContent = false;
      $this->Content = "";

      $this->CustomEnd = false;
      $this->End = "";
   }

   function setParam($param,$value)
   {
      array_push($this->ParamList,(strtolower($param)));
      array_push($this->ValueList,$value);
   }

   function setAttrib($value)
   {
      array_push($this->AttribList,$value);
   }

   function setTag($value)
   {
      $this->CustomTag = true;
      $this->Tag = $value;
   }

   function setContent($value)
   {
      $this->CustomContent = true;
      $this->Content = $value;
   }

   function setEnd($value)
   {
      $this->CustomEnd = true;
      $this->End = $value;
   }

   function result($tag,$content,$end)
   {
      if ($this->CustomTag != "")
      {
         $tag = $this->Tag;
      }
      if ($this->CustomContent != "")
      {
         $content = $this->Content;
      }
      if ($this->CustomEnd != "")
      {
         $end = $this->End;
      }
      $temp = explode(" ",$tag);
      $tagName = $temp[0];
      $elementType = substr($tagName,1);
      #debug(htmlspecialchars($elementType, ENT_QUOTES));

      // Add parameters (name=value)
      $imax = sizeof($this->ParamList);
      for($i=0;$i<$imax;$i++)
      {
         $param = $this->ParamList[$i];
         $value = $this->ValueList[$i];

         // Special dropdown only case
         if ($elementType == "select" && $param == "selectedindex")
         {
            $found = "value=\"".$value."\"";
            $posi = strpos($content,$found);
            if ($posi !== false)
            {
               $content = substr($content,0,$posi-1)." selected ".substr($content,$posi);
            }
            continue;
         }

         if (ereg(" ".$param,$tag))
         {
            $tag = str_replace(">","",$tag);
            $tagData = explode(" ",$tag);

            $jmax = sizeof($tagData);
            for($j=0;$j<$jmax;$j++)
            {
               if (substr($tagData[$j],0,strlen($param)) == $param)
               {
                  $tagData[$j] = "";
                  if ($tagData[$j+1][0] == "=")
                  {
                     $tagData[$j+1] = "";
                  }
               }
            }
            $tag = join(" ",$tagData).">";
         }

         $tag = str_replace($tagName,$tagName." ".$param."=\"".$value."\"",$tag);
      }

      $imax = sizeof($this->AttribList);
      for($i=0;$i<$imax;$i++)
      {
         $attrib = $this->AttribList[$i];
         $tag = str_replace($tagName,$tagName." ".$attrib,$tag);
      }

      return $tag.$content.$end;
   }
}

class tpParseTable extends tpParseObject
{
   var $Data;

   function tpParseTable()
   {
      $this->ParamList = array();
      $this->AttribList = array();
      $this->ValueList = array();

      $this->CustomTag = false;
      $this->Tag = "";

      $this->CustomContent = false;
      $this->Content = "";

      $this->CustomEnd = false;
      $this->End = "";

      $this->Data = array();
   }

   function setTag($value)
   {
      $this->CustomTag = true;
      $this->Tag = $value;
   }

   function setContent($value)
   {
      $this->CustomContent = true;
      $this->Content = $value;
   }

   function setEnd($value)
   {
      $this->CustomEnd = true;
      $this->End = $value;
   }

   function result($tag,$content,$end)
   {
      if ($this->CustomTag != "")
      {
         $tag = $this->Tag;
      }
      if ($this->CustomContent != "")
      {
         $content = $this->Content;
      }
      if ($this->CustomEnd != "")
      {
         $end = $this->End;
      }
      $temp = explode(" ",$tag);
      $tagName = $temp[0];

      // Add parameters (name=value)
      $imax = sizeof($this->ParamList);
      for($i=0;$i<$imax;$i++)
      {
         $param = $this->ParamList[$i];
         $value = $this->ValueList[$i];
         if (ereg(" ".$param,$tag))
         {
            $tag = str_replace(">","",$tag);
            $tagData = explode(" ",$tag);

            $jmax = sizeof($tagData);
            for($j=0;$j<$jmax;$j++)
            {
               if (substr($tagData[$j],0,strlen($param)) == $param)
               {
                  $tagData[$j] = "";
                  if ($tagData[$j+1][0] == "=")
                  {
                     $tagData[$j+1] = "";
                  }
               }
            }
            $tag = join(" ",$tagData).">";
         }

         $tag = str_replace($tagName,$tagName." ".$param."=\"".$value."\"",$tag);
      }

      $imax = sizeof($this->AttribList);
      for($i=0;$i<$imax;$i++)
      {
         $attrib = $this->AttribList[$i];
         $tag = str_replace($tagName,$tagName." ".$attrib,$tag);
      }

      if (sizeof($this->Data) > 0)
      {
         $key = "<tr";
         $raw = split($key,$content);
         $imax = sizeof($raw);
         $rowpos = strlen($raw[0]) + strlen($key);
         $rowFound = false;
         $rowOpen = 0;
         $rowClose = 0;

         for ($i=1;$i<$imax;$i++)
         {
            $tabPos = strpos($raw[$i],"<table");
            $staPos = strpos($raw[$i]," static");
            if ($staPos !== false && ($tabPos === false || $staPos < $tabPos))
            {
               $rowpos += strlen($raw[$i]) + strlen($key);
               continue;
            }

            if ($tabPos !== false)
            {
               $otCount = substr_count($raw[$i],"<table");
               $ctCount = substr_count($raw[$i],"</table");
               if ($otCount != $ctCount)
               {
                  $raw[($i+1)] = $raw[$i].$key.$raw[($i+1)];
                  $raw[$i] = "";
                  continue;
               }
            }
            $startTag = tpFindEdges($rowpos,$content);

            $closePoint = tpFindClosePos($startTag[tagName],$startTag[cPos],$content);
            #debug_hard($startTag[tagName],$startTag[cPos],$content,$rowpos,$closePoint);
            $stopTag = tpFindEdges($closePoint+1,$content);
            $open = $startTag[oPos];
            $close = $stopTag[cPos] - $startTag[oPos];

            $rowpos += strlen($raw[$i]) + strlen($key);
            if (!$rowFound)
            {
               $rowFound = true;
               $rowOpen = $open;
               $rowClose = $close;
            }
            else
            {
               $raw[$i] = "";
            }
         }
         $content = "";
         $nmax = sizeof($raw);
         for ($n=0;$n<$nmax;$n++)
         {
            if ($raw[$n] != "")
            {
               if (trim($raw[$n]) != "") $content .= $key;
               $content .= $raw[$n];
            }
         }

         $rowText = substr($content,$rowOpen,$rowClose);
         #debug_hard("a: ".$content,"b: ".$rowText);

         $rowCode = '"row_';
         $imax = sizeof($this->Data);
         for ($i=0;$i<$imax;$i++)
         {
            $thisRowText = $rowText;
            while (strpos($thisRowText,$rowCode) !== false)
            {
               $tmp = split($rowCode,$thisRowText);
               $posStart = strlen($tmp[0]) + strlen($rowCode);
               $tmp = substr($thisRowText,$posStart,strlen($thisRowText));
               $posEnd = strpos($tmp,'"');
               $keyCode = substr($thisRowText,$posStart,$posEnd);
               $thisRowText = substr($thisRowText,0,$posStart-(strlen($rowCode))+1).$this->Data[$i][$keyCode].substr($thisRowText,$posStart+$posEnd,strlen($thisRowText));
            }
            if (!is_array($this->Data[$i][incrementer])) $this->Data[$i][incrementer] = $i + 1;
            #debug_hard($thisRowText);
            $rowData .= tpParse($this->Data[$i],$thisRowText,$debug);
         }

         $content = substr($content,0,$rowOpen).$rowData.substr($content,$rowClose+$rowOpen);
      }

      return $tag.$content.$end;
   }
}

?>
