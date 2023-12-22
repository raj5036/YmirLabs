<template>
    <v-card class="card--examination elevation-2">
        <v-card-title primary-title>
            <h2 class="text-xs-center">{{$t('title.examination')}}</h2>
            <v-spacer></v-spacer>
            <h3 class="text-xs-center">{{examination.deadline}}</h3>
        </v-card-title>
        <v-card-text>
           <v-layout>
                <v-flex xs12>
                    <v-text-field
                        v-if="examination.client  && examination.client.partner"
                        :label="$t('examination.partner.label')"
                        :value="examination.client.partner"
                        readonly>
                    </v-text-field>
                    <v-text-field v-if="ssn"
                        :label="$t('examination.ssn.label')"
                        :value="ssn"
                        readonly>
                    </v-text-field>
                    <v-text-field v-if="examination.child_ssn"
                        :label="$t('examination.childSsn.label')"
                        :value="examination.child_ssn"
                        readonly>
                    </v-text-field>
                </v-flex>
            </v-layout>

            <v-layout v-if="examination.category !== 'video'">
                <v-flex xs5 sm5>
                    <v-text-field
                        :label="$t('examination.email.label')"
                        :value="examination.client.email"
                        readonly>
                    </v-text-field>
                </v-flex>
                <v-flex xs5 sm4>
                    <v-text-field
                        :label="$t('examination.who.label')"
                        :value="whoLabel"
                        readonly>
                    </v-text-field>
                </v-flex>
                <v-flex xs3 sm3>
                    <v-text-field
                        :label="$t('examination.deadline.label')"
                        :value="examination.deadline_time"
                        readonly>
                    </v-text-field>
                </v-flex>
            </v-layout>

            <v-layout v-if="examination.category !== 'video'">
                <v-flex xs5 sm5>
                    <v-text-field
                        :label="$t('examination.name.label')"
                        :value="name"
                        readonly>
                    </v-text-field>
                </v-flex>
                <v-flex xs5 sm4>
                    <v-text-field
                        :label="$t('examination.contact.label')"
                        :value="phonenumber"
                        readonly>
                    </v-text-field>
                </v-flex>
                <v-flex xs3 sm3>
                    <v-text-field
                        :label="$t('examination.gender.label')"
                        :value="genderLabel"
                        readonly>
                    </v-text-field>
                </v-flex>
            </v-layout>
            <v-layout v-if="examination.gender === 'woman'">
                <v-flex xs12 sm5>
                    <v-text-field
                        :label="$t('examination.pregnant.label')"
                        :value="pregnantLabel"
                        readonly>
                    </v-text-field>
                </v-flex>
                <v-flex xs12 sm7>
                    <v-text-field
                        :label="$t('examination.breastfeeding.label')"
                        :value="breastfeedingLabel"
                        readonly>
                    </v-text-field>
                </v-flex>
            </v-layout>
            <v-layout>
                <v-flex xs5 sm6>
                    <v-text-field
                        :label="$t('examination.dob.label')"
                        :value="examination.client.date_of_birth"
                        readonly>
                    </v-text-field>
                </v-flex>
                <v-flex xs3 sm12>
                    <v-text-field
                        :label="$t('examination.age.label')"
                        :value="examination.age"
                        readonly>
                    </v-text-field>
                </v-flex>
            </v-layout>
            <v-layout v-if="examination.client.ethnicity">
                 <v-flex xs5 sm5>
                    <label :style="{ fontSize: '13px' }" class="v-label v-label--active theme--light">{{$t('examination.ethnic.label')}}</label>
                    <div class="skin-button" :style="{ background: examination.client.ethnicity }"/>
                </v-flex>
            </v-layout>
            <v-layout>
                <v-flex xs12>
                    <v-text-field
                        v-if="examination.duration"
                        :label="$t('examination.disease_duration.label')"
                        :value="examination.duration"
                        readonly>
                    </v-text-field>
                </v-flex>
            </v-layout>
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <!--                                        MOLE                                                         -->
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <v-layout column v-if="examination.category === 'mole'">
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.mole.size.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.mole_size}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.mole.symmetri.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.mole_symmetri}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.mole.color.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.mole_color}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.mole.change.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.mole_change}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.mole.others.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.mole_others}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap v-if="examination.mole_others === 'yes'">
                    <v-flex xs12>
                        <v-textarea
                            :label="$t('questions.mole.others.description.question')"
                            :value="examination.mole_others_description"
                            readonly>
                        </v-textarea>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.mole.doctor.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.mole_doctor}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap>
                    <v-flex xs12>
                        <v-textarea
                            :label="$t('questions.mole.description.question')"
                            :value="examination.mole_description"
                            readonly>
                        </v-textarea>
                    </v-flex>
                </v-layout>
            </v-layout>
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <!--                                        RASH                                                         -->
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <v-layout column v-if="examination.category === 'rash'">
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.rash.same.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.rash_same}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.rash.cold.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.rash_cold}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.rash.drugs.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.rash_drugs}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap v-if="examination.rash_drugs === 'yes'">
                    <v-flex xs12>
                        <v-textarea
                            :label="$t('questions.rash.drugs.description.question')"
                            :value="examination.rash_drugs_description"
                            readonly>
                        </v-textarea>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.rash.doctor.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.rash_doctor}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap>
                    <v-flex xs12>
                        <v-textarea
                            :label="$t('questions.rash.description.question')"
                            :value="examination.rash_description"
                            readonly>
                        </v-textarea>
                    </v-flex>
                </v-layout>
            </v-layout>
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <!--                                      SKIN CANCER                                                    -->
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <v-layout column v-if="examination.category === 'skin_cancer'">
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.skin_cancer.size.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.skin_cancer_size}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.skin_cancer.change.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.skin_cancer_change}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap v-if="examination.skin_cancer_change === 'yes'">
                    <v-flex xs12>
                        <v-textarea
                            :label="$t('questions.skin_cancer.change.description.question')"
                            :value="examination.skin_cancer_change_description"
                            readonly>
                        </v-textarea>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap class="yes-no-question">
                    <v-flex xs12 sm10><v-icon >question_answer</v-icon>{{$t('questions.skin_cancer.doctor.question')}}</v-flex>
                    <v-flex xs12 sm2 text-right>
                        <v-text-field
                            :value="$t(`answer.options.${examination.skin_cancer_doctor}`)"
                            hide-details
                            class="text-xs-right"
                            readonly>
                        </v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout align-center row wrap>
                    <v-flex xs12>
                        <v-textarea
                            :label="$t('questions.skin_cancer.description.question')"
                            :value="examination.skin_cancer_description"
                            readonly>
                        </v-textarea>
                    </v-flex>
                </v-layout>
            </v-layout>
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <!--                                        OTHER                                                        -->
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <v-flex xs11 sm6 pa-2
                v-for="idProof in idProofs"
                :key="idProof">
                <v-layout column>
                    <label>Id Proof</label>
                    <img :src="idProof"
                    title="idProof"
                    width="100%"
                    class="elevation-4"
                    @click.stop="showDialog(idProof)">
                </v-layout>
            </v-flex>
            <v-layout column wrap v-if="examination.category === 'picture' || examination.category === 'video'">
                <v-textarea
                    v-if="examination.client  && examination.client.address"
                    :label="$t('examination.address.label')"
                    :value="examination.client.address"
                    readonly>
                </v-textarea>
                <v-textarea
                    v-if="bodyPart"
                    :label="$t('examination.body_part.label')"
                    :value="bodyPart"
                    readonly>
                </v-textarea>
                <v-textarea
                    v-if="illness"
                    :label="$t('examination.illness.label')"
                    :value="illness"
                    readonly>
                </v-textarea>
                <v-textarea
                    :label="$t('examination.allergy_description.label')"
                    :value="allergy"
                    readonly>
                </v-textarea>
                <v-textarea
                    :label="$t('examination.medication_description.label')"
                    :value="medication"
                    readonly>
                </v-textarea>
                <v-textarea
                    :label="$t('examination.past_treatment.label')"
                    :value="pastTreatment"
                    readonly>
                </v-textarea>
                <v-textarea
                    :label="$t('examination.family_history.label')"
                    :value="familyHistory"
                    readonly>
                </v-textarea>
            </v-layout>
            <!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
            <v-layout row wrap>
                <v-flex xs11 sm6 pa-2
                    v-for="closeup in closeups"
                    :key="closeup">
                    <img :src="closeup"
                        title="Closeup"
                        width="100%"
                        class="elevation-4"
                        @click.stop="showDialog(closeup)">
                </v-flex>
                <v-flex xs11 sm6 pa-2
                    v-for="overview in overviews"
                    :key="overview">
                    <img :src="overview"
                        title="Overview"
                        width="100%"
                        class="elevation-4"
                        @click.stop="showDialog(overview)">
                </v-flex>
                <v-dialog v-model="dialog">
                    <v-card>
                        <v-card-title class="headline">Zoomed</v-card-title>
                        <v-card-text>
                            <img :src="dialogImg"
                                title="Zoomed"
                                class="image--contain">
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" @click.native="dialog = false">Close</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-layout>
        </v-card-text>
        <v-card-actions v-if="!viewOnly">
            <v-spacer></v-spacer>
            <v-dialog v-model="dialogPhotos" persistent max-width="500px">
                <v-btn  slot="activator" flat color="primary">{{$t('action.new_photos')}}</v-btn>
                <v-card>
                    <v-card-title primary-title dark class="primary">
                        <span class="headline" v-t="'title.new_photos'"></span>
                    </v-card-title>
                    <v-card-text>
                        <v-textarea
                            :label="$t('examination.new_photos.reason')"
                            v-model="dialogPhotosReason"
                            required>
                        </v-textarea>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="primary darken-1" flat @click.native="dialogPhotos = false">{{$t('action.photos.cancel')}}</v-btn>
                        <v-btn color="primary" :disabled="!dialogPhotosReason" @click.native="requestNewImages">{{$t('action.photos.request')}}</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-card-actions>
    </v-card>
