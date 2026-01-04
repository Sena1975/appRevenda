# Estoque Multi-Empresa + Movimentação + Kits (appRevenda)

Este documento descreve as alterações implementadas para garantir **isolamento por empresa (empresa_id)** em:
- `appestoque`
- `appmovestoque`
- `view_app_produtos` (lookup e estoque por empresa)
- Fluxos de venda/compra/cancelamento
- Rateio de valores em KIT (opção C)
- Tela de edição de produto (Composição do Kit) com confirmação de item

---

## 1) Objetivo

Garantir que:
1. Toda movimentação de estoque grave **empresa_id** corretamente.
2. O estoque (`appestoque`) seja mantido **por empresa + produto**.
3. O lookup de produtos (para kits e vendas) respeite a empresa, evitando duplicidade.
4. Cancelamento de venda:
   - Se **PENDENTE/RESERVADO**: estorna a **reserva**.
   - Se **ENTREGUE**: devolve o estoque e registra movimento correspondente.
5. Kits tenham **rateio do valor** proporcional ao preço base do componente (opção C).
6. Na UI de composição do kit, o item só é considerado válido se for **confirmado** (✅).

---

## 2) Middleware de Empresa Ativa

### Arquivos
- `app/Http/Middleware/EmpresaAtiva.php`
- `bootstrap/app.php` (alias: `empresa.ativa`)
- `routes/web.php` (grupo com `auth` + `empresa.ativa`)

### Resultado
- A empresa ativa é disponibilizada globalmente via:
  - `app('empresa')` (instance)

Isso permite fallback seguro de empresa em serviços e controllers.

---

## 3) Banco de Dados

### 3.1) Tabela `appestoque`

#### Regras
- `empresa_id` **NOT NULL**
- Unique key: **(empresa_id, produto_id)**
- Estoque calculado: `disponivel = estoque_gerencial - reservado - avaria`

