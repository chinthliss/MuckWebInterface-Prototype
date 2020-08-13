<template>
    <div id="dialog-approve-transaction" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Transaction</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="transactionDeclined">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Do you approve paying {{ transaction.price }} for:</p>
                    <p v-html="transaction.purchase"></p>
                    <p v-if="transaction.note" class="text-muted">{{ transaction.note }}</p>
                </div>
                <div class="modal-footer">
                    <button @click="transactionAccepted" type="button" class="btn btn-primary" data-dismiss="modal">Yes</button>
                    <button @click="transactionDeclined" type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "dialog-approve-transaction",
        props: [
            'transaction' // Array of [purchase, price, token, [note]]
        ],
        methods: {
            'transactionAccepted': function() {
                $('#dialog-approve-transaction').modal('hide');
                this.$emit('transaction-accepted', this.transaction.token);
            },
            'transactionDeclined': function() {
                $('#dialog-approve-transaction').modal('hide');
                this.$emit('transaction-declined', this.transaction.token);
            }
        }
    }
</script>

<style scoped>

</style>
