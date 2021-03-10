<template>
    <div class="card">
        <h4 class="card-header">View Account</h4>
        <div class="card-body">
            <dl>
                <div class="row">
                    <dt class="col-sm-2">Id</dt>
                    <dd class="col-sm-10">{{ account.id }}</dd>
                </div>
                <div class="row">
                    <dt class="col-sm-2">Created</dt>
                    <dd class="col-sm-10">{{ account.created }}</dd>
                </div>
                <div class="row">
                    <dt class="col-sm-2">Characters</dt>
                    <dd class="col-sm-10">
                        <div v-if="!account.characters.length">None</div>
                        <div v-for="character in account.characters"><character-card :character="character" mode="tag"></character-card></div>
                    </dd>
                </div>
                <div class="row">
                    <dt class="col-sm-2">Notes</dt>
                    <dd class="col-sm-10">
                        <div v-if="!account.notes.length">None</div>
                        <div v-for="note in account.notes">
                            {{ outputCarbonString(note.whenAt) + ' ' + note.staffMember + ': ' + note.body }}
                        </div>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</template>

<script>
import CharacterCard from "./CharacterCard";
export default {
    name: "admin-account",
    components: {CharacterCard},
    props: ['account'],
    data: function () {
        return {}
    },
    computed: {},
    methods: {
        outputCarbonString: function(carbonString) {
            if (!carbonString) return '--';
            return new Date(carbonString).toLocaleString();
        }
    }
}
</script>

<style scoped>

</style>
