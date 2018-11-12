<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\InvitationLinkWebclient;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractWebclientModule
{
	protected $oMinModuleDecorator;
	
	protected $aRequireModules = array(
		'Min'
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
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser();
		if (!empty($oUser) && $oUser->Role === \Aurora\System\Enums\UserRole::SuperAdmin)
		{
			$this->includeTemplate('StandardAuthWebclient_StandardAccountsSettingsFormView', 'Edit-Standard-Account-After', 'templates/AccountPasswordHintView.html', $this->sName);
		}
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
			$this->oMinModuleDecorator = \Aurora\Modules\Min\Module::Decorator();
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
		$sResult = null;
		$oRegisterModuleDecorator = \Aurora\System\Api::GetModuleDecorator($this->getConfig('RegisterModuleName'));
		if ($oRegisterModuleDecorator)
		{
			$oRegisterModuleSettings = $oRegisterModuleDecorator->GetSettings();
			$sResult = $oRegisterModuleSettings['HashModuleName'];
		}

		return $sResult;
	}
	
	/**
	 * Returns login module hash.
	 * 
	 * @return string
	 */
	protected function getLoginModuleHash()
	{
		$sResult = null;
		$oLoginModuleDecorator = \Aurora\System\Api::GetModuleDecorator($this->getConfig('LoginModuleName'));
		if ($oLoginModuleDecorator)
		{
			$oLoginModuleSettings = $oLoginModuleDecorator->GetSettings();
			$sResult = $oLoginModuleSettings['HashModuleName'];
		}

		return $sResult;
	}
	
	/**
	 * Returns id for Min Module
	 * 
	 * @return string
	 */
	protected function generateMinId($iUserId)
	{
		return \implode('|', array($this->GetName(), $iUserId, \md5($iUserId)));
	}

	/**
	 * Returns user with identifier obtained from the Invitation link hash.
	 * 
	 * @param string $InvitationLinkHash Invitation link hash.
	 * @return \Aurora\Modules\Core\Classes\User
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
				$oCore = \Aurora\Modules\Core\Module::Decorator();
				if ($oCore)
				{
					$oUser = $oCore->GetUser($iUserId);
				}
			}
		}
		return $oUser;
	}
	
	/**
	 * Writes to $aParams['UserId'] user identifier obtained from Invitation link hash.
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
				$aArgs['UserId'] = $oUser->EntityId;
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
	 * Updates Invitation link hash in Min module for user with $aData['UserId'] identifier.
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
		
//		$mResult = $aArgs;
	}
	
	/**
	 * Writes to $oUser variable user object for Invitation link hash from cookie.
	 * 
	 * @ignore
	 * @param \Aurora\Modules\Core\Classes\User $oUser
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
	 * Updates Invitation link hash in Min module for user with $aData['UserId'] identifier.
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
		$mResult = $this->sendNotification($aArgs['PublicId'], $aArgs['Hash']);
	}
	
	/**
	 * Deletes hash which are owened by the specified user.
	 * 
	 * @ignore
	 * @param int $iUserId User Identifier.
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
	 * Obtains list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
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
	 * @param int $UserId User identifier.
	 * @return string
	 */
	public function CreateInvitationLinkHash($UserId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
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
	protected function sendNotification($Email, $Hash)
	{
		$oSettings =& \Aurora\System\Api::GetSettings();
		$sSiteName = $oSettings->GetConf('SiteName');
		$sBody = \file_get_contents($this->GetPath().'/templates/InvitationMail.html');
		if (\is_string($sBody)) 
		{
			$sBody = \strtr($sBody, array(
				'{{INVITATION_URL}}' => \rtrim($this->oHttp->GetFullUrl(), '\\/ ') . "/index.php#register/" . $Hash,
				'{{SITE_NAME}}' => $sSiteName
			));
		}
		$sSubject = "You're invited to join " . $sSiteName;
		$sFrom = $this->getConfig('NotificationEmail', '');
		
		$oMail = new \PHPMailer();
		
		$sType = $this->getConfig('NotificationType', 'mail');
		if (\strtolower($sType) === 'mail')
		{
			$oMail->isMail();                                      
		}
		else if (\strtolower($sType) === 'smtp')
		{
			$oMail->isSMTP();                                      
			$oMail->Host = $this->getConfig('NotificationHost', '');
			$oMail->Port = (int) $this->getConfig('NotificationPort', 25);;                                    
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
	 * @param int $UserId User identifier.
	 * @return string
	 */
	public function GetInvitationLinkHash($UserId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
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
		
		$oAuthenticatedUser = \Aurora\System\Api::getAuthenticatedUser();
		if (empty($oAuthenticatedUser) || $oAuthenticatedUser->Role !== \Aurora\System\Enums\UserRole::SuperAdmin)
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
		$oUser = $this->getUserByInvitationLinkHash($InvitationLinkHash);
		if ($oUser)
		{
			return $oUser->PublicId;
		}
		return '';
	}
	/***** public functions might be called with web API *****/
}
