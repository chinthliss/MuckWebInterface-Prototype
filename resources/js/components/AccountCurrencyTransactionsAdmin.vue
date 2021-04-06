<template>
    <div class="card">
        <h4 class="card-header">View Transactions</h4>

        <div class="card-body">

            <div class="row">
                <div class="col-md">
                    <b-form-group label="Type" label-cols="auto" v-slot="{ ariaDescribedby }">
                        <b-form-radio-group v-model="transactionsFilter.type" name="type" buttons
                                            :aria-describedby="ariaDescribedby">
                            <b-form-radio value="any">Any</b-form-radio>
                            <b-form-radio value="card">Card</b-form-radio>
                            <b-form-radio value="paypal">Paypal</b-form-radio>
                            <b-form-radio value="patreon">Patreon</b-form-radio>
                        </b-form-radio-group>
                    </b-form-group>
                </div>
                <div class="col-md">
                    <b-input-group prepend="Filter">
                        <b-form-input v-model="transactionsFilter.text"></b-form-input>
                    </b-input-group>
                </div>
            </div>

            <b-table dark striped hover small
                     :items="transactionsData"
                     :fields="transactionsFields"
                     :busy="loadingTransactions"
                     :filter="transactionsFilter"
                     :filter-function="filterTransactions"
            >
                <template #table-busy>
                    <div class="text-center my-2">
                        <b-spinner class="align-middle" variant="primary"></b-spinner>
                        <strong>Loading...</strong>
                    </div>
                </template>

                <template #cell(id)="data">
                    <a target="_blank" :href="data.item.url">{{ data.value }}</a>
                </template>

                <template #cell(subscription_id)="data">
                    <a v-if="data.item.subscription_id" target="_blank"
                       :href="data.item.subscription_url">{{ data.item.subscription_id }}</a>
                </template>
            </b-table>
        </div>
    </div>
</template>

<script>
export default {
    name: "account-currency-transactions-admin",
    data() {
        return {
            loadingTransactions: false,
            transactionsData: [],
            transactionsFilter: {
                type: 'any',
                text: ''
            },
            transactionsFields: [
                {
                    key: 'id',
                    label: 'Id',
                    sortable: true,
                    class: 'limit-column-width',
                    tdClass: 'text-truncate small'
                },
                {
                    key: 'type',
                    label: 'Type',
                    sortable: false
                },
                {
                    key: 'created_at',
                    label: 'Created',
                    sortable: true,
                    formatter: 'outputCarbonString'
                },
                {
                    key: 'completed_at',
                    label: 'Completed',
                    sortable: true,
                    formatter: 'outputCarbonString'
                },
                {
                    key: 'total_usd',
                    label: 'Amount (USD)',
                    sortable: true,
                    formatter: 'outputUsd'
                },
                {
                    key: 'account_currency_rewarded',
                    label: 'Account Currency',
                    sortable: true
                },
                {
                    key: 'items',
                    label: 'Items?',
                    sortable: false
                },
                {
                    key: 'subscription_id',
                    label: 'Subscription?',
                    sortable: true,
                    class: 'limit-column-width',
                    tdClass: 'text-truncate small'
                },
                {
                    key: 'result',
                    label: 'Result',
                    sortable: false
                }
            ]
        }
    },
    props: [],
    computed: {},
    methods: {
        getTransactions: function (context) {
            this.loadingTransactions = true;
            let promise = axios.get('/accountcurrency/transactions/api', {
                params: context
            });
            return promise
                .then(response => {
                    this.transactionsData = response.data;
                }).catch(error => {
                    console.log("Failed to load transactions from API: ", error);
                    this.transactionsData = [];
                }).finally(() => {
                    this.loadingTransactions = false;
                });

        },
        filterTransactions: function (row, filter) {
            if (filter.type === 'paypal' && row.type !== 'Paypal') return false;
            if (filter.type === 'card' && row.type !== 'Card') return false;
            if (filter.type === 'patreon' && row.type !== 'Patreon') return false;

            if (filter.text !== '') {
                let show = false;
                if (row.id.toLowerCase().indexOf(filter.text.toLowerCase()) !== -1) show = true;
                if (row.account_id.toString().toLowerCase().indexOf(filter.text.toLowerCase()) !== -1) show = true;
                if (!show) return false;
            }

            return true;
        },
        outputCarbonString: function (carbonString) {
            if (!carbonString) return '--';
            return new Date(carbonString).toLocaleString();
        },
        outputUsd: function (usd) {
            if (!usd) return '--';
            return '$' + usd;
        }
    },
    mounted() {
        this.getTransactions();
    },
    updated() {
        $('[data-toggle="tooltip"]').tooltip();
    },
}
</script>

<style scoped>
>>> .limit-column-width {
    max-width: 140px;
}
</style>
