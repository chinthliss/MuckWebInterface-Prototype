<template>
    <div class="card">
        <h4 class="card-header">Dashboard</h4>
        <div class="card-body">
            <div v-for="(character, dbref) in characters">
                {{ character.name }}
            </div>
        </div>
    </div>


</template>

<script>
    export default {
        name: "character-dashboard",
        props: ['characters'],
        data: function () {
            return {
            }
        },
        methods: {
        }
    }
</script>

<style scoped>

</style>
