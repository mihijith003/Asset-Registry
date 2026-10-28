<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
<head>
    <title>{{ $title ?? config('company.company', 'ERAV') }} | Nethu Builders</title>
    
    <!-- Vite Assets (Original logic preserved) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" />

    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link rel="preload" href="{{ asset('plugins/global/fonts/keenicons/keenicons-duotone.woff') }}" as="font" type="font/woff" crossorigin="anonymous">
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle (Metronic)-->
    <link href="{{ asset('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->

    <!--begin::Vendor Stylesheets (Merged from both projects)-->
    <link href="{{ asset('plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    
    <!-- Original DataTables & Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    
    <!-- Original Specific App CSS (Migrated to resources/css/app.css via Vite) -->
    <link href="{{ asset('css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet"/>
    <!-- <link href="{{ asset('css/styles.css') }}" rel="stylesheet"/> -->
    <!--end::Vendor Stylesheets-->

    <style>
        
    </style>

    {{-- Page-specific stylesheets pushed by child views --}}
    @stack('styles')
</head>
<!--end::Head-->

<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <!--begin::Theme mode setup-->
    <script>
        var defaultThemeMode = "light"; var themeMode; if (document.documentElement) { if (document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if (localStorage.getItem("data-bs-theme") !== null) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }
    </script>
    <!--end::Theme mode setup-->

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <!--begin::Header-->
            <div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-minimize" data-kt-sticky-offset="{default: '200px', lg: '0'}" data-kt-sticky-animation="false">
                <x-layouts.partials.navbar />
            </div>
            <!--end::Header-->

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Sidebar-->
                <div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
                    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
                        <a href="{{ url('/') }}" class="d-flex align-items-center gap-2">
                            <img alt="Logo" src="{{ asset('images/logo.png') }}" class="h-25px app-sidebar-logo-default" />
                            <span class="fw-bold fs-4 text-white app-sidebar-logo-default">Nethu Builders</span>
                        </a>
                        <div id="kt_app_sidebar_toggle" class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="app-sidebar-minimize">
                            <i class="ki-duotone ki-black-left-line fs-3 rotate-180"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                    </div>
                    <x-layouts.partials.sidebar />
                </div>
                <!--end::Sidebar-->

                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>

                    <!--begin::Footer-->
                    <div id="kt_app_footer" class="app-footer">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
                            <div class="text-gray-900 order-2 order-md-1">
                                <span class="text-muted fw-semibold me-1">{{ date('Y') }} &copy;</span>
                                <a href="https://erav.lk" target="_blank" class="text-gray-800 text-hover-primary">eRav Technology</a>
                            </div>
                        </div>
                    </div>
                    <!--end::Footer-->
                </div>
                <!--end::Main-->
            </div>
        </div>
    </div>

    <!--begin::Javascript-->
    <!-- 1. jQuery (First) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- 2. Metronic Core -->
    <script src="{{ asset('plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('js/scripts.bundle.js') }}"></script>

    <!-- 3. DataTables & Export Tools (Merged from your original list) -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <!-- SheetJS Library -->
    <!-- Replace standard SheetJS with xlsx-js-style for styling support -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <!-- 4. Original App Logic Scripts -->
    <script src="{{ asset('js/global.js') }}"></script>
    <script src="{{ asset('js/inquiries.js') }}"></script>
    <script src="{{ asset('js/boq-create.js') }}"></script>
    <script src="{{ asset('js/boq-variation.js') }}"></script>
    <script src="{{ asset('js/boq-variation-enhanced.js') }}"></script>
    <script src="{{ asset('js/boq-lazy-load.js') }}"></script>
    <script src="{{ asset('js/suppliers.js') }}"></script>
    <script src="{{ asset('js/purchase-orders.js') }}"></script>
    <script src="{{ asset('js/grn.js') }}"></script>

    <!-- 5. Inline Initialization Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'OK'
                });
            @endif
        });

        $(document).ready(function() {
            // Select2 init (Standardized)
            if ($.fn.select2) {
                $('.select2-init').select2({ width: '100%' });
            }

            // AJAX Prefilter for Base URL
            const baseUrl = $('meta[name="base-url"]').attr('content');
            if (baseUrl) {
                $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
                    if (options.url.startsWith('/')) {
                        options.url = baseUrl + options.url;
                    }
                });
            }
        });
    </script>

    @stack('scripts')
    @yield('scripts')
    <!--end::Javascript-->
</body>
</html>