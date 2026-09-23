/**
 * LogiStock - Validação e Formatação em Tempo Real
 * 
 * Aplica máscaras, validações e feedback visual em todos os formulários.
 */

// ============================================================
// MÁSCARAS
// ============================================================

function mascaraCPF(input) {
    input.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '').substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
    });
}

function mascaraCNPJ(input) {
    input.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '').substring(0, 14);
        v = v.replace(/^(\d{2})(\d)/, '$1.$2');
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
        e.target.value = v;
    });
}

function mascaraData(input) {
    input.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '').substring(0, 8);
        if (v.length >= 5) {
            v = v.replace(/^(\d{2})(\d{2})(\d)/, '$1/$2/$3');
        } else if (v.length >= 3) {
            v = v.replace(/^(\d{2})(\d)/, '$1/$2');
        }
        e.target.value = v;
    });
}

function mascaraPeso(input) {
    input.addEventListener('input', function(e) {
        // Aceita apenas números e vírgula/ponto
        let v = e.target.value.replace(/[^0-9.,]/g, '');
        e.target.value = v;
    });
}

// ============================================================
// FEEDBACK VISUAL
// ============================================================

function marcarInvalido(input, mensagem) {
    input.classList.add('campo-invalido');
    let feedback = input.parentElement.querySelector('.feedback-erro');
    if (!feedback) {
        feedback = document.createElement('small');
        feedback.className = 'feedback-erro';
        input.parentElement.appendChild(feedback);
    }
    feedback.textContent = mensagem;
    feedback.style.color = '#dc3545';
    feedback.style.display = 'block';
    feedback.style.marginTop = '4px';
    feedback.style.fontSize = '12px';
}

function marcarValido(input) {
    input.classList.remove('campo-invalido');
    let feedback = input.parentElement.querySelector('.feedback-erro');
    if (feedback) feedback.style.display = 'none';
}

// ============================================================
// VALIDADORES EM TEMPO REAL
// ============================================================

function validarCampoCPF(input) {
    input.addEventListener('blur', function() {
        const cpf = this.value.replace(/\D/g, '');
        if (cpf.length > 0 && cpf.length !== 11) {
            marcarInvalido(this, 'CPF deve ter 11 dígitos');
        } else {
            marcarValido(this);
        }
    });
}

function validarCampoCNPJ(input) {
    input.addEventListener('blur', function() {
        const cnpj = this.value.replace(/\D/g, '');
        if (cnpj.length > 0 && cnpj.length !== 14) {
            marcarInvalido(this, 'CNPJ deve ter 14 dígitos');
        } else {
            marcarValido(this);
        }
    });
}

function validarCampoData(input) {
    input.addEventListener('blur', function() {
        const v = this.value;
        if (v.length === 0) return marcarValido(this);
        if (!/^\d{2}\/\d{2}\/\d{4}$/.test(v)) {
            marcarInvalido(this, 'Data inválida (use dd/mm/aaaa)');
            return;
        }
        const [d, m, a] = v.split('/').map(Number);
        const data = new Date(a, m - 1, d);
        if (data.getDate() !== d || data.getMonth() !== m - 1 || data.getFullYear() !== a) {
            marcarInvalido(this, 'Data inválida');
        } else {
            marcarValido(this);
        }
    });
}

function validarCampoPeso(input) {
    input.addEventListener('blur', function() {
        const v = this.value.replace(',', '.');
        if (v.length > 0 && (isNaN(v) || parseFloat(v) < 0)) {
            marcarInvalido(this, 'Valor inválido');
        } else {
            marcarValido(this);
        }
    });
}

function validarCampoQuantidade(input) {
    input.addEventListener('blur', function() {
        const v = parseInt(this.value);
        if (isNaN(v) || v <= 0) {
            marcarInvalido(this, 'Deve ser maior que zero');
        } else {
            marcarValido(this);
        }
    });
}

// ============================================================
// AUTO-DETECTA CAMPOS E APLICA MÁSCARAS
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    // Aplica máscaras automaticamente baseado no ID/name
    const campos = document.querySelectorAll('input, textarea');
    
    campos.forEach(campo => {
        const id = (campo.id || '').toLowerCase();
        const name = (campo.name || '').toLowerCase();
        const tipo = (campo.type || '').toLowerCase();
        const chave = id + ' ' + name;

        // CPF
        if (chave.includes('cpf')) {
            mascaraCPF(campo);
            validarCampoCPF(campo);
        }
        // CNPJ
        else if (chave.includes('cnpj')) {
            mascaraCNPJ(campo);
            validarCampoCNPJ(campo);
        }
        // Data
        else if (chave.includes('data')) {
            mascaraData(campo);
            validarCampoData(campo);
        }
        // Peso
        else if (chave.includes('peso') || chave.includes('tara') || chave.includes('vgm')) {
            mascaraPeso(campo);
            validarCampoPeso(campo);
        }
        // Quantidade
        else if (chave.includes('quantidade') && tipo === 'number') {
            validarCampoQuantidade(campo);
        }
    });
});