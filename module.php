<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 * @package Modules
 */

class InvitationLinkWebclientModule extends AApiModule
{
	protected $oMinModuleDecorator;
	
	protected $aRequireModules = array(
		'Min'
	);
	
	protected $aSettingsMap = array(
		'RegisterModuleName' => array('StandardRegisterFormWebclient', 'string'),
		'LoginModuleName' => array('StandardLoginFormWebclient', 'string'),
		'EnableSendInvitationLinkViaMail' => array(true, 'bool'),
		
		'NotificationType' => array('mail', 'string'),
		'NotificationEmail' => array('mail@localhost', 'string'),
		'NotificationHost' => array('localhost', 'string'),
		'NotificationPort' => array('25', 'string'),
		'NotificationUseAuth' => array(false, 'bool'),
		'NotificationLogin' => array('', 'string'),
		'NotificationPassword' => array('', 'string')
	);
	
	/***** private functions *****/
	/**
	 * Initializes module.
	 * 
	 * @ignore
	 */
	public function init()
	{
		$this->subscribeEvent('Register::before', array($this, 'onBeforeRegister'));
		$this->subscribeEvent('Register::after', array($this, 'onAfterRegister'));
		
		$this->subscribeEvent('AdminPanelWebclient::CreateUser::after', array($this, 'onAfterCreateUser'));

		$this->subscribeEvent('StandardAuth::CreateUserAccount::after', array($this, 'onAfterCreateUserAccount'));
		$this->subscribeEvent('InvitationLinkWebclient::CreateInvitationLinkHash', array($this, 'onCreateInvitationLinkHash'));
		
		$this->subscribeEvent('CreateOAuthAccount', array($this, 'onCreateOAuthAccount'));
		$this->subscribeEvent('Core::AfterDeleteUser', array($this, 'onAfterDeleteUser'));
		
		$this->includeTemplate('AdminPanelWebclient_EditUserView', 'Edit-User-After', 'templates/InvitationLinkView.html', $this->sName);
		$this->includeTemplate('StandardAuthWebclient_AccountsSettingsView', 'Edit-Standard-Account-After', 'templates/AccountPasswordHintView.html', $this->sName);
	}
	
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
		$oRegisterModuleDecorator = \CApi::GetModuleDecorator($this->getConfig('RegisterModuleName'));
		$oRegisterModuleSettings = $oRegisterModuleDecorator->GetSettings();
		return $oRegisterModuleSettings['HashModuleName'];
	}
	
	/**
	 * Returns login module hash.
	 * 
	 * @return string
	 */
	protected function getLoginModuleHash()
	{
		$oLoginModuleDecorator = \CApi::GetModuleDecorator($this->getConfig('LoginModuleName'));
		$oLoginModuleSettings = $oLoginModuleDecorator->GetSettings();
		return $oLoginModuleSettings['HashModuleName'];
	}
	
	/**
	 * Returns id for Min Module
	 * 
	 * @return string
	 */
	protected function generateMinId($iUserId)
	{
		return implode('|', array($this->GetName(), $iUserId, md5($iUserId)));
	}

	/**
	 * Returns user with identificator obtained from the Invitation link hash.
	 * 
	 * @param string $InvitationLinkHash Invitation link hash.
	 * @return \CUser
	 */
	protected function getUserByInvitationLinkHash($InvitationLinkHash)
	{
		$oUser = null;
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$mHash = $oMin->GetMinByHash($InvitationLinkHash);
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
	 * Writes to $aParams['UserId'] user identificator obtained from Invitation link hash.
	 * 
	 * @ignore
	 * @param array $aParams Is passed by reference.
	 */
	public function onBeforeRegister(&$aArgs, &$mResult)
	{
		if (!empty($aArgs['InvitationLinkHash']))
		{
			$oUser = $this->getUserByInvitationLinkHash($aArgs['InvitationLinkHash']);
			if ($oUser)
			{
				$aArgs['UserId'] = $oUser->iId;
			}
		}
	}
	
	/**
	 * Updates Invitation link hash in Min module.
	 * 
	 * @ignore
	 * @param array $aParams Is passed by reference.
	 */
	public function onAfterRegister($aArgs, &$mResult)
	{
		if (!empty($aArgs['InvitationLinkHash']))
		{
			$oMin = $this->getMinModuleDecorator();
			if ($oMin)
			{
				$mHash = $oMin->GetMinByHash($aArgs['InvitationLinkHash']);
				if (isset($mHash['__hash__'], $mHash['UserId']) && !isset($mHash['Registered']))
				{
					$mHash['Registered'] = true;
					$oMin->UpdateMinByHash($aArgs['InvitationLinkHash'], $mHash);
				}
			}
		}
	}
	
	/**
	 * Updates Invitation link hash in Min module for user with $aData['UserId'] identificator.
	 * 
	 * @ignore
	 * @param array $aData Is passed by reference.
	 */
	public function onAfterCreateUserAccount($aArgs, &$mResult)
	{
		$oMin = $this->getMinModuleDecorator();
		if (isset($aArgs['UserId']) && $oMin)
		{
			$mHash = $oMin->GetMinById(
				$this->generateMinId($aArgs['UserId'])
			);
			
			if (isset($mHash['__hash__'], $mHash['UserId']) && !isset($mHash['Registered']))
			{
				$mHash['Registered'] = true;
				$oMin->UpdateMinByHash($mHash['__hash__'], $mHash);
			}
		}
	}
	
	/**
	 * Writes to $oUser variable user object for Invitation link hash from cookie.
	 * 
	 * @ignore
	 * @param \CUser $oUser
	 */
	public function onCreateOAuthAccount($aArgs, &$oUser)
	{
		if (isset($_COOKIE['InvitationLinkHash']))
		{
			$InvitationLinkHash = $_COOKIE['InvitationLinkHash'];
			
			$oFoundUser = $this->getUserByInvitationLinkHash($InvitationLinkHash);
			if (!empty($oFoundUser))
			{
				unset($_COOKIE['InvitationLinkHash']);
				$oUser = $oFoundUser;
				
				$oMin = $this->getMinModuleDecorator();
				if ($oMin)
				{
					$mHash = $oMin->GetMinByHash($InvitationLinkHash);
					if (isset($mHash['__hash__'], $mHash['UserId']) && !isset($mHash['Registered']))
					{
						$mHash['Registered'] = true;
						$oMin->UpdateMinByHash($InvitationLinkHash, $mHash);
					}
				}
			}
		}
	}
	
	/**
	 * Updates Invitation link hash in Min module for user with $aData['UserId'] identificator.
	 * 
	 * @ignore
	 * @param array $aData Is passed by reference.
	 */
	public function onAfterCreateUser($aArgs, &$mResult)
	{
		$iUserId = isset($mResult) && (int) $mResult > 0 ? $mResult : 0;
		if (0 < $iUserId)
		{
			$sHash = $this->CreateInvitationLinkHash($iUserId);
			$mResult  = $sHash;
			if (!empty($sHash))
			{
				$aEventArgs = array(
					'PublicId' => $aArgs['PublicId'],
					'Hash' => $sHash
				);
				$this->broadcastEvent(
					'CreateInvitationLinkHash', 
					$aEventArgs
				);
			}
		}
	}	
	
	public function onCreateInvitationLinkHash($aArgs, &$mResult)
	{
		$mResult = $this->SendNotification($aArgs['PublicId'], $aArgs['Hash']);
	}
	
	/**
	 * Deletes hash which are owened by the specified user.
	 * 
	 * @ignore
	 * @param int $iUserId User Identificator.
	 */
	public function onAfterDeleteUser($aArgs, &$iUserId)
	{
		$this->getMinModuleDecorator()->DeleteMinByID(
			$this->generateMinId($iUserId)
		);
	}
	/***** private functions *****/
	
	/***** public functions might be called with web API *****/
	/**
	 * Obtaines list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return array(
			'RegisterModuleHash' => $this->getRegisterModuleHash(),
			'RegisterModuleName' => $this->getConfig('RegisterModuleName'),
			'LoginModuleHash' => $this->getLoginModuleHash(),
			'EnableSendInvitationLinkViaMail' => $this->getConfig('EnableSendInvitationLinkViaMail'),
		);
	}
	
	/**
	 * Create Invitation link hash for specified user.
	 * 
	 * @param int $UserId User identificator.
	 * @return string
	 */
	public function CreateInvitationLinkHash($UserId)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$mHash = '';
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$sMinId = $this->generateMinId($UserId);
			$aHashData = $oMin->GetMinById($sMinId);
			if (!$aHashData)
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
				$mHash = $this->GetInvitationLinkHash($UserId);
			}
		}
		
		return $mHash;
	}
	
	/**
	 * 
	 * @param string $Email
	 * @param string $Hash
	 */
	public function SendNotification($Email, $Hash)
	{
		$oSettings =& CApi::GetSettings();
		$sSiteName = $oSettings->GetConf('SiteName');
		$sBody = file_get_contents($this->GetPath().'/templates/InvitationMail.html');
		if (is_string($sBody)) 
		{
			$sBody = strtr($sBody, array(
				'{{INVITATION_URL}}' => rtrim($this->oHttp->GetFullUrl(), '\\/ ') . "/index.php#register/" . $Hash,
				'{{SITE_NAME}}' => $sSiteName
			));
		}
		$sSubject = "You're invited to join " . $sSiteName;
		$sFrom = $this->getConfig('NotificationEmail', '');
		
		$oMail = new PHPMailer();
		
		$sType = $this->getConfig('NotificationType', 'mail');
		if (strtolower($sType) === 'mail')
		{
			$oMail->isMail();                                      
		}
		else if (strtolower($sType) === 'smtp')
		{
			$oMail->isSMTP();                                      
			$oMail->Host = $this->getConfig('NotificationHost', '');
			$oMail->Port = 25;                                    
			$oMail->SMTPAuth = (bool) $this->getConfig('NotificationUseAuth', false);
			if ($oMail->SMTPAuth)
			{
				$oMail->Username = $this->getConfig('NotificationLogin', '');
				$oMail->Password = $this->getConfig('NotificationPassword', '');
			}
			$oMail->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			);			
		}
		
		$oMail->setFrom($sFrom);
		$oMail->addAddress($Email);
		$oMail->addReplyTo($sFrom, $sSiteName);

		$oMail->isHTML(true);                                  // Set email format to HTML

		$oMail->Subject = $sSubject;
		$oMail->Body    = $sBody;

		return $oMail->send();
	}
	
	/**
	 * Returns Invitation link hash for specified user.
	 * 
	 * @param int $UserId User identificator.
	 * @return string
	 */
	public function GetInvitationLinkHash($UserId)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$mHash = '';
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$sMinId = $this->generateMinId($UserId);
			$mHash = $oMin->GetMinById($sMinId);
			
			if ($mHash)
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
	
	/**
	 * Returns public id of user obtained from Invitation link hash.
	 * 
	 * @param string $InvitationLinkHash Invitation link hash with information about user and its registration status.
	 * @return string
	 */
	public function GetUserPublicId($InvitationLinkHash)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$oUser = $this->getUserByInvitationLinkHash($InvitationLinkHash);
		if ($oUser)
		{
			return $oUser->PublicId;
		}
		return '';
	}
	/***** public functions might be called with web API *****/
}
