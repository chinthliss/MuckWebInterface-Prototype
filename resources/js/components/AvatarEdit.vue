<template>
    <div class="container">

        <h2>Avatar Editor</h2>
        <div id="DrawingHolder">
            <canvas :width="avatarWidth" :height="avatarHeight" id="Renderer"></canvas>
        </div>

        <nav class="mt-2">
            <div class="nav nav-tabs nav-fill" id="avatar-edit-tab" role="tablist">
                <a class="nav-link active" id="nav-colors-tab" data-toggle="tab" href="#nav-colors" role="tab"
                   aria-controls="nav-colors" aria-selected="true">Select Colors</a>
                <a class="nav-link" id="nav-background-tab" data-toggle="tab" href="#nav-background" role="tab"
                   aria-controls="nav-background" aria-selected="false">Edit / Change Background</a>
                <a class="nav-link" id="nav-items-edit-tab" data-toggle="tab" href="#nav-items-edit" role="tab"
                   aria-controls="nav-items-edit" aria-selected="false">Edit Items</a>
                <a class="nav-link" id="nav-items-add-tab" data-toggle="tab" href="#nav-items-add" role="tab"
                   aria-controls="nav-items-add" aria-selected="false">Add Items</a>
            </div>
        </nav>

        <div class="tab-content border p-4" id="nav-tabContent">

            <!-- Gradients -->
            <div class="tab-pane show active" id="nav-colors" role="tabpanel" aria-labelledby="nav-colors-tab">
                <div class="form-group" v-for="color in [
                        {id: 'skin1', slot: 'fur', label: 'Primary Fur / Skin'},
                        {id: 'skin2', slot: 'fur', label: 'Secondary Fur / Skin'},
                        {id: 'skin3', slot: 'skin', label: 'Naughty Bits'},
                        {id: 'hair', slot: 'hair', label: 'Hair'},
                        {id: 'eyes', slot: 'eyes', label: 'Eyes'}
                    ]">
                    <label :for="color.id">{{ color.label }}</label>
                    <select class="form-control" :id="color.id" v-model="avatar.colors[color.id]"
                            @change="updateDollImage">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="(owned, gradient) in gradients">{{ gradient + (owned.indexOf(color.slot) !== -1 ? '' : ' (Requires Purchase)') }}</option>
                    </select>
                </div>
            </div>

            <!-- Background -->
            <div class="tab-pane" id="nav-background" role="tabpanel" aria-labelledby="nav-background-tab">
                <div v-if="avatar.background">
                    <div>Present background: {{ avatar.background.name }}</div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Rotation</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="avatar.background.rotate"
                                                           class="form-control-range" min="0" max="359"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ avatar.background.rotate }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Scale</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="avatar.background.scale"
                                                           class="form-control-range" min="0.1" max="2.0" step="0.01"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ avatar.background.scale }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">X Offset</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="avatar.background.x"
                                                           class="form-control-range" :min="this.background.minWidth"
                                                           :max="background.maxWidth"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ avatar.background.x }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Y Offset</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="avatar.background.y"
                                                           class="form-control-range" :min="background.minHeight"
                                                           :max="background.maxHeight"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ avatar.background.y }}</div>
                    </div>

                </div>
                <h4 class="mt-2">Change to a different background:</h4>
                <div class="row">
                    <div role="button" class="card item-card " v-for="background in backgrounds"
                         v-bind:class="[avatar.background && background.id === avatar.background.id ? 'border' : '']"
                         @click="changeBackground(background.id)">
                        <div class="card-img-top position-relative">
                            <img :src="background.preview_url" alt="Background Thumbnail">
                        </div>
                        <div class="card-body">
                            <div class="text-center">{{ background.name }}</div>
                            <div class="text-center small">{{ itemCostOrStatus(background) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items - Edit -->
            <div class="tab-pane" id="nav-items-edit" role="tabpanel" aria-labelledby="nav-items-edit-tab">
                <p>This list will re-order automatically as the drawing order is changed. Items with a negative Z value are drawn behind the character.</p>
                <p v-if="avatar.items.length === 0">No items added - use the 'Add Items' tab to add them.</p>
                <div class="mb-2" v-for="item in avatar.items">
                    <span>{{ item.name }} @ X: {{ item.x }}, Y: {{ item.y }}, Z: {{ item.z }}</span>
                    <button class="btn btn-secondary" @click="adjustZ(item, 1)">Move Forwards</button>
                    <button class="btn btn-secondary" @click="adjustZ(item, -1)">Move Backwards</button>
                    <button class="btn btn-secondary" @click="deleteItem(item)">Delete</button>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">X</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="item.x"
                                                           class="form-control-range" min="0" :max="avatarWidth"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ item.x }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Y</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="item.y"
                                                           class="form-control-range" min="0" :max="avatarHeight"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ item.y }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Rotation</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="item.rotate"
                                                           class="form-control-range" min="0" max="359"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ item.rotate }}</div>
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="sliderLabel">Scale</div>
                        <div class="ml-1 flex-fill"><input type="range" v-model.number="item.scale"
                                                           class="form-control-range" min="0.1" max="2.0" step="0.01"
                                                           @change="redrawCanvas"></div>
                        <div class="ml-1 sliderValue">{{ item.scale }}</div>
                    </div>


                </div>
            </div>

            <!-- Items - Add -->
            <div class="tab-pane" id="nav-items-add" role="tabpanel" aria-labelledby="nav-items-add-tab">
                <div class="row">
                    <div role="button" class="card item-card" v-for="item in items"
                         @click="addItemAndGotoIt(item.id)">
                        <div class="card-img-top position-relative">
                            <img :src="item.preview_url" alt="Background Thumbnail">
                        </div>
                        <div class="card-body">
                            <div class="text-center">{{ item.name }}</div>
                            <div class="text-center small">{{ itemCostOrStatus(item) }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <button class="mt-2 btn btn-primary" @click="saveAvatarState">Save Changes</button>

        <dialog-message id="DialogMessage" :title="messageDialogHeader">
            {{ messageDialogContent }}
        </dialog-message>
    </div>
</template>

<script>
import DialogMessage from "./DialogMessage";

export default {
    name: "avatar-edit",
    props: {
        items: {type: Array, required: true},
        backgrounds: {type: Array, required: true},
        gradients: {type: Object, required: true},
        renderUrl: {type: String, required: true},
        apiUrl: {type: String, required: true},
        avatarWidth: {type: Number, required: false, default: 384},
        avatarHeight: {type: Number, required: false, default: 640},
    },
    data: function () {
        return {
            avatarCanvasContext: null,
            avatarImg: null,
            avatar: {
                colors: {
                    skin1: null,
                    skin2: null,
                    skin3: null,
                    hair: null,
                    eyes: null
                },
                background: null,
                items: []
            },
            background: { // Used to limit the sliders for such
                minWidth: -200,
                maxWidth: 200,
                minHeight: -200,
                maxHeight: 200
            },
            messageDialogHeader:'',
            messageDialogContent:''
        };
    },
    mounted: function () {
        let canvasElement = document.getElementById('Renderer');
        this.avatarCanvasContext = canvasElement.getContext('2d');
        this.loadAvatarState();
    },
    methods: {
        loadAvatarState: function() {
            console.log("Loading avatar state");
            axios.get(this.apiUrl)
                .then((response) => {
                    console.log("Loaded avatar state:", response.data);
                    let state = response.data;
                    this.avatar.colors.skin1 = state.colors?.skin1 || '';
                    this.avatar.colors.skin2 = state.colors?.skin2 || '';
                    this.avatar.colors.skin3 = state.colors?.skin3 || '';
                    this.avatar.colors.hair = state.colors?.hair || '';
                    this.avatar.colors.eyes = state.colors?.eyes || '';

                    if (state.background) {
                        this.changeBackground(state.background.id);
                        if (this.avatar.background) {
                            this.avatar.background.x = state.background.x;
                            this.avatar.background.y = state.background.y;
                            this.avatar.background.scale = state.background.scale;
                            this.avatar.background.rotate = state.background.rotate;
                        }
                    }

                    if (state.items) {
                        for (const startingItem of state.items) {
                            const item = this.addItem(startingItem.id);
                            if (item) {
                                item.x = startingItem.x;
                                item.y = startingItem.y;
                                item.z = startingItem.z;
                                item.scale = startingItem.scale;
                                item.rotate = startingItem.rotate;
                            }
                        }
                    }
                    this.sortItems(); // Because legacy avatars may be in the wrong order
                    this.updateDollImage();
                })
                .catch(function (error) {
                    console.log("Attempt to load avatar state failed: ", error);
                });
        },
        saveAvatarState() {
            console.log("Saving avatar state");
            axios.post(this.apiUrl, this.avatar)
                .then((response) => {
                    console.log("Saved avatar state.");
                })
                .catch((error) => {
                    console.log("Attempt to save avatar state failed: ", error?.response?.data || error);
                    this.messageDialogHeader = "An error occurred..";
                    this.messageDialogContent = "The save request was rejected:\n" + error.response.data.message;
                    $('#DialogMessage').modal();
                });
        },
        updateDollImage: function () {
            console.log("Updating doll image");
            //For the editor the only thing on the doll loaded from the server is the coloring
            let setColors = {};
            if (this.avatar.colors.skin1) setColors.skin1 = this.avatar.colors.skin1;
            if (this.avatar.colors.skin2) setColors.skin2 = this.avatar.colors.skin2;
            if (this.avatar.colors.skin3) setColors.skin3 = this.avatar.colors.skin3;
            if (this.avatar.colors.hair) setColors.hair = this.avatar.colors.hair;
            if (this.avatar.colors.eyes) setColors.eyes = this.avatar.colors.eyes;
            this.avatarImg = new Image();
            this.avatarImg.onload = () => {
                console.log("Avatar Doll Loaded loaded " + this.avatarImg.src);
                this.redrawCanvas();
            }
            this.avatarImg.src = this.renderUrl + '/' + (Object.values(setColors).length > 0 ? btoa(JSON.stringify(setColors)) : '');
        },
        drawItemOnContext: function (ctx, item) {
            const imageWidth = item.image.naturalWidth;
            const imageHeight = item.image.naturalHeight;
            ctx.translate(item.x, item.y);
            ctx.scale(item.scale, item.scale);
            // Ideally we wouldn't do the next line and we'd work from the centre
            // However the existing framework works from the topleft, so we need to match
            ctx.translate(imageWidth / 2, imageHeight / 2);
            ctx.rotate(item.rotate * (Math.PI / 180.0));

            ctx.drawImage(item.image, -imageWidth / 2, -imageHeight / 2);
            //Reset translation/rotation
            ctx.setTransform(1, 0, 0, 1, 0 ,0);
        },
        redrawCanvas: function () {
            console.log("Redrawing canvas");
            const ctx = this.avatarCanvasContext;
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

            if (this.avatar.background) this.drawItemOnContext(ctx, this.avatar.background);

            //Draw items behind avatar first
            for (const item of this.avatar.items) {
                if (item.z < 0) this.drawItemOnContext(ctx, item);
            }

            //Draw Avatar
            ctx.drawImage(this.avatarImg, 0, 0);

            //Draw items in front of avatar
            for (const item of this.avatar.items) {
                if (item.z >= 0) this.drawItemOnContext(ctx, item);
            }
        },
        adjustZ: function (item, modifier) {
            let oldZ = item.z;
            let newZ = oldZ + modifier;

            //Avoid using 0, since that represents the character doll
            if (newZ === 0 && oldZ === -1) newZ = 1;
            if (newZ === 0 && oldZ === 1) newZ = -1;
            item.z = newZ;
            this.sortItems();
            this.redrawCanvas();
        },
        sortItems: function () {
            this.avatar.items.sort((a, b) => {
                if (a.z === b.z) return 0;
                return a.z < b.z ? -1 : 1;
            });
        },
        changeBackground: function (newId) {
            console.log("Changing background to: " + newId);
            for (const item of this.backgrounds) {
                if (item.id === newId) {
                    this.avatar.background = {...item};
                }
            }
            if (!this.avatar.background) throw "Unable to find background '" + newId + "' in the background catalog.";
            if (!this.avatar.background.url) throw "Background doesn't have an url to load an image from!";
            this.avatar.background.image = new Image();
            this.avatar.background.image.onload = () => {
                this.background.minWidth = -this.avatar.background.image.naturalWidth;
                this.background.minHeight = -this.avatar.background.image.naturalHeight;
                this.background.maxWidth = this.avatar.background.image.naturalWidth;
                this.background.maxHeight = this.avatar.background.image.naturalHeight;
                this.redrawCanvas();
            }
            this.avatar.background.image.src = this.avatar.background.url;
        },
        addItem: function (newId) {
            console.log("Adding item: " + newId);
            let item = null;
            for (const possibleItem of this.items) {
                if (possibleItem.id === newId) {
                    item = {...possibleItem};
                }
            }
            if (!item) throw "Unable to find item '" + newId + "' in the item catalog.";
            if (!item.url) throw "Item doesn't have an url to load an image from!";
            // Find highest Z so far
            for (const otherItem of this.avatar.items) {
                item.z = Math.max(item.z, otherItem.z);
            }
            this.avatar.items.push(item);
            item.image = new Image();
            item.image.onload = () => {
                console.log("Item loaded " + item.image.src);
                this.redrawCanvas();
            }
            item.image.src = item.url;
            return item;
        },
        addItemAndGotoIt: function(itemId) {
            this.addItem(itemId);
            let triggerEl = document.querySelector('#avatar-edit-tab a[href="#nav-items-edit"]')
            triggerEl.click();
        },
        deleteItem: function(item) {
            let index = this.avatar.items.indexOf(item);
            if (index === -1) throw "Couldn't find an index to delete requested item!";
            this.avatar.items.splice(index, 1);
            this.sortItems();
            this.redrawCanvas();
        },
        itemCostOrStatus: function(item) {
            if (item.owner) return 'Owner';
            if (item.earned) return 'Earned';
            if (item.cost) return item.cost + ' ' + lex('accountcurrency');
            if (item.requirement) return 'Requirements unmet';
            return '';
        }
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.imgResource {
    display: none;
}

#Renderer {
    position: absolute;
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

.item-card {
    width: 160px;
    height: 180px;
    display: inline-block;
}

.item-card .card-img-top {
    width: 160px;
    height: 60px;
}

.item-card .card-img-top img {
    max-height: 100%;
    max-width: 100%;
    width: auto;
    height: auto;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    margin: auto;
}

.sliderLabel {
    min-width: 80px;
}

.sliderValue {
    min-width: 32px;
}


</style>
