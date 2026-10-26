<footer class="footer">
    <span>CuyoTech University · Student Services</span>
    <span>University records, connected.</span>
</footer>
</main>
</div>
<dialog class="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-message">
    <h2 id="confirm-title">Confirm action</h2>
    <p id="confirm-message"></p>
    <div class="actions">
        <button type="button" class="button secondary" data-cancel autofocus>Cancel</button>
        <button type="button" class="button" data-proceed>Confirm</button>
    </div>
</dialog>
<script>
    const profileMenu = document.querySelector('.profile-menu');
    document.addEventListener('click', event => {
        if (profileMenu && !profileMenu.contains(event.target)) profileMenu.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && profileMenu?.open) {
            profileMenu.open = false;
            profileMenu.querySelector('summary').focus();
        }
    });
    document.querySelectorAll('[data-clear-form]').forEach(button => {
        button.form.addEventListener('reset', () => setTimeout(() => {
            button.form.querySelectorAll('input:not([type=hidden]), textarea, select').forEach(field => {
                if (field.type === 'checkbox' || field.type === 'radio') field.checked = field.defaultChecked;
                else if (field.tagName === 'SELECT') {
                    field.selectedIndex = [...field.options].findIndex(option => option.defaultSelected);
                    if (field.selectedIndex < 0) field.selectedIndex = 0;
                } else field.value = '';
                field.removeAttribute('aria-invalid');
                field.removeAttribute('aria-describedby');
                field.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
            button.form.querySelectorAll('.field-error').forEach(message => message.remove());
            dismissToast('form-validation');
            button.form.querySelector('input:not([type=hidden])')?.focus();
        }, 0));
    });
    const menu = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    menu?.addEventListener('click', () => {
        menu.setAttribute('aria-expanded', String(sidebar.classList.toggle('open')));
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            menu.setAttribute('aria-expanded', 'false');
            menu.focus();
        }
    });
    const dialog = document.querySelector('.confirm-dialog');
    const proceed = dialog.querySelector('[data-proceed]');
    let pendingForm = null;
    let pendingButton = null;
    let approvedForm = null;

    function decisionStyle(form) {
        const decision = form.querySelector('[name="decision"], [name="clearance_status"], [name="is_active"]');
        const value = decision?.value;
        if (form.dataset.confirmLabel) return {
            style: form.dataset.confirmStyle || '',
            label: form.dataset.confirmLabel
        };
        if (['REJECTED', 'NOT_CLEARED', '0'].includes(value)) {
            return {
                style: 'danger',
                label: value === '0' ? 'Deactivate account' : 'Confirm rejection'
            };
        }
        if (['VERIFIED', 'CLEARED', '1'].includes(value)) {
            return {
                style: 'success',
                label: value === '1' ? 'Activate account' : value === 'CLEARED' ? 'Confirm clearance' : 'Verify payment'
            };
        }
        const action = form.querySelector('[name="action"]')?.value;
        if (action === 'reject') return {
            style: 'danger',
            label: 'Reject request'
        };
        if (['approve', 'ready'].includes(action)) return {
            style: 'success',
            label: action === 'approve' ? 'Approve request' : 'Mark ready'
        };
        if (action === 'release') return {
            style: 'success',
            label: 'Confirm release'
        };
        return {
            style: '',
            label: 'Confirm'
        };
    }
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        const decision = form.querySelector('[name="decision"], [name="clearance_status"], [name="is_active"]');
        if (decision) {
            const update = () => {
                const button = form.querySelector('button[type="submit"]');
                const state = decisionStyle(form);
                button.classList.remove('secondary', 'success', 'danger');
                if (state.style) button.classList.add(state.style);
            };
            decision.addEventListener('change', update);
            update();
        }
    });
    document.querySelectorAll('form[method="post"]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            let partial = false;
            if (form.hasAttribute('data-bulk-grades')) {
                const fields = [...form.querySelectorAll('[data-grade]')];
                const entered = fields.filter(field => field.value.trim() !== '').length;
                dismissToast('grade-validation');
                if (!entered) {
                    event.preventDefault();
                    showToast('No grades entered. Please enter at least one grade before saving.', 'error', 'grade-validation');
                    fields[0]?.focus();
                    return;
                }
                const changed = fields.some(field => {
                    const note = field.closest('tr').querySelector('.grade-remarks');
                    return field.value.trim() !== '' && (field.value.trim() !== field.dataset.savedGrade ||
                        (note?.value.trim() || '') !== field.dataset.savedRemarks);
                });
                if (!changed) {
                    event.preventDefault();
                    showToast('No changes to save. The entered grades and remarks match the saved records.', 'info', 'grade-validation');
                    return;
                }
                partial = entered < fields.length;
            }
            if (form.dataset.confirm && approvedForm !== form) {
                event.preventDefault();
                pendingForm = form;
                pendingButton = event.submitter;
                const state = decisionStyle(form);
                dialog.querySelector('#confirm-title').textContent = partial ? 'Some grades are blank. Continue?' : (form.dataset.confirmTitle || (state.label === 'Confirm' ? 'Confirm record change' : state.label));
                dialog.querySelector('#confirm-message').textContent = partial ? 'Entered grades will be saved. Blank fields will remain unencoded or retain their existing grades and remarks.' : form.dataset.confirm;
                proceed.className = 'button ' + state.style;
                proceed.textContent = partial ? 'Save Entered Grades' : state.label;
                dialog.showModal();
                return;
            }
            approvedForm = null;
            form.dataset.submitting = 'true';
            // Delay disabling until the browser has captured the submitter's value.
            const button = event.submitter;
            if (button) setTimeout(() => {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.textContent = form.dataset.confirmLabel === 'Sign Out' ? 'Signing out…' : 'Saving…';
            }, 0);
        });
    });
    dialog.querySelector('[data-cancel]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        const closedMenu = pendingButton?.closest('details');
        if (closedMenu && !closedMenu.open) closedMenu.querySelector('summary')?.focus();
        else pendingButton?.focus();
        pendingForm = null;
        pendingButton = null;
    });
    proceed.addEventListener('click', () => {
        const form = pendingForm;
        const button = pendingButton;
        dialog.close();
        if (form) {
            approvedForm = form;
            if (form.hasAttribute('data-bulk-grades')) form.querySelector('[name=partial_confirmed]').value = '1';
            if (button) form.requestSubmit(button);
            else form.requestSubmit();
            approvedForm = null;
        }
    });
    document.querySelectorAll('[data-bulk-grades]').forEach(form => form.addEventListener('input', () => {
        form.querySelector('[name=partial_confirmed]').value = '0';
        dismissToast('grade-validation');
    }));
    document.querySelectorAll('[data-account-form]').forEach(form => {
        const role = form.querySelector('[name=role]');
        const scope = form.querySelector('[name=access_scope]');
        const department = form.querySelector('[name=department_id]');
        const studentNumber = form.querySelector('[name=student_number]');

        function visibility(field, visible, required = false) {
            if (!field) return;
            field.disabled = !visible;
            field.required = visible && required;
            field.closest('label').hidden = !visible;
        }

        function updateRoles() {
            const student = role.value === 'STUDENT';
            visibility(studentNumber, student, true);
            visibility(form.querySelector('[name=academic_status]'), student, true);
            visibility(scope, !student, true);
            if (scope && !student) {
                const fixedScope = role.value === 'ADMIN' ? 'UNIVERSITY' : role.value === 'DEPARTMENT' ? 'DEPARTMENT' : '';
                [...scope.options].forEach(option => option.disabled = !!fixedScope && option.value !== fixedScope);
                if (fixedScope) scope.value = fixedScope;
            }
            visibility(department, student || scope?.value === 'DEPARTMENT', true);
        }
        role.addEventListener('change', updateRoles);
        scope?.addEventListener('change', updateRoles);
        updateRoles();
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted) location.reload(); // Restore usable forms after returning from a submitted page.
    });
    document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
</script>
<?php validation_script(); ?>
</body>

</html>