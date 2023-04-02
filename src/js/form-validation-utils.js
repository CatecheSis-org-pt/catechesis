
function telefone_valido(num)
{
    var phoneno = /^\d{9}$/;
    var internacional = /^\+\d{1,}[-\s]{0,1}\d{9}$/;
    return (num.match(phoneno) || num.match(internacional));
}

function email_valido(email)
{
    var pattern = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
    return (pattern.test(email));
}

function codigo_postal_valido(codigo, locale)
{
    var pattern = "";
    if(locale==="PT")
        pattern = /^[0-9]{4}\-[0-9]{3}\s\S+/;
    else if(locale==="BR")
        pattern = /^[0-9]{5}\-[0-9]{3}\s\S+/;

    return (pattern.test(codigo));
}


function data_valida(data)
{
    var pattern = /^[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}$/;
    return (pattern.test(data));
}