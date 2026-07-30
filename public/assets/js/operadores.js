/*
==========================================================
BT Queue Enterprise
M  dulo: Operadores
Arquivo: operadores.js
==========================================================
*/

window.BT = window.BT || {};

BT.operadores = {

    apiEndpoint: 'api/operadores.php',

    init() {

        this.carregar();
        this.registrarEventos();

    },

    registrarEventos() {

        const btn = document.getElementById('btnNovo');

        if(btn){

            btn.removeEventListener('click', this.novo);

            btn.addEventListener('click', ()=>{

                this.novo();

            });

        }

    },

    async carregar(){

        const tbody=document.getElementById('listaOperadores');

        if(!tbody) return;

        try{

            const resposta=await fetch(this.apiEndpoint);

            const json=await resposta.json();

            if(!json.success){

                             BT.toast.erro(json.message);

                return;

            }

            this.renderizar(json.data);

        }catch(e){

            console.error(e);

        }

    },

    renderizar(lista){
        const grid=document.getElementById('listaOperadores');
        if(!grid) return;

        if(lista.length===0){
            grid.innerHTML='<div class="bt-card" style="grid-column: 1/-1; text-align:center;">Nenhum operador cadastrado.</div>';
            return;
        }

        grid.innerHTML=lista.map(op=>`
            <div class="op-card animate__animated animate__fadeIn">
                <div class="op-header">
                    <div class="op-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="op-info">
                        <h3 class="op-name">${op.nome}</h3>
                        <span class="op-login">@${op.login}</span>
                    </div>
                    <div class="op-status-indicator">
                        ${op.ativo == 1
                            ? '<span class="status-badge status-online" style="font-size:9px;">ATIVO</span>'
                            : '<span class="status-badge status-offline" style="font-size:9px;">INATIVO</span>'}
                    </div>
                </div>

                <div class="op-badges">
                    <div class="op-badge">
                        <i class="fa-solid fa-desktop"></i> Local: <b>${op.guiche || 'Não definido'}</b>
                    </div>
                    <div class="op-badge">
                        <i class="fa-solid fa-user-doctor"></i> Serviço: <b>${op.servico || 'Não definido'}</b>
                    </div>
                </div>

                <div class="op-actions">
                    <button class="op-btn op-btn-edit" onclick="BT.operadores.editar(${op.id})">
                        <i class="fa-solid fa-pen-to-square"></i> EDITAR
                    </button>
                    <button class="op-btn op-btn-delete" onclick="BT.operadores.excluir(${op.id})">
                        <i class="fa-solid fa-trash-can"></i> REMOVER
                    </button>
                </div>
            </div>
        `).join('');
    },

    novo(){

        const html=this.formulario();

        BT.modal.abrir(

            'Novo Operador',

            html,

            ()=>this.salvar('POST')

        );

        setTimeout(()=>{

            this.carregarCombos();

        },100);

    },

    async editar(id){

        try{

            const resposta=await fetch(

                `${this.apiEndpoint}?id=${id}`

            );

                       const json=await resposta.json();

            if(!json.success){

                BT.toast.erro(json.message);

                return;

            }

            BT.modal.abrir(

                'Editar Operador',

                this.formulario(json.data),

                ()=>{
                    this.salvar('PUT');
                }

            );

            setTimeout(()=>{
                this.carregarCombos(
                    json.data.servico_id,
                    json.data.guiche_id
                );
            },100);

        }catch(e){

            console.error(e);

        }

    },

    async salvar(metodo){

        const dados={

            id:document.getElementById('op_id')?.value||'',

            nome:document.getElementById('op_nome').value.trim(),

            login:document.getElementById('op_login').value.trim(),

            senha:document.getElementById('op_senha').value,

            servico_id:document.getElementById('op_servico').value,

            guiche_id:document.getElementById('op_guiche').value,

             ativo:document.getElementById('op_ativo').checked?1:0

        };

        try{

            const resposta=await fetch(this.apiEndpoint,{

                method:metodo,

                headers:{

                    'Content-Type':'application/json'

                },

                body:JSON.stringify(dados)

            });

            const json=await resposta.json();

            if(json.success){

                BT.modal.fechar();

                BT.toast.sucesso('Operador salvo com sucesso.');

                this.carregar();

            }else{

                BT.toast.erro(json.message);

            }

        }catch(e){

            console.error(e);

        }

    },

    async excluir(id){

        if(!confirm('Excluir este operador?')){

            return;

        }

        try{

           const resposta=await fetch(this.apiEndpoint,{

                method:'DELETE',

                headers:{

                    'Content-Type':'application/json'

                },

                body:JSON.stringify({

                    id:id

                })

            });

            const json=await resposta.json();

            if(json.success){

                BT.toast.sucesso('Operador exclu  do.');

                this.carregar();

            }else{

                BT.toast.erro(json.message);

            }

        }catch(e){

            console.error(e);

        }

    },

    async carregarCombos(servicoSelecionado='',guicheSelecionado=''){

        try{

            const servicos=await fetch('api/servicos.php');
            const jsServicos=await servicos.json();

            const guiches=await fetch('api/guiches.php');
            const jsGuiches=await guiches.json();

            const cmbServico=document.getElementById('op_servico');
            const cmbGuiche=document.getElementById('op_guiche');

          if(cmbServico){

                cmbServico.innerHTML='';

                (jsServicos.data||[]).forEach(s=>{

                    const o=document.createElement('option');

                    o.value=s.id;
                    o.textContent=s.nome;

                    if(String(s.id)===String(servicoSelecionado)){
                        o.selected=true;
                    }

                    cmbServico.appendChild(o);

                });

            }

            if(cmbGuiche){

                cmbGuiche.innerHTML='';

                (jsGuiches||[]).forEach(g=>{

                    const o=document.createElement('option');

                    o.value=g.id;
                    o.textContent=g.nome;

                    if(String(g.id)===String(guicheSelecionado)){
                        o.selected=true;
                    }

                    cmbGuiche.appendChild(o);

                });

            }

        }catch(e){

            console.error(e);

        }

    },


    formulario(dados={}){

     return `

        <form id="formOperador">

            <input
                type="hidden"
                id="op_id"
                value="${dados.id||''}">

            <div class="form-group">

                <label>Nome</label>

                <input
                    id="op_nome"
                    class="form-control"
                    value="${dados.nome||''}"
                    required>

            </div>

            <div class="form-group">

                <label>Login</label>

                <input
                    id="op_login"
                    class="form-control"
                    value="${dados.login||''}"
                    required>

            </div>

            <div class="form-group">

                <label>Senha</label>

                <input
                    id="op_senha"
                    type="password"
                    class="form-control"
                    placeholder="${dados.id?'Deixe em branco para manter':'Informe a senha'}>

            </div>

            <div class="form-group">

                <label>Servi  o</label>

                <select
                    id="op_servico"
                    class="form-control">

              </select>

            </div>

            <div class="form-group">

                <label>Guich  </label>

                <select
                    id="op_guiche"
                    class="form-control">

                </select>

            </div>

            <div class="form-group">

                <label>

                    <input
                        id="op_ativo"
                        type="checkbox"
                        ${(dados.ativo==0)?'':'checked'}>

                    Ativo

                </label>

            </div>

        </form>

        `;

    }

};

document.addEventListener('DOMContentLoaded',()=>{

    BT.operadores.init();

});
