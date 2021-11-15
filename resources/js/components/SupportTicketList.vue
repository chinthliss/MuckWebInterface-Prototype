<template>
    <div class="container">
        <h2>Tickets</h2>
        <b-table dark hover small
                 :items="tableContent"
                 :fields="tableFields"
                 :busy="tableLoading"
                 :tbody-tr-class="rowClass"
                 @row-clicked="tableRowClicked"
        >
            <template #cell(lastUpdatedAt)="data">
                <span>{{ outputCarbonString(data.value) }}</span> <span
                class="small text-muted">{{ data.item.lastUpdatedAtTimespan }}</span>
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

            <template #cell(votes)="data">
                <span v-if="data.item.isPublic">
                    {{ data.value.up }} <i class="fas fa-thumbs-up"></i>,
                    {{ data.value.down }} <i class="fas fa-thumbs-down"></i>
                </span>
            </template>

        </b-table>

    </div>
</template>

<script>
export default {
    name: "support-ticket-list",
    props: {
        ticketsUrl: {type: String, required: true}
    },
    data: function () {
        return {
            tableContent: [],
            tableLoading: false,
            tableFields: [
                {
                    key: 'id',
                    label: 'ID',
                    sortable: true
                },
                {
                    key: 'category',
                    label: 'Category',
                    sortable: true
                },
                {
                    key: 'title',
                    label: 'Title',
                    sortable: true
                },
                {
                    key: 'from',
                    label: 'Requester',
                    sortable: true
                },
                {
                    key: 'agent',
                    label: 'Assigned',
                    sortable: true
                },
                {
                    key: 'status',
                    label: 'Status',
                    formatter: 'capital',
                    sortable: true
                },
                {
                    key: 'lastUpdatedAt',
                    label: 'Last Update',
                    sortable: true
                },
                {
                    key: 'votes',
                    label: 'Votes'
                }
            ]
        }
    },
    methods: {
        refreshTableContent: function () {
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
        },
        rowClass: function (item) {
            if (item.status === 'closed') return "ticket-closed";
            if (item.status === 'open' || item.status === 'new') return "ticket-active";
            return "ticket-inactive";
        }
    },
    mounted() {
        this.refreshTableContent();
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

::v-deep .ticket-closed {
    cursor: pointer;
    background-color: black;
    color: $text-muted;
}

::v-deep .ticket-inactive {
    cursor: pointer;
    color: $text-muted;
}

::v-deep .ticket-active {
    cursor: pointer;
}


</style>
