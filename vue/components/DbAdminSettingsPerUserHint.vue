<template>
  <div class="q-pa-lg" v-if="!loading && hasInvitationLink">
    <q-card flat bordered class="card-edit-settings">
      <q-card-section>
        <div class="row">
          <div class="col-10">
            <q-item-label class="color: text-negative" v-t="'INVITATIONLINKWEBCLIENT.INFO_ACCOUNT_PASSWORD'" />
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script>

export default {
  name: 'EditUserInvitationLinkData',

  props: {
    user: Object,
    loading: Boolean,
  },

  computed: {
    hasInvitationLink () {
      const invitationLinks = this.$store.getters['invitationlink/getInvitationLinks']
      return !!invitationLinks[this.user?.id]
    },
  },

  mounted () {
    this.requestData()
  },

  methods: {
    requestData () {
      if (this.user?.publicId) {
        this.$store.dispatch('invitationlink/requestInvitationLink', {
          tenantId: this.user.tenantId,
          userId: this.user.id,
          publicId: this.user.publicId
        })
      }
    },
  },
}
</script>

<style scoped>

</style>
