'use strict';

module.exports = function (oAppData, iUserRole, bPublic) {
	require('jquery.cookie');
	
	var
		_ = require('underscore'),
		$ = require('jquery'),
		ko = require('knockout'),
		
		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
		
		Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Routing = require('%PathToCoreWebclientModule%/js/Routing.js'),
		Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),
		UserSettings = require('%PathToCoreWebclientModule%/js/Settings.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		
		bAdminUser = iUserRole === Enums.UserRole.SuperAdmin,
		bAnonimUser = iUserRole === Enums.UserRole.Anonymous,
		
		aMagicLinks = [],
		aHashArray = Routing.getCurrentHashArray(),
		sMagicLinkHash = ''
	;
	
	Settings.init(oSettings);
	if (aHashArray.length >= 2 && aHashArray[0] === Settings.RegisterModuleHash)
	{
		sMagicLinkHash = aHashArray[1];
	}

	if (!bPublic && bAnonimUser)
	{
		return {
			start: function (ModulesManager) {
				App.subscribeEvent('StandardRegisterFormWebclient::ShowView::after', function (oParams) {
					if ('CRegisterView' === oParams.Name)
					{
						if (sMagicLinkHash !== '')
						{
							$.cookie('MagicLinkHash', sMagicLinkHash, { expires: 30 });
						}
						else
						{
							$.removeCookie('MagicLinkHash');
						}
					}
				});
				App.subscribeEvent('SendAjaxRequest::before', function (oParams) {
					if (oParams.Module === Settings.RegisterModuleName && oParams.Method === 'Register')
					{
						oParams.Parameters.MagicLinkHash = sMagicLinkHash;
					}
				});
				Ajax.send('%ModuleName%', 'GetUserName', { 'MagicLinkHash': sMagicLinkHash }, function (oResponse) {
					if (oResponse.Result)
					{
						App.broadcastEvent('ShowWelcomeRegisterText', { 'UserName': oResponse.Result, 'WelcomeText': TextUtils.i18n('%MODULENAME%/LABEL_WELCOME', {'USERNAME': oResponse.Result, 'SITE_NAME': UserSettings.SiteName}) });
					}
				});
			}
		};
	}
	
	$.removeCookie('MagicLinkHash');
	
	if (sMagicLinkHash !== '')
	{
		Ajax.send('%ModuleName%', 'GetUserName', { 'MagicLinkHash': sMagicLinkHash }, function (oResponse) {
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
						oParams.View.magicLink = ko.observable('');
						oParams.View.id.subscribe(function (iId) {
							if (iId > 0)
							{
								oParams.View.magicLink(aMagicLinks[iId] ? aMagicLinks[iId] : '');
								Ajax.send('%ModuleName%', 'GetMagicLinkHash', { 'UserId': iId }, function (oResponse) {
									var sLink = oResponse.Result ? Routing.getAppUrlWithHash([Settings.RegisterModuleHash, oResponse.Result]) : '';
									oParams.View.magicLink(sLink);
									aMagicLinks[iId] = sLink;
								});
							}
							else
							{
								oParams.View.magicLink('');
							}
						});
					}
				});
			}
		};
	}
	
	return null;
};
