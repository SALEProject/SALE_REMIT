<?php

	require_once 'structfile.php';

	class TRegistry
	{
		var $DataObject = null;
		
		function __construct()
		{
			$this->DataObject = new TStructFile('registry');
		}
		
		
	}
	
	
?>