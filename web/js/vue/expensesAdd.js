Vue.component('form-errors', {
    props: ['error'],
    template: '<li>{{error}}</li>'
});

Vue.component('groupstack-item', {
    props: ['group'],
    template: '<li v-bind:data-id="group.id"><span class="btn btn-danger fa fa-remove" v-on:click="revertSelectors"></span> {{ group.text }}</li>',
    methods: {
        revertSelectors: function (evt) {
            var newArrayLength = $(evt.currentTarget.parentElement).index();
            this.$root.groupStack = this.$root.groupStack.slice(0, newArrayLength);

            var prevParentId = this.$root.groupStack[newArrayLength - 1] ? this.$root.groupStack[newArrayLength - 1].id : 0;
            this.$root.fetchGroups(prevParentId);
        }
    }
});

Vue.component('groupsavaliable-item', {
    props: ['group'],
    template: '<span class="btn btn-default" v-bind:data-id="group.id" v-on:click="loadNewGroups">{{ getName() }}</span>',
    methods: {
        loadNewGroups: function (evt) {
            this.$root.groupStack.push({id: this.group.id + '-' + this.group.type, text: this.group.name});
            if (this.group.type === 'activity') {

                this.$root.tripData.activityId = this.group.id;
            }
            else {
                this.$root.fetchGroups(evt.currentTarget.dataset.id);
                this.$root.tripData.groupId = this.group.id;
            }

            //clear the shown active groups until the new ones are loaded
            this.$root.activeGroups = [];
        },
        getName: function() {
            var name = this.group.name;
            if(this.group.startDate) name = this.group.startDate + ' - ' + name;
            return name;
        }
    }
});

var app = new Vue({
    delimiters: ['${', '}'],
    el: '#expenses_app',
    data: {
        counter: '',
        page: 0,
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
        submitStatus: 0,
        // editPersonDatapersonId: false,
        editPersonData: {
            personId: false,
            iban: false,
            address: false,
        },
    },
    computed: {
        submitStatusClass: function (transportType) {
            return {
                'fa fa-cog fa-spin fa-fw': this.submitStatus === 0,
                'fa fa-check': this.submitStatus === 200,
                'fa fa-exclamation-triangle': this.submitStatus === 500,
            }
        },
    },
    methods: {
        prevStep: function () {
            this.formErrors = [];
            this.page--;
        },
        nextStep: function () {
            if (this.validatePage(this.page)) this.page++;
        },
        submit: function () {
            var self = this;
            this.page++;
            self.submitStatus = 0;

            var tripData = {
                userData: this.userData,
                tripData: this.tripData,
                regionId: this.regionId
            };
            axios.post('/expenses/api/createTrip', tripData)
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
        validatePage: function (pageId) {
            this.formErrors = [];

            if (pageId === 1) {
                if (!this.userData.name.length) this.formErrors.push('vul je naam in');
                else if (this.userData.name.trim().indexOf(' ') < 0) this.formErrors.push('schrijf je voor- en achternaam gescheiden door een spatie');
                if (!this.userData.email.length) this.formErrors.push('vul je email in');
                else if (this.userData.email.indexOf('@') < 0 || this.userData.email.split('@')[1].indexOf('.') < 0) this.formErrors.push('het opgegeven emailadres is ongeldig');
                if (!this.userData.iban.length) this.formErrors.push('vul je iban in');
                if (!this.userData.personId.length) this.formErrors.push('vul je rijksregisternummer in');
                if (!this.userData.address.length) this.formErrors.push('vul je adres in');
            }
            else if (pageId === 2) {
                if (this.activeGroups.length) this.formErrors.push('specifieer je activiteit. Kies tussen de aangegeven activiteiten');
                if (!this.tripData.date) this.formErrors.push('vul de datum in waarop de activiteit plaats vond');
                if (!this.tripData.to) this.formErrors.push('vul de plaats van de activiteit in');
            }
            else if(pageId === 3) {
                if (this.tripData.transportType === 'car' &&
                    this.tripData.estimateDistance > 0 &&
                    this.tripData.distance > this.tripData.estimateDistance * 1.15 &&
                    this.tripData.comment.replace(/\s/g, '').length < 5) this.formErrors.push('Omdat het door jou opgegeven aantal kilometers te veel afwijkt van het geschatte aantal kilometers is een opmerking met meer uitleg verplicht.');
                if(this.tripData.from === '') this.formErrors.push('vul je vertrekplaats in');
                if(this.tripData.transportType === '') this.formErrors.push('kies je transportmiddel');
                if(this.tripData.transportType === 'car' && this.tripData.company === '' ) this.formErrors.push('specifieer je reisgezelschap');
                if(this.tripData.transportType === 'publicTransport' && this.tripData.tickets.length < 1 ) this.formErrors.push('upload een duidelijke foto of scan van je ticketjes');
                if(this.tripData.transportType === 'publicTransport' && !this.tripData.price ) this.formErrors.push('geef de totale prijs van je ticketjes in');
            }
            return !this.formErrors.length > 0;
        },
        fetchGroups: function (id = 0) {
            var self = this;
            axios.get('/expenses/api/getChildGroups?group=' + id.toString() + '&region=' + this.regionId)
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
            axios.get('/expenses/api/getTripActivities?group=' + id.toString() + '&region=' + this.regionId)
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
        onFileChange(e) {
            var files = e.target.files || e.dataTransfer.files;
            if (!files.length)
                return;
            this.createImage(files);
        },
        createImage(files) {
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

                    console.log(e, self.tripData.tickets);
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
                axios.post('/expenses/api/getTripDistance', locationData)
                    .then(function (response) {
                        self.tripData.estimateDistance = (response.data.distance * 2) / 1000;
                        self.tripData.distance = self.tripData.estimateDistance;
                    })
                    .catch(function (error) {
                        self.tripData.estimateDistance = -1;
                        console.log(error);
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

            console.log(d.getMonth());
            return weekday[d.getDay()] + ' ' + (Number(date.substr(8,2))).toString() + ' ' + month[d.getMonth()] + ' ' + d.getFullYear();
        },
        setStartData(data) {
            if (!this.startDataSet) {
                this.userData = Object.assign({}, this.userData, data.user);
                this.regionId = data.regionId;
                this.startDataSet = true;
            }
        }
    },
    mounted: function () {
        this.fetchGroups(0);
    }
});
