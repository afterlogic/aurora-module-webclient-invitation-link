<template>
  <div v-if="!createMode && link !== ''">
    <div class="row q-mt-lg">
      <div class="col-1 q-my-sm" v-t="'INVITATIONLINKWEBCLIENT.LABEL_LINK'"></div>
      <div class="col-5">
        <q-input outlined dense class="bg-white" v-model="link" :disable="true" />
      </div>
    </div>
    <div class="row q-mt-md">
      <div class="col-1 q-my-sm"></div>
      <div class="col-10">
        <q-item-label caption v-t="'INVITATIONLINKWEBCLIENT.INFO_PASS_LINK_TO_USER'" />
      </div>
    </div>
    <div class="row q-mt-md">
      <div class="col-1 q-my-sm"></div>
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
import urlUtils from 'src/utils/url'
import webApi from 'src/utils/web-api'

export default {
  name: 'EditUserInvitationLinkData',

  props: {
    user: Object,
    createMode: Boolean,
    currentTenantId: Number,
  },

  data () {
    return {
      hash: '',
      link: '',
      resending: false,
    }
  },

  watch: {
    user () {
      this.getData()
    },
  },

  mounted () {
    this.getData()
  },

  methods: {
    getSaveParameters () {
      return {}
    },

    hasChanges () {
      return false
    },

    isDataValid () {
      return true
    },

    save () {
      this.$emit('save')
    },

    getData () {
      this.link = ''
      if (this.user?.publicId) {
        const parameters = {
          UserId: this.user?.id,
          TenantId: this.user?.tenantId,
          Email: this.user?.publicId, // this parameter will be used im manager.js for just created users, server doesn't expect it
        }
        webApi.sendRequest({
          moduleName: 'InvitationLinkWebclient',
          methodName: 'GetInvitationLinkHash',
          parameters,
        }).then(result => {
          if (typesUtils.isNonEmptyString(result)) {
            this.hash = result
            this.link = urlUtils.getAppPath() + '#/register/' + result
          }
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
