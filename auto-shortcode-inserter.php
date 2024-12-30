<?php
/**
 * Plugin Name: Auto Shortcode Inserter
 * Plugin URI: https://github.com/mailsonm/auto-shortcode-inserter
 * Description: Insere um shortcode automaticamente no início ou final de todas as postagens.
 * Version: 1.0
 * Author: mailsonm
 * Author URI: https://mailsondev.com.br/
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// Adiciona o menu de configurações do plugin no painel de administração do WordPress
add_action('admin_menu', 'asi_add_admin_menu');
add_action('admin_init', 'asi_settings_init');

// Função que cria o item de menu nas configurações do WordPress
function asi_add_admin_menu() {
    add_menu_page(
        'Auto Shortcode Inserter', // Título da página
        'Auto Shortcode',          // Título do item do menu
        'manage_options',          // Permite acesso para administradores
        'auto-shortcode-inserter', // Slug do plugin
        'asi_options_page',        // Função que renderiza o conteúdo da página
        'dashicons-editor-code',   // Ícone do menu
        100                        // Posição no menu
    );
}

// Função que inicializa as configurações do plugin
function asi_settings_init() {
    // Registra as configurações do plugin para serem armazenadas no banco de dados
    register_setting('asiSettings', 'asi_settings');

    // Cria a seção de configurações do plugin
    add_settings_section(
        'asi_section',
        __('Configurações Gerais', 'wordpress'), // Título da seção
        'asi_settings_section_callback', // Função de callback que descreve a seção
        'asiSettings'                    // Página onde a seção será exibida
    );

    // Cria os campos de configuração para os shortcodes no topo e no final das postagens
    for ($i = 1; $i <= 3; $i++) {
        // Adiciona campos para shortcodes no topo das postagens
        add_settings_field(
            "asi_shortcode_top_$i",
            __("Shortcode no topo $i", 'wordpress'), // Rótulo do campo
            'asi_shortcode_render',                  // Função que renderiza o campo
            'asiSettings',                            // Página de configurações
            'asi_section',                            // Seção onde o campo será exibido
            ['name' => "asi_shortcode_top_$i"]        // Parâmetro que identifica o campo
        );

        // Adiciona campos para shortcodes no final das postagens
        add_settings_field(
            "asi_shortcode_bottom_$i",
            __("Shortcode no final $i", 'wordpress'), // Rótulo do campo
            'asi_shortcode_render',                   // Função que renderiza o campo
            'asiSettings',                             // Página de configurações
            'asi_section',                             // Seção onde o campo será exibido
            ['name' => "asi_shortcode_bottom_$i"]      // Parâmetro que identifica o campo
        );
    }
}

// Função que renderiza o campo de configuração para os shortcodes
function asi_shortcode_render($args) {
    // Recupera as opções salvas no banco de dados
    $options = get_option('asi_settings');
    $name = $args['name']; // Nome do campo
    $value = $options[$name] ?? ''; // Valor armazenado, se existir
    // Exibe o campo de input para edição do shortcode
    echo "<input type='text' name='asi_settings[$name]' value='" . esc_attr($value) . "' style='width: 100%;' />";
}

// Função de callback que descreve a seção de configurações
function asi_settings_section_callback() {
    echo __('Configure os shortcodes que deseja adicionar no início ou final das postagens.', 'wordpress');
}

// Função que exibe a página de configurações no painel de administração
function asi_options_page() {
    // Verifica se o usuário tem a permissão necessária para acessar as configurações
    if ( ! current_user_can( 'manage_options' ) ) { // Permite acesso para administradores
        wp_die( __( 'Você não tem permissão para acessar esta página.' ) );
    }

    ?>
    <form action="options.php" method="post">
        <h1>Auto Shortcode Inserter</h1> <!-- Título da página -->
        <?php
        settings_fields('asiSettings'); // Gera o campo de segurança para o formulário
        do_settings_sections('asiSettings'); // Exibe as seções de configuração
        submit_button(); // Exibe o botão de envio do formulário
        ?>
    </form>
    <?php
}

// Filtra o conteúdo das postagens e adiciona os shortcodes definidos pelo usuário
add_filter('the_content', 'asi_add_shortcodes_to_content');

// Função que adiciona os shortcodes ao conteúdo das postagens
function asi_add_shortcodes_to_content($content) {
    // Verifica se é uma postagem singular
    if (is_singular('post')) {
        $options = get_option('asi_settings'); // Recupera as opções salvas
        $shortcodes_top = '';   // Variável para armazenar os shortcodes do topo
        $shortcodes_bottom = ''; // Variável para armazenar os shortcodes do final

        // Adiciona os shortcodes no topo, se existirem
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($options["asi_shortcode_top_$i"])) {
                $shortcodes_top .= '<div style="margin-bottom: 10px;">' . do_shortcode($options["asi_shortcode_top_$i"]) . '</div>' . "\n";
            }
            // Adiciona os shortcodes no final, se existirem
            if (!empty($options["asi_shortcode_bottom_$i"])) {
                $shortcodes_bottom .= '<div style="margin-top: 10px;">' . do_shortcode($options["asi_shortcode_bottom_$i"]) . '</div>' . "\n";
            }
        }

        // Adiciona os shortcodes ao conteúdo da postagem (topo + conteúdo + final)
        $content = $shortcodes_top . $content . $shortcodes_bottom;
    }
    return $content; // Retorna o conteúdo modificado
}
