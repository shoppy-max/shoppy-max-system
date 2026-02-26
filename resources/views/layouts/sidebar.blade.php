<aside class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 font-sans" aria-label="Sidebar">
    <div class="h-full px-3 py-4 overflow-y-auto bg-white dark:bg-gray-800 flex flex-col justify-between">
        <!-- Brand & Header -->
        <div>
            <div class="flex items-center justify-between mb-5 pl-2">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <div class="bg-primary-600 p-2 rounded-xl shadow-lg shadow-primary-500/50">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <span class="self-center text-xl font-bold whitespace-nowrap dark:text-white tracking-tight">Shoppy<span class="text-primary-600">Max</span></span>
                </a>
                
                <!-- Dark Mode Toggle (Sidebar) -->
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            
            <ul class="space-y-2 font-medium">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('dashboard') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : '' }}">
                        <svg class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white {{ request()->routeIs('dashboard') ? 'text-primary-600 dark:text-primary-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="ms-3">Dashboard</span>
                    </a>
                </li>

                <!-- Contacts -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('customers.*') || request()->routeIs('suppliers.*') || request()->routeIs('cities.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-contacts" data-collapse-toggle="dropdown-contacts">
                         <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('customers.*') || request()->routeIs('suppliers.*') || request()->routeIs('cities.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Contact Management</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <ul id="dropdown-contacts" class="{{ request()->routeIs('customers.*') || request()->routeIs('suppliers.*') || request()->routeIs('cities.*') ? '' : 'hidden' }} py-2 space-y-2">
                        <li>
                            <a href="{{ route('customers.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('customers.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Customers</a>
                        </li>
                        <li>
                            <a href="{{ route('suppliers.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('suppliers.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Suppliers</a>
                        </li>

                        <li>
                            <a href="{{ route('cities.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('cities.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Cities</a>
                        </li>
                    </ul>
                </li>

                <!-- Resellers -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('resellers.*') || request()->routeIs('direct-resellers.*') || request()->routeIs('reseller-targets.*') || request()->routeIs('reseller-payments.*') || request()->routeIs('reseller-dues.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-resellers" data-collapse-toggle="dropdown-resellers">
                        <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('resellers.*') || request()->routeIs('direct-resellers.*') || request()->routeIs('reseller-targets.*') || request()->routeIs('reseller-payments.*') || request()->routeIs('reseller-dues.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Resellers</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <ul id="dropdown-resellers" class="{{ request()->routeIs('resellers.*') || request()->routeIs('direct-resellers.*') || request()->routeIs('reseller-targets.*') || request()->routeIs('reseller-payments.*') || request()->routeIs('reseller-dues.*') ? '' : 'hidden' }} py-2 space-y-2">
                        <li>
                            <a href="{{ route('resellers.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('resellers.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Resellers List</a>
                        </li>
                        <li>
                            <a href="{{ route('resellers.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('resellers.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Reseller</a>
                        </li>
                        <li>
                            <a href="{{ route('direct-resellers.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('direct-resellers.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Direct Resellers List</a>
                        </li>
                        <li>
                            <a href="{{ route('direct-resellers.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('direct-resellers.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Direct Reseller</a>
                        </li>
                        <li>
                            <a href="{{ route('reseller-targets.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('reseller-targets.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Targets List</a>
                        </li>
                        <li>
                            <a href="{{ route('reseller-payments.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('reseller-payments.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">User Payments</a>
                        </li>
                        <li>
                            <a href="{{ route('reseller-dues.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('reseller-dues.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">User Due Payments</a>
                        </li>
                    </ul>
                </li>

                <!-- Products -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('sub-categories.*') || request()->routeIs('units.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-products" data-collapse-toggle="dropdown-products">
                        <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('sub-categories.*') || request()->routeIs('units.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Products</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <ul id="dropdown-products" class="{{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('sub-categories.*') || request()->routeIs('units.*') ? '' : 'hidden' }} py-2 space-y-2">
                        <li>
                             <a href="{{ route('products.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('products.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Products List</a>
                        </li>
                        <li>
                             <a href="{{ route('products.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('products.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Product</a>
                        </li>
                        <li>
                             <a href="{{ route('categories.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('categories.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Categories List</a>
                        </li>
                        <li>
                             <a href="{{ route('sub-categories.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('sub-categories.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Sub Categories List</a>
                        </li>
                        <li>
                             <a href="{{ route('units.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('units.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Units List</a>
                        </li>
                    </ul>
                </li>





            <!-- Couriers -->
            <li>
                <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('couriers.*') || request()->routeIs('courier-receive.*') || request()->routeIs('courier-payments.*') || request()->routeIs('bank-accounts.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-couriers" data-collapse-toggle="dropdown-couriers">
                    <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('couriers.*') || request()->routeIs('courier-receive.*') || request()->routeIs('courier-payments.*') || request()->routeIs('bank-accounts.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Couriers</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                    </svg>
                </button>
                <ul id="dropdown-couriers" class="{{ request()->routeIs('couriers.*') || request()->routeIs('courier-receive.*') || request()->routeIs('courier-payments.*') || request()->routeIs('bank-accounts.*') ? '' : 'hidden' }} py-2 space-y-2">
                    <li>
                         <a href="{{ route('couriers.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('couriers.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Couriers List</a>
                    </li>
                    <li>
                         <a href="{{ route('couriers.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('couriers.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Courier</a>
                    </li>
                    <li>
                         <a href="{{ route('courier-receive.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('courier-receive.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Receive Courier Payment</a>
                    </li>
                    <li>
                         <a href="{{ route('courier-payments.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('courier-payments.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Courier Payments</a>
                    </li>
                    <li>
                         <a href="{{ route('bank-accounts.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('bank-accounts.index') || request()->routeIs('bank-accounts.edit') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Bank Accounts</a>
                    </li>
                    <li>
                         <a href="{{ route('bank-accounts.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('bank-accounts.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Bank Account</a>
                    </li>
                </ul>
            </li>

            <!-- Orders -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('orders.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-orders" data-collapse-toggle="dropdown-orders">
                        <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('orders.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Orders</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                <ul id="dropdown-orders" class="{{ request()->routeIs('orders.*') ? '' : 'hidden' }} py-2 space-y-2">
                    <li>
                         <a href="{{ route('orders.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('orders.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Order List</a>
                    </li>
                    <li>
                         <a href="{{ route('orders.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('orders.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Order</a>
                    </li>
                    <li>
                         <a href="{{ route('orders.call-list') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('orders.call-list') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Call List</a>
                    </li>
                    <li>
                         <a href="{{ route('orders.waybill.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('orders.waybill.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Waybill Print</a>
                    </li>
                </ul>
            </li>

            <!-- Purchases -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('purchases.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-purchases" data-collapse-toggle="dropdown-purchases">
                        <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('purchases.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Purchases</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                <ul id="dropdown-purchases" class="{{ request()->routeIs('purchases.*') ? '' : 'hidden' }} py-2 space-y-2">
                    <li>
                            <a href="{{ route('purchases.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('purchases.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Purchase List</a>
                    </li>
                    <li>
                            <a href="{{ route('purchases.create') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('purchases.create') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Add Purchase</a>
                    </li>
                </ul>
            </li>
                
                <!-- User Section -->
                <li>
                    <button type="button" class="flex items-center w-full p-2 text-base transition duration-75 rounded-lg group {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}" aria-controls="dropdown-users" data-collapse-toggle="dropdown-users">
                        <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Users</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <ul id="dropdown-users" class="{{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? '' : 'hidden' }} py-2 space-y-2">
                         @can('view users')
                        <li>
                            <a href="{{ route('admin.users.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('admin.users.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Users</a>
                        </li>
                        @endcan
                        @can('view roles')
                        <li>
                            <a href="{{ route('admin.roles.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('admin.roles.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Roles</a>
                        </li>
                        @endcan
                        @can('view permissions')
                        <li>
                            <a href="{{ route('admin.permissions.index') }}" class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-11 group {{ request()->routeIs('admin.permissions.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700' }}">Permissions</a>
                        </li>
                        @endcan
                    </ul>
                </li>



            <ul class="pt-4 mt-4 space-y-2 font-medium border-t border-gray-200 dark:border-gray-700">
                <!-- Reports -->
                <li>
                    <a href="{{ route('reports.index') }}" class="flex items-center p-2 transition duration-75 rounded-lg group {{ request()->routeIs('reports.index') ? 'bg-primary-50 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <svg class="flex-shrink-0 w-5 h-5 transition duration-75 {{ request()->routeIs('reports.index') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span class="ms-3">Reports</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Footer -->
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">System Date & Time</p>
                <p id="sidebar-system-date" class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-200">--</p>
                <p id="sidebar-system-time" class="text-sm font-bold text-gray-900 dark:text-white">--:--:--</p>
                <p id="sidebar-system-zone" class="text-[11px] text-gray-500 dark:text-gray-400">--</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="font-medium dark:text-white">
                    <div class="cursor-default">{{ Auth::user()->name }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Script to handle Sidebar Dark Toggle -->
<script>
    var themeToggleBtn = document.getElementById('theme-toggle');
    var darkIcon = document.getElementById('theme-toggle-dark-icon');
    var lightIcon = document.getElementById('theme-toggle-light-icon');

    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        lightIcon.classList.remove('hidden');
    } else {
        darkIcon.classList.remove('hidden');
    }

    themeToggleBtn.addEventListener('click', function() {
        // toggle icons inside button
        darkIcon.classList.toggle('hidden');
        lightIcon.classList.toggle('hidden');

        // if set via local storage previously
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }

        // if NOT set via local storage previously
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
    });

    function updateSidebarSystemClock() {
        const now = new Date();
        const englishLocale = 'en-GB';
        const dateElement = document.getElementById('sidebar-system-date');
        const timeElement = document.getElementById('sidebar-system-time');
        const zoneElement = document.getElementById('sidebar-system-zone');

        if (!dateElement || !timeElement || !zoneElement) {
            return;
        }

        dateElement.textContent = now.toLocaleDateString(englishLocale, {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });

        timeElement.textContent = now.toLocaleTimeString(englishLocale, {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });

        const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local Time';
        zoneElement.textContent = timeZone;
    }

    updateSidebarSystemClock();
    setInterval(updateSidebarSystemClock, 1000);
</script>
