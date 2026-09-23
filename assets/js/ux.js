/**
 * LogiStock - UX (modal de confirmação + notificações toast + loading + modal detalhes)
 */

// ============================================================
// TOAST
// ============================================================

function mostrarToast(mensagem, tipo = 'info', duracao = 3000) {
    const antigo = document.querySelector('.toast-logistock');
    if (antigo) antigo.remove();

    const cores = {
        sucesso: { bg: '#28a745', icone: '✅' },
        erro: { bg: '#dc3545', icone: '❌' },
        aviso: { bg: '#ffc107', icone: '⚠️', cor: '#333' },
        info: { bg: '#17a2b8', icone: 'ℹ️' }
    };
    const c = cores[tipo] || cores.info;

    const toast = document.createElement('div');
    toast.className = 'toast-logistock';
    toast.style.cssText = `
        position: fixed;
        top: 90px;
        right: 20px;
        background: ${c.bg};
        color: ${c.cor || 'white'};
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        z-index: 10001;
        font-weight: 600;
        font-size: 14px;
        max-width: 350px;
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    toast.innerHTML = `<span style="font-size:18px;">${c.icone}</span><span>${mensagem}</span>`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(() => toast.remove(), 300);
    }, duracao);
}

// ============================================================
// MODAL DE CONFIRMAÇÃO
// ============================================================

function confirmarAcao(mensagem, onConfirm, opcoes = {}) {
    const titulo = opcoes.titulo || 'Confirmação';
    const textoBotaoSim = opcoes.textoSim || 'Confirmar';
    const textoBotaoNao = opcoes.textoNao || 'Cancelar';
    const corBotao = opcoes.corBotao || '#dc3545';

    const overlay = document.createElement('div');
    overlay.className = 'modal-confirmacao-overlay';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    `;

    overlay.innerHTML = `
        <div class="modal-confirmacao" style="
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            transform: scale(0.9);
            transition: transform 0.2s;
            text-align: center;
        ">
            <div style="font-size: 48px; margin-bottom: 15px;">⚠️</div>
            <h3 style="margin: 0 0 15px; color: #0b2b40; font-size: 20px;">${titulo}</h3>
            <p style="color: #555; font-size: 15px; margin: 0 0 25px; line-height: 1.5;">${mensagem}</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn-modal-nao" style="
                    background: #f0f4f8;
                    color: #555;
                    padding: 12px 24px;
                    border: 1px solid #ccc;
                    border-radius: 8px;
                    font-weight: bold;
                    cursor: pointer;
                    font-size: 14px;
                    flex: 1;
                ">${textoBotaoNao}</button>
                <button class="btn-modal-sim" style="
                    background: ${corBotao};
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    font-weight: bold;
                    cursor: pointer;
                    font-size: 14px;
                    flex: 1;
                ">${textoBotaoSim}</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    setTimeout(() => {
        overlay.style.opacity = '1';
        overlay.querySelector('.modal-confirmacao').style.transform = 'scale(1)';
    }, 10);

    function fechar(executar) {
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.remove();
            if (executar && typeof onConfirm === 'function') onConfirm();
        }, 200);
    }

    overlay.querySelector('.btn-modal-sim').addEventListener('click', () => fechar(true));
    overlay.querySelector('.btn-modal-nao').addEventListener('click', () => fechar(false));
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) fechar(false);
    });
    document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') {
            document.removeEventListener('keydown', esc);
            fechar(false);
        }
    });
}

// ============================================================
// SUBSTITUIR CONFIRM() NATIVO POR MODAL BONITO
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach(form => {
        const codigoOriginal = form.getAttribute('onsubmit');
        const match = codigoOriginal.match(/confirm\(['"]([^'"]+)['"]\)/);
        if (!match) return;
        
        const mensagem = match[1];
        form.removeAttribute('onsubmit');
        form.setAttribute('data-modal-processado', '1');

        form.addEventListener('submit', function(e) {
            if (form.getAttribute('data-confirmado') === '1') {
                form.removeAttribute('data-confirmado');
                const botao = form.querySelector('button[type="submit"]');
                const textoBotao = botao ? botao.textContent.trim() : 'Processando...';
                mostrarLoading(textoBotao.substring(0, 30) + '...');
                return true;
            }

            e.preventDefault();
            confirmarAcao(mensagem, () => {
                form.setAttribute('data-confirmado', '1');
                form.submit();
            });
        });
    });

    document.querySelectorAll('a[onclick*="confirm("]').forEach(link => {
        const codigoOriginal = link.getAttribute('onclick');
        const match = codigoOriginal.match(/confirm\(['"]([^'"]+)['"]\)/);
        if (!match) return;
        
        const mensagem = match[1];
        const destino = link.getAttribute('href');

        link.removeAttribute('onclick');

        link.addEventListener('click', function(e) {
            e.preventDefault();
            confirmarAcao(mensagem, () => {
                if (destino && destino !== '#') {
                    window.location.href = destino;
                }
            });
        });
    });
});

// ============================================================
// LOADING/SPINNER
// ============================================================

function mostrarLoading(texto = 'Processando...') {
    const antigo = document.getElementById('loading-overlay');
    if (antigo) antigo.remove();

    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(11, 43, 64, 0.85);
        z-index: 10000;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        font-weight: bold;
    `;
    overlay.innerHTML = `
        <div style="
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255,255,255,0.2);
            border-top-color: #17a2b8;
            border-radius: 50%;
            animation: spinner-logistock 0.8s linear infinite;
            margin-bottom: 20px;
        "></div>
        <div>${texto}</div>
        <style>
            @keyframes spinner-logistock {
                to { transform: rotate(360deg); }
            }
        </style>
    `;
    document.body.appendChild(overlay);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(form => {
        if (form.getAttribute('data-modal-processado') === '1') return;

        form.addEventListener('submit', function() {
            if (form.checkValidity && form.checkValidity()) {
                const botao = form.querySelector('button[type="submit"]');
                const textoBotao = botao ? botao.textContent.trim() : 'Processando...';
                mostrarLoading(textoBotao.substring(0, 30) + '...');
            }
        });
    });
});

