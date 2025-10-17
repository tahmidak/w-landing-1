
jQuery(document).ready(function($) {
    // Handle Contact Form
    $(document).on('submit', '.contact-form', function(e) {
        e.preventDefault();
        submitForm(this, 'contact');
    });

    // Handle Quote Form
    $(document).on('submit', '.quote-form', function(e) {
        e.preventDefault();
        submitForm(this, 'quote');
    });

    function submitForm(form, formType) {
        const $form = $(form);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.text();

        // Get form data
        const formData = {
            first_name: $form.find('input[placeholder*="First Name"]').val(),
            last_name: $form.find('input[placeholder*="Last Name"]').val(),
            email: $form.find('input[placeholder*="Email"]').val(),
            phone: $form.find('input[placeholder*="Telephone"]').val(),
            company: $form.find('input[placeholder*="Company"]').val(),
            message: $form.find('textarea[placeholder*="Message"]').val(),
            action: formType === 'contact' ? 'submit_contact_form' : 'submit_quote_form',
            nonce: formType === 'contact' ? ajax_object.contact_nonce : ajax_object.quote_nonce
        };

        // Disable button and show loading state
        $submitBtn.prop('disabled', true).text('Sending...');

        // AJAX request
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showAlert($form, response.data, 'success');
                    $form[0].reset();
                } else {
                    showAlert($form, response.data, 'error');
                }
            },
            error: function() {
                showAlert($form, 'An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    }

    function showAlert($form, message, type) {
        // Remove existing alerts
        $form.find('.alert').remove();

        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;

        $form.prepend(alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $form.find('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }
});