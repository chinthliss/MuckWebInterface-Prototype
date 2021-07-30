<template>
    <div class="container">
        <h4>Stretch Goals</h4>
        As the monthly contributions hit certain values, new things will be added to the game for all to enjoy.
        <div class="goal-next" v-if="progressToNext">{{ progressToNext }} dollars to the next goal!</div>
        <div class="goal-container">
            <div class="goal" v-bind:class="[ amount <= progress ? 'text-primary' : 'text-muted' ]"
                 v-for="(goal,amount) in goals">
                <i class="goal-lock fas" v-bind:class="[ amount < progress ? 'fa-lock-open' : 'fa-lock' ]"></i>
                <span class="goal-amount">${{ amount }}</span>
                <span class="goal-description">{{ goal }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "game-stretch-goals",
    props: ['goals', 'progress'],
    data: function () {
        return {}
    },
    methods: {},
    computed: {
        progressToNext: function () {
            let next = null;
            for (let amount of Object.keys(this.goals)) {
                amount = parseInt(amount);
                if (amount > this.progress && (!next || amount < next)) next = amount;
            }
            return next ? next - this.progress : null;
        }
    }
}
</script>

<style scoped>
.goal i {
    width: 18px;
}

.goal-amount {
    width: 60px;
    display: inline-block;
    text-align: right;
}

.goal-next {
    font-weight: bold;
}
</style>
