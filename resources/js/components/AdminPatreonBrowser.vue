<style scoped>
>>> .limit-column-width {
    max-width: 100px;
}
</style>

<template>
    <div class="card">
        <h4 class="card-header">Patreon Supporter Browser</h4>
        <div class="card-body">
            <div class="row justify-content-end">
            <b-form-group label="Has Account?" label-cols="auto" v-slot="{ ariaDescribedby }">
                <b-form-radio-group v-model="filter.filterOnAccount" name="has-account" buttons
                                    :aria-describedby="ariaDescribedby">
                    <b-form-radio value="both">Both</b-form-radio>
                    <b-form-radio value="yes">Yes</b-form-radio>
                    <b-form-radio value="no">No</b-form-radio>
                </b-form-radio-group>
            </b-form-group>
        </div>

            <b-table dark striped hover small
                     :items="patronData"
                     :fields="fields"
                     :busy="loadingPatrons"
                     :filter="filter"
                     :filter-function="filterPatrons"
            >
                <template #table-busy>
                    <div class="text-center my-2">
                        <b-spinner class="align-middle" variant="primary"></b-spinner>
                        <strong>Loading...</strong>
                    </div>
                </template>
            </b-table>
        </div>
    </div>
</template>

<script>
export default {
    name: "admin-patron-browser",
    props: ['apiUrl'],
    data: function () {
        return {
            patronData: [],
            loadingPatrons: false,
            filter: {filterOnAccount: 'both'},
            fields: [
                {
                    key: 'patronId',
                    label: 'Patreon Id',
                    sortable: true
                },
                {
                    key: 'accountId',
                    label: 'Account Id',
                    sortable: true
                },
                {
                    key: 'name',
                    label: 'Fullname (Vanity)',
                    class: 'limit-column-width',
                    tdClass: 'text-truncate small'
                },
                {
                    key: 'totalSupportUsd',
                    label: 'Total Support (USD)',
                    sortable: true
                },
                {
                    key: 'totalRewardedUsd',
                    label: 'Total Rewarded (USD)',
                    sortable: true
                }
            ]
        }
    },

    methods: {
        getPatrons(context) {
            console.log("Getting patrons from API for campaign");
            this.loadingPatrons = true;
            let promise = axios.get(this.apiUrl, {
                params: context
            });
            return promise
                .then(response => {
                    console.log("Got patrons from API");
                    console.log(response.data);
                    this.patronData = response.data;
                }).catch(error => {
                    console.log("Failed to get patrons from API");
                    this.patronData = [];
                }).finally(() => {
                    this.loadingPatrons = false;
                });
        },
        filterPatrons(row, filter) {
            let show = true;
            if (this.filter.filterOnAccount === 'yes' && !row.accountId) show = false;
            if (this.filter.filterOnAccount === 'no' && row.accountId) show = false;
            return show;
        }
    },

    created: function () {
        this.getPatrons();
    }
}
</script>

