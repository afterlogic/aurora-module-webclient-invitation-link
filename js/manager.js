'use strict';

module.exports = function (oAppData, iUserRole, bPublic) {
	require('jquery.cookie');
	
	var
		_ = require('underscore'),
		$ = require('jquery'),
		ko = require('knockout'),
		
		Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Routing = require('%PathToCoreWebclientModule%/js/Routing.js'),
		
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		
		bAdminUser = iUserRole === Enums.UserRole.SuperAdmin,
		bAnonimUser = iUserRole === Enums.UserRole.Anonymous,
		
		aMagicLinks = []
	;
	
	Settings.init(oSettings);

	if (!bPublic && bAnonimUser)
	{
		return {
			start: function (ModulesManager) {
				App.subscribeEvent('StandardRegisterFormWebclient::ShowView::after', function (oParams) {
					if ('CRegisterView' === oParams.Name)
					{
						var aHashArray = Routing.getCurrentHashArray();
						if (aHashArray.length >= 2 && aHashArray[0] === Settings.RegisterModuleHash)
						{
							$.cookie('MagicLinkHash', aHashArray[1], { expires: 30 });
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
						oParams.Parameters.MagicLinkHash = $.cookie('MagicLinkHash');
					}
				});
			}
		};
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
								if (aMagicLinks[iId])
								{
									oParams.View.magicLink(aMagicLinks[iId]);
								}
								else
								{
									Ajax.send('%ModuleName%', 'GetMagicLinkHash', { 'UserId': iId }, function (oResponse) {
										if (oResponse.Result)
										{
											var sLink = Routing.getAppUrlWithHash([Settings.RegisterModuleHash, oResponse.Result]);
											oParams.View.magicLink(sLink);
											aMagicLinks[iId] = sLink;
										}
									});
								}
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
