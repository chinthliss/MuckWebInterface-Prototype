<template>
    <div class="container">
        <h2>Ticket #{{ ticket.id }}</h2>

        <div class="row">
            <div class="col-12 col-lg-3 col-xl-2 mt-2">
                <div class="label">Category</div>
                <div class="value">{{ ticket.category }}</div>
            </div>
            <div class="col-12 col-lg-9 col-xl-7 mt-2">
                <div class="label">Title</div>
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
                <div class="value">{{ ticket.status }}</div>
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
                <div class="value">{{ ticket.closureReason }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Working</div>
                <div class="value">
                    <span class="mr-2" v-for="worker in ticket.workers"><a
                        :href="worker.url">User#{{ worker.accountId }}</a></span>
                </div>
            </div>
            <div class="col-12 col-xl-4 mt-2" v-if="ticket.watchers.length > 0">
                <div class="label">Watching</div>
                <div class="value">
                    <span class="mr-2" v-for="watcher in ticket.watchers"><a
                        :href="watcher.url">User#{{ watcher.accountId }}</a></span>
                </div>
            </div>
            <div class="col-12 col-xl-4 mt-2">
                <div class="label">Voting</div>
                <div class="value"><i class="fas fa-thumbs-up"></i> 0 Agree<br/>
                    <i class="fas fa-thumbs-down"></i> 0 Disagree
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col" v-for="link in ticket.links_from"><i class="fas fa-arrow-left"></i> Linked as
                '{{ link.type }}' from <a :href="link.from_url">Ticket #{{ link.from }}</a> ({{ link.from_title }}).
            </div>
        </div>

        <div class="row mt-2">
            <div class="col" v-for="link in ticket.links_to"><i class="fas fa-arrow-right"></i> Linked as
                '{{ link.type }}' to <a :href="link.to_url">Ticket #{{ link.to }}</a> ({{ link.to_title }}).
            </div>
        </div>

        <div class="divider"></div>

        <div class="row">
            <div class="col mt-2" v-html="parseUserContent(ticket.content)"></div>
        </div>

        <div class="divider"></div>

        <h3 class="mt-2">Log</h3>
        <b-table dark hover small
                 :items="ticket.log"
                 :fields="logFields"
        >
            <template #cell(when)="data">
                <span>{{ outputCarbonString(data.value) }}</span>
                <span class="small text-muted">{{ data.item.whenTimespan }}</span>
            </template>

            <template #cell(staffOnly)="data">
                <span v-if="data.value"><i class="fas fa-eye-slash"></i></span>
            </template>

            <template #cell(who)="data">
                <span v-if="data.item.character">{{ data.item.character }}</span>
                <span v-bind:class="[ data.item.character ? ['text-muted', 'small'] : [] ]" v-if="data.item.user">
                    User#{{ data.item.user }}
                </span>
            </template>

            <template #cell(content)="data">
                <span v-bind:class="[ 'log-' + data.item.type ]"
                      v-html="data.item.type === 'note' ? parseUserContent(data.value) : data.value" >
                </span>
            </template>

        </b-table>
    </div>
</template>

<script>
import CharacterCard from "./CharacterCard";

export default {
    name: "support-ticket-agent",
    components: {CharacterCard},
    props: ['ticket'],
    data: function () {
        return {
            logFields: [
                {
                    key: 'when',
                    label: 'When'
                },
                {
                    key: 'staffOnly',
                    label: ''
                },
                {
                    key: 'who',
                    label: 'Who'
                },
                {
                    key: 'content',
                    label: 'Content'
                }
            ]
        };
    },
    computed: {},
    methods: {
        parseUserContent: function (content) {
            let parsedContent = $('<div class="user-content"></div>');
            content.split('\\n').forEach(function (line) {
                let parsedLine = $('<div></div>');
                parsedLine.text(line);
                parsedContent.append(parsedLine);
            });
            return parsedContent[0].outerHTML;
        }
    }
}
</script>

<style scoped lang="scss">
@import '@/_variables.scss';

.label {
    font-size: 80%;
    font-weight: bold;
    color: $primary;
}

.divider {
    border-bottom: 1px solid $secondary;
}

.log-note {
    color: #8888cc;
}
</style>
