function goto_group(id)
{
    // Submit the form to go to the group listing page
    document.getElementById(id).submit();
}

// Avoid clicks on the "birthday links" firing the group card click event
$(".birthday_link").click(function(e) {
    e.stopPropagation();
});