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
                        {{ doll.name }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "admin-avatar-doll-tester",
    props: {
        dolls: {type: Array, required: true}
    }
}
</script>

<style scoped>

</style>
