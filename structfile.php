<?php

	
	class TStructFile
	{
		var $filename = '';
		var $data = null;
		var $diffs = array();
		var $latest	= null;
		var $latestRevision = 0;
		var $revisions = array();
		var $currentRevision = 0;
		
		var $currentPath = '';
		var $currentKey = null;
		
		function __construct($filename)
		{
			$this->filename = $filename;
			if (!is_dir($filename)) mkdir($filename);
			
			$this->checkRevisionNumber();
		}
		
		function checkRevisionNumber()
		{
			$this->currentRevision = 0;
			
			if (!isset($this->filename) || $this->filename == '') return false;
			if (!is_dir($this->filename)) mkdir($this->filename);
			
			$dir_data = new DirectoryIterator($this->filename);
			foreach ($dir_data as $file)
			if (!$file->isDot())
			{
				$name = $file->getFilename();
				
				if ($name != 'data.json') 
				{
					$idx_diff = strpos($name, 'diff');
					$idx_php = strpos($name, '.php');
					if ($idx_diff >= 0 && $idx_php >= 0)
					{
						$s = substr($name, 4, $idx_php - 4);
						$k = strpos($s, '_');
						if ($k >= 0) $s = substr($s, 0, $k);
					
						if ((int) $s > $this->currentRevision) $this->currentRevision = (int) $s;
					}
				}
			}				
		}
		
 		function buildLatest()
		{
			$this->latestRevision = 0;
			
			if (!isset($this->filename) || $this->filename == '') return false;
			if (!is_dir($this->filename)) mkdir($this->filename);

			$dir_data = new DirectoryIterator($this->filename);
			foreach ($dir_data as $file)
			if (!$file->isDot())
			{
				$name = $file->getFilename();
				
				if ($name == 'data.json') $this->data = json_decode(file_get_contents($filename.DIRECTORY_SEPARATOR.$name));
				else
				{
					$idx_diff = strpos($name, 'diff');
					$idx_php = strpos($name, '.php');
					if ($idx_diff >= 0 && $idx_php >= 0)
					{
						$s = substr($name, 4, $idx_php - 4);
						$k = strpos($s, '_');
						if ($k >= 0) $s = substr($s, 0, $k);
					
						$this->diffs[(int) $s] = file_get_contents($filename.DIRECTORY_SEPARATOR.$name);
						if ((int) $s > $this->latestRevision) $this->latestRevision = (int) $s;
					}
				}
			}

			ksort($this->diffs);
				
			if (!isset($this->data)) $this->data = new stdClass();

			//  build the actual latest data block
			if (isset($this->latest))
			{
				unset($this->latest);
				$this->latest = null;
			}
			
			$this->latest = clone $this->data;
			foreach ($this->diffs as $diff)
			{
				try 
				{
					eval($diff);
				}
				catch (Exception $e)
				{
					
				}
			}				
		}
		
		function setPath($path)
		{
			if ($path == $this->currentPath) return;
				
			$trace = explode('.', $path);
			
			$this->currentKey = $this->latest;
			foreach ($trace as $element)
			{
				if (isset($this->currentKey->$element)) $this->currentKey = $this->currentKey->$element;
				else 
				{
					$this->currentKey->$element = new stdClass();			
					$this->currentKey = $this->currentKey->$element;
				}
			}
		}
		
		function set($key, $value, $incrementRevision = true)
		{			
			if ($pos = strrpos($key, '.'))
			{
				$path = substr($key, 0, $pos);
				$key = substr($key, $pos + 1);
				$this->setPath($path);
			}
				
			$this->currentKey->$key = $value;
			
			//  build the diff command
			if ($incrementRevision)
			{
				if (isset($path)) $key = $path.'.'.$key;
				$key = '"'.addslashes($key).'"';
				if (is_string($value)) $value = '"'.addslashes($value).'"';
						
				$cmd = '$this->set('.$key.', '.$value.', false);';
				$this->revisions[] = $cmd;
			}
		}
		
		function save()
		{
			$sid = session_id();
			if ($sid == '') 
			{
				session_start();
				$sid = session_id();
			}
			
			if ($this->filename == '') return;
			$filename = $this->filename;
			
			if (!is_dir($filename)) mkdir($filename);
						
			$i = $this->currentRevision;
			foreach ($this->revisions as $revision) 
			{
				echo $revision;
				file_put_contents($filename.DIRECTORY_SEPARATOR.'diff'.$i.'_'.$sid.'.php', $revision);
				$i++;
			}
		}
	}

?>
