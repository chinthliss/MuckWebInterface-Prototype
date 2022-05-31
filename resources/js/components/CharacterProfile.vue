<template>
    <div class="container">
        <div class="row">
            <div class="col">
                <h2>{{ this.character.name }}</h2>
            </div>
        </div>
        <div class="d-flex flex-column flex-xl-row">
            <div class="mx-auto">
                <div id="AvatarContainer" class="border border-primary">
                    <img v-if="avatarUrl" :src="avatarUrl" alt="Character Avatar" id="AvatarImg">
                    <div v-if="!avatarUrl" class="mt-4 text-center">
                        Avatars are disabled
                    </div>
                </div>
            </div>

            <div id="ProfileContainer" class="flex-grow-1 mt-2 mt-xl-0 ml-xl-4">
                <div v-if="profileLoading" class="text-center">
                    <div class="spinner-border" role="status"></div>
                    <div>Profile Loading...</div>
                </div>
                <div v-else>
                    <!-- Gender, Species and Height -->
                    <div class="d-flex">
                        <div>
                            <div class="label">Height</div>
                            <div class="value">{{ profile.height || '--' }}</div>
                        </div>
                        <div class="ml-4">
                            <div class="label">Gender</div>
                            <div class="value">{{ profile.sex || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <div class="label">Species</div>
                            <div class="value">{{ profile.species || '--' }}</div>
                        </div>
                    </div>

                    <!-- Level and Role -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Level</div>
                            <div class="value">{{ character.level  || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <div class="label">Role</div>
                            <div class="value">{{ profile.role || '--' }}</div>
                        </div>
                    </div>

                    <!-- Faction and Group -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Faction</div>
                            <div class="value">{{ profile.faction || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <div class="label">Group</div>
                            <div class="value">{{ profile.group || '--' }}</div>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div class="mt-2">
                        <div class="label">Short Description</div>
                        <div class="value">{{ profile.shortDescription || '--' }}</div>
                    </div>

                    <!-- WhatIs -->
                    <div class="mt-2">
                        <div class="label">What Is (wi)</div>
                        <div v-html="profile.whatIs || '--'" class="value"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lower Pane -->
        <div v-if="!profileLoading">

            <!-- Views -->
            <h3 class="mt-2">Views</h3>
            <div v-if="!Object.keys(profile.views).length">None configured</div>
            <b-table v-else dark small striped :items="profile.views" :fields="fields.views"></b-table>

            <!-- Pinfo -->
            <h3 class="mt-2">Pinfo</h3>
            <div v-if="!Object.keys(profile.pinfo).length">No custom fields</div>
            <b-table v-else dark small striped :items="profile.pinfo" :fields="fields.pinfo"></b-table>

            <!-- Equipment -->
            <h3 class="mt-2">Equipment</h3>
            <div v-if="!profile.equipment.length">Nothing equipped</div>
            <b-table v-else dark small striped :items="profile.equipment" :fields="fields.equipment"></b-table>

            <!-- Badges -->
            <h3 class="mt-2">Badges</h3>
            <div v-if="badgesLoading" class="d-flex align-items-center">
                <span class="spinner-border" role="status"></span>
                <span class="ml-2">Loading...</span>
            </div>
            <div v-else-if="!profile.badges" class="alert alert-danger">Badges failed to load</div>
            <div v-else-if="!profile.badges.length">No badges</div>
            <b-table v-else dark small striped :items="profile.badges" :fields="fields.badges"></b-table>

        </div>
    </div>
</template>

<script>
/**
 * @typedef {object} Character
 * @property {string} name
 * @property {int} level
 * @property {int} dbref
 *
 * @typedef {object} Profile
 * @property {string} sex
 * @property {string} species
 * @property {string} height
 * @property {string} role
 * @property {string} shortDescription
 * @property {string} faction
 * @property {string} group
 * @property {string} whatIs
 * @property {array} equipment
 * @property {array} views
 * @property {array} finger
 *
 */
export default {
    name: "character-profile",
    props: {
        /** @type {Character} */
        character: {Type: Object, required: true},
        controls: {Type: Boolean, required: false, default: false},
        avatarUrl: {Type: String, required: true},
        profileUrl: {Type: String, required: true},
        avatarWidth: {Type: Number, required: true},
        avatarHeight: {Type: Number, required: true}
    },
    data: function () {
        return {
            /** @type {Profile} */
            profile: null,
            profileLoading: true,
            badgesLoading: true,
            fields: {
                badges: [
                    {key: 'name', label: 'Badge', sortable: true},
                    {key: 'description', label: 'Description', sortable: false, formatter: 'outputArray'},
                    {key: 'awarded', label: 'Awarded', sortable: true, formatter: 'outputCarbonString'}
                ],
                pinfo: [
                    {key: 'field', label: 'Field', sortable: true},
                    {key: 'value', label: 'Value', sortable: false},
                ],
                views: [
                    {key: 'view', label: 'View', sortable: true},
                    {key: 'content', label: 'Content', sortable: false},
                ],
                equipment: [
                    {key: 'name', label: 'Name', sortable: true},
                    {key: 'description', label: 'Description', sortable: false},
                ]
            }
        }
    },
    mounted: function () {
        $('#AvatarContainer').css('width', this.avatarWidth).css('height', this.avatarHeight);
        $('#AvatarImg').css('width', this.avatarWidth).css('height', this.avatarHeight);

        axios.get(this.profileUrl).then((response) => {
            console.log("Character profile received.");
            this.profile = response.data;
            // Add slots for late loading things
            this.profile.badges = null;
            this.loadBadges();
        }).catch((error) => {
            console.log("There was an error with fetching the character profile: ", error);
        }).then(() => {
            this.profileLoading = false;
        });
    },
    methods: {
        outputArray: function (arrayToOutput) {
            if (!Array.isArray(arrayToOutput) || arrayToOutput.length === 0) return '--';
            return arrayToOutput.join('\n');
        },
        loadBadges: function() {
            axios.get(this.profileUrl + '/badges/').then((response) => {
                console.log("Badges received.");
                this.profile.badges = response.data;
            }).catch((error) => {
                console.log("There was an error with fetching badges: ", error);
            }).then(() => {
                this.badgesLoading = false;
            });
        }
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.label {
    font-size: 80%;
    font-weight: 600;
    color: $primary;
}

</style>
