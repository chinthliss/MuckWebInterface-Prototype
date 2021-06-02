<template>
    <div class="container">
        <h4>Change Email</h4>
        <div class="row">
            <div class="col">
                <p>Use this page to change your present email. Please note that after changing your email it will need
                    to be verified - a link will be sent to it in order to do this.</p>
            </div>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="_token" :value="csrf">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="password">Password</label></div>
                    <div class="col">
                        <input type="password" name="password" class="form-control" id="password"
                               placeholder="Enter existing password" v-bind:class="{ 'is-invalid' : errors.password }">
                        <div class="invalid-feedback" role="alert">
                            <p v-for="error in errors.password">{{ error }}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-form-label text-md-right"><label for="email">New Email</label></div>
                    <div class="col">
                        <input type="email" name="email" class="form-control" id="email"
                               placeholder="Enter new email" v-bind:class="{ 'is-invalid' : errors.email }">
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
    name: "auth-email-change",
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
