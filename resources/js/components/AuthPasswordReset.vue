<template>
    <div class="container">
        <h4>Password Reset</h4>
        <div class="row">
            <div class="col">
                <p>Enter a new password here.</p>
            </div>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="_token" :value="csrf">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="password">New Password</label></div>
                    <div class="col">
                        <input type="password" name="password" class="form-control" id="password"
                               placeholder="Enter new password" v-bind:class="{ 'is-invalid' : errors.password }">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.password">{{ error }}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="password_confirmation">New Password
                        Again</label></div>
                    <div class="col">
                        <input type="password" name="password_confirmation" class="form-control"
                               id="password_confirmation"
                               placeholder="Re-enter new password"
                               v-bind:class="{ 'is-invalid' : errors.password_confirmation }">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.password_confirmation">{{ error }}</p>
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
    name: "auth-password-reset",
    props: {errors: {}},
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
