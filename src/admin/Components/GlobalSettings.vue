<template>
    <div class="ff_pdf_wrap">
        <el-skeleton :loading="loading" animated :rows="10" :class="loading ? 'ff_card' : ''">
            <card>
                <card-head>
                    <h2 class="title">Global PDF Settings</h2>
                    <p class="text">This global settings will be set as default for your new PDF feed for any form.Then
                        you can customize for a specific PDF generator feed</p>
                </card-head>
                <card-body>
                    <el-form class="ff_pdf_form_wrap" label-position="top">
                        <field-mapper
                            v-if="!loading"
                            :fields="fields"
                            :errors="errors"
                            :settings="settings"
                        />
                    </el-form>
                </card-body>
            </card>
            <div>
                <el-button
                    type="primary"
                    icon="el-icon-success"
                    @click="save"
                >
                    Save Settings
                </el-button>
            </div>
        </el-skeleton>
    </div>
</template>


<script type="text/babel">
import FieldMapper from "./Inputs/FieldMapper.vue";
import Card from './Card/Card.vue';
import CardBody from './Card/CardBody.vue';
import CardHead from './Card/CardHead.vue';
import Errors from "./Inputs/Common/Errors.js";
import {ElMessage} from 'element-plus'

export default {
    name: "GlobalSettings",
    props: ["app"],
    components: {
        FieldMapper,
        Card,
        CardHead,
        CardBody,
        ElMessage
    },
    data() {
        return {
            loading: false,
            settings: {},
            fields: {},
            errors: new Errors()
        };
    },
    methods: {
        save() {
            this.saving = true;
            this.$post('', {
                action: 'fluent_pdf_admin_ajax_actions',
                route: 'save_global_settings',
                settings: this.settings
            })
                .then(response => {
                    ElMessage({
                        showClose: true,
                        message: 'Settings saved successfully.',
                        type: 'success',
                    })
                })
                .fail(e => {
                    console.log(e);
                })
                .always(() => {
                    this.saving = false;
                });
        },
        getGlobalPdfSettings() {
            this.loading = true;
            this.$get('',
                {
                    action: 'fluent_pdf_admin_ajax_actions',
                    route: 'get_global_settings'
                })
                .then(response => {
                    this.settings = response.data.settings;
                    this.fields = response.data.fields;
                })
                .fail(e => {
                    this.$fail('Global settings fetch error, please reload.');
                })
                .always(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.getGlobalPdfSettings();
    }
};
</script>
