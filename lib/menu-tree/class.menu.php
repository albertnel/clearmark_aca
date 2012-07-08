<?php
	require_once("class.tree.php");
	class menu extends tree
	{
		var $content = array();
		var $name = "navi1";
		var $type = "horizontal";
		var $tree;

		function menu($name = "navi1",$type = "vertical")
		{
			$this->name = $name;
			if ($type == "v")
				$this->type = "vertical";
			elseif ($type == "h")
				$this->type == "horizontal";
			else
				$this->type = $type;
		}

		function setType($type)
		{
			$this->name = $name;
			if ($type == "v")
				$this->type = "vertical";
			elseif ($type == "h")
				$this->type == "horizontal";
			else
				$this->type = $type;
		}

		function setName($name)
		{
			$this->name = $name;
		}

		function addMenuItem($uid,$pid,$title,$url = "",$target = "",$desc = "",$icon = "",$event = array())
		{
			$size = sizeof($this->content);
			$this->content[$size][title] = $title;
			$this->content[$size][pid] = $pid;
			$this->content[$size][uid] = $uid;
			$this->content[$size][icon] = trim($icon);
			$this->content[$size][url] = $url;
			$this->content[$size][target] = $target;
			$this->content[$size][desc] = $desc;
			$this->content[$size][splitline] = false;
			$this->content[$size][hc] = false;
			$this->content[$size][last] = true;
			$this->content[$size][event] = $event;

			for ($a = 0; $a < $size; $a++)
			{
				if ($this->content[$a][uid] == $pid)
				{
					if ($pid != "0")
						$this->content[$a][hc] = true;
				}
				if ($uid == $this->content[$a][pid])
				{
					$this->content[$size][hc] = true;
				}
         }
		}

		function addSplit($uid,$pid)
		{
			$size = sizeof($this->content);
			$this->content[$size][pid] = $pid;
			$this->content[$size][uid] = $uid;
			$this->content[$size][splitline] = true;
			$this->content[$size][hc] = false;
			$this->content[$size][last] = true;
			for ($a = 0; $a <$size; $a++)
			{
				if ($this->content[$a][pid] == $pid)
					$this->content[$a][last] = false;
				if ($this->content[$a][uid] == $pid)
					$this->content[$a][hc] = true;
			}
		}

		function buildarrayMenu($pid,$arr = Array())
		{
			$size = sizeof($this->content);
			for ($a = 0; $a < $size; $a++)
			{
				if ($this->content[$a][pid] == $pid)
				{
					$menuarray[children][] = $this->buildarrayMenu($this->content[$a][uid],$arr);
				}
				elseif ($this->content[$a][uid] == $pid)
				{
					$menuarray[pos] = $a;
				}
			}
			return $menuarray;
		}

		function constructArrayMenu($pid)
		{
			$menuarray = $this->buildarrayMenu(0);
			return $menuarray[children];
      }

		function buildBarMenu()
		{
			$menu = $this->buildHeader();
			$data = $this->constructArrayMenu(0);
			$menu .= $this->buildmenudata($data);
			$menu .= $this->buildFooter();
			return $menu;
		}

		function buildHeader()
		{
			$menu = '<link rel="stylesheet" href="lib/menu-tree/theme.css" type="text/css" />'."\r\n";
			$menu .= '<script language="JavaScript" src="lib/menu-tree/JSCookMenu.js" type="text/javascript"></script>'."\r\n";
			$menu .= '<script language="JavaScript" src="lib/menu-tree/theme.js" type="text/javascript"></script>'."\r\n\r\n";
			return $menu;
		}

		function buildmenu($arr, $str = "")
		{
			$size = sizeof($arr);
			$data = "";
			for ($a = 0; $a < $size; $a++)
			{
				$node = $this->content[$arr[$a][pos]];
				if ($str != "")
					$str .= ",";
				if (!$node[splitline])
				{
					$str .= "[";
					if ($node[icon] == "")
						$str .= "null,";
					else
						$str .= "'<img src=\"".$node[icon]."\" />',";
						//<img src="ThemeOffice/config.png" />
					$str .= "'".$node[title]."','".$node[url]."',";
					if ($node[target] == "")
						$str .= "null,";
					else
						$str .= "'".$node[target]."',";
					$str .= "'".$node[desc]."'";
					if (!$node[hc])
						$str .= "]";
				}
				if (is_array($arr[$a][children]))
				{
					$str = $this->buildmenu($arr[$a][children],$str);
					$str .= "]";
				}
        }
        return $str;
		}

		function buildmenudata($arr, $str = "")
		{
			$menu = "<script language=\"javascript\"><!--\r\n";
			$menu .= "var ".$this->name."_data = \r\n";
			$menu .= "[\r\n";
			$menu .= $this->buildmenu($arr,$str);
			$menu .= "\r\n];\r\n";
			$menu .= "--></script>\r\n\r\n";
			return $menu;
		}

		function buildFooter()
		{
			$menu = "<div id=".$this->name.">&nbsp;</div>\r\n";
			$menu .= "<script language=\"javascript\"><!--\r\n";
			$menu .= "cmDraw('".$this->name."',".$this->name."_data,'";
			if ($this->type == "horizontal")
				$menu .= "h";
			elseif ($this->type == "vertical")
				$menu .= "v";
			if (true)
				$menu .= "br";
			$menu .= "', cmThemeOffice, 'ThemeOffice');\r\n";
			$menu .= "--></script>\r\n";
			return $menu;
		}

		function buildbar($parent_id,$str)
		{
			/*$size = sizeof($this->content);
			$a = 0;
			foreach($this->content as $node)
			{
				$a++;
				if ($parent_id == $node[pid])
				{
					$menu .= $this->buildbar($node[uid],$menu);
					if ($node[hc])
						$menu .= "]";
				}
				elseif ($parent_id == $node[uid])
				{
					if ($str != "")
						$menu .= ",";
					if (!$node[splitline])
					{
						$menu .= "[";
						if ($node[icon] == "")
							$menu .= "null,";
						else
							$menu .= "'<img src=\"".$node[icon]."\" />',";
							//<img src="ThemeOffice/config.png" />
						$menu .= "'".$node[title]."','".$node[url]."',";
						if ($node[target] == "")
							$menu .= "null,";
						else
							$menu .= "'".$node[target]."',";
						$menu .= "'".$node[desc]."'";
						if (!$node[hc])
							$menu .= "]";
					}
					else
					{
						$menu .= "_cmSplit";
					}
				}
			}*/



			return $menu;
		}

		function buildTreeMenu()
		{
			$this->initializeTree();
			$size = sizeof($this->content);
			for ($a = 0; $a < $size; $a++)
			{
				if ($this->content[$a][pid] == -1)
					$this->content[$a][pid] = 0;

				$this->addnode($this->content[$a][uid],$this->content[$a][pid],$this->content[$a][title],$this->content[$a][url],$this->content[$a][title],$this->content[$a][target],$this->content[$a][icon],"","",$this->content[$a][event]);
				//addNode($uid, $pid,$name,$url = "", $title = "", $target = "", $icon = "", $iconOpen = "", $open = "",$events = "")
			}
			return $this->getTree();
		}

		function getMenu()
		{
			if ($this->type == "horizontal" || $this->type == "vertical")
				return $this->buildBarMenu();
			if ($this->type == "tree")
			{
				return $this->buildTreeMenu();
			}
			return false;
		}
	}
?>