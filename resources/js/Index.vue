<template>
    <LoadingView
        :loading="initialLoading"
        :dusk="resourceName + '-index-component'"
        :data-relationship="viaRelationship"
    >
        <template v-if="shouldOverrideMeta && resourceInformation">
            <Head :title="__(`${resourceInformation.label}`)"/>
        </template>

        <Cards
            v-if="shouldShowCards"
            :cards="cards"
            :resource-name="resourceName"
        />

        <Heading
            :level="1"
            class="mb-3 flex items-center"
            :class="{ 'mt-6': shouldShowCards && cards.length > 0 }"
            dusk="index-heading"
        >
            <span v-html="headingTitle"/>

        </Heading>

        <template v-if="!shouldBeCollapsed">
            <div class="flex gap-2 mb-6">
                <IndexSearchInput
                    v-if="resourceInformation && resourceInformation.searchable"
                    :searchable="resourceInformation && resourceInformation.searchable"
                    v-model:keyword="search"
                    @update:keyword="search = $event"
                />

                <div
                    v-if="
            availableStandaloneActions.length > 0 ||
            authorizedToCreate ||
            authorizedToRelate
          "
                    class="inline-flex items-center gap-2 ml-auto"
                >
                    <!-- Action Dropdown -->
                    <ButtonInertiaLink
                        v-if="totalPages > 1"
                        class="link-default  items-center font-bold"
                        dusk="create-button"
                        :href="$url(`/resources/${viaResource}/${viaResourceId}`)"
                    >
                        {{ __(`All ${resourceInformation.label}`) }}
                    </ButtonInertiaLink>


                    <CreateRelationButton
                        v-if="authorizedToCreate"
                        :authorized-to-create="authorizedToCreate"
                        :label="createButtonLabel"
                        :singular-name="singularName"
                        @click="openRelationModal"
                        class="ml-2"
                        :dusk="`${field.attribute}-inline-create`"
                    />
                    <CustomCreateRelationModal
                        :size="size"
                        :show="relationModalOpen"
                        @set-resource="handleSetResource"
                        @create-cancelled="closeRelationModal"
                        :resource-name="resourceName"
                        :resource-id="viaResourceId"
                        :via-relationship="viaRelationship"
                        :via-resource="viaResource"
                        :relationship-type="relationshipType"
                        :authorized-to-create="authorizedToCreate"
                        :authorized-to-relate="authorizedToRelate"
                        :via-resource-id="viaResourceId"

                    />

                    <!-- Create / Attach Button -->
                    <!--          <CreateResourceButton
                                :label="createButtonLabel"
                                :singular-name="singularName"
                                :resource-name="resourceName"
                                :via-resource="viaResource"
                                :via-resource-id="viaResourceId"
                                :via-relationship="viaRelationship"
                                :relationship-type="relationshipType"
                                :authorized-to-create="authorizedToCreate"
                                :authorized-to-relate="authorizedToRelate"
                                class="shrink-0"
                              />-->
                </div>
            </div>

            <Card>


                <LoadingView
                    :loading="loading"
                    :variant="!resourceResponse ? 'default' : 'overlay'"
                >
                    <IndexErrorDialog
                        v-if="resourceResponseError != null"
                        :resource="resourceInformation"
                        @click="getResources"
                    />

                    <template v-else>

                        <IndexEmptyDialog
                            v-if="!loading && !resources.length"
                            :create-button-label="createButtonLabel"
                            :singular-name="singularName"
                            :resource-name="resourceName"
                            :via-resource="viaResource"
                            :via-resource-id="viaResourceId"
                            :via-relationship="viaRelationship"
                            :relationship-type="relationshipType"
                            :authorized-to-create="false"
                            :authorized-to-relate="authorizedToRelate"
                        />
                        <div
                            v-if="!loading && !resources.length"
                            class="flex justify-center items-center">
                            <CreateRelationButton
                                v-if="authorizedToCreate"
                                :authorized-to-create="authorizedToCreate"
                                :label="createButtonLabel"
                                :singular-name="singularName"
                                @click="openRelationModal"
                                class="ml-2"
                                :dusk="`${field.attribute}-inline-create`"
                            />
                        </div>
                        <ResourceTable
                            :authorized-to-relate="authorizedToRelate"
                            :resource-name="resourceName"
                            :resources="resources"
                            :singular-name="singularName"
                            :selected-resources="selectedResources"
                            :selected-resource-ids="selectedResourceIds"
                            :actions-are-available="allActions.length > 0"
                            :should-show-checkboxes="false"
                            :via-resource="viaResource"
                            :via-resource-id="viaResourceId"
                            :via-relationship="viaRelationship"
                            :relationship-type="relationshipType"
                            :update-selection-status="updateSelectionStatus"
                            :sortable="sortable"
                            @order="orderByField"
                            @reset-order-by="resetOrderBy"
                            @delete="deleteResources"
                            @restore="restoreResources"
                            @actionExecuted="handleActionExecuted"
                            ref="resourceTable"
                        />


                        <!--                        <ResourcePagination
                                                    v-if="shouldShowPagination"
                                                    :pagination-component="paginationComponent"
                                                    :has-next-page="hasNextPage"
                                                    :has-previous-page="hasPreviousPage"
                                                    :load-more="loadMore"
                                                    :select-page="selectPage"
                                                    :total-pages="totalPages"
                                                    :current-page="currentPage"
                                                    :per-page="perPage"
                                                    :resource-count-label="resourceCountLabel"
                                                    :current-resource-count="currentResourceCount"
                                                    :all-matching-resource-count="allMatchingResourceCount"
                                                />-->

                        <div class="flex justify-center items-center">
                            <ButtonInertiaLink
                                v-if="totalPages > 1"
                                class="link-default  items-center font-bold"
                                dusk="create-button"
                                :href="$url(`/resources/${viaResource}/${viaResourceId}`)"
                            >
                                {{ __(`All ${resourceInformation.label}`) }}
                            </ButtonInertiaLink>
                        </div>


                        <!--                        <button

                                                    @click.stop
                                                    v-if="totalPages > 1"
                                                    class="link-default"
                                                    @click="visitRelation"
                                                >

                                                    {{ __(`All ${resourceInformation.label}`) }}
                                                </button>-->
                    </template>
                </LoadingView>
            </Card>
        </template>
    </LoadingView>
