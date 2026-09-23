# 🚢 LogiStock

**Sistema de Conferência Portuária e Armazéns Gerais**

TCC do 3º ano do Ensino Médio Técnico em Desenvolvimento de Sistemas.

## Sobre

Sistema web para digitalizar a conferência de cargas em terminais portuários. Substitui planilhas e anotações manuais por um fluxo digital entre Supervisor, Conferente e ADM.

## Funcionalidades

- Autenticação com 3 perfis (ADM, Supervisor, Conferente)
- Cadastro de Mercadorias e Clientes
- Criação de Bookings (Supervisor)
- Conferência de Cargas (Conferente)
- Dashboards com gráficos
- Geração de Packing List em PDF
- Notificações internas
- Exportação para Excel
- Filtros e auditoria

## Tecnologias

- PHP 8
- HTML5, CSS3, JavaScript
- Chart.js
- FPDF
- JSON (persistência atual)

## Como Rodar

1. Clone o repositório
2. Crie os arquivos JSON na pasta `data/` com `[]` dentro:
   - operacoes.json
   - usuarios.json
   - clientes.json
   - produtos.json
   - emails_pendentes.json
3. Rode: `php -S localhost:8081 router.php`
4. Acesse: `http://localhost:8081/pages/login.php`

## Credenciais de Teste

| Perfil | CPF | Senha |
|--------|-----|-------|
| ADM | 111.111.111-11 | 123 |
| Supervisor | 222.222.222-22 | 123 |
| Conferente | 333.333.333-33 | 123 |

## Autor

Tony Lopes