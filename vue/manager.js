export default {
  moduleName: 'InvitationLinkWebclient',

  requiredModules: [],

  init (appData) {},

  getUserOtherDataComponents () {
    return import('src/../../../InvitationLinkWebclient/vue/components/EditUserOtherData')
  },
}
