<template>
    <div class="container">
        <h4>Forgotten Password</h4>
        <div class="row">
            <div class="col">
                <p>If you're forgotten your password you can use this page to trigger a reset on it by entering your
                    email below.</p>
                <p>If there is an account associated with the email given, it will be sent a link to reset the password
                    with.</p>
            </div>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="_token" :value="csrf">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="email">Email address</label></div>
                    <div class="col">
                        <input type="email" name="email" class="form-control" id="email"
                               placeholder="Enter email" v-bind:class="{ 'is-invalid' : errors.email }">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.email">{{ error }}</p>
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
    name: "auth-password-forgotten",
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
