<?php

	$WidgetCollection = Array();

	class TWidget extends TComponent
	{
		protected $FJSFile = '';
		protected $FCSSFile = ''; 
		var $ExternalJS = '';
		static $DefaultStyle = '';
		protected $FTheme = '';
		protected $FLeft = 0;
		protected $FTop = 0;
		protected $FWidth = 0;
		protected $FHeight = 0;
		protected $FVisible = true;
		protected $st_rendered = false;
		public $PopupMenu = '';
		var $CSSClass = '';
		var $Style = '';
		var $Data = null;
		
		function __construct($AParent)
		{
			parent::__construct($AParent);
			
			if (isset($AParent) && $AParent instanceof TWidget) $this->FTheme = $AParent->Theme;
				
			$this->FJSFile = dirname($this->FCodeFile).DIRECTORY_SEPARATOR.basename($this->FCodeFile, '.php');
			$this->FCSSFile = $this->FJSFile;
			$this->FJSFile .= '.js';		
			$this->FCSSFile .= '.css';	
			
			if (file_exists($this->FJSFile))
			{
				$this->ExternalJS = file_get_contents($this->FJSFile);
			}			
		}
		
		function get_JSFile()
		{
			return $this->FJSFile;
		}
		
		function get_CSSFile()
		{
			return $this->FCSSFile;
		}
		
		function setProperty($name, $value)
		{
			switch (strtolower($name))
			{
				case 'left':
					$this->Left = $value;
					break;
				case 'top':
					$this->Top = $value;
					break;
				case 'width':
					$this->Width = $value;
					break;
				case 'height':
					$this->Height = $value;
					break;
				case 'visible':
					if (strtolower(trim($value)) == 'false') $this->FVisible = false;
					break;
				case 'popupmenu':
					$this->PopupMenu = $value;
					break;
				case 'cssclass':
					$this->CSSClass = $value;
					break;
				case 'style':
					$this->Style = $value;
					break;
				default:
					parent::setProperty($name, $value);
					break;
			}
		}
		
		function get_Theme()
		{
			return $this->FTheme;
		}
		
		function set_Theme($value)
		{
			if (isset(TQuark::instance()->themes[$value])) $this->FTheme = $value;
			else $this->FTheme = 'default';
		}
		
		function get_Left()
		{
			return $this->FLeft;
		}
		
		function set_Left($value)
		{
			if ($this->st_rendered)
			{
				$id = $this->Name;
				if ($this->Parent != null) $id = $this->id; //  $id = $this->Parent->Name.'.'.$id;
			
				TQuark::instance()->addAjaxStack($id, 'setStyle', 'left: '.$value);
			}
				
			$this->FLeft = $value;
		}
		
		function get_Top()
		{
			return $this->FTop;
		}
		
		function set_Top($value)
		{
			if ($this->st_rendered)
			{
				$id = $this->Name;
				if ($this->Parent != null) $id = $this->id; //  $id = $this->Parent->Name.'.'.$id;
			
				TQuark::instance()->addAjaxStack($id, 'setStyle', 'top: '.$value);
			}
				
			$this->FTop = $value;
		}
		
		function get_Width()
		{
			return $this->FWidth;
		}
		
		function set_Width($value)
		{
			if ($this->st_rendered)
			{
				$id = $this->Name;
				if ($this->Parent != null) $id = $this->id; //  $id = $this->Parent->Name.'.'.$id;
	
				TQuark::instance()->addAjaxStack($id, 'setStyle', 'width: '.$value);
			}
			
			$this->FWidth = $value;
		}
		
		function get_Height()
		{
			return $this->FHeight;
		}
		
		function set_Height($value)
		{
			if ($this->st_rendered)
			{
				$id = $this->Name;
				if ($this->Parent != null) $id = $this->id; //  $id = $this->Parent->Name.'.'.$id;
	
				TQuark::instance()->addAjaxStack($id, 'setStyle', 'height: '.$value);
			}
			
			$this->FHeight = $value;
		}
		
		function get_Visible()
		{
			return $this->FVisible;
		}
		
		function set_Visible($value)
		{
			if ($this->st_rendered)
			{
				$id = $this->Name;
				if ($this->Parent != null) $id = $this->id; //  $id = $this->Parent->Name.'.'.$id;
	
				//TQuark::instance()->addAjaxStack('', 'alert', $value);
				switch ($this->FVisible)
				{
					case false:						
						if ((bool)$value == true) TQuark::instance()->addAjaxStack($id, 'setStyle', 'visibility: visible');
						break;
					case true:
						if ((bool)$value == false) TQuark::instance()->addAjaxStack($id, 'setStyle', 'visibility: hidden');
						break;				
				}
			}
			
			$this->FVisible = $value;		
		}
		
		function BringToFront()
		{
			
		}
		
		function SendToBack()
		{
			
		}

		function innerHTML()
		{
			$s = '';
			foreach ($this->Controls as $ctrl)
			{
				$s .= $ctrl->generateHTML();
			}	
			
			return $s;
		}
		
		function generateHTML()
		{
		}
		
		function innerJS()
		{
			$s = '';
			foreach ($this->Controls as $ctrl)
			{
				$s .= $ctrl->generateJS();
			}
				
			$js = '';
			if ($this->ExternalJS != '')
			{
				$id = $this->id; //'%parent%.'.$this->Name;
				
				$js =	'var jsfrm = getJSform(\'%parent%\');'."\n".
						'jsfrm.'.$this->ClassName.' = '.$this->ExternalJS.';'."\n".
						'jsfrm.'.$this->Name.' = new jsfrm.'.$this->ClassName.'("'.$id.'");'."\n".
						'var jsself = jsfrm.'.$this->Name.';'."\n";
			}
			
			return $s.$js;
		}
		
		function generateJS()
		{
			
		}
		
		function release()
		{
			
		}
		
		function setValue($value)
		{
			
		}
		
		function OnLoad()
		{
			
		}
	}
	
	
	function registerWidget($typename, $type)
	{
		global $WidgetCollection;
		
		$WidgetCollection[$typename] = $type;
	}
	
	function isWidget($typename)
	{
		global $WidgetCollection;
		
		if (isset($WidgetCollection[$typename])) return true;
		else return false;
	}
	
	function createWidget($typename)
	{
		global $WidgetCollection;
		
		return new $WidgetCollection[$typename]();
	}
		

?>