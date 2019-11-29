<template>
    <div class="card">
        <h4 class="card-header">Card Management</h4>
        <div class="card-body">
            <div v-if="typeof profile == 'undefined'">You have no cards configured.</div>
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
        name: "auth-card-management",
        props: {
            profile: {type: Object}
        },
        data: function () {
            return {
                cardNumber: '',
                expiryDate: '',
                securityCode: '',
                errors: {}
            }
        },
        methods: {
            addCard: function (e) {
                this.errors = {};
                axios({
                    method: 'post',
                    url: '/account/cardmanagement',
                    data: {
                        'cardNumber': $('#inputCardNumber').val(),
                        'expiryDate': $('#inputExpiryDate').val(),
                        'securityCode': $('#inputSecurityCode').val()
                    }
                }).then(response => {

                }).catch(error => {
                    if (error.response.status === 422) {
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
