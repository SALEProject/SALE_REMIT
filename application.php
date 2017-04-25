<?php

	class TApplication
	{
		var $ApplicationDirectory = '';
		var $WorkingDirectory = '';
		var $contextID = 0;
		
		function __construct()
		{
			$reflector = new ReflectionClass(get_class($this));
			$str_derived = dirname($reflector->getFileName());
			$str_base = dirname($_SERVER['SCRIPT_FILENAME']);
			$s = $str_derived;
			if (strpos($str_derived, $str_base) >= 0) $s = substr($str_derived, strlen($str_base));
			$this->ApplicationDirectory = $s;
		}
		
		function CreateForm($url)
		{
			$filename = $url;
			if (!strpos($filename, $this->WorkingDirectory))
			{
				$filename = $this->WorkingDirectory.DIRECTORY_SEPARATOR.$filename;
			}
			
			if (file_exists($filename)) return TQuark::instance()->loadForm($filename, $this->contextID);
			
			return null;
		}
		
		function main()
		{
			
		}
		
		function processMessage($msg)
		{
			
		}
	}

?>