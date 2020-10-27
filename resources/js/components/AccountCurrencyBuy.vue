<template>
    <div class="card">
        <div class="text-center">You are buying for account {{ account }}.</div>
        <h4 class="card-header">Buy Mako</h4>
        <div class="card-body">
            <div class="row mb-2 justify-content-center">
                <div class="col-md-3" v-for="(gameCurrency, usd) in suggestedAmounts">
                    <div class="card border-primary">
                        <h3 class="card-header bg-primary text-dark">${{ usd }}</h3>
                        <div class="card-body text-center">
                            <img :src="accountCurrencyImage" alt="Account Currency Image">
                            <p class="card-text">{{ gameCurrency }} Mako </p>
                            <button @click="cardUseSuggestedAmount" :data-amount="usd" type="button"
                                    class="btn btn-primary btn-block">Select
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-2 justify-content-center">
                <div class="col-12 col-md-5 col-lg-3 text-center">
                    <label for="baseAmount">Amount</label>
                    <input id="baseAmount" style="width:5em;" type="number" v-model="baseAmount"
                           @change="baseAmountChanged" value="10" min="5" step="5">
                </div>
                <div class="col-12 col-md-5 col-lg-3 text-center">
                    <span class="text-warning" v-if="baseAmountExchangeError">{{ baseAmountExchangeError }}</span>
                    <span v-if="baseAmountExchange">You'll get {{ baseAmountExchange }} <img :src="accountCurrencyImage"
                                                                                             alt="Account Currency Image"></span>
                </div>
            </div>
            <div class="row mb-2 justify-content-center">
                <div class="col-12 col-md-6 text-center">
                    <label for="recurring">Make this a recurring payment?</label>
                    <input id="recurring" v-model="recurring" type="checkbox">
                </div>
            </div>
            <div class="row mb-2 justify-content-center" v-if="recurring.valueOf()">
                <div class="col-12 col-md-6 text-center">
                    <label for="recurringInterval">Recurring Interval</label>
                    <select v-model="recurringInterval" id="recurringInterval" class="custom-select">
                        <option value="7">Every 7 days</option>
                        <option value="14">Every 14 days</option>
                        <option value="30" selected>Every 30 days</option>
                        <option value="60">Every 60 days</option>
                        <option value="90">Every 90 days</option>
                        <option value="120">Every 120 days</option>
                        <option value="150">Every 150 days</option>
                        <option value="180">Every 180 days</option>
                        <option value="360">Every 360 days</option>
                    </select>
                </div>
            </div>
            <!-- Items -->
            <div>
                <h4>Add-on Items</h4>
                <p>Some items marked below reward supporter points. TODO: Add link to help page on supporter once help page exists.</p>
                <div v-for="item in itemCatalogue">
                    <div class="form-check">
                        <input class="form-check-input purchase-item-input"
                               :id="'item_' + item.code" type="checkbox" name="items" :value="item.code"
                               :data-item-code="item.code">
                        <label class="form-check-label font-weight-bold" :for="'item_' + item.code">
                            {{ item.name + ' - $' + item.amountUsd }}
                        </label>
                        <span v-if="item.supporter" class="badge badge-primary">Supporter</span>
                    </div>
                    <div class="mb-2">{{ item.description }}</div>
                </div>
                <div class="alert alert-danger" v-if="!itemCatalogue.length">
                    No items were found in the Item Catalogue, this is likely a loading error.
                </div>
            </div>
            <!-- Payment Controls -->
            <div v-if="defaultCardMaskedNumber">
                <div class="p-2 mb-2 bg-info text-white text-center">
                    If you pay by Card, your card ending in '{{ defaultCardMaskedNumber }}' will be used.
                </div>
            </div>
            <div v-else class="p-2 mb-2 bg-warning text-dark text-center"><span class="sr-only">Warning: </span>
                You have no default card configured and will need to use 'Manage Cards' if you wish to pay by card.
            </div>
            <div class="text-center">
                <a class="btn btn-secondary" :href="cardManagementPage" role="button">Manage Cards</a>
                <a v-if="defaultCardMaskedNumber" class="btn btn-primary" @click="startCardTransaction" href=""
                   role="button">via CreditCard</a>
                <a class="btn btn-primary" href="" @click="startPayPalTransaction" role="button">via PayPal</a>
            </div>
        </div>
        <dialog-approve-transaction id="approveTransactionModal"
                                    :transaction="transaction"
                                    @transaction-accepted="transactionAccepted"
                                    @transaction-declined="transactionDeclined"
        ></dialog-approve-transaction>
        <dialog-approve-transaction id="approveSubscriptionModal"
                                    :transaction="transaction"
                                    @transaction-accepted="subscriptionAccepted"
                                    @transaction-declined="subscriptionDeclined"
        ></dialog-approve-transaction>
        <dialog-message id="messageModal"
                        :content="message_dialog_content"
                        :header="message_dialog_header"
        ></dialog-message>
    </div>
