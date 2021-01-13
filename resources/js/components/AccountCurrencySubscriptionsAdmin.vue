<template>
    <div class="card">
        <h4 class="card-header">View Subscriptions</h4>
        <div class="card-body">
            <div v-if="!subscriptions.length">Loading</div>
            <table v-else class="table table-hover table-striped table-responsive-lg">
                <thead>
                <tr>
                    <th scope="col">Id</th>
                    <th scope="col">Type</th>
                    <th scope="col">Created</th>
                    <th scope="col">Next</th>
                    <th scope="col">Last</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Interval</th>
                    <th scope="col">Status</th>
                </tr>
                </thead>
                <tr v-for="subscription in subscriptions">
                    <td class="small text-truncate align-middle" data-toggle="tooltip" data-placement="right" :title="subscription.id">
                        <a :href="subscription.url">{{ subscription.id }}</a>
                    </td>
                    <td>{{ subscription.type }}</td>
                    <td>{{ outputCarbonString(subscription.created_at) }}</td>
                    <td>{{ outputCarbonString(subscription.next_charge_at) }}</td>
                    <td>{{ outputCarbonString(subscription.last_charge_at) }}</td>
                    <td>${{ subscription.amount_usd }}</td>
                    <td>{{ subscription.recurring_interval }}</td>
                    <td>{{ subscription.status }}</td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: "account-currency-subscriptions-admin",
    data() {
        return {
            subscriptions: []
        }
    },
    props: [],
    computed: {},
    methods: {
        outputCarbonString: function(carbonString) {
            if (!carbonString) return '--';
            return new Date(carbonString).toLocaleString();
        }
    },
    mounted() {
        axios.get('/accountcurrency/subscriptions/api')
            .then(response => {
                this.subscriptions = response.data;
            });
    },
    updated() {
        $('[data-toggle="tooltip"]').tooltip();
    },
}
</script>

<style scoped>
td.small {
    max-width: 100px;
}
</style>
