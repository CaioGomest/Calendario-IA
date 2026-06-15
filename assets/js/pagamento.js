$(function () {
  $('.js-cartao-numero').on('input', function () {
    var digitos = $(this).val().replace(/\D/g, '').slice(0, 16);
    $(this).val(digitos.replace(/(\d{4})(?=\d)/g, '$1 '));
  });

  $('.js-vencimento').on('input', function () {
    var digitos = $(this).val().replace(/\D/g, '').slice(0, 4);
    if (digitos.length > 2) {
      digitos = digitos.slice(0, 2) + '/' + digitos.slice(2);
    }
    $(this).val(digitos);
  });

  $('.js-cvc').on('input', function () {
    $(this).val($(this).val().replace(/\D/g, '').slice(0, 4));
  });
});
