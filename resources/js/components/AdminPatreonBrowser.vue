<template>
    <div class="container">
        <h4>Patreon Supporter Browser</h4>
        <div class="row">
            <div class="col-md">
                <b-form-group label="Has Account?" label-cols="auto" v-slot="{ ariaDescribedby }">
                    <b-form-radio-group v-model="filter.filterOnAccount" name="has-account" buttons
                                        :aria-describedby="ariaDescribedby">
                        <b-form-radio value="both">Both</b-form-radio>
                        <b-form-radio value="yes">Yes</b-form-radio>
                        <b-form-radio value="no">No</b-form-radio>
                    </b-form-radio-group>
                </b-form-group>
            </div>
            <div class="col-md">
                <b-input-group prepend="Filter">
                    <b-form-input v-model="filter.filterString"></b-form-input>
                </b-input-group>
            </div>
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

            <template #cell(accountId)="data">
                <a target="_blank" :href="data.item.account_url">{{ data.value }}</a>
            </template>
        </b-table>
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
            filter: {filterOnAccount: 'both', filterString: ''},
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
                    key: 'lastConnect',
                    label: 'Last Connect',
                    sortable: true,
                    formatter: 'outputCarbonString'
                },
                {
                    key: 'name',
                    label: 'Fullname (Vanity)',
                    class: 'limit-column-width',
                    tdClass: 'text-truncate small'
                },
                {
                    key: 'email',
                    label: 'Email',
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
            this.loadingPatrons = true;
            let promise = axios.get(this.apiUrl, {
                params: context
            });
            return promise
                .then(response => {
                    this.patronData = response.data;
                }).catch(error => {
                    console.log("Failed to get patrons from API: ", error);
                    this.patronData = [];
                }).finally(() => {
                    this.loadingPatrons = false;
                });
        },
        filterPatrons(row, filter) {
            let show = true;
            let accountId = row?.accountId;
            if (filter.filterString !== '') {
                show = false;
                if (row.name.toLowerCase().indexOf(filter.filterString.toLowerCase()) !== -1) show = true;
                if (row.email && row.email.toLowerCase().indexOf(filter.filterString.toLowerCase()) !== -1) show = true;
                if (row.patronId.toString().toLowerCase().indexOf(filter.filterString.toLowerCase()) !== -1) show = true;
                if (accountId && accountId.toString().toLowerCase().indexOf(filter.filterString.toLowerCase()) !== -1) show = true;
            }
            if (this.filter.filterOnAccount === 'yes' && !accountId) show = false;
            if (this.filter.filterOnAccount === 'no' && accountId) show = false;
            return show;
        },
    },

    mounted: function () {
        this.getPatrons();
    }
}
</script>

<style scoped>
>>> .limit-column-width {
    max-width: 100px;
}
</style>
