/* Общие скрипты сайта «Корочки.есть». Каждый блок включается только если нужный элемент есть на странице. */
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Слайдер на главной ---------- */
    var slider = document.getElementById('slider');
    if (slider) {
        var slides = slider.querySelectorAll('.slide');
        var dots   = slider.querySelectorAll('.dot');
        var current = 0, timer = null;

        function show(n) {
            current = (n + slides.length) % slides.length;
            slides.forEach(function (s, i) { s.classList.toggle('active', i === current); });
            dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
        }
        function start() { stop(); timer = setInterval(function () { show(current + 1); }, 5000); }
        function stop()  { if (timer) { clearInterval(timer); timer = null; } }

        slider.querySelector('.slider-btn-prev').addEventListener('click', function () { show(current - 1); start(); });
        slider.querySelector('.slider-btn-next').addEventListener('click', function () { show(current + 1); start(); });
        dots.forEach(function (d) {
            d.addEventListener('click', function () { show(parseInt(d.dataset.index, 10)); start(); });
        });
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
        if (slides.length > 1) { start(); }
    }

    /* ---------- Маска телефона 8(XXX)XXX-XX-XX ---------- */
    document.querySelectorAll('[data-phone-mask]').forEach(function (input) {
        function format(digits) {
            if (!digits) { return ''; }
            if (digits[0] === '7') { digits = '8' + digits.slice(1); }
            else if (digits[0] !== '8') { digits = '8' + digits; }
            digits = digits.slice(0, 11);
            var out = digits[0];
            if (digits.length > 1) { out += '(' + digits.slice(1, 4); }
            if (digits.length > 4) { out += ')' + digits.slice(4, 7); }
            if (digits.length > 7) { out += '-' + digits.slice(7, 9); }
            if (digits.length > 9) { out += '-' + digits.slice(9, 11); }
            return out;
        }
        var prev = input.value;
        input.addEventListener('input', function (e) {
            var digits = input.value.replace(/\D/g, '');
            var prevDigits = prev.replace(/\D/g, '');
            // Если стёрли скобку или дефис — удаляем последнюю цифру, иначе маска вернёт символ обратно
            if (e.inputType && e.inputType.indexOf('delete') === 0 && digits === prevDigits) {
                digits = digits.slice(0, -1);
            }
            input.value = format(digits);
            prev = input.value;
        });
    });

    /* ---------- Звёзды в форме отзыва ---------- */
    document.querySelectorAll('[data-stars-input]').forEach(function (box) {
        var stars  = box.querySelectorAll('.star');
        var hidden = box.parentNode.querySelector('input[name="stars"]');
        function paint(n, cls) {
            stars.forEach(function (s) {
                s.classList.toggle(cls, parseInt(s.dataset.value, 10) <= n);
            });
        }
        stars.forEach(function (s) {
            s.addEventListener('mouseover', function () { paint(parseInt(s.dataset.value, 10), 'hover'); });
            s.addEventListener('click', function () {
                hidden.value = s.dataset.value;
                paint(parseInt(s.dataset.value, 10), 'active');
            });
        });
        box.addEventListener('mouseleave', function () { paint(0, 'hover'); });
    });

    /* ---------- Счётчик символов ---------- */
    document.querySelectorAll('textarea[data-counter]').forEach(function (ta) {
        var out = document.getElementById(ta.dataset.counter);
        if (!out) { return; }
        ta.addEventListener('input', function () { out.textContent = ta.value.length; });
    });

    /* ---------- Подтверждение опасных действий ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.dataset.confirm)) { e.preventDefault(); }
        });
    });
});
