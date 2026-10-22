// HelpDesk Pro front-end
var commentCount = 0;
var saving = false;

$(document).ready(function () {

    // inline status change on tickets.php
    $('.status-change').change(function () {
        var $sel = $(this);
        $sel.attr('disabled', 'disabled');
        $.post('ajax/update_status.php', { id: $sel.attr('data-id'), status: $sel.val() }, function (data) {
            $sel.removeAttr('disabled');
            if (data == 'OK') {
                $sel.closest('tr').css('background', '#ffffcc');
                setTimeout(function () { $sel.closest('tr').css('background', ''); }, 1000);
            } else {
                alert('Error: ' + data);
            }
        });
    });

    // comments on ticket.php
    $('#comment-form').submit(function (e) {
        e.preventDefault();
        if (saving) return false;
        var body = $('#comment-form textarea[name=body]').val();
        if (body == '') { alert('Please enter a comment'); return false; }
        saving = true;
        $('#comment-status').html('<img src="images/spinner.gif" /> Saving...');
        $.post('ajax/add_comment.php', $(this).serialize(), function (html) {
            $('#comments').append(html);
            $('#comment-form textarea[name=body]').val('');
            $('#comment-status').html('Saved!');
            commentCount++;
            saving = false;
        });
        return false;
    });

    // customer autocomplete on ticket_edit.php
    if ($('#customer-search').length) {
        $('#customer-search').autocomplete({
            source: 'ajax/search_customers.php',
            minLength: 2,
            select: function (event, ui) {
                $('#customer_id').val(ui.item.id);
            }
        });
    }
});

function validateTicketForm() {
    if ($('#customer_id').val() == '') { alert('Pick a customer from the list'); return false; }
    if ($('#subject').val() == '') { alert('Subject is required'); return false; }
    return true;
}