</template>

<script>
import DialogApproveTransaction from "./DialogApproveTransaction";
import DialogMessage from "./DialogMessage";

export default {
    name: "account-currency-buy",
    components: {DialogApproveTransaction, DialogMessage},
    props: [
        'defaultCardMaskedNumber', 'account', 'suggestedAmounts',
        'cardManagementPage', 'accountCurrencyImage', 'itemCatalogue'
    ],
    data: function () {
        return {
            'recurring': false,
            'recurringInterval': '90',
            'baseAmount': 0,
            'baseAmountExchange': 0,
            'baseAmountExchangeError': '',
            'transaction': {
                'purchase': 'test'
            },
            'message_dialog_header': '',
            'message_dialog_content': ''

        }
    },
    methods: {
        buildPurchaseRequest: function () {
            let data = {
                'amountUsd': this.baseAmount
            }
            if (this.recurring) data.recurringInterval = this.recurringInterval;
            let items = $.map($('.purchase-item-input:checked'), function (item) {
                return $(item).data('item-code');
            });
            if (items.length > 0) data.items = items;
            return data;
        },
        cardUseSuggestedAmount: function (e) {
            this.baseAmount = e.currentTarget.getAttribute('data-amount');
            this.baseAmountChanged(e);
        },
        baseAmountChanged: function (e) {
            this.baseAmountExchange = 0;
            this.baseAmountExchangeError = '';
            axios.post('accountcurrency/fromUsd', {
                'amount': this.baseAmount
            }).then(response => {
                this.baseAmountExchange = response.data;
            }).catch(error => {
                this.baseAmountExchangeError = error.response.data.message;
            });
        },
        newTransaction: function (endpoint) {
            axios.post(endpoint, this.buildPurchaseRequest())
                .then(response => {
                    this.transaction = response.data;
                    $('#approveTransactionModal').modal();
                })
                .catch(error => {
                    this.message_dialog_header = 'Transaction Declined';
                    this.message_dialog_content = error.response.data;
                    $('#messageModal').modal();
                });
        },
        newSubscription: function (endpoint) {
            axios.post(endpoint, this.buildPurchaseRequest())
                .then(response => {
                    this.transaction = response.data;
                    $('#approveSubscriptionModal').modal();
                })
                .catch(error => {
                    this.message_dialog_header = 'Subscription Declined';
                    this.message_dialog_content = error.response.data;
                    $('#messageModal').modal();
                });
        },
        startCardTransaction: function (e) {
            e.preventDefault();
            if (this.recurring) this.newSubscription('accountcurrency/newCardSubscription');
            else this.newTransaction('accountcurrency/newCardTransaction');
        },
        startPayPalTransaction: function (e) {
            e.preventDefault();
            if (this.recurring) this.newSubscription('accountcurrency/newPayPalSubscription');
            else this.newTransaction('accountcurrency/newPayPalTransaction');
        },
        transactionAccepted: function (token) {
            //Redirect to accept page - it should redirect us as required.
            window.location = 'accountcurrency/acceptTransaction?token=' + token;
        },
        transactionDeclined: function (token) {
            //Notify the site but don't care about the result
            axios.post('accountcurrency/declineTransaction', {'token': token});
        },
        subscriptionAccepted: function (token) {
            //Redirect to accept page - it should redirect us as required.
            window.location = 'accountcurrency/acceptSubscription?token=' + token;
        },
        subscriptionDeclined: function (token) {
            //Notify the site but don't care about the result
            axios.post('accountcurrency/declineSubscription', {'token': token});
        }

    }
}
</script>

<style scoped>

</style>
