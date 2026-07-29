/**
 * AIA Alert Interceptor & Notice Wrapper
 * Core 2026 - Modernization Phase
 * 
 * Este script secuestra window.alert para usar SweetAlert2 con estética 
 * "Apple Liquid Glass" manteniendo la identidad corporativa de AIA.
 */

window.AIA = window.AIA || {};

(function() {
    /**
     * SweetAlert2 se sirve con el bundle `.all.min.js`, que al ejecutarse inyecta
     * su hoja completa en un <style> SIN capa. Una hoja sin capa gana a todas las
     * capas en declaraciones normales, así que el fondo blanco que el vendor le
     * pone a `.swal2-popup` dejaba inerte al adaptador (`adapters/sweetalert2.css`,
     * layer components) y los diálogos salían claros sobre la app oscura.
     *
     * Mismo defecto que 40d402c resolvió quitando el <link> crudo de select2, pero
     * aquí el duplicado no es un <link> que se pueda borrar de una vista: solo
     * existe en runtime. Lo capamos envolviéndolo en la capa que le corresponde.
     * El texto inyectado es byte a byte el mismo /public/vendor/sweetalert2.min.css
     * que el design system ya importa con layer(vendor), así que envolverlo en esa
     * capa no pierde ninguna regla ni cambia nada dentro del propio vendor.
     */
    const VENDOR_MARKER = '.swal2-popup';
    for (const style of document.querySelectorAll('head > style')) {
        // El vendor crea un <style> pelado; el atributo que ponemos abajo evita
        // reprocesarlo y descarta de paso cualquier <style> con dueño conocido.
        if (style.attributes.length > 0) continue;
        const css = style.textContent || '';
        if (!css.includes(VENDOR_MARKER)) continue;
        style.setAttribute('data-aia-capado', 'vendor');
        style.textContent = `@layer vendor {${css}}`;
    }

    // Interceptor de window.alert
    const nativeAlert = window.alert;
    window.alert = function(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Notificación del Sistema',
                html: message ? message.replace(/\\n|\n/g, '<br>') : '',
                icon: 'info',
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'aia-glass-title',
                    htmlContainer: 'aia-glass-content',
                    confirmButton: 'aia-glass-confirm-btn'
                },
                buttonsStyling: false,
                confirmButtonText: 'Entendido'
            });
        } else {
            nativeAlert(message);
        }
    };

    // 3. Wrapper AIA.Notice
    window.AIA.Notice = {
        toast: (options) => {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'aia-glass-title',
                    htmlContainer: 'aia-glass-content'
                },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            return Toast.fire(options);
        },

        confirm: function(messageOrOptions, title) {
            let options = {};
            if (typeof messageOrOptions === 'string') {
                options = { 
                    html: messageOrOptions.replace(/\\n|\n/g, '<br>'),
                    title: title || '¿Está seguro?'
                };
            } else {
                options = messageOrOptions;
                if (options.html) options.html = options.html.replace(/\\n|\n/g, '<br>');
            }

            const defaults = {
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container',
                    confirmButton: 'aia-glass-confirm-btn',
                    cancelButton: 'aia-glass-cancel-btn'
                },
                buttonsStyling: false,
                backdrop: true,
                allowOutsideClick: false
            };

            return Swal.fire({ ...defaults, ...options }).then(result => result.isConfirmed);
        },

        dialog: function(options = {}) {
            const defaults = {
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container',
                    confirmButton: 'aia-glass-confirm-btn',
                    cancelButton: 'aia-glass-cancel-btn'
                },
                buttonsStyling: false,
                backdrop: true
            };

            if (options.html) {
                options.html = options.html.replace(/\\n|\n/g, '<br>');
            }

            return Swal.fire({ ...defaults, ...options });
        },

        success: function(msg) {
            if ($('#save-status').length) {
                this.badge('success', msg);
                return;
            }
            return this.toast({
                icon: 'success',
                title: msg,
                customClass: {
                    popup: 'aia-glass-popup aia-toast-success',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container'
                }
            });
        },

        error: function(msg, title) {
            return Swal.fire({
                icon: 'error',
                title: title || 'Error detectado',
                html: msg ? msg.replace(/\\n|\n/g, '<br>') : '',
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container',
                    confirmButton: 'aia-glass-confirm-btn'
                },
                buttonsStyling: false
            });
        },

        info: function(msg) {
            return this.toast({
                icon: 'info',
                title: msg,
                customClass: {
                    popup: 'aia-glass-popup aia-toast-construction',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container'
                }
            });
        },

        warningToast: function(msg) {
            return this.toast({
                icon: 'warning',
                title: msg,
                customClass: {
                    popup: 'aia-glass-popup aia-toast-construction',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container'
                }
            });
        },

        warning: function(msg, title = 'Atención Requerida') {
            return Swal.fire({
                icon: 'warning',
                title: title,
                html: msg ? msg.replace(/\\n|\n/g, '<br>') : '',
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container',
                    confirmButton: 'aia-glass-confirm-btn'
                },
                buttonsStyling: false
            });
        },

        errorToast: function(msg) {
            return this.toast({
                icon: 'error',
                title: msg,
                customClass: {
                    popup: 'aia-glass-popup',
                    title: 'swal2-title',
                    htmlContainer: 'swal2-html-container'
                }
            });
        },

        badge: function(type, msg, duration = 3000) {
            if (type !== 'success') {
                if (type === 'error') this.errorToast(msg);
                else if (type === 'warning') this.warningToast(msg);
                return;
            }
            const $el = $('#save-status');
            if ($el.length) {
                $el.removeClass('badge-badge-hidden').text(msg || 'Guardado').fadeIn(200);
                setTimeout(() => {
                    $el.fadeOut(600, function() {
                        $(this).addClass('badge-badge-hidden');
                    });
                }, duration);
            } else {
                this.success(msg);
            }
        }
    };

})();
