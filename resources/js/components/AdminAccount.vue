<template>
    <div class="container">
        <h4>View Account</h4>
        <dl>
            <div class="row">
                <dt class="col-sm-2">Id</dt>
                <dd class="col-sm-10">{{ account.id }}</dd>
            </div>
            <div class="row">
                <dt class="col-sm-2">Created</dt>
                <dd class="col-sm-10">{{ outputCarbonString(account.created) }}</dd>
            </div>
            <div class="row">
                <dt class="col-sm-2">Last Connected</dt>
                <dd class="col-sm-10">{{ outputCarbonString(account.lastConnected) }}</dd>
            </div>
            <div class="row bg-danger" v-if="account.locked">
                <dt class="col-sm-2">Locked</dt>
                <dd class="col-sm-10">{{ outputCarbonString(account.locked) }}</dd>
            </div>
            <div class="row">
                <dt class="col-sm-2">Referrals</dt>
                <dd class="col-sm-10">{{ account.referrals }}</dd>
            </div>
            <div class="row">
                <dt class="col-sm-2">Characters ({{ muckName }})</dt>
                <dd class="col-sm-10">
                    <div v-if="!account.characters.length">None</div>
                    <character-card v-for="character in account.characters" v-bind:key="character.dbref"
                                    :character="character" class="mr-2">
                    </character-card>
                </dd>
            </div>
            <div class="row">
                <dt class="col-sm-2">Emails</dt>
                <dd class="col-sm-10">
                    <table class="table table-striped">
                        <tr>
                            <th scope="col">Email</th>
                            <th scope="col">Registered</th>
                            <th scope="col">Verified</th>
                            <th scope="col">Primary?</th>
                        </tr>
                        <tr v-for="(details, email) in account.emails">
                            <td>{{ email }}</td>
                            <td>{{ outputCarbonString(details.created_at) }}</td>
                            <td>{{ outputCarbonString(details.verified_at) }}</td>
                            <td>{{ email == account.primary_email }}</td>
                        </tr>
                    </table>
                </dd>
            </div>
            <div class="row">
                <dt class="col-sm-2">Notes</dt>
                <dd class="col-sm-10">
                    <div v-if="!account.notes.length">None</div>
                    <div v-for="note in account.notes">
                        {{
                            outputCarbonString(note.whenAt) + ' ' + note.staffMember + '@' + note.game + ': ' + note.body
                        }}
                    </div>
                </dd>
            </div>
        </dl>
    </div>
</template>

<script>
import CharacterCard from "./CharacterCard";

export default {
    name: "admin-account",
    components: {CharacterCard},
    props: ['account', 'muckName'],
    data: function () {
        return {}
    },
    computed: {},
    methods: {
    }
}
</script>

<style scoped>

</style>
