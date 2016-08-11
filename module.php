<?php

class MagicLinkWebclientModule extends AApiModule
{
	public function init()
	{
		$this->includeTemplate('AdminPanelWebclient_EditUserView', 'Edit-User-After', 'templates/MagicLinkView.html', $this->sName);
	}
	
	public function GetMagicLink($UserId)
	{
		$oAuthenticatedUser = \CApi::getAuthenticatedUser();
		if (empty($oAuthenticatedUser) || $oAuthenticatedUser->Role !== \EUserRole::SuperAdmin)
		{
			return '';
		}
		
		return \api_Utils::GetAppUrl() . '?magic-link=' . md5($UserId);
	}
}
