<template>
    <div class="card">
        <h4 class="card-header">View Account Currency Transactions</h4>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th scope="col">id</th>
                    <th scope="col">Date/Time</th>
                    <th scope="col">Type</th>
                    <th scope="col">USD</th>
                    <th scope="col">Account Currency</th>
                    <th scope="col">Status</th>
                </tr>
                </thead>
                <tbody>
                    <tr v-for="transaction in transactions">
                        <td><a :href="transaction.url">{{ transaction.id }}</a></td>
                        <td>{{ transaction.timeStamp }}</td>
                        <td>{{ transaction.type }}</td>
                        <td>${{ transaction.usd }}</td>
                        <td>{{ transaction.accountCurrency }}</td>
                        <td>{{ transaction.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    export default {
        name: "account-currency-transactions",
        props: ['transactions', 'transaction-view']
    }
</script>

<style scoped>

</style>
