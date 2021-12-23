<template>
    <div class="container">
        <h2>Avatar Paper Doll Tester</h2>
        <div class="row">
            <div class="col-12 col-xl-2 mb-2" v-for="doll in dolls">
                <div class="card">
                    <div class="card-img-top" >
                        <img :src="doll.url" alt="Avatar Doll Thumbnail" class="d-block m-auto">
                    </div>
                    <div class="card-body m-auto">
                        {{ doll.name }} <span class="badge badge-pill badge-info" v-if="doll.usage.length > 0">{{ doll.usage.length }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="Object.values(invalid).length > 0">
            The following avatar dolls were referenced but couldn't be found:
            <div v-for="(forms, avatar) in invalid">
                {{ avatar }}: {{ Object.values(forms).join(', ') }}
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "admin-avatar-doll-tester",
    props: {
        dolls: {type: Array, required: true},
        invalid: {type: Object, required: false}
    }
}
</script>

<style scoped>

</style>
