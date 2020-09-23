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
            // console.log(this.details);

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

            if(this.details.code) this.$root.tripData.code_vacation = this.details.code;
            if(this.details.code_s2) this.$root.tripData.code_s2 = this.details.code_s2;
            if(this.details.code_s3) this.$root.tripData.code_s3 = this.details.code_s3;
            if(this.details.code_s5) this.$root.tripData.code_s5 = this.details.code_s5;
            console.log(this.details);

            if(this.details && this.details.activities) {
                this.$root.activityNodes = this.details.activities;
                // console.log(this.details.activities, 'activities');
                this.$root.groupNodes = [];
            }
            else {
                console.log(this.tree);
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

Vue.component('flat-pickr', VueFlatpickr);

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
            price: 0,
            shareFromTrip: null,
            code_vacation: null,
            code_s2: null,
            code_s3: null,
            code_s5: null
        },
        distanceError: '',
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
        regionSelectorActive: false,
        datePickerConfig: {
            altFormat: "Y-m-d",
            altInput: true
        }
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

            // console.log(this.tripData);

            this.tripData.groupStack = this.crumbTrace;

            var tripData = {
                tripData: this.tripData,
                regionId: this.region.id
            };
            // axios.post('/app_dev.php/api/createTrip', tripData)
            axios.post('/api/createTrip', tripData)
                .then(function (respoconst axios = require('axios');nse) {
                    // console.log(response.data.status);
                    if(response.data.status === 'ok') {
                        self.submitStatus = 200;
                        self.tripData.id = response.data.tripId;

                        new QRCode(document.getElementById("qrcode"), "onkosten.kazourmt.be/add?t=" + self.tripData.id);
                    }
                    else {
                        self.submitStatus = 500;
                        console.log(self.formErrors);
                        self.formErrors = response.data.errors;
                    }
                })
                .catch(function (error) {
                    self.submitStatus = 500;
                });
        },
        onFileChange: function(e) {
            var files = e.target.files || e.dataTransfer.files;
            if (!files.length)
                return;
            this.createImage(files);
        },
        createImage: function(files) {
            // var image = new Image();

            var self = this;

            for (var i = 0; i < files.length; i++) {

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
                        console.log(response);
                        if(response.data.status === 'error') {
                            self.distanceError = 'Er kan momenteel geen verbinding gemaakt worden met de Google Maps service.\nAutomatische afstandsberekening is bij gevolg momenteel niet mogelijk.';
                            self.tripData.estimateDistance = -1;
                            console.log(response.data.message);
                        }
                        else if(response.data.status === 'no_data_found') {
                            self.distanceError = 'Er kon geen afstand berekend worden voor de door jou opgegeven route.\nVul daarom zelf je afgelegde aantal Km\'s in.';
                            self.tripData.estimateDistance = -1;
                            console.log('no results found for the given route.');
                        }
                        else if(response.data.status === 'ok') {
                            self.distanceError = '';
                            self.tripData.estimateDistance = (response.data.distance * 2) / 1000;
                            self.tripData.distance = self.tripData.estimateDistance;
                        }
                    })
                    .catch(function (error) {
                        self.distanceError = 'Er kon geen afstand berekend worden voor de door jou opgegeven route.\nVul daarom zelf je afgelegde aantal Km\'s in.';
                        self.tripData.estimateDistance = -1;
                    });
            }
        },
        setStartData(data) {
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
                    console.log(response);
                    if(response.data.data) {
                        self.groupNodes = response.data.data;
                    }

                    // console.log(response.data.data);

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
        },
        fetchShareTripDetails: function(tripId) {
            var self = this;
            axios.post('/api/getShareTripDetails?tripId=' + tripId)
                .then(function (response) {
                    self.tripData.shareFromTrip = response.data.tripId;
                    self.tripData.to = response.data.to;
                    self.tripData.date = response.data.date;
                    self.region = { id:response.data.regionId, name: response.data.regionName};
                    self.tripData.regionId = response.data.regionId;
                    self.tripData.groupCode = response.data.groupCode ? response.data.groupCode : '-';
                    self.tripData.code_vacation = response.data.code_vacation;
                    self.tripData.code_s2 = response.data.code_s2;
                    self.tripData.code_s3 = response.data.code_s3;
                    self.tripData.code_s5 = response.data.code_s5;
                    console.log(response);

                    self.crumbTrace = response.data.crumbTrace;
                    // todo: set distance multiplier
                    self.activityNodes = [{distanceMultiplier: 2, activity:response.data.activity}];
                    self.tripData.activity = response.data.activity;
                })
                .catch(function (error) {
                    console.log('>>>> ',error);
                });
        }
    },
    mounted: function () {

        if(getUrlParameter('t')) {
            this.fetchShareTripDetails(getUrlParameter('t'));
        }
        else {
            this.fetchTree(this.region.id);
            this.fetchRegions();
        }

    }
});
