<template>
    <div class="container">
        <h2>Avatar Paper Doll Tester</h2>

        <p>Resume or share this configuration via <a :href="getUrlForCode()">this link</a>.</p>
        <p>Code: {{ this.code }} </p>
        <p>JSON: {{ this.json }} </p>
        <p>Drawing Steps: {{ this.drawingSteps }} </p>

        <div class="form-group">
            <label for="torso">Torso (Base)</label>
            <select class="form-control" id="torso" v-model="torso" @change="updateAndRefresh">
                <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="head">Head</label>
            <select class="form-control" id="head" v-model="head" @change="updateAndRefresh">
                <option value="">(Same as base)</option>
                <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="arms">Arms</label>
            <select class="form-control" id="arms" v-model="arms" @change="updateAndRefresh">
                <option value="">(Same as base)</option>
                <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="legs">Legs</label>
            <select class="form-control" id="legs" v-model="legs" @change="updateAndRefresh">
                <option value="">(Same as base)</option>
                <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="groin">Groin</label>
            <select class="form-control" id="groin" v-model="groin" @change="updateAndRefresh">
                <option value="">(Same as base)</option>
                <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ass">Ass</label>
            <select class="form-control" id="ass" v-model="ass" @change="updateAndRefresh">
                <option value="">(Same as base)</option>
                <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
            </select>
        </div>

        <div>
            <h3>Avatar</h3>
            <div class="avatarHolder">
                <img class="avatar" v-if="avatarImg" :width="avatarWidth" :height="avatarHeight" :src="avatarImg" alt="Avatar Render">
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "admin-avatar-doll-tester",
    props: {
        drawingSteps: {Type: Array, required: true},
        dolls: {type: Array, required: true},
        initialCode: {type: String, required: true},
        baseUrl: {type: String, required: true},
        renderUrl: {type: String, required: true},
        avatarWidth: {type: Number, required: false, default: 384},
        avatarHeight: {type: Number, required: false, default: 640}
    },
    data: function () {
        return {
            avatarImg: null,
            code: "",
            json: "",
            head: "",
            torso: "",
            arms: "",
            legs: "",
            groin: "",
            ass: ""
        };
    },
    mounted: function () {
        this.code = this.initialCode;
        this.json = JSON.parse(atob(this.code));
        this.torso = this.json.base;
        this.head = this.json.head ?? '';
        this.arms = this.json.arms ?? '';
        this.legs = this.json.legs ?? '';
        this.groin = this.json.groin ?? '';
        this.ass = this.json.ass ?? '';
        this.avatarImg = this.renderUrl + '/' + this.code;
    },
    methods: {
        getUrlForCode: function () {
            return this.baseUrl + '/' + this.code;
        },
        updateAndRefresh: function(part) {
            this.refreshCode();
            window.location = this.getUrlForCode();
        },
        refreshCode: function() {
            let avatar = {
                base: this.torso
            };
            if (this.head && this.head !== this.torso) avatar.head = this.head;
            if (this.arms && this.arms !== this.torso) avatar.arms = this.arms;
            if (this.legs && this.legs !== this.torso) avatar.legs = this.legs;
            if (this.groin && this.groin !== this.torso) avatar.groin = this.groin;
            if (this.ass && this.ass !== this.torso) avatar.ass = this.ass;
            this.json = JSON.stringify(avatar);
            this.code = btoa(this.json);
        }
    }
}
</script>

<style scoped lang="scss">
    @import '@/_variables.scss';

    .avatarHolder img {
        border: 1px solid $primary;
    }
</style>
