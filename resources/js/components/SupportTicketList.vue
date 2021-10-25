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

            <template #cell(character)="data">
                <span>{{ data.value }}</span> <span class="small text-muted">{{ data.item.user ? `Account#${data.item.user}` : '' }}</span>
            </template>

            <template #cell(working)="data">
                <span>{{ data.value.join(', ') }}</span>
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
                    key: 'character',
                    label: 'Requester'
                },
                {
                    key: 'working',
                    label: 'Assigned'
                },
                {
                    key: 'status',
                    label: 'Status'
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
