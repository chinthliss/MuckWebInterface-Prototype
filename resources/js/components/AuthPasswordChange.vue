<template>
    <div class="container">
        <div class="card">
            <h4 class="card-header">Change Password</h4>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <p>Use this page to change your present password.</p>
                    </div>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3 col-form-label text-md-right"><label for="oldpassword">Old Password</label></div>
                            <div class="col">
                                <input type="password" name="oldpassword" class="form-control" id="oldpassword"
                                       placeholder="Enter existing password" v-bind:class="{ 'is-invalid' : errors.oldpassword }">
                                <div class="invalid-feedback" role="alert">
                                    <p v-for="error in errors.oldpassword">{{ error }}</p>
                                </div>
                            </div>
                        </div>
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
                            <div class="col-md-3 col-form-label text-md-right"><label for="password_confirmation">New Password Again</label></div>
                            <div class="col">
                                <input type="password" name="password_confirmation" class="form-control" id="password_confirmation"
                                       placeholder="Re-enter new password" v-bind:class="{ 'is-invalid' : errors.password_confirmation }">
                                <div class="invalid-feedback" role="alert">
                                    <p v-for="error in errors.password_confirmation">{{ error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row"><div class="col text-center">
                            <button type="submit" value="submit" class="btn btn-primary">Submit</button>
                        </div></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "auth-password-change",
        props: { errors: {} },
        data: function () {
            return {
                csrf: document.querySelector('meta[name="csrf-token"]').content
            }
        },
        methods: {
        }
    }
</script>

<style scoped>

</style>
