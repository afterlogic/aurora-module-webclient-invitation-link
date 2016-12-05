'use strict';

module.exports = function (oAppData) {
	require('%PathToCoreWebclientModule%/js/vendors/jquery.cookie.js');
	
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
				var
					iId = 0,
					aInvitationLinks = {},
					aInvitationHashes = {},
					oInvitationView = null
				;
				App.subscribeEvent('StandardAuthWebclient::ConstructView::after', function (oParams) {
					if (oParams.Name === 'CAccountsSettingsView')
					{
						oParams.View.showPasswordRevokesInvitationHint = ko.observable(false);
					}
				});
				App.subscribeEvent('CAccountsSettingsView::onRoute::after', function (oParams) {
					iId = oParams.View.iUserId;
					oParams.View.showPasswordRevokesInvitationHint(!!aInvitationHashes[iId]);
					if (!aInvitationLinks[iId])
					{
						Ajax.send(Settings.ServerModuleName, 'GetInvitationLinkHash', { 'UserId': iId }, function (oResponse) {
							var sLink = oResponse.Result ? Routing.getAppUrlWithHash([Settings.RegisterModuleHash, oResponse.Result]) : '';
							aInvitationLinks[iId] = sLink;
							aInvitationHashes[iId] = oResponse.Result;
							oParams.View.showPasswordRevokesInvitationHint(!!aInvitationHashes[iId]);
						});
					}
				});
				App.subscribeEvent('AdminPanelWebclient::ConstructView::after', function (oParams) {
					if ('CEditUserView' === oParams.Name)
					{
						oParams.View.invitationLink = ko.observable('');
						oParams.View.bEnableSendInvitationLinkViaMail = Settings.EnableSendInvitationLinkViaMail;
						oParams.View.resendInvitationLink = function () {
							Ajax.send(Settings.ServerModuleName, 'SendNotification', { 'Email': oParams.View.publicId(), 'Hash': aInvitationHashes[iId] }, function (oResponse) {
								if (oResponse.Result)
								{
									Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_SEND_LINK'));
								}
								else
								{
									Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_SEND_LINK'));
								}
							});
						};
						oInvitationView = oParams.View;
					}
				});
				App.subscribeEvent('CCommonSettingsPaneView::onRoute::after', function (oParams) {
					iId = Types.pInt(oParams.Id);
					if (iId > 0)
					{
						oParams.View.invitationLink(aInvitationLinks[iId] ? aInvitationLinks[iId] : '');
						if (!aInvitationLinks[iId])
						{
							Ajax.send(Settings.ServerModuleName, 'GetInvitationLinkHash', { 'UserId': iId }, function (oResponse) {
								var sLink = oResponse.Result ? Routing.getAppUrlWithHash([Settings.RegisterModuleHash, oResponse.Result]) : '';
								oParams.View.invitationLink(sLink);
								aInvitationLinks[iId] = sLink;
								aInvitationHashes[iId] = oResponse.Result;
							});
						}
					}
					else
					{
						oParams.View.invitationLink('');
					}
				});
				App.subscribeEvent('ReceiveAjaxResponse::after', function (oParams) {
					var oResponse = oParams.Response;
					if (oInvitationView && oResponse.Module === 'AdminPanelWebclient' && oResponse.Method === 'CreateUser' && oResponse.Result)
					{
						Ajax.send(Settings.ServerModuleName, 'SendNotification', { 'Email': oInvitationView.publicId(), 'Hash': aInvitationHashes[iId] }, function (oResponse) {
							if (!oResponse.Result)
							{
								Screens.showReport(TextUtils.i18n('%MODULENAME%/ERROR_AUTO_SEND_LINK'));
							}
						});
					}
				});
			}
		};
	}
	
	return null;
};
