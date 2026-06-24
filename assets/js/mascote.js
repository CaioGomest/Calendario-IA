(function () {
  function botSVG(opts) {
    var stroke = opts.stroke, eye = opts.eye, pupil = opts.pupil, blush = opts.blush;
    var pupils = pupil
      ? '<circle cx="25" cy="30.5" r="1.7" fill="' + pupil + '"/><circle cx="39" cy="30.5" r="1.7" fill="' + pupil + '"/>'
      : '';
    var cheeks = blush
      ? '<circle cx="19.5" cy="36" r="2.6" fill="' + blush + '"/><circle cx="44.5" cy="36" r="2.6" fill="' + blush + '"/>'
      : '';
    return '<svg class="bot-svg" viewBox="0 0 64 64" fill="none" stroke="' + stroke + '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">'
      + '<g class="bot-antenna">'
      +   '<line x1="22" y1="13" x2="16" y2="4"/><circle cx="15" cy="3" r="2.2" fill="' + stroke + '" stroke="none"/>'
      +   '<line x1="42" y1="13" x2="48" y2="4"/><circle cx="49" cy="3" r="2.2" fill="' + stroke + '" stroke="none"/>'
      + '</g>'
      + '<rect x="9" y="13" width="46" height="36" rx="15"/>'
      + '<g class="bot-eyes">'
      +   '<circle cx="25" cy="30" r="3.8" fill="' + eye + '" stroke="none"/>'
      +   '<circle cx="39" cy="30" r="3.8" fill="' + eye + '" stroke="none"/>'
      +   pupils
      + '</g>'
      + cheeks
      + '<path d="M26 39 q6 5 12 0"/>'
      + '<line x1="32" y1="49" x2="32" y2="55"/><circle cx="32" cy="58.5" r="3.4"/>'
      + '</svg>';
  }

  var MODES = {
    ink:    { stroke: '#1f2733', eye: '#2f6bd6', pupil: null,      blush: null },
    onblue: { stroke: '#ffffff', eye: '#ffffff', pupil: '#2f6bd6', blush: null },
    white:  { stroke: '#ffffff', eye: '#9ec3ff', pupil: '#2f6bd6', blush: null }
  };

  function render(root) {
    (root || document).querySelectorAll('[data-bot]').forEach(function (el) {
      if (el.dataset.botDone) return;
      var mode = MODES[el.getAttribute('data-bot')] || MODES.ink;
      el.innerHTML = botSVG(mode);
      var size = el.getAttribute('data-size');
      if (size) {
        var svg = el.querySelector('svg');
        svg.style.width = size + 'px';
        svg.style.height = size + 'px';
      }
      el.dataset.botDone = '1';
    });
  }

  window.CalBot = { render: render };
  if (document.readyState !== 'loading') render();
  else document.addEventListener('DOMContentLoaded', function () { render(); });
})();
