'use strict';

var
	_ = require('underscore'),
	
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: '%ModuleName%',
	
	RegisterModuleName: 'StandardRegisterFormWebclient',
	RegisterModuleHash: 'register',
	LoginModuleHash: 'login',
	EnableSendInvitationLinkViaMail: true,
	
	/**
	 * Initializes settings from AppData object sections.
	 * 
	 * @param {Object} oAppData Object contained modules settings.
	 */
	init: function (oAppData)
	{
		var oAppDataSection = oAppData['%ModuleName%'];
		
		if (!_.isEmpty(oAppDataSection))
		{
			this.RegisterModuleName = Types.pString(oAppDataSection.RegisterModuleName, this.RegisterModuleName);
			this.RegisterModuleHash = Types.pString(oAppDataSection.RegisterModuleHash, this.RegisterModuleHash);
			this.LoginModuleHash = Types.pString(oAppDataSection.LoginModuleHash, this.LoginModuleHash);
			this.EnableSendInvitationLinkViaMail = Types.pBool(oAppDataSection.EnableSendInvitationLinkViaMail, this.EnableSendInvitationLinkViaMail);
		}
	}
};
