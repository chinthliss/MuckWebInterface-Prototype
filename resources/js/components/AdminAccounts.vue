<template>
    <div class="container">
        <h4>View Accounts</h4>
        <div class="form-inline">

            <label class="sr-only" for="searchAccountId">Account ID</label>
            <input type="number" class="form-control mb-2 mr-sm-2" id="searchAccountId" placeholder="Account ID"
                   v-model="searchAccountId">

            <label class="sr-only" for="searchCharacterName">Character</label>
            <input type="text" class="form-control mb-2 mr-sm-2" id="searchCharacterName" placeholder="Character Name"
                   v-model="searchCharacterName">

            <label class="sr-only" for="searchEmail">Email</label>
            <input type="text" class="form-control mb-2 mr-sm-2" id="searchEmail" placeholder="Email"
                   v-model="searchEmail">

            <label class="sr-only" for="searchCreationDate">Created On</label>
            <input type="date" class="form-control mb-2 mr-sm-2" id="searchCreationDate" placeholder="Created On"
                   v-model="searchCreationDate">

            <button class="btn btn-primary mb-2" @click="searchAccounts">
                <i class="fas fa-search btn-icon-left"></i>Search
            </button>
        </div>

        <div v-if="tableLoading" class="text-center my-2">
            <b-spinner class="align-middle primary" variant="primary"></b-spinner>
            <strong>Loading...</strong>
        </div>

        <b-table dark striped hover small
                 :items="tableContent"
                 :fields="tableFields"
                 :busy="tableLoading"
                 @row-clicked="tableRowClicked"
        >
        </b-table>
    </div>
</template>

<script>

export default {
    name: "admin-accounts",
    props: {
        apiUrl: {type: String, required: true},
    },
    data: function () {
        return {
            searchAccountId: "",
            searchCharacterName: "",
            searchEmail: "",
            searchCreationDate: "",
            tableContent: [],
            tableLoading: false,
            tableFields: [
                {
                    key: 'id',
                    label: 'ID'
                },
                {
                    key:'primary_email',
                    label: 'Primary Email'
                },
                {
                    key:'characters',
                    label: 'Characters',
                    formatter: 'characterList'
                },
                {
                    key:'lastConnected',
                    label: 'Last Connect',
                    formatter: 'outputCarbonString'
                }
            ]
        }
    },
    computed: {},
    methods: {
        searchAccounts: function () {
            let searchCriteria = {};
            if (this.searchAccountId) searchCriteria.account = this.searchAccountId;
            if (this.searchCharacterName) searchCriteria.character = this.searchCharacterName;
            if (this.searchEmail) searchCriteria.email = this.searchEmail;
            if (this.searchCreationDate) searchCriteria.creationDate = this.searchCreationDate;

            this.tableLoading = true;
            this.tableContent = [];
            axios
                .get(this.apiUrl, {params: searchCriteria})
                .then(response => {
                    this.tableContent = response.data;
                })
                .catch(error => {
                    console.log("Request failed:", error);
                })
                .finally(() => this.tableLoading = false);
        },
        tableRowClicked: function(row) {
            window.open(row.url, '_blank');
        },
        characterList: function(characters) {
            let names = [];
            for (const character of characters) {
                names.push(character.name)
            }
            return names.join(', ');
        },
        outputCarbonString: function (carbonString) {
            if (!carbonString) return '--';
            return new Date(carbonString).toLocaleString();
        }
    }
}
</script>

<style scoped>
>>> tr {
    cursor: pointer;
}
</style>
