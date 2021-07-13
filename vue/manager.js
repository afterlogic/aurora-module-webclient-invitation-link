import _ from 'lodash'
import { i18n } from 'boot/i18n'

import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import eventBus from 'src/event-bus'

let justCreatedId = 0

const _getDbAdminSettingsPerUserHintComponent = (params) => {
  if (!_.isArray(params.components)) {
    params.components = []
  }
  params.components.push(import('./components/DbAdminSettingsPerUserHint'))
}

const _webApiResponseHandler = ({ moduleName, methodName, parameters, response }) => {
  if (moduleName === 'Core' && methodName === 'CreateUser' && response.Result) {
    justCreatedId = typesUtils.pInt(response.Result)
  }
  if (moduleName === 'InvitationLinkWebclient' && methodName === 'GetInvitationLinkHash' && response.Result) {
    const userId = parameters.UserId
    const hash = response.Result
    if (userId === justCreatedId) {
      justCreatedId = 0
      const sendNotificationParameters = {
        Email: parameters.Email,
        Hash: hash,
        TenantId: parameters.TenantId,
      }
      webApi.sendRequest({
        moduleName: 'InvitationLinkWebclient',
        methodName: 'SendNotification',
        parameters: sendNotificationParameters,
      }).then(result => {
        if (!result) {
          notification.showReport(i18n.tc('INVITATIONLINKWEBCLIENT.ERROR_AUTO_SEND_LINK'))
        }
      })
    }
  }
}

export default {
  moduleName: 'InvitationLinkWebclient',

  requiredModules: [],

  initSubscriptions (appData) {
    eventBus.$off('DbAdminSettingsPerUser::GetAdditionalComponents', _getDbAdminSettingsPerUserHintComponent)
    eventBus.$on('DbAdminSettingsPerUser::GetAdditionalComponents', _getDbAdminSettingsPerUserHintComponent)
    eventBus.$off('webApi::Response', _webApiResponseHandler)
    eventBus.$on('webApi::Response', _webApiResponseHandler)
  },

  getUserOtherDataComponents () {
    return import('./components/EditUserOtherData')
  },
}
