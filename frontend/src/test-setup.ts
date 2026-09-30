import '@testing-library/jest-dom';

// jsdom no implementa `HTMLDialogElement.showModal()/close()`. Este stub reproduce lo observable
// (atributo `open` y evento `close`) para poder probar componentes que abren `<dialog>` como modal.
if (typeof HTMLDialogElement !== 'undefined' && typeof HTMLDialogElement.prototype.showModal !== 'function') {
  HTMLDialogElement.prototype.showModal = function showModal(this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.close = function close(this: HTMLDialogElement) {
    if (!this.hasAttribute('open')) return;
    this.removeAttribute('open');
    this.dispatchEvent(new Event('close'));
  };
}
