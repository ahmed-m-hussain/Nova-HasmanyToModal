<template>
    <div>
        <button

            @click.stop
            :class="addClass"
            @click="showModal = true;"
        >

            {{ field.value.totalRelationship }} {{ field.value.title }}
        </button>



        <Modal
            :show="showModal"
            @close-via-escape="$emit('close')"
            role="alertdialog"
            :size="field.modalSize"
            :use-focus-trap="false"

        >

            <form
                slot-scope="props"
                class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden"
                style="width: 90%"
            >
                <slot :uppercaseMode="uppercaseMode" :mode="mode">
                    <div class="p-8">

                        <ResourceIndexModal
                            :size="field.modalSize"
                            :field="field"
                            :resource-name="field.resourceName"
                            :via-resource="resourceName"
                            :via-resource-id="field.value.resourceId"
                            :via-relationship="field.hasManyRelationship"
                            :relationship-type="'hasMany'"
                            @actionExecuted="actionExecuted"
                            :load-cards="false"
                            :initialPerPage="field.perPage || 10"
                            :should-override-meta="false"
                            :current-page="1"


                        />
                    </div>
                </slot>

                <div class="bg-30 px-6 py-3 flex">
                    <div class="ml-auto">
                        <button
                            type="button"
                            data-testid="cancel-button"
                            dusk="cancel-delete-button"
                            @click.prevent="handleClose"
                            class="btn text-80 font-normal h-9 px-3 mr-3 btn-link"
                        >
                            {{ __('Cancel') }}
                        </button>

                    </div>
                </div>
            </form>
        </Modal>


    </div>
</template>

<script>
import find from 'lodash/find'
import isNil from 'lodash/isNil'
import {storage, DependentFormField, HandlesValidationErrors} from 'laravel-nova'


