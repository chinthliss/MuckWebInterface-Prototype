<template>
    <div class="container">
        <div class="card">
            <h4 class="card-header">Account</h4>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-2">Email</dt>
                    <dd class="col-sm-10">{{ email }}</dd>

                    <dt class="col-sm-2">Created</dt>
                    <dd class="col-sm-10">{{ accountCreated }}</dd>
                </dl>
                <div class="row col"><a href="/account/changepassword">Change Password</a></div>
                <div class="row col"><a href="/account/changeemail">Change Email</a></div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "auth-account",
        props: ['accountCreated', 'email'],
        data: function () {
            return {
            }
        },
        methods: {
        }
    }
</script>

<style scoped>

</style>
