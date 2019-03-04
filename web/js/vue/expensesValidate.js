Vue.component('group-container-item', {
    props: ['groups'],
    template: `<div>
<group-item
                    class="group"
                    v-for="(group, index) in groups"
                    :key="index"
                    :group="group"
            ></group-item>
</div>`,
    methods: {

    }
});

Vue.component('group-item', {
    props: {
        group: {},
        active: false
    },
    template: `<span v-show="filterCheck" class="btn btn-default" v-bind:class="btnType" @click="toggleActive">{{ group.name }} ({{ group.count }}) - {{ group.level }}</span>`,
    methods: {
        toggleActive: function () {
            this.active = !this.active;

            if(this.active) {
                // add fo filter
                this.$root.groupFilters[this.group.level].push(this.group.name);
            }
            else {
                // remove from filter
                var index = this.$root.groupFilters[this.group.level].indexOf(this.group.name);
                if (index > -1) {
                    this.$root.groupFilters[this.group.level].splice(index, 1);
                }
            }

            this.$root.fetchTrips();

            if(this.active) {
                console.log('todo: apply filter');
            }
        }
    },
    computed: {
        filterCheck: function () {
            return true;


        },
        btnType: function () {
            return this.active ? 'btn-primary' : 'btn-light';
        }
    },
    mounted: function() {
        this.active = false;
    }
});

// Vue.component('groupstack-item', {
//     props: ['group'],
//     template: `<li v-bind:data-id="group.id"><span class="btn btn-info fa fa-check" v-on:click="loadExpenses"></span> {{ group.name }} ({{ group.tripCount }})
//                     <groupstack-item
//                         v-for="group in this.$root.groupStack"
//                         v-bind:group="group"
//                         v-bind:key="group.id">
//                     </groupstack-item>
//                 </li>`,
//     methods: {
//         loadExpenses: function (evt) {
//             console.log('todo: load expenses');
//             // var newArrayLength = $(evt.currentTarget.parentElement).index();
//             // this.$root.groupStack = this.$root.groupStack.slice(0, newArrayLength);
//             //
//             // var prevParentId = this.$root.groupStack[newArrayLength - 1] ? this.$root.groupStack[newArrayLength - 1].id : 0;
//             // this.$root.fetchGroups(prevParentId);
//         }
//     }
// });

// define the item component
// Vue.component('tree-item', {
//     template: '#item-template',
//     props: {
//         model: Object,
//
//     },
//     data: function () {
//         return {
//             open: false,
//             inChecklist: false
//         }
//     },
//     computed: {
//         isFolder: function () {
//             return this.model.children;
//         },
//         getTripCount: function () {
//             return this.model.tripCount;
//         }
//     },
//     methods: {
//         toggle: function () {
//             if (this.model.children !== []) {
//                 this.open = !this.open
//             }
//         },
//         fetchTrips: function () {
//             var groupId = this.model.id;
//             var self = this;
//             axios.get('/api/validate/' + this.groupId + '/getTrips?denied=' + this.$root.tripStatusFilter[0] + '&awaiting=' + this.$root.tripStatusFilter[1] + '&approved=' + this.$root.tripStatusFilter[2] + '&sorting=' + this.$root.sorting)
//                 .then(function (response) {
//                     if(response.data.status === 'ok') {
//                         self.inChecklist = true;
//                         self.$root.tripGroups.push(response.data.data);
//                         console.log('reques finished: ', self.$root.tripGroups);
//                     }
//                     else {
//                         alert('error fetching trips. Check console log');
//                         console.log(response.data.status);
//                     }
//                 })
//                 .catch(function (error) {
//                     self.fetchError = error;
//                 })
//         }
//     },
//     mounted: function () {
//         // console.log(this.model);
//     }
// });

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
            tripCount: 0,
            levels: [],
            groupFilters: []
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

                this.fetchLevels();
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
            },
            fetchLevels: function () {

                var self = this;
                axios.post('/api/validate/' + this.regionId + '/getLevels')
                    .then(function (response) {
                        self.levels = response.data.levels;
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

            },
            fetchTrips: function () {
                var self = this;

                this.$root.tripGroups = [];

                var search = [];
                for(var i = 0; i < this.groupFilters.length; ++i) {
                    for(var j = 0; j < this.groupFilters[i].length; ++j) {
                        search.push(this.groupFilters[i][j]);
                        console.log(search);
                    }
                }

                axios.get('/app_dev.php/api/validate/' + this.regionId + '/getTrips?search=' + encodeURI(search.toString()) + '&denied=' + this.$root.tripStatusFilter[0] + '&awaiting=' + this.$root.tripStatusFilter[1] + '&approved=' + this.$root.tripStatusFilter[2] + '&sorting=' + this.$root.sorting)
                    .then(function (response) {
                        if(response.data.status === 'ok') {
                            console.log(response.data);
                            self.inChecklist = true;
                            self.$root.tripGroups.push(response.data.data);
                            // console.log('reques finished: ', self.$root.tripGroups);
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
            this.groupFilters.push([]);
            this.groupFilters.push([]);
            this.groupFilters.push([]);
        }
    })
;
