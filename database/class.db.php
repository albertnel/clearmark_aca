<?php
	class db
	{
		var $class;
		var $classfile;
		var $database;
		var $init = false;
		var $conf = "database/conf.db.php";
		var $config;

		function setup()
		{
			$this->class['mysql'] = "MySQLDB";
			$this->classfile['mysql'] = "class.mysqldb.php";
		}

		function db($conf = "database/conf.db.php")
		{
			$this->conf = $conf;
		}

		function initialize()
		{
			$this->setup();
			require($this->conf);
			$this->config = $config;

			$classfile = $this->classfile[$config[dbType]];
			$class = $this->class[$config[dbType]];
			require_once($classfile);
			$this->database = new $class($config[dbName],$config[dbUser],$config[dbPass],$config[dbHost],$config[dbPort]);
			$this->init = true;
		}

		function getcell($sql,$debug="0")
		{
			if (!$init) $this->initialize();
			return $this->get($sql,4,$debug);
		}

		function getrow($sql,$debug="0")
		{
			if (!$init) $this->initialize();
			return $this->get($sql,6,$debug);
		}

		function getcol($sql,$debug="0")
		{
			if (!$init) $this->initialize();
			return $this->get($sql,3,$debug);
		}

		function get($string,$mode="7",$debug="0")
		{
			if (!$init) $this->initialize();
			return $this->database->get($string,$mode,$debug);
		}

		function put($string,$debug=false)
		{
			if (!$init) $this->initialize();
			return $this->database->put($string,$debug);
		}

		function showtables($mode)
		{
			if (!$init) $this->initialize();
			return $this->database->showtables($mode);
		}

		function describeTable($table)
		{
			if (!$init) $this->initialize();
			return $this->database->describeTable($table);
		}

		function dbgetkey($fields)
		{
			$result = '';
			foreach($fields as $value)
			{
				$ak = array_keys($value);
				foreach($ak as $k)
				{
					if(strtoupper($k) == 'KEY' && $value[$k] == 'PRI')
						$result = $value[Field];
				}
			}
			return $result;
		}

		function dbinsert($tablename,&$data,&$fields,$debug)
		{
			return $this->database->insert($tablename,$data,$fields,$debug);
		}

		function dbupdate($tablename,&$data,&$fields,$debug)
		{
			$s = sizeof($fields);

			$sql = "update ".$tablename."\nset ";
			$keys = array_keys($data);
			$i1 = 0;
			for ($i=0;$i<$s;$i++)
			{
				if($fields[$i][Key]=="PRI")
				{
					$key = $fields[$i][Field];
				}
				if(in_array($fields[$i][Field],$keys)&&$fields[$i][Key]!="PRI")
				{
					if($i1!=0) $sql .= ",";
					if ($_SESSION[dbtype] == "mssql")
						$sql .= $fields[$i][Field]." = '".str_replace("'","''",$data[$fields[$i][Field]])."'";
					else
						$sql .= $fields[$i][Field]." = '".addslashes($data[$fields[$i][Field]])."'";

					$i1++;
				}
			}
			$sql .= "\nwhere ".$key." = '".addslashes($data[$key])."'";

			if($debug==1)
				$this->put($sql,1);
			else
				$this->put($sql);
		}

		function dbsave($tablename,&$data,$debug=0)
		{
			$fields = $this->describeTable($tablename);
			$key = $this->dbgetkey($fields);

			if($data[$key])
				$this->dbupdate($tablename,$data,$fields,$debug);
			else
				$this->dbinsert($tablename,$data,$fields,$debug);
		}

		function dbload($tablename,&$value,$key="")
		{
			$fields = $this->describeTable($tablename);
			if(!$key)	$key = $this->dbgetkey($fields);
			if(is_array($value)) $val = $value[$key];
			else $val = $value;
			$sql = "select * from ".$tablename." where ".$key." = '".addslashes($val)."'";
			$result = $this->get($sql,6);
			if(is_array($value))
			foreach($result as $key=>$val) $value[$key] = $val;
			return $result;
		}

		function dbdelete($tablename,$value,$key="")
		{
			$fields = $this->describeTable($tablename);
			if(!$key)	$key = $this->dbgetkey($fields);
			$sql = "delete from ".$tablename." where ".$key." = '".addslashes($value)."'";
			$this->put($sql);
		}

		function makeSeed($table_name)
		{
			return $this->seed();
		}
	}
?>