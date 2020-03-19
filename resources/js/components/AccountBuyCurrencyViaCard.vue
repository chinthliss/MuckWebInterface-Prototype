<template>
    <div class="card">
        <div class="text-center">You are buying for account {{ account }}.</div>
        <h4 class="card-header">Via Credit Card</h4>
        <div class="card-body">
            <div v-if="defaultCardMaskedNumber">
                <div class="p-2 mb-2 bg-info text-white text-center">
                    This will be charged to your card ending in '{{ defaultCardMaskedNumber }}'.
                </div>
                <div class="row mb-2 justify-content-center">
                    <div class="col-md-3" v-for="(gameCurrency, usd) in suggestedAmounts">
                        <div class="card border-primary">
                            <h3 class="card-header bg-primary text-dark">${{ usd }}</h3>
                            <div class="card-body text-center">
                                <p class="card-text">{{ gameCurrency }} Mako</p>
                                <button @click="cardUseSuggestedAmount" :data-amount="usd" type="button" class="btn btn-primary btn-block">Select</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-2 justify-content-center">
                    <div class="col-12 col-md-5 col-lg-3 text-center">
                        <label for="cardAmount">Amount</label>
                        <input id="cardAmount" style="width:5em;" type="number" v-model="cardAmount" @change="cardAmountChanged" value="10" min="5" step="5">
                    </div>
                    <div class="col-12 col-md-5 col-lg-3 text-center">
                        <span v-if="cardAmountExchange">You'll get {{ cardAmountExchange }}</span>
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

            </div>
            <div v-else class="p-2 mb-2 bg-warning text-dark text-center"><span class="sr-only">Warning: </span>
                You have no default card configured and will need to use 'Manage Cards' before making a payment.
            </div>
            <div class="text-center">
            <a class="btn btn-secondary" :href="cardManagementPage" role="button">Manage Cards</a>
            <a v-if="defaultCardMaskedNumber" class="btn btn-primary" :href="cardManagementPage" role="button">Proceed</a>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "account-buy-currency-via-card",
        props: ['defaultCardMaskedNumber', 'account', 'cardManagementPage', 'suggestedAmounts'],
        data: function () {
            return {
                'cardRecurring':false,
                'cardRecurringInterval':'90',
                'cardAmount':0,
                'cardAmountExchange':0
            }
        },
        methods: {
            cardUseSuggestedAmount: function(e) {
                this.cardAmount = e.currentTarget.getAttribute('data-amount');
                this.cardAmountChanged(e);
            },
            cardAmountChanged: function(e) {
                this.cardAmountExchange = 0;
                axios.post('accountcurrency/fromUsd', {
                    'amount': this.cardAmount
                }).then(response => {
                    this.cardAmountExchange = response.data;
                });
            }
        },
        mounted:function()  {
            let secondIndex = Object.keys(this.suggestedAmounts)[1];
            this.cardAmount = secondIndex;
            this.cardAmountExchange = this.suggestedAmounts[secondIndex];
        }
    }
</script>

<style scoped>

</style>
