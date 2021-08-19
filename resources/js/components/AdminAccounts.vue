<template>
    <div class="container">
        <h4>View Accounts</h4>
        <div class="form-inline">

            <div class="form-group mb-2 mr-sm-2">
                <label class="mr-2" for="searchAccountId">Account ID</label>
                <input type="number" class="form-control" id="searchAccountId" placeholder="Account ID"
                       v-model="searchAccountId">
            </div>

            <div class="form-group mb-2 mr-sm-2">
                <label class="mr-2" for="searchCharacterName">Character</label>
                <input type="text" class="form-control" id="searchCharacterName"
                       placeholder="Character Name"
                       v-model="searchCharacterName">
            </div>

            <div class="form-group mb-2 mr-sm-2">
                <label class="mr-2" for="searchEmail">Email</label>
                <input type="text" class="form-control" id="searchEmail" placeholder="Email"
                       v-model="searchEmail">
            </div>

            <div class="form-group mb-2 mr-sm-2">
                <label class="mr-2" for="searchCreationDate">Created On</label>
                <input type="date" class="form-control" id="searchCreationDate" placeholder="Created On"
                       v-model="searchCreationDate">
            </div>

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
                    key: 'primary_email',
                    label: 'Primary Email'
                },
                {
                    key: 'characters',
                    label: 'Characters',
                    formatter: 'characterList'
                },
                {
                    key: 'lastConnected',
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
        tableRowClicked: function (row) {
            window.open(row.url, '_blank');
        },
        characterList: function (characters) {
            let names = [];
            for (const character of characters) {
                names.push(character.name)
            }
            return names.join(', ');
        }
    }
}
</script>

<style scoped>
>>> tr {
    cursor: pointer;
}

.form-inline .form-group label {
    min-width: 160px;
    display: inline-block;
    text-align: right;
}

</style>
