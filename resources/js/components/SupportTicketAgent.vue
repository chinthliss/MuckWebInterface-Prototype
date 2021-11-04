<template>
    <div v-if="!ticket" class="container">
        No ticket.
    </div>
    <div v-else class="container">
        <h2>Ticket #{{ ticket.id }}</h2>
        <div v-if="remoteUpdatedAt && remoteUpdatedAt > ticket.updatedAt" class="alert alert-warning text-center">
            This ticket has been updated since you loaded it, some of the details may have changed.
            <br/>You should refresh as soon as possible to get the latest details!
        </div>
        <div class="row">
            <div class="col-12 col-lg-3 col-xl-2 mt-2">
                <div class="label editable" @click="showEditCategoryOrTitle">Category <i class="fas fa-edit"></i></div>
                <div class="value">{{ categoryLabel() }}</div>
            </div>
            <div class="col-12 col-lg-9 col-xl-7 mt-2">
                <div class="label editable" @click="showEditCategoryOrTitle">Title <i class="fas fa-edit"></i></div>
                <div class="value" v-html="parseUserContent(ticket.title)"></div>
            </div>
            <div class="col-12 col-xl-3 mt-2">
                <div class="label">Raised by</div>
                <div class="value">{{ ticket.requesterCharacterName }} (#{{ ticket.requesterCharacterDbref }})</div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="row">
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Created</div>
                <div class="value">{{ outputCarbonString(ticket.createdAt) }} <span
                    class="text-muted small">{{ ticket.createdAtTimespan }}</span></div>
            </div>
            <div class="col-12 col-lg-8 col-xl-4 mt-2">
                <div class="label">Status last changed</div>
                <div class="value">{{ outputCarbonString(ticket.statusAt) }} <span
                    class="text-muted small">{{ ticket.statusAtTimespan }}</span></div>
            </div>
            <div class="col-12 col-lg-4 col-xl-4 mt-2">
                <div class="label">Status</div>
                <div class="value">{{ capital(ticket.status) }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Last updated</div>
                <div class="value">{{ outputCarbonString(ticket.updatedAt) }} <span
                    class="text-muted small">{{ ticket.updatedAtTimespan }}</span></div>
            </div>
            <div class="col-12 col-lg-8 col-xl-4 mt-2">
                <div class="label">Closed</div>
                <div class="value">{{ outputCarbonString(ticket.closedAt) }} <span
                    class="text-muted small">{{ ticket.closedAtTimespan }}</span></div>
            </div>
            <div class="col-12 col-lg-4 col-xl-4 mt-2">
                <div class="label">Closure Reason</div>
                <div class="value">{{ capital(ticket.closureReason) }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Working</div>
                <div class="value">
                    <span class="mr-2" v-for="worker in ticket.workers"><a
                        :href="worker.accountUrl">User#{{ worker.accountId }}</a></span>
                </div>
            </div>
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Watching</div>
                <div class="value">
                    <span class="mr-2" v-for="watcher in ticket.watchers"><a
                        :href="watcher.accountUrl">User#{{ watcher.accountId }}</a></span>
                </div>
            </div>
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Voting</div>
                <div class="value" v-if="ticket.isPublic">
                    <i class="fas fa-thumbs-up"></i> 0 Agree<br/>
                    <i class="fas fa-thumbs-down"></i> 0 Disagree
                </div>
                <div class="value" v-else>
                    No voting, ticket isn't public.
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12" v-for="link in ticket.links_from"><i class="fas fa-arrow-left"></i> Linked as
                '{{ capital(link.type) }}' from <a :href="link.from_url">Ticket #{{ link.from }}</a> ({{
                    link.from_title
                }}).
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12" v-for="link in ticket.links_to"><i class="fas fa-arrow-right"></i> Linked as
                '{{ capital(link.type) }}' to <a :href="link.to_url">Ticket #{{ link.to }}</a> ({{ link.to_title }}).
            </div>
        </div>

        <div class="row">
            <div class="col text-center">
                <div class="mb-2 mt-2" role="group">
                    <button type="button" class="btn btn-secondary" @click="showChangeStatusOrClose">Change Status /
                        Close
                    </button>
                    <button type="button" class="btn btn-secondary" @click="assignOrUnassignToMe">
                        {{ assignOrUnassignLabel() }}
                    </button>
                    <button type="button" class="btn btn-secondary">Assign to Other(s)</button>
                    <button type="button" class="btn btn-secondary" @click="watchOrUnwatchTicket">
                        {{ watchOrUnwatchLabel() }}
                    </button>
                    <button type="button" class="btn btn-secondary" @click="changePublicStatus">
                        {{ publicOrPrivateLabel() }}</button>
                    <button type="button" class="btn btn-secondary" @click="showAddLink">Add Link</button>
                </div>
            </div>
        </div>
        <div class="divider"></div>

        <div class="row">
            <div class="col mt-2 muckContent" v-html="parseUserContent(ticket.content)"></div>
        </div>

        <div class="divider"></div>

        <h3 class="mt-2">Log</h3>
        <p class="text-muted"> Items marked with a '<i class="fas fa-eye-slash"></i>' are visible to staff only.</p>


        <div class="log-entry" v-for="entry in ticket.log">
            <div class="row mt-2">
                <div class="col-8 col-xl-4">
                    <span class="log-when">{{ outputCarbonString(entry.when) }}</span>
                    <span class="small text-muted">{{ entry.whenTimespan }}</span>
                </div>
                <div class="col-4 col-xl-2">
                    <span>{{ capital(entry.type) }}</span>
                    <span v-if="entry.staffOnly"><i class="fas fa-eye-slash"></i></span>
                </div>
                <div class="col-12 col-xl-6">
                    <span v-if="entry.character">{{ entry.character }}</span>
                    <span v-bind:class="[ entry.character ? ['text-muted', 'small'] : [] ]" v-if="entry.user">
                    User#{{ entry.user }}
                    </span>
                </div>
            </div>
            <div class="row">
                <div class="col" v-bind:class="[ 'log-type-' + entry.type ]"
                     v-html="entry.type === 'note' ? parseUserContent(entry.content) : entry.content"></div>
            </div>
        </div>

        <div class="form-group">
            <label class="label mt-2" for="addNote">Add New Note</label>
            <textarea class="form-control muckContent" id="addNote" rows="3" v-model="newNoteContent"></textarea>
            <button class="mt-2 btn btn-secondary" :disabled="!newNoteContent" @click="addPublicNote">Add Public Note</button>
            <button class="mt-2 btn btn-secondary" :disabled="!newNoteContent" @click="addPrivateNote">Add Staff-Only Note</button>
        </div>

        <DialogConfirmEdit id="editCategoryOrTitle" title="Edit Category/Title" @save="saveCategoryOrTitle">
            <div class="form-group">
                <label for="editCategory">Select Category</label>
                <select class="form-control" id="editCategory" v-model="newCategory">
                    <option :value="category.code" v-for="category in categoryConfiguration">{{
                            category.name
                        }}
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label for="editTitle">Title</label>
                <input class="form-control w-100" v-model="newTitle" id="editTitle">
            </div>
        </DialogConfirmEdit>

        <DialogConfirmEdit id="changeStatusOrClose" title="Change Status / Close" hide-save>

            <div class="row">
                <div class="col">
                    <button type="button" class="btn btn-primary btn-block h-100"
                            @click="saveChangeStatusOrClose('open')">
                        Open
                    </button>
                </div>
                <div class="col"><span class="small text-muted">Being actively worked upon.</span></div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <button type="button" class="btn btn-primary btn-block h-100"
                            @click="saveChangeStatusOrClose('pending')">
                        Pending
                    </button>
                </div>
                <div class="col"><span class="small text-muted">Waiting for a response from the requester. If they respond the ticket will automatically change to 'Open'.</span>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <button type="button" class="btn btn-primary btn-block h-100"
                            @click="saveChangeStatusOrClose('held')">
                        Held
                    </button>
                </div>
                <div class="col"><span class="small text-muted">Waiting for some other factor, ideally mentioned in the ticket's note.</span>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <button type="button" class="btn btn-secondary btn-block mt-2" :disabled="ticket.closedAt"
                            @click="saveChangeStatusOrClose('closed', 'completed')">
                        Closed - Completed
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <button type="button" class="btn btn-secondary btn-block mt-2" :disabled="ticket.closedAt"
                            @click="saveChangeStatusOrClose('closed', 'denied')">
                        Closed - Denied
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <button type="button" class="btn btn-secondary btn-block mt-2" :disabled="ticket.closedAt"
                            @click="saveChangeStatusOrClose('closed', 'duplicate')">
                        Closed - Duplicate
                    </button>
                </div>
            </div>
        </DialogConfirmEdit>

        <DialogConfirmEdit id="addLink" title="Add Link" hide-save>
            <div class="form-group">
                <label for="newLinkTo">ID of ticket to link to</label>
                <input class="form-control w-100" v-model="newLinkTo" id="newLinkTo">

                <div class="row mt-2">
                    <div class="col">
                        <button type="button" class="btn btn-primary btn-block"
                                :disabled="!newLinkTo"
                                @click="saveLink('duplicate')">
                            Link as Duplicate
                        </button>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <button type="button" class="btn btn-primary btn-block"
                                :disabled="!newLinkTo"
                                @click="saveLink('related')">
                            Link as Related
                        </button>
                    </div>
                </div>

            </div>

        </DialogConfirmEdit>

    </div>
