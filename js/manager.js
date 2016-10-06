'use strict';

module.exports = function (oAppData) {
	require('jquery.cookie');
	
	var
		_ = require('underscore'),
		$ = require('jquery'),
		ko = require('knockout'),
		
		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
		Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),
		
		Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Routing = require('%PathToCoreWebclientModule%/js/Routing.js'),
		Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),
		UserSettings = require('%PathToCoreWebclientModule%/js/Settings.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		
		bAdminUser = App.getUserRole() === Enums.UserRole.SuperAdmin,
		bAnonimUser = App.getUserRole() === Enums.UserRole.Anonymous,
		
		aInvitationLinks = [],
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
	
	Settings.init(oSettings);

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
						Ajax.send('%ModuleName%', 'GetUserPublicId', { 'InvitationLinkHash': sInvitationLinkHash }, function (oResponse) {
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
		Ajax.send('%ModuleName%', 'GetUserPublicId', { 'InvitationLinkHash': sInvitationLinkHash }, function (oResponse) {
			if (oResponse.Result)
			{
				Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_LOGGED_IN'), 0);
			}
		});
		App.subscribeEvent('clearAndReloadLocation::before', function (oParams) {
			oParams.OnlyReload = true;
		});
	}
	
	if (bAdminUser)
	{
		return {
			start: function (ModulesManager) {
				App.subscribeEvent('AdminPanelWebclient::ConstructView::after', function (oParams) {
					if ('CEditUserView' === oParams.Name)
					{
						oParams.View.invitationLink = ko.observable('');
						oParams.View.bEnableSendInvitationLinkViaMail = Settings.EnableSendInvitationLinkViaMail;
					}
				});
				App.subscribeEvent('CCommonSettingsPaneView::onRoute::after', function (oParams) {
						var iId = Types.pInt(oParams.Id);
						if (iId > 0)
						{
							oParams.View.invitationLink(aInvitationLinks[iId] ? aInvitationLinks[iId] : '');
							if (aInvitationLinks[iId] !== '')
							{
								Ajax.send('%ModuleName%', 'GetInvitationLinkHash', { 'UserId': iId }, function (oResponse) {
									var sLink = oResponse.Result ? Routing.getAppUrlWithHash([Settings.RegisterModuleHash, oResponse.Result]) : '';
									oParams.View.invitationLink(sLink);
									aInvitationLinks[iId] = sLink;
								});
							}
						}
						else
						{
							oParams.View.invitationLink('');
						}
				});
			}
		};
	}
	
	return null;
};
