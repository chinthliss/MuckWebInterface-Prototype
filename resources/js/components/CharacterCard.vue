<template>
    <div class="card character-card" :class="'mode-' + mode">
        <a :href="'/p/' + character.name">
            <div class="card-body">
                <div class="avatar"></div>
                <div class="name">{{ character.name }}</div>
                <div class="level">{{ character.level }}</div>
                <div v-if="character.wizard" class="staff">Staff</div>
            </div>
        </a>
    </div>
</template>

<script>
    export default {
        name: "character-card",
        props: {
            character: {type: Object, required: true},
            mode: {type: String, default: 'tag'}
        },
        data: function () {
            return {}
        },
        methods: {}
    }
</script>

<style scoped lang="scss">
    // Shared in all modes
    .card-body {
        position:absolute;
        left:1px;
        right:1px;
        top:1px;
        bottom:1px;
        background:red;
    }

    // Tag Mode
    .character-card.mode-tag {
        width: 240px;
        height: 100px;
        box-shadow: 2px 2px 2px #888888;

        .avatar {
            position: absolute;
            z-index: 1;
            left: 0;
            top: 0;
            bottom: 0;
            width: 192px;
            background: #3490dc;
        }

        .name {
            position: absolute;
            z-index: 2;
            right: 3px;
            top: 3px;
            width: 124px;
            height: 40px;
            background: navy;
            color: white;
            text-align: right;
            font-weight: bold;
            font-size: large;
            font-family: Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans serif;
        }

        .level {
            position: absolute;
            z-index: 2;
            left: 198px;
            right: 3px;
            top: 48px;
            bottom: 3px;
            background: darkcyan;
            color: white;
            text-align: center;
        }

        .staff {
            position: absolute;
            z-index: 2;
            width: 98px;
            height: 24px;
            color: black;
            text-align: center;
            top: 35px;
            left: 12px;
            background: cornsilk;
            transform: translateX(-50%) rotate(-90deg);
        }

    }

</style>
