<?php

	class TPanel extends TWidget
	{
		static $DefaultStyle = "
div.default_TPanel
{
	background-color: #dddddd;
	border: 1px solid white;
	/*border-radius: 10px;*/
	/*box-shadow: 0px 0px 10px #888888;*/
	overflow: hidden;	
}

				";
		var $Align = 'alNone';
		
		function __construct($AParent)
		{
			parent::__construct($AParent);
			$this->Left = 0;
			$this->Top = 0;
			$this->Width = 128;
			$this->Height = 128;
		}

		function setProperty($name, $value)
		{
			switch (strtolower($name))
			{
				case 'align':
					$this->Align = $value;
					break;
				default:
					parent::setProperty($name, $value);
					break;
			}
		}

		function generateHTML()
		{
			$s = $this->innerHTML();
			
			$class = '';
			if ($this->Theme != '') $class = $this->Theme.'_'.$this->ClassName;
						
			$style = 	'display: block; '.
						'position: absolute; '.
						'width: '.$this->Width.'px; '.
						'height: '.$this->Height.'px;';
			
			switch (strtolower($this->Align))
			{
				case 'alnone':
					$style .=	'left: '.$this->Left.'px; '.
								'top: '.$this->Top.'px; ';				
					break;
				case 'altop':
					$style .=	'left: 0px; top: 0px; right: 0px; min-width: 100%; ';				
					break;
				case 'alleft':
					$style .=	'left: 0px; top: 0px; bottom: 0px; min-height: 100%; ';				
					break;
				case 'alright':
					$style .=	'top: 0px; right: 0px; bottom: 0px; min-height: 100%; ';				
					break;
				case 'albottom':
					$style .=	'left: 0px; right: 0px; bottom: 0px; min-width: 100%; ';				
					break;
				case 'alclient':
					$style .=	'left: 0px; top: 0px; right: 0px; bottom: 0px; min-width: 100%; min-height: 100%; ';				
					break;
			}
			
			if (!$this->FVisible) $style.= 'visibility: hidden; ';
			
			$this->st_rendered = true;
			
			return '<div id="'.$this->id.'" class="'.$class.'" style="'.$style.'">'.$s.'</div>';
		}
		
		function generateJS()
		{
			$s = $this->innerJS();
			
			return $s;
		}
		
	} 
	
	registerWidget('TPanel', 'TPanel');
	
?>