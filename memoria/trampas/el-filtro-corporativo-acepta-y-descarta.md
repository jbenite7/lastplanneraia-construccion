---
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [arquitectura, deploy]
fuente: medido de punta a punta el 2026-08-18, con recepcion confirmada por el usuario
resumen: "Un filtro corporativo acepta el mensaje con 250 y lo descarta sin rebote; lo que decide la entrega no es la autenticacion sino la CATEGORIA de la IP: relay de envio masivo frente a MTA del hosting"
---

# El filtro corporativo acepta con 250 y lo descarta en silencio

Los correos de restablecimiento no llegaban a los buzones `@aia.com.co`. **La aplicacion no tenia
ningun defecto**, y ese es el punto: todo el instrumental decia que el envio era correcto.

## Lo que decia cada instrumento

| Instrumento | Decia |
|---|---|
| Auditoria de la app | `RESET_CLAVE_ENVIADO` — PHPMailer sin excepcion |
| Panel de Brevo | **Entregado**, con hora y Message-ID |
| Listas negras (5) | IP de origen limpia |
| DNS | SPF, DKIM y DMARC publicados y resolviendo |
| Bandeja y spam del destinatario | vacios |
| Cuarentena de Microsoft 365 | **vacia: nunca lo recibio** |

El destinatario tiene un **FortiMail** (`fml2.its-consultores.com`) por delante de Microsoft 365.
Ese aparato acepta el mensaje con un `250`, no lo entrega, no devuelve rebote y no lo deja en
ninguna cuarentena que el usuario pueda ver. Para el emisor es indistinguible de una entrega
correcta; para el destinatario, de un correo que nunca se envio.

## La medicion que lo resolvio

Tres envios, **mismo remitente y mismo contenido**, cambiando solo por donde salen:

| Camino | A Gmail | A `@aia.com.co` |
|---|---|---|
| Relay externo (Brevo) | llega a bandeja | **no llega** |
| `sendmail` local del hosting | llega a bandeja | **llega a bandeja** |

Que Gmail —de los filtros mas severos que existen con la autenticacion— aceptara el de Brevo es lo
que descarta la hipotesis facil: **no era un problema de firma**. Ambos caminos van autenticados y
alineados; el SPF del dominio cubre la IP del servidor via `+a` y el hosting publica DKIM propio.

Lo que cambia es la **categoria de la IP de origen**: un pool compartido de envio masivo frente a
una IP de hosting con historial propio. Un filtro corporativo pesa esas dos cosas de forma muy
distinta, y ahi se perdian los correos.

## Lo que NO era, aunque lo parecia

Se descartaron con evidencia, y por ese orden, el fallo de la aplicacion (la auditoria distingue
`enviado` / `ignorado` / `fallido` desde B-10 y decia `enviado` — ver [[variable-vacia-tapa-el-env]]
para la otra cara del mismo servicio), las credenciales SMTP (handshake real: `235 Authentication
succeeded`, remitente y destinatario aceptados), la reputacion de la IP (limpia en cinco listas) y
la cuarentena del destinatario (vacia).

**La leccion que dejo el tercer descarte merece quedarse:** el remitente SI estaba sin autenticar
—Brevo reescribia el `From` a un subdominio suyo por no poder acreditar un `@gmail.com`— y
arreglarlo era necesario y correcto. Pero no basto. **Un defecto real puede no ser *el* defecto**, y
confundir "he encontrado algo roto" con "he encontrado la causa" costo medio dia.

## Que hacer

- **Para buzones corporativos detras de un filtro propio, envia por el MTA del hosting**
  (`MAIL_TRANSPORT=sendmail`), no por un ESP. No pide credenciales: el MTA local no las tiene.
- **No confies en "Entregado" de un ESP.** Significa que el gateway del destinatario acepto la
  conexion, no que el mensaje llegara a un buzon.
- **Prueba a un dominio de control** (Gmail) antes de acusar a nadie: separa "nuestro lado esta mal"
  de "el filtro del destinatario lo retiene" en un solo envio.
- Si hay que volver a un ESP, hay que pedir antes al administrador del filtro que autorice el
  dominio; con DKIM y DMARC ya publicados esa peticion es defendible.

El contrato de codigo lo fija `tests/test_mail_transport.php`, y el porque esta en el comentario de
`SmtpMailer::buildMailer()`.
