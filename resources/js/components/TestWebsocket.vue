<template>
    <div class="container">
        <h2>Test</h2>
        <div class="row">
            <div id="ChatHistory" class="col-10 border rounded">
                <div v-for="message in history" class="message"><span class="user">{{ message.name }}</span>{{ message.message}}</div>
            </div>
            <div id="ChatUserList" class="col-2 border rounded">
                <div v-for="user in users">{{user}}</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function() {
        return {
            history: [ // In the form {name, message, sameAsLast}
                {name:'system', message:'Waiting on connection..', sameAsLast: false}
            ],
            users:[],
            channel: null
        }
    },
    name: "test-websocket",
    mounted() {
        MwiWebsocket.init();
        this.channel = MwiWebsocket.channel('test-websocket');

        this.channel.on('player-joined', (data) => {
            this.users.push(data);
        });

        this.channel.on('player-left', (data) => {
            const index = this.users.indexOf(data);
            if (index !== -1) this.users.splice(index, 1);
        });
    }
}
</script>

<style scoped>
    #ChatHistory {
        min-height:500px;
        background: mintcream;
    }

    #ChatUserList {
        min-height:500px;
        background: mintcream;
    }

    .message:first-child { border-top:none; }
    .message { border-top:1px solid gray; color:black; }
    .message-same-user { border-top:1px dashed gray; }
    .message-self { background-color: cornsilk; }
    .message span { font-weight:bold; margin-right:8px; color:navy; }
    .message-self span { color:darkgreen; }

</style>
