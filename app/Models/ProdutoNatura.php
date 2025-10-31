<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoNatura extends Model
{


    // ajuste se o nome for diferente
    protected $table = 'appprodutonatura';
// ...dentro da classe ProdutoNatura

    public function getImageUrlAttribute(): ?string
    {
        // 1) decodifica o campo images
        $imgs = is_array($this->images) ? $this->images : json_decode((string)$this->images, true);
        if (!is_array($imgs)) return null;

        // 2) percorre e tenta achar a melhor URL
        $candidates = [];
        foreach ($imgs as $img) {
            // tenta em várias chaves comuns
            $url = data_get($img, 'link')
                ?? data_get($img, 'url')
                ?? (data_get($img, 'disBaseLink') && data_get($img, 'path') ? data_get($img, 'disBaseLink') . data_get($img, 'path') : null)
                ?? data_get($img, 'absUrl');
            if ($url) $candidates[] = $url;
        }

        $url = $candidates[0] ?? null;
        if (!$url) return null;

        // 3) se vier relativo, prefixa o host
        if (str_starts_with($url, '/')) {
            $url = 'https://www.natura.com.br' . $url;
        }

        // 4) algumas imagens aceitam parâmetros de tamanho (ajuda no layout)
        if (!str_contains($url, '?')) {
            $url .= '?sw=480&sh=480&sm=fit';
        }

        return $url;
    }

    // Se sua tabela NÃO tem created_at/updated_at:
    public $timestamps = false;

    protected $fillable = [
        'productId',
        'name',
        'friendlyName',
        'categoryId',
        'categoryName',
        'classificationId',
        'classificationName',
        'description',
        'price_sales_value',
        'price_list_value',
        'discount_percent',
        'rating',
        'tags',
        'images',
        'url',
        'raw_json',
        'categoria_slug',
        'ciclo_data',
    ];

    // opcionalmente proteja:
    protected $guarded = [
        'datainclusao',
    ];
}
