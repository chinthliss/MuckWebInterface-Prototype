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
                            <img :src="accountCurrencyImage" alt="Account Currency Image"><p class="card-text">{{ gameCurrency }} Mako </p>
                            <button @click="cardUseSuggestedAmount" :data-amount="usd" type="button"
                                    class="btn btn-primary btn-block">Select
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-2 justify-content-center">
                <div class="col-12 col-md-5 col-lg-3 text-center">
                    <label for="cardAmount">Amount</label>
                    <input id="cardAmount" style="width:5em;" type="number" v-model="cardAmount"
                           @change="cardAmountChanged" value="10" min="5" step="5">
                </div>
                <div class="col-12 col-md-5 col-lg-3 text-center">
                    <span v-if="cardAmountExchange">You'll get {{ cardAmountExchange }} <img :src="accountCurrencyImage" alt="Account Currency Image"></span>
                </div>
            </div>
            <div class="row mb-2 justify-content-center">
                <div class="col-12 col-md-6 text-center">
                    <label for="cardRecurring">Make this a recurring payment?</label>
                    <input id="cardRecurring" v-model="cardRecurring" type="checkbox">
                </div>
            </div>
            <div class="row mb-2 justify-content-center" v-if="cardRecurring.valueOf()">
                <div class="col-12 col-md-6 text-center">
                    <label for="cardRecurringInterval">Recurring Interval</label>
                    <select v-model="cardRecurringInterval" id="cardRecurringInterval" class="custom-select">
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
                <a v-if="defaultCardMaskedNumber" class="btn btn-primary" @click="startCardTransaction" href="" role="button">via CreditCard</a>
                <a class="btn btn-primary" href="" @click="startPayPalTransaction" role="button">via PayPal</a>
            </div>
        </div>
        <dialog-approve-transaction id="approveTransactionModal"
                                    :transaction="transaction"
                                    @transaction-accepted="transactionAccepted"
                                    @transaction-declined="transactionDeclined"
        ></dialog-approve-transaction>
    </div>
</template>

<script>
    import DialogApproveTransaction from "./DialogApproveTransaction";
    export default {
        name: "account-buy-currency",
        components: {DialogApproveTransaction},
        props: ['defaultCardMaskedNumber', 'account', 'suggestedAmounts', 'cardManagementPage', 'accountCurrencyImage'],
        data: function () {
            return {
                'cardRecurring': false,
                'cardRecurringInterval': '90',
                'cardAmount': 0,
                'cardAmountExchange': 0,
                'transaction' : {
                    'purchase': 'test'
                }
            }
        },
        methods: {
            cardUseSuggestedAmount: function (e) {
                this.cardAmount = e.currentTarget.getAttribute('data-amount');
                this.cardAmountChanged(e);
            },
            cardAmountChanged: function (e) {
                this.cardAmountExchange = 0;
                axios.post('accountcurrency/fromUsd', {
                    'amount': this.cardAmount
                }).then(response => {
                    this.cardAmountExchange = response.data;
                });
            },
            startCardTransaction: function(e) {
                let data = {
                    'amountUsd': this.cardAmount
                }
                if (this.cardRecurring) data.recurringInterval = this.cardRecurringInterval;
                axios.post('accountcurrency/newTransaction', data).then(response => {
                    console.log(response.data);
                    this.transaction = response.data;
                    $('#approveTransactionModal').modal();
                });
                e.preventDefault();
            },
            startPayPalTransaction: function(e) {
                console.log("TBC");
            },
            transactionAccepted: function(e) {
                console.log("Accepted!", e);
            },
            transactionDeclined: function(e) {
                console.log("Declined!", e);
            }
        },
        mounted: function () {
            let secondIndex = Object.keys(this.suggestedAmounts)[1];
            this.cardAmount = secondIndex;
            this.cardAmountExchange = this.suggestedAmounts[secondIndex];
        }
    }
</script>

<style scoped>

</style>