export default {
    mixins: [
        DependentFormField,
        HandlesValidationErrors,

    ],

    props: ['resourceName', 'resourceId', 'resource', 'field'],

    data: () => ({
        availableResources: [],
        initializingWithExistingResource: false,
        createdViaRelationModal: false,
        selectedResource: null,
        selectedResourceId: null,
        softDeletes: false,
        withTrashed: false,
        search: '',
        relationModalOpen: false,
        showModal: false,
        showModalCreate: false,
        addClass: 'link-default ',
        showCreateForm: false,

    }),

    /**
     * Mount the component.
     */
    mounted() {

        this.initializeComponent()
    },

    methods: {
        handleCreateCancelled() {
            return this.$emit('create-cancelled')
        },
        handleRefresh(data) {
            this.$emit('set-resource', data)
        },

        handleClose() {

            this.showModal = false;
        },

        initializeComponent() {
            this.withTrashed = false

            this.selectedResourceId = this.currentField.value

            if (this.viaRelatedResource) {
                // If the user is creating this resource via a related resource's index
                // page we'll have a viaResource and viaResourceId in the params and
                // should prefill the resource in this field with that information
                this.initializingWithExistingResource = true
                this.selectedResourceId = this.viaResourceId
            }


            this.field.fill = this.fill
        },

        /**
         * Select a resource using the <select> control
         */
        selectResourceFromSelectControl(value) {
            this.selectedResourceId = value
            this.selectInitialResource()

            if (this.field) {
                this.emitFieldValueChange(this.fieldAttribute, this.selectedResourceId)
            }
        },

        /**
         * Fill the forms formData with details from this field
         */
        fill(formData) {
            this.fillIfVisible(
                formData,
                this.fieldAttribute,
                this.selectedResource ? this.selectedResource.value : ''
            )
            this.fillIfVisible(
                formData,
                `${this.fieldAttribute}_trashed`,
                this.withTrashed
            )
        },

        /**
         * Get the resources that may be related to this resource.
         */
        getAvailableResources() {
            Nova.$progress.start()

            return storage
                .fetchAvailableResources(this.resourceName, this.fieldAttribute, {
                    params: this.queryParams,
                })
                .then(({data: {resources, softDeletes, withTrashed}}) => {
                    Nova.$progress.done()

                    if (this.initializingWithExistingResource || !this.isSearchable) {
                        this.withTrashed = withTrashed
                    }

                    if (this.viaRelatedResource) {
                        let selectedResource = find(resources, r =>
                            this.isSelectedResourceId(r.value)
                        )

                        if (
                            isNil(selectedResource) &&
                            !this.shouldIgnoresViaRelatedResource
                        ) {
                            return Nova.visit('/404')
                        }
                    }

                    // Turn off initializing the existing resource after the first time
                    if (this.useSearchInput) {
                        this.initializingWithExistingResource = false
                    }
                    this.availableResources = resources
                    this.softDeletes = softDeletes
                })
                .catch(e => {
                    Nova.$progress.done()
                })
        },


        /**
         * Determine if the given value is numeric.
         */
        isNumeric(value) {
            return !isNaN(parseFloat(value)) && isFinite(value)
        },

        /**
         * Select the initial selected resource
         */
        selectInitialResource() {
            this.selectedResource = find(this.availableResources, r =>
                this.isSelectedResourceId(r.value)
            )
        },

        /**
         * Toggle the trashed state of the search
         */
        toggleWithTrashed() {
            // Reload the data if the component doesn't have selected resource
            if (!filled(this.selectedResource)) {
                this.withTrashed = !this.withTrashed

                if (!this.useSearchInput) {
                    this.getAvailableResources()
                }
            }
        },

        openRelationModal() {
            Nova.$emit('create-relation-modal-opened')
            this.relationModalOpen = true
        },

        closeRelationModal() {
            this.relationModalOpen = false
            Nova.$emit('create-relation-modal-closed')
        },

        handleSetResource({id}) {
            this.closeRelationModal()
            this.selectedResourceId = id
            this.initializingWithExistingResource = true
            this.createdViaRelationModal = true
            this.getAvailableResources().then(() => {
                this.selectInitialResource()

                this.emitFieldValueChange(this.fieldAttribute, this.selectedResourceId)
            })
        },

        performResourceSearch(search) {
            if (this.useSearchInput) {
                this.performSearch(search)
            } else {
                this.search = search
            }
        },

        clearResourceSelection() {
            this.clearSelection()

            if (this.viaRelatedResource && !this.createdViaRelationModal) {
                this.updateQueryString({
                    viaResource: null,
                    viaResourceId: null,
                    viaRelationship: null,
                    relationshipType: null,
                }).then(() => {
                    Nova.$router.reload({
                        onSuccess: () => {
                            this.initializingWithExistingResource = false
                            this.initializeComponent()
                        },
                    })
                })
            } else {
                if (this.createdViaRelationModal) {
                    this.createdViaRelationModal = false
                    this.initializingWithExistingResource = false
                }

                this.getAvailableResources()
            }
        },

        onSyncedField() {
            if (this.viaRelatedResource) {
                return
            }

            this.initializeComponent()

            if (isNil(this.syncedField.value) && isNil(this.selectedResourceId)) {
                this.selectInitialResource()
            }
        },

        emitOnSyncedFieldValueChange() {
            if (this.viaRelatedResource) {
                return
            }

            this.emitFieldValueChange(this.fieldAttribute, this.selectedResourceId)
        },

        syncedFieldValueHasNotChanged() {
            return this.isSelectedResourceId(this.currentField.value)
        },

        isSelectedResourceId(value) {
            return (
                !isNil(value) &&
                value?.toString() === this.selectedResourceId?.toString()
            )
        },
    },

    computed: {
        /**
         * Determine if we are editing and existing resource
         */

        /**
         * Determine if we are creating a new resource via a parent relation
         */
        viaRelatedResource() {
            return Boolean(
                this.viaResource === this.field.resourceName &&
                this.field.reverse &&
                this.viaResourceId
            )
        },

        /**
         * Determine if we should select an initial resource when mounting this field
         */
        shouldSelectInitialResource() {
            return Boolean(
                this.editingExistingResource ||
                this.viaRelatedResource ||
                this.currentField.value
            )
        },

        /**
         * Determine if the related resources is searchable
         */
        isSearchable() {
            return Boolean(this.currentField.searchable)
        },

        /**
         * Get the query params for getting available resources
         */
        queryParams() {
            return {
                current: this.selectedResourceId,
                first: this.shouldLoadFirstResource,
                search: this.search,
                withTrashed: this.withTrashed,
                resourceId: this.resourceId,
                viaResource: this.viaResource,
                viaResourceId: this.viaResourceId,
                viaRelationship: this.viaRelationship,
                component: this.field.dependentComponentKey,
                dependsOn: this.encodedDependentFieldValues,
                editing: true,
                editMode:
                    isNil(this.resourceId) || this.resourceId === ''
                        ? 'create'
                        : 'update',
            }
        },

        shouldLoadFirstResource() {
            return (
                (this.initializingWithExistingResource &&
                    !this.shouldIgnoresViaRelatedResource) ||
                Boolean(this.currentlyIsReadonly && this.selectedResourceId)
            )
        },

        shouldShowTrashed() {
            return (
                this.softDeletes &&
                !this.viaRelatedResource &&
                !this.currentlyIsReadonly &&
                this.currentField.displaysWithTrashed
            )
        },

        authorizedToCreate() {
            return find(Nova.config('resources'), resource => {
                return resource.uriKey === this.field.resourceName
            }).authorizedToCreate
        },

        canShowNewRelationModal() {
            return (
                this.currentField.showCreateRelationButton &&
                !this.shownViaNewRelationModal &&
                !this.viaRelatedResource &&
                !this.currentlyIsReadonly &&
                this.authorizedToCreate
            )
        },

        /**
         * Return the placeholder text for the field.
         */
        placeholder() {
            return this.currentField.placeholder || this.__('—')
        },

        /**
         * Return the field options filtered by the search string.
         */
        filteredResources() {
            if (!this.isSearchable) {
                return this.availableResources.filter(option => {
                    return (
                        option.display.toLowerCase().indexOf(this.search.toLowerCase()) >
                        -1 || new String(option.value).indexOf(this.search) > -1
                    )
                })
            }

            return this.availableResources
        },

        shouldIgnoresViaRelatedResource() {
            return this.viaRelatedResource && filled(this.search)
        },

        useSearchInput() {
            return this.isSearchable || this.viaRelatedResource
        },
    },
}
</script>
