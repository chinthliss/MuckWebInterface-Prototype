<template>
    <div class="container">
        <h2>Test</h2>
        <div class="row">
            <div id="ChatHistory" class="col-10 border rounded">
                <div v-for="message in history" class="message" v-bind:class="[
                        message.sameAsLast ? 'message-same-user' : '',
                        message.name === this.userName ? 'message-self' : ''
                    ]">
                    <span class="user">{{ message.name }}</span>{{ message.message }}
                </div>
            </div>
            <div id="ChatUserList" class="col-2 border rounded">
                <div v-for="user in users" class="user">{{ user }}</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            history: [ // In the form {name, message, sameAsLast}
                {name: 'system', message: 'Waiting on connection..', sameAsLast: false}
            ],
            users: [],
            userName: null,
            channel: null,
            lastUser: 0,
            connected: false
        }
    },
    name: "test-websocket",
    mounted() {
        MwiWebsocket.init();
        this.channel = MwiWebsocket.channel('test-websocket');

        this.channel.on('connected', () => {
            this.connected = true;
            this.userName = MwiWebsocket.getPlayerName();
            this.history.push({name: 'system', message: 'Connected', sameAsLast: false});
        });

        this.channel.on('disconnected', () => {
            this.connected = false;
            this.userName = null;
            this.history.push({name: 'system', message: 'Disconnected', sameAsLast: false});
        });

        this.channel.on('player-list', (data) => {
            this.users = data;
        });

        // Expecting [player, playerName, message]
        this.channel.on('message', (data) => {
            if (typeof data !== 'object') throw "Unexpected data in chat message";
            let [player, playerName, message] = data;
            const chatOutput = $('#ChatHistory');
            let safeParse = $('<div></div>');
            safeParse.text(message);
            this.history.push({name: playerName, message: message, sameAsLast: player === this.lastUser});
            chatOutput.parent().scrollTop(chatOutput.parent()[0].scrollHeight);
            this.lastUser = player;
        });
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
}

</style>
