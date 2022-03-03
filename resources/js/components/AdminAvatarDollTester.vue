<template>
    <div class="container">
        <h2>Avatar Paper Doll Tester</h2>

        <div class="d-flex flex-column flex-xl-row">

            <!-- Avatar -->
            <div class="mr-xl-4">
                <div class="avatarHolder">
                    <img class="avatar" v-if="avatarImg" :width="avatarWidth" :height="avatarHeight" :src="avatarImg"
                         alt="Avatar Render">
                </div>
            </div>

            <!-- Doll Controls -->
            <div class="mr-xl-4">

                <div class="form-group">
                    <label for="torso">Torso (Base)</label>
                    <select class="form-control" id="torso" v-model="torso" @change="updateAndRefresh">
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="head">Head</label>
                    <select class="form-control" id="head" v-model="head" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="arms">Arms</label>
                    <select class="form-control" id="arms" v-model="arms" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="legs">Legs</label>
                    <select class="form-control" id="legs" v-model="legs" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="groin">Groin</label>
                    <select class="form-control" id="groin" v-model="groin" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ass">Ass</label>
                    <select class="form-control" id="ass" v-model="ass" @change="updateAndRefresh">
                        <option value="">(Same as base)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="skin">Skin</label>
                    <select class="form-control" id="skin" v-model="skin" @change="updateAndRefresh">
                        <option value="">(Unset)</option>
                        <option :value="doll" v-for="doll in dolls">{{ doll }}</option>
                    </select>
                </div>

            </div>

            <!-- Gradient Controls -->
            <div>

                <div class="form-group">
                    <label for="skin1">Fur / Skin 1</label>
                    <select class="form-control" id="skin1" v-model="colors.skin1" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="skin2">Fur / Skin 2</label>
                    <select class="form-control" id="skin2" v-model="colors.skin2" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="skin3">Bare Skin</label>
                    <select class="form-control" id="skin3" v-model="colors.skin3" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="hair">Hair</label>
                    <select class="form-control" id="hair" v-model="colors.hair" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="eyes">Eye</label>
                    <select class="form-control" id="eyes" v-model="colors.eyes" @change="updateAndRefresh">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                    </select>
                </div>

            </div>

        </div>

        <h3 class="mt-2">Technical:</h3>
        <div>
            <div class="label">Code</div>
            <div class="value small text-break">{{ this.code }}</div>
        </div>
        <div>
            <div class="label">JSON</div>
            <div class="value">{{ this.json }}</div>
        </div>
        <div>
            <div class="label">Drawing Steps</div>
            <div class="value">
                <ul>
                    <li v-for="step in this.drawingSteps">
                        {{ step.part}}/{{ step.subPart }} from {{ step.dollName }}, using: {{ layerListToString(step.layers) }}
                    </li>
                </ul>
            </div>
        </div>


    </div>
</template>

<script>
export default {
    name: "admin-avatar-doll-tester",
    props: {
        drawingSteps: {Type: Array, required: true},
        dolls: {type: Array, required: true},
        gradients: {type: Array, required: true},
        initialCode: {type: String, required: true},
        baseUrl: {type: String, required: true},
        renderUrl: {type: String, required: true},
        avatarWidth: {type: Number, required: false, default: 384},
        avatarHeight: {type: Number, required: false, default: 640}
    },
    data: function () {
        return {
            avatarImg: null,
            code: "",
            json: "",
            head: "",
            torso: "",
            arms: "",
            legs: "",
            groin: "",
            ass: "",
            skin: "",
            colors: {
                skin1: '',
                skin2: '',
                skin3: '',
                hair: '',
                eyes: ''
            }
        };
    },
    mounted: function () {
        this.code = this.initialCode;
        this.json = JSON.parse(atob(this.code));
        this.torso = this.json.base;
        this.head = this.json.head ?? '';
        this.arms = this.json.arms ?? '';
        this.legs = this.json.legs ?? '';
        this.groin = this.json.groin ?? '';
        this.ass = this.json.ass ?? '';
        this.skin = this.json.skin ?? '';
        if (this.json.colors) {
            this.colors.skin1 = this.json.colors.skin1 || '';
            this.colors.skin2 = this.json.colors.skin2 || '';
            this.colors.skin3 = this.json.colors.skin3 || '';
            this.colors.hair = this.json.colors.hair || '';
            this.colors.eyes = this.json.colors.eyes || '';
        }
        this.avatarImg = this.renderUrl + '/' + this.code;
    },
    methods: {
        updateAndRefresh: function () {
            let newJson = {
                base: this.torso
            };
            if (this.head && this.head !== this.torso) newJson.head = this.head;
            if (this.arms && this.arms !== this.torso) newJson.arms = this.arms;
            if (this.legs && this.legs !== this.torso) newJson.legs = this.legs;
            if (this.groin && this.groin !== this.torso) newJson.groin = this.groin;
            if (this.ass && this.ass !== this.torso) newJson.ass = this.ass;
            if (this.skin) newJson.skin = this.skin;

            let setColors = {};
            if (this.colors.skin1) setColors.skin1 = this.colors.skin1;
            if (this.colors.skin2) setColors.skin2 = this.colors.skin2;
            if (this.colors.skin3) setColors.skin3 = this.colors.skin3;
            if (this.colors.hair) setColors.hair = this.colors.hair;
            if (this.colors.eyes) setColors.eyes = this.colors.eyes;
            if (Object.keys(setColors).length > 0) newJson.colors = setColors;

            let newCode = btoa(JSON.stringify(newJson));
            window.location = this.baseUrl + '/' + newCode;
        },
        layerListToString: unparsed => {
            let parsed = [];
            for (let i = 0; i < unparsed.length; i++) {
                parsed.push("layer " + unparsed[i].layerIndex + ", color " + unparsed[i].colorChannel);
            }
            return parsed.join(' >> ');
        }
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.avatarHolder img {
    border: 1px solid $primary;
    background-image: linear-gradient(45deg, #808080 25%, transparent 25%), linear-gradient(-45deg, #808080 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #808080 75%), linear-gradient(-45deg, transparent 75%, #808080 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
}

.label {
    color: $primary;
}

.value {
    margin-bottom: 4px;
}

</style>
