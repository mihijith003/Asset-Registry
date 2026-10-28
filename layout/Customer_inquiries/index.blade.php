@php
    $inquiryHeaders = ['Inquiry No', 'Customer Name', 'Contact', 'Project Type', 'Status', 'Actions'];
@endphp

<x-layouts.app :title="'Customer Inquiries'">

    <!-- Header -->
    <div class="container-fluid bg-white border-bottom shadow-sm mb-3">
    <div class="px-4 py-3 d-flex flex-column">

        <div class="d-flex align-items-end justify-content-between">
            <div class="d-flex align-items-center">
                <h1 class="mb-0 fw-bold">
                    Customer Inquiries
                </h1>
            </div>
        </div>

    </div>
</div>
    
    <!-- Create New Inquiry Modal -->
    <div class="modal fade" id="inquiryModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Customer Inquiry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">
                    <form id="inquiryForm" data-store-url="{{ route('inquiries.store') }}">
                        @csrf
                        <div class="row g-3">
                            <!-- Customer Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Customer Name*</label>
                                <input type="text" name="customer_name" class="form-control" autocomplete="off">
                                <div class="invalid-feedback">Please enter a customer name.</div>
                            </div>

                            <!-- Contact Number -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Contact Number*</label>
                                <input type="text" name="contact_no" class="form-control" placeholder="071 657 8718">
                                <div class="invalid-feedback">Please enter a contact number.</div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Email Address</label>
                                <input type="email" name="email" class="form-control">
                            </div>

                            <!-- Project Type -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Project Type*</label>
                                <select name="project_type" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Government">Government</option>
                                    <option value="Private">Private</option>
                                </select>
                            </div>

                            <!-- Payment Basis -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Payment Basis*</label>
                                <select name="payment_basis" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Stage-wise">Stage-wise</option>
                                    <option value="Payment Plan">Payment Plan</option>
                                </select>
                            </div>

                            <!-- Full Address -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Full Address*</label>
                                <input type="text" name="address" class="form-control">
                            </div>

                            <!-- Requirement Details -->
                            <div class="col-12">
                                <label class="form-label fw-bold small">Requirement Details</label>
                                <textarea name="requirement_details" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Modal Footer / Action Buttons -->
                        <div class="mt-4 d-flex justify-content-end border-top pt-3">
                            <button type="button" class="btn btn-light border me-2"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Save Inquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Inquiry Modal -->
    <div class="modal fade" id="editInquiryModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Customer Inquiry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">
                    <form id="editInquiryForm" data-base-url="{{ url('/inquiries') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_inquiry_id">
                        <div class="row g-3">
                            <!-- Inquiry No (Read-Only) -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Inquiry No</label>
                                <input type="text" id="edit_inquiry_no" class="form-control bg-light" readonly>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Status*</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="Pending">Pending</option>
                                    <option value="BOQ_Drafted">BOQ Drafted</option>
                                    <option value="Converted">Converted</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>

                            <!-- Project Type -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Project Type*</label>
                                <select name="project_type" id="edit_project_type" class="form-select" required>
                                    <option value="Government">Government</option>
                                    <option value="Private">Private</option>
                                </select>
                            </div>

                            <!-- Customer Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Customer Name*</label>
                                <input type="text" name="customer_name" id="edit_customer_name" class="form-control" autocomplete="off" required>
                                <div class="invalid-feedback">Please enter a customer name.</div>
                            </div>

                            <!-- Contact Number -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Contact Number*</label>
                                <input type="text" name="contact_no" id="edit_contact_no" class="form-control" placeholder="071 657 8718" required>
                                <div class="invalid-feedback">Please enter a contact number.</div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Email Address</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>

                            <!-- Payment Basis -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Payment Basis*</label>
                                <select name="payment_basis" id="edit_payment_basis" class="form-select" required>
                                    <option value="Stage-wise">Stage-wise</option>
                                    <option value="Payment Plan">Payment Plan</option>
                                    <option value="Government Standard">Government Standard</option>
                                </select>
                            </div>

                            <!-- Full Address -->
                            <div class="col-12">
                                <label class="form-label fw-bold small">Full Address*</label>
                                <input type="text" name="address" id="edit_address" class="form-control" required>
                            </div>

                            <!-- Requirement Details -->
                            <div class="col-12">
                                <label class="form-label fw-bold small">Requirement Details</label>
                                <textarea name="requirement_details" id="edit_requirement_details" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Modal Footer / Action Buttons -->
                        <div class="mt-4 d-flex justify-content-end border-top pt-3">
                            <button type="button" class="btn btn-light border me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="btnUpdateInquiry" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Update Inquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="viewInquiryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Customer Inquiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Inquiry No</span>
                            <span class="fw-bold" id="view_inquiry_no">-</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Status</span>
                            <div id="view_status">-</div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Project Type</span>
                            <span class="fw-bold" id="view_project_type">-</span>
                        </div>

                        <div class="col-md-6 border-top pt-2">
                            <span class="text-muted small d-block">Customer Name</span>
                            <span class="fw-bold" id="view_customer_name">-</span>
                        </div>
                        <div class="col-md-6 border-top pt-2">
                            <span class="text-muted small d-block">Contact Number</span>
                            <span class="fw-bold" id="view_contact_no">-</span>
                        </div>

                        <div class="col-md-6">
                            <span class="text-muted small d-block">Email Address</span>
                            <span class="fw-bold" id="view_email">-</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Payment Basis</span>
                            <span class="fw-bold" id="view_payment_basis">-</span>
                        </div>

                        <div class="col-12 border-top pt-2">
                            <span class="text-muted small d-block">Full Address</span>
                            <span class="fw-bold" id="view_address">-</span>
                        </div>

                        <div class="col-12">
                            <span class="text-muted small d-block">Requirement Details</span>
                            <div class="p-3 bg-light rounded" id="view_requirement_details" style="white-space: pre-wrap;">-</div>
                        </div>

                        <div class="col-md-6 border-top pt-2">
                            <span class="text-muted small d-block">Created At</span>
                            <span class="fw-semibold text-muted" id="view_created_at">-</span>
                        </div>
                        <div class="col-md-6 border-top pt-2">
                            <span class="text-muted small d-block">Last Updated</span>
                            <span class="fw-semibold text-muted" id="view_updated_at">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Search</label>
                    <input type="text" class="form-control" placeholder="Search inquiries...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Project Type</label>
                    <select class="form-select">
                        <option value="">All Types</option>
                        <option value="Government">Government</option>
                        <option value="Private">Private</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Status</label>
                    <select class="form-select">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Converted">Converted</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-secondary w-100">
                        <i class="fa-solid fa-filter me-2"></i>Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-end mb-4">
                <a href="#" class="btn btn-primary" id="btnOpenModal">
                    <i class="fa-solid fa-plus me-1"></i> New Inquiry
                </a>
            </div>
            <x-table :headers="$inquiryHeaders" id="inquiryTable"></x-table>
        </div>
    </div>

</x-layouts.app>



<script>

    $(document).ready(function () {
        $('#uploadDocumentModal').on('show.bs.modal', function (event) {
            let button = $(event.relatedTarget);
            let inquiryId = button.data('inquiry-id');
            $(this).find('#doc_inquiry_id').val(inquiryId);
        });
    });
    window.InquiryRoutes = {
        index: "{{ route('inquiries.index') }}",
        destroy: "{{ route('inquiries.destroy', ':id') }}",
        boqCreate: "{{ route('boqs.create', ':id') }}"
    };


</script>