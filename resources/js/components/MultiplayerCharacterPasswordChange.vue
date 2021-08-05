<template>
    <div class="container">
        <h4>Change Character Password</h4>
        <div class="row">
            <div class="col">
                <p>Use this page to change the password of one of your characters.</p>
                <p>For security you need to enter your account password to do this.</p>
            </div>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="_token" :value="csrf">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="accountpassword">Your Account Password</label>
                    </div>
                    <div class="col">
                        <input type="password" name="accountpassword" class="form-control" id="accountpassword"
                               placeholder="Enter account password"
                               v-bind:class="{ 'is-invalid' : errors.accountpassword }">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.accountpassword">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="character">Character</label>
                    </div>
                    <div class="col">
                        <select class="form-control" name="character" id="character" v-bind:class="{ 'is-invalid' : errors.character }">>
                            <option selected>Select a character</option>
                            <option v-for="character in characters" :value="character.dbref">{{ character.name }}</option>
                        </select>
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.character">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right">
                        <label for="password">Character's New Password</label>
                    </div>
                    <div class="col">
                        <input type="password" name="password" class="form-control" id="password"
                               placeholder="Enter new password" v-bind:class="{ 'is-invalid' : errors.password }">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.password">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <div class="col text-center">
                        <button type="submit" value="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>

        </form>
    </div>
</template>

<script>
export default {
    name: "multiplayer-character-password-change",
    props: {
        errors: {
            required: false, default: function () {
                return [];
            }
        },
        characters: { type: Array, required: true }
    },
    data: function () {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').content
        }
    },
    methods: {}
}
</script>

<style scoped>

</style>
