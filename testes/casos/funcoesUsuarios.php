<?php
require_once __DIR__ . '/../../funcoes/funcoesUsuarios.php';

testar('Usuários', 'buscaUsuarioPorId retorna null para id inexistente', function () {
    esperaNulo(buscaUsuarioPorId(999999999));
});

testar('Usuários', 'buscaUsuarioPorEmail retorna null para email inexistente', function () {
    esperaNulo(buscaUsuarioPorEmail('naoexiste_teste@naoexiste.com'));
});

testar('Usuários', 'buscaUsuarioPorTelefone retorna null para telefone inexistente', function () {
    esperaNulo(buscaUsuarioPorTelefone('000000000000000'));
});

testar('Usuários', 'listaUsuarios retorna array', function () {
    $lista = listaUsuarios();
    esperaVerdadeiro(is_array($lista));
});

testar('Usuários', 'listaUsuarios filtra por busca sem lançar erro', function () {
    $lista = listaUsuarios(['busca' => 'xyz_nao_existe']);
    esperaVerdadeiro(is_array($lista));
    espera(count($lista), 0);
});
