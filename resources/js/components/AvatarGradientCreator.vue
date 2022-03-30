<template>
    <div class="container">
        <h2>Create an Avatar Gradient</h2>

        <div class="form-group">
            <label for="newName">Name</label>
            <input v-model="name" type="text" class="form-control"
                   id="newName" maxlength="40" placeholder="Enter name">
        </div>

        <div class="form-group">
            <label for="newDescription">Description</label>
            <input v-model="description" type="text" class="form-control"
                   id="newDescription" maxlength="200" placeholder="Enter a short description">
        </div>

        <p class="text-muted">A submitted gradient's name and description may be changed if they're deemed inappropriate, unclear or misleading.</p>

        <div class="d-flex flex-column flex-xl-row justify-content-between">
            <div class="flex-grow-1">

                <label>Steps</label>

                <p class="text-muted">If you change when a step occurs to be before or after another step then this list will automatically re-arrange itself.</p>
                <div class="ml-4">
                    <div v-for="(step, index) in steps" class="mb-2">

                        <div class="d-flex">
                            <div class="font-weight-bold">Step {{ index + 1 }}</div>
                            <div class="stepPreview ml-2 flex-grow-1"
                                 :style="stepPreviewCss(step[1], step[2], step[3])"></div>
                        </div>

                        <div class="d-flex align-items-center mt-2">
                            <div class="sliderLabel">When</div>
                            <div class="ml-1 flex-fill"><input type="range" v-model.number="step[0]"
                                                               class="form-control-range" min="0" max="255"
                                                               @change="maybeReorderSteps"></div>
                            <div class="ml-1 sliderValue">{{ step[0] }}</div>
                        </div>

                        <div class="d-flex align-items-center mt-2">
                            <div class="sliderLabel">Red</div>
                            <div class="ml-1 flex-fill"><input type="range" v-model.number="step[1]"
                                                               class="form-control-range" min="0" max="255"
                                                               @change="renderGradient"></div>
                            <div class="ml-1 sliderValue">{{ step[1] }}</div>
                        </div>

                        <div class="d-flex align-items-center mt-2">
                            <div class="sliderLabel">Green</div>
                            <div class="ml-1 flex-fill"><input type="range" v-model.number="step[2]"
                                                               class="form-control-range" min="0" max="255"
                                                               @change="renderGradient"></div>
                            <div class="ml-1 sliderValue">{{ step[2] }}</div>
                        </div>

                        <div class="d-flex align-items-center mt-2">
                            <div class="sliderLabel">Blue</div>
                            <div class="ml-1 flex-fill"><input type="range" v-model.number="step[3]"
                                                               class="form-control-range" min="0" max="255"
                                                               @change="renderGradient"></div>
                            <div class="ml-1 sliderValue">{{ step[3] }}</div>
                        </div>

                        <div class="btn-group mt-2 w-100" role="group">
                            <button class="btn btn-secondary" @click="deleteStep(index)">
                                Delete Step {{ index +1 }}
                            </button>
                            <button v-if="steps.length < 16" class="btn btn-secondary" @click="addStepAfter(index)">
                                Add new Step here
                            </button>
                        </div>

                    </div>
                </div>


            </div>
            <div class="ml-xl-4">
                <div v-if="steps.length < 2" class="alert alert-danger">A gradient requires at least two steps.</div>
                <label>Entire Gradient</label>
                <div>
                    <canvas width="256" height="24" id="GradientCanvas"></canvas>
                </div>

                <label>Preview</label>
                <img v-if="previewUrl" :src="previewUrl" alt="Preview Avatar">
            </div>
        </div>

        <button disabled class="mt-4 btn btn-primary btn-with-img-icon">
            <span class="btn-icon-accountcurrency btn-icon-left"></span>
            Create Gradient
            <span class="btn-second-line">20 {{ lex('accountcurrency') }}</span>
        </button>
        (Presently not available)

    </div>


</template>

<script>
export default {
    name: "avatar-gradient-creator",
    props: {
        basePreviewUrl: { type: String, required: true}
    },
    data: function () {
        return {
            name: "",
            description: "",
            steps: [ // each inner array is in the form [step, R, G, B] with values of 0..255
                [0, 0, 0, 0],
                [255, 255, 255, 255]
            ],
            gradientCanvas: null,
            previewUrl: ''
        };
    },
    mounted: function () {
        let canvasElement = document.getElementById('GradientCanvas');
        this.gradientCanvas = canvasElement.getContext('2d');
        this.renderGradient();
    },
    methods: {
        maybeReorderSteps: function () {
            // In case the user causes the drawing order to change
            this.steps.sort((a, b) => {
                if (a[0] === b[0]) return 0;
                return a[0] < b[0] ? -1 : 1;
            });
            this.renderGradient();
        },
        stepPreviewCss: function (r, g, b) {
            return {'background-color': `rgb(${r}, ${g}, ${b})`};
        },
        deleteStep: function (index) {
            this.steps.splice(index, 1);
            this.renderGradient();

        },
        addStepAfter: function (index) {
            if (this.steps.length >= 16) return;

            let previousStep = this.steps[index];
            this.steps.splice(index, 0, [previousStep[0], previousStep[1], previousStep[2], previousStep[3]]);
            this.renderGradient();
        },
        renderGradient: function () {
            let ctx = this.gradientCanvas;
            if (this.steps.length < 2) {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            } else {
                //Conveniently HTML canvas has a gradient renderer for us!
                let gradient = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);
                for (let i = 0; i < this.steps.length; i++) {
                    let step = this.steps[i];
                    let color = `rgb(${step[1]}, ${step[2]}, ${step[3]})`;
                    gradient.addColorStop(step[0] / 255.0, color);
                }
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            }
            this.refreshPreview();
        },
        refreshPreview: function() {
            if (this.steps.length < 2) {
                this.previewUrl = "";
            } else {
                let config = {
                    'steps': this.steps
                };
                this.previewUrl = this.basePreviewUrl + '/' +  btoa(JSON.stringify(config));

            }
        }
    },
    computed: {}
}
</script>

<style scoped>

.sliderLabel {
    min-width: 80px;
}

.sliderValue {
    min-width: 32px;
}

.stepPreview {
    border: 1px solid dimgray;
}

</style>
