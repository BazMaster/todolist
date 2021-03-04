<template>
    <div>
        <div v-if="unautorized"
             class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
            <strong class="text-bold">Unauthorized</strong>
            To save your tasks, please log in.
        </div>
        <div v-if="offline"
             class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
            <strong class="text-bold">Offline mode!</strong>
            Please check your internet connection.
        </div>
        <div v-if="hasOfflineTasks && !unautorized && !offline"
             id="sync"
             class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
             role="alert">
            Some tasks are not saved.
            <a @click.prevent="saveStorage" class="underline hover:no-underline">
                Sync?
            </a>
        </div>

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
            <div v-if="!unautorized" class="ml-4 mt-1 text-gray-500">
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
            <div v-if="Object.keys(rows).length === 0"
                 class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                Your Todo List is empty!
            </div>

            <div v-for="row in rows"
                 :class="{ 'opacity-50' : row.status, 'bg-blue-50' : row.offline,  }"
                 :key="row.id"
                 class="flex px-3 py-1 mb-0 rounded items-center hover:bg-gray-200">
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
                status: false,
                offline: false,
            },
            filter: '',
            unautorized: false,
            offline: false,
            loading: false,
            loading_form: false,
            api: '/api/tasks'
        }
    },
    mounted() {
        this.getRows();
    },
    computed: {
        hasOfflineTasks: function () {
            return !!localStorage.getItem('todo_list');
        }
    },
    methods: {
        getRows() {
            this.loading = true;

            axios.get('/sanctum/csrf-cookie').then(response => {
                this.offline = false;
                axios
                    .get(this.api, {
                        params: {
                            filter: this.filter
                        }
                    })
                    .then(response => {
                        this.rows = response.data;
                        this.unautorized = false;
                        console.log(response);
                    })
                    .catch(error => {
                        console.log(error);

                        this.unautorized = true;

                        if (localStorage.getItem('todo_list')) {
                            this.rows = JSON.parse(localStorage.getItem('todo_list'));
                        }
                    })
                    .finally(() => this.loading = false)
                ;
            }).catch(error => {
                if (!error.response) {
                    this.offline = true;

                    if (localStorage.getItem('todo_list')) {
                        this.rows = JSON.parse(localStorage.getItem('todo_list'));
                    }
                } else {
                    const code = error.response.status
                    const response = error.response.data
                    console.log(code, response);
                }
            });


        },

        deleteRow(id) {
            let conf = confirm('Do you confirm deleting the record?');
            if(conf) {
                this.loading = true;

                axios.get('/sanctum/csrf-cookie').then(response => {
                    this.offline = false;
                    axios
                        .delete(this.api + '/' + id)
                        .then(response => {
                            console.log('The entry was successfully deleted');
                            Vue.$toast.success(response.data.message)
                            this.getRows();
                        })
                        .catch(error => {
                            console.log('An error occurred');

                            if(error.response.status === 401) {
                                console.log('404 - Unauthorized');
                                this.unautorized = true;

                                const obj = this.rows;
                                for(let item in obj) {
                                    if(obj[item].id === id) {
                                        delete obj[item];
                                    }
                                }
                                this.rows = Object.assign({}, this.rows, obj);
                                this.syncStorage();
                            }
                            else {
                                if(error.response.data.message) {
                                    Vue.$toast.error(error.response.data.message)
                                }
                            }
                        })
                    ;
                }).catch(error => {
                    if (!error.response) {
                        this.offline = true;

                        const obj = this.rows;
                        for(let item in obj) {
                            if(obj[item].id === id) {
                                delete obj[item];
                            }
                        }
                        this.rows = Object.assign({}, this.rows, obj);
                        this.syncStorage();
                    } else {
                        const code = error.response.status
                        const response = error.response.data
                        console.log(code, response);
                    }
                });

            }
        },

        submitItem() {
            this.loading_form = true;

            console.log('add');

            axios.get('/sanctum/csrf-cookie').then(response => {
                this.offline = false;
                axios
                    .post(this.api, this.row)
                    .then(response => {
                        console.log(response);
                        this.formClearFields();
                        this.getRows();
                        console.log('The entry was successfully added');
                        Vue.$toast.success(response.data.message)
                    })
                    .catch(error => {
                        console.log('An error occurred');
                        console.log(error);

                        if(error.response.status === 401) {
                            console.log('404 - Unauthorized');
                            this.unautorized = true;

                            if(this.row.title) {
                                const obj = {};
                                let count = Object.keys(this.rows).length;

                                obj[count] = {
                                    id: count + 1,
                                    title: this.row.title,
                                    status: false,
                                    offline: false,
                                };
                                this.rows = Object.assign({}, this.rows, obj);

                                this.formClearFields();
                                this.syncStorage();
                            }
                        }
                        else {
                            if(error.response.data.message) {
                                Vue.$toast.error(error.response.data.message)
                            }
                            else if (Object.keys(error.response.data.errors).length) {
                                let errors = Object.values(error.response.data.errors).join(', ');
                                if(errors) {
                                    Vue.$toast.error(errors)
                                }
                            }
                        }
                        console.log(error.response);

                    })
                    .finally(() => this.loading_form = false)
            }).catch(error => {
                if (!error.response) {
                    this.offline = true;

                    if(this.row.title) {
                        const obj = {};
                        let count = Object.keys(this.rows).length;

                        obj[count] = {
                            id: count + 1,
                            title: this.row.title,
                            status: false,
                            offline: false,
                        };
                        this.rows = Object.assign({}, this.rows, obj);

                        this.formClearFields();
                        this.syncStorage();
                    }
                } else {
                    const code = error.response.status
                    const response = error.response.data
                    console.log(code, response);
                }
            });

        },

        editItem(row) {
            this.loading_form = true;

            console.log('edit');

            axios.get('/sanctum/csrf-cookie').then(response => {
                this.offline = false;
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

                        if(error.response.status === 401) {
                            // console.log('404 - Unauthorized');
                            // this.unautorized = true;
                            //
                            // const obj = this.rows;
                            // for(let item in obj) {
                            //     if(obj[item].id === row.id) {
                            //         obj[item] = row;
                            //     }
                            // }
                            // this.rows = Object.assign({}, this.rows, obj);
                            this.syncStorage();
                        }
                        else {
                            if(error.response.data.message) {
                                Vue.$toast.error(error.response.data.message)
                            }
                            else if(Object.keys(error.response.data.errors).length) {
                                let errors = Object.values(error.response.data.errors).join(', ');
                                if(errors) {
                                    Vue.$toast.error(errors)
                                }
                            }
                        }
                    })
                    .finally(() => this.loading_form = false)
            }).catch(error => {
                if (!error.response) {
                    this.offline = true;

                    this.syncStorage();
                } else {
                    const code = error.response.status
                    const response = error.response.data
                    console.log(code, response);
                }
            });

        },

        formClearFields() {
            this.row.id = '';
            this.row.title = '';
            this.row.status = false;
            this.row.offline = false;
        },

        sendFilter(filter) {
            this.filter = filter;
            this.getRows();
        },

        syncStorage() {
            localStorage.setItem('todo_list', JSON.stringify(this.rows));
        },

        saveStorage() {
            let rows = localStorage.getItem('todo_list');

            axios.get('/sanctum/csrf-cookie').then(response => {
                this.offline = false;
                axios
                    .post(this.api + '/saveStorage', {rows: rows})
                    .then(response => {
                        console.log(response);
                        localStorage.removeItem('todo_list');
                        this.hasOfflineTasks = false;
                        document.getElementById('sync').remove();
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
            });

        }

    }
}
</script>


<style scoped>

</style>
