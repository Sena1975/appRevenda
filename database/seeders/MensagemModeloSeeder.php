<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MensagemModeloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Timestamp padrão para created_at / updated_at
        $now = Carbon::now();

        // Evita duplicar se já existir o código
        $modelos = [
            [
                'codigo'   => 'boas_vindas_cliente',
                'nome'     => 'Boas-vindas para novo cliente',
                'canal'    => 'whatsapp',
                'conteudo' => "Olá {{NOME_CLIENTE}}! 👋\n\n"
                             ."Seja muito bem-vinda(o)! 💙\n"
                             ."Aqui é {{NOME_LOJA}} e a partir de agora você vai receber por aqui novidades, promoções exclusivas e dicas especiais.\n\n"
                             ."Sempre que precisar de ajuda com produtos, pedidos ou dúvidas, é só me chamar por aqui no WhatsApp. 😊\n\n"
                             ."Obrigado por confiar em {{NOME_LOJA}}!",
                'ativo'    => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo'   => 'pedido_pendente_cliente',
                'nome'     => 'Resumo do pedido pendente para o cliente',
                'canal'    => 'whatsapp',
                'conteudo' => "Olá {{NOME_CLIENTE}}! 👋\n\n"
                             ."Registramos o seu pedido *#{{NUMERO_PEDIDO}}* e já estamos providenciando os produtos que você solicitou. 🙌\n\n"
                             ."🧾 Data do pedido: *{{DATA_PEDIDO}}*\n"
                             ."💰 Valor do pedido: *R$ {{VALOR_PEDIDO}}*\n"
                             ."💳 Forma de pagamento: *{{FORMA_PAGAMENTO}}*{{LINHA_PLANO_PAGAMENTO}}{{LINHA_PREVISAO_ENTREGA}}{{LINHA_OBSERVACAO}}\n\n"
                             ."Assim que o pedido for entregue, você receberá uma confirmação por aqui.\n"
                             ."Qualquer dúvida, é só responder esta mensagem. 🙂",
                'ativo'    => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo'   => 'recibo_entrega_cliente',
                'nome'     => 'Recibo de entrega do pedido',
                'canal'    => 'whatsapp',
                'conteudo' => "Olá {{NOME_CLIENTE}}! 👋\n\n"
                             ."Seu pedido nº *{{NUMERO_PEDIDO}}* foi *ENTREGUE* em {{DATA_ENTREGA}}. 🎉\n\n"
                             ."Ele foi registrado em {{DATA_PEDIDO}} e ficou no valor final de *R$ {{VALOR_LIQUIDO}}*.\n\n"
                             ."📅 Detalhes do pagamento:\n"
                             ."{{LINHAS_PARCELAS}}\n\n"
                             ."Qualquer dúvida, estou à disposição por aqui. Muito obrigado pela confiança! 💙",
                'ativo'    => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo'   => 'indicacao_pedido_pendente',
                'nome'     => 'Indicação – pedido do indicado registrado',
                'canal'    => 'whatsapp',
                'conteudo' => "Olá {{NOME_INDICADOR}}! 👋\n\n"
                             ."Boa notícia: a sua indicação *{{NOME_INDICADO}}* acabou de fazer um pedido comigo. 🙌\n\n"
                             ."🧾 Pedido nº: *{{NUMERO_PEDIDO}}*\n"
                             ."💰 Valor do pedido: *R$ {{VALOR_PEDIDO}}*\n\n"
                             ."Assim que o pedido for entregue, sua indicação pode gerar uma recompensa em dinheiro 💰 que pode chegar a *até 10%* do valor da compra, de acordo com a tabela da campanha.\n\n"
                             ."Fique de olho por aqui que, quando o prêmio estiver disponível, eu te aviso e combinamos o pagamento. 😉",
                'ativo'    => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo'   => 'indicacao_premio_pix',
                'nome'     => 'Indicação – prêmio em dinheiro disponível',
                'canal'    => 'whatsapp',
                'conteudo' => "{{NOME_INDICADOR}}, olha que notícia boa! 🎉\n\n"
                             ."O pedido do(a) indicado(a) *{{NOME_INDICADO}}* (pedido nº {{NUMERO_PEDIDO}}, valor *R$ {{VALOR_PEDIDO}}*) foi ENTREGUE com sucesso. 🙌\n\n"
                             ."De acordo com a nossa campanha de indicação, isso gerou um prêmio de *R$ {{VALOR_PREMIO}}* pra você. 💰\n\n"
                             ."Me envie ou confirme sua chave Pix para que eu faça o pagamento, ou me chama aqui pra combinarmos a melhor forma de receber.\n\n"
                             ."Obrigado por indicar! Continue indicando amigas, familiares e colegas para acumular ainda mais prêmios. 😉",
                'ativo'    => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo'   => 'convite_indicacao_primeira_compra',
                'nome'     => 'Convite para campanha de indicação (primeira compra)',
                'canal'    => 'whatsapp',
                'conteudo' => "Olá {{NOME_CLIENTE}}! 👋\n\n"
                             ."Que bom ter você comigo! Seu primeiro pedido já foi entregue e espero que tenha gostado dos produtos. 💙\n\n"
                             ."Agora quero te fazer um convite especial: participe de *{{NOME_CAMPANHA}}*.\n\n"
                             ."Funciona assim:\n"
                             ."➡️ Você indica amigas, familiares ou colegas;\n"
                             ."➡️ Quando elas fizerem a primeira compra, você ganha uma recompensa em dinheiro 💰 que pode chegar a *10%* do valor da compra.\n\n"
                             ."Seu último pedido foi de *R$ {{VALOR_PEDIDO}}*, imagina quanto dá pra ganhar indicando algumas pessoas? 😉\n\n"
                             ."{{LINHA_LINK_REGULAMENTO}}\n\n"
                             ."Se quiser participar, é só me chamar aqui e eu já te explico como começar a indicar. 🙌",
                'ativo'    => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($modelos as $modelo) {
            // Insere apenas se não existir o código
            $exists = DB::table('appmensagem_modelo')
                        ->where('codigo', $modelo['codigo'])
                        ->exists();

            if (! $exists) {
                DB::table('appmensagem_modelo')->insert($modelo);
            }
        }
    }
}