</template>

<script>
// this.$refs.selectControl.selectedIndex = 0
import {CancelToken, isCancel} from 'axios'
import {
    HasCards,
    Paginatable,
    PerPageable,
    Deletable,
    Collapsable,
    LoadsResources,
    IndexConcerns,
    InteractsWithResourceInformation,
    InteractsWithQueryString,
    SupportsPolling,
} from '@/mixins'
import {minimum} from '@/util'
import {mapActions} from 'vuex'

export default {
    name: 'ResourceIndexModal',

    mixins: [
        Collapsable,
        Deletable,
        HasCards,
        Paginatable,
        PerPageable,
        LoadsResources,
        IndexConcerns,
        InteractsWithResourceInformation,
        InteractsWithQueryString,
        SupportsPolling,
    ],

    props: {
        size: {type: String, default: '2xl'},
        shouldOverrideMeta: {
            type: Boolean,
            default: false,
        },

        shouldEnableShortcut: {
            type: Boolean,
            default: false,
        },
    },

    data: () => ({
        lenses: [],
        sortable: true,
        actionCanceller: null,


        selectedResource: null,
        selectedResourceId: null,
        relationModalOpen: false,
    }),

    /*    mounted() {
            this.currentPage = 1
            this.currentPageLoadMore = 1

        },*/
    /**
     * Mount the component and retrieve its initial data.
     */
    async created() {

        if (!this.resourceInformation) return

        // Bind the keydown event listener when the router is visited if this
        // component is not a relation on a Detail page
        if (this.shouldEnableShortcut === true) {
            Nova.addShortcut('c', this.handleKeydown)
            Nova.addShortcut('mod+a', this.toggleSelectAll)
            Nova.addShortcut('mod+shift+a', this.toggleSelectAllMatching)

        }

        this.getLenses()

        Nova.$on('refresh-resources', this.getResources)

        if (this.actionCanceller !== null) this.actionCanceller()
    },

    /**
     * Unbind the keydown even listener when the before component is destroyed
     */
    beforeUnmount() {
        if (this.shouldEnableShortcut) {
            Nova.disableShortcut('c')
            Nova.disableShortcut('mod+a')
            Nova.disableShortcut('mod+shift+a')
        }

        Nova.$off('refresh-resources', this.getResources)

        if (this.actionCanceller !== null) this.actionCanceller()
    },

    methods: {
        ...mapActions(['fetchPolicies']),
        handleSetResource({id}) {
            this.closeRelationModal()
            this.selectedResourceId = id
            this.initializingWithExistingResource = true

            this.getResources()
        },
        openRelationModal() {
            Nova.$emit('create-relation-modal-opened')
            this.relationModalOpen = true
        },

        closeRelationModal() {
            this.relationModalOpen = false
            Nova.$emit('create-relation-modal-closed')
        },
        visitRelation() {
            Nova.visit(`/resources/${this.viaResource}/${this.viaResourceId}`)
        },
        /**
         * Handle the keydown event
         */
        handleKeydown(e) {
            // `c`
            if (
                this.authorizedToCreate &&
                e.target.tagName !== 'INPUT' &&
                e.target.tagName !== 'TEXTAREA' &&
                e.target.contentEditable !== 'true'
            ) {
                Nova.visit(`/resources/${this.resourceName}/new`)
            }
        },

        /**
         * Get the resources based on the current page, search, filters, etc.
         */
        getResources() {
            if (this.shouldBeCollapsed) {
                this.loading = false
                return
            }

            this.loading = true
            this.resourceResponseError = null

            this.$nextTick(() => {
                this.clearResourceSelections()

                return minimum(
                    Nova.request().get('/nova-api/' + this.resourceName, {
                        params: this.resourceRequestQueryString,
                        cancelToken: new CancelToken(canceller => {
                            this.canceller = canceller
                        }),
                    }),
                    300
                )
                    .then(({data}) => {
                        this.resources = []

                        this.resourceResponse = data
                        this.resources = data.resources
                        this.softDeletes = data.softDeletes
                        this.perPage = data.per_page
                        this.sortable = data.sortable

                        this.handleResourcesLoaded()
                    })
                    .catch(e => {
                        if (isCancel(e)) {
                            return
                        }

                        this.loading = false
                        this.resourceResponseError = e

                        throw e
                    })
            })
        },

        /**
         * Get the relatable authorization status for the resource.
         */
        getAuthorizationToRelate() {
            if (
                this.shouldBeCollapsed ||
                (!this.authorizedToCreate &&
                    this.relationshipType !== 'belongsToMany' &&
                    this.relationshipType !== 'morphToMany')
            ) {
                return
            }

            if (!this.viaResource) {
                return (this.authorizedToRelate = true)
            }

            return Nova.request()
                .get(
                    '/nova-api/' +
                    this.resourceName +
                    '/relate-authorization' +
                    '?viaResource=' +
                    this.viaResource +
                    '&viaResourceId=' +
                    this.viaResourceId +
                    '&viaRelationship=' +
                    this.viaRelationship +
                    '&relationshipType=' +
                    this.relationshipType
                )
                .then(response => {
                    this.authorizedToRelate = response.data.authorized
                })
        },

        /**
         * Get the lenses available for the current resource.
         */
        getLenses() {
            this.lenses = []

            if (this.viaResource) {
                return
            }

            return Nova.request()
                .get('/nova-api/' + this.resourceName + '/lenses')
                .then(response => {
                    this.lenses = response.data
                })
        },

        /**
         * Get the actions available for the current resource.
         */
        getActions() {
            if (this.actionCanceller !== null) this.actionCanceller()

            this.actions = []
            this.pivotActions = null

            if (this.shouldBeCollapsed) {
                return
            }

            return Nova.request()
                .get(`/nova-api/${this.resourceName}/actions`, {
                    params: {
                        viaResource: this.viaResource,
                        viaResourceId: this.viaResourceId,
                        viaRelationship: this.viaRelationship,
                        relationshipType: this.relationshipType,
                        display: 'index',
                        resources: this.selectAllMatchingChecked
                            ? 'all'
                            : this.selectedResourceIds,
                        pivots: !this.selectAllMatchingChecked
                            ? this.selectedPivotIds
                            : null,
                    },
                    cancelToken: new CancelToken(canceller => {
                        this.actionCanceller = canceller
                    }),
                })
                .then(response => {
                    this.actions = response.data.actions
                    this.pivotActions = response.data.pivotActions
                    this.resourceHasActions = response.data.counts.resource > 0
                })
                .catch(e => {
                    if (isCancel(e)) {
                        return
                    }

                    throw e
                })
        },

        /**
         * Get the count of all of the matching resources.
         */
        getAllMatchingResourceCount() {
            Nova.request()
                .get('/nova-api/' + this.resourceName + '/count', {
                    params: this.resourceRequestQueryString,
                })
                .then(response => {
                    this.allMatchingResourceCount = response.data.count
                })
        },

        /**
         * Load more resources.
         */
        loadMore() {
            if (this.currentPageLoadMore === null) {
                this.currentPageLoadMore = this.currentPage
            }

            this.currentPageLoadMore = this.currentPageLoadMore + 1

            return minimum(
                Nova.request().get('/nova-api/' + this.resourceName, {
                    params: {
                        ...this.resourceRequestQueryString,
                        page: this.currentPageLoadMore, // We do this to override whatever page number is in the URL
                    },
                }),
                300
            ).then(({data}) => {
                this.resourceResponse = data
                this.resources = [...this.resources, ...data.resources]

                if (data.total !== null) {
                    this.allMatchingResourceCount = data.total
                } else {
                    this.getAllMatchingResourceCount()
                }

                Nova.$emit('resources-loaded', {
                    resourceName: this.resourceName,
                    mode: this.isRelation ? 'related' : 'index',
                })
            })
        },

        async handleCollapsableChange() {
            this.loading = true

            this.toggleCollapse()

            if (!this.collapsed) {
                if (!this.filterHasLoaded) {
                    await this.initializeFilters(null)
                    if (!this.hasFilters) {
                        await this.getResources()
                    }
                } else {
                    await this.getResources()
                }

                await this.getAuthorizationToRelate()
                await this.getActions()
                this.restartPolling()
            } else {
                this.loading = false
            }
        },
    },

    computed: {
        actionQueryString() {
            return {
                currentSearch: this.currentSearch,
                encodedFilters: this.encodedFilters,
                currentTrashed: this.currentTrashed,
                viaResource: this.viaResource,
                viaResourceId: this.viaResourceId,
                viaRelationship: this.viaRelationship,
            }
        },

        /**
         * Determine if the index view should be collapsed.
         */
        shouldBeCollapsed() {
            return this.collapsed && this.viaRelationship != null
        },

        collapsedByDefault() {
            return this.field?.collapsedByDefault ?? false
        },

        /**
         * Get the endpoint for this resource's metrics.
         */
        cardsEndpoint() {
            return `/nova-api/${this.resourceName}/cards`
        },

        /**
         * Build the resource request query string.
         */
        resourceRequestQueryString() {
            return {
                search: this.currentSearch,
                filters: this.encodedFilters,
                orderBy: this.currentOrderBy,
                orderByDirection: this.currentOrderByDirection,
                perPage: this.currentPerPage,
                trashed: this.currentTrashed,
                page: this.currentPage,
                viaResource: this.viaResource,
                viaResourceId: this.viaResourceId,
                viaRelationship: this.viaRelationship,
                viaResourceRelationship: this.viaResourceRelationship,
                relationshipType: this.relationshipType,
            }
        },

        /**
         * Determine whether the user is authorized to perform actions on the delete menu
         */
        canShowDeleteMenu() {
            return Boolean(
                this.authorizedToDeleteSelectedResources ||
                this.authorizedToForceDeleteSelectedResources ||
                this.authorizedToRestoreSelectedResources ||
                this.selectAllMatchingChecked
            )
        },

        /**
         * Return the heading for the view
         */
        headingTitle() {
            if (this.initialLoading) {
                return '&nbsp;'
            } else {
                if (this.isRelation && this.field) {
                    return this.field.name
                } else {
                    if (this.resourceResponse !== null) {
                        return this.resourceResponse.label
                    } else {
                        return this.resourceInformation.label
                    }
                }
            }
        },
    },
}
</script>
