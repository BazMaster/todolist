<template>
    <div>
        <div class="mb-4">
            <form action="" @submit.prevent="submitItem">
                <div class="flex mt-4">
                    <input
                        v-model="row.title"
                        @submit="submitItem"
                        class="appearance-none shadow border rounded w-full py-2 px-3 mr-4 focus:outline-none focus:border-red-300"
                        placeholder="Add Todo">
                    <button
                        type="submit"
                        class="flex-no-shrink p-2 border-2 rounded text-green-600 border-green-300 hover:bg-green-100 focus:outline-none">
                        Add
                    </button>
                </div>
            </form>
            <div class="ml-4 mt-1 text-gray-500">
                <a @click="sendFilter('')"
                   :class="{'text-red-500 font-bold': filter === ''}"
                    class="hover:underline"
                   href="#">All</a> |
                <a @click="sendFilter('checked')"
                   :class="{'text-red-500 font-bold': filter === 'checked'}"
                   class="hover:underline" href="#">Checked</a> |
                <a @click="sendFilter('unchecked')"
                   :class="{'text-red-500 font-bold': filter === 'unchecked'}"
                   class="hover:underline" href="#">Unchecked</a>
            </div>
        </div>
        <div>
            <div v-for="row in rows"
                 :class="{ 'opacity-50' : row.status }"
                 :key="row.id"
                 class="flex p-3 mb-4 rounded items-center hover:bg-gray-200">
                <input v-model="row.status"
                       @change="editItem(row)"
                    type="checkbox" class="appearance-none checked:bg-blue-600 checked:border-transparent">
                <p class="w-full mr-4 ml-1 text-gray-900">
                    <input
                        v-model="row.title"
                        @change="editItem(row)"
                        :class="{ 'text-gray-500 line-through' : row.status }"
                        class="appearance-none shadow-none border-none rounded w-full py-2 px-3 bg-transparent focus:bg-white"
                    >
                </p>
                <button v-if="row.status"
                        @click="deleteRow(row.id)"
                        title="Remove task"
                    class="p-1 bg-red-500 text-white font-semibold rounded shadow-md hover:bg-red-700 focus:outline-none">
                    <svg class="w-4 h-4"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import Vue from 'vue'
import VueToast from 'vue-toast-notification';
import 'vue-toast-notification/dist/theme-sugar.css';
Vue.use(VueToast);

export default {
    data: function() {
        return {
            rows: {},
            row: {
                id: '',
                title: '',
                status: false
            },
            filter: '',
            loading: false,
            loading_form: false,
            api: '/api/tasks'
        }
    },
    mounted() {
        this.getRows();
    },
    methods: {
        getRows() {
            this.loading = true;
            axios
                .get(this.api, {
                    params: {
                        filter: this.filter
                    }
                })
                .then(response => {
                    this.rows = response.data;
                    console.log(response);
                })
                .catch(error => {
                    console.log(error);
                })
                .finally(() => this.loading = false)
            ;
        },

        deleteRow(id) {
            let conf = confirm('Do you confirm deleting the record?');
            if(conf) {
                this.loading = true;
                axios
                    .delete(this.api + '/' + id)
                    .then(response => {
                        console.log('The entry was successfully deleted');
                        Vue.$toast.success(response.data.message)
                        this.getRows();
                    })
                    .catch(error => {
                        console.log('An error occurred');
                        if(error.response.data.message) {
                            Vue.$toast.error(error.response.data.message)
                        }
                    })
                ;
            }
        },
        submitItem() {
            this.loading_form = true;

            console.log('add');
            axios
                .post(this.api,
                    this.row
                )
                .then(response => {
                    console.log(response);
                    this.formClearFields();
                    this.getRows();
                    console.log('The entry was successfully added');
                    Vue.$toast.success(response.data.message)
                })
                .catch(error => {
                    console.log('An error occurred');
                    if(error.response.data.message) {
                        Vue.$toast.error(error.response.data.message)
                    }
                    else if (Object.keys(error.response.data.errors).length) {
                        let errors = Object.values(error.response.data.errors).join(', ');
                        if(errors) {
                            Vue.$toast.error(errors)
                        }
                    }
                })
                .finally(() => this.loading_form = false)
        },
        editItem(row) {
            this.loading_form = true;

            console.log('edit');
            axios
                .put(this.api + '/' + row.id, row)
                .then(response => {
                    console.log(response);
                    this.getRows();

                    console.log('The task was successfully changed');
                    Vue.$toast.success(response.data.message)
                })
                .catch(error => {
                    console.log('An error occurred');
                    if(error.response.data.message) {
                        Vue.$toast.error(error.response.data.message)
                    }
                    else if(Object.keys(error.response.data.errors).length) {
                        let errors = Object.values(error.response.data.errors).join(', ');
                        if(errors) {
                            Vue.$toast.error(errors)
                        }
                    }
                })
                .finally(() => this.loading_form = false)
        },

        formClearFields() {
            this.row.id = '';
            this.row.title = '';
            this.row.status = false;
        },

        sendFilter(filter) {
            this.filter = filter;
            this.getRows();
        }

        // checkRow(row) {
        //     console.log(row);
        //
        //     this.loading = true;
        //     axios
        //         .get(this.api + '/' + row.id + '/edit')
        //         .then(response => {
        //             console.log(response);
        //             this.getRows();
        //         })
        //         .catch(error => {
        //             console.log(error);
        //         })
        //     ;
        //
        // }


    }
}
</script>


<style scoped>

</style>
