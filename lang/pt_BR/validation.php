<?php

declare(strict_types=1);

return [
    // ... várias linhas acima ...

    'regex'                  => 'O formato do campo :attribute é inválido.',
    
    // ALTERAÇÃO 1: Mude esta linha
    'required'               => 'Campo Obrigatório', 
    
    'required_array_keys'    => 'O campo :attribute deve conter entradas para: :values.',
    
    // ... linhas do meio ...

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        
        // ALTERAÇÃO 2: Adicione isso aqui dentro do array 'custom'
        'cpfcnpj' => [
            'unique' => 'CPF já cadastrado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        // DICA: Você pode traduzir os nomes dos campos aqui para as mensagens ficarem bonitas
        'nome' => 'nome',
        'cpfcnpj' => 'CPF/CNPJ',
        'password' => 'senha',
        'email' => 'e-mail',
    ],
];