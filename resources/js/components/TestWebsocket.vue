<template>
    <div class="container">
        <h2>WebSocket Test</h2>
        <div class="row mb-2">
            <div id="ChatHistory" class="col-10 border rounded">
                <div v-for="message in history" class="message" v-bind:class="[
                        message.sameAsLast ? 'message-same-user' : '',
                        message.name === userName ? 'message-self' : ''
                    ]">
                    <span class="user">{{ message.name }}</span>{{ message.message }}
                </div>
            </div>
            <div id="ChatUserList" class="col-2 border rounded">
                <div v-for="user in users" class="user">{{ user }}</div>
            </div>
        </div>
        <div class="row">
            <div class="input-group">
                <input id="ChatInput" v-model="message" type="text" class="form-control"
                       placeholder="Enter your message here.." aria-label="Message input"
                       aria-describedby="message-send-button">
                <div class="input-group-append">
                    <button id="ChatInputButton" class="btn btn-primary" type="button">Send</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            history: [], // In the form {name, message, sameAsLast}
            users: [],
            userName: null,
            /** @type {ChannelInterface} */
            channel: null,
            lastUser: 0,
            connected: false,
            message: ''
        }
    },
    name: "test-websocket",
    mounted() {
        this.addSystemMessageToHistory('Waiting on connection');

        MwiWebsocket.init();
        this.channel = MwiWebsocket.channel('test-websocket');

        this.channel.on('connected', () => {
            this.connected = true;
            this.userName = MwiWebsocket.getPlayerName();
            this.addSystemMessageToHistory('Connected');
        });

        this.channel.on('disconnected', () => {
            this.connected = false;
            this.userName = null;
            this.addSystemMessageToHistory('Disconnected');
        });

        this.channel.on('player-list', (data) => {
            this.users = data;
        });

        this.channel.on('player-joined', (data) => {
            this.addSystemMessageToHistory('Player joined: ' + data);
        });

        this.channel.on('player-left', (data) => {
            this.addSystemMessageToHistory('Player left: ' + data);
        });

        $('#ChatInput').keypress((event) => {
            if (event.which === 13) {
                this.sendCurrentMessage();
                event.preventDefault();
            }
        });

        $('#ChatInputButton').click((event) => {
            this.sendCurrentMessage();
            event.preventDefault();
        });

        // Expecting [playerDbref, playerName, message]
        this.channel.on('message', (data) => {
            if (typeof data !== 'object') throw "Unexpected data in chat message";
            let [playerDbref, playerName, message] = data;
            const chatOutput = $('#ChatHistory');
            let safeParse = $('<div></div>');
            safeParse.text(message);
            this.addMessageToHistory(message, playerDbref, playerName);
            chatOutput.parent().scrollTop(chatOutput.parent()[0].scrollHeight);
        });
    },
    methods: {
        sendCurrentMessage: function () {
            if (this.message) {
                this.channel.send("message", this.message);
                this.message = "";
            }
        },
        addMessageToHistory: function(message, fromDbref, fromName) {
            this.history.push({name: fromName, message: message, sameAsLast: fromDbref === this.lastUser});
            this.lastUser = fromDbref;
        },
        addSystemMessageToHistory: function(message) {
            this.addMessageToHistory(message, -1, 'system')
        }
    }
}
</script>

<style scoped>
#ChatHistory {
    min-height: 500px;
    background: mintcream;
}

#ChatUserList {
    min-height: 500px;
    background: mintcream;
}

.message:first-child {
    border-top: none;
}

.message {
    border-top: 1px solid gray;
    color: black;
}

.message-same-user {
    border-top: 1px dashed gray;
}

.message-self {
    background-color: cornsilk;
}

.message span {
    font-weight: bold;
    margin-right: 8px;
    color: navy;
}

.message-self span {
    color: darkgreen;
}

.user {
    color: black;
    min-width: 96px;
    display: inline-block;
}

</style>
