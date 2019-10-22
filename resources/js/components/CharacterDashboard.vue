<template>
    <div class="card">
        <h4 class="card-header">Dashboard</h4>
        <div class="card-body">
            <div v-for="character in characters">
                <character-card :character="character"></character-card>
            </div>
        </div>
    </div>


</template>

<script>
    import CharacterCard from "./CharacterCard";
    export default {
        name: "character-dashboard",
        components: {CharacterCard},
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
