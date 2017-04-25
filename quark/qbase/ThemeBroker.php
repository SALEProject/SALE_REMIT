<?php

	class TThemeBroker extends TPropertyClass
	{
		var $ThemeName = '';
		var $ThemeURL = '';
		var $BuildTime;
		var $ModifiedTime;
		
		function __construct($ThemeName, $ThemeURL)
		{
			$this->ThemeName = $ThemeName;
			$this->ThemeURL = $ThemeURL;
			
			$this->load();
		}
		
		function load()
		{
			//  obtain the build date of the cached version
			unset($this->BuildTime);
			if (is_dir('cache'))
			{
				$cachefile = 'cache'.DIRECTORY_SEPARATOR.$this->ThemeName.'css';
				if (file_exists($cachefile)) $this->BuildTime = filemtime($cachefile);
			}
			
			//  obtain the modify date of the source
			switch (strtolower($this->ThemeName))
			{
				case 'default':
					$this->ModifiedTime = $this->getDefaultModifiedTime();
					break;
				default:
					$this->ModifiedTime = $this->getModifiedTime();
					break;
			}			
		}
		
		function get_Style()
		{
			$cachefile = 'cache'.DIRECTORY_SEPARATOR.$this->ThemeName.'.css';
			if (isset($this->BuildTime) && isset($this->ModifiedTime) && $this->BuildTime >= $this->ModifiedTime)
			{
				if (file_exists($cachefile)) return file_get_contents($cachefile);
				else return '';
			}
			else
			{			
				$style = '';
				switch (strtolower($this->ThemeName))
				{
					case 'default':
						$style = $this->buildDefault();
						break;
					default:
						$style = $this->buildTheme();
						break;
				}
				
				file_put_contents($cachefile, $style);
				return $style;
			}
		}
		
		function getDefaultModifiedTime()
		{
			global $WidgetCollection;
			$result;
				
			if (is_array($WidgetCollection))
				foreach ($WidgetCollection as $widget)
				{
					$obj = new $widget(null);
					if ($obj != null) 
					{
						$filename = $obj->CodeFile;
						$time = filemtime($filename);
						if (!isset($result) || $time > $result) $result = $time;
					}
					$obj = null;
					unset($obj);
				}
			
			return $result;
		}
		
		function getModifiedTime()
		{
			$result;
			if (is_dir($this->ThemeURL))
			{
				$dir = new DirectoryIterator($this->ThemeURL);
				foreach ($dir as $file)
					if (!$file->isDot())
					{
						$time = $file->getMTime();
						if (!isset($result) || $time > $result) $result = $time;
					}				
			}
			
			return $result;
		}
		
		function buildDefault()
		{
			global $WidgetCollection;
			$defstyle = '';
			
			if (is_array($WidgetCollection))
				foreach ($WidgetCollection as $widget)
				{
					if (property_exists($widget, 'DefaultStyle'))
						$defstyle .= $widget::$DefaultStyle."\n";
				}
				
			return $defstyle;
		}
		
		function buildTheme()
		{
			$style = '';
			if (is_dir($this->ThemeURL))
			{
				$dir = new DirectoryIterator($this->ThemeURL);
				foreach ($dir as $file)
					if (!$file->isDot() && $file->getExtension() == 'css') 
					{
						$filename = $this->ThemeURL.DIRECTORY_SEPARATOR.$file->getFilename();
						if (file_exists($filename)) $style .= file_get_contents($filename);
					}
			}
			return $style;
		}
		
	}

?>