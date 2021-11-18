<template>
    <div class="container">
        <h2>Tickets</h2>

        <div class="btn-toolbar">

            <div class="btn-group btn-group-toggle mb-2 mr-2" role="group" aria-label="View Mode" data-toggle="buttons">
                <label class="btn btn-secondary active">
                    <input type="radio" name="view" autocomplete="off" value="active" v-model="tableFilter.view">
                    All Active Tickets
                </label>
                <label class="btn btn-secondary">
                    <input type="radio" name="view" autocomplete="off" value="open" v-model="tableFilter.view">
                    Open Tickets
                </label>
                <label v-if="agent" class="btn btn-secondary">
                    <input type="radio" name="view" autocomplete="off" value="assigned" v-model="tableFilter.view">
                    My Assigned Tickets
                </label>
                <label class="btn btn-secondary">
                    <input type="radio" name="view" autocomplete="off" value="raised" v-model="tableFilter.view">
                    My Raised Tickets
                </label>
            </div>

            <div class="dropdown mb-2 mr-2">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ this.categoryLabel() }}
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" @click="setCategoryFilter(null)">All categories</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" v-for="category in categoryList()"
                       href="#" @click="setCategoryFilter(category)">{{ category }}</a>
                </div>
            </div>

            <div class="input-group form-inline mb-2">
                <label class="sr-only" for="quickOpen">Jump to ticket</label>
                <input type="text" id="quickOpen" class="form-control" @change="gotoTicket"
                       v-model="gotoTicketId" placeholder="Enter an ID to jump to">
                <button class="btn btn-secondary" @click="gotoTicket">Go</button>
            </div>
        </div>

        <b-table dark hover small stacked="lg"
                 :items="tableContent"
                 :fields="tableFields"
                 :busy="tableLoading"
                 :tbody-tr-class="rowClass"
                 :filter="tableFilter"
                 :filter-function="filterRow"
                 @row-clicked="tableRowClicked"
        >
            <template #cell(lastUpdatedAt)="data">
                <span>{{ outputCarbonString(data.value) }}</span><br/>
                <span class="small text-muted">{{ data.item.lastUpdatedAtTimespan }}</span>
            </template>

            <template #cell(from)="data">
                <span v-if="data.value.character">{{ data.value.character.name }}</span>
                <span v-else-if="data.value.user.id">{{ `Account#${data.value.user.id}` }}</span>
                <span v-else-if="data.value.user">Account Based</span>
                <span v-else>None</span>
            </template>

            <template #cell(agent)="data">
                <span v-if="data.value.character">{{ data.value.character.name }}</span>
                <span v-else-if="data.value.user && data.value.user.id">{{ `Account#${data.value.user.id}` }}</span>
                <span v-else-if="data.value.user">Yes</span>
                <span v-else>--</span>
            </template>

            <template #cell(votes)="data">
                <span v-if="data.item.isPublic">
                    <i class="fas fa-thumbs-up mr-1"></i>{{ data.value.up }}<br/>
                    <i class="fas fa-thumbs-down mr-1"></i>{{ data.value.down }}
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
            tableFilter: {
                view: 'active',
                category: null
            },
            tableLoading: false,
            tableFields: [
                {
                    key: 'id',
                    label: 'ID',
                    class: 'text-nowrap',
                    sortable: true
                },
                {
                    key: 'category',
                    label: 'Category',
                    formatter: 'capital',
                    class: 'text-nowrap',
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
                    class: 'text-nowrap',
                    sortable: true
                },
                {
                    key: 'agent',
                    label: 'Assigned',
                    class: 'text-nowrap',
                    sortable: true
                },
                {
                    key: 'status',
                    label: 'Status',
                    formatter: 'capital',
                    class: 'text-nowrap',
                    sortable: true
                },
                {
                    key: 'lastUpdatedAt',
                    label: 'Last Update',
                    class: 'text-nowrap',
                    sortable: true
                },
                {
                    key: 'votes',
                    label: 'Votes',
                    class: 'text-nowrap'
                }
            ],
            gotoTicketId: null
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
        categoryList: function () {
            let result = [];
            this.tableContent.forEach(row => {
                result.push(this.capital(row.category));
            });
            return result;
        },
        rowClass: function (item) {
            if (item.status === 'closed') return "ticket-closed";
            if (item.status === 'open' || item.status === 'new') return "ticket-active";
            return "ticket-inactive";
        },
        setCategoryFilter: function (filter) {
            if (!filter)
                this.tableFilter.category = null;
            else
                this.tableFilter.category = filter;
        },
        categoryLabel: function () {
            return this.tableFilter.category ? "Category filter: " + this.tableFilter.category : "Showing all Categories";
        },
        filterRow: function (row, filter) {
            //Category filtering, find a reason to not show it
            if (filter.category) {
                if (filter.category.toLowerCase() !== row.category.toLowerCase()) return false;
            }
            //View filtering, find a reason to show it
            if (filter.view === 'active') return true;
            if (filter.view === 'open' && row.status !== 'closed') return true;
            if (filter.view === 'assigned' && row.agent.own) return true;
            if (filter.view === 'raised' && row.from.own) return true;
            return false;
        },
        gotoTicket: function() {
            if (!this.gotoTicketId) return;
            window.location = window.location.origin + window.location.pathname + "/ticket/" + this.gotoTicketId;
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
