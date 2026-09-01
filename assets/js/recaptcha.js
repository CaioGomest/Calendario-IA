function obtemTokenRecaptcha(acao) {
    return new Promise(function (resolve) {
        if (typeof grecaptcha === 'undefined' || !window.RECAPTCHA_SITE_KEY) {
            resolve('');
            return;
        }
        grecaptcha.ready(function () {
            grecaptcha.execute(window.RECAPTCHA_SITE_KEY, { action: acao })
                .then(resolve)
                .catch(function () { resolve(''); });
        });
    });
}
