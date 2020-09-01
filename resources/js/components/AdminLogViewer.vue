<style scoped>
.log-line {
    word-break: break-all;
    font-size: 90%;
}
</style>

<template>
    <div class="card">
        <h4 class="card-header">Site Logs</h4>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 border border-secondary rounded" id="date-selector">
                    <div v-for="date in dates"><a href="#" @click="loadDate(date, $event)">{{ date }}</a></div>
                </div>
                <div class="col-md-10">
                    <table v-if="log" class="table">
                        <thead>
                        <tr>
                            <th scope="col">Time</th>
                            <th scope="col">Level</th>
                            <th scope="col">Log</th>
                        </tr>
                        </thead>
                        <tr v-for="entry in log">
                            <td>{{ entry.time }}</td>
                            <td :class="classForLevel(entry.level)">{{ entry.level }}</td>
                            <td>
                                <div v-for="line in entry.lines" class="log-line">{{ line }}</div>
                            </td>
                        </tr>
                    </table>
                    <div v-else>
                        Select a date to view log entries.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const logRegEx = /^\[\d{4}-\d\d-\d\d (\d\d:\d\d:\d\d)] (?:\S*)?\.(\S*): /mg;
export default {
    name: "admin-log-viewer",
    props: ['dates'],
    data: function () {
        return {
            'log': ''
        }
    },

    methods: {
        loadDate: function (date, event) {
            event.preventDefault();
            axios.get('/admin/logs/' + date)
                .then(response => this.parseLog(response.data));
        },
        parseLog: function (rawText) {
            this.log = [];
            let slicePoints = [];
            // Figure out where individual line entries start
            let token = logRegEx.exec(rawText);
            while (token) {
                this.log.push({
                    time: token[1],
                    level: token[2]
                });
                slicePoints.push({
                    token_starts: token.index,
                    log_starts: token.index + token[0].length,
                });
                token = logRegEx.exec(rawText);
            }
            // Slice log around the points found
            for (let i = slicePoints.length - 1; i >= 0; i--) {
                let logStart = slicePoints[i].log_starts;
                let logEnd = (i === slicePoints.length - 1 ? rawText.length : slicePoints[i + 1].token_starts);
                this.log[i].lines = rawText
                    .slice(logStart, logEnd)
                    .split('\n');
            }
        },
        classForLevel: function(level) {
            if (level === 'ERROR') return 'text-danger';
            if (level === 'WARNING') return 'text-warning';
            return null;
        }
    }
}
</script>

