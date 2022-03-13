<template>
    <div class="container">
        <div class="row">
            <div class="col text-center">
                <h1>{{ this.character.name }}</h1>
                <img :src="this.avatar" alt="Character Avatar">
                <h1><i class="fas fa-hammer"></i>To Be Completed <i class="fas fa-hammer fa-flip-horizontal"></i></h1>
            </div>
        </div>
    </div>
</template>

<script>
    /**
     * @typedef {object} Character
     * @property {string} name
     * @property {int} level
     * @property {int} dbref
     */
    export default {
        name: "character-profile",
        props: {
            /** @type {Character} */
            character: {Type: Object, required: true},
            avatar: {Type: String, required: true}
        }
    }
</script>

<style scoped>

</style>
