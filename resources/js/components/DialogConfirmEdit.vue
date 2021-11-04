<template>
    <div class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ titleOrDefault }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="clickCancel">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <slot></slot>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="clickCancel" type="button" class="btn btn-secondary" data-dismiss="modal">Cancel
                    </button>
                    <button v-if="!hideSave" @click="clickSave" type="button" class="btn btn-primary" data-dismiss="modal">
                        {{ saveLabelOrDefault }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "dialog-confirm-edit",
    props: {
        title: {type: String, required: false},
        saveLabel: {type: String, required: false},
        hideSave: {type: Boolean, required: false}
    },
    computed: {
        titleOrDefault: function () {
            return this.title ?? "Confirm Edit";
        },
        saveLabelOrDefault: function () {
            return this.saveLabel ?? "Save";
        }
    },
    methods: {
        'clickSave': function () {
            this.$emit('save');
        },
        'clickCancel': function () {
            this.$emit('cancel');
        }
    }
}
</script>

<style scoped>

</style>
