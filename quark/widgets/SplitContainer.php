<?php

	class TSplitContainer extends TWidget
	{
		function __construct($AParent)
		{
			parent::__construct($AParent);
			
			$this->Left = 0;
			$this->Top = 0;
			$this->Width = 320;
			$this->Height = 240;
			
			$pnl_minus = new TPanel($this);
			//$pnl_minus = 
			$pnl_plus = new TPanel($this);
			$splitter = new TSplitter($this);
		}
		
		function setProperty($name, $value)
		{
			switch (strtolower($name))
			{
				default:
					parent::setProperty($name, $value);
					break;
			}
		}
		
		function generateHTML()
		{
			$html = $this->innerHTML();
			
			$id = '%parent%'.$this->Name;

			return	'<div id="'.$id.'">'."\n".
					$html.
					'</div>'."\n";
		}
		
		function generateJS()
		{
			
		}
		
	}
	
	registerWidget('TSplitContainer', 'TSplitContainer');
?>