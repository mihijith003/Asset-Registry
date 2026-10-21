<x-app-layout>
    <x-slot name="header">Master Files</x-slot>

    @php
        $activeTab = session('active_tab');

        if (! $activeTab) {
            $hasErrors = isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag;
            if ($hasErrors && $errors->assetType->any()) {
                $activeTab = 'asset-types';
            } elseif ($hasErrors && $errors->subType->any()) {
                $activeTab = 'sub-types';
            } elseif ($hasErrors && $errors->brand->any()) {
                $activeTab = 'brands';
            } else {
                $activeTab = 'asset-types';
            }
        }
    @endphp

    {{-- Bootstrap nav-tabs header --}}
    <ul class="nav nav-tabs" id="masterFilesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'asset-types' ? 'active' : '' }}"
                    id="asset-types-tab"
                    type="button"
                    role="tab"
                    data-tab-target="asset-types"
                    aria-controls="asset-types"
                    aria-selected="{{ $activeTab === 'asset-types' ? 'true' : 'false' }}">
                Asset Types
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'sub-types' ? 'active' : '' }}"
                    id="sub-types-tab"
                    type="button"
                    role="tab"
                    data-tab-target="sub-types"
                    aria-controls="sub-types"
                    aria-selected="{{ $activeTab === 'sub-types' ? 'true' : 'false' }}">
                Sub Types
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'brands' ? 'active' : '' }}"
                    id="brands-tab"
                    type="button"
                    role="tab"
                    data-tab-target="brands"
                    aria-controls="brands"
                    aria-selected="{{ $activeTab === 'brands' ? 'true' : 'false' }}">
                Brands
            </button>
        </li>
    </ul>

    {{-- Tab content panes --}}
    <div class="tab-content" id="masterFilesTabsContent">
        <div class="tab-pane {{ $activeTab === 'asset-types' ? 'active' : '' }}"
             id="pane-asset-types"
             role="tabpanel"
             aria-labelledby="asset-types-tab">
            @include('master-files.partials.asset-types')
        </div>

        <div class="tab-pane {{ $activeTab === 'sub-types' ? 'active' : '' }}"
             id="pane-sub-types"
             role="tabpanel"
             aria-labelledby="sub-types-tab">
            @include('master-files.partials.sub-types')
        </div>

        <div class="tab-pane {{ $activeTab === 'brands' ? 'active' : '' }}"
             id="pane-brands"
             role="tabpanel"
             aria-labelledby="brands-tab">
            @include('master-files.partials.brands')
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tabButtons = document.querySelectorAll('#masterFilesTabs button[data-tab-target]');
            var tabPanes = document.querySelectorAll('#masterFilesTabsContent > .tab-pane');

            tabButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var targetId = button.getAttribute('data-tab-target');

                    tabButtons.forEach(function (btn) {
                        btn.classList.remove('active');
                        btn.setAttribute('aria-selected', 'false');
                    });

                    tabPanes.forEach(function (pane) {
                        pane.classList.remove('active');
                    });

                    button.classList.add('active');
                    button.setAttribute('aria-selected', 'true');

                    var targetPane = document.getElementById('pane-' + targetId);
                    if (targetPane) {
                        targetPane.classList.add('active');
                    }
                });
            });
        });
    </script>
</x-app-layout>
