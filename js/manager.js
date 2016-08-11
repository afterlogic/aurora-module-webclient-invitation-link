'use strict';

module.exports = function (oAppData, iUserRole, bPublic) {
	var
		ko = require('knockout'),
		
		Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		
		bAdminUser = iUserRole === Enums.UserRole.SuperAdmin,
		
		aMagicLinks = []
	;

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
									Ajax.send('%ModuleName%', 'GetMagicLink', { 'UserId': iId }, function (oResponse) {
										if (oResponse.Result)
										{
											oParams.View.magicLink(oResponse.Result);
											aMagicLinks[iId] = oResponse.Result;
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
