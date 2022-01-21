<template>
    <div class="container">
        <h2>Avatar Gradients</h2>
        <div v-if="!admin">To purchase a gradient, simply use them in the avatar editor.</div>
        <div>
            <table class="table table-responsive">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Description</th>
                        <th scope="col" v-if="admin">Created</th>
                        <th scope="col" v-if="admin">Owner</th>
                        <th scope="col">Ownership</th>
                        <th scope="col">Preview</th>
                    </tr>
                </thead>
                <tr v-for="gradient in gradients">
                    <td class="text-wrap">{{ gradient.name }}</td>
                    <td>{{ gradient.desc }}</td>
                    <td v-if="admin">
                        {{ outputCarbonString(gradient.created_at) }}
                    </td>
                    <td v-if="admin">
                        <a v-if="gradient.owner_url" :href="gradient.owner_url">Account #{{ gradient.owner_aid }}</a>
                        <span v-else-if="gradient.owner_aid">{{ gradient.owner_aid }} </span>
                    </td>
                    <td>{{ gradient.free ? 'Free' : '' }}</td>
                    <td><img class="gradient-preview" :src="gradient.url" alt="Gradient Preview"></td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
/**
 * @typedef {object} Gradient
 * @property {string} name
 * @property {string} desc
 * @property {date} [created_at]
 * @property {boolean} free
 * @property {number} [owner_aid]
 * @property {string} [owner_url]
 * @property {string} url
 */
export default {
    name: "avatar-gradient-viewer",
    props: {
        admin: {Type: Boolean, required: false},
        /** @type {Gradient[]} */
        gradients: {Type: Array, required: true}
    },
    data: function () {
        return {
        };
    },
    mounted: function () {
    },
    methods: {
    }
}
</script>

<style scoped lang="scss">
    .gradient-preview {
        width: 256px;
        height: 24px;
    }
</style>
