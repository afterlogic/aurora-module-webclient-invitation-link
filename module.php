<?php

class MagicLinkWebclientModule extends AApiModule
{
	protected $oMinModuleDecorator;
	
	protected $aRequireModules = array(
		'Min'
	); 
	
	/**
	* Returns Min module decorator.
	* 
	* @return \CApiModuleDecorator
	*/
	private function getMinModuleDecorator()
	{
		if ($this->oMinModuleDecorator === null)
		{
			$this->oMinModuleDecorator = \CApi::GetModuleDecorator('Min');
		}
		
		return $this->oMinModuleDecorator;
	}
		
	
	public function init()
	{
		$this->includeTemplate('AdminPanelWebclient_EditUserView', 'Edit-User-After', 'templates/MagicLinkView.html', $this->sName);
	}
	
	public function GetMagicLink($UserId)
	{
		$mHash = '';
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$sMinId = implode('|', array($UserId, md5($UserId)));
			$mHash = $oMin->GetMinById($sMinId);

			if (!$mHash)
			{
				$mHash = $oMin->CreateMin($sMinId, array($UserId));
			}
			else 
			{
				if (isset($mHash['__hash__']))
				{
					$mHash = $mHash['__hash__'];
				}
			}
		}
		
		$oAuthenticatedUser = \CApi::getAuthenticatedUser();
		if (empty($oAuthenticatedUser) || $oAuthenticatedUser->Role !== \EUserRole::SuperAdmin)
		{
			return '';
		}
		
		return \api_Utils::GetAppUrl() . '?magic-link=' . $mHash;
	}
}
