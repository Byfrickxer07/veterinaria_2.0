document.querySelector('#sign-in').addEventListener('click', function() {
    document.querySelector('.container-form.register').classList.add('hide');
    document.querySelector('.container-form.login').classList.remove('hide');
});

document.querySelector('#sign-up').addEventListener('click', function() {
    document.querySelector('.container-form.login').classList.add('hide');
    document.querySelector('.container-form.register').classList.remove('hide');
});
