<template>
    <div class="card">
        <h4 class="card-header">Notifications</h4>
        <div class="card-body">
            <div v-if="loadingContent" class="text-center my-2">
                <b-spinner class="align-middle primary" variant="primary"></b-spinner>
                <strong>Loading...</strong>
            </div>

            <div v-if="contentData.length === 0 && !loadingContent">
                You have no notifications. <i class="fas fa-thumbs-up"></i>
            </div>
            <div v-else class="d-flex justify-content-center mb-2">
                <button class="btn btn-warning" @click="deleteAllNotifications">
                    <i class="fas fa-trash"></i>Delete all notifications
                </button>
            </div>
            <!-- Account or game notifications -->
            <div v-if="contentData.user">
                <b-table dark striped hover small
                         :items="contentData.user"
                         :fields="contentFields"
                         :busy="loadingContent"
                >
                    <template #cell(controls)="data">
                        <i class="fas"
                           :class="data.item.read_at ? ['text-muted', 'fa-envelope'] : ['text-primary', 'fa-envelope-open']"></i>
                        <button class="btn btn-secondary" :data-id="data.item.id" @click="deleteNotification"><i
                            class="fas fa-trash"></i>Delete
                        </button>
                    </template>

                </b-table>
            </div>

            <!-- Character notifications -->
            <div
                v-if="contentData.character"
                v-for="(notifications, character) in contentData.character"
                v-bind:data="notifications"
                v-bind:key="character"
            >
                <h4>{{ character }}</h4>
                <b-table dark striped hover small
                         :items="notifications"
                         :fields="contentFields"
                >
                    <template #cell(controls)="data">
                        <i class="fas"
                           :class="data.item.read_at ? ['text-muted', 'fa-envelope'] : ['text-primary', 'fa-envelope-open']"></i>
                        <button class="btn btn-secondary" :data-id="data.item.id" @click="deleteNotification"><i
                            class="fas fa-trash"></i>Delete
                        </button>
                    </template>

                </b-table>
            </div>
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
                    tdClass: 'align-middle'
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
        outputCarbonString: function (carbonString) {
            if (!carbonString) return '--';
            return new Date(carbonString).toLocaleString();
        },
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
                    for (let i = 0; i < this.contentData.user.length; i++) {
                        if (this.contentData.user[i].id === id) this.contentData.user.splice(i, 1);
                    }
                    for (let [character, notifications] of Object.entries(this.contentData.character)) {
                        console.log(character);
                        for (let i = 0; i < notifications.length; i++) {
                            if (notifications[i].id === id) notifications.splice(i, 1);
                        }
                        if (notifications.length === 0) Vue.delete(this.contentData.character, character);
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
                    console.log("Failed to delete all: ", error);
                });
        }
    }
}
</script>

<style scoped>

</style>
