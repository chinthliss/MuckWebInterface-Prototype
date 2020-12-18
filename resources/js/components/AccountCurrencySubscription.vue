<template>
    <div class="card" v-if="this.subscription">
        <h4 class="card-header">View Subscription</h4>
        <div class="card-body">
            <dl>
                <div class="row">
                    <dt class="col-sm-3">Id</dt>
                    <dd class="col-sm-9">{{ subscription.id }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Type</dt>
                    <dd class="col-sm-9">{{ typeCapitalized }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Amount (USD)</dt>
                    <dd class="col-sm-9">${{ subscription.amount_usd }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Recurring Interval (Days)</dt>
                    <dd class="col-sm-9">{{ subscription.recurring_interval }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ friendlyStatus }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9">{{ subscription.created_at }}</dd>
                </div>

                <div v-if="subscription.last_charge_at" class="row">
                    <dt class="col-sm-3">Last Charge</dt>
                    <dd class="col-sm-9">{{ subscription.last_charge_at }}</dd>
                </div>

                <div v-if="subscription.next_charge_at" class="row">
                    <dt class="col-sm-3">Next Charge</dt>
                    <dd class="col-sm-9">{{ subscription.next_charge_at }} (Estimated)</dd>
                </div>

                <div v-if="subscription.closed_at" class="row">
                    <dt class="col-sm-3">Closed</dt>
                    <dd class="col-sm-9">{{ subscription.closed_at }}</dd>
                </div>

            </dl>
        </div>
    </div>
</template>

<script>
    export default {
        name: "account-currency-subscription",
        props: ['subscription'],
        computed: {
            typeCapitalized: function() {
                return this.subscription.type[0].toUpperCase() + this.subscription.type.slice(1);
            },
            friendlyStatus: function() {
                switch(this.subscription.status) {
                    case 'approval_pending': return 'Approval Pending';
                    case 'user_declined': return 'User declined';
                    case 'active': return "Active";
                    case 'suspended': return "Suspended";
                    case 'cancelled': return "Cancelled";
                    case 'expired': return "Expired";
                    default: return 'Unknown';
                }
            }
        }
    }
</script>

<style scoped>

</style>
