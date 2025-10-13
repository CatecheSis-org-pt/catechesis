
function telefone_valido(num, locale)
{
    var phoneno = '';

    if(locale==="PT")
        phoneno = /^(\+\d{1,}[-\s]{0,1})?\d{9}$/;
    else if(locale==="BR")
        phoneno = /^(\+\d{1,}[-\s]{0,1})?\s*\(?(\d{2}|\d{0})\)?[-. ]?(\d{5}|\d{4})[-. ]?(\d{4})[-. ]?\s*$/;

    return !!num.match(phoneno);
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

function nif_valido(nif)
{
    // Remove espaços e caracteres não numéricos
    nif = (nif || "").toString().replace(/\D/g, '');

    // Verifica se o NIF tem 9 dígitos
    if (nif.length !== 9) {
        return false;
    }

    // Converte o NIF em um array de dígitos
    const digitos = nif.split('').map(Number);

    // Calcula o dígito de controlo (PT): sum(d1*9 + d2*8 + ... + d8*2)
    const soma = digitos.slice(0, 8).reduce((acc, curr, index) => acc + curr * (9 - index), 0);

    let check = 11 - (soma % 11);
    if (check >= 10) check = 0;

    // Verifica se o dígito de controlo está correto
    return check === digitos[8];
}