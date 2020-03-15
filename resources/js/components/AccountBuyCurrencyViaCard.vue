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
                    <div class="col-md-3" v-for="amount in cardSuggestedAmounts">
                        <div class="card border-primary">
                            <h3 class="card-header bg-primary text-dark">${{ amount }}</h3>
                            <div class="card-body text-center">
                                <p class="card-text">??? Mako</p>
                                <button @click="cardUseSuggestedAmount" :data-amount="amount" type="button" class="btn btn-primary btn-block">Select</button>
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
                        You'll get ???
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
            <div class="float-right">
            <a class="btn btn-primary" :href="cardManagementPage" role="button">Manage Cards</a>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "account-buy-currency-via-card",
        props: ['defaultCardMaskedNumber', 'account', 'cardManagementPage'],
        data: function () {
            return {
                'cardSuggestedAmounts':[5, 10, 20, 50],
                'cardRecurring':false,
                'cardRecurringInterval':'90',
                'cardAmount':10
            }
        },
        methods: {
            cardUseSuggestedAmount: function(e) {
                this.cardAmount = e.currentTarget.getAttribute('data-amount');
                this.cardAmountChanged(e);
            },
            cardAmountChanged: function(e) {
                console.log("TBC - Should call the muck here to update quote here..");
            }
        },
        mounted:function()  {
            axios.post('accountcurrency/fromUsd', {
                'amount': 5
            }).then(response => {
                console.log(response);
            });
        }
    }
</script>

<style scoped>

</style>
