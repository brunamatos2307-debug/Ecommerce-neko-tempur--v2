const pratosPadrao = [
  {
    id: 1,
    nome: "Combo sushi da casa (16 peças)",
    categoria: "sushis",
    preco: 42.9,
    estoque: 15,
    descricao: "Salmão, uramaki e hossomaki feitos na hora.",
    imagem: "7ae101c6-91cd-4ec1-8507-595675a9d40f.jpg",
  },
  {
    id: 2,
    nome: "Temaki de salmão",
    categoria: "temakis",
    preco: 26.5,
    estoque: 12,
    descricao: "Alga, arroz temperado, salmão e cebolinha.",
    imagem: "18aeaa31-b2f0-4aa0-92fc-07b899fea5e2.jpg",
  },
  {
    id: 3,
    nome: "Tempurá crocante",
    categoria: "tempura",
    preco: 34.0,
    estoque: 10,
    descricao: "Camarão e legumes empanados com molho da casa.",
    imagem: "f9696b17-1268-4dda-8c4d-cf01a0fc84a8.jpg",
  },
  {
    id: 4,
    nome: "Lámen da casa",
    categoria: "lamen",
    preco: 38.0,
    estoque: 8,
    descricao: "Caldo quente, macarrão, ovo marinado e vegetais.",
    imagem: "fea1b850-abe0-44a0-9fbe-c4a76bc4f808.jpg",
  },
  {
    id: 5,
    nome: "Yakisoba na chapa",
    categoria: "yakisoba",
    preco: 35.9,
    estoque: 14,
    descricao: "Macarrão, legumes variados e molho shoyu.",
    imagem: "e81cf581-73cd-43a7-b065-4de7b7172f0f.jpg",
  },
  {
    id: 6,
    nome: "Guioza (6 unidades)",
    categoria: "guioza",
    preco: 22.0,
    estoque: 20,
    descricao: "Pastéis selados na chapa para compartilhar.",
    imagem: "667152ea-d072-44a9-a40d-692ecd120c7d.jpg",
  },
];

const fotosPorCategoria = {
  sushis: "7ae101c6-91cd-4ec1-8507-595675a9d40f.jpg",
  temakis: "18aeaa31-b2f0-4aa0-92fc-07b899fea5e2.jpg",
  tempura: "f9696b17-1268-4dda-8c4d-cf01a0fc84a8.jpg",
  lamen: "fea1b850-abe0-44a0-9fbe-c4a76bc4f808.jpg",
  yakisoba: "e81cf581-73cd-43a7-b065-4de7b7172f0f.jpg",
  guioza: "667152ea-d072-44a9-a40d-692ecd120c7d.jpg",
};

const listaProdutos = document.getElementById("productList");
const campoBusca = document.getElementById("productSearch");
const msgVazia = document.getElementById("emptyMessage");
const contadorCarrinho = document.getElementById("cartCount");
const botoesCategoria = document.querySelectorAll(".cartao-cat");
const btnLimparFiltro = document.getElementById("btnLimparFiltro");
const filtroAtivoTexto = document.getElementById("filtroAtivoTexto");
const listaCarrinho = document.getElementById("listaCarrinho");
const totalCarrinhoEl = document.getElementById("totalCarrinho");
const btnFinalizarPedido = document.getElementById("btnFinalizarPedido");
const mensagemPedido = document.getElementById("mensagemPedido");

let pratos = [...pratosPadrao];
let categoriaAtiva = "";
let carrinho = [];

function formatarPreco(valor) {
  return Number(valor || 0).toLocaleString("pt-BR", {
    style: "currency",
    currency: "BRL",
  });
}

function resolverFotoPrato(prato) {
  if (prato && typeof prato.imagem === "string" && prato.imagem.trim() !== "") {
    return prato.imagem.trim();
  }
  const cat = String(prato?.categoria || "").toLowerCase();
  if (fotosPorCategoria[cat]) {
    return fotosPorCategoria[cat];
  }
  return "c9c95be8-5955-404f-8cfc-83e400fa0907.jpg";
}

