<template>
    <div class="card">
        <h4 class="card-header">Account</h4>
        <div class="card-body">
            <dl>
                <div class="row">
                    <dt class="col-sm-2">Created</dt>
                    <dd class="col-sm-10">{{ accountCreated }}</dd>
                </div>
                <div class="row">
                    <dt class="col-sm-2">Subscription</dt>
                    <dd class="col-sm-10">{{ overallSubscriptionStatus() }}</dd>
                </div>
            </dl>

            <!-- Subscriptions -->
            <div v-if="subscriptions.length > 0">
                <h5 class="mt-2">Subscription</h5>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Amount (USD)</th>
                        <th scope="col">Interval (days)</th>
                        <th scope="col">Next (approx)</th>
                        <th scope="col">Status</th>
                        <th scope="col"></th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tr v-for="subscription in subscriptions">
                        <td class="align-middle">{{ subscription.type }}</td>
                        <td class="align-middle">${{ subscription.amount_usd }}</td>
                        <td class="align-middle">{{ subscription.recurring_interval }}</td>
                        <td class="align-middle">{{ outputCarbonString(subscription.next_charge_at) }}</td>
                        <td class="align-middle">{{ friendlySubscriptionStatus(subscription.status) }}</td>
                        <td class="align-middle"><a :href="subscription.url">
                            <i class="fas fa-search"></i>
                        </a></td>
                        <td class="align-middle">
                            <button class="btn btn-secondary" v-if="subscription.status === 'active'"
                                    @click="cancelSubscription(subscription.id)">Cancel
                            </button>
                        </td>
                    </tr>
                </table>
                <p>Payments made via subscriptions show on the Account Transactions page.</p>
            </div>

            <!-- Emails -->
            <div>
                <h5 class="mt-2">Emails</h5>
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center">Primary?</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Verified</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(details, email) in emails">
                        <td class="align-middle">{{ email }}</td>
                        <td class="text-center align-middle">
                            <span v-if="email === primaryEmail" class="text-muted">Primary</span>
                            <button v-else class="btn btn-secondary" @click="verifyUseEmail(email)">Make Primary
                            </button>
                        </td>
                        <td class="align-middle">{{ details.created_at }}</td>
                        <td class="align-middle">{{ details.verified_at }}</td>
                    </tr>
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        <div class="btn-toolbar" role="group" aria-label="Account Controls">
                            <a class="btn btn-secondary mt-1 ml-1" href="/account/changepassword">Change Password</a>
                            <a class="btn btn-secondary mt-1 ml-1" href="/account/changeemail">Change to new Email</a>
                            <a class="btn btn-secondary mt-1 ml-1" href="/account/cardmanagement">Card Management</a>
                            <a class="btn btn-secondary mt-1 ml-1" href="/accountcurrency/history">Account Transactions</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session settings -->
            <div>
                <h5 class="mt-2">Preferences</h5>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="hideAvatars"
                           v-model="hideAvatars" @change="hideAvatarsChanged">
                    <label class="form-check-label" for="hideAvatars">Hide Avatars</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="useFullWidth"
                           v-model="useFullWidth" @change="useFullWidthChanged">
                    <label class="form-check-label" for="useFullWidth">Use Full Screen Width for all pages</label>
                </div>
            </div>
        </div>

        <!-- Change Email Modal -->
        <div class="modal fade" id="changeEmailModal" tabindex="-1" role="dialog"
             aria-labelledby="changeEmailModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeEmailModalLabel">Change Email</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="changeEmailForm" action="/account/useexistingemail" method="POST">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="email" :value="this.changeEmailTo">
                        </form>
                        <p>Your primary email is the one where notifications are sent to.</p>
                        <p>If it hasn't been verified, you'll be prompted to verify it next.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" data-dismiss="modal" @click="useEmail">Change
                            Email
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Modal -->
        <dialog-message id="messageModal"
                        :content="message_dialog_content"
                        :header="message_dialog_header"
        ></dialog-message>

    </div>
</template>

<script>
import DialogMessage from "./DialogMessage";

export default {
    name: "auth-account",
    components: {DialogMessage},
    props: [
        'accountCreated', 'primaryEmail', 'emails', 'errors',
        'subscriptions', 'subscriptionActive', 'subscriptionRenewing', 'subscriptionExpires',
        'initialUseFullWidth', 'initialHideAvatars'
    ],
    data: function () {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            changeEmailTo: '',
            message_dialog_header: '',
            message_dialog_content: '',
            useFullWidth: this.initialUseFullWidth,
            hideAvatars: this.initialHideAvatars
        }
    },
    methods: {
        verifyUseEmail: function (email) {
            this.changeEmailTo = email;
            $('#changeEmailModal').modal();
        },
        useEmail: function () {
            $('#changeEmailForm').submit();
        },
        friendlySubscriptionStatus: function (status) {
            switch (status) {
                case 'user_declined':
                    return 'Never Accepted';
                case 'approval_pending':
                    return 'Never Accepted';
                case 'suspended':
                    return 'Suspended';
                case 'cancelled':
                    return 'Cancelled';
                case 'expired':
                    return 'Expired';
                case 'active':
                    return 'Active';
            }
            return 'Unknown'
        },
        cancelSubscription: function (id) {
            axios({
                method: 'post',
                url: '/accountcurrency/cancelSubscription',
                data: {
                    'id': id
                }
            }).then(response => {
                //Reload to see change
                window.location.reload();
            }).catch(error => {
                this.message_dialog_header = 'Cancellation failed';
                this.message_dialog_content = `An error occurred, please notify staff. The error was:<br/> ${error}`;
                $('#messageModal').modal();
            });

        },
        overallSubscriptionStatus: function () {
            if (!this.subscriptionActive) return 'No Subscription';
            if (this.subscriptionRenewing) return 'Active, renews sometime before ' + this.subscriptionExpires;
            return 'Active, expires sometime before ' + this.subscriptionExpires;
        },
        useFullWidthChanged: function () {
            axios({
                method: 'post',
                url: '/account/updatePreference',
                data: {
                    'useFullWidth': this.useFullWidth
                }
            }).then(response => {
                //Reload to see change
                window.location.reload();
            }).catch(error => {
                this.message_dialog_header = 'Preference update failed';
                this.message_dialog_content = `An error occurred. The error was:<br/> ${error}`;
                $('#messageModal').modal();
            });
        },
        hideAvatarsChanged: function () {
            axios({
                method: 'post',
                url: '/account/updatePreference',
                data: {
                    'hideAvatars': this.hideAvatars
                }
            }).then(response => {
                //Reload to see change
                window.location.reload();
            }).catch(error => {
                this.message_dialog_header = 'Preference update failed';
                this.message_dialog_content = `An error occurred. The error was:<br/> ${error}`;
                $('#messageModal').modal();
            });
        },
        outputCarbonString: function (carbonString) {
            if (!carbonString) return '--';
            return new Date(carbonString).toLocaleString();
        }
    }
}
</script>

<style scoped>

</style>
