<template>
    <div>
    <v-card class="v-card--diagnosis elevation-2">
        <v-card-title primary-title dark class="primary">
            <h2 class="text-xs-center">
                {{$t('title.categorisation')}}
            </h2>
        </v-card-title>
        <v-card-text>
            <treeselect
                ref="treeselect"
                v-model="value.icd_codes"
                :async="true"
                :multiple="true"
                :default-options="value.icd_codes"
                :load-options="loadOptions"
                :normalizer="normalizer"
                :disabled="viewOnly"
                :clear-on-select="true"
                :close-on-select="true"
                :value-format="'object'"
                :placeholder="$t('diagnosis.categorisation_label')"
                :loading-text="$t('diagnosis.search_loader')"
                :no-results-text="$t('diagnosis.search_no_result')"
                :search-prompt-text="$t('diagnosis.search_promt_text')"
                :clear-all-text="$t('diagnosis.clear_all')"
                @select="clearSearchQuery"
                >
                <div slot="value-label" slot-scope="{ node }">{{ node.label }} ({{ node.id }})</div>
                <label slot="option-label" slot-scope="{ node, labelClassName }" :class="labelClassName">
                    {{ node.label }} ({{ node.id }})
                </label>
            </treeselect>
        </v-card-text>
        <v-card-actions v-if="this.shouldShowUpdatePrivateDescriptionButton()">
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="onUpdatePrivateClick">{{$t('action.update_private_description')}}</v-btn>
        </v-card-actions>
    </v-card>
    <br>
    <v-card class="v-card--diagnosis elevation-2">
        <v-card-title primary-title dark class="primary" :class="{'error': secondOpinion}">
            <h2 class="text-xs-center">
                {{$t('title.diagnosis')}}
                <span v-if="secondOpinion" class="second-opinion"> - {{$t('diagnosis.second_opinion')}}
                    <v-tooltip bottom close-delay="500" content-class="second-opinion--reason">
                        <v-icon slot="activator" dark>info</v-icon>
                        <span>{{secondOpinionReason}}</span>
                    </v-tooltip>
                </span>
                <v-spacer></v-spacer>
            </h2>
            <v-tooltip bottom>
                <v-icon class="interesting-marker" slot="activator" dark @click="markCaseAsInteresting">{{starredIcon}}</v-icon>
                <span>{{$t('diagnosis.interesting')}}</span>
            </v-tooltip>
            <v-tooltip bottom>
                <v-icon :color="colorClass" slot="activator" @click="togglePrescription">ballot</v-icon>
                <span>{{$t('diagnosis.prescription')}}</span>
            </v-tooltip>
        </v-card-title>
        <v-card-text>
            <v-textarea
                prepend-icon="short_text"
                :label="$t('diagnosis.description')"
                v-model="value.description"
                rows="10"
                :readonly="viewOnly">
            </v-textarea>
            <div v-if="isReferralUploaded">
                <v-chip v-for="(referral) in referrals" :key="referral.name">{{referral.name}}</v-chip>
            </div>
            <label>
                <div class="v-btn primary btn-min-width">
                    <v-icon class="attachment-marker">attachment</v-icon>
                </div>
                <input v-show="false" type="file" @change="handleFileChange" accept=".jpg,.jpeg,.png,.pdf" multiple/>
            </label>
                <v-chip v-for="(file, index) in files" :key="file.name" close @input=removeFile(index)>{{file.name}}</v-chip>
        </v-card-text>
        <v-card-actions v-if="!viewOnly">
            <v-spacer></v-spacer>
            <v-btn color="primary" :disabled="loading" :loading="loading" @click="onClick">{{$t('action.submit_exam')}}</v-btn>
        </v-card-actions>
    </v-card>
    </div>
</template>

<script>
import { Treeselect, ASYNC_SEARCH } from '@riophae/vue-treeselect';
import '@riophae/vue-treeselect/dist/vue-treeselect.css';

export default {
    props: {
        value: {
            type: Object,
            required: true
        },
        isCaseInteresting: {
            type: Boolean,
            default: false
        },
        secondOpinion: {
            type: Boolean,
            default: false
        },
        secondOpinionReason: {
            type: String,
            default: ''
        },
        viewOnly: {
            type: Boolean,
            default: false
        },
        referrals: {
            type: Array,
            required: false
        }
    },
    data () {
        return {
            files: [],
            isPrescribed: false,
            loading: false,
            normalizer (node) {
                return {
                    id: node.code,
                    label: node.name
                };
            }
        };
    },
    mounted () {
        this.isPrescribed = !!this.value.is_prescribed;
    },
    computed: {
        starredIcon () {
            return this.isCaseInteresting ? 'star' : 'star_border';
        },
        colorClass () {
            let color = this.isPrescribed ? '#fff' : '#696969';
            return color;
        },
        isReferralUploaded () {
            if (this.referrals && this.referrals.length > 0) {
                return true;
            }
            return false;
        }
    },
    methods: {
        async loadOptions ({ action, searchQuery, callback }) {
            if (action === ASYNC_SEARCH) {
                let response = await this.axios.post(`https://clinicaltables.nlm.nih.gov/api/icd10cm/v3/search?sf=name,code&terms=${searchQuery}`);
                const options = response.data[3].map(function (x) {
                    return {
                        code: x[0],
                        name: x[1]
                    };
                });
                callback(null, options);
            }
        },
        clearSearchQuery (node, instanceId) {
            this.$refs.treeselect.trigger.searchQuery = '';
        },
        shouldShowUpdatePrivateDescriptionButton () {
            if (this.viewOnly) {
                return false;
            } else {
                return this.value && this.value.uuid && this.value.icd_codes !== null && this.value.icd_codes.length > 0;
            }
        },
        onClick () {
            this.loading = true;
            if (this.files) {
                this.value.referrals = this.files;
            }
            // Send out an updated value
            this.$emit('value', this.value);
            // Send out that the user has clicked submit.
            this.$emit('submit');
        },
        onUpdatePrivateClick () {
            this.$emit('updatePrivate', this.value.icd_codes);
        },
        markCaseAsInteresting () {
            this.$emit('toggleCaseAsInteresting');
        },
        handleFileChange (e) {
            let selectedFiles = e.target.files;
            if (!selectedFiles.length) {
                return false;
            }
            for (let i = 0; i < selectedFiles.length; i++) {
                const found = this.files.some(el => el.name === selectedFiles[i].name);
                if (!found) {
                    this.files.push(selectedFiles[i]);
                }
            }
        },
        removeFile (index) {
            this.files.splice(index, 1);
        },
        togglePrescription () {
            this.isPrescribed = !this.isPrescribed;
            this.value.is_prescribed = this.isPrescribed;
        }
    },
    components: {
        Treeselect
    }
};
</script>

<style lang="scss">
.v-card--diagnosis {
    .second-opinion {
        font-size: 0.8em;
    }

    .interesting-marker {
        padding: 0 15px;
    }

    .attachment-marker {
        transform: rotate(135deg) scaleX(-1);
        color: white !important;
    }

    .btn-min-width {
        min-width: 35px;
    }
}
.v-tooltip__content.second-opinion--reason {
    font-size: 1.1em;
    padding: 15px;
    border: 1px solid darkgrey;
    background: white;
    color: black;
}

</style>
