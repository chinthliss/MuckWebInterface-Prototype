<template>
    <div class="card">
        <h4 class="card-header">View Account Currency Transactions</h4>
        <div class="card-body">
            <table class="table table-hover table-striped table-responsive-lg">
                <thead>
                <tr>
                    <th scope="col">id</th>
                    <th scope="col">Created</th>
                    <th scope="col">Completed</th>
                    <th scope="col">Type</th>
                    <th scope="col">USD</th>
                    <th scope="col">Account Currency</th>
                    <th scope="col">Items?</th>
                    <th scope="col">Subscription?</th>
                    <th scope="col">Result</th>
                </tr>
                </thead>
                <tbody>
                    <tr v-for="transaction in transactions">
                        <td class="small text-truncate align-middle" data-toggle="tooltip" data-placement="right" :title="transaction.id">
                            <a :href="transaction.url">{{ transaction.id }}</a>
                        </td>
                        <td>{{ outputCarbonString(transaction.created_at) }}</td>
                        <td>{{ outputCarbonString(transaction.completed_at) }}</td>
                        <td>{{ transaction.type }}</td>
                        <td>${{ transaction.total_usd }}</td>
                        <td>{{ transaction.total_account_currency_rewarded }}</td>
                        <td>{{ transaction.items }}</td>
                        <td>{{ transaction.subscription_id ? 'Y' : '' }}</td>
                        <td>{{ transaction.result }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    export default {
        name: "account-currency-transactions",
        props: ['transactions', 'transaction-view'],
        methods: {
            outputCarbonString: function(carbonString) {
                if (!carbonString) return '--';
                return new Date(carbonString).toLocaleString();
            }
        }
    }
</script>

<style scoped>
td.small {
    max-width: 100px;
}
</style>
