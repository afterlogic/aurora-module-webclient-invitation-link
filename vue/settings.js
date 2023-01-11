import _ from 'lodash'

import typesUtils from 'src/utils/types'

class InvitationLinkSettings {
  constructor(appData) {
    const invitationLinkData = typesUtils.pObject(appData.InvitationLinkWebclient)
    if (!_.isEmpty(invitationLinkData)) {
      this.enableSendInvitationLinkViaMail = !!invitationLinkData.EnableSendInvitationLinkViaMail
    }
  }
}

let settings = null

export default {
  init(appData) {
    settings = new InvitationLinkSettings(appData)
  },
  isEnableSendInvitationLinkViaMail() {
    return settings?.enableSendInvitationLinkViaMail
  },
}
