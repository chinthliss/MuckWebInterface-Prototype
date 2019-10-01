<template>
    <div class="card">
        <h4 class="card-header">Login</h4>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-md-4 col-form-label text-md-right" for="email">Email Address</label>

                <div class="col-md-6">
                    <input autocomplete="email" autofocus class="form-control" id="email" name="email"
                           placeholder="Enter a valid email address." required type="email"
                           v-bind:class="{ 'is-invalid' : errors.email }"
                           v-model="email">
                    <div class="invalid-feedback" role="alert">
                        <p v-for="error in errors.email">{{ error }}</p>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-md-4 col-form-label text-md-right" for="password">Password</label>

                <div class="col-md-6">
                    <input autocomplete="current-password" class="form-control" id="password" name="password"
                           placeholder="Enter password." required type="password"
                           v-bind:class="{ 'is-invalid' : errors.password }"
                           v-model="password">
                    <div class="invalid-feedback" role="alert">
                        <p v-for="error in errors.password">{{ error }}</p>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col">
                    <div class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="forget">
                            <label class="form-check-label" for="forget">
                                Don't remember login (e.g. for public computers)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col">
                    <div class="text-center">
                        <button @click="onClickLogin" class="btn btn-primary" type="button">Login</button>
                        <button @click="onClickCreate" class="btn btn-primary" type="button">Create Account</button>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col">
                    <div class="text-center">
                        <a href="/account/passwordforgotten">Reset a forgotten password.</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
    export default {
        name: "auth-account-login",
        data: function () {
            return {
                email: '',
                password: '',
                errors: {}
            }
        },
        methods: {
            onClickLogin: function (e) {
                this.errors = {};
                axios.post('/account/login', { //TODO: Replace uri with route
                    'email': this.email,
                    'password': this.password
                }).then(response => {
                    if (response.data.redirectUrl) location.replace(response.data.redirectUrl);
                    else location.reload();
                }).catch(error => {
                    if (error.response.status === 422)
                        this.errors = error.response.data.errors;
                    else if (error.response.status === 429)
                        Vue.set(this.errors, 'password', ['Too many attempts - please wait a minute before trying again.']);
                    else {
                        Vue.set(this.errors, 'password', ['Got an unexpected response from the server: ' +
                        error.response.statusText + '(' + error.response.status + ')']);
                    }
                });
            },
            onClickCreate: function (e) {
                this.errors = {};
                axios.post('/account/create', { //TODO: Replace uri with route
                    'email': this.email,
                    'password': this.password
                }).then(response => {
                    if (response.data.redirectUrl) location.replace(response.data.redirectUrl);
                    else location.reload();
                }).catch(error => {
                    if (error.response.status === 422)
                        this.errors = error.response.data.errors;
                    else {
                        Vue.set(this.errors, 'password', ['Got an unexpected response from the server: ' +
                        error.response.statusText + '(' + error.response.status + ')']);
                    }
                });
            }

        }
    }
</script>

<style scoped>

</style>
