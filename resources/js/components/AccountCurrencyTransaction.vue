<template>
    <div class="card">
        <h4 class="card-header">View Transaction</h4>
        <div class="card-body">
            <dl>
                <div class="row">
                    <dt class="col-sm-3">Id</dt>
                    <dd class="col-sm-9">{{ transaction.id }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Type</dt>
                    <dd class="col-sm-9">{{ typeCapitalized }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Purchase Description</dt>
                    <dd class="col-sm-9" v-html="transaction.purchase_description"></dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Total Price (USD)</dt>
                    <dd class="col-sm-9">${{ transaction.total_usd }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ friendlyStatus }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9">{{ transaction.created_at }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Completed</dt>
                    <dd class="col-sm-9">{{ transaction.completed_at }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Account Currency Quoted</dt>
                    <dd class="col-sm-9">{{ transaction.account_currency_quoted }}</dd>
                </div>

                <div class="row">
                    <dt class="col-sm-3">Account Currency Rewarded</dt>
                    <dd class="col-sm-9">{{ transaction.account_currency_rewarded }}</dd>
                </div>

                <div class="row" v-if="transaction.account_currency_rewarded_items">
                    <dt class="col-sm-3">Additional Account Currency Rewarded from Items</dt>
                    <dd class="col-sm-9">{{ transaction.account_currency_rewarded }}</dd>
                </div>
            </dl>
        </div>
    </div>
</template>

<script>
    export default {
        name: "account-currency-transaction",
        props: ['transaction'],
        computed: {
            typeCapitalized: function() {
                return this.transaction.type[0].toUpperCase() + this.transaction.type.slice(1);
            },
            friendlyStatus: function() {
                switch(this.transaction.status) {
                    case 'open': return 'Open';
                    case 'fulfilled': return 'Fulfilled';
                    case 'user_declined': return 'User declined transaction';
                    case 'vendor_refused': return "Payment attempted but wasn't accepted";
                    case 'expired': return "Timed out (Expired)";
                    case 'reprocess': return "Fulfillment failed and will be reattempted.";
                    default: return 'Unknown';
                }
            }
        }
    }
</script>

<style scoped>

</style>
