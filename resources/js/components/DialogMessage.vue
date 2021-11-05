<!-- Dialog that contains no buttons and can be closed at a whim with no reaction. -->
<template>
    <div class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ titleOrDefault }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <slot></slot>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "dialog-message",
        props: {
            title: {type: String, required: false}
        },
        computed: {
            titleOrDefault: function() {
                return this.title ?? "Message";
            }
        }
    }
</script>

<style scoped>

</style>
