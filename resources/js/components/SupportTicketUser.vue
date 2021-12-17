<template>
    <div v-if="!ticket" class="container">
        Ticket loading..
    </div>
    <div v-else class="container">
        <h2>Ticket #{{ ticket.id }}</h2>

        <div v-if="remoteUpdatedAt && remoteUpdatedAt > ticket.updatedAt" class="alert alert-warning text-center">
            This ticket has been updated since you loaded it, some of the details may have changed.
            <br/>You should refresh as soon as possible to get the latest details!
        </div>

        <div class="d-flex flex-column flex-xl-row">
            <div class="flex-xl-grow-1">
                <div class="d-flex flex-column flex-xl-row">
                    <div>
                        <div class="label">Category</div>
                        <div class="value">{{ ticket.categoryLabel }}</div>
                    </div>
                    <div class="mt-2 mt-xl-0 ml-xl-4 flex-xl-grow-1">
                        <div class="label">Title</div>
                        <div class="value" v-html="parseUserContent(ticket.title)"></div>
                    </div>
                </div>
                <div class="mt-2">
                        <div class="label">Description</div>
                        <div class="muckContent" v-html="parseUserContent(ticket.content)"></div>
                </div>
            </div>
            <div class="mt-2 mt-xl-0 ml-xl-4" v-if="ticket.from.character">
                <div class="label">Raised by</div>
                <character-card :character="ticket.from.character"
                                mode="tag" class="mr-2 mb-2 align-top">
                </character-card>
            </div>
        </div>

        <div class="divider"></div>

        <div class="row">
            <div class="col-12 col-lg-6 mt-2">
                <div class="label">Created</div>
                <div class="value">{{ outputCarbonString(ticket.createdAt) }} <span
                    class="text-muted small">{{ ticket.createdAtTimespan }}</span></div>
            </div>
            <div class="col-12 col-lg-6 mt-2">
                <div class="label">Last updated</div>
                <div class="value">{{ outputCarbonString(ticket.updatedAt) }} <span
                    class="text-muted small">{{ ticket.updatedAtTimespan }}</span></div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-6 mt-2">
                <div class="label">Status</div>
                <div class="value">{{ capital(ticket.status) }}</div>
            </div>
            <div class="col-12 col-lg-6 mt-2" v-if="ticket.closedAt">
                <div class="label">Closure Reason</div>
                <div class="value">{{ capital(ticket.closureReason) }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col mt-2">
                <div class="label">Handling</div>
                <div class="value">
                    <span v-if="ticket.agent.character">{{ ticket.agent.character.name }}</span>
                    <span v-else-if="ticket.agent.user">Assigned</span>
                    <span v-else>Unassigned</span>
                </div>

            </div>
        </div>

        <div class="row" v-if="ticket.isPublic">
            <div class="col mt-2">
                <div class="label">Voting</div>
                <div class="value">
                    <i class="fas fa-thumbs-up"></i> {{ ticket.votes.up }} Agree / For<br/>
                    <i class="fas fa-thumbs-down"></i> {{ ticket.votes.down }} Disagree / Against
                </div>
            </div>
            <div class="col mt-2" v-if="ticket.canVote">
                <button class="btn btn-secondary" @click="voteUp">Vote Up <i class="fas fa-thumbs-up"></i></button>
                <button class="btn btn-secondary ml-2" @click="voteDown">Vote Down <i class="fas fa-thumbs-down"></i></button>
                <div v-if="ticket.vote">You have previously voted this ticket {{ ticket.vote === 'upvote' ? 'up' : 'down' }}</div>
            </div>
            <div class="col mt-2" v-else>
                You can't vote on this ticket.
            </div>
        </div>

        <div class="d-flex mt-2" v-if="!ticket.isPublic && ticket.canMakePublic">
            <div class="my-auto">
                <button type="button" class="btn btn-secondary" @click="makePublic">Make Ticket Public</button>
            </div>
            <div class="my-auto ml-2">
                This ticket is private. If you would like other players to be able to see and comment upon it then consider making it public.<br/>
                Do not make a ticket public if it contains personal information.
            </div>
        </div>

        <div class="d-flex mt-2">
            <div class="my-auto">
                <button type="button" class="btn btn-secondary" @click="watchOrUnwatchTicket">
                    {{ watchOrUnwatchLabel() }}
                </button>
            </div>
            <div class="my-auto ml-2">
                You can watch this ticket in order to receive notifications when something changes on it.
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

        <div class="divider"></div>

        <h3 class="mt-2">Log</h3>

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
                    Account#{{ entry.user }}
                    </span>
                </div>
            </div>
            <div class="row">
                <div class="col" v-bind:class="[ 'log-type-' + entry.type ]"
                     v-html="entry.type === 'note' ? parseUserContent(entry.content) : entry.content"></div>
            </div>
        </div>

        <div v-if="this.postingAs" class="form-group">
            <label class="label mt-2" for="addNote">Add New Note</label>
            <textarea class="form-control muckContent" id="addNote" rows="3" v-model="newNoteContent"></textarea>
            <button class="mt-2 btn btn-secondary" :disabled="!newNoteContent" @click="addNote">Add Note as {{ this.postingAs }}</button>
        </div>
        <div v-else class="alert alert-warning text-center">
            You will need to login as a character before you can add notes to this ticket.
        </div>

        <DialogMessage id="errorMessage">
            {{ this.errorMessage }}
        </DialogMessage>

    </div>
</template>

<script>
import DialogMessage from "./DialogMessage";
import CharacterCard from "./CharacterCard";

export default {
    name: "support-ticket-user",
    components: {DialogMessage, CharacterCard},
    props: {
        initialTicket:{type: Object, required: true},
        pollUrl:{type: String, required: true},
        updateUrl:{type: String, required: true}
    },
    data: function () {
        return {
            ticket: null,
            remoteUpdatedAt: null,
            newNoteContent: null,
            errorMessage: null
        };
    },
    computed: {
        postingAs: function() {
            return document.head.querySelector('meta[name="character-name"]')?.content;
        }

    },
    methods: {
        parseUserContent: function (content) {
            let parsedContent = $('<div class="user-content"></div>');
            content.split('\n').forEach(function (line) {
                let parsedLine = $('<div></div>');
                parsedLine.text(line);
                parsedContent.append(parsedLine);
            });
            return parsedContent[0].outerHTML;
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
                    console.log(error.response);
                    this.errorMessage = error?.response?.data?.message || error.message;
                    $('#errorMessage').modal();
                });
        },
        watchOrUnwatchTicket: function () {
            let data = {};
            if (this.ticket.watching)
                data['task'] = 'RemoveWatcher';
            else
                data['task'] = 'AddWatcher';
            this.updateTicket(data);
        },
        makePublic: function() {
            this.updateTicket({isPublic: true});
        },
        addNote: function() {
            const content = this.newNoteContent.replace(/\r/g, '');
            this.updateTicket({task: 'AddPublicNote', muck_content: content});
            this.newNoteContent = '';
        },
        watchOrUnwatchLabel: function() {
            return this.ticket.watching ? 'Stop Watching' : 'Start Watching';
        },
        voteUp: function() {
            this.updateTicket({task: 'VoteUp'});
        },
        voteDown: function() {
            this.updateTicket({task: 'VoteDown'});
        }
    },
    mounted: function () {
        const self = this;
        self.ticket = self.initialTicket;

        setInterval(function () {
            axios.get(self.pollUrl)
                .then(response => self.remoteUpdatedAt = response.data);
        }, 60000);
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.label {
    font-size: 80%;
    font-weight: 600;
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
