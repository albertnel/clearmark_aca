<?php

   // Node elements

   // parent_id
   // id
   // name
   // target
   // event
   // closeImage
   // openImage
   // last

   class tree
   {
      var $imagePath = "lib/menu-tree/img/";
      var $root = array();
      var $nodes = array();
      var $windowname = "messages";
      var $tree = array();

      function tree()
      {
         $this->root[id] = 0;
         $this->root[name]= "Root Node";
         $this->root[image] = $this->imagePath."base.gif";
      }

      function setWindow($name)
      {
         $this->windowname = $name;
      }

      function setRoot($arr)
      {
         $this->root[name] = $arr[name];
         $this->root[event] = $arr[events];
         if ($arr[image] != "")
            $this->root[image] = $arr[image];
      }

      function setRootName($name)
      {
         $this->root[name] = $name;
      }

      function setRootEvents($arr)
      {
         $this->root[event] = $arr;
      }

      function setRootImage($image)
      {
         $this->root[image] = $image;
      }

      function initializeTree()
      {
         $this->root[id] = 0;
         if ($this->root[name] == "")
         	if($_SERVER[argv][0] == 'structure')
         		$this->root[name]= "Admin";
         	else
            	$this->root[name]= "Root Node";
         $this->root[image] = $this->imagePath."base.gif";
      }

      function setOpenNode($uid)
      {
      	$this->closeAllNodes();
      	$this->openNodes($uid);
      }

      function openNodes($pid)
      {
      	$size = sizeof($this->nodes);
			for ($a = 0; $a < $size; $a++)
			{
				if ($this->nodes[$a][id] == $pid)
				{
					$this->nodes[$a][open] = true;
					$this->openNodes($this->nodes[$a][parent_id]);
				}
			}
      }

      function closeAllNodes()
      {
			$size = sizeof($this->nodes);
			for ($a = 0; $a < $size; $a++)
			{
				$this->nodes[$a][open] = false;
			}
      }

      function addNode($uid, $pid,$name,$url = "", $title = "", $target = "", $icon = "", $iconOpen = "", $close = "",$events = "")
      {
         if($pid != $uid)
         {
            $size = sizeof($this->nodes);
            $this->nodes[$size][id] = $uid;
            $this->nodes[$size][parent_id] = $pid;
            $this->nodes[$size][name] = $name;
            $this->nodes[$size][target] = $target;
            $this->nodes[$size][image] = $icon;
            $this->nodes[$size][openImage] = $iconOpen;
            $this->nodes[$size][closeImage] = $close;
            $this->nodes[$size][hc] = false;
            $this->nodes[$size][event] = $events;
            $this->nodes[$size][open]  = true;

            for ($a = 0; $a < $size; $a++)
            {
               if ($this->nodes[$a][id] == $pid)
               {
                  if ($pid != "0")
                     $this->nodes[$a][hc] = true;
               }
               if ($uid == $this->nodes[$a][parent_id])
               {
                  $this->nodes[$size][hc] = true;
               }
            }
         }
      }

      function buildTreeRow($pos,$rsize,$rpos)
      {

         $size = sizeof($this->levels);
         $str = "<span>";
         for ($a = 0; $a < $size; $a++)
         {
            $lsize = $this->levels[$a][size];
            $lsize--;
            $lpos = $this->levels[$a][pos];

            if ($lsize == $lpos)
               $str .= '<img src="'.$this->imagePath.'empty.gif" alt="" />';
            else
               $str .= '<img src="'.$this->imagePath.'line.gif" alt="" />';
         }

         //debug($this->nodes[$pos]);


         if ($this->nodes[$pos][hc])
         {
            $str .= '<a href="javascript: OpenNode(';

            //debug($this->nodes[$pos]);

            if ($this->nodes[$pos][open])
            {
            	$_SESSION['node']['nodesToOpen'][] = $this->count;
            	//debug($this->nodes[$pos][open]);
            	if ($rsize-1 == $rpos)
            	   $str .= $this->count.',1);"><img src="'.$this->imagePath.'minusbottom.gif" alt="" id="plus'.$this->count.'" />';
            	else
            	   $str .= $this->count.',0);"><img src="'.$this->imagePath.'minus.gif" alt="" id="plus'.$this->count.'"/>';
           	}
           	else
           	{
           		if ($rsize-1 == $rpos)
						$str .= $this->count.',1);"><img src="'.$this->imagePath.'plusbottom.gif" alt="" id="plus'.$this->count.'" />';
					else
            	   $str .= $this->count.',0);"><img src="'.$this->imagePath.'plus.gif" alt="" id="plus'.$this->count.'"/>';
           	}
            $str .= '</a>';
         }
         else
         {
            if ($rsize-1 == $rpos)
               $str .= '<img src="'.$this->imagePath.'joinbottom.gif" alt="" />';
            else
               $str .= '<img src="'.$this->imagePath.'join.gif" alt="" />';
         }
         if ($this->nodes[$pos][image] == "")
         {
            if ($this->nodes[$pos][hc])
            {
               $str .= '<img id="image'.$this->count.'" src="'.$this->imagePath.'folder.gif" alt="" />';
            }
            else
               $str .= '<img id="image'.$this->count.'" src="'.$this->imagePath.'page.gif" alt="" />';
         }
         else
            $str .= '<img id="image'.$this->count.'" src="'.$this->nodes[$pos][image].'" alt="" />';
         $str .= '<a unselectable="on" id="sd'.$this->nodes[$pos][id].'" class="node"';
         if ($this->nodes[$pos][target] != "")
            $str .= ' href="'.$this->nodes[$pos][target].'"';
         $eventsize = sizeof($this->nodes[$pos][event]);
         for ($e = 0; $e < $eventsize; $e++)
         {

            $str .= ' '.$this->nodes[$pos][event][$e][name].'="'.$this->nodes[$pos][event][$e][action].'"';
            if ($this->nodes[$pos][event][$e][name] == "onclick")
               $str .= ' style="cursor:pointer;"';
            if ($this->nodes[$pos][event][$e][name] == "mouseleftclick")
               $str .= ' style="cursor:pointer;"';
         }
         $str .= " >";
         $str .= $this->nodes[$pos][name].'</a>';
         //debug($this->nodes[$pos]);
         $str .= "</span>";
         return $str;
      }

      var $levels = array();
      var $count = 0;
      function buildTree($arr,$str = "")
      {
         $size = sizeof($arr);
         $data = "";

         //debug($arr);

         for ($a = 0; $a < $size; $a++)
         {

            if (is_array($arr[$a][children]))
            {

            	 //HACK FOR CTRU - NB -> SESSION VARIABLE
            	 /*
            	 if ($_SESSION[node][currentNode][parent_id] == 0)
            	 {
            	 	if ($_SESSION[node][currentNode][menu_id] != $this->nodes[$arr[$a][pos]][id])
            	 	{
            	 	  $this->nodes[$arr[$a][pos]][open] = 0;
            	 	}
            	 }
            	 else if ($_SESSION[node][currentNode][parent_id] != $this->nodes[$arr[$a][pos]][id])
            	 {
            	 	$this->nodes[$arr[$a][pos]][open] = 0;
            	 }
					*/

               //$this->nodes[$arr[$a][pos]][open] = 0;

               //debug($this->nodes[$arr[$a][pos]]);


               $this->count++;
               $str .= $this->buildTreeRow($arr[$a][pos],$size,$a);
               $str .= "<br>";

               //close all links
               //$this->nodes[$arr[$a][pos]][open] = 0;


               if ($this->nodes[$arr[$a][pos]][open])
               	$str .= '<span id="dd'.$this->count.'" style="display:block;">';
               else
               	$str .= '<span id="dd'.$this->count.'" style="display:none;">';
               $level = sizeof($this->levels);
               $this->levels[$level][size] = $size;
               $this->levels[$level][pos] = $a;
               $str = $this->buildTree($arr[$a][children],$str);
            }
            else
            {
               $str .= $this->buildTreeRow($arr[$a][pos],$size,$a);
               $str .= "<br>";
            }
         }

         if (is_array($this->levels))
            array_pop($this->levels);
         $str .= '</span>';
         return $str;
      }

      function buildarrayTree($pid,$arr = Array())
      {
         $size = sizeof($this->nodes);
         for ($a = 0; $a < $size; $a++)
         {
            if ($this->nodes[$a][parent_id] == $pid)
            {
               $tree[children][] = $this->buildarrayTree($this->nodes[$a][id],$arr);
            }
            elseif ($this->nodes[$a][id] == $pid)
            {
               $tree[pos] = $a;
            }
         }
         return $tree;
      }

      function construtArrayTree($pid)
      {
         $tree = $this->buildarrayTree(0);
         return $tree[children];
      }

      function getTree()
      {
         $str = '';
         $str = '<script type="text/javascript" src="lib/menu-tree/tree.js"></script>';
         $str .= '<link rel="StyleSheet" href="lib/menu-tree/tree.css" type="text/css" />';
         $str .= "<div class=\"tree\" style=\"position:relative\">";
         $str .= '<span class="TreeNode" style="position:relative;"><img id="id0" src="'.$this->root[image].'" alt=""/><a';
         $str .= ' id="sd0" class="node"';

         $eventsize = sizeof($this->root[event]);
         if ($eventsize > 0)
         {
            $str .= ' style="cursor:pointer;" ';
         }
         for ($e = 0; $e < $eventsize; $e++)
         {
            $str .= ' '.$this->root[event][$e][name].'="'.$this->root[event][$e][action].'"';
         }
         $str .= '>'.$this->root[name].'</a></span><br>';

         //$this->sortNodes();
         //debug_hard(nl2br($this->buildTree($this->construtArrayTree(0))));

         $str .= $this->buildTree($this->construtArrayTree(0));
         $str .= "</div>";
         return $str;
      }
   }
?>
