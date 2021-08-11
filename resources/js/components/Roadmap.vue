<template>
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>Site Roadmap</h1>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="bg-warning text-dark p-2 mb-2 text-center rounded">
                    <b>Please Note</b>: This roadmap is just for the website rewrite.
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <h2>Present Phase: {{ phase }}</h2>
                <p>{{ phaseDescription }}</p>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <h2>Tasks</h2>
            </div>
        </div>
        <div v-for="item in roadmap" class="row mb-2">
            <div class="col-12 col-lg text-primary">
                {{ item.title }}
            </div>
            <div class="col-12 col-lg text-muted">
                {{ item.description }}
            </div>
            <div class="col-12 col-lg">
                {{ item.progress }}
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h2>Up Next</h2>
                <p>{{ future }}</p>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "roadmap",
    props: {
        phase: {type: String, required: true},
        phaseDescription: {type: String, required: true},
        future: {type: String, required: true},
        roadmap: {type: Array, required: true}
    },
    data: function () {
        return {

        }
    },
    methods: {
    }
}
</script>
