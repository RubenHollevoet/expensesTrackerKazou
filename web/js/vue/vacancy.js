Vue.component('vacancy-filter', {
    data: function () {
        return {
            isActive: false
        }
    },
    props: ['name'],
    // template: '<button>{{ name }}</button>'
    template: '<span class="btn btn-default vacancyFilter" v-on:click="isActive = !isActive" v-bind:class="{ active: isActive }">{{ name }}</span>'
});

var app = new Vue({
    el: '#vacancy_app',
    data: {
        ageGroups: [
            'group 1',
            'group 2',
            'group 3-',
        ],
        userDataSet: false,
    },
    methods: {
        setStartData(data) {
            if(!this.userDataSet) {
                for (var group in data) {
                    this.ageGroups.push(data[group].name);
                    console.log('add group', group);
                }

                this.userDataSet = true;
            }
        },
    }
});