#### Estrutura (referência)
```sql
CREATE TABLE `appestoque` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `produto_id` bigint unsigned NOT NULL,
  `codfabnumero` bigint DEFAULT NULL,
  `estoque_gerencial` decimal(10,3) NOT NULL DEFAULT '0.000',
  `reservado` decimal(10,3) NOT NULL DEFAULT '0.000',
  `avaria` decimal(10,3) NOT NULL DEFAULT '0.000',
  `disponivel` decimal(10,3) GENERATED ALWAYS AS (((`estoque_gerencial` - `reservado`) - `avaria`)) STORED,
  `ultimo_preco_compra` decimal(10,2) DEFAULT '0.00',
  `ultimo_preco_venda` decimal(10,2) DEFAULT '0.00',
  `data_ultima_mov` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appestoque_empresa_produto` (`empresa_id`,`produto_id`),
  KEY `fk_estoque_produto` (`produto_id`),
  CONSTRAINT `appestoque_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `appempresas` (`id`),
  CONSTRAINT `fk_estoque_produto` FOREIGN KEY (`produto_id`) REFERENCES `appproduto` (`id`) ON UPDATE CASCADE
);
Índices (estado final)

PRIMARY(id)

uq_appestoque_empresa_produto(empresa_id, produto_id)

fk_estoque_produto(produto_id)

3.2) Tabela appmovestoque
Regras

empresa_id NOT NULL

Índices para performance:

por origem: (empresa_id, origem, origem_id, status)

por produto/data: (empresa_id, produto_id, data_mov)

Resultado prático

Cancelamento e confirmação passam a atualizar/filtrar por empresa corretamente.

4) View view_app_produtos com empresa_id
Problema resolvido

O lookup estava duplicando resultados por não filtrar a empresa.
A solução foi incluir p.empresa_id diretamente na view e ajustar joins por empresa quando necessário.

Fonte da view (atualizada)
CREATE OR REPLACE
ALGORITHM = UNDEFINED VIEW `view_app_produtos` AS
select
    `p`.`empresa_id` AS `empresa_id`,
    `p`.`codfabnumero` AS `codigo_fabrica`,
    `p`.`nome` AS `descricao_produto`,
    `c`.`nome` AS `categoria`,
    `sc`.`nome` AS `subcategoria`,
    ifnull(`p`.`preco_revenda`, 0) AS `preco_revenda`,
    ifnull(`p`.`preco_compra`, 0) AS `preco_compra`,
    ifnull(`tp`.`pontuacao`, 0) AS `pontos`,
    'CICLO-2025' AS `ciclo`,
    ifnull(`e`.`disponivel`, 0) AS `qtd_estoque`,
    (
      select max(`m`.`data_mov`)
      from `appmovestoque` `m`
      where (`m`.`codfabnumero` = `p`.`codfabnumero`
        and `m`.`tipo_mov` = 'entrada'
        and `m`.`origem` = 'compra')
    ) AS `data_ultima_entrada`
from
    ((((`appproduto` `p`
left join `appcategoria` `c` on (`p`.`categoria_id` = `c`.`id`))
left join `appsubcategoria` `sc` on (`p`.`subcategoria_id` = `sc`.`id`))
left join `apptabelapreco` `tp` on (`tp`.`codfab` = `p`.`codfabnumero`))
left join `appestoque` `e` on (`e`.`codfabnumero` = `p`.`codfabnumero`)));


Observação: o ideal (em versão futura) é amarrar o join de estoque também por empresa:
e.empresa_id = p.empresa_id, para evitar “vazamento” entre empresas quando houver produtos com mesmo codfab.

5) Lookup de produtos por empresa (para KIT e telas de venda)
Arquivo

app/Http/Controllers/ProdutoLookupController.php

Ajuste obrigatório

Filtrar ViewProduto por empresa_id da empresa ativa:

preferir app('empresa')->id (middleware)

fallback para auth()->user()->empresa_id

Resultado

Lookup deixa de duplicar itens e passa a respeitar empresa.

6) EstoqueService (empresa_id + Kit + Rateio)
Arquivo

app/Services/EstoqueService.php

Ponto-chave

Todos os métodos principais obtêm empresa_id via:

parâmetro

objeto/pedido

app('empresa')

usuário logado

Métodos envolvidos

registrarEntradaCompra() ✅

reservarVenda() ✅

confirmarSaidaVenda() ✅

cancelarReservaVenda() ✅

registrarSaidaVenda() ✅

estornarEntradaCompra() ✅

7) Rateio do KIT (Opção C)
O que é

Quando o item é KIT, o valor total do KIT é rateado entre os componentes proporcionalmente ao preço base do componente.

Compra: usa appproduto.preco_compra como “V.Unit”

Venda: usa appproduto.preco_revenda como “V.Unit”

Fórmula (conceito)

Base do componente = (V.Unit_base * Quantidade_componente)

Base total = soma de todas as bases

Parte do componente = (base_comp / base_total) * Valor_total_kit

Preço unitário do componente = parte_comp / qtd_comp

Implementação

Foi implementada no helper:

explodeItemEmComponentes($item, $campoBase, $valorKitTotal)

Com rateio em centavos para reduzir erro de arredondamento e ajuste do resto no último item.

8) Cancelamento de Venda (PENDENTE / ENTREGUE)
Fluxo

Se status for PENDENTE, ABERTO, RESERVADO:

chama cancelarReservaVenda($pedido)

libera appestoque.reservado

marca movimentos pendentes como CANCELADO

registra estorno (ENTRADA) por componente (quando kit)

Se status for ENTREGUE:

devolve estoque_gerencial

registra movimento correspondente

Resultado

Testado: estorna corretamente tanto pendente quanto entregue.

9) UI: Edição do Produto (Composição do Kit)
Arquivo

resources/views/produtos/edit.blade.php

Problema resolvido

Durante os testes, era fácil esquecer de:

selecionar produto corretamente

preencher quantidade
e o item acabava não entrando na composição (impactando estoque depois).

Solução implementada

Botões por linha:

✅ Confirmar (obrigatório para validar)

✏️ Editar (volta a permitir mexer)

❌ Remover

Botão ➕ “Adicionar item”:

movido para o título

só habilita quando todas as linhas estiverem confirmadas

Submit do formulário:

bloqueia se existir item do kit não confirmado ou inválido

10) Checklist de Testes (QA)
Estoque e empresa

 Criar venda PENDENTE e confirmar que appestoque.reservado aumenta na empresa correta

 Cancelar venda PENDENTE e confirmar que reserva é liberada e appmovestoque.empresa_id é gravado

 Confirmar venda ENTREGUE e verificar baixa de estoque na empresa correta

 Cancelar venda ENTREGUE e verificar devolução de estoque na empresa correta

Kit + Rateio

 Criar um kit com 3+ componentes

 Registrar venda/compra do kit

 Conferir appmovestoque.preco_unitario de cada componente (deve ser rateado)

 Validar se soma dos totais rateados bate com total do kit (diferença de centavos no máximo)

UI do kit

 Tentar salvar com item não confirmado -> deve bloquear

 Confirmar item -> botão ➕ libera

 Editar item confirmado -> botão ➕ volta a bloquear até confirmar

11) Observações Futuras (melhorias)

Ajustar join do estoque na view:

e.empresa_id = p.empresa_id

No lookup, permitir filtro por “somente em estoque” por empresa.

Em appmovestoque, considerar gravar também total explícito (hoje é computed) se precisar histórico fixo.

Criar testes automatizados (Feature Tests) para os fluxos de estoque multi-empresa.