// ============================================================
// MODAL DE DETALHES COMPACTO (carrega via AJAX)
// ============================================================

function abrirDetalhesModal(idOperacao) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-detalhes-overlay';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        padding: 20px;
    `;

    overlay.innerHTML = `
        <div class="modal-detalhes" style="
            background: white;
            padding: 25px;
            border-radius: 12px;
            max-width: 850px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            transform: scale(0.9);
            transition: transform 0.2s;
            position: relative;
        ">
            <button class="btn-fechar-modal" style="
                position: absolute;
                top: 15px;
                right: 15px;
                background: #f0f4f8;
                border: none;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                color: #555;
                z-index: 10;
            ">✕</button>
            <div class="conteudo-detalhes" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                <div style="text-align: center; color: #666;">
                    <div style="font-size: 40px; margin-bottom: 10px;">⏳</div>
                    <div>Carregando...</div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    setTimeout(() => {
        overlay.style.opacity = '1';
        overlay.querySelector('.modal-detalhes').style.transform = 'scale(1)';
    }, 10);

    function fechar() {
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 200);
    }

    overlay.querySelector('.btn-fechar-modal').addEventListener('click', fechar);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) fechar();
    });
    document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') {
            document.removeEventListener('keydown', esc);
            fechar();
        }
    });

    fetch('detalhes_modal.php?id=' + idOperacao)
        .then(response => response.text())
        .then(html => {
            overlay.querySelector('.conteudo-detalhes').innerHTML = html;
            overlay.querySelector('.conteudo-detalhes').style.display = 'block';
        })
        .catch(error => {
            overlay.querySelector('.conteudo-detalhes').innerHTML = '<p style="color:#dc3545;">Erro ao carregar detalhes.</p>';
        });
}