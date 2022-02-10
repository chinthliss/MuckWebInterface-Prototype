<template>
    <div class="container">
        <h2>Avatar Editor</h2>
        TBC

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

    </div>
</template>

<script>
export default {
    name: "avatar-edit",
    props: {
        presentCustomizations: {Type: Object, required: true},
        gradients: {type: Array, required: true},
        renderUrl: {type: String, required: true},
        avatarWidth: {type: Number, required: false, default: 384},
        avatarHeight: {type: Number, required: false, default: 640}
    },
    data: function () {
        return {
            avatarImg: '',
            colors: {
                skin1: null,
                skin2: null,
                skin3: null,
                hair: null,
                eyes: null
            }
        };
    },
    mounted: function () {
        this.colors.skin1 = this.presentCustomizations?.colors?.skin1 || '';
        this.colors.skin2 = this.presentCustomizations?.colors?.skin2 || '';
        this.colors.skin3 = this.presentCustomizations?.colors?.skin3 || '';
        this.colors.hair = this.presentCustomizations?.colors?.hair || '';
        this.colors.eyes = this.presentCustomizations?.colors?.eyes || '';
        this.updateDollImage();
    },
    methods: {
        updateDollImage: function() {
            //For the editor the only thing on the doll loaded from the server is the coloring
            let setColors = {};
            if (this.colors.skin1) setColors.skin1 = this.colors.skin1;
            if (this.colors.skin2) setColors.skin1 = this.colors.skin2;
            if (this.colors.skin3) setColors.skin1 = this.colors.skin3;
            if (this.colors.hair) setColors.skin1 = this.colors.hair;
            if (this.colors.eyes) setColors.skin1 = this.colors.eyes;
            this.avatarImg = this.renderUrl + '/' + (Object.values(setColors).length > 0 ? btoa(JSON.stringify(setColors)) : '');
        }
    }
}
</script>

<style scoped lang="scss">
</style>
