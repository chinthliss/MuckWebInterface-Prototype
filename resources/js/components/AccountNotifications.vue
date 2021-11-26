<template>
    <div class="container">
        <h3>Notifications</h3>

        <div v-if="loadingContent" class="text-center my-2">
            <b-spinner class="align-middle primary" variant="primary"></b-spinner>
            <strong>Loading...</strong>
        </div>

        <div v-else-if="contentData.length === 0">
            You have no notifications. <i class="fas fa-thumbs-up"></i>
        </div>

        <div v-else>
            <div class="clearfix mb-2 d-flex align-items-center">
                <h4 class="mr-auto">Account Notifications</h4>
                <button class="btn btn-warning" @click="deleteAllNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>Delete All Notifications
                </button>
            </div>

            <b-table dark striped hover small
                     :items="contentData"
                     :fields="contentFields"
                     :busy="loadingContent"
            >
                <template #cell(controls)="data">
                    <i class="fas"
                       :class="data.item.read_at ? ['text-muted', 'fa-envelope'] : ['text-primary', 'fa-envelope-open']"></i>
                    <button class="btn btn-secondary" :data-id="data.item.id" @click="deleteNotification"><i
                        class="fas fa-trash btn-icon-left"></i>Delete
                    </button>
                </template>

            </b-table>
        </div>

    </div>
</template>

<script>
export default {
    name: "account-notifications",
    props: ['apiUrl'],
    data: function () {
        return {
            loadingContent: false,
            contentData: [],
            contentFields: [
                {
                    key: 'controls',
                    label: ''
                },
                {
                    key: 'created_at',
                    label: 'When',
                    formatter: 'outputCarbonString',
                    tdClass: 'align-middle',
                    sortable: true
                },
                {
                    key: 'character',
                    label: 'Character',
                    tdClass: 'align-middle',
                    sortable: true
                },
                {
                    key: 'message',
                    label: 'Message',
                    tdClass: 'align-middle'
                },
            ]
        }
    },
    mounted() {
        this.getContent();
    },
    methods: {
        getContent: function () {
            this.loadingContent = true;
            let promise = axios.get(this.apiUrl);
            return promise
                .then(response => {
                    this.contentData = response.data;
                }).catch(error => {
                    console.log("Failed to load content from API: ", error);
                    this.contentData = [];
                }).finally(() => {
                    this.loadingContent = false;
                });
        },
        deleteNotification: function (e) {
            let id = parseInt(e.target.getAttribute('data-id'));
            let promise = axios.delete(this.apiUrl + '/' + id);
            return promise
                .then(response => {
                    //Find the actual entry with this ID to delete locally
                    for (let i = 0; i < this.contentData.length; i++) {
                        if (this.contentData[i].id === id) this.contentData.splice(i, 1);
                    }
                }).catch(error => {
                    console.log("Failed to delete: ", error);
                });
        },
        deleteAllNotifications: function () {
            let promise = axios.delete(this.apiUrl);
            return promise
                .then(response => {
                    this.contentData = [];
                }).catch(error => {
                    console.log("Failed to delete notifications: ", error);
                });
        }
    }
}
</script>

<style scoped>

</style>
