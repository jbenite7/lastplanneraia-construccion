(function() {
    function handleSessionExpiry(payload) {
        var redirectUrl = (payload && payload.redirect) || '/logout?timeout=1';

        if (window.AIA && window.AIA.SessionTimeoutManager && typeof window.AIA.SessionTimeoutManager.forceLogout === 'function') {
            window.AIA.SessionTimeoutManager.forceLogout(redirectUrl);
            return;
        }

        window.location.replace(redirectUrl);
    }

    function initNotifications() {
    const badgeDesk = document.getElementById('notificationBadge');
    const listDesk = document.getElementById('notificationList');
    const badgeMob = document.getElementById('notificationBadgeMobile');
    const listMob = document.getElementById('notificationListMobile');
    
    if (!badgeDesk && !badgeMob) return;

    // Obtener notificaciones
    function fetchNotifications() {
        fetch('/api/notifications/unread', {
            headers: {
                'X-AIA-Expect-Json': '1',
                'X-AIA-Idle-Refresh': '0',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (response.status === 401) {
                    return response.json()
                        .then(payload => {
                            handleSessionExpiry(payload);
                            return null;
                        })
                        .catch(() => {
                            handleSessionExpiry();
                            return null;
                        });
                }

                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(res => {
                if (!res) {
                    return;
                }

                if (res.success && res.data) {
                    renderNotifications(res.data);
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
    }

    // Dibujar en el DOM
    function renderNotifications(data) {
        if (data.length > 0) {
            if (badgeDesk) {
                badgeDesk.textContent = data.length;
                badgeDesk.style.display = 'inline-block';
            }
            if (badgeMob) {
                badgeMob.textContent = data.length;
                badgeMob.style.display = 'inline-block';
            }
            
            let html = '';
            data.forEach(item => {
                // Formato de fecha simple
                const date = new Date(item.created_at).toLocaleDateString('es-CO', {day: '2-digit', month: 'short'});
                const count = parseInt(item.item_count) || 1;
                const countBadge = count > 1 ? `<span class="badge badge-pill badge-secondary ml-1" style="font-size:0.65rem;">${count}</span>` : '';
                
                html += `
                <a class="dropdown-item d-flex align-items-center border-bottom py-2 notification-item" href="#" data-id="${item.id}" style="white-space: normal;">
                    <div class="mr-3">
                        <div class="icon-circle bg-warning text-white" style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-exclamation-triangle text-xs"></i>
                        </div>
                    </div>
                    <div>
                        <div class="small text-gray-500">${date}</div>
                        <span class="font-weight-bold" style="font-size: 0.85rem;">${item.title}${countBadge}</span>
                        <div class="small text-muted" style="font-size: 0.75rem; line-height: 1.2;">${item.message}</div>
                    </div>
                </a>`;
            });
            if (listDesk) listDesk.innerHTML = html;
            if (listMob) listMob.innerHTML = html;
            
            // Asignar eventos de "Marcar como leído"
            document.querySelectorAll('.notification-item').forEach(el => {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const notifId = this.getAttribute('data-id');
                    // Find all matching notifications in both lists
                    const allMatching = document.querySelectorAll(`.notification-item[data-id="${notifId}"]`);
                    markAsRead(notifId, Array.from(allMatching));
                });
            });
            
        } else {
            const emptyHtml = '<a class="dropdown-item text-muted text-center py-3" href="#">No hay notificaciones nuevas</a>';
            if (badgeDesk) { badgeDesk.style.display = 'none'; }
            if (badgeMob) { badgeMob.style.display = 'none'; }
            if (listDesk) { listDesk.innerHTML = emptyHtml; }
            if (listMob) { listMob.innerHTML = emptyHtml; }
        }
    }

    // Marcar como leído
    function markAsRead(id, elementNodes) {
        fetch('/api/notifications/read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                // Remover visualmente de todas las listas
                elementNodes.forEach(node => node.remove());
                
                // Read current count
                let currentText = badgeDesk ? badgeDesk.textContent : (badgeMob ? badgeMob.textContent : "0");
                let currentCount = parseInt(currentText) || 0;
                currentCount--;
                
                if (currentCount > 0) {
                    if (badgeDesk) badgeDesk.textContent = currentCount;
                    if (badgeMob) badgeMob.textContent = currentCount;
                } else {
                    const emptyHtml = '<a class="dropdown-item text-muted text-center py-3" href="#">No hay notificaciones nuevas</a>';
                    if (badgeDesk) { badgeDesk.style.display = 'none'; listDesk.innerHTML = emptyHtml; }
                    if (badgeMob) { badgeMob.style.display = 'none'; listMob.innerHTML = emptyHtml; }
                }
            }
        });
    }

    // Llamada inicial
    fetchNotifications();
    
    // Polling ligero (cada 120 segundos)
    setInterval(fetchNotifications, 120000);
    } // fin initNotifications

    // Ejecutar: si el DOM ya cargó, ejecutar directo; si no, esperar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        // DOM ya listo (carga dinámica vía createElement)
        initNotifications();
    }
})();
