<template>
    <div class="container">
        <h1>Account Roles List</h1>
        <p>Even if an account doesn't have a staff flag, they will still count as such if logged in as a wizard character.</p>
        <b-table dark striped hover small
                 :items="users"
                 :fields="fields"
                 :busy="isLoading"
        >
        </b-table>
    </div>
</template>

<script>
export default {
    name: "account-roles",
    props: {
        users: {type: Array, required: true}
    },
    data: function () {
        return {
            isLoading: false,
            fields: [
                {
                    key: 'id',
                    label: 'ID',
                    sortable: true
                },
                {
                    key: 'roles',
                    label: 'Roles',
                    formatter: 'outputArrayAsList',
                    sortable: true
                },
                {
                    key: 'characters',
                    label: 'Staff Characters',
                    formatter: 'outputWizards'
                },
                {
                    key: 'lastConnected',
                    label: 'Last Connected',
                    formatter: 'outputCarbonString',
                    sortable: true
                }
            ]
        }
    },

    methods: {
        outputArrayAsList: function (arrayToOutput) {
            if (!Array.isArray(arrayToOutput) || arrayToOutput.length === 0) return '--';
            return arrayToOutput.join(', ');
        },
        outputWizards: function (characterArray) {
            if (!Array.isArray(characterArray) || characterArray.length === 0) return '--';
            console.log("Running for", characterArray);
            let characters = characterArray.reduce((list, current) => {
                if (current.wizard) list.push(current.name);
                return list;
            }, []);
            return this.outputArrayAsList(characters);
        }
    }
}
</script>

<style scoped>
</style>
