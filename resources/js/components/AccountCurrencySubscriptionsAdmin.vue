<template>
    <div class="container">
        <h4>View Subscriptions</h4>
        <div class="row">
            <div class="col-md">
                <b-form-group label="Type" label-cols="auto" v-slot="{ ariaDescribedby }">
                    <b-form-radio-group v-model="subscriptionsFilter.type" name="type" buttons
                                        :aria-describedby="ariaDescribedby">
                        <b-form-radio value="any">Any</b-form-radio>
                        <b-form-radio value="card">Card</b-form-radio>
                        <b-form-radio value="paypal">Paypal</b-form-radio>
                    </b-form-radio-group>
                </b-form-group>
            </div>
            <div class="col-md">
                <b-input-group prepend="Filter">
                    <b-form-input v-model="subscriptionsFilter.text"></b-form-input>
                </b-input-group>
            </div>
        </div>

        <b-table dark striped hover small
                 :items="subscriptionsData"
                 :fields="subscriptionsFields"
                 :busy="loadingSubscriptions"
                 :filter="subscriptionsFilter"
                 :filter-function="filterSubscriptions"
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
        </b-table>
    </div>
</template>

<script>
export default {
    name: "account-currency-subscriptions-admin",
    data() {
        return {
            loadingSubscriptions: false,
            subscriptionsData: [],
            subscriptionsFilter: {
                type: 'any',
                text: ''
            },
            subscriptionsFields: [
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
                    key: 'next_charge_at',
                    label: 'Next',
                    sortable: true,
                    formatter: 'outputCarbonString'
                },
                {
                    key: 'last_charge_at',
                    label: 'Last',
                    sortable: true,
                    formatter: 'outputCarbonString'
                },
                {
                    key: 'attempts_since_last_success',
                    label: 'Fails',
                    sortable: true
                },
                {
                    key: 'amount_usd',
                    label: 'Amount (USD)',
                    sortable: true,
                    formatter: 'outputUsd'
                },
                {
                    key: 'recurring_interval',
                    label: 'Interval (days)',
                    sortable: false
                },
                {
                    key: 'status',
                    label: 'Status',
                    sortable: false
                }
            ]
        }
    },
    props: [],
    computed: {},
    methods: {
        getSubscriptions: function (context) {
            this.loadingSubscriptions = true;
            let promise = axios.get('/accountcurrency/subscriptions/api', {
                params: context
            });
            return promise
                .then(response => {
                    this.subscriptionsData = response.data;
                }).catch(error => {
                    console.log("Failed to load subscriptions from API: ", error);
                    this.subscriptionsData = [];
                }).finally(() => {
                    this.loadingSubscriptions = false;
                });

        },
        filterSubscriptions: function (row, filter) {
            if (filter.type === 'paypal' && row.type !== 'Paypal') return false;
            if (filter.type === 'card' && row.type !== 'Card') return false;
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
        this.getSubscriptions();
    },
    updated() {
        $('[data-toggle="tooltip"]').tooltip();
    },
}
</script>

<style scoped>
>>> .limit-column-width {
    max-width: 100px;
}
</style>
