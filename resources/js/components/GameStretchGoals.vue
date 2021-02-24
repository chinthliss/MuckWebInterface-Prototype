<template>
    <div class="card">
        <h4 class="card-header">Stretch Goals</h4>
        <div class="card-body">
            As the monthly contributions hit certain values, new things will be added to the game for all to enjoy.
            <div class="goal-next" v-if="progressToNext">{{ progressToNext }} dollars to the next goal!</div>
            <div class="goal-container">
                <div class="goal" v-bind:class="[ amount <= progress ? 'text-primary' : 'text-secondary' ]"
                     v-for="(goal,amount) in goals">
                    <b-icon-unlock-fill v-if="amount < progress"></b-icon-unlock-fill>
                    <b-icon-lock-fill v-else></b-icon-lock-fill>
                    <span class="goal-amount">${{ amount }}</span>
                    <span class="goal-description">{{ goal }}</span>
                </div>
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
        progressToNext: function() {
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
    width:18px;
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
