<?php

	class Tfrm_ControlPanel extends TForm
	{
		function OnLoad()
		{
		}
		
		function pmItem1OnClick()
		{
			TQuark::instance()->addAjaxStack('', 'alert', 'item 1');
		}
		
		function pmItem2OnClick()
		{
			TQuark::instance()->addAjaxStack('', 'alert', 'item 2');
		}
		
		function pmItem3OnClick()
		{
			TQuark::instance()->addAjaxStack('', 'alert', 'item 3');
		}
		
	}
	
?>
