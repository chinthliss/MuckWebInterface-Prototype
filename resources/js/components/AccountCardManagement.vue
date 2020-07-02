
<template>
    <div class="card">
        <h4 class="card-header">Card Management</h4>
        <div class="card-body">
            <div v-if="!cards.length">You have no cards configured.</div>
            <table v-else class="table table-striped">
                <tr>
                    <th scope="col">Card Type</th>
                    <th scope="col">Ends With</th>
                    <th scope="col">Expiry Date</th>
                </tr>
                <tbody>
                <tr v-for="card in cards" :data-id="card.id">
                    <td>{{ card.cardType }}</td>
                    <td>{{ card.maskedCardNumber }}</td>
                    <td>{{ card.expiryDate }}</td>
                    <td class="text-center">
                        <div v-if="card.isDefault">Default</div>
                        <button v-else class="btn btn-secondary" :data-id="card.id" @click="setDefaultCard">Make Default</button>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-secondary" :data-id="card.id" @click="deleteCard">Delete</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <div id="add-card" class="border border-primary rounded p-3">
                <form>
                    <div class="form-row">
                        <div class="col">
                            <label for="inputCardNumber">Card Number</label>
                            <input type="text" class="form-control" id="inputCardNumber"
                                   placeholder="Enter the long number across the front of the card"
                                   v-model="cardNumber"
                                   v-bind:class="{ 'is-invalid' : errors.cardNumber }"
                            >
                            <div class="invalid-feedback" role="alert">
                                <p v-for="error in errors.cardNumber">{{ error }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-xl-5">
                            <label for="inputExpiryDate">Expiry Date</label>
                            <input type="text" class="form-control" id="inputExpiryDate"
                                   placeholder="Enter the expiry date in the form MM/YYYY"
                                   v-model="expiryDate"
                                   v-bind:class="{ 'is-invalid' : errors.expiryDate }"
                            >
                            <div class="invalid-feedback" role="alert">
                                <p v-for="error in errors.expiryDate">{{ error }}</p>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <label for="inputSecurityCode">Security Code</label>
                            <input type="text" class="form-control" id="inputSecurityCode"
                                   placeholder="Enter the security code from the back of the card"
                                   v-model="securityCode"
                                   v-bind:class="{ 'is-invalid' : errors.securityCode }"
                            >
                            <div class="invalid-feedback" role="alert">
                                <p v-for="error in errors.securityCode">{{ error }}</p>
                            </div>
                        </div>
                        <div class="col-xl-2">
                            <div class="mb-2">&nbsp;</div>
                            <button id="addCardButton" class="btn btn-primary btn-block" @click="addCard">
                                Add New Card
                            </button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <div class="invalid-feedback" role="alert">
                                <p v-for="error in errors.other">{{ error }}</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</template>

<script>
    export default {
        name: "account-card-management",
        props: {
            profileId: {type: String},
            initialCards: {type: Array}
        },
        data: function () {
            return {
                cardNumber: '',
                expiryDate: '',
                securityCode: '',
                errors: {},
                cards: this.initialCards
            }
        },
        methods: {
            addCard: function (e) {
                this.errors = {};
                axios({
                    method: 'post',
                    url: '/account/cardmanagement',
                    data: {
                        'cardNumber': this.cardNumber,
                        'expiryDate': this.expiryDate,
                        'securityCode': this.securityCode
                    }
                }).then(response => {
                    this.cardNumber = '';
                    this.expiryDate = '';
                    this.securityCode = '';
                    $('#add-card-form').trigger('reset');
                    this.cards.push(response.data);
                    //Any new card is the default, so need to reflect this
                    for (let card in this.cards) {
                        if (this.cards.hasOwnProperty(card)) {
                            this.cards[card].isDefault = (this.cards[card].id === response.data.id);
                        }
                    }
                }).catch(error => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else console.log(error);
                });
                e.preventDefault();
            },
            deleteCard: function (e) {
                let cardId = e.target.getAttribute('data-id');
                axios({
                    method: 'delete',
                    url: '/account/cardmanagement',
                    data: {'id': cardId}
                }).then(response => {
                    this.cards = this.cards.filter(card => card.id !== cardId);
                }).catch(error => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else console.log(error);
                });
                e.preventDefault();
            },
            setDefaultCard: function(e) {
                let cardId = e.target.getAttribute('data-id');
                axios({
                    method: 'patch',
                    url: '/account/cardmanagement',
                    data: {'id': cardId}
                }).then(response => {
                    for (let card in this.cards) {
                        if (this.cards.hasOwnProperty(card)) {
                            this.cards[card].isDefault = (this.cards[card].id === cardId);
                        }
                    }
                }).catch(error => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    } else console.log(error);
                });
                e.preventDefault();
            }
        }
    }
</script>

<style scoped>

</style>
