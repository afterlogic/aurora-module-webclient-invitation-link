<?php

class MagicLinkWebclientModule extends AApiModule
{
	protected $oMinModuleDecorator;
	
	protected $sRegisterModuleHash = '';
	
	protected $aRequireModules = array(
		'Min'
	); 
	
	protected $aSettingsMap = array(
		'RegisterModuleName' => array('StandardRegisterFormWebclient', 'string'),
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
	
	/**
	 * Returns register module hash.
	 * 
	 * @return string
	 */
	protected function getRegisterModuleHash()
	{
		if (empty($this->sRegisterModuleHash))
		{
			$oRegisterModuleDecorator = \CApi::GetModuleDecorator($this->getConfig('RegisterModuleName'));
			$oRegisterModuleSettings = $oRegisterModuleDecorator->GetAppData();
			$this->sRegisterModuleHash = $oRegisterModuleSettings['HashModuleName'];
		}
		return $this->sRegisterModuleHash;
	}
	
	/**
	 * Initializes module.
	 */
	public function init()
	{
		$this->setNonAuthorizedMethods(array('GetUserName'));
		
		$this->subscribeEvent('Register::before', array($this, 'onRegisterBefore'));
		$this->subscribeEvent('Register::after', array($this, 'onRegisterAfter'));
		
		$this->subscribeEvent('CreateOAuthAccount', array($this, 'onCreateOAuthAccount'));
		$this->subscribeEvent('Core::AfterDeleteUser', array($this, 'onAfterDeleteUser'));		
		
		$this->includeTemplate('AdminPanelWebclient_EditUserView', 'Edit-User-After', 'templates/MagicLinkView.html', $this->sName);
	}
	
	/**
	 * Returns module settings.
	 * 
	 * @return array
	 */
	public function GetAppData()
	{
		return array(
			'RegisterModuleHash' => $this->getRegisterModuleHash(),
			'RegisterModuleName' => $this->getConfig('RegisterModuleName'),
		);
	}
	
	/**
	 * Returns magic link hash for specified user.
	 * 
	 * @param int $UserId User identificator.
	 * @return string
	 */
	public function GetMagicLinkHash($UserId)
	{
		$mHash = '';
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$sMinId = implode('|', array($UserId, md5($UserId)));
			$mHash = $oMin->GetMinById($sMinId);

			if (!$mHash)
			{
				$mHash = $oMin->CreateMin(
						$sMinId, 
						array(
							'UserId' => $UserId
						)
				);
			}
			else 
			{
				if (isset($mHash['__hash__']) && !isset($mHash['Registered']))
				{
					$mHash = $mHash['__hash__'];
				}
				else
				{
					$mHash = '';
				}
			}
		}
		
		$oAuthenticatedUser = \CApi::getAuthenticatedUser();
		if (empty($oAuthenticatedUser) || $oAuthenticatedUser->Role !== \EUserRole::SuperAdmin)
		{
			return '';
		}
		
		return $mHash;
	}
	
	protected function getUserByMagicLinkHash($sMagicLinkHash)
	{
		$oUser = null;
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$mHash = $oMin->GetMinByHash($sMagicLinkHash);
			if (isset($mHash['__hash__'], $mHash['UserId']) && !isset($mHash['Registered']))
			{
				$iUserId = $mHash['UserId'];
				$oCore = \CApi::GetModuleDecorator('Core');
				if ($oCore)
				{
					$oUser = $oCore->GetUser($iUserId);
				}
			}
		}
		return $oUser;
	}
	
	/**
	 * Returns user for magic link from cookie.
	 * 
	 * @param \CUser $oUser
	 */
	public function onCreateOAuthAccount(&$oUser)
	{
		if (isset($_COOKIE['MagicLinkHash']))
		{
			$sMagicLinkHash = $_COOKIE['MagicLinkHash'];
			
			$oFoundUser = $this->getUserByMagicLinkHash($sMagicLinkHash);
			if (!empty($oFoundUser))
			{
				unset($_COOKIE['MagicLinkHash']);
				$oUser = $oFoundUser;
				
				$oMin = $this->getMinModuleDecorator();
				if ($oMin)
				{
					$mHash = $oMin->GetMinByHash($sMagicLinkHash);
					if (isset($mHash['__hash__'], $mHash['UserId']) && !isset($mHash['Registered']))
					{
						$mHash['Registered'] = true;
						$oMin->UpdateMinByHash($sMagicLinkHash, $mHash);
					}
				}
			}
		}
	}
	
	public function onRegisterBefore(&$aParams)
	{
		$sMagicLinkHash = $aParams['MagicLinkHash'];
		if (!empty($sMagicLinkHash))
		{
			$oUser = $this->getUserByMagicLinkHash($sMagicLinkHash);
			$aParams['UserId'] = $oUser->iId;
		}
	}
	
	public function onRegisterAfter(&$aParams)
	{
		$sMagicLinkHash = $aParams['MagicLinkHash'];
		if (!empty($sMagicLinkHash))
		{
			$oMin = $this->getMinModuleDecorator();
			if ($oMin)
			{
				$mHash = $oMin->GetMinByHash($sMagicLinkHash);
				if (isset($mHash['__hash__'], $mHash['UserId']) && !isset($mHash['Registered']))
				{
					$mHash['Registered'] = true;
					$oMin->UpdateMinByHash($sMagicLinkHash, $mHash);
				}
			}
		}
	}
	
	/**
	 * Deletes hash which are owened by the specified user.
	 * 
	 * @param int $iUserId User Identificator.
	 */	
	public function onAfterDeleteUser($iUserId)
	{
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$sMinId = implode('|', array($iUserId, md5($iUserId)));
			$oMin->DeleteMinByID($sMinId);
		}
	}
	
	/**
	 * Returns name of user obtained from magic link hash.
	 * @param string $MagicLinkHash Magic link hash with information about user and its registration status.
	 * @return string
	 */
	public function GetUserName($MagicLinkHash)
	{
		$oUser = $this->getUserByMagicLinkHash($MagicLinkHash);
		if ($oUser)
		{
			return $oUser->Name;
		}
		return '';
	}
}