function atualizarCarrinhoUI() {
  const totalQtd = carrinho.reduce((acc, item) => acc + item.quantidade, 0);
  const totalValor = carrinho.reduce(
    (acc, item) => acc + item.quantidade * item.preco_unitario,
    0
  );

  if (contadorCarrinho) {
    contadorCarrinho.textContent = totalQtd;
  }

  if (totalCarrinhoEl) {
    totalCarrinhoEl.textContent = formatarPreco(totalValor);
  }

  if (!listaCarrinho) return;

  if (carrinho.length === 0) {
    listaCarrinho.innerHTML = '<p class="texto-sobre">Nenhum item adicionado ainda.</p>';
    return;
  }

  listaCarrinho.innerHTML = carrinho
    .map(
      (item) => `
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <div>
          <strong>${item.nome}</strong>
          <small class="d-block text-muted">${item.quantidade}x ${formatarPreco(item.preco_unitario)}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span>${formatarPreco(item.quantidade * item.preco_unitario)}</span>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerItem(${item.produto_id})">×</button>
        </div>
      </div>
    `
    )
    .join("");
}

function adicionarItem(pratoId) {
  const prato = pratos.find((p) => Number(p.id) === Number(pratoId));
  if (!prato) return;

  const estoque = Number(prato.estoque ?? 0);
  const existente = carrinho.find((item) => Number(item.produto_id) === Number(prato.id));
  const qtdAtual = existente ? existente.quantidade : 0;

  if (estoque <= 0 || qtdAtual >= estoque) {
    alert(`Estoque máximo disponível para ${prato.nome}: ${estoque}.`);
    return;
  }

  if (existente) {
    existente.quantidade += 1;
  } else {
    carrinho.push({
      produto_id: Number(prato.id),
      nome: prato.nome,
      quantidade: 1,
      preco_unitario: Number(prato.preco),
    });
  }

  atualizarCarrinhoUI();
}

function removerItem(pratoId) {
  carrinho = carrinho.filter((item) => Number(item.produto_id) !== Number(pratoId));
  atualizarCarrinhoUI();
}

window.removerItem = removerItem;

function desenharProdutos() {
  if (!listaProdutos) return;

  const termo = campoBusca ? campoBusca.value.trim().toLowerCase() : "";

  const filtrados = pratos.filter((prato) => {
    const catPrato = String(prato.categoria || "").toLowerCase();
    const passaCategoria = !categoriaAtiva || catPrato === categoriaAtiva.toLowerCase();
    const passaBusca =
      !termo ||
      String(prato.nome || "").toLowerCase().includes(termo) ||
      String(prato.descricao || "").toLowerCase().includes(termo) ||
      catPrato.includes(termo);

    return passaCategoria && passaBusca;
  });

  if (filtroAtivoTexto) {
    if (categoriaAtiva && termo) {
      filtroAtivoTexto.textContent = `Categoria: ${categoriaAtiva} • Busca: "${termo}"`;
    } else if (categoriaAtiva) {
      filtroAtivoTexto.textContent = `Categoria: ${categoriaAtiva}`;
    } else if (termo) {
      filtroAtivoTexto.textContent = `Busca: "${termo}"`;
    } else {
      filtroAtivoTexto.textContent = "Todos os pratos";
    }
  }

  listaProdutos.innerHTML = "";

  if (filtrados.length === 0) {
    if (msgVazia) msgVazia.classList.remove("d-none");
    return;
  }

  if (msgVazia) msgVazia.classList.add("d-none");

  filtrados.forEach((prato) => {
    const estoque = Number(prato.estoque ?? 0);
    const semEstoque = estoque <= 0;
    const fotoSrc = resolverFotoPrato(prato);

    const col = document.createElement("div");
    col.className = "col-12 col-sm-6 col-lg-4";

    col.innerHTML = `
      <article class="item-cardapio">
        <img src="${fotoSrc}" alt="${prato.nome}" class="item-cardapio-img">
        <div class="item-cardapio-corpo">
          <span class="item-cat">${prato.categoria} • ${semEstoque ? "Esgotado" : `${estoque} em estoque`}</span>
          <h3>${prato.nome}</h3>
          <p>${prato.descricao || ""}</p>
          <div class="item-rodape">
            <span class="valor-item">${formatarPreco(prato.preco)}</span>
            <div class="d-flex gap-2">
              <a href="produto.php?id=${prato.id}" class="btn btn-sm btn-outline-dark">Detalhes</a>
              <button type="button" class="btn-add" data-id="${prato.id}" ${semEstoque ? "disabled" : ""}>
                ${semEstoque ? "Sem estoque" : "Adicionar"}
              </button>
            </div>
          </div>
        </div>
      </article>
    `;

    listaProdutos.appendChild(col);
  });

  document.querySelectorAll(".btn-add").forEach((botao) => {
    botao.addEventListener("click", () => {
      adicionarItem(Number(botao.dataset.id));
    });
  });
}

