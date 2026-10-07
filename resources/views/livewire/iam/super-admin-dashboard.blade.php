<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6 border-t-4 border-indigo-500">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Super Admin Dashboard</h2>
        <p class="text-gray-600">Welcome to the Albareka Business Management Platform. From here you can manage all business lines, branches, and platform configurations.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Businesses Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Businesses</h3>
            </div>
            <p class="text-gray-600 mb-4">Manage Factories, Bookstores, and other business lines.</p>
            <button class="w-full bg-blue-50 text-blue-600 font-medium py-2 rounded border border-blue-200 hover:bg-blue-100 transition">View Businesses</button>
        </div>

        <!-- Branches Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-green-100 p-3 rounded-full text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Branches</h3>
            </div>
            <p class="text-gray-600 mb-4">Configure branch locations and assign managers.</p>
            <button class="w-full bg-green-50 text-green-600 font-medium py-2 rounded border border-green-200 hover:bg-green-100 transition">View Branches</button>
        </div>

        <!-- IAM Card -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4 mb-4">
                <div class="bg-purple-100 p-3 rounded-full text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Users & Roles</h3>
            </div>
            <p class="text-gray-600 mb-4">Manage RBAC scoped to specific businesses or branches.</p>
            <button class="w-full bg-purple-50 text-purple-600 font-medium py-2 rounded border border-purple-200 hover:bg-purple-100 transition">Manage Access</button>
        </div>
    </div>
</div>
