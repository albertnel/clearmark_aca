<?php

Class MySQLDB
{

// MySQLDB Variables
// =================
   var $name;
   var $login;
   var $passwd;
   var $host;
   var $conn;
   var $error;


// MySQLDB Methods
// ===============
   function MySQLDB ($name,$login,$passwd="",$host=localhost,$port="3306")
   {

      # set object variables
      $this->name = $name;
      $this->login = $login;
      $this->passwd = $passwd;
      $this->host = $host;

      $this->error = "";
      $this->conn = mysql_connect($this->host,$this->login,$this->passwd);

      # set this db as the active one
      $this->select();
   }

   function select()
   {
      mysql_select_db($this->name,$this->conn);
   }

   function put($string,$debug=false)
   {
      if ($debug) debug('$'."lDB->put(\"\n".$string."\n\",$mode,$debug);");
      # If the query executed succesfully return true


      if (mysql_query($string,$this->conn))
      {
        return true;
      }
      else
      {
         echo $string."<br>".mysql_error($this->conn)."<br>";
         return false;
      }
   }

/*
# if mode 0, return result directly
# if mode 1, return one row of result in multi-dimension array
# if mode 2, return all rows in result in multi-dimension array
# if mode 3, return all rows' first column in single dimension array
# if mode 4, return the first row's first column value
# if mode 5, ???
# if mode 6, return one row of result in single-dimension array but no int indexes
# if mode 7, return all rows in result in multi-dimension array with no int indexes
*/
   function get($string,$mode="7",$debug="0")
   {
      $this->error = "";
      if ($debug == "1") debug_hard('$'."DB->get(\"\n".$string."\n\",$mode,$debug);");

      # get the values in
      $result = mysql_query($string,$this->conn);
      $error = mysql_error($this->conn);
      if ($debug == "1") debug('$'."lDB error: $error");

      if ($error == "")
      {
         # switch on get 'mode'
         switch ($mode)
         {

            # if mode 0, return result directly
            case 0:
               return $result;
               break;

            # if mode 1, return one row of result in multi-dimension array
            case 1:
               if (mysql_num_rows($result) != "0")
               {
                  $array = mysql_fetch_array($result);
                  return $array;
               }
               else
               {
                  $array = array("0");
                  return $array;
               }
            break;

            # if mode 2, return all rows in result in multi-dimension array
            case 2:
               $rows = mysql_num_rows($result);

               $array = array();
               for ($q=0;$q<$rows;$q++)
               {
                  $thing = mysql_fetch_array($result);
                  if (!$thing)
                  {
                     break;
                  }
                  else
                  {
                     array_push($array,$thing);
                  }
               }
               return $array;

            break;

            # if mode 3, return all rows' first column in single dimension array
            case 3:
               $rows = mysql_num_rows($result);

               $array = array();
               for ($q=0;$q<$rows;$q++)
               {
                  $thing = mysql_fetch_array($result);
                  if (!$thing) {
                     break;
                  }
                  else
                  {
                     array_push($array,$thing[0]);
                  }
               }
               return $array;

            break;

            # if mode 4, return the first row's first column value
            case 4:
               if (mysql_num_rows($result) != "0")
               {
                  $array = mysql_fetch_array($result);
                  return $array[0];
               }
               else
                  return "";

            break;

            # if mode 2, return all rows in result in multi-dimension array
            case 5:
               $rows = mysql_num_rows($result);

               $array = array();
               for ($q=0;$q<$rows;$q++)
               {
                  $thing = mysql_fetch_row($result);
                  if (!$thing)
                  {
                     break;
                  }
                  else
                  {
                     array_push($array,$thing);
                  }
               }

               return $array;
            break;

            # if mode 6, return one row of result in multi-dimension array but no int indexes
            case 6:
               if (mysql_num_rows($result) != "0")
               {
                  $array = mysql_fetch_array($result);
                  $out = array();
                  $thingKeys = array_keys($array);
                  $xmax = sizeof($array);
                  for ($x=0;$x<$xmax;$x++)
                  {
                     $key = $thingKeys[$x];
                     $intkey = (int) $key;
                     $strkey = (string) $intkey;
                     if ($key != $strkey)
                     {
                        $out[$key] = $array[$key];
                     }
                  }
                  return $out;
               }
               else
               {
                  $array = array();
                  return $array;
               }
            break;

            # if mode 7, return all rows in result in multi-dimension array with no int indexes
            case 7:
               $rows = mysql_num_rows($result);

               $array = array();
               for ($q=0;$q<$rows;$q++)
               {
                  $thing = mysql_fetch_array($result);
                  if (!$thing)
                  {
                     break;
                  }
                  else
                  {
                     $out = array();
                     $thingKeys = array_keys($thing);
                     $xmax = sizeof($thing);
                     for ($x=0;$x<$xmax;$x++)
                     {
                        $key = $thingKeys[$x];
                        $intkey = (int) $key;
                        $strkey = (string) $intkey;
                        if ($key != $strkey)
                        {
                           $out[$key] = $thing[$key];
                        }
                     }
                     array_push($array,$out);
                  }
               }
               return $array;

            break;
         }
      }
      else
      {
         echo $string."<br>".$error."<br>";
      }
   }

   function count($table,$field,$value,$check,$where="where",$debug=0)
   {
      # compile sql query from arguments
      $qry = "select count(*) from ";
      $qry .= $table;
      if ($where == "where") {
         $qry .= " ".$where." ";
         $qry .= $field;
         $qry .= " = '";
         $qry .= $value;
         $qry .= "'";
      }
      else
      {
         $qry .= $where;
      }

      # execute query
      $tmp = $this->get($qry,"1");
      if ($debug == "1")
      {
         echo $qry."<br>\n".$tmp[0]." vs ".$check."<br>\n".mysql_error($this->conn)."<br>\n";
      }

      # return true or false on check
      if ($tmp[0] == $check)
      {
         Return True;
      }
      else
      {
         Return False;
      }
   }

   function showTables($mode)
   {
   	return $this->get("show tables",$mode);
   }

   function describeTable($table)
   {
   	$sql="DESCRIBE ".$table;
      return $this->get($sql,2);
   }

   function insert($tablename,&$data,&$fields,$debug)
   {
			global $DB;
			$s = sizeof($fields);
			$sql = "insert into \n ".$tablename." (";
			$keys = array_keys($data);
			$values = "(";
			$i1 = 0;
			for($i=0;$i<$s;$i++)
			{
				if($fields[$i][Key]=="PRI")
				{
					$id = $this->seed();
					$data[$fields[$i][Field]] = $id;
					$keys[] = $fields[$i][Field];
				}
				if(in_array($fields[$i][Field],$keys))
				{
					if($i1!=0)
					{
						$sql .= ",";
						$values .= ',';
					}
					$sql .= $fields[$i][Field];
					if ($_SESSION[dbtype] == "mssql")
						$values .= "'".str_replace("'","''",$data[$fields[$i][Field]])."'";
					else
						$values .= "'".addslashes($data[$fields[$i][Field]])."'";
					$i1++;
				}
			}
			$sql .= ")";
			$values .= ")";
			$sql .= "\n values ".$values;

			if($debug==1)
				$this->put($sql,1);
			else
				$this->put($sql);

			return $id;

   }

   function seed()
   {
			$this->put("update core_seed set seed_id = (seed_id)+1");
			return $this->get("SELECT seed_id FROM core_seed",4);
   }
}
?>
