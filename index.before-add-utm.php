<?php
/*
|--------------------------------------------------------------------------
| UTM MONITOR
|--------------------------------------------------------------------------
| Интерфейс мониторинга УТМ.
| API: api.php?action=status
|--------------------------------------------------------------------------
*/

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UTM Monitor</title>

    <style>
        :root {
            --bg:#070b14; --bg2:#0b1220; --panel:rgba(15,23,42,.86);
            --border:rgba(148,163,184,.13); --border2:rgba(148,163,184,.24);
            --text:#f1f5f9; --muted:#8492a8; --green:#22c55e; --red:#ef4444;
            --orange:#f59e0b; --blue:#3b82f6; --purple:#8b5cf6; --cyan:#06b6d4;
            --shadow:0 22px 60px rgba(0,0,0,.34);
        }
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;min-height:100vh;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--text);
            background:radial-gradient(circle at 10% 0%,rgba(59,130,246,.14),transparent 30%),
            radial-gradient(circle at 90% 0%,rgba(139,92,246,.12),transparent 27%),
            linear-gradient(135deg,var(--bg),var(--bg2));background-attachment:fixed}
        .header{position:sticky;top:0;z-index:50;padding:20px 30px;background:rgba(7,11,20,.78);border-bottom:1px solid var(--border);
            backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);box-shadow:0 10px 35px rgba(0,0,0,.2)}
        .title{font-size:28px;font-weight:850;letter-spacing:.4px}
        .subtitle{margin-top:5px;color:var(--muted);font-size:12px;letter-spacing:.6px;text-transform:uppercase}
        .toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
        button{position:relative;min-height:44px;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:0 18px;cursor:pointer;
            color:#fff;background:linear-gradient(135deg,#2563eb,#4f46e5 55%,#7c3aed);font:800 12px Inter,Segoe UI,Arial,sans-serif;letter-spacing:.45px;
            box-shadow:0 10px 28px rgba(37,99,235,.24),inset 0 1px 0 rgba(255,255,255,.12);transition:.2s ease}
        button:hover{transform:translateY(-2px);filter:brightness(1.08);box-shadow:0 14px 34px rgba(37,99,235,.34),0 0 22px rgba(99,102,241,.14)}
        button:active{transform:translateY(0) scale(.98)}
        button.loading{pointer-events:none;opacity:.7}
        button.loading::after{content:'';width:15px;height:15px;margin-left:9px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite}
        .container{width:min(1700px,calc(100% - 48px));margin:0 auto;padding:28px 0 45px}
        .stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:15px;margin-bottom:20px}
        .stat{position:relative;overflow:hidden;min-height:125px;padding:20px;border:1px solid var(--border);border-radius:17px;background:linear-gradient(145deg,rgba(17,25,40,.96),rgba(10,16,28,.88));box-shadow:var(--shadow);transition:.22s ease}
        .stat:hover{transform:translateY(-3px);border-color:var(--border2)}
        .stat::after{content:'';position:absolute;right:-50px;top:-50px;width:120px;height:120px;border-radius:50%;background:radial-gradient(circle,rgba(59,130,246,.16),transparent 70%)}
        .stat-title{color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px}
        .stat-value{margin-top:10px;font-size:34px;line-height:1;font-weight:900}
        .section{background:linear-gradient(145deg,rgba(15,23,42,.90),rgba(9,15,27,.88));border:1px solid var(--border);border-radius:17px;margin-bottom:20px;overflow:hidden;box-shadow:var(--shadow)}
        .section-title{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 20px;font-size:16px;font-weight:800;border-bottom:1px solid var(--border);background:rgba(255,255,255,.015)}
        .table-wrap{overflow:auto}
        table{width:100%;min-width:1120px;border-collapse:separate;border-spacing:0}
        th{padding:13px 15px;color:#718096;background:rgba(0,0,0,.14);border-bottom:1px solid var(--border);font-size:10px;font-weight:800;text-align:left;text-transform:uppercase;letter-spacing:.65px}
        td{padding:14px 15px;border-bottom:1px solid rgba(148,163,184,.07);font-size:12px;white-space:nowrap}
        tbody tr{transition:background .18s ease} tbody tr:hover{background:rgba(59,130,246,.045)} tbody tr:last-child td{border-bottom:0}
        .online{display:inline-flex;align-items:center;gap:7px;color:#86efac;font-weight:800;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.14);padding:6px 9px;border-radius:8px}
        .online::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 12px rgba(34,197,94,.75);animation:pulse 1.7s infinite}
        .offline{display:inline-flex;align-items:center;gap:7px;color:#fca5a5;font-weight:800;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.14);padding:6px 9px;border-radius:8px}
        .offline::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--red)}
        .valid{color:#4ade80;font-weight:800}.warning{color:#fbbf24;font-weight:800}.critical,.expired,.invalid{color:#f87171;font-weight:800}.unknown{color:var(--muted);font-weight:700}
        .cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;padding:20px}
        .card{min-width:0;padding:18px;border:1px solid var(--border);border-radius:13px;background:rgba(5,10,19,.52);transition:.2s ease}
        .card:hover{transform:translateY(-2px);border-color:rgba(96,165,250,.2)}
        .card h3{margin:0 0 15px;font-size:14px;font-weight:850}
        .line{display:flex;justify-content:space-between;align-items:center;gap:18px;min-height:38px;padding:8px 0;border-bottom:1px solid rgba(148,163,184,.07)}
        .line:last-child{border-bottom:0}.label{color:var(--muted);font-size:11px}.value{max-width:68%;text-align:right;font-size:11px;font-weight:700;overflow-wrap:anywhere}
        .alerts{padding:20px}.alert{padding:14px 16px;border-radius:11px;margin-bottom:10px;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.12);border-left:4px solid var(--orange)}
        .alert.critical{border-left-color:var(--red);background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.14)}
        .muted{color:var(--muted)}.footer{display:flex;justify-content:space-between;gap:15px;padding:18px 4px;color:#536174;font-size:10px}
        .loading{min-height:140px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:var(--muted);font-size:12px}
        .loading::before{content:'';width:26px;height:26px;border-radius:50%;border:3px solid rgba(255,255,255,.08);border-top-color:var(--blue);animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}} @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.78)}}
        @media (max-width:1250px){.stats{grid-template-columns:repeat(3,1fr)}.cards{grid-template-columns:repeat(2,1fr)}}
        @media (max-width:800px){.header{padding:18px}.container{width:calc(100% - 24px);padding-top:20px}.stats{grid-template-columns:repeat(2,1fr)}.cards{grid-template-columns:1fr}}
        @media (max-width:520px){.stats{grid-template-columns:1fr}.title{font-size:23px}.footer{flex-direction:column}}
    </style>
