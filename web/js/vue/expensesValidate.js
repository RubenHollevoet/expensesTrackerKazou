Vue.component('groupstack-item', {
    props: ['group'],
    template: `<li v-bind:data-id="group.id"><span class="btn btn-info fa fa-check" v-on:click="loadExpenses"></span> {{ group.name }} ({{ group.tripCount }})
                    <groupstack-item
                        v-for="group in this.$root.groupStack"
                        v-bind:group="group"
                        v-bind:key="group.id">
                    </groupstack-item>
                </li>`,
    methods: {
        loadExpenses: function (evt) {
            console.log('todo: load expenses');
            // var newArrayLength = $(evt.currentTarget.parentElement).index();
            // this.$root.groupStack = this.$root.groupStack.slice(0, newArrayLength);
            //
            // var prevParentId = this.$root.groupStack[newArrayLength - 1] ? this.$root.groupStack[newArrayLength - 1].id : 0;
            // this.$root.fetchGroups(prevParentId);
        }
    }
});

// define the item component
Vue.component('tree-item', {
    template: '#item-template',
    props: {
        model: Object,

    },
    data: function () {
        return {
            open: false,
            inChecklist: false
        }
    },
    computed: {
        isFolder: function () {
            return this.model.children;
        },
        getTripCount: function () {
            return this.model.tripCount;
        }
    },
    methods: {
        toggle: function () {
            if (this.model.children !== []) {
                this.open = !this.open
            }
        },
        fetchTrips: function () {
            var groupId = this.model.id;
            var self = this;
            axios.get('/api/getExpenses?group=' + groupId + '&denied=' + this.$root.tripStatusFilter[0] + '&awaiting=' + this.$root.tripStatusFilter[1] + '&approved=' + this.$root.tripStatusFilter[2] + '&sorting=' + this.$root.sorting)
                .then(function (response) {
                    if(response.data.status === 'ok') {
                        self.inChecklist = true;
                        self.$root.tripGroups.push(response.data.data);
                        console.log('reques finished: ', self.$root.tripGroups);
                    }
                    else {
                        alert('error fetching trips. Check console log');
                        console.log(response.data.status);
                    }
                })
                .catch(function (error) {
                    self.fetchError = error;
                })
        }
    },
    mounted: function () {
        // console.log(this.model);
    }
});

Vue.component('groupsavaliable-item', {
    props: ['group'],
    template: '<span class="btn btn-default" v-bind:data-id="group.id" v-on:click="loadNewGroups" v-bind:class="[this.group.tripsAwaitingConfirmation === 0? \'btn-primary\' : \'btn-default\']">{{ getName() }} ({{ this.group.tripsAwaitingConfirmation }})</span>',
    methods: {
        loadNewGroups: function (evt) {
            this.$root.groupStack.push({id: this.group.id, text: this.group.name});
            // this.$root.fetchGroups(evt.currentTarget.dataset.id);
            this.$root.activeGroups = [];
        },
        getName: function () {
            var name = this.group.name;
            if (this.group.startDate) name = this.group.startDate + ' - ' + name;
            return name;
        }
    }
});

Vue.component('tripgroup-item', {
    props: {
        tripgroup: Object
    },
    template: '#trip-line-template',
    methods: {}
});

Vue.component('trip-item', {
    props: {
        trip: Object,
        pushStatus: 'unchanged'
    },
    computed: {
        tripDistanceAccurracy: function() {
            return Math.round((this.trip.distance / this.trip.estimatedDistance) * 100) - 100;
        },
        tripDistanceAccurracyInfo: function() {
            return 'schatting Google Distance Matrix: ' + this.trip.estimatedDistance + ' Km'
        },
        calculatedPrice: function() {
            if(this.trip.transportType === 'car') {
                return this.trip.distance * 0.25;
            }
            else {
                return this.trip.price;
            }
        }
    },
    template: '#trip-item-template',
    methods: {
        updateStatus: function (status) {
            this.pushExpense(status);
        },
        pushExpense(status) {

            this.trip.originalStatus = this.trip.status;
            console.log('hello',this.trip);
            this.trip.status = 'pending';

            var tripData = {
                id: this.trip.id,
                status: status,
                adminComment: this.trip.adminComment,
                distance: this.trip.distance
            };

            var self = this;
            axios.post('/api/updateExpense', tripData)
                .then(function (response) {
                    if(response.data.status === 'error') {
                        alert('Er liep iets mis bij het opslaan van de onkosten. De status is niet geweizigd. Bekijk de console voor meer info.');
                        self.trip.status = self.trip.originalStatus;
                    }
                    else {
                        self.trip.status = response.data.data.tripStatus;
                    }
                })
                .catch(function (error) {
                    alert('Er liep iets mis bij het opslaan van de onkosten. Bekijk de console voor meer info.');
                    console.log('error', error);
                        console.log('hello',self.trip);
                    self.pushStatus = self.trip.originalStatus;
                });
        }
    }
});


var app = new Vue({
        // delimiters: ['${', '}'],
        el: '#validate_app',
        data: {
            startDataSet: false,
            regionId: null,
            // groupStack: [{"id":4,"text":"Jaarwerking"},{"id":1,"text":"Amoniac"}],
            groupStack: [],
            activeGroups: [],
            tripGroups: [],
            tripStatusFilter: [false, true, false],
            sorting: 'date',
            tripCount: 0
        },
        computed: {
            submitStatusClass: function (transportType) {
                return {
                    'fa fa-cog fa-spin fa-fw': this.submitStatus === 0,
                    'fa fa-check': this.submitStatus === 200,
                    'fa fa-exclamation-triangle': this.submitStatus === 500,
                }
            }
        },
        methods: {
            fetchGroups: function () {
                this.groupStack = [];
                this.tripGroups = [];
                var self = this;
                axios.get('/api/getValidateTree?region=' + this.regionId + '&denied=' + this.tripStatusFilter[0] + '&awaiting=' + this.tripStatusFilter[1] + '&approved=' + this.tripStatusFilter[2] + '&sorting=' + this.sorting)
                    .then(function (response) {
                        if(response.data.status === 'ok') {
                            self.groupStack = response.data.data;
                            self.tripCount = response.data.count;
                        }
                        else {
                            alert('server returned following error when fetching trips: ' + response.data.error);
                        }
                    })
                    .catch(function (error) {
                        self.fetchError = error;
                        alert('error fetching trip');
                    })
            },
            setStartData(data) {
                if (!this.startDataSet) {
                    this.startDataSet = true;
                }
            },
            setRegionId: function (regionId) {
                this.regionId = regionId;
                console.log(this.regionId);
                this.fetchGroups();
            },
            toggleTripStatusFilter: function(statusId) {
                this.tripStatusFilter[statusId] = !this.tripStatusFilter[statusId];
                this.$forceUpdate();

                console.log('kk', this.regionId);
                if(this.regionId !== null) {
                    this.fetchGroups();
                }
            },
            setSortingValue: function(sortingOrder) {
                this.sorting = sortingOrder;

                if(this.regionId !== null) {
                    this.fetchGroups();
                }
            }
        },
        mounted: function () {

        }
    })
;
