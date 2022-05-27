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
                    <div v-if="avatarLoading" class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <div>Avatar Loading...</div>
                    </div>
                    <img v-if="avatar" src="" alt="Character Avatar">
                    <div v-if="!avatar && !avatarLoading" class="text-center">
                        No Avatar to Display
                    </div>
                </div>
                <div v-if="controls && avatarEditUrl">
                    <a class="btn btn-primary w-100" :href="avatarEditUrl">Edit Avatar</a>
                </div>
            </div>

            <div id="ProfileContainer" class="flex-grow-1 mt-2 mt-xl-0 ml-xl-4">
                <div v-if="profileLoading" class="text-center">
                    <div class="spinner-border" role="status"></div>
                    <div>Profile Loading...</div>
                </div>
                <div v-else>
                    <!-- Sex, Species and Height -->
                    <div class="d-flex">
                        <div>
                            <div class="label">Height</div>
                            <div class="value">{{ profile.height || '--' }}</div>
                        </div>
                        <div class="ml-4">
                            <div class="label">Sex</div>
                            <div class="value">{{ profile.sex || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ml-4">
                            <div class="label">Species</div>
                            <div class="value">{{ profile.species || '--' }}</div>
                        </div>
                    </div>

                    <!-- Faction and Group -->
                    <div class="mt-2 d-flex">
                        <div>
                            <div class="label">Faction</div>
                            <div class="value">{{ profile.faction || '--' }}</div>
                        </div>
                        <div class="flex-grow-1 ml-xl-4">
                            <div class="label">Group</div>
                            <div class="value">{{ profile.group || '--' }}</div>
                        </div>
                    </div>

                    <!-- WhatIs -->
                    <div class="mt-2">
                        <div class="label">Preferences (WhatIs)</div>
                        <div class="value">{{ profile.whatIs || '--' }}</div>
                    </div>

                    <!-- Short Description -->
                    <div class="mt-2">
                        <div class="label">Short Description</div>
                        <div class="value">{{ profile.shortDescription || '--' }}</div>
                    </div>

                    <!-- Views -->
                    <h3 class="mt-2">Views</h3>
                    <div v-if="!Object.keys(profile.views).length">None configured</div>
                    <table v-else class="table table-borderless table-sm">
                        <thead>
                        <tr>
                            <th>View</th>
                            <th>Detail</th>
                        </tr>
                        </thead>
                        <tr v-for="(detail, view) in profile.views">
                            <td>{{ view }}</td>
                            <td>{{ detail }}</td>
                        </tr>
                    </table>

                    <!-- Pinfo -->
                    <h3 class="mt-2">Pinfo</h3>
                    <div v-if="!Object.keys(profile.pinfo).length">No custom fields</div>
                    <table v-else class="table table-borderless table-sm">
                        <thead>
                        <tr>
                            <th>Field</th>
                            <th>Detail</th>
                        </tr>
                        </thead>
                        <tr v-for="(detail, field) in profile.pinfo">
                            <td>{{ field }}</td>
                            <td>{{ detail }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lower Pane -->
        <div v-if="!profileLoading">
            <!-- Badges -->
            <h3 class="mt-2">Badges</h3>
            <div v-if="!profile.badges.length">No badges</div>
            <table v-else class="table table-sm">
                <thead>
                <tr>
                    <th>Badge</th>
                    <th>Awarded</th>
                    <th>Description</th>
                </tr>
                </thead>
                <tr v-for="badge in profile.badges">
                    <td>{{ badge.name }}</td>
                    <td>{{ outputCarbonString(badge.awarded) }}</td>
                    <td>{{ badge.description }}</td>
                </tr>
            </table>

            <!-- Equipment -->
            <h3 class="mt-2">Equipment</h3>
            <div v-if="!profile.equipment.length">Nothing equipped</div>
            <div v-else>
                !! Equipment !!
            </div>
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
 * @property {string} shortDescription
 * @property {string} faction
 * @property {string} group
 * @property {string} whatIs
 * @property {array} badges
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
        avatarEditUrl: {Type: String, required: false},
        profileUrl: {Type: String, required: true},
        avatarWidth: {Type: Number, required: true},
        avatarHeight: {Type: Number, required: true}
    },
    data: function () {
        return {
            avatar: null,
            avatarLoading: true,
            /** @type {Profile} */
            profile: null,
            profileLoading: true
        }
    },
    mounted: function () {
        $('#AvatarContainer').css('width', this.avatarWidth).css('height', this.avatarHeight);
        this.avatar = new Image();
        this.avatar.onload = () => {
            this.avatarLoading = false;
        }
        this.avatar.onerror = (e) => {
            console.log("Avatar didn't load (this could be expected if they're disabled): ", e);
            this.avatar = null;
            this.avatarLoading = false;
        }
        this.avatar.src = this.avatarUrl;

        axios.get(this.profileUrl).then((response) => {
            console.log("Character profile received.");
            this.profile = response.data;
        }).catch((error) => {
            console.log("There was an error with fetching the character profile: ", error);
        }).then(() => {
            this.profileLoading = false;
        });
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
