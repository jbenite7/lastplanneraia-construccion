/**
 * AIA Alert Interceptor & Notice Wrapper
 * Core 2026 - Modernization Phase
 * 
 * Este script secuestra window.alert para usar SweetAlert2 con estética 
 * "Apple Liquid Glass" manteniendo la identidad corporativa de AIA.
 */

window.AIA = window.AIA || {};

(function() {
    // 1. Estilos Base para Liquid Glass AIA
    const injectStyles = () => {
        const style = document.createElement('style');
        style.innerHTML = `
            /* Reglas de Blindaje de Contraste para Banners de Estado Internos (Regla de Oro: Fondos Oscuros = Textos Claros) */
            #save-status.badge-success, #save-error.badge-danger, .pi-status-badges .badge, .pg-status-badges .badge {
                backdrop-filter: blur(16px) saturate(180%) !important;
                -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
                color: #ffffff !important; 
                text-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
                padding: 10px 20px !important;
                border-radius: 12px !important;
                font-family: 'Montserrat', sans-serif !important;
                font-weight: 700 !important;
                display: inline-flex !important;
                align-items: center !important;
                border: 1px solid rgba(255,255,255,0.25) !important;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2) !important;
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
                transform: translateY(0);
                opacity: 1;
            }
            #save-status.badge-success {
                background: linear-gradient(135deg, rgba(26, 86, 51, 0.95), rgba(30, 100, 60, 0.85)) !important;
            }
            #save-error.badge-danger {
                background: linear-gradient(135deg, rgba(229, 57, 53, 0.95), rgba(183, 28, 28, 0.85)) !important;
            }
            .badge-badge-hidden {
                opacity: 0 !important;
                transform: translateY(-10px) !important;
                pointer-events: none !important;
            }

            .aia-glass-popup {
                background: rgba(22, 22, 24, 0.75) !important; /* Obsidian Dark Glass V2026.3 */
                backdrop-filter: blur(28px) saturate(220%) !important;
                -webkit-backdrop-filter: blur(28px) saturate(220%) !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
                border-radius: 24px !important;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6) !important;
                font-family: 'Inter', sans-serif !important;
            }
            /* Refuerzo de Contraste Radical: Forzar Textos Claros */
            .aia-glass-popup .swal2-title {
                font-family: 'Montserrat', sans-serif !important;
                font-weight: 700 !important;
                color: #ffffff !important; 
                font-size: 1.45rem !important;
                margin-top: 1rem !important;
            }
            .aia-glass-popup .swal2-html-container, 
            .aia-glass-popup .swal2-content {
                color: #ecedf1 !important; /* Gris plata de alta visibilidad */
                font-size: 1.08rem !important;
                line-height: 1.6 !important;
                font-weight: 400 !important;
            }
            .aia-glass-confirm-btn {
                background: #1a5633 !important; /* Verde AIA Corporativo */
                color: #ffffff !important; /* Texto claro siempre */
                border-radius: 12px !important;
                padding: 14px 36px !important;
                font-weight: 700 !important;
                letter-spacing: 0.5px !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                box-shadow: 0 4px 15px rgba(26, 86, 51, 0.3) !important;
                transition: all 0.2s ease !important;
            }
            .aia-glass-confirm-btn:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(26, 86, 51, 0.4) !important;
                background: #237a46 !important;
            }
            .aia-toast-construction {
                box-shadow: 0 8px 32px rgba(181, 82, 17, 0.35) !important;
                border: 1px solid rgba(181, 82, 17, 0.5) !important;
            }
            .aia-toast-success {
                box-shadow: 0 8px 32px rgba(26, 86, 51, 0.35) !important;
                border: 1px solid rgba(26, 86, 51, 0.5) !important;
            }
            .swal2-icon {
                border-width: 2px !important;
                margin-top: 1.5rem !important;
            }
            /* Garantía de visibilidad para iconos sobre Dark Glass */
            .swal2-icon.swal2-error { border-color: #ff3b30 !important; color: #ff3b30 !important; }
            .swal2-icon.swal2-success { border-color: #34c759 !important; color: #34c759 !important; }
            .swal2-icon.swal2-info { border-color: #ff9500 !important; color: #ff9500 !important; }
        `;
        document.head.appendChild(style);
    };

    injectStyles();

    // 2. Interceptor de window.alert
    const nativeAlert = window.alert;
    window.alert = function(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Notificación del Sistema',
                text: message,
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

        error: function(msg) {
            return Swal.fire({
                icon: 'error',
                title: 'Error detectado',
                text: msg,
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
                text: msg,
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

    console.log("AIA Alert Interceptor (Liquid Glass) cargado correctamente.");
})();
