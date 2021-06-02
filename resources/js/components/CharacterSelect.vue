<template>
    <div class="container">
        <h4>Select Character</h4>
        <div class="mb-2 text-center row">
            <div class="col">
                <character-card v-for="character in characters" v-bind:key="character.dbref" :character="character"
                                mode="tag" class="mr-2 mb-2 align-top"
                                @click="setActiveCharacter(character.dbref)"></character-card>
                <div v-for="i in emptyCharacterSlots" v-bind:key="i"
                     class="card empty-character-card border-primary mr-2 mb-2 align-top">
                    <div class="card-body h-100">
                        <a class="btn btn-primary" href="#"><span class="d-flow"><i
                            class="fas fa-plus btn-icon-left"></i>New character</span></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col">
                <a class="btn btn-primary" href="#"><i class="fas fa-cocktail btn-icon-left"></i>Buy Character Slot
                    {{ characterSlotCost }} {{ lex('accountcurrency') }}</a>
            </div>
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
    computed: {
        emptyCharacterSlots: function () {
            if (this.characterSlotCount > this.characters.length)
                return this.characterSlotCount - this.characters.length
            else return 0;
        }
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
.empty-character-card {
    display: inline-block;
    border-style: dashed;
    width: 240px;
    height: 100px;
}
</style>
