<template>
    <div class="container">
        <h2>Tickets</h2>
        <b-table dark striped hover small
                 :items="tableContent"
                 :fields="tableFields"
                 :busy="tableLoading"
                 @row-clicked="tableRowClicked"
        >
            <template #cell(lastUpdatedAt)="data">
                <span>{{ outputCarbonString(data.value) }}</span> <span class="small text-muted">{{ data.item.lastUpdatedAtTimespan }}</span>
            </template>

            <template #cell(from)="data">
                <span v-if="data.value.character">{{ data.value.character.name }}</span>
                <span v-else-if="data.value.user">{{ `Account#${data.value.user.id}` }}</span>
                <span v-else>None</span>
            </template>

            <template #cell(agent)="data">
                <span v-if="data.value.character">{{ data.value.character.name }}</span>
                <span v-else-if="data.value.user">{{ `Account#${data.value.user.id}` }}</span>
                <span v-else>--</span>
            </template>


        </b-table>

    </div>
</template>

<script>
export default {
    name: "support-ticket-list",
    props: {
        ticketsUrl: {type: String, required: true},
    },
    data: function() {
        return {
            tableContent: [],
            tableLoading: false,
            tableFields: [
                {
                    key: 'id',
                    label: 'ID'
                },
                {
                    key: 'category',
                    label: 'Category'
                },
                {
                    key: 'title',
                    label: 'Title'
                },
                {
                    key: 'from',
                    label: 'Requester'
                },
                {
                    key: 'agent',
                    label: 'Assigned'
                },
                {
                    key: 'status',
                    label: 'Status',
                    formatter: 'capital'
                },
                {
                    key: 'lastUpdatedAt',
                    label: 'Last Update'
                }
            ]
        }
    },
    methods: {
        refreshTableContent: function() {
            this.tableLoading = true;
            axios
                .get(this.ticketsUrl, {})
                .then(response => {
                    this.tableContent = response.data;
                })
                .catch(error => {
                    console.log("Request failed:", error);
                })
                .finally(() => this.tableLoading = false);
        },
        tableRowClicked: function (row) {
            window.open(row.url, '_blank');
        }
    },
    mounted() {
        this.refreshTableContent();
    }
}
</script>

<style scoped>

</style>
