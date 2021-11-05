<template>
    <div class="container">
        <h4 class="text-center">Character Selection</h4>
        <div class="mb-2 text-center row">
            <div class="col">
                <character-card v-for="character in characters" v-bind:key="character.dbref" :character="character"
                                mode="tag" class="mr-2 mb-2 align-top"
                                @click="setActiveCharacter(character.dbref)"></character-card>
                <div v-for="i in emptyCharacterSlots" v-bind:key="i"
                     class="card empty-character-card border-primary mr-2 mb-2 align-top">
                    <div class="card-body h-100">
                        <a class="btn btn-primary" :href="createCharacterUrl"><span class="d-flow"><i
                            class="fas fa-plus btn-icon-left"></i>New character</span></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <div class="col alert alert-danger" v-if="moreCharacterSlotsRequired">
                You are over your character limit by {{ moreCharacterSlotsRequired }}. This may cause characters to become unavailable.
            </div>
        </div>
        <div class="row text-center">
            <div class="col">
                <a class="btn btn-primary btn-with-img-icon" href="#" @click="buyCharacterSlot">
                    <span class="btn-icon-accountcurrency btn-icon-left"></span>
                    Buy Character Slot
                    <span class="btn-second-line">{{ characterSlotCost }} {{ lex('accountcurrency') }}</span>
                </a>
            </div>
        </div>

        <dialog-approve-transaction id="DialogBuyCharacterSlot"
                                    :transaction="transaction"
                                    @transaction-accepted="buyCharacterSlotAccepted"
        ></dialog-approve-transaction>

        <dialog-message id="DialogMessage" :title="messageDialogHeader">
            {{ messageDialogContent }}
        </dialog-message>
    </div>
</template>

<script>
import DialogApproveTransaction from "./DialogApproveTransaction";
import DialogMessage from "./DialogMessage";
import CharacterCard from "./CharacterCard";

export default {
    name: "character-select",
    components: {DialogApproveTransaction, DialogMessage, CharacterCard},
    props: {
        characters: {type: Array, required: true},
        initialCharacterSlotCount: {type: Number, required: true},
        initialCharacterSlotCost: {type: Number, required: true},
        buyCharacterSlotUrl: {type: String, required: true},
        createCharacterUrl: {type: String, required: true}
    },
    data: function () {
        return {
            characterSlotCount: 0,
            characterSlotCost: 0,
            transaction: {},
            messageDialogHeader: "",
            messageDialogContent: ""
        }
    },
    mounted: function () {
        this.characterSlotCost = this.initialCharacterSlotCost;
        this.characterSlotCount = this.initialCharacterSlotCount;
    },
    computed: {
        emptyCharacterSlots: function () {
            if (this.characterSlotCount > this.characters.length)
                return this.characterSlotCount - this.characters.length
            else return 0;
        },
        moreCharacterSlotsRequired: function () {
            if (this.characterSlotCount < this.characters.length)
                return this.characters.length - this.characterSlotCount;
            else return 0;
        }
    },
    methods: {
        setActiveCharacter: (dbref) => {
            let promise = axios.post('/account/setactivecharacter', {dbref: dbref});
            return promise
                .then(response => {
                    if (response.data.redirectUrl) location.replace(response.data.redirectUrl);
                    else location.reload();
                });
        },
        buyCharacterSlot: function () {
            this.transaction = {
                price: this.characterSlotCost + ' ' + lex('accountcurrency'),
                purchase: "New Character Slot"
            };
            $('#DialogBuyCharacterSlot').modal();
        },
        buyCharacterSlotAccepted: function () {
            return axios.post(this.buyCharacterSlotUrl)
                .then(response => {
                    if (response.data.error) {
                        this.messageDialogHeader = "An error occurred..";
                        this.messageDialogContent = response.data.error;
                        $('#DialogMessage').modal();
                    } else {
                        this.characterSlotCost = response.data.characterSlotCost;
                        this.characterSlotCount = response.data.characterSlotCount;
                    }
                })
                .catch(error => {
                    this.messageDialogHeader = "An error occurred..";
                    this.messageDialogContent = "Something went wrong with the request to the muck.";
                    $('#DialogMessage').modal();
                });
        }
    }
}
</script>

<style scoped>
.empty-character-card {
    display: inline-block;
    border-style: dashed;
    width: 240px;
    height: 100px;
}

.empty-character-card .btn {
    margin-top: 8px;
}
</style>
