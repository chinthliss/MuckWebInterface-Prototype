<template>
    <div class="card">
        <h4 class="card-header">Account</h4>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-2">Created</dt>
                <dd class="col-sm-10">{{ accountCreated }}</dd>
            </dl>
            <h5>Emails</h5>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th scope="col">Email</th>
                    <th scope="col" class="text-center">Primary?</th>
                    <th scope="col">Registered</th>
                    <th scope="col">Validated</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(details, email) in emails">
                    <td>{{ email }}</td>
                    <td class="text-center">
                        <span v-if="email === primaryEmail" class="text-muted">Primary</span>
                        <button v-else class="btn btn-secondary" @click="verifyUseEmail(email)">Make Primary
                        </button>
                    </td>
                    <td>{{details.created_at}}</td>
                    <td>{{details.verified_at}}</td>
                </tr>
                </tbody>
            </table>
            <div class="row">
                <div class="col">
                    <div class="btn-toolbar" role="group" aria-label="Account Controls">
                        <a class="btn btn-secondary mt-1 ml-1" href="/account/changepassword">Change Password</a>
                        <a class="btn btn-secondary mt-1 ml-1" href="/account/changeemail">Change to new Email</a>
                        <a class="btn btn-secondary mt-1 ml-1" href="/account/cardmanagement">Card Management</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Email Modal -->
        <div class="modal fade" id="changeEmailModal" tabindex="-1" role="dialog"
             aria-labelledby="changeEmailModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeEmailModalLabel">Change Email</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="changeEmailForm" action="/account/useexistingemail" method="POST">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="email" :value="this.changeEmailTo">
                        </form>
                        <p>Your primary email is the one where notifications are sent to.</p>
                        <p>If it hasn't been verified, you'll be prompted to verify it next.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" data-dismiss="modal" @click="useEmail">Change
                            Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


</template>

<script>
    export default {
        name: "auth-account",
        props: ['accountCreated', 'primaryEmail', 'emails', 'errors'],
        data: function () {
            return {
                csrf: document.querySelector('meta[name="csrf-token"]').content,
                changeEmailTo: ''
            }
        },
        methods: {
            verifyUseEmail: function (email) {
                this.changeEmailTo = email;
                $('#changeEmailModal').modal();
            },
            useEmail: function () {
                $('#changeEmailForm').submit();
            }
        }
    }
</script>

<style scoped>

</style>
