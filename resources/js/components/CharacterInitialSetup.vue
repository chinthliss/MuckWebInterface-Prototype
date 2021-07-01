<template>
    <div class="container">
        <h2 class="text-center">Character Generation</h2>
        <form action="" method="POST">

            <h3>Gender</h3>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>This is your starting gender and may change rapidly. See some of the perks below if you wish to prevent or reduce the chance of this.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" value="male" id="gender_male">
                            <label class="form-check-label" for="gender_male">Male</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" value="female" id="gender_female">
                            <label class="form-check-label" for="gender_female">Female</label>
                        </div>
                    </div>
                </div>
            </div>

            <h3>Birthday</h3>
            <div class="row">
                <div class="col-12 col-md-6">
                    <p>Your birthday can be anytime between 1940 and present day.</p>
                    <p>Regardless of what date you were born, due to nanites accelerating development the minimum age of a character is 18.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label class="sr-only" for="birthday">Birthday</label>
                        <input type="date" id="birthday">
                    </div>
                </div>
            </div>

            <h3>Faction</h3>
            <p>This is the faction that helped you get settled in this world. Whichever one you select will define how others see you, by assuming you follow that faction's ideals and broad outlook. It will also directly control where you start in the game.</p>
            <div class="form-group btn-group-toggle" data-toggle="buttons">
                <table>
                    <tr v-for="(faction, name) in config.factions">
                        <td class="btn-group-toggle">
                            <label class="btn btn-secondary">
                                <input type="radio" name="faction" :value="name" autocomplete="off">{{ name }}
                            </label>
                        </td>
                        <td>{{ faction.description }}</td>
                    </tr>
                </table>
            </div>

            <h3>Starting Perks</h3>
            <p>The perks you chose here are especially important because they effect your preferences on how you respond to nanite changes.</p>
            <p>There are many more perks available - be sure to visit the perk page after character generation to spend the points you start with.</p>
            <div class="form-group btn-group-toggle" data-toggle="buttons">
                <table>
                    <tr v-for="(faction, name) in config.perks">
                        <td class="btn-group-toggle">
                            <label class="btn btn-secondary">
                                <input type="checkbox" name="faction" :value="name" autocomplete="off">{{ name }}
                            </label>
                        </td>
                        <td>{{ faction.description }}
                            <div class="text-muted" v-if="faction.excludes.length">Excludes: {{ arrayToList(faction.excludes) }} </div>
                        </td>
                    </tr>
                </table>
            </div>

            <h3>Flaws</h3>
            <p>You may take as many, or as few, flaws as you want.</p>
            <div class="form-group">
                <div>[Full width list]</div>
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
        arrayToList: function(arrayToParse) {
            return arrayToParse.join(', ');
        }
    },
    data: function () {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').content
        }
    }
}
</script>

<style scoped>

</style>
