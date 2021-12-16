<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <h2 class="text-center">Getting started with Multiplayer</h2>
                <h3>Verify you're ready to play</h3>
            </div>
        </div>
        <div id="checklist">
            <div class="row mb-2">
                <div class="col-10 col-lg-4">Create an account or login</div>
                <div class="col-2 col-lg-1"><i class="fas" v-bind:class="[ account ? ['fa-check', 'text-primary'] : ['fa-times', 'text-danger'] ]"></i></div>
                <div class="col" v-if="!account">
                    Go to the <a :href="accountUrl">Multiplayer Dashboard</a> to either create a new account or login to an existing one.
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-10 col-lg-4">Create at least one character</div>
                <div class="col-2 col-lg-1"><i class="fas" v-bind:class="[ character ? ['fa-check', 'text-primary'] : ['fa-times', 'text-danger'] ]"></i></div>
                <div class="col" v-if="account && !character">
                    Go to <a :href="characterUrl">Character Creation</a> to start making a new character.
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-10 col-lg-4">Make a character active</div>
                <div class="col-2 col-lg-1"><i class="fas" v-bind:class="[ characterActive ? ['fa-check', 'text-primary'] : ['fa-times', 'text-danger'] ]"></i></div>
                <div class="col" v-if="character && !characterActive">
                    Go to <a :href="characterActiveUrl">Character Select</a> to make one of your characters active.
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-10 col-lg-4">Finalize active character</div>
                <div class="col-2 col-lg-1"><i class="fas" v-bind:class="[ characterApproved ? ['fa-check', 'text-primary'] : ['fa-times', 'text-danger'] ]"></i></div>
                <div class="col" v-if="characterActive && !characterApproved">
                    Go to <a :href="characterApprovedUrl">Character Generation</a> to finish making your character..
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h3 class="mt-2">Connecting to the game</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-lg-6 border-primary border-lg-right">
                <h4>Via the website</h4>
                <p>The easiest way to access the game once you're set up is to use the webpage's <a :href="directConnectUrl">Direct Connect</a> client.</p>
                <p>However it's worth noting that 3rd party clients often provide far more functionality, such as extended interfaces and triggers. So once you've given the game a try, consider switching to a 3rd party client.</p>
            </div>
            <div class="col-12 col-lg-6">
                <h4>Via a 3rd party client</h4>
                <p>Please see the list of popular <a href="https://wiki.flexiblesurvival.com/w/MU*_Clients">Clients</a> and what they run on.</p>
                <p>Configure your client to connect to <b>flexiblesurvival.com</b> on port <b>2002</b>, which is the encrypted port.</p>
                <p>If your client can't connect on that port, then try one of the following: 2000, 2222, 22222, 20202, and 23.</p>
                <p>You'll need your character's name and password to connect. This is separate from your account password; if you've forgotten it you can use the <a :href="resetCharacterPasswordUrl">Reset Character Password</a> page to reset it.</p>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h3 class="mt-2">Optional steps</h3>
                <p>A number of pages on the site are especially useful for new characters, here's a recommended shortlist:</p>
            </div>
        </div>
        <div id="recommended_pages">
            <div class="row mb-2" v-for="item in pageRecommendations">
                <div class="col-12 col-lg-2"><a :href="item.url">{{ item.page }}</a></div>
                <div class="col-12 col-lg-10">{{ item.description }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h3 class="mt-2">Playing the game</h3>
                <p>Visit the <a href="https://wiki.flexiblesurvival.com/w/New_Newbie_Guide">Newbie Guide</a> on the wiki for getting more information.</p>
            </div>
        </div>


    </div>
</template>

<script>
import CharacterCard from "./CharacterCard";

export default {
    name: "multiplayer-getting-started",
    components: {CharacterCard},
    props: {
        account: {type: Boolean, required: true},
        accountUrl: {type: String, required: true},

        character: {type: Boolean, required: true},
        characterUrl: {type: String, required: true},

        characterActive: {type: Boolean, required: true},
        characterActiveUrl: {type: String, required: true},

        characterApproved: {type: Boolean, required: true},
        characterApprovedUrl: {type: String, required: true},

        directConnectUrl: {type: String, required: true},

        resetCharacterPasswordUrl: {type: String, required: true},

        pageRecommendations: {type: Object, required: false}
    },
    data: function () {
        return {}
    }
}
</script>

<style scoped>

</style>
