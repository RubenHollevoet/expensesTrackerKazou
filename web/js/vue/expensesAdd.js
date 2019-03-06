Vue.component('region', {
    props: ['name', 'id'],
    template: '<span class="btn" v-bind:class="btnType" v-on:click="switchRegion">{{name}}</span>',
    methods: {
        switchRegion: function () {
            this.$root.region = { id:this.id, name: this.name};
            this.$root.resetActivityGroupStack();
        }
    },
    computed: {
        btnType: function () {
            return this.id === this.$root.region.id ? 'btn-primary' : 'btn-light';
        }
    }
});

Vue.component('form-errors', {
    props: ['error'],
    template: '<li>{{error}}</li>'
});

Vue.component('crumb', {
    props: {
        name: ''
    },
    template: '<span class="crumb"><span class="disableClick btn btn-outline-secondary btn-sm">{{this.name}}</span></span>'
});

Vue.component('group-node', {
    props: {
        tree: {},
        name: '',
        details: {}
    },
    template: '#group-node-template',
    computed: {
        getLabel: function() {
            console.log(this.details);

            var label = '';
            label += this.name;
            if(this.details && this.details.location) {
                label += ' - ' + this.details.location;
            }
            if(this.details && this.details.start) {
                let start = new Date(this.details.start);
                label += ' - ' + start.getDate() + '/' + start.getMonth();
            }
            return label;
        }
    },
    methods: {
        onClick: function () {
            this.$root.crumbTrace.push(this.name);

            if(this.details.hash) {
                this.$root.tripData.groupCode = this.details.hash;
            }

            if(this.details && this.details.activities) {
                this.$root.activityNodes = this.details.activities;
                console.log(this.details.activities, 'activities');
                this.$root.groupNodes = [];
            }
            else {
                this.$root.groupNodes = this.tree;
                this.$root.activityNodes = [];
            }

        }
    }
});

Vue.component('activity-node', {
    props: {
        tree: {},
        name: '',
        details: {}
    },
    template: '#activity-node-template',
    computed: {
        getBtnType: function() {
            return this.$root.tripData.activity === this.name ? 'btn-primary' : 'btn-light';
        }
    },
    methods: {
        onClick: function () {
            this.$root.tripData.activity = this.name;
        }
    }
});

