<template>
    <div class="card">
        <h4 class="card-header">Select Character</h4>
        <div class="card-body">
            <div class="mb-2">
                <div v-for="character in characters" class="row">
                    <div class="col-lg-auto">
                        <character-card :character="character" mode="tag"
                                        @click="setActiveCharacter(character.dbref)"></character-card>
                    </div>
                </div>
            </div>
            <div>Character Slots:  {{ characterSlotCount }}</div>
            <div>Buy Character Slot: {{ characterSlotCost }} {{ lex('accountcurrency') }}</div>
        </div>
    </div>
</template>

<script>
export default {
    name: "character-select",
    props: {
        characters: {type: Array, required: true},
        characterSlotCount: {type: Number, required: true},
        characterSlotCost: {type: Number, required: true}
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
