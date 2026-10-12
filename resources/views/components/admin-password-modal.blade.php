{{--
    NEW: Admin password popup.

    Usage:  <x-admin-password-modal form="my-form-id" />

    When the given form is submitted (e.g. "Record Sale" / "Record Modification"), the
    submit is paused and this popup asks for an Admin password. On "Authorize" the
    password is copied into a hidden "admin_password" field and the form is submitted
    for real; the server then verifies it. "Cancel" closes the popup and submits nothing.
--}}
@props(['form'])

@once
    {{-- Popup styling (kept here so no CSS rebuild is needed). Uses the existing CSS variables. --}}
    <style>
        .admin-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.55);
        }
        .admin-modal-overlay[hidden] { display: none; }
        .admin-modal-box {
            width: 100%;
            max-width: 400px;
            padding: 1.5rem;
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--radius, 8px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
        }
        .admin-modal-box h3 { margin-bottom: 0.25rem; }
        .admin-modal-box p { margin-bottom: 1rem; font-size: 0.875rem; color: var(--text-muted, #64748b); }
        .admin-modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; }
    </style>
@endonce

<div id="{{ $form }}-admin-modal" class="admin-modal-overlay" hidden>
    <div class="admin-modal-box" role="dialog" aria-modal="true" aria-labelledby="{{ $form }}-admin-modal-title">
        <h3 id="{{ $form }}-admin-modal-title">Admin Authorization Required</h3>
        <p>Enter an administrator's password to authorize this action.</p>

        <label for="{{ $form }}-admin-modal-password" class="form-label">Admin Password</label>
        {{-- autocomplete="new-password" stops browsers offering to save/autofill this field --}}
        <input type="password" id="{{ $form }}-admin-modal-password" class="form-control" autocomplete="new-password">

        <div class="admin-modal-actions">
            <button type="button" class="btn btn-secondary" data-admin-cancel>Cancel</button>
            <button type="button" class="btn btn-primary" data-admin-confirm>Authorize</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var form = document.getElementById(@json($form));
        var modal = document.getElementById(@json($form . '-admin-modal'));
        if (!form || !modal) { return; }

        var input = modal.querySelector('input[type="password"]');
        var confirmed = false; // true only for the programmatic re-submit after the password was entered

        function openModal() {
            input.value = '';
            modal.hidden = false;
            input.focus();
        }

        function closeModal() {
            modal.hidden = true;
            input.value = '';
        }

        function confirmPassword() {
            if (input.value === '') { input.focus(); return; }

            // Copy the password into a hidden field so it is posted with the form.
            var hidden = form.querySelector('input[name="admin_password"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'admin_password';
                form.appendChild(hidden);
            }
            hidden.value = input.value;

            confirmed = true;
            closeModal();
            form.requestSubmit();
        }

        // Intercept the normal submit (browser validation of required fields has already passed).
        form.addEventListener('submit', function (e) {
            if (confirmed) { return; } // password collected - let this submit go through
            e.preventDefault();
            openModal();
        });

        modal.querySelector('[data-admin-confirm]').addEventListener('click', confirmPassword);
        modal.querySelector('[data-admin-cancel]').addEventListener('click', closeModal);

        // Enter = authorize, Escape = cancel
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); confirmPassword(); }
            if (e.key === 'Escape') { closeModal(); }
        });

        // Clicking the dark backdrop cancels
        modal.addEventListener('click', function (e) {
            if (e.target === modal) { closeModal(); }
        });

        // If the page is restored from the back/forward cache, require the password again.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                confirmed = false;
                var hidden = form.querySelector('input[name="admin_password"]');
                if (hidden) { hidden.value = ''; }
            }
        });
    })();
</script>
