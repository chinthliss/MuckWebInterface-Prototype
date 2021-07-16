<template>
    <div class="container">
        <h2 class="text-center">Character Generation</h2>
        <form action="" method="POST">
            <input type="hidden" name="_token" :value="csrf">

            <h3>Gender</h3>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>This is your starting gender and may change rapidly. See some of the perks below if you wish to
                        prevent or reduce the chance of this.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender"
                                   value="male" id="gender_male">
                            <label class="form-check-label" for="gender_male">Male</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender"
                                   value="female" id="gender_female">
                            <label class="form-check-label" for="gender_female">Female</label>
                        </div>
                        <div class="text-danger" role="alert">
                            <p v-for="error in errors.gender">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Birthday</h3>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>Your birthday can be between 1940 and present day.</p>
                    <p>Regardless of what date you were born, due to nanites accelerating development the minimum age of
                        a character is 18.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label class="sr-only" for="birthday">Birthday</label>
                        <input type="date" id="birthday" name="birthday" placeholder="YYYY-MM-DD">
                        <div class="text-danger" role="alert">
                            <p v-for="error in errors.birthday">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Faction</h3>
            <p>This is the faction that helped you get settled in this world. Whichever one you select will define how
                others see you, by assuming you follow that faction's ideals and broad outlook. It will also directly
                control where you start in the game.</p>
            <div class="form-group btn-group-toggle" data-toggle="buttons">
                <table>
                    <tr v-for="(item, name) in config.factions" class="align-top">
                        <td class="btn-group-toggle pr-2 pb-2">
                            <label class="btn btn-outline-primary w-100">
                                <input type="radio" name="faction" :value="name" autocomplete="off">
                                {{ name }}
                            </label>
                        </td>
                        <td class="pb-2">
                            <div v-html="item.description"></div>
                        </td>
                    </tr>
                </table>
                <div class="text-danger" role="alert">
                    <p v-for="error in errors.faction">{{ error }}</p>
                </div>
            </div>

            <h3>Starting Perks</h3>
            <p>These are only a fraction of the perks available and to streamline character generation their costs are
                hidden.</p>
            <p>They can be purchased at any time, so be sure to visit the perk page later to spend the rest of your
                points or to get more information.</p>
            <div class="form-group btn-group-toggle" data-toggle="buttons">
                <div v-for="category in perkCategories">
                    <h4>• {{ category.label }}</h4>
                    <p>{{ category.description }}</p>
                    <table>
                        <tr v-for="(item, name) in config.perks"
                            v-if="category.category === item.category" class="align-top">
                            <td class="btn-group-toggle pr-2 pb-2">
                                <label class="btn btn-outline-primary w-100" :disabled="item.disabled">
                                    <input type="checkbox" name="perks[]" :value="name"
                                           autocomplete="off" @change="updateExclusions('perks')">{{ name }}
                                </label>
                            </td>
                            <td class="pb-2">
                                <div v-html="item.description"></div>
                                <div class="small" v-if="item.excludes.length">Excludes: {{
                                        arrayToList(item.excludes)
                                    }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <h3>Flaws</h3>
            <p>You may take as many, or as few, flaws as you want.</p>
            <div class="form-group btn-group-toggle" data-toggle="buttons">
                <table>
                    <tr v-for="(item, name) in config.flaws" class="align-top">
                        <td class="btn-group-toggle pr-2 pb-2">
                            <label class="btn btn-outline-primary w-100" :disabled="item.disabled">
                                <input type="checkbox" name="flaws[]" :value="name"
                                       autocomplete="off" @change="updateExclusions('flaws')">{{ name }}
                            </label>
                        </td>
                        <td class="pb-2">
                            <div v-html="item.description"></div>
                            <div class="small" v-if="item.excludes.length">Excludes: {{
                                    arrayToList(item.excludes)
                                }}
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="text-danger" role="alert">
                    <p v-for="error in errors.flaws">{{ error }}</p>
                </div>
            </div>

            <div class="text-danger" role="alert">
                <p v-for="error in errors.other">{{ error }}</p>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">Submit Character</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: "character-initial-setup",
    props: {
        errors: {required: false},
        old: {type: Object, required: false},
        config: {type: Object, required: true}
    },
    methods: {
        arrayToList: function (arrayToParse) {
            return arrayToParse.join(', ');
        },
        updateExclusions: function (type) {
            // Pass 1 - get active exclusions
            let excluded = [];
            const config = this.config;
            $(`input[name="${type}[]"]:checked`).each(function () {
                const itemName = $(this).val();
                if (config[type] && config[type][itemName]) {
                    excluded = excluded.concat(config[type][itemName].excludes);
                }
            });
            console.log("Excluded:", excluded);
            // Pass 2 - enable/disable as required
            $(`input[name="${type}[]"]`).each(function () {
                const itemName = $(this).val(), jqThis = $(this);
                if (excluded.includes(itemName)) {
                    jqThis.prop('disabled', true);
                    jqThis.parent().addClass('disabled');
                    // Also ensure we're not set, just in case
                    jqThis.prop('checked', false);
                    jqThis.parent().removeClass('active');
                } else {
                    jqThis.prop('disabled', false);
                    jqThis.parent().removeClass('disabled');
                }
            });

        }
    },
    mounted: function () {
        //Restore any previous values
        if (this.old.gender) $(`input[name=gender][value="${this.old.gender}"]`).prop('checked', true);
        if (this.old.birthday) $('input[name=birthday]').val(this.old.birthday);
        if (this.old.faction) $(`input[name=faction][value="${this.old.faction}"]`).click();
        if (this.old.perks) {
            Object.values(this.old.perks).forEach(item => {
                $(`input[name="perks[]"][value="${item}"]`).click();
            });
        }
        if (this.old.flaws) {
            Object.values(this.old.flaws).forEach(item => {
                $(`input[name="flaws[]"][value="${item}"]`).click();
            });
        }
    },
    data: function () {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            perkCategories: [ // This is a list to allow order to be controlled easily
                {
                    category: "infection",
                    label: 'Infection Resistance',
                    description: "These perks control the overall rate of how quickly or how slowly transformation will effect you."
                },
                {
                    category: "gender",
                    label: 'Gender Perks',
                    description: "There are many more preference related perks but these are the critical ones controlling your gender preferences."

                },
                {
                    category: "appearance",
                    label: 'Appearance',
                    description: 'Following on from gender perks, these perks control how you appear to others.'
                },
                {
                    category: "history",
                    label: 'Historic',
                    description: 'Finally, these perks effect how you start in this world.'
                }
            ]
        }
    }
}
</script>

<style scoped>

</style>
