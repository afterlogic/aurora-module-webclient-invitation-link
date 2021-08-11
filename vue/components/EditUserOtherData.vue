<template>
  <div v-if="!createMode && link !== ''">
    <div class="row q-mt-lg">
      <div class="col-2 q-my-sm" v-t="'INVITATIONLINKWEBCLIENT.LABEL_LINK'"></div>
      <div class="col-5 q-my-sm"><b>{{ link }}</b></div>
    </div>
    <div class="row q-mt-md">
      <div class="col-2 q-my-sm"></div>
      <div class="col-10">
        <q-item-label caption v-t="'INVITATIONLINKWEBCLIENT.INFO_PASS_LINK_TO_USER'" />
      </div>
    </div>
    <div class="row q-mt-md">
      <div class="col-2 q-my-sm"></div>
      <div class="col-5">
        <q-btn :loading="resending" unelevated no-caps dense class="q-px-sm" :ripple="false" color="primary"
               :label="$t('INVITATIONLINKWEBCLIENT.ACTION_RESEND')" @click="resend">
        </q-btn>
      </div>
    </div>
  </div>
</template>

<script>
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import settings from 'src/settings'

export default {
  name: 'EditUserInvitationLinkData',

  props: {
    user: Object,
    createMode: Boolean,
    currentTenantId: Number,
  },

  data () {
    return {
      resending: false,
    }
  },

  computed: {
    hash () {
      const invitationLinks = this.$store.getters['invitationlink/getInvitationLinks']
      return typesUtils.pString(invitationLinks[this.user?.id]?.hash)
    },

    link () {
      return this.hash !== '' ? settings.getBaseUrl() + '#register/' + this.hash : ''
    },
  },

  watch: {
    user () {
      this.requestData()
    },
  },

  mounted () {
    this.requestData()
  },

  methods: {
    getSaveParameters () {
      return {}
    },

    /**
     * Method is used in parent component
     */
    hasChanges () {
      return false
    },

    /**
     * Method is used in parent component,
     * do not use async methods - just simple and plain reverting of values
     * !! hasChanges method must return true after executing revertChanges method
     */
    revertChanges () {},

    isDataValid () {
      return true
    },

    save () {
      this.$emit('save')
    },

    requestData () {
      if (this.user?.publicId) {
        this.$store.dispatch('invitationlink/requestInvitationLink', {
          tenantId: this.user.tenantId,
          userId: this.user.id,
          publicId: this.user.publicId
        })
      }
    },

    resend () {
      const parameters = {
        Email: this.user?.publicId,
        Hash: this.hash,
        TenantId: this.user?.tenantId,
      }
      this.resending = true
      webApi.sendRequest({
        moduleName: 'InvitationLinkWebclient',
        methodName: 'SendNotification',
        parameters,
      }).then(result => {
        this.resending = false
        if (result) {
          notification.showReport(this.$t('INVITATIONLINKWEBCLIENT.REPORT_SEND_LINK'))
        } else {
          notification.showError(this.$t('INVITATIONLINKWEBCLIENT.ERROR_SEND_LINK'))
        }
      })
    },
  },
}
</script>

<style scoped>

</style>
