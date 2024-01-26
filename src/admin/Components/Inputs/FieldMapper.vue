<template>
    <div class="ff_field_manager" v-for="field in fields" :key="field.key">
        <el-form-item :required="field.required" :label="field.label">
            <template slot="default">
                {{field.label}}
                <el-tooltip
                    v-if="field.tips"
                    class="item"
                    placement="bottom-start"
                    popper-class="ff_tooltip_wrap"
                >
                    <div slot="content">
                        <p v-html="field.tips"></p>
                    </div>
                    <i class="ff-icon ff-icon-info-filled text-primary"></i>
                </el-tooltip>
            </template>

            <template v-if="field.component == 'text'" >
                <el-input :placeholder="field.placeholder" v-model="settings[field.key]" :readonly="field.readonly"></el-input>
            </template>

            <template v-else-if="field.component == 'number'">
                <el-input-number v-model="settings[field.key]"></el-input-number>
            </template>

            <template v-else-if="field.component == 'radio_choice'">
                <el-radio-group v-model="settings[field.key]">
                    <el-radio
                        v-for="(fieldLabel, fieldValue) in field.options"
                        :key="fieldValue"
                        :label="fieldValue"
                    >{{fieldLabel}}</el-radio>
                </el-radio-group>
            </template>

            <template v-else-if="field.component == 'dropdown'">
                <el-select v-model="settings[field.key]" :placeholder="field.placeholder">
                    <el-option
                        v-for="(item,itemValue) in field.options"
                        :key="itemValue"
                        :label="item"
                        :value="itemValue">
                    </el-option>
                </el-select>
            </template>

            <template v-else-if="field.component == 'dropdown-group'">
                <el-select v-model="settings[field.key]" :placeholder="field.placeholder">
                    <el-option-group 
                        v-for="(group,groupLabel) in field.options"
                        :key="groupLabel"
                        :label="groupLabel">
                        <el-option
                            v-for="(item,itemValue) in group"
                            :key="itemValue"
                            :label="item"
                            :value="itemValue">
                        </el-option>
                    </el-option-group>
                </el-select>
            </template>

            <template v-else-if="field.component == 'color_picker'">
                <el-color-picker v-model="settings[field.key]" />
            </template>

            <template v-else-if="field.component == 'checkbox-single'">
                <el-checkbox v-model="settings[field.key]">
                    {{field.checkbox_label}}
                </el-checkbox>
            </template>

            <template v-else-if="field.component == 'checkbox-multiple'">
                <el-checkbox-group v-model="settings[field.key]">
                    <el-checkbox
                        v-for="(fieldLabel, fieldValue) in field.options"
                        :key="fieldValue"
                        :label="fieldValue"
                    >{{fieldLabel}}</el-checkbox>
                </el-checkbox-group>
            </template>

            <template v-else>
                <p>Invalid Vue Element</p>
                <pre>{{field}}</pre>
            </template>

            <p class="mt-2 text-note" v-if="field.inline_tip" v-html="field.inline_tip"></p>
            <error-view :field="field.key" :errors="errors"></error-view>
        </el-form-item>
    </div>
</template>

<script type="text/babel">
    import ErrorView from './Common/errorView.vue';

    export default {
        name: 'FieldManager',
        props: ['fields', 'errors', 'settings'],
        components: {
            ErrorView
        },
        computed: {
            htmlBodyeditorShortcodes() {
                const freshCopy = _ff.cloneDeep(this.editorShortcodes);
                if (freshCopy && freshCopy.length) {
                    freshCopy[0].shortcodes = {
                        ...freshCopy[0].shortcodes,
                        '{all_data}': 'All Data',
                        '{all_data_without_hidden_fields}' : 'All Data Without Hidden Fields'
                    };
                }
                return freshCopy;
            }
        }
    }
</script>

<style>
.ff_pdf_form_wrap {
    display: flex;
    width: 100%;
    flex-wrap: wrap;
}

.ff_field_manager {
    min-width: 50% !important;
}

.ff_pdf_wrap {
    width: 600px;
    width: 600px;
    margin: 10px auto;
    border: 1px solid #ccc;
    padding: 23px;
    border-radius: 8px;
}
</style>
