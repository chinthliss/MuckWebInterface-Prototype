<template>
    <div class="container">
        <h2>Avatar Paper Doll List</h2>
        <div v-if="Object.values(invalid).length > 0" class="alert alert-warning">
            The following avatar dolls were referenced but couldn't be found:
            <div v-for="(forms, avatar) in invalid">
                {{ avatar }}, referenced by: {{ Object.values(forms).join(', ') }}
            </div>
        </div>

        <div>
            <a :href="doll.edit" v-for="doll in dolls">
                <div class="card doll-card">
                    <div class="card-img-top">
                        <img :src="doll.url" alt="Avatar Doll Thumbnail" class="d-block m-auto">
                    </div>
                    <div class="card-body">
                        <div class="text-center small">{{ doll.name }}</div>
                        <div class="text-center"><span class="badge badge-pill badge-info"
                                                       v-bind:class="[doll.usage.length ? 'badge-info' : 'badge-warning']">{{
                                doll.usage.length
                            }}</span></div>
                    </div>
                </div>
            </a>
        </div>

        <h3>Usage Breakdown</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Doll</th>
                <th>Used by</th>
            </tr>
            </thead>
            <tr v-for="doll in dolls" v-if="doll.usage.length > 0">
                <td>{{ doll.name }}</td>
                <td>{{ doll.usage.join(', ') }}</td>
            </tr>
        </table>
    </div>
</template>

<script>
export default {
    name: "admin-avatar-doll-list",
    props: {
        dolls: {type: Array, required: true},
        invalid: {type: Object, required: false},
        avatarWidth: {type: Number, required: false, default: 384},
        avatarHeight: {type: Number, required: false, default: 640}
    }
}
</script>

<style scoped>
.doll-card {
    width: 185px;
    height: 260px;
    display: inline-block;
}
</style>
