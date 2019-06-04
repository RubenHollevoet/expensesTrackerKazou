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
    template: `<span v-show="filterCheck" class="btn btn-default" v-bind:class="btnType" @click="toggleActive">{{ group.name }} ({{ group.count }})</span>`,
    methods: {
        toggleActive: function () {
            console.log(this.group);
            this.active = !this.active;

            if(this.active) {
                // add fo filter
                console.log(this.group.level);
                this.$root.groupFilters[this.group.level].push(this.group.name);
                console.log('starting to fetsh trips -2');
            }
            else {
                // remove from filter
                var index = this.$root.groupFilters[this.group.level].indexOf(this.group.name);
                if (index > -1) {
                    this.$root.groupFilters[this.group.level].splice(index, 1);
                }
            }

            console.log('starting to fetsh trips');
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
            /*fetchGroups: function () {
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
            },*/
            setStartData: function(data) {
                if (!this.startDataSet) {
                    this.startDataSet = true;
                }
            },
            setRegionId: function (regionId) {
                this.regionId = regionId;
                console.log(this.regionId);
                //this.fetchGroups();

                this.fetchLevels();
            },
            toggleTripStatusFilter: function(statusId) {
                this.tripStatusFilter[statusId] = !this.tripStatusFilter[statusId];
                this.$forceUpdate();

                console.log('kk', this.regionId);
                if(this.regionId !== null) {
                    this.fetchLevels();
                    //this.fetchGroups();
                }
            },
            setSortingValue: function(sortingOrder) {
                this.sorting = sortingOrder;

                if(this.regionId !== null) {
                    this.fetchLevels();
                    //this.fetchGroups();
                }
            },
            fetchLevels: function () {

                //clear search filters when reloading levels
             //   this.groupFilters = [];

                var self = this;
                axios.post('/api/validate/' + this.regionId + '/getLevels?' + this.getFiltersAsQueryParameters())
                    .then(function (response) {
                        self.levels = response.data.levels;
                        self.tripCount = response.data.count;
                    })
                    .catch(function (error) {
                        console.log(error);
                    });

            },
            fetchTrips: function () {
                var self = this;

                this.$root.tripGroups = [];

                // var url = '/app_dev.php/api/validate/' + this.regionId + '/getTrips?' + this.getFiltersAsQueryParameters();
                var url = '/api/validate/' + this.regionId + '/getTrips?' + this.getFiltersAsQueryParameters();
                console.log(url);

                axios.get(url)
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
            },
            getFiltersAsQueryParameters: function() {

                var url = '';

                //group filters
                var search = [];
                for(var i = 0; i < this.groupFilters.length; ++i) {
                    for(var j = 0; j < this.groupFilters[i].length; ++j) {
                        search.push(this.groupFilters[i][j]);
                    }
                }

                if(search.length > 0) {
                    url += 'search=' + encodeURI(search.toString()) + '&';
                }

                //status filters
                var statusOptions = [];
                if(this.$root.tripStatusFilter[0]) statusOptions.push('denied');
                if(this.$root.tripStatusFilter[1]) statusOptions.push('awaiting');
                if(this.$root.tripStatusFilter[2]) statusOptions.push('approved');

                if(statusOptions.length > 0) {
                    url += 'status=' + statusOptions.toString() + '&';
                }

                if(this.$root.sorting) {
                    url += 'sorting=' + this.$root.sorting;
                }

                //remove last character of string if = '&'
                if(url.charAt(url.length-1) === '&') url = url.substring(0, url.length - 1);

                return url;
            }
        },
        mounted: function () {
            this.groupFilters.push([]);
            this.groupFilters.push([]);
            this.groupFilters.push([]);
        }
    })
;
