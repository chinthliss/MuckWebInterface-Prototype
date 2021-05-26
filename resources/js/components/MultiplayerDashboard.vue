<template>
    <div class="card">
        <h4 class="card-header">Multiplayer Dashboard</h4>
        <div class="card-body">
            <div class="text-center">You can create a new character from the
                <a class="btn btn-primary" :href="characterSelectUrl">Character Selection</a> page.
            </div>
            <div v-for="character in characters" class="row">
                <div class="col-lg-auto"><character-card :character="character" mode="tag"></character-card></div>
                <div class="col">
                    <button class="btn btn-primary" @click="setActiveCharacter(character.dbref)">Make Active Character</button>
                </div>
            </div>
        </div>
    </div>


</template>

<script>
    import CharacterCard from "./CharacterCard";
    export default {
        name: "multiplayer-dashboard",
        components: {CharacterCard},
        props: {
            characters: {type: Array, required: true},
            characterSelectUrl: {type: String, required: true}
        },
        data: function () {
            return {
            }
        },
        methods: {
            setActiveCharacter : (dbref) => {
                let promise = axios.post('/account/setactivecharacter', {dbref:dbref});
                return promise
                    .then(response => {
                        window.location.reload();
                    });

            }
        }
    }
</script>

<style scoped>

</style>