</head>

<body>

<div class="header">
    <div class="title">UTM MONITOR</div>
    <div class="subtitle">Центр мониторинга ЕГАИС</div>

    <div class="toolbar">
        <button id="refreshButton" onclick="loadStatus(true)"><span>↻</span><span>ПРОВЕРИТЬ ВСЕ</span></button>
    </div>
</div>

<div class="container">

    <div class="stats">
        <div class="stat">
            <div class="stat-title">Всего УТМ</div>
            <div class="stat-value" id="total">0</div>
        </div>

        <div class="stat">
            <div class="stat-title">Online</div>
            <div class="stat-value" id="online">0</div>
        </div>

        <div class="stat">
            <div class="stat-title">Offline</div>
            <div class="stat-value" id="offline">0</div>
        </div>

        <div class="stat">
            <div class="stat-title">RSA</div>
            <div class="stat-value" id="rsaCount">0</div>
        </div>

        <div class="stat">
            <div class="stat-title">GOST</div>
            <div class="stat-value" id="gostCount">0</div>
        </div>
    </div>


    <div class="section">
        <div class="section-title">Установленные УТМ</div>

        <div id="tableContainer" class="loading">
            Загрузка...
        </div>
    </div>


    <div id="detailsContainer"></div>


    <div class="section">
        <div class="section-title">Предупреждения</div>

        <div id="alertsContainer" class="alerts">
            <div class="muted">Предупреждений нет.</div>
        </div>
    </div>

</div>

<div class="footer">
    Автообновление: 10 секунд
    <span id="lastUpdate"></span>
</div>


<script>

const API_URL = 'api.php?action=status';
const REFRESH_SECONDS = 10;

function escapeHtml(value) {

    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}


function certificateClass(status) {

    if (!status) {
        return 'unknown';
    }

    return status;
}


function certificateStatus(cert) {

    if (!cert) {
        return '<span class="unknown">НЕТ ДАННЫХ</span>';
    }

    const cls = certificateClass(cert.status);

    let text = cert.status_text || 'Неизвестно';

    if (cert.days_left !== null && cert.days_left !== undefined) {
        text += ' — ' + cert.days_left + ' дн.';
    }

    return '<span class="' + cls + '">' +
        escapeHtml(text) +
        '</span>';
}


