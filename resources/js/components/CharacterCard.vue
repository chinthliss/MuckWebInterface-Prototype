<template>
    <a :href="'/p/' + character.name">
    <div class="character">
        <div class="avatar"></div>
        <div class="name">{{ character.name }}</div>
        <div class="level">{{ character.level }}</div>
        <span class="flag">Flag</span>
    </div>
    </a>
</template>

<script>
    export default {
        name: "character-card",
        props: {
            character: {type: Object, required: true}
        },
        data: function () {
            return {
            }
        },
        methods: {
        }
    }
</script>

<style scoped>
    .character {
        position:relative;
        width:300px;
        height:100px;
        border:1px solid black;
    }

    .avatar {
        position:absolute;
        left:3px;
        width:192px;
        top:3px;
        bottom:3px;
        background:black;
    }

    .name {
        position:absolute;
        left:198px;
        right:3px;
        top:3px;
        bottom:3px;
        background:navy;
        color:white;
        text-align:center;
        font-family: Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans serif;
    }

    .level {
        position:absolute;
        left:198px;
        right:3px;
        top:48px;
        bottom:3px;
        background:darkcyan;
        color:white;
        text-align:center;
    }

    .flag {
        position:absolute;
        width:92px;
        height:24px;
        color:black;
        text-align:center;
        z-index: 1;
        top: 37px;
        left: 15px;
        background: cornsilk;
        transform:  translateX(-50%) rotate(-90deg);
    }
</style>
