<template>
    <div class="container">
        <h2>Avatar Editor</h2>
        <div id="DrawingHolder">
            <canvas :width="avatarWidth" :height="avatarHeight" id="Renderer"></canvas>
        </div>
        <div id="Resources"> <!-- Images used in rendering, should all be invisible -->
            <img id="AvatarImage" v-if="avatarImg" alt="Avatar image" :src="avatarImg" @load="redrawCanvas">
        </div>

        <!-- Gradient Controls -->
        <div>

            <div class="form-group" v-for="color in [
                {id: 'skin1', label: 'Primary Fur / Skin'},
                {id: 'skin2', label: 'Secondary Fur / Skin'},
                {id: 'skin3', label: 'Naughty Bits'},
                {id: 'hair', label: 'Hair'},
                {id: 'eyes', label: 'Eyes'}
            ]">
                <label :for="color.id">{{ color.label }}</label>
                <select class="form-control" :id="color.id" v-model="colors[color.id]" @change="updateDollImage">
                    <option value="">(Default)</option>
                    <option :value="gradient" v-for="gradient in gradients">{{ gradient }}</option>
                </select>
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
        avatarHeight: {type: Number, required: false, default: 640},
    },
    data: function () {
        return {
            avatarCanvasContext: null,
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
        let canvasElement = document.getElementById('Renderer');
        this.avatarCanvasContext = canvasElement.getContext('2d');
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
            if (this.colors.skin2) setColors.skin2 = this.colors.skin2;
            if (this.colors.skin3) setColors.skin3 = this.colors.skin3;
            if (this.colors.hair) setColors.hair = this.colors.hair;
            if (this.colors.eyes) setColors.eyes = this.colors.eyes;
            this.avatarImg = this.renderUrl + '/' + (Object.values(setColors).length > 0 ? btoa(JSON.stringify(setColors)) : '');
        },
        redrawCanvas: function() {
            const ctx = this.avatarCanvasContext;
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

            const avatarImage = document.getElementById('AvatarImage');
            ctx.drawImage(avatarImage, 0, 0);
        }
    }
}
</script>

<style scoped lang="scss">
    @import '@/_variables.scss';

    #AvatarImage {
        display: none;
    }

    #Renderer {
        position:absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }

    #DrawingHolder {
        border: 1px solid $primary;
        position: relative;
        display: inline-block;
        width: 386px;
        height: 642px;
    }


</style>
