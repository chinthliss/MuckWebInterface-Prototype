<template>
    <div class="container">
        <h3>Notifications</h3>
        <div v-if="loadingContent" class="text-center my-2">
            <b-spinner class="align-middle primary" variant="primary"></b-spinner>
            <strong>Loading...</strong>
        </div>

        <div v-if="noNotifications">
            You have no notifications. <i class="fas fa-thumbs-up"></i>
        </div>
        <!-- Account or game notifications -->
        <div v-if="contentData.user && contentData.user.length">
            <div class="clearfix mb-2 d-flex align-items-center">
                <h4 class="mr-auto">Account Notifications</h4>
                <button class="btn btn-warning" @click="deleteAllAccountNotifications">
                    <i class="fas fa-trash btn-icon-left"></i>Delete all account notifications
                </button>
            </div>

            <b-table dark striped hover small
                     :items="contentData.user"
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

        <!-- Character notifications -->
        <div
            v-if="contentData.character"
            v-for="(notifications, character) in contentData.character"
            v-bind:data="notifications"
            v-bind:key="character"
        >
            <div class="clearfix mb-2 d-flex align-items-center">
                <h4 class="mr-auto">{{ character }}</h4>
                <button class="btn btn-warning" @click="deleteAllNotificationsFor(character)">
                    <i class="fas fa-trash btn-icon-left"></i>Delete all notifications for {{ character }}
                </button>
            </div>

            <b-table dark striped hover small
                     :items="notifications"
                     :fields="contentFields"
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
        deleteAllAccountNotifications: function () {
            let promise = axios.delete(this.apiUrl, {
                data: {
                    'scope': 'account'
                }
            });
            return promise
                .then(response => {
                    this.contentData.user = [];
                }).catch(error => {
                    console.log("Failed to delete account notifications: ", error);
                });
        },
        deleteAllNotificationsFor: function (character) {
            let promise = axios.delete(this.apiUrl, {
                data: {
                    'scope': 'character',
                    'dbref': this.contentData.character[character][0].character_dbref
                }
            });
            return promise
                .then(response => {
                    Vue.delete(this.contentData.character, character);
                }).catch(error => {
                    console.log("Failed to delete notifications for character " + character + ": ", error);
                });
        }
    },
    computed: {
        noNotifications: function () {
            if (this.loadingContent) return false;
            if (!this.contentData || this.contentData.length === 0) return true;
            if (this.contentData.user.length === 0 && this.contentData.character.length === 0) return true;
            return false;
        }
    }
}
</script>

<style scoped>

</style>
