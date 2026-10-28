


<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <!--begin::Menu wrapper-->
    <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
        <!--begin::Scroll wrapper-->
        <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" 
            data-kt-scroll="true"
            data-kt-scroll-activate="true" 
            data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
            data-kt-scroll-wrappers="#kt_app_sidebar_menu" 
            data-kt-scroll-offset="5px" 
            data-kt-scroll-save-state="true">
            
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
                
                <!-- Single Item Example (Dashboard) -->
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('dashboardcan ') ? ' active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-element-11 fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">Default Dashboard</span>
                    </a>
                </div>

                <!-- Section Heading -->
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Main Sections</span>
                    </div>
                </div>

                <!-- Simple Link Example -->
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('inquiries.*') ? ' active' : '' }}" href="{{ route('inquiries.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-profile-user fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Customer Inquiries</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('projects.*') ? ' active' : '' }}" href="{{ route('projects.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-folder fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Projects</span>
                    </a>
                </div>

                <!-- Estimating & BOQ Section -->
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Estimating & BOQ</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('material-master.*') ? ' active' : '' }}" href="{{ route('material-master.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-abstract-26 fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Material Master</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('rate-analysis.*') ? ' active' : '' }}" href="{{ route('rate-analysis.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-calculator fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Rate Analysis</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('procurement-dashboard.*') ? ' active' : '' }}" href="{{ route('procurement-dashboard.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-chart-pie-3 fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title">Procurement Dashboard</span>
                    </a>
                </div>

                <!-- Procurement & Supply Section -->
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Procurement & Supply</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('purchase-orders.*') ? ' active' : '' }}" href="{{ route('purchase-orders.index')}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-purchase fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Purchase Orders</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('suppliers.*') ? ' active' : '' }}" href="{{ route('suppliers.index')}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-truck fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Supplier Management</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('good-receive-notes.*') ? ' active' : '' }}" href="{{ route('good-receive-notes.index')}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-delivery-3 fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Good Receive Note</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('transfer-requests.*') ? ' active' : '' }}" href="{{ route('transfer-requests.index')}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-arrow-right-left fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Good Transfer Request</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('stock.*') ? ' active' : '' }}" href="{{ route('stock.index')}}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-abstract-26 fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Stock Management</span>
                    </a>
                </div>

                <!-- Dropdown / Accordion Example -->
                <div data-kt-menu-trigger="click" class="menu-item menu-accordion{{ request()->is('users.account') || request()->routeIs('users.type') || request()->routeIs('users.privilege') || request()->routeIs('users.*')  ? ' show' : '' }}">
                    <!-- Parent Link -->
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-address-book fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </span>
                        <span class="menu-title">Users</span>
                        <span class="menu-arrow"></span>
                    </span>

                    <!-- Submenu Items -->
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('users.account') ? ' active' : '' }}" href="{{ route('users.account') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">User Account</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('users.type') ? ' active' : '' }}" href="{{  route('users.type') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">User Type</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('users.privilege') ? ' active' : '' }}" href="{{  route('users.privilege') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">User Privilege</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ─── SITE MANAGEMENT SECTION ────────────────────────────────────────── --}}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Site Management</span>
                    </div>
                </div>

                <div data-kt-menu-trigger="click" class="menu-item menu-accordion{{ request()->routeIs('material-requests.*') ? ' show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-note-2 fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">Site Requests</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('material-requests.create') ? ' active' : '' }}" href="{{ route('material-requests.create') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">New Request</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('material-requests.pending') ? ' active' : '' }}" href="{{ route('material-requests.pending') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Pending Requests</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('material-requests.approved') ? ' active' : '' }}" href="{{ route('material-requests.approved') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Approved List</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('material-requests.index') && !request()->routeIs('material-requests.pending') && !request()->routeIs('material-requests.approved') && !request()->routeIs('material-requests.create') && !request()->routeIs('material-requests.received') ? ' active' : '' }}" href="{{ route('material-requests.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">All Requests</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link{{ request()->routeIs('material-requests.received') ? ' active' : '' }}" href="{{ route('material-requests.received') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Material Received</span>
                            </a>
                        </div>
                    </div>
                </div>


                {{-- ─── LABOR MANAGEMENT SECTION ────────────────────────────────────────── --}}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Labor Management</span>
                    </div>
                </div>

                {{-- Labor Categories --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('labor.categories.*') ? ' active' : '' }}" href="{{ route('labor.categories.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-people fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">Labor Categories</span>
                    </a>
                </div>

                {{-- Subcontractors --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('subcontractors.*') ? ' active' : '' }}" href="{{ route('subcontractors.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-badge fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">Subcontractors</span>
                    </a>
                </div>

                {{-- Subcontractor Bills --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('subcontractor-bills.*') ? ' active' : '' }}" href="{{ route('subcontractor-bills.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-bill fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                            </i>
                        </span>
                        <span class="menu-title">Subcontractor Bills</span>
                    </a>
                </div>

                {{-- Per-project labor features accessed from the Project detail page --}}
                {{-- (Labor Allocation, Daily Records, Work Orders, Cost Report) --}}

                {{-- ─── CUSTOMERS ────────────────────────────────────────────────────────── --}}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Customers</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('customers.*') ? ' active' : '' }}" href="{{ route('customers.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-profile-user fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Customer Master</span>
                    </a>
                </div>

                {{-- ─── MACHINERY MANAGEMENT ─────────────────────────────────────────────── --}}
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Machinery Management</span>
                    </div>
                </div>

                {{-- Machinery Master --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('machinery.*') ? ' active' : '' }}" href="{{ route('machinery.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-setting-3 fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                <span class="path4"></span><span class="path5"></span>
                            </i>
                        </span>
                        <span class="menu-title">Machinery Master</span>
                    </a>
                </div>

                {{-- Machinery Allocations --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('machinery-allocations.*') ? ' active' : '' }}" href="{{ route('machinery-allocations.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-calendar-tick fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Machinery Allocations</span>
                    </a>
                </div>

                {{-- Daily Machinery Logs --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('machinery-logs.*') ? ' active' : '' }}" href="{{ route('machinery-logs.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-note-2 fs-2">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span>
                            </i>
                        </span>
                        <span class="menu-title">Daily Machinery Logs</span>
                    </a>
                </div>

                {{-- Rental Bills --}}
                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('machinery-bills.*') ? ' active' : '' }}" href="{{ route('machinery-bills.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-bill fs-2">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span>
                                <span class="path5"></span><span class="path6"></span>
                            </i>
                        </span>
                        <span class="menu-title">Rental Bills</span>
                    </a>
                </div>

                <div class="menu-item">
                    <a class="menu-link{{ request()->routeIs('activity-logs.*') ? ' active' : '' }}" href="{{ route('activity-logs.index') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-profile-user fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Activity Logs</span>
                    </a>
                </div>



            </div>
            <!--end::Menu-->
        </div>
        <!--end::Scroll wrapper-->
    </div>
    <!--end::Menu wrapper-->
</div>