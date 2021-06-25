<template>
    <div class="container">
        <h4 class="text-center">Character Creation</h4>
        <div class="row">
            <div class="col-12 col-md-6">
                <p>Please enter a name, it needs to be at least three characters and can only include letters or a
                    hyphen.</p>
                <p>This is supposed to be a reasonable name, like what you might assume a person to go by in real
                    life.</p>
                <p>WARNING: Profane or copyrighted names will be changed without warning.</p>
            </div>
            <div class="col-12 col-md-6">
                <form action="" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <div class="form-group">
                        <label for="characterName">New Character Name</label>
                        <input type="text" class="form-control" id="characterName" name="characterName"
                               placeholder="Enter name" v-bind:class="{ 'is-invalid' : errors.characterName }"
                               :value="old.characterName"
                        >
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.characterName">{{ error }}</p>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "character-create",
    props: {
        errors: {required: false},
        old: {type: Object, required: false}
    },
    data: function () {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').content
        }

    }
}
</script>

<style scoped>

</style>
