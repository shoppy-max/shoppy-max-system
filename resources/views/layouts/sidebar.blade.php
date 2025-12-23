<aside class="w-64 bg-gray-800 text-white min-h-screen flex flex-col">
    <div class="h-16 flex items-center justify-center border-b border-gray-700">
        <h1 class="text-xl font-bold">ShoppyMax</h1>
    </div>

    <nav class="flex-1 px-2 py-4 space-y-2">
        <!-- Dashboard (Removed as per user request, but keeping a home link usually best practice, user said "no need dashboard", so omitted) -->
        
        <!-- Contact Management -->
        <div x-data="{ open: {{ request()->routeIs('customers.*') || request()->routeIs('suppliers.*') || request()->routeIs('resellers.index') || request()->routeIs('resellers.show') || request()->routeIs('resellers.edit') || request()->routeIs('resellers.create') || request()->routeIs('cities.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Contact Management
                </span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-12 mt-2 space-y-1">
                <a href="{{ route('customers.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('customers.*') ? 'bg-gray-700 text-white' : '' }}">Customers</a>
                <a href="{{ route('suppliers.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('suppliers.*') ? 'bg-gray-700 text-white' : '' }}">Supplier</a>
                <a href="{{ route('resellers.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('resellers.index') || request()->routeIs('resellers.show') || request()->routeIs('resellers.edit') || request()->routeIs('resellers.create') ? 'bg-gray-700 text-white' : '' }}">Reseller</a>
                <a href="{{ route('cities.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('cities.*') ? 'bg-gray-700 text-white' : '' }}">City</a>
            </div>
        </div>

        <!-- Reseller Management -->
        <div x-data="{ open: {{ request()->routeIs('resellers.dashboard') || request()->routeIs('resellers.users.*') || request()->routeIs('resellers.payments.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Reseller Section
                </span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-12 mt-2 space-y-1">
                <a href="{{ route('resellers.dashboard') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('resellers.dashboard') ? 'bg-gray-700 text-white' : '' }}">Dashboard</a>
                <a href="{{ route('resellers.users.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('resellers.users.*') ? 'bg-gray-700 text-white' : '' }}">User Details</a>
                <a href="{{ route('resellers.payments.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('resellers.payments.index') || request()->routeIs('resellers.payments.create') ? 'bg-gray-700 text-white' : '' }}">Payments</a>
                <a href="{{ route('resellers.payments.dues') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('resellers.payments.dues') ? 'bg-gray-700 text-white' : '' }}">Due Payments</a>
            </div>
        </div>

        <!-- Seller Management -->
        <div x-data="{ open: {{ request()->routeIs('sellers.dashboard') || request()->routeIs('sellers.users.*') || request()->routeIs('sellers.targets.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Seller Section
                </span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-12 mt-2 space-y-1">
                <a href="{{ route('sellers.dashboard') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('sellers.dashboard') ? 'bg-gray-700 text-white' : '' }}">Dashboard</a>
                <a href="{{ route('sellers.users.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('sellers.users.*') ? 'bg-gray-700 text-white' : '' }}">User Details</a>
                <a href="{{ route('sellers.targets.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('sellers.targets.*') ? 'bg-gray-700 text-white' : '' }}">Targets</a>
            </div>
        </div>

        <!-- Placeholders for other requested modules -->
        <!-- Product Management -->
        <div x-data="{ open: {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('sub-categories.*') || request()->routeIs('attributes.*') || request()->routeIs('units.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Products
                </span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-12 mt-2 space-y-1">
                <a href="{{ route('products.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('products.*') ? 'bg-gray-700 text-white' : '' }}">Product List</a>
                <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('categories.*') ? 'bg-gray-700 text-white' : '' }}">Categories</a>
                <a href="{{ route('sub-categories.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('sub-categories.*') ? 'bg-gray-700 text-white' : '' }}">Sub Categories</a>
                <a href="{{ route('attributes.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('attributes.*') ? 'bg-gray-700 text-white' : '' }}">Attributes</a>
                <a href="{{ route('units.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('units.*') ? 'bg-gray-700 text-white' : '' }}">Units</a>
            </div>
        </div>
        <!-- Courier Management -->
        <div x-data="{ open: {{ request()->routeIs('couriers.*') || request()->routeIs('courier-payments.*') ? 'true' : 'false' }} }">
             <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                 <span class="flex items-center">
                     <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 012-2h5a2 2 0 012 2"></path></svg>
                     Courier Service
                 </span>
                 <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
             </button>
             <div x-show="open" class="pl-12 mt-2 space-y-1">
                 <a href="{{ route('couriers.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('couriers.*') ? 'bg-gray-700 text-white' : '' }}">Courier List</a>
                 <a href="{{ route('courier-payments.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('courier-payments.index') ? 'bg-gray-700 text-white' : '' }}">Payment Ledger</a>
                 <a href="{{ route('courier-payments.create') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('courier-payments.create') ? 'bg-gray-700 text-white' : '' }}">Receive Payment</a>
             </div>
        </div>
        <!-- Order Management -->
        <div x-data="{ open: {{ request()->routeIs('orders.*') || request()->routeIs('reseller-orders.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Order Section
                </span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-12 mt-2 space-y-1">
                <a href="{{ route('orders.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('orders.index') ? 'bg-gray-700 text-white' : '' }}">Order List</a>
                <a href="{{ route('reseller-orders.create') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('reseller-orders.create') ? 'bg-gray-700 text-white' : '' }}">Add Reseller Orders</a>
                <a href="{{ route('orders.create') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('orders.create') ? 'bg-gray-700 text-white' : '' }}">Add Orders</a>
                <a href="{{ route('orders.call-list') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('orders.call-list') ? 'bg-gray-700 text-white' : '' }}">Call List</a>
                <a href="{{ route('orders.waybill.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('orders.waybill.*') ? 'bg-gray-700 text-white' : '' }}">Waybill Print</a>
                <a href="{{ route('orders.packing.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('orders.packing.*') ? 'bg-gray-700 text-white' : '' }}">Packing / Scanner</a>
            </div>
        </div>
        <!-- Purchase Management -->
        <div x-data="{ open: {{ request()->routeIs('purchases.*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg focus:outline-none">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    Purchase Section
                </span>
                <svg :class="{'rotate-180': open}" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" class="pl-12 mt-2 space-y-1">
                <a href="{{ route('purchases.index') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('purchases.index') ? 'bg-gray-700 text-white' : '' }}">Purchase List</a>
                <a href="{{ route('purchases.create') }}" class="block px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg {{ request()->routeIs('purchases.create') ? 'bg-gray-700 text-white' : '' }}">Add Purchase</a>
                <!-- Future/Placeholder for Payments/Returns if needed separately -->
            </div>
        </div>
        <!-- Reports -->
        <a href="{{ route('reports.index') }}" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg flex items-center">
             <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Reports
        </a>
         <a href="#" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 rounded-lg flex items-center">
             <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Settings
        </a>
    </nav>
</aside>