</template>

<script>
import presignedUrl from '@/s3Config.js';

export default {
    props: {
        value: {
            type: Object,
            required: true
        },
        phonenumber: {
            type: String,
            required: false
        },
        ssn: {
            type: String
        },
        childSsn: {
            type: String
        },
        viewOnly: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            dialogPhotos: false,
            dialogPhotosReason: null,
            dialog: false,
            dialogImg: '',
            closeups: [],
            overviews: [],
            idProofs: []
        };
    },
    computed: {
        examination () {
            this.value.closeups.forEach(element => {
                element.dialog = false;
            });
            this.value.overviews.forEach(element => {
                element.dialog = false;
            });
            return this.value;
        },
        pregnantLabel () {
            if (this.examination.pregnant) {
                return this.$t('examination.pregnant.' + this.examination.pregnant);
            } else {
                return this.$t('examination.pregnant.no');
            }
        },
        breastfeedingLabel () {
            if (this.examination.breastfeeding) {
                return this.$t('examination.breastfeeding.' + this.examination.breastfeeding);
            } else {
                return this.$t('examination.breastfeeding.no');
            }
        },
        whoLabel () {
            if (this.examination.who) {
                return this.$t('examination.who.' + this.examination.who);
            } else {
                return '';
            }
        },
        genderLabel () {
            if (this.examination.client.gender) {
                return this.$t('examination.gender.' + this.examination.client.gender);
            } else {
                return '';
            }
        },
        conditionDesc () {
            if (this.examination.body_part) {
                return 'Body part: ' + this.examination.body_part + '\n' + this.examination.other_description;
            } else {
                return this.examination.other_description;
            }
        },
        bodyPart () {
            try {
                if (this.examination.body_part) {
                    return JSON.parse(this.examination.body_part).map(item => {
                        if (item.custom) return item.custom;
                        else return item;
                    }).join(', ');
                } else {
                    return false;
                }
            } catch (err) {
                return this.examination.body_part;
            }
        },
        illness () {
            try {
                if (this.examination.other_description) {
                    return JSON.parse(this.examination.other_description).map(item => {
                        if (item.custom) return item.custom;
                        else return item;
                    }).join(', ');
                } else {
                    return false;
                }
            } catch (err) {
                return this.examination.other_description;
            }
        },
        pastTreatment () {
            if (this.examination.treatment === 'yes') {
                return this.examination.treatment_description;
            } else {
                return 'No';
            }
        },
        familyHistory () {
            if (this.examination.family_history === 'yes') {
                return this.examination.family_history_description;
            } else {
                return 'No';
            }
        },
        allergy () {
            if (this.examination.allergy === 'yes') {
                return this.examination.allergy_description;
            } else {
                return 'No';
            }
        },
        medication () {
            if (this.examination.medication === 'yes') {
                return this.examination.medication_description;
            } else {
                return 'No';
            }
        },
        name () {
            if (this.examination.client.firstname !== null && this.examination.client.lastname !== null) {
                return this.examination.client.firstname + ' ' + this.examination.client.lastname;
            } else if (this.examination.client.firstname !== null) {
                return this.examination.client.firstname;
            }
            return '';
        }
    },
    methods: {
        showDialog (url) {
            this.dialogImg = url;
            this.dialog = true;
        },
        requestNewImages () {
            this.$emit('requestNewImages', this.dialogPhotosReason);
            this.dialogPhotos = false;
            this.dialogPhotosReason = null;
        }
    },
    mounted () {
        if (this.examination && this.examination.closeups) {
            this.closeups = [];
            this.examination.closeups.forEach(async (closeup) => {
                let url = await presignedUrl(`${closeup.uuid}.${closeup.suffix}`, 'uploads/');
                this.closeups.push(url);
            });
        }
        if (this.examination && this.examination.overviews) {
            this.overviews = [];
            this.examination.overviews.forEach(async (overview) => {
                let url = await presignedUrl(`${overview.uuid}.${overview.suffix}`, 'uploads/');
                this.overviews.push(url);
            });
        }
        if (this.examination && this.examination.client.id_proof) {
            this.idProofs = [];
            this.examination.client.id_proof.forEach(async (idProofs) => {
                let url = await presignedUrl(`${idProofs.uuid}.${idProofs.suffix}`, 'uploads/');
                this.idProofs.push(url);
            });
        }
    }
};
</script>

<style lang="scss">
.image--contain {
    object-fit: contain;
    width: 100%;
}
.row.yes-no-question {
    .icon.material-icons {
        margin-right: 10px;
    }
    .input-group {
        padding-top: 0;
        input {
            padding: 0 10px;
        }
    }
}
</style>
