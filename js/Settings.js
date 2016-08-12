'use strict';

var
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
	ServerModuleName: '%ModuleName%',
	RegisterModuleName: 'StandardRegisterFormWebclient',
	RegisterModuleHash: 'register',
	
	/**
	 * Initializes settings from AppData section.
	 * 
	 * @param {Object} oAppDataSection contains module settings from server.
	 */
	init: function (oAppDataSection) {
		if (oAppDataSection)
		{
			this.RegisterModuleName = Types.pString(oAppDataSection.RegisterModuleName);
			this.RegisterModuleHash = Types.pString(oAppDataSection.RegisterModuleHash);
		}
	}
};