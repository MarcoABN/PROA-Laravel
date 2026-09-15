<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administradores do PROA
    |--------------------------------------------------------------------------
    | E-mails das contas de administrador (separados por vírgula no .env).
    | O administrador entra em todas as áreas restritas (financeiro, usuários,
    | agendamento), é o único que concede permissões e fica fora da lista de Usuários.
    | A comparação ignora maiúsculas/minúsculas e espaços.
    */

    'administradores' => array_values(array_filter(array_map(
        fn(string $email) => mb_strtolower(trim($email)),
        explode(',', (string) env('PROA_ADMINISTRADORES', 'marcoanunes23@gmail.com'))
    ))),

];