function certificateDate(cert) {

    if (!cert || !cert.expire_date) {
        return '—';
    }

    return escapeHtml(cert.expire_date);
}


function renderTable(utms) {

    if (!utms.length) {

        document.getElementById('tableContainer').innerHTML =
            '<div class="loading">УТМ не настроены</div>';

        return;
    }

    let html = `
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>УТМ</th>
                    <th>IP</th>
                    <th>Статус</th>
                    <th>API</th>
                    <th>RSA</th>
                    <th>GOST</th>
                    <th>RSA до</th>
                    <th>GOST до</th>
                    <th>Лицензия</th>
                    <th>Версия</th>
                    <th>Ответ</th>
                </tr>
            </thead>
            <tbody>
    `;

    utms.forEach((u, index) => {

        const rsaInfo = u.rsa_info;
        const gostInfo = u.gost_info;

        let license = '—';

        if (u.license === true) {
            license = '<span class="valid">Действует</span>';
        } else if (u.license === false) {
            license = '<span class="expired">НЕДЕЙСТВИТЕЛЬНА</span>';
        }

        html += `
            <tr>
                <td>${index + 1}</td>

                <td>
                    <strong>${escapeHtml(u.name)}</strong>
                </td>

                <td>${escapeHtml(u.ip)}</td>

                <td>
                    ${
                        u.online
                        ? '<span class="online">● ONLINE</span>'
                        : '<span class="offline">● OFFLINE</span>'
                    }
                </td>

                <td>${escapeHtml(u.port)}</td>

                <td>
                    <span class="${u.rsa > 0 ? 'valid' : 'critical'}">
                        ${u.rsa}
                    </span>
                </td>

                <td>
                    <span class="${u.gost > 0 ? 'valid' : 'critical'}">
                        ${u.gost}
                    </span>
                </td>

                <td>
                    ${certificateDate(rsaInfo)}
                </td>

                <td>
                    ${certificateDate(gostInfo)}
                </td>

                <td>
                    ${license}
                </td>

                <td>
                    ${escapeHtml(u.version || '—')}
                </td>

                <td>
                    ${
                        u.online
                        ? '<span class="valid">Ответ ' + escapeHtml(u.response_time) + ' мс</span>'
                        : '<span class="offline">' + escapeHtml(u.error || 'Нет соединения') + '</span>'
                    }
                </td>

            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
        </div>
    `;

    document.getElementById('tableContainer').innerHTML = html;
}


function renderDetails(utms) {

    let html = '';

    utms.forEach((u, index) => {

        const rsa = u.rsa_info;
        const gost = u.gost_info;

        html += `
            <div class="section">
                <div class="section-title">
                    УТМ №${index + 1} — ${escapeHtml(u.name)}
                </div>

                <div class="cards">

                    <div class="card">
                        <h3>RSA сертификат</h3>

                        <div class="line">
                            <span class="label">Сертификат</span>
                            <span class="value">
                                ${
                                    u.rsa_aliases && u.rsa_aliases.length
                                    ? escapeHtml(u.rsa_aliases.join(', '))
                                    : '—'
                                }
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Статус</span>
                            <span class="value">
                                ${certificateStatus(rsa)}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Срок действия до</span>
                            <span class="value">
                                ${certificateDate(rsa)}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Осталось</span>
                            <span class="value">
                                ${
                                    rsa && rsa.days_left !== null
                                    ? escapeHtml(rsa.days_left) + ' дней'
                                    : '—'
                                }
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Издатель</span>
                            <span class="value">
                                ${escapeHtml(rsa ? rsa.issuer : '—')}
                            </span>
                        </div>
                    </div>


                    <div class="card">
                        <h3>GOST сертификат</h3>

                        <div class="line">
                            <span class="label">Сертификат</span>
                            <span class="value">
                                ${
                                    u.gost_aliases && u.gost_aliases.length
                                    ? escapeHtml(u.gost_aliases.join(', '))
                                    : '—'
                                }
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Статус</span>
                            <span class="value">
                                ${certificateStatus(gost)}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Срок действия до</span>
                            <span class="value">
                                ${certificateDate(gost)}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Осталось</span>
                            <span class="value">
                                ${
                                    gost && gost.days_left !== null
                                    ? escapeHtml(gost.days_left) + ' дней'
                                    : '—'
                                }
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Издатель</span>
                            <span class="value">
                                ${escapeHtml(gost ? gost.issuer : '—')}
                            </span>
                        </div>
                    </div>


                    <div class="card">
                        <h3>Основные параметры УТМ</h3>

                        <div class="line">
                            <span class="label">Версия</span>
                            <span class="value">
                                ${escapeHtml(u.version || '—')}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Контур</span>
                            <span class="value">
                                ${escapeHtml(u.contour || '—')}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">FSRAR ID</span>
                            <span class="value">
                                ${escapeHtml(u.owner_id || '—')}
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Лицензия</span>
                            <span class="value">
                                ${
                                    u.license === true
                                    ? '<span class="valid">ДЕЙСТВУЕТ</span>'
                                    : '<span class="critical">НЕТ</span>'
                                }
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">База УТМ</span>
                            <span class="value">
                                ${escapeHtml(
                                    u.db && u.db.create_date
                                    ? u.db.create_date
                                    : '—'
                                )}
                            </span>
                        </div>
                    </div>


                    <div class="card">
                        <h3>Документы</h3>

                        <div class="line">
                            <span class="label">Входящие</span>
                            <span class="value">
                                ${
                                    u.documents &&
                                    u.documents.incoming !== null
                                    ? escapeHtml(u.documents.incoming)
                                    : '—'
                                }
                            </span>
                        </div>

                        <div class="line">
                            <span class="label">Исходящие</span>
                            <span class="value">
                                ${
                                    u.documents &&
                                    u.documents.outgoing !== null
                                    ? escapeHtml(u.documents.outgoing)
                                    : '—'
                                }
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        `;
    });

    document.getElementById('detailsContainer').innerHTML = html;
}


function renderAlerts(utms) {

    let alerts = [];

    utms.forEach((u, index) => {

        if (Array.isArray(u.warnings)) {

            u.warnings.forEach(w => {

                alerts.push({
                    index: index + 1,
                    name: u.name,
                    level: w.level || 'warning',
                    message: w.message
                });

            });
        }

    });

    const container = document.getElementById('alertsContainer');

    if (!alerts.length) {

        container.innerHTML =
            '<div class="muted">✅ Критических предупреждений нет.</div>';

        return;
    }

    let html = '';

    alerts.forEach(a => {

        html += `
            <div class="alert ${escapeHtml(a.level)}">
                <strong>УТМ №${a.index} — ${escapeHtml(a.name)}</strong><br>
                ${escapeHtml(a.message)}
            </div>
        `;

    });

    container.innerHTML = html;
}


async function loadStatus(manual = false) {

    const button = document.getElementById('refreshButton');
    if (manual) button.classList.add('loading');

    try {

        const response = await fetch(
            API_URL + '&_=' + Date.now(),
            {
                cache: 'no-store'
            }
        );

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.error || 'Ошибка API'
            );
        }

        const utms = Array.isArray(data.utms)
            ? data.utms
            : [];

        const online = utms.filter(
            u => u.online
        ).length;

        const offline = utms.length - online;

        const rsa = utms.reduce(
            (sum, u) => sum + Number(u.rsa || 0),
            0
        );

        const gost = utms.reduce(
            (sum, u) => sum + Number(u.gost || 0),
            0
        );

        document.getElementById('total').textContent = utms.length;
        document.getElementById('online').textContent = online;
        document.getElementById('offline').textContent = offline;
        document.getElementById('rsaCount').textContent = rsa;
        document.getElementById('gostCount').textContent = gost;

        renderTable(utms);
        renderDetails(utms);
        renderAlerts(utms);

        document.getElementById('lastUpdate').textContent =
            ' | Обновлено: ' +
            new Date().toLocaleTimeString('ru-RU');

    } catch (error) {

        document.getElementById('tableContainer').innerHTML =
            '<div class="alert critical">' +
            'Ошибка мониторинга: ' +
            escapeHtml(error.message) +
            '</div>';

    } finally {
        if (button) button.classList.remove('loading');
    }
}


/*
|--------------------------------------------------------------------------
| Первый запуск
|--------------------------------------------------------------------------
*/
loadStatus();


/*
|--------------------------------------------------------------------------
| Автообновление
|--------------------------------------------------------------------------
*/
setInterval(
    loadStatus,
    REFRESH_SECONDS * 1000
);

</script>

</body>
</html>
