import Vue from 'vue'

import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

export default {
  namespaced: true,
  state: {
    invitationLinks: {},
  },
  mutations: {
    setInvitationLink (state, { tenantId, userId, publicId, hash }) {
      Vue.set(state.invitationLinks, userId, { tenantId, userId, publicId, hash })
    },
  },
  actions: {
    requestInvitationLink ({ state, commit }, { tenantId, userId, publicId }) {
      if (!state.invitationLinks[userId]) {
        const parameters = {
          TenantId: tenantId,
          UserId: userId,
          Email: publicId, // this parameter will be used im manager.js for just created users, server doesn't expect it
        }
        webApi.sendRequest({
          moduleName: 'InvitationLinkWebclient',
          methodName: 'GetInvitationLinkHash',
          parameters,
        }).then(result => {
          if (typesUtils.isNonEmptyString(result)) {
            commit('setInvitationLink', { tenantId, userId, publicId, hash: result })
          }
        })
      }
    },
  },
  getters: {
    getInvitationLinks (state) {
      return state.invitationLinks
    },
  },
}
