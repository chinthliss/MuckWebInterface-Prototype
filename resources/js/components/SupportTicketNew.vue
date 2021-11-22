<template>
    <div class="container">
        <h2>Raise a Ticket</h2>
        <div v-if="!ticketType">
            <h3>What type of ticket do you want to raise?</h3>
            <div class="row mb-1">
                <div class="col col-lg-2">
                    <button class="btn btn-primary w-100" @click="setType('issue')">Issue<br/>
                        <i class="fas fa-bug"></i></button>
                </div>
                <div class="col my-auto">
                    Something is broken or not working as intended.
                </div>
            </div>

            <div class="row mb-1">
                <div class="col col-lg-2">
                    <button class="btn btn-primary w-100" @click="setType('request')">Request
                        <br/><i class="fas fa-comment-dots"></i></button>
                </div>
                <div class="col my-auto">
                    Request something or make a suggestion.
                </div>
            </div>

            <div class="row mb-1" v-if="staffCharacter">
                <div class="col col-lg-2">
                    <button class="btn btn-primary w-100" @click="setType('task')">Task
                        <br/><i class="fas fa-tasks"></i></button>
                </div>
                <div class="col my-auto">
                    Usually these are raised by automated processes.
                </div>
            </div>

        </div>
        <div v-if="ticketType">
            <button class="btn btn-secondary mb-2" @click="reset">Change Type/Category</button>
        </div>
        <div v-if="ticketType && !ticketCategoryCode">
            <h3>Please select a category</h3>
            <div class="row mb-1" v-for="category in categoryConfiguration" v-if="category.type === ticketType">
                <div class="col col-lg-2">
                    <button class="btn btn-primary w-100" @click="setCategory(category.code)">{{ category.name }}</button>
                </div>
                <div class="col my-auto">
                    {{ category.description }}
                </div>
            </div>

        </div>
        <div v-if="ticketType && ticketCategoryCode">
            <form method="POST">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="ticketCategoryCode" v-model="ticketCategoryCode">
                <input type="hidden" name="ticketType" v-model="ticketType">

                <div v-if="staffCharacter">
                    <div class="form-group">
                        <label for="ticketCharacter">Character</label>
                        <input class="form-control" id="ticketCharacter" name="ticketCharacter" v-model="ticketCharacter">
                        <small id="characterHelp" class="form-text text-muted">Optional - fill this out to raise the ticket on behalf of somebody else.</small>
                        <div class="text-danger" role="alert">
                            <p v-for="error in errors.gender">{{ ticketCharacter }}</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ticketCategoryLabel">Category</label>
                    <input class="form-control" id="ticketCategoryLabel" name="ticketCategoryLabel" v-model="ticketCategoryLabel" readonly>
                </div>

                <div class="form-group">
                    <label for="ticketTitle">Subject</label>
                    <input class="form-control" id="ticketTitle" name="ticketTitle" v-model="ticketTitle" maxlength="80">
                    <small id="subjectHelp" class="form-text text-muted">Subject should be a short, single line description.</small>
                    <div class="text-danger" role="alert">
                        <p v-for="error in errors.ticketTitle">{{ error }}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ticketContent">Body</label>
                    <textarea class="form-control" id="ticketContent" rows="5" name="ticketContent" v-model="ticketContent"></textarea>
                    <div class="text-danger" role="alert">
                        <p v-for="error in errors.ticketContent">{{ error }}</p>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

    </div>
</template>

<script>
export default {
    name: "support-ticket-new",
    props: {
        categoryConfiguration: {type: Array, required: true},
        staffCharacter: {type: String, required: false},
        errors: {required: false},
        old: {type: Object, required: false}
    },
    data: function () {
        return {
            csrf: document.querySelector('meta[name="csrf-token"]').content,
            ticketCharacter: this.old?.ticketCharacter,
            ticketType: this.old?.ticketType,
            ticketCategoryCode: this.old?.ticketCategoryCode,
            ticketCategoryLabel: this.old?.ticketCategoryLabel,
            ticketTitle: this.old?.ticketTitle,
            ticketContent: this.old?.ticketContent
        };
    },
    methods: {
        setType: function(type) {
            this.ticketType = type;
        },
        setCategory: function(categoryCode) {
            this.ticketCategoryCode = categoryCode;
            this.categoryConfiguration.forEach(category => {
                if (category.code === categoryCode) this.ticketCategoryLabel = category.name;
            });
        },
        reset: function() {
            this.ticketType = null;
            this.ticketCategoryCode = null;
            this.ticketCategoryLabel = null;
        }
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.label {
    font-weight: bold;
    color: $primary;
}

.divider {
    margin-top: 2px;
    border-bottom: 1px solid $secondary;
}

</style>