</template>

<script>
import CharacterCard from "./CharacterCard";
import DialogConfirmEdit from "./DialogConfirmEdit";

export default {
    name: "support-ticket-agent",
    components: {DialogConfirmEdit, CharacterCard},
    props: ['initialTicket', 'pollUrl', 'updateUrl', 'categoryConfiguration'],
    data: function () {
        return {
            ticket: null,
            remoteUpdatedAt: null,
            newTitle: null,
            newCategory: null,
            newNoteContent: null,
            newLinkTo: null,
        };
    },
    computed: {},
    methods: {
        categoryLabel: function () {
            let label = null;
            this.categoryConfiguration.forEach(config => {
                if (config.code === this.ticket.category) label = config.name;
            });
            return label || `Unknown(${this.ticket.category}))`;
        },
        isWorkingTicket: function() {
            let accountId = parseInt(document.querySelector('meta[name="account-id"]').content);
            if (!this.ticket.workers) return false;
            let found = false;
            this.ticket.workers.forEach(worker => {
                if (worker.accountId === accountId) found = true;
            });
            return found;
        },
        isWatchingTicket: function() {
            let accountId = parseInt(document.querySelector('meta[name="account-id"]').content);
            if (!this.ticket.watchers) return false;
            let found = false;
            this.ticket.watchers.forEach(watcher => {
                if (watcher.accountId === accountId) found = true;
            });
            return found;
        },
        assignOrUnassignLabel: function () {
            return this.isWorkingTicket() ? 'Remove me as Working' : 'Add me as Working';
        },
        watchOrUnwatchLabel: function() {
            return this.isWatchingTicket() ? 'Stop Watching' : 'Start Watching';
        },
        publicOrPrivateLabel: function() {
            return this.ticket.isPublic ? 'Make Ticket Private' : 'Make Ticket Public';
        },
        parseUserContent: function (content) {
            let parsedContent = $('<div class="user-content"></div>');
            content.split('\n').forEach(function (line) {
                let parsedLine = $('<div></div>');
                parsedLine.text(line);
                parsedContent.append(parsedLine);
            });
            return parsedContent[0].outerHTML;
        },
        showEditCategoryOrTitle: function () {
            $('#editCategoryOrTitle').modal();
        },
        showChangeStatusOrClose: function () {
            $('#changeStatusOrClose').modal();
        },
        showAddLink: function () {
            $('#addLink').modal();
        },
        updateTicket: function(requestData) {
            // Passes an update of the ticket to the API. Expects an updated ticket object in response
            const self = this;
            console.log("Sending update: ", requestData);
            axios.post(self.updateUrl, requestData)
                .then(response => {
                    this.ticket = response.data;
                    console.log("New ticket data: ", response.data);
                    self.remoteUpdatedAt = this.ticket.updatedAt;
                })
                .catch(error => {
                    console.log("An error occurred with the requestData ", requestData, " when updating ticket: ", error);
                });
        },
        saveCategoryOrTitle: function () {
            let updateData = {};
            if (this.ticket.title !== this.newTitle)
                updateData.title = this.newTitle;
            if (this.ticket.category !== this.newCategory)
                updateData.category = this.newCategory;
            if (updateData.title || updateData.category) this.updateTicket(updateData);
        },
        saveChangeStatusOrClose: function (newStatus, newClosureReason) {
            $('#changeStatusOrClose').modal('hide');
            let updateData = {status: newStatus, closureReason: newClosureReason};
            this.updateTicket(updateData);
        },
        assignOrUnassignToMe: function () {
            let data = {};
            if (this.isWorkingTicket())
                data['task'] = 'RemoveMeAsWorker';
            else
                data['task'] = 'AddMeAsWorker';
            this.updateTicket(data);
        },
        watchOrUnwatchTicket: function () {
            let data = {};
            if (this.isWatchingTicket())
                data['task'] = 'RemoveMeAsWatcher';
            else
                data['task'] = 'AddMeAsWatcher';
            this.updateTicket(data);
        },
        changePublicStatus: function() {
            this.updateTicket({isPublic: !this.ticket.isPublic});
        },
        saveLink: function(typeOfLink) {
            $('#addLink').modal('hide');
            this.updateTicket({task: 'AddLink', to: this.newLinkTo, type: typeOfLink});
        },
        addPublicNote: function() {
            const content = this.newNoteContent.replace(/\r/g, '');
            this.updateTicket({task: 'AddPublicNote', content: content});
            this.newNoteContent = '';
        },
        addPrivateNote: function() {
            const content = this.newNoteContent.replace(/\r/g, '');
            this.updateTicket({task: 'AddPrivateNote', content: content});
            this.newNoteContent = '';
        }

    },
    mounted: function () {
        const self = this;
        self.ticket = self.initialTicket;
        self.newTitle = self.ticket.title;
        self.newCategory = self.ticket.category;

        setInterval(function () {
            axios.get(self.pollUrl)
                .then(response => self.remoteUpdatedAt = response.data);
        }, 60000);
    },
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.editable {
    cursor: pointer;
}

.label {
    font-size: 80%;
    font-weight: bold;
    color: $primary;
}

.divider {
    margin-top: 2px;
    border-bottom: 1px solid $secondary;
}

.log-when {
    color: $primary;
}

.log-entry {
    border-bottom: 1px dashed $secondary;
}

.log-type-note {
    @extend .muckContent;
    color: #8888cc;
}

</style>
