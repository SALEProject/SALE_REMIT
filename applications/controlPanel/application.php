<?php

	class TControlPanelApp extends TApplication
	{
		function main()
		{
			$frm = $this->CreateForm('frm_ControlPanel.xml');
			if ($frm != null) $frm->show();
		}
	}

?>