<template>
    <div class="card">
        <h4 class="card-header">Dashboard</h4>
        <div class="card-body">
            <div v-for="character in characters" class="row">
                <div class="col-lg-auto"><character-card :character="character" mode="tag"></character-card></div>
                <div class="col">
                    <button class="btn btn-primary">Make Active Character</button>
                </div>
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
