<template>
    <div class="container">
        <h2>Tickets</h2>

        <div class="btn-group btn-group-toggle mb-2" role="group" aria-label="View Mode" data-toggle="buttons">
            <label class="btn btn-secondary active">
                <input type="radio" name="view" autocomplete="off" value="active" v-model="tableFilter">Active
            </label>
            <label class="btn btn-secondary">
                <input type="radio" name="view" autocomplete="off" value="open" v-model="tableFilter">Any Open Tickets
            </label>
            <label v-if="agent" class="btn btn-secondary">
                <input type="radio" name="view" autocomplete="off" value="assigned" v-model="tableFilter">My Assigned Tickets
            </label>
            <label class="btn btn-secondary">
                <input type="radio" name="view" autocomplete="off" value="raised" v-model="tableFilter">My Raised Tickets
            </label>
        </div>

        <b-table dark hover small
                 :items="tableContent"
                 :fields="tableFields"
                 :busy="tableLoading"
                 :tbody-tr-class="rowClass"
                 :filter="tableFilter"
                 :filter-function="filterRow"
                 @row-clicked="tableRowClicked"
        >
            <template #cell(lastUpdatedAt)="data">
                <span>{{ outputCarbonString(data.value) }}</span> <span
                class="small text-muted">{{ data.item.lastUpdatedAtTimespan }}</span>
            </template>

            <template #cell(from)="data">
                <span v-if="data.value.character">{{ data.value.character.name }}</span>
                <span v-else-if="data.value.user.id">{{ `Account#${data.value.user.id}` }}</span>
                <span v-else-if="data.value.user">Account Based</span>
                <span v-else>None</span>
            </template>

            <template #cell(agent)="data">
                <span v-if="data.value.character">{{ data.value.character.name }}</span>
                <span v-else-if="data.value.user.id">{{ `Account#${data.value.user.id}` }}</span>
                <span v-else-if="data.value.user">Yes</span>
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
        ticketsUrl: {type: String, required: true},
        agent: {type: Boolean, required: false}
    },
    data: function () {
        return {
            tableContent: [],
            tableFilter: 'active',
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
        },
        filterRow: function(row, filter) {
            if (filter === 'active') return true;
            if (filter === 'open' && row.status !== 'closed') return true;
            if (filter === 'assigned' && row.agent.own) return true;
            if (filter === 'raised' && row.from.own) return true;
            return false;
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
