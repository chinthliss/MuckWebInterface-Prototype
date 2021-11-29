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

            <div class="row mb-1" v-if="staff">
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
                    <span v-if="staff">
                        <span class="badge badge-pill badge-info" v-if="category.usersCannotRaise">Staff Only</span>
                        <span class="badge badge-pill badge-info" v-if="category.notGameSpecific">Account Based</span>
                        <span class="badge badge-pill badge-info" v-if="category.neverPublic">No Public Option</span>
                    </span>
                </div>
            </div>

        </div>
        <div v-if="ticketType && ticketCategoryCode">
            <form method="POST">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="ticketCategoryCode" v-model="ticketCategoryCode">
                <input type="hidden" name="ticketType" v-model="ticketType">

                <div class="form-group">
                    <label for="ticketCategoryLabel">Category</label>
                    <input class="form-control" id="ticketCategoryLabel" name="ticketCategoryLabel" v-model="ticketCategoryLabel" readonly>
                </div>

                <div v-if="staff" class="form-group">
                    <label for="ticketCharacter">Assign to a different Character <span class="badge badge-pill badge-info">Staff Only</span></label>
                    <input class="form-control" id="ticketCharacter" name="ticketCharacter" v-model="ticketCharacter">
                    <small id="characterHelp" class="form-text text-muted">Optional - Any character can be entered here. If no character is entered, the ticket will be from yourself.</small>
                    <div class="text-danger" role="alert">
                        <p v-for="error in errors.ticketCharacter">{{ error }}</p>
                    </div>
                </div>
                <div v-else class="form-group">
                    <label for="character">Character</label>
                    <select class="form-control" name="ticketCharacter" v-model="ticketCharacter" >
                        <option value="_initial">Select a character</option>
                        <option value="_account">No Character (Account based)</option>
                        <option v-for="character in characters" :value="character">{{ character }}</option>
                    </select>
                    <div class="text-danger" role="alert">
                        <p v-for="error in errors.ticketCharacter">{{ error }}</p>
                    </div>
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
        staff: {type: Boolean, required: false, default: false},
        characters: {type: Object, required: false, default: []},
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
            let category = null;
            this.ticketCategoryCode = categoryCode;
            this.categoryConfiguration.forEach(potentialCategory => {
                if (potentialCategory.code === categoryCode) category = potentialCategory;
            });
            this.ticketCategoryLabel = category.name;

            // Character default rules if not staff
            if (!this.staff) {
                if (category.notGameSpecific)
                    this.ticketCharacter = '_account';
                else if (!this.ticketCharacter) {
                    let dbref = parseInt(document.querySelector('meta[name="character-dbref"]')?.content);
                    if (dbref && this.characters[dbref])
                        this.ticketCharacter = this.characters[dbref];
                    else
                        this.ticketCharacter = '_initial';
                }
            }
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
