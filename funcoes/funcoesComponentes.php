<?php

require_once __DIR__ . '/funcoesConfiguracao.php';

function renderizaMarca($variante = 'ink', $tamanho = 20, $href = null) {
    $tag = $href !== null ? 'a' : 'div';
    $atributo_href = $href !== null ? ' href="' . htmlspecialchars($href) . '"' : '';
    echo '<' . $tag . ' class="marca"' . $atributo_href . '>'
        . '<span class="logo"><span style="display:flex;" data-bot="' . htmlspecialchars($variante) . '" data-size="' . (int) $tamanho . '"></span></span> '
        . htmlspecialchars(nomeApp())
        . '</' . $tag . '>';
}
