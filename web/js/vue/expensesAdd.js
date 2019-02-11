Vue.component('region', {
    props: ['name', 'id'],
    template: '<span class="btn" v-bind:class="btnType" v-on:click="switchRegion">{{name}}</span>',
    methods: {
        switchRegion: function () {
            this.$root.region = { id:this.id, name: this.name};
            this.$rood.resetActivityGroupStack();
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
    methods: {
        onClick: function () {
            this.$root.crumbTrace.push({
                'name': this.name
            });

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


            console.log('clicked');
        }
    }
});

var app = new Vue({
    delimiters: ['${', '}'],
    el: '#expenses_app',
    data: {
        counter: '',
        // page: 2,
        formErrors: [],
        groupStack: [],
        activeGroups: [],
        startDataSet: false,
        userData: {
            name: '',
            email: '',
            iban: '',
            personId: '',
            address: '',
        },
        tripData: {
            activity: '',
            from: '',
            to: '',
            date: '',
            transportType: '',
            company: '',
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
                'fa fa-cog fa-spin fa-fw': this.submitStatus === 0,
                'fa fa-check': this.submitStatus === 200,
                'fa fa-exclamation-triangle': this.submitStatus === 500,
            }
        }
    },
    methods: {
        // prevStep: function () {
        //     this.formErrors = [];
        //     this.page--;
        // },
        // nextStep: function () {
        //     if (this.validatePage(this.page)) this.page++;
        // },
        enableRegionSelector: function () {
            this.regionSelectorActive = true
        },
        submit: function () {
            var self = this;
            // this.page++;
            self.submitStatus = 0;

            var tripData = {
                userData: this.userData,
                tripData: this.tripData,
                regionId: this.regionId
            };
            axios.post('/api/createTrip', tripData)
                .then(function (response) {
                    if(response.data !== '') {
                        self.submitStatus = 200;
                    }
                    else {
                        self.submitStatus = 500;
                    }
                })
                .catch(function (error) {
                    self.submitStatus = 500;
                    console.log(error);
                });
        },
        // validatePage: function (pageId) {
        //     this.formErrors = [];
        //
        //     if (pageId === 1) {
        //         if (!this.userData.name.length) this.formErrors.push('vul je naam in');
        //         else if (this.userData.name.trim().indexOf(' ') < 0) this.formErrors.push('schrijf je voor- en achternaam gescheiden door een spatie');
        //         if (!this.userData.email.length) this.formErrors.push('vul je email in');
        //         else if (this.userData.email.indexOf('@') < 0 || this.userData.email.split('@')[1].indexOf('.') < 0) this.formErrors.push('het opgegeven emailadres is ongeldig');
        //         if (!this.userData.iban.length) this.formErrors.push('vul je iban in');
        //         if (!this.userData.personId.length) this.formErrors.push('vul je rijksregisternummer in');
        //         if (!this.userData.address.length) this.formErrors.push('vul je adres in');
        //     }
        //     else if (pageId === 2) {
        //         if (this.activeGroups.length) this.formErrors.push('specifieer je activiteit. Kies tussen de aangegeven activiteiten');
        //         if (!this.tripData.date) this.formErrors.push('vul de datum in waarop de activiteit plaats vond');
        //         if (!this.tripData.to) this.formErrors.push('vul de plaats van de activiteit in');
        //     }
        //     else if(pageId === 3) {
        //         if (this.tripData.transportType === 'car' &&
        //             this.tripData.estimateDistance > 0 &&
        //             this.tripData.distance > this.tripData.estimateDistance * 1.15 &&
        //             this.tripData.comment.replace(/\s/g, '').length < 5) this.formErrors.push('Omdat het door jou opgegeven aantal kilometers te veel afwijkt van het geschatte aantal kilometers is een opmerking met meer uitleg verplicht.');
        //         if(this.tripData.from === '') this.formErrors.push('vul je vertrekplaats in');
        //         if(this.tripData.transportType === '') this.formErrors.push('kies je transportmiddel');
        //         if(this.tripData.transportType === 'car' && this.tripData.company === '' ) this.formErrors.push('specifieer je reisgezelschap');
        //         if(this.tripData.transportType === 'publicTransport' && this.tripData.tickets.length < 1 ) this.formErrors.push('upload een duidelijke foto of scan van je ticketjes');
        //         if(this.tripData.transportType === 'publicTransport' && !this.tripData.price ) this.formErrors.push('geef de totale prijs van je ticketjes in');
        //     }
        //     return !this.formErrors.length > 0;
        // },
        fetchGroups: function (id = 0) {
            var self = this;
            axios.get('/api/getChildGroups?group=' + id.toString() + '&region=' + this.regionId)
                .then(function (response) {
                    self.activeGroups = response.data.data;
                    self.formErrors = [];
                    if (response.data.data.length < 1) self.fetchActivity(self.groupStack.slice(-1)[0].id)
                })
                .catch(function (error) {
                    self.fetchError = error;
                })
        },
        fetchActivity: function (id) {
            var self = this;
            axios.get('/api/getTripActivities?group=' + id.toString() + '&region=' + this.regionId)
                .then(function (response) {
                    // console.log(response.data.status);
                    if (response.data.status === 'ok') {
                        self.activeGroups = response.data.data;
                        self.formErrors = [];
                    }
                    else {
                        self.formErrors.push('De huidige groep bezit geen activiteiten. Contacteer het Kazou team.');
                        console.log('error when requesting activities', response.data);
                    }
                })
                .catch(function (error) {
                    self.fetchError = error;
                    console.log('error 123', error);
                })
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
        removeTicket: function (e) {
            if(e) {
                //TODO: remove element
                // this.tripData.tickets[] = '';
            }
            else {
                this.tripData.tickets = [];
            }

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
        formatDateNl: function(date) {
            var d = new Date(date);

            var month = new Array(12);
            month[0] = 'januari';
            month[1] = 'februari';
            month[2] = 'maart';
            month[3] = 'april';
            month[4] = 'mei';
            month[5] = 'juni';
            month[6] = 'juli';
            month[7] = 'augustus';
            month[8] = 'zaterdag';
            month[9] = 'september';
            month[10] = 'oktober';
            month[11] = 'november';
            month[12] = 'december';

            var weekday = new Array(7);
            weekday[0] = 'zondag';
            weekday[1] = 'maandag';
            weekday[2] = 'dinsdag';
            weekday[3] = 'woensdag';
            weekday[4] = 'donderdag';
            weekday[5] = 'vrijdag';
            weekday[6] = 'zaterdag';

            return weekday[d.getDay()] + ' ' + (Number(date.substr(8,2))).toString() + ' ' + month[d.getMonth()] + ' ' + d.getFullYear();
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

            this.$root.fetchTree(this.id);
        }
    },
    mounted: function () {
        // this.fetchGroups(0);
        this.fetchTree(this.region.id);
        this.fetchRegions();
    }
});
