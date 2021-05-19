<template>
    <div class="card">
        <h4 class="card-header">Select Character</h4>
        <div class="card-body">
            <p class="text-center">You need to select an active character before continuing.</p>
            <div class="mb-2">
                <div v-for="character in characters" class="row">
                    <div class="col-lg-auto">
                        <character-card :character="character" mode="tag"
                                        @click="setActiveCharacter(character.dbref)"></character-card>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <p>Alternatively you can manage your characters, including creating a new one, from the character
                    dashboard.</p>
                <a class="btn btn-primary" href="/multiplayer/home">Character Dashboard</a>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "character-select",
    props: {
        characters: {type: Array, required: true}
    },
    data: function () {
        return {}
    },
    methods: {
        setActiveCharacter: (dbref) => {
            let promise = axios.post('/account/setactivecharacter', {dbref: dbref});
            return promise
                .then(response => {
                    if (response.data.redirectUrl) location.replace(response.data.redirectUrl);
                    else location.reload();
                });

        }
    }
}
</script>

<style scoped>

</style>
