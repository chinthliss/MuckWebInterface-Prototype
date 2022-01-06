<template>
    <div class="container">
        <h2>Avatar Paper Doll List</h2>
        <div v-if="Object.values(invalid).length > 0" class="alert alert-warning">
            The following avatar dolls were referenced but couldn't be found:
            <div v-for="(forms, avatar) in invalid">
                {{ avatar }}, referenced by: {{ Object.values(forms).join(', ') }}
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-2 mb-2" v-for="doll in dolls">
                <a :href="doll.edit">
                    <div class="card">
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
        </div>

        <h3>Usage Breakdown</h3>
        <div v-for="doll in dolls" v-if="doll.usage.length > 0">
            {{ doll.name }}: {{ doll.usage.join(', ') }}
        </div>

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

</style>