async function carregarCatalogoBanco() {
  try {
    const resposta = await fetch("api.produtos.php");
    if (!resposta.ok) {
      desenharProdutos();
      return;
    }

    const dados = await resposta.json();
    if (dados.ok && Array.isArray(dados.produtos) && dados.produtos.length > 0) {
      pratos = dados.produtos.map((p) => ({
        ...p,
        id: Number(p.id),
        preco: Number(p.preco),
        estoque: Number(p.estoque ?? 0),
        imagem: resolverFotoPrato(p),
      }));
    }
  } catch (e) {
    // Mantém os pratos padrão se api.produtos.php ainda não estiver acessível
  }

  desenharProdutos();
}

btnFinalizarPedido?.addEventListener("click", async () => {
  if (carrinho.length === 0) {
    if (mensagemPedido) {
      mensagemPedido.innerHTML = '<div class="alert alert-warning">Adicione pelo menos um item antes de finalizar.</div>';
    }
    return;
  }

  const clienteNome = document.getElementById("clienteNome")?.value.trim() || "Cliente";
  const clienteEmail = document.getElementById("clienteEmail")?.value.trim() || "";
  const formaPagamento = document.getElementById("formaPagamento")?.value || "PIX";

  try {
    const resposta = await fetch("salvar.pedido.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        cliente_nome: clienteNome,
        cliente_email: clienteEmail,
        forma_pagamento: formaPagamento,
        itens: carrinho,
      }),
    });

    const resultado = await resposta.json();

    if (!resultado.ok && resultado.precisa_login) {
      sessionStorage.setItem("carrinho_pendente", JSON.stringify(carrinho));
      window.location.href = resultado.redirect || "login.php";
      return;
    }

    if (resultado.ok) {
      carrinho = [];
      sessionStorage.removeItem("carrinho_pendente");
      atualizarCarrinhoUI();
      await carregarCatalogoBanco();
      if (mensagemPedido) {
        mensagemPedido.innerHTML = `
          <div class="alert alert-success">
            Pagamento via <strong>${resultado.forma_pagamento}</strong> confirmado!
            Pedido #${resultado.pedido_id} gravado com sucesso. Total: ${formatarPreco(resultado.total)}
          </div>
        `;
      }
    } else if (mensagemPedido) {
      mensagemPedido.innerHTML = `<div class="alert alert-danger">Erro: ${resultado.erro}</div>`;
    }
  } catch (erro) {
    if (mensagemPedido) {
      mensagemPedido.innerHTML = `<div class="alert alert-danger">Falha de comunicação com o servidor: ${erro.message}</div>`;
    }
  }
});

botoesCategoria.forEach((botao) => {
  botao.addEventListener("click", () => {
    const cat = botao.dataset.categoria || "";

    if (categoriaAtiva === cat) {
      categoriaAtiva = "";
      botao.classList.remove("ativo");
    } else {
      categoriaAtiva = cat;
      botoesCategoria.forEach((b) => b.classList.remove("ativo"));
      botao.classList.add("ativo");
    }

    desenharProdutos();
    document.getElementById("cardapio")?.scrollIntoView({ behavior: "smooth" });
  });
});

btnLimparFiltro?.addEventListener("click", () => {
  categoriaAtiva = "";
  if (campoBusca) campoBusca.value = "";
  botoesCategoria.forEach((b) => b.classList.remove("ativo"));
  desenharProdutos();
});

campoBusca?.addEventListener("input", desenharProdutos);

// Recupera itens caso o usuário tenha ido para login/cadastro antes de concluir
const carrinhoPendente = sessionStorage.getItem("carrinho_pendente");
if (carrinhoPendente) {
  try {
    const itensSalvos = JSON.parse(carrinhoPendente);
    if (Array.isArray(itensSalvos) && itensSalvos.length > 0) {
      carrinho = itensSalvos;
    }
  } catch (e) {}
  sessionStorage.removeItem("carrinho_pendente");
}

atualizarCarrinhoUI();
carregarCatalogoBanco();