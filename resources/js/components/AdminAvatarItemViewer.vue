<template>
    <div class="container">
        <h2>Avatar Items</h2>
        <div v-for="category in [
            {type: 'item', label: 'Items'},
            {type: 'background', label: 'Backgrounds'}
        ]">
            <h3>{{ category.label }}</h3>
            <table class="table table-responsive small">
                <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">Id</th>
                    <th scope="col">Name</th>
                    <th scope="col">Filename</th>
                    <th scope="col">Requirement</th>
                    <th scope="col">Created</th>
                    <th scope="col">Owner</th>
                    <th scope="col">Cost</th>
                    <th scope="col">X</th>
                    <th scope="col">Y</th>
                    <th scope="col">Rotate</th>
                    <th scope="col">Scale</th>
                </tr>
                </thead>
                <tr v-for="item in items" v-if="item.type === category.type">
                    <td><img :src="item.url" alt="Image for an avatar item"></td>
                    <td>{{ item.id }}</td>
                    <td class="text-wrap">{{ item.name }}</td>
                    <td>{{ item.filename }}</td>
                    <td>{{ item.requirement }}</td>
                    <td>{{ outputCarbonString(item.created_at) }}</td>
                    <td><a v-if="item.owner" :href="item.owner.url">Account #{{ item.owner.id }}</a></td>
                    <td>{{ item.cost }}</td>
                    <td>{{ item.x }}</td>
                    <td>{{ item.y }}</td>
                    <td>{{ item.rotate }}</td>
                    <td>{{ item.scale }}</td>

                </tr>
            </table>
        </div>
        <h2>Unused files:</h2>
        <div v-for="file in fileUsage" v-if="!file.inUse">{{ file.filename }}</div>
    </div>
</template>

<script>
/**
 * @typedef {Object} AvatarItem
 * @property {string} id
 * @property {string} name
 * @property {string} desc
 * @property {date} [created_at]
 * @property {boolean} free
 * @property {number} [owner_aid]
 * @property {string} [owner_url]
 * @property {string} url
 */
export default {
    name: "admin-avatar-item-viewer",
    props: {
        /** @type {AvatarItem[]} */
        items: {Type: Array, required: true},
        fileUsage: {Type: Array, required: false}
    },
    data: function () {
        return {};
    },
    mounted: function () {
    },
    methods: {}
}
</script>

<style scoped lang="scss">
</style>
