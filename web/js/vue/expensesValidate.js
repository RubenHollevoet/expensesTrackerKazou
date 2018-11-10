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
            this.$root.groupStack.push({id: this.group.id, text: this.group.name});
            this.$root.fetchGroups(evt.currentTarget.dataset.id);
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
    template: '<table><tr><td><h2>{{ tripgroup.name }}</h2></td></tr><trip-item v-for="trip in tripgroup.trips" v-bind:trip="trip" v-bind:key="trip.id"></trip-item></div></tr></table>',
    methods: {}
});

Vue.component('trip-item', {
    props: {
        trip: Object
    },
    template: `<tr>
                    <td>
                       <span v-show="trip.transportType==='car'" class="fa fa-car"></span>
                       <span v-show="trip.transportType==='publicTransport'" class="fa fa-bus"></span>
                       <span v-show="trip.transportType==='bike'" class="fa fa-bicycle"></span>
                       <span v-show="trip.transportType==='scooter'" class="fa fa-motorcycle"></span>
                    </td>
                    <td>{{ trip.name }}</td>
                    <td>{{ trip.date }}</td>
                    <td>{{ trip.from }}</td>
                    <td>{{ trip.to }}</td>
                    <td>{{ trip.activity }}</td>
                    <td>{{ trip.comment }}</td>
                    <td>{{ trip.distance }}</td>
                    <td>{{ trip.estimateDistance }}</td>
                    <td>
                        <span v-show="trip.status!=='processed'" class="statusActions">
                            <span v-on:click="updateStatus('denied')">afgekeurd</span>
                            <span v-on:click="updateStatus('awaiting')">in afwachting</span>
                            <span v-on:click="updateStatus('approved')">goedgekeurd</span>
                        </span>
                        <span v-show="trip.status==='processed'">
                        </span>
                    </td>
                </tr>`,
    methods: {}
});

Vue.component('')

var app = new Vue({
        delimiters: ['${', '}'],
        el: '#validate_app',
        data: {
            startDataSet: false,
            regionId: 5,
            groupStack: [{"id":4,"text":"Jaarwerking"},{"id":1,"text":"Amoniac"}],
            activeGroups: [],
            tripGroups: []
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
            loadExpenses() {
                var self = this;
                var groupId = this.groupStack[this.groupStack.length - 1] ? this.groupStack[this.groupStack.length - 1].id.toString() : 'null';
                axios.get('/app_dev.php/expenses/api/getExpenses?group=' + groupId + '&region=' + this.regionId)
                    .then(function (response) {
                        if(response.data.status === 'ok') {
                            self.tripGroups = response.data.data;
                            console.log(response.data.data);
                        }
                        else {
                            alert('error fetching trips. Check console log');
                            console.log(response.data.status);
                        }


                        // self.formErrors = [];
                        // if (response.data.data.length < 1) self.fetchActivity(self.groupStack.slice(-1)[0].id)
                    })
                    .catch(function (error) {
                        self.fetchError = error;
                    })
            },
            setStartData(data) {
                if (!this.startDataSet) {
                    this.regionId = data.regionId;
                    this.startDataSet = true;
                }
            }
        },
        mounted: function () {
            this.fetchGroups(0);
        }
    })
;