var app = new Vue({
    delimiters: ['${', '}'],
    el: '#expenses_app',
    data: {
        counter: '',
        formErrors: [],
        groupStack: [],
        activeGroups: [],
        startDataSet: false,
        userData: {
            name: '',
            email: '',
            iban: '',
            personId: '',
            address: ''
        },
        tripData: {
            id: 0,
            groupCode: '',
            groupStack: [],
            activity: '',
            from: '',
            to: '',
            date: '',
            transportType: '',
            company: 'soloDriver',
            distance: 0,
            estimateDistance: -1,
            comment: '',
            tickets: [],
            price: 0
        },
        crumbTrace: [],
        groupNodes: [],
        activityNodes: [],
        submitStatus: 0,
        editPersonData: {
            personId: false,
            iban: false,
            address: false
        },
        region: {},
        regions: [],
        regionSelectorActive: false
    },
    computed: {
        submitStatusClass: function (transportType) {
            return {
                'validator-ok': this.submitStatus === 200,
                'validator-nok': this.submitStatus === 500,
                'validator-to-finish': this.submitStatus === 0
            }
        },
        qrLink : function () {
            if(this.tripData.id) {
                return "onkosten.kazourmt.be/add?t=" + this.tripData.id;
            }

            return '';
        }
    },
    methods: {
        enableRegionSelector: function () {
            this.regionSelectorActive = true
        },
        getDaysAgo: function (daysAgo) {
            let date = new Date();
            date.setDate(date.getDate() - daysAgo);

            let month = '' + (date.getMonth() + 1);
            let day = '' + date.getDate();
            const year = date.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            return [year, month, day].join('-');
        },
        createTrip: function () {
            var self = this;
            self.submitStatus = 1;

            console.log(this.tripData);

            this.tripData.groupStack = this.crumbTrace;

            var tripData = {
                tripData: this.tripData,
                regionId: this.region.id
            };
            axios.post('/api/createTrip', tripData)
                .then(function (response) {
                    console.log(response.data.status);
                    if(response.data.status === 'ok') {
                        self.submitStatus = 200;
                        self.tripData.id = response.data.tripId;

                        new QRCode(document.getElementById("qrcode"), "onkosten.kazourmt.be/add?t=" + self.tripData.id);
                    }
                    else {
                        self.submitStatus = 500;
                        self.formErrors = response.data.errors;
                    }
                })
                .catch(function (error) {
                    self.submitStatus = 500;
                    console.log(error);
                });
        },
        // fetchGroups: function (id = 0) {
        //     var self = this;
        //     axios.get('/api/getChildGroups?group=' + id.toString() + '&region=' + this.regionId)
        //         .then(function (response) {
        //             self.activeGroups = response.data.data;
        //             self.formErrors = [];
        //             if (response.data.data.length < 1) self.fetchActivity(self.groupStack.slice(-1)[0].id)
        //         })
        //         .catch(function (error) {
        //             self.fetchError = error;
        //         })
        // },
        // fetchActivity: function (id) {
        //     var self = this;
        //     axios.get('/app_dev.php/api/getTripActivities?group=' + id.toString() + '&region=' + this.regionId)
        //         .then(function (response) {
        //             // console.log(response.data.status);
        //             if (response.data.status === 'ok') {
        //                 self.activeGroups = response.data.data;
        //                 self.formErrors = [];
        //             }
        //             else {
        //                 self.formErrors.push('De huidige groep bezit geen activiteiten. Contacteer het Kazou team.');
        //                 console.log('error when requesting activities', response.data);
        //             }
        //         })
        //         .catch(function (error) {
        //             self.fetchError = error;
        //             console.log('error 123', error);
        //         })
        // },
        onFileChange: function(e) {
            var files = e.target.files || e.dataTransfer.files;
            if (!files.length)
                return;
            this.createImage(files);
        },
        createImage: function(files) {
            // var image = new Image();

            var self = this;

            console.log(files);

            for (var i = 0; i < files.length; i++) {
                console.log(files[i]);

                var file = files[i];
                var reader = new FileReader();

                if (!file.type.match('image.*')) {
                    alert('Je kan enkel afbeeldingen uploaden als ticketjes');
                    return;
                }
                if (file.size >= 10000000) {
                    alert('Een afbeeldingen mag maximaal 10Mb zijn.');
                    return;
                }

                reader.onload = (e) => {
                    // vm.tripData.tickets = e.target.result;
                    self.tripData.tickets.push({
                        content: e.target.result,
                        mime: file.type
                    });
                }
                reader.readAsDataURL(file);
            };
        },
        removeTickets: function () {
            this.tripData.tickets = [];
        },
        updateFrom: function (from) {
            this.tripData.from = from;
            if(from === this.userData.address) {
                this.calculateDistance();
            }
        },
        updateTo: function (to) {
            this.tripData.to = to;
            this.calculateDistance();
        },
        forceUpdateMapsField: function (place, field) {
            eval(field + " = '" + place + "'");
        },
        calculateDistance: function() {
            if( this.tripData.from && this.tripData.to) {
                var self = this;

                var locationData = {
                    from: this.tripData.from,
                    to: this.tripData.to
                };
                axios.post('/api/getTripDistance', locationData)
                    .then(function (response) {
                        self.tripData.estimateDistance = (response.data.distance * 2) / 1000;
                        self.tripData.distance = self.tripData.estimateDistance;
                    })
                    .catch(function (error) {
                        self.tripData.estimateDistance = -1;
                    });
            }
        },
        setStartData(data) {
            console.log(data);
            if (!this.startDataSet) {
                this.userData = Object.assign({}, this.userData, data.user);
                this.region = data.region;
                this.startDataSet = true;
            }
        },
        fetchRegions: function() {
            var self = this;
            axios.post('/api/getRegions')
                .then(function (response) {
                    self.regions = response.data;
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        fetchTree: function(regionId) {
            var self = this;

            axios.post('/api/getActivityTree?regionId=' + regionId)
                .then(function (response) {
                    self.groupNodes = response.data.data;
                    console.log(response.data.data);

                    // console.log(self.activityTree);
                })
                .catch(function (error) {
                    console.log(error);
                });
        },
        setTripDate: function(when) {
            const $tripDate = document.querySelector('input[type=date]');
            let date = new Date();
            if(when === 'yesterday') {
                date.setDate(date.getDate() - 1);
            }

            let month = '' + (date.getMonth() + 1);
            let day = '' + date.getDate();
            const year = date.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            this.tripData.date = [year, month, day].join('-');
            // $tripDate.value = [year, month, day].join('-');
        },
        resetGroups: function() {
            this.resetActivityGroupStack();
        },
        resetActivityGroupStack: function() {
            this.$root.regionSelectorActive = false;

            this.$root.groupNodes = [];
            this.$root.activityNodes = [];
            this.$root.crumbTrace = [];

            this.$root.tripData.activity = '';

            this.$root.fetchTree(this.region.id);
        }
    },
    mounted: function () {
        // this.fetchGroups(0);
        this.fetchTree(this.region.id);
        this.fetchRegions();
    }
});
