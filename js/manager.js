'use strict';

module.exports = function (oAppData) {
	require('%PathToCoreWebclientModule%/js/vendors/jquery.cookie.js');
	
	var
		$ = require('jquery'),
		
		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
		
		Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Routing = require('%PathToCoreWebclientModule%/js/Routing.js'),
		Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),
		UserSettings = require('%PathToCoreWebclientModule%/js/Settings.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		
		bAnonimUser = App.getUserRole() === Enums.UserRole.Anonymous,
		
		fGetInvitationLinkHash = function () {
			var aHashArray = Routing.getCurrentHashArray();
			if (aHashArray.length >= 2 && aHashArray[0] === Settings.RegisterModuleHash)
			{
				return aHashArray[1];
			}
			return '';
		},
		sInvitationLinkHash = fGetInvitationLinkHash()
	;
	
	Settings.init(oAppData);

	if (!App.isPublic() && bAnonimUser)
	{
		return {
			start: function (ModulesManager) {
				App.subscribeEvent('StandardRegisterFormWebclient::ShowView::after', function (oParams) {
					if ('CRegisterView' === oParams.Name)
					{
						sInvitationLinkHash = fGetInvitationLinkHash();
						if (sInvitationLinkHash !== '')
						{
							$.cookie('InvitationLinkHash', sInvitationLinkHash, { expires: 30 });
						}
						else
						{
							$.removeCookie('InvitationLinkHash');
						}
						Ajax.send(Settings.ServerModuleName, 'GetUserPublicId', { 'InvitationLinkHash': sInvitationLinkHash }, function (oResponse) {
							if (oResponse.Result)
							{
								App.broadcastEvent('ShowWelcomeRegisterText', { 'UserName': oResponse.Result, 'WelcomeText': TextUtils.i18n('%MODULENAME%/INFO_WELCOME', {'USERNAME': oResponse.Result, 'SITE_NAME': UserSettings.SiteName}) });
							}
							else
							{
								Screens.showError(TextUtils.i18n('%MODULENAME%/REPORT_INVITATION_LINK_INCORRECT'), true);
								Routing.setHash([Settings.LoginModuleHash]);
							}
						});
					}
				});
				App.subscribeEvent('SendAjaxRequest::before', function (oParams) {
					if (oParams.Module === Settings.RegisterModuleName && oParams.Method === 'Register')
					{
						oParams.Parameters.InvitationLinkHash = sInvitationLinkHash;
					}
				});
			}
		};
	}
	
	$.removeCookie('InvitationLinkHash');
	
	if (sInvitationLinkHash !== '')
	{
		Ajax.send(Settings.ServerModuleName, 'GetUserPublicId', { 'InvitationLinkHash': sInvitationLinkHash }, function (oResponse) {
			if (oResponse.Result)
			{
				Screens.showError(TextUtils.i18n('%MODULENAME%/REPORT_LOGGED_IN'), true);
			}
		});
		App.subscribeEvent('clearAndReloadLocation::before', function (oParams) {
			oParams.OnlyReload = true;
		});
	}
	
	return null;
};
