<template>
    <div class="container">

        <div class="row">
            <div class="col">
                <div class="bg-secondary text-primary p-2 mb-4 text-center rounded">
                    <div>If you're using the latest version of the BeipMU client, it's possible to see avatars ingame!</div>
                    <div>Type 'avatars #help' whilst connected for more information.</div>
                </div>
            </div>
        </div>

        <h2>Avatar Editor</h2>

        <div id="DrawingHolder">
            <div v-if="loading" class="text-center">
                <div class="spinner-border" role="status"></div>
                <div>Loading...</div>
            </div>
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
                <div class="form-group" v-for="color in colorSlots">
                    <label :for="color.id">{{ color.label }}</label>
                    <select class="form-control" :id="color.id" v-model="avatar.colors[color.id]"
                            @change="updateDollImage">
                        <option value="">(Default)</option>
                        <option :value="gradient" v-for="(slots, gradient) in gradients">
                            {{ gradient + (slots.indexOf(color.slot) !== -1 ? '' : ' (Requires Purchase)') }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Background -->
            <div class="tab-pane" id="nav-background" role="tabpanel" aria-labelledby="nav-background-tab">
                <div v-if="avatar.background">
                    <div>Present background: {{ avatar.background.base.name }}</div>

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
                         :class="[{
                             border: avatar.background && background.id === avatar.background.id,
                             unavailable: !background.earned && !background.owner && !background.cost && background.requirement
                         }]"
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
                <p>This list will re-order automatically as the drawing order is changed. Items with a negative Z value
                    are drawn behind the character.</p>
                <p v-if="avatar.items.length === 0">No items added - use the 'Add Items' tab to add them.</p>
                <div class="mb-2" v-for="item in avatar.items">
                    <span>{{ item.base.name }} @ X: {{ item.x }}, Y: {{ item.y }}, Z: {{ item.z }}</span>
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
                         :class="[{
                             unavailable: !item.earned && !item.owner && !item.cost && item.requirement
                         }]"
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

        <!-- Any pending purchases -->
        <div v-if="Object.keys(purchases.items).length || Object.keys(purchases.gradients).length">
            <h4>Purchases Required</h4>
            <div v-for="(slots, gradient) in purchases.gradients">
                Color '{{ gradient }}'
                <button :data-gradient="gradient" :disabled="slots.length > 1"
                        class="mt-2 ml-2 btn btn-primary btn-with-img-icon"
                        @click="purchaseGradient(gradient, slots[0])">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy for a single slot
                    <span class="btn-second-line">5 {{ lex('accountcurrency') }}</span>
                </button>
                <button :data-gradient="gradient" class="mt-2 ml-2 btn btn-primary btn-with-img-icon"
                        @click="purchaseGradient(gradient, 'all')">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy for all slots
                    <span class="btn-second-line">10 {{ lex('accountcurrency') }}</span>
                </button>
            </div>
            <div v-for="(item, id) in purchases.items">
                Accessory '{{ item.name }}'
                <button :data-item="id" class="mt-2 ml-2 btn btn-primary btn-with-img-icon" @click="purchaseItem(id)">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy
                    <span class="btn-second-line">{{ item.cost }} {{ lex('accountcurrency') }}</span>
                </button>
            </div>
        </div>

        <!-- Save -->
        <button
            :disabled="saving || Object.keys(purchases.items).length > 0 || Object.keys(purchases.gradients).length > 0"
            class="mt-2 btn btn-primary" @click="saveAvatarState">Save Changes
            <span v-if="saving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </button>

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
        itemsIn: {type: Array, required: true},
        backgroundsIn: {type: Array, required: true},
        gradientsIn: {type: Object, required: true},
        renderUrl: {type: String, required: true},
        stateUrl: {type: String, required: true},
        gradientUrl: {type: String, required: true},
        itemUrl: {type: String, required: true},
        avatarWidth: {type: Number, required: false, default: 384},
        avatarHeight: {type: Number, required: false, default: 640}
    },
    data: function () {
        return {
            colorSlots: [
                {id: 'skin1', slot: 'fur', label: 'Primary Fur / Skin'},
                {id: 'skin2', slot: 'fur', label: 'Secondary Fur / Skin'},
                {id: 'skin3', slot: 'skin', label: 'Naughty Bits'},
                {id: 'hair', slot: 'hair', label: 'Hair'},
                {id: 'eyes', slot: 'eyes', label: 'Eyes'}
            ],
            items: null,
            backgrounds: null,
            gradients: null,
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
            loading: true,
            saving: false,
            messageDialogHeader: '',
            messageDialogContent: '',
            purchases: {
                gradients: {},
                items: {}
            }
        };
    },
    mounted: function () {
        let canvasElement = document.getElementById('Renderer');
        this.avatarCanvasContext = canvasElement.getContext('2d');
        this.items = this.itemsIn;
        this.backgrounds = this.backgroundsIn;
        this.gradients = this.gradientsIn;
        this.loadAvatarState();
    },
    methods: {
        loadAvatarState: function () {
            console.log("Loading avatar state");
            this.loading = true;
            axios.get(this.stateUrl)
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
                    this.loading = false;
                })
                .catch((error) => {
                    console.log("Attempt to load avatar state failed: ", error);
                    this.messageDialogHeader = "An error occurred..";
                    this.messageDialogContent = "Unable to load avatar:\n" + error?.response?.data?.message || error;
                    $('#DialogMessage').modal();
                });
        },
        saveAvatarState() {
            console.log("Saving avatar state");
            this.saving = true;
            let avatarState = {
                'colors': this.avatar.colors,
                'items': this.avatar.items.map((item) => {
                    return {
                        'id': item.base.id,
                        'name': item.base.name,
                        'rotate': item.rotate,
                        'scale': item.scale,
                        'x': item.x,
                        'y': item.y,
                        'z': item.z
                    };
                }),
                'background': {
                    'id': this.avatar.background.base.id,
                    'name': this.avatar.background.base.name,
                    'rotate': this.avatar.background.rotate,
                    'scale': this.avatar.background.scale,
                    'x': this.avatar.background.x,
                    'y': this.avatar.background.y,
                    'z': this.avatar.background.z
                }
            };
            axios.post(this.stateUrl, avatarState)
                .then((response) => {
                    console.log("Saved avatar state.");
                })
                .catch((error) => {
                    console.log("Attempt to save avatar state failed: ", error?.response?.data || error);
                    this.messageDialogHeader = "An error occurred..";
                    this.messageDialogContent = "The save request was rejected:\n" + error.response.data.message;
                    $('#DialogMessage').modal();
                })
                .then(() => {
                    this.saving = false;
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
            this.refreshPurchases();
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
            ctx.setTransform(1, 0, 0, 1, 0, 0);
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
            if (this.avatarImg) ctx.drawImage(this.avatarImg, 0, 0);

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
            let template = null;
            for (const item of this.backgrounds) {
                if (item.id === newId) {
                    template = item;
                }
            }
            if (!template) throw "Unable to find background '" + newId + "' in the background catalog.";
            if (!template.url) throw "Background doesn't have an url to load an image from!";
            if (!template.owner && !template.earned && !template.cost && template.requirement)
                throw "Couldn't switch to new background because it has an unmet requirement.";

            this.avatar.background = {
                base: template,
                rotate: template.rotate,
                scale: template.scale,
                x: template.x,
                y: template.y,
                z: template.z
            };
            this.avatar.background.image = new Image();
            this.avatar.background.image.onload = () => {
                this.background.minWidth = -this.avatar.background.image.naturalWidth;
                this.background.minHeight = -this.avatar.background.image.naturalHeight;
                this.background.maxWidth = this.avatar.background.image.naturalWidth;
                this.background.maxHeight = this.avatar.background.image.naturalHeight;
                this.redrawCanvas();
            }
            this.avatar.background.image.src = template.url;
            this.refreshPurchases();
        },
        addItem: function (newId) {
            console.log("Adding item: " + newId);
            let template = null;
            // Find and make a new instanced version
            for (const possibleItem of this.items) {
                if (possibleItem.id === newId) {
                    template = possibleItem;
                }
            }
            if (!template) throw "Unable to find item '" + newId + "' in the item catalog.";
            if (!template.url) throw "Item doesn't have an url to load an image from!";
            if (!template.owner && !template.earned && !template.cost && template.requirement)
                throw "Couldn't switch to new item because it has an unmet requirement.";
            let item = {
                base: template,
                rotate: template.rotate,
                scale: template.scale,
                x: template.x,
                y: template.y,
                z: 1
            };
            // Find highest Z so far
            for (const otherItem of this.avatar.items) {
                item.z = Math.max(item.z, otherItem.z + 1);
            }
            this.avatar.items.push(item);
            item.image = new Image();
            item.image.onload = () => {
                console.log("Item loaded " + item.image.src);
                this.redrawCanvas();
            }
            item.image.src = template.url;
            this.refreshPurchases();
            return item;
        },
        addItemAndGotoIt: function (itemId) {
            if (this.avatar.items.length >= 10) return;
            this.addItem(itemId);
            let triggerEl = document.querySelector('#avatar-edit-tab a[href="#nav-items-edit"]')
            triggerEl.click();
        },
        deleteItem: function (item) {
            let index = this.avatar.items.indexOf(item);
            if (index === -1) throw "Couldn't find an index to delete requested item!";
            this.avatar.items.splice(index, 1);
            this.sortItems();
            this.redrawCanvas();
            this.refreshPurchases();
        },
        itemCostOrStatus: function (item) {
            if (item.owner) return 'Owner';
            if (item.earned) return 'Earned';
            if (item.cost) return item.cost + ' ' + lex('accountcurrency');
            if (item.requirement) return 'Requirements unmet';
            return '';
        },
        refreshPurchases: function () {
            // Collect a list of anything that requires purchasing
            this.purchases.gradients = {};
            for (const color of this.colorSlots) {
                let gradientId = this.avatar.colors[color.id];
                let gradient = gradientId && this.gradients[gradientId];
                if (gradient) {
                    if (gradient.indexOf(color.slot) === -1) {
                        if (!this.purchases.gradients[gradientId])
                            this.purchases.gradients[gradientId] = [];
                        this.purchases.gradients[gradientId].push(color.slot);
                    }
                }
            }

            this.purchases.items = {};
            for (const item of this.avatar.items) {
                if (item.base.cost && !item.base.earned && !item.base.owner) {
                    this.purchases.items[item.base.id] = {
                        name: item.base.name,
                        cost: item.base.cost
                    };
                }
            }
            if (this.avatar.background && this.avatar.background.base.cost && !this.avatar.background.base.earned && !this.avatar.background.base.owner)
                this.purchases.items[this.avatar.background.base.id] = {
                    name: this.avatar.background.base.name,
                    cost: this.avatar.background.base.cost
                };
        },
        purchaseGradient: function (gradientId, slot, event) {
            $(`button[data-gradient="${gradientId}"]`).prop('disabled', true);
            console.log("Purchasing gradient: ", gradientId, " for slot ", slot);
            axios.post(this.gradientUrl, {gradient: gradientId, slot: slot})
                .then((response) => {
                    if (response.data === 'OK') {
                        console.log("Purchasing gradient successful.");
                        if (slot === 'all') {
                            this.gradients[gradientId] = ["fur", "skin", "hair", "eyes"];
                        } else this.gradients[gradientId].push(slot);
                        this.refreshPurchases();
                    } else {
                        console.log("Purchasing gradient refused: " + response.data);
                        this.messageDialogHeader = "Purchase failed";
                        this.messageDialogContent = "Something went wrong with the purchase:\n" + response.data;
                        $('#DialogMessage').modal();
                    }
                })
                .catch((error) => {
                    console.log("Error with purchasing gradient: ", error);
                    this.messageDialogHeader = "An error occurred";
                    this.messageDialogContent = "Unable to purchase gradient:\n" + error?.response?.data?.message || error;
                    $('#DialogMessage').modal();
                })
                .then(() => {
                    $(`button[data-gradient="${gradientId}"]`).prop('disabled', false);
                });
        },
        purchaseItem: function (itemId, event) {
            $(`button[data-item="${itemId}"]`).prop('disabled', true);
            console.log("Purchasing item: ", itemId);
            axios.post(this.itemUrl, {item: itemId})
                .then((response) => {
                    if (response.data === 'OK') {
                        console.log("Purchasing item successful.");
                        // Might be a background or item
                        for (const item of this.items) {
                            if (item.id === itemId) item.earned = true;
                        }
                        for (const item of this.backgrounds) {
                            if (item.id === itemId) item.earned = true;
                        }
                        this.refreshPurchases();
                    } else {
                        console.log("Purchasing item refused: " + response.data);
                        this.messageDialogHeader = "Purchase failed";
                        this.messageDialogContent = "Something went wrong with the purchase:\n" + response.data;
                        $('#DialogMessage').modal();
                    }
                })
                .catch((error) => {
                    console.log("Error with purchasing item: ", error);
                    this.messageDialogHeader = "An error occurred..";
                    this.messageDialogContent = "Unable to purchase item:\n" + error?.response?.data?.message || error;
                    $('#DialogMessage').modal();
                })
                .then(() => {
                    $(`button[data-item="${itemId}"]`).prop('disabled', false);
                });
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

.item-card.unavailable {
    cursor: not-allowed;
    filter: grayscale(100%);
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
