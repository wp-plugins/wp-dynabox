<?php
/*
  Plugin Name: WP-Dynabox
  Plugin URI: http://oquetananet.com/wordpress/plugins/wp-dynabox/
  Description: Inclui o código para monetização do sistema Dynabox do programa de afiliados do Buscapé <a href="http://afiliados.buscape.com.br/afiliados/Lomadee.do">Programa de afiliados do Buscapé</a> e permite personalizá-lo sem mexer no tema do blog.
  Author: O quê ta na net
  Version: 1.0.5
  Author URI: http://oquetananet.com/
 */
global $wpdynabox_options;
global $domain;
global $wpdynaboxversion;

$wpdynaboxversion = "1.0.5";
$domain = "wp-dynabox";
$wpdynabox_options = get_option('wpdynabox_options');

register_activation_hook(__FILE__, 'wpdynabox_activate');
register_deactivation_hook(__FILE__, 'wpdynabox_deactivate');

add_action('admin_notices', 'wpdynabox_alerta');
add_action('admin_head', 'wpdynabox_admin_head');
add_action('admin_menu', 'wpdynabox_add_pages');
add_action('admin_menu', 'wpdynabox_create_meta_box');

add_action('wp_head', 'wpdynabox_footer_css');
add_action('wp_footer', 'wpdynabox_footer');

add_action('edit_post', 'wpdynabox_code_exclusionUpdate');
add_action('publish_post', 'wpdynabox_code_exclusionUpdate');
add_action('save_post', 'wpdynabox_code_exclusionUpdate');

add_action('edit_post', 'wpdynabox_custom_colorUpdate');
add_action('publish_post', 'wpdynabox_custom_colorUpdate');
add_action('save_post', 'wpdynabox_custom_colorUpdate');

if ($wpdynabox_options['show_post'] == 'checked')
    add_filter('the_content', 'wpdynabox_core');

if ($wpdynabox_options['show_com'] == 'checked')
    add_filter('comment_text', 'wpdynabox_core');

load_plugin_textdomain("wp-dynabox", null, '/wp-dynabox');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpdynabox_plugin_actions');

/* * *************************************************************************************************
 * Link para configuração do plugin na página de administração de plugins
 */

function wpdynabox_plugin_actions($links) {

    $settings_link = '<a href="options-general.php?page=wp-dynabox.php">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);

    return $links;
}

/* * *************************************************************************************************
 *  Metabox
 */

function wpdynabox_create_meta_box() {
    if (function_exists('add_meta_box')) {
        add_meta_box('wpdynabox_write_post_sidebar', 'WP-Dynabox', 'wpdynabox_write_post_sidebar', 'post', 'normal', 'high');
    }
}

/* * *************************************************************************************************
 *  Coisas para serem feitas na instalacao do plugin
 */

function wpdynabox_activate() {

    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

    global $wpdynaboxversion;

    $wpdynabox_options = get_option('wpdynabox_options');

    if ($wpdynabox_options == FALSE) {
        $wpdynabox_options = array('uninstall' => '', 'id' => '', 'colour' => '', 'show_footer' => 'checked', 'footer_align' => 'center', 'footer_line' => 'inline', 'show_com' => '', 'show_post' => 'checked', 'show_index' => '', 'clicks' => '', 'earnings' => '', 'lastrun' => '', 'DynaboxS' => 'Dynaboxbr', 'version' => $wpdynaboxversion);
        add_option('wpdynabox_options', $wpdynabox_options);
    } else {
        $wpdynabox_options['version'] = $wpdynaboxversion;
        $wpdynabox_options['DynaboxS'] = "Dynaboxbr";
        update_option('wpdynabox_options', $wpdynabox_options);
    }

    if (!wp_next_scheduled('wpdynabox_cron')) {
        wp_schedule_event(time(), 'daily', 'wpdynabox_cron');
    }
}

/* * *************************************************************************************************
 *  Antes de desativar a funcao abaixo eh executada
 */

function wpdynabox_deactivate() {

    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

    global $wpdynabox_options;

    if ($wpdynabox_options['uninstall'] == "checked") {
        delete_option('wpdynabox_options');
    }

    if (wp_next_scheduled('wpdynabox_cron')) {
        wp_clear_scheduled_hook('wpdynabox_cron');
    }
}

/* * *************************************************************************************************
 *  Alerta sobre problemas com a configuracao do plugin
 */

function wpdynabox_alerta() {

    global $wpdynabox_options;
    global $domain;
    global $wpdynaboxversion;

    if (!isset($_POST['info_update'])) {

        if ($wpdynabox_options['version'] != $wpdynaboxversion) {
            $msg = __('* Parece que você atualizou a versão nova sem desativar o plugin!! Por favor desative e re-ative.', $domain);
        } else {

            if ($wpdynabox_options['id'] == '') {
                $msg = '* ' . __('Você ainda não informou o código do site de origem do Dynabox!', $domain) . '<br />' . sprintf(__('Se você já tem uma conta de afiliado do programa de afiliados do buscapé, informe <a href="%1$s">aqui</a>, caso contrário <a href="%2$s">crie uma agora</a>.', $domain), "options-general.php?page=wp-dynabox.php", "http://afiliados.buscape.com.br/afiliados/Lomadee.do") . '<br />';
            }
        }

        if ($msg) {
            echo "<div class='updated fade-ff0000'><p><strong>" . __('WP-Dynabox Alerta!', $domain) . "</strong><br /> " . $msg . "</p></div>";
        }
        return;
    }
}

/* * *************************************************************************************************
 *  Inclui um menu de administracao
 */

function wpdynabox_add_pages() {
    if (function_exists('add_options_page')) {
        add_options_page('WP-Dynabox', 'WP-Dynabox', 9, 'wp-dynabox.php', 'wpdynabox_options_page');
    }
}

/* * *************************************************************************************************
 *  Codigos a serem inseridos no HEAD do admin.
 */

function wpdynabox_admin_head() {
    echo '	<link rel="stylesheet" href="' . WP_PLUGIN_URL . '/wp-dynabox/js_color_picker_v2.css" media="screen">';
    echo '	<script src="' . WP_PLUGIN_URL . '/wp-dynabox/color_functions.js"></script>		';
    echo '	<script type="text/javascript" src="' . WP_PLUGIN_URL . '/wp-dynabox/js_color_picker_v2.js"></script>';
}

/* * *************************************************************************************************
 *  Inclui o codigo do Dynabox
 */

function wpdynabox_core($content) {

    global $thePostID;
    global $wp_query;
    global $wpdynabox_options;

    $thePostID = $wp_query->post->ID;
    $EmbedDynaboxTag = get_post_custom_values('wp-dynabox');

    if (!is_single() AND ( $wpdynabox_options['show_index'] != 'checked'))
        return ($content);

    if ((!is_feed()) AND ( !$EmbedDynaboxTag[0])) {
        $content = '<div id="dynabox" class="dynabox">' . $content . '</div>';
    }
    return $content;
}

/* * *************************************************************************************************
 *  Mostra rodape de creditos do desenvolvedor do plugin
 */

function wpdynabox_footer() {

    global $wpdynabox_options;
    global $thePostID;

    switch ($wpdynabox_options['footer_line']) {
        case "br_before":
            $br_before = "<br />";
            break;
        case "br_after":
            $br_after = "<br />";
            break;
        case "p":
            $p_before = "<p>";
            $p_after = "</p>";
            break;
        case "inline":
            break;
    }

    $corpadrao = $wpdynabox_options['colour'];

    $corpersonalizada = get_post_meta($thePostID, 'wp-dynabox_custom_color', true);

    if (is_single()) {
        if ($corpersonalizada != '') {
            $Dynaboxcor = $corpersonalizada;
        } else {
            $Dynaboxcor = $corpadrao;
        }
    } else {
        $Dynaboxcor = $corpadrao;
    }

    $Dynaboxcor = str_replace('#', '', $corpadrao);
    if ($Dynaboxcor != "")
        $Dynaboxcolour = "&amp;cor=" . $Dynaboxcor;
    else
        $Dynaboxcolour = '';

    switch ($wpdynabox_options['DynaboxS']) {

        case "Dynaboxbr":
            echo '<!-- WP-Dynabox for WordPress | http://oquetananet.com/wordpress/plugins/wp-dynabox/ --><br/>';
            echo '<script type="text/javascript" src="http://boxes.lomadee.com/bs/config.html?divname=dynabox&amp;c=BR&amp;mdsrc=' . $wpdynabox_options['id'] . $Dynaboxcolour . '"></script><br/>';
            if ($wpdynabox_options['show_footer'] == 'checked') {
                echo $br_before . $p_before . '<div class="wpdynabox_footer">Este blog está utilizando o plugin <a href="http://oquetananet.com/wordpress/plugins/wp-dynabox/">WP-Dynabox ';
                echo $wpdynabox_options['version'] . '</a></div>' . $p_after . $br_after;
            }
            echo '<br/><!-- End of WP-Dynabox code -->';

            break;
    }
}

/* * *************************************************************************************************
 *  Inclui o CSS para o footer
 */

function wpdynabox_footer_css() {

    global $wpdynabox_options;
    $Dynabox_align_footer = $wpdynabox_options['footer_align'];

    echo '<style type="text/css"> <!-- div.wpdynabox_footer {';

    if ($Dynabox_align_footer == 'center')
        echo "text-align: center;";

    if ($Dynabox_align_footer == 'left')
        echo "text-align: left;";

    if ($Dynabox_align_footer == 'right')
        echo "text-align: right;";

    echo " } --> </style>";
}

/* * *************************************************************************************************
 *  Barra Lateral para edicao opcoes wp-dynabox por artigo.
 */

function wpdynabox_write_post_sidebar() {

    global $post;
    global $domain;

    $checked = '';
    $showDynabox = get_post_meta($post->ID, 'wp-dynabox', true);
    $ccDynabox = get_post_meta($post->ID, 'wp-dynabox_custom_color', true);

    if ($showDynabox == "nao")
        $checked = "checked";

    echo "<div class=\"inside\">";
    echo '<input type="checkbox" id="DynaboxcodEX" name="DynaboxcodeExclusion" value="nao"' . $checked . '> <label for="DynaboxcodEX">' . __('Sem anúncios', $domain) . '</label><br />';
    echo '<input type="hidden" name="DynaboxcodeExclusion-key" id="DynaboxcodeExclusion-key" value="' . wp_create_nonce('DynaboxcodeExclusion') . '" />';

    echo '<br /><label for="cordif">' . __('Cor diferenciada:', $domain) . '</label><br />';
    echo '<input type="hidden" name="Dynabox_custom_color-key" id="Dynabox_custom_color-key" value="' . wp_create_nonce('rgb2') . '" />';
    echo '<input type="text" id="cordif" size="7" maxlength="7" name="rgb2" value="' . $ccDynabox . '">';
    //echo '<input type="button" value="'.__('Escolher', $domain).'" onclick="showColorPicker(this,document.forms[0].rgb2)">';

    echo '<br />' . __('Para usar a cor padrão, deixe a caixa de texto acima em branco.', $domain) . '</div>';
}

/* * *************************************************************************************************
 *  Painel de opcoes do plugin
 */

function wpdynabox_options_page() {

//pega dados da base
    global $wpdynabox_options;
    global $domain;

    //processa novos dados para atualizacao
    if (isset($_POST['info_update'])) {

        $wpdynabox_options['id'] = $_POST['id'];

        if (isset($_POST['DynaboxS']))
            $wpdynabox_options['DynaboxS'] = $_POST['DynaboxS'];

        if (isset($_POST['footer_align']))
            $wpdynabox_options['footer_align'] = $_POST['footer_align'];

        if (isset($_POST['footer_line']))
            $wpdynabox_options['footer_line'] = $_POST['footer_line'];

        if (isset($_POST['rgb2']))
            $wpdynabox_options['colour'] = $_POST['rgb2'];

        $wpdynabox_options['show_footer'] = $_POST['show_footer'];
        $wpdynabox_options['show_post'] = $_POST['show_post'];
        $wpdynabox_options['show_com'] = $_POST['show_com'];
        $wpdynabox_options['show_index'] = $_POST['show_index'];

        $wpdynabox_options['uninstall'] = $_POST['uninstall'];
        //atualiza base de dados com informacaoes do formulario
        update_option('wpdynabox_options', $wpdynabox_options);
    }

    switch ($wpdynabox_options['DynaboxS']) {
        case "Dynaboxbr":
            $DynaboxSHbr = "checked";
            break;
        default:
            $DynaboxSHbr = "checked";
    }

    switch ($wpdynabox_options['footer_align']) {
        case "center":
            $center = "checked";
            break;
        case "left":
            $left = "checked";
            break;
        case "right":
            $right = "checked";
            break;
        default:
            $left = "checked";
    }

    switch ($wpdynabox_options['footer_line']) {
        case "br_before":
            $br_before = "checked";
            break;
        case "br_after":
            $br_after = "checked";
            break;
        case "p":
            $p = "checked";
            break;
        case "inline":
            $inline = "checked";
            break;
        default:
            $p = "checked";
    }

    $cor = $wpdynabox_options['colour'];
    if ($wpdynabox_options['colour'] == '') {
        $cor = '';
        $msg = 'Cor padrão do Dynabox.';
    }
    ?>
    <div class="wrap">
        <form method="post">

            <h2><?php _e('Configuração WP-Dynabox', $domain); ?> <?php echo $wpdynabox_options['version']; ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php _e('Código do site de origem ', $domain); ?></th>
                    <td>
                        <label for="id"><?php _e('Código :', $domain); ?></label> <input name="id" type="text" id="id" value="<?php echo $wpdynabox_options['id']; ?>" size=8  /><br />
                        <?php _e('O seu código do site de origem pode ser encontrado na aba ferramentas e depois em Dynabox. <br />O código do site de origem é o número que aparece após o texto \'DynaboxConfig?div_nome=dynabox&amp;site_origem=XXXXXXX\'.', $domain); ?><br />
                        <input type="radio" id="Dynaboxbr" name="DynaboxS" value="Dynaboxbr" <?php echo $DynaboxSHbr; ?> /> <label for="Dynaboxbr">Dynabox Brasil</label> <small><a href="http://afiliados.buscape.com.br/afiliados/Dynabox.do">(site)</a></small>
                    </td>
                </tr>
            </table>
            <br />

            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php _e('Defina onde os anúncios deverão ser mostrados', $domain); ?></th>
                    <td>
                        <input type="checkbox" id="show_post" name="show_post" value="checked" <?php echo $wpdynabox_options['show_post']; ?>> <label for="show_post"><?php _e('No texto do artigo', $domain); ?></label><br />
                        <input type="checkbox" id="show_com" name="show_com" value="checked" <?php echo $wpdynabox_options['show_com']; ?>> <label for="show_com"><?php _e('No texto dos comentários', $domain); ?></label><br />
                        <input type="checkbox" id="show_index" name="show_index" value="checked" <?php echo $wpdynabox_options['show_index']; ?>> <label for="show_index"><?php _e('Na página com mais de um artigo', $domain); ?></label><br />
                    </td>
                </tr>
            </table>
            <br />

            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php _e('Personalização do link Dynabox', $domain); ?></th>
                    <td>
                        <br /><?php _e('Você pode selecionar a cor padrão para os links de anúncios Dynabox. Se você quiser, pode ainda trocar a cor dos links em um determinado artigo. Para fazê-lo, basta selecionar a cor na página de edição do artigo.<br /><br />Para usar cor padrão do Dynabox deixe a caixa Nova cor vazia.', $domain); ?>
                        <table>
                            <tr>
                                <td><strong><?php _e('Cor atual', $domain); ?> : </strong></td><td WIDTH="20" BGCOLOR="<?php echo $cor; ?>"><?php echo $cor; ?></td><td><?php echo $msg; ?></td><td><strong><?php _e('Nova cor', $domain); ?> : </strong><input type="text" id="cor" size="10" maxlength="7" name="rgb2" value="<?php echo $cor; ?>">
                                    <input type="button" value="<?php _e('Escolha uma Cor', $domain); ?>" onclick="showColorPicker(this, document.forms[0].rgb2)">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <br />

            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php _e('Aparência do Rodapé', $domain); ?></th>
                    <td>
                        <?php _e('Você pode escolher se deseja que os créditos no rodapé seja exibido ou não e pode configurar como os créditos no rodapé iram aparecer.', $domain); ?><br />
                        <br />
                        <input type="checkbox" id="show_footer" name="show_footer" value="checked" <?php echo $wpdynabox_options['show_footer']; ?>> <label for="show_footer"><?php _e('Exibir os créditos no rodapé', $domain); ?></label><br />
                        <br />
                        <?php _e('Alinhamento horizontal', $domain); ?>:
                        <input type="radio" id="fac" name="footer_align" value="center" <?php echo $center; ?> /> <label for="fac"><?php _e('Centralizado', $domain); ?></label>
                        <input type="radio" id="fal" name="footer_align" value="left" <?php echo $left; ?>/> <label for="fal"><?php _e('Esquerda', $domain); ?></label>
                        <input type="radio" id="far" name="footer_align" value="right" <?php echo $right; ?>/> <label for="far"><?php _e('Direita', $domain); ?></label>
                        <br />
                        <?php _e('Alinhamento vertical', $domain); ?>:
                        <input type="radio" id="flb" name="footer_line" value="br_before" <?php echo $br_before; ?>/> <label for="flb"><?php _e('Nova linha antes do rodapé', $domain); ?></label>
                        <input type="radio" id="fla" name="footer_line" value="br_after" <?php echo $br_after; ?>/> <label for="fla"><?php _e('Nova linha após o rodapé', $domain); ?></label>
                        <input type="radio" id="flp" name="footer_line" value="p" <?php echo $p; ?>/> <label for="flp"><?php _e('Novo parágrafo', $domain); ?></label>
                        <input type="radio" id="fli" name="footer_line" value="inline" <?php echo $inline; ?>/> <label for="fli"><?php _e('Mesma linha', $domain); ?></label>

                    </td>
                </tr>
            </table>
            <br />

            <table class="form-table">
                <tr>
                    <th scope="row" valign="top"><?php _e('Desinstalação', $domain); ?></th>
                    <td>
                        <input type="checkbox" id="uninstall" name="uninstall" value="checked" <?php echo $wpdynabox_options['uninstall']; ?>> <label for="uninstall"><?php _e('Remover Todas as opções *', $domain); ?></label><br />
                        <br /><strong>(*) <?php _e('Atenção', $domain); ?> : </strong> <?php _e('Ative esta opção para remover todas as configurações deste plugin na sua desativação através da página de administração de plugins.', $domain); ?>
                    </td>
                </tr>
            </table>
            <br />

            <span class="submit">
                <input type="submit" name="info_update" value="<?php _e('Atualizar Opções', $domain); ?> &raquo;" />
            </span>

            <h2><?php _e('Sobre o WP-Dynabox', $domain); ?> <?php echo $wpdynabox_options['version']; ?></h2>
            <p><?php echo sprintf(__('O sistema <a href="%1$s">Dynabox</a> publica anúncios contextuais dentro de textos de uma grande e qualificada rede de sites parceiros, o que possibilita ao anunciante comunicar-se com seu público-alvo de maneira inovadora, direta e segmentada.', $domain), "http://afiliados.buscape.com.br/afiliados/Dynabox.do"); ?></p>
            <p><?php echo sprintf(__('Este plugin foi desenvolvido pelo <a href="%1$s">O quê ta na net</a> para facilitar a vida do blogueiro que utiliza <a href="%2$s">Wordpress</a>. Com ele os artigos recebem automaticamente os Divs necessários para que o programa funcione no seu blog e também inclui o script no rodapé. Com este plugin você não precisa mais editar o seu tema para que o Dynabox funcione.', $domain), "http://oquetananet.com/", "http://wordpress.org/"); ?></p>
            <p><?php _e('Algumas perguntas frequêntes:', $domain); ?></p>
            <ul>
                <li><strong><?php _e('Não aparecem anúncios nos meus artigos. Verifiquei o código da página do artigo e os códigos do Dynabox não estão sendo incluídos. O que eu faço?', $domain); ?></strong><br />
                    <?php _e('O problema pode estar no tema do seu blog, garanta que existe a chamada "wp_footer()" no arquivo de tema "Rodapé" (ou footer, caso seu wordpress esteja em inglês) do seu tema ativo.', $domain); ?></li>
                <li><strong><?php _e('Preciso me cadastrar em algum lugar para usar este plugin?', $domain); ?></strong><br />
                    <?php _e('Sim! é necessário ter uma conta ativa no programa de afiliados do buscapé para usar a ferramenta Dynabox, para que o plugin funcione como esperado.', $domain); ?></li>
                <li><strong><?php _e('Posso determinar que não sejam mostrados anúncios do Dynabox em alguns artigos?', $domain); ?></strong><br />
                    <?php _e('Se você quiser que algum artigo não receba anúncios do Dynabox, basta escolher a opção "Não mostrar anúncios do Dynabox neste artigo" na página de edição de artigos.', $domain); ?></li>
                <li><strong><?php _e('Como posso otimizar meus ganhos com o Dynabox?', $domain); ?></strong><br />
                    <?php echo sprintf(__('O primeiro passo é ter conteúdo relevante. O segundo é personalizar as cores de links do Dynabox para serem mais atraentes para o seu tema. Veja mais <a href="%1$s">aqui</a>.', $domain), "http://ivitrine.buscape.com.br/site/Manual-Dynabox_BR.pdf"); ?></li>

            </ul>


            <p><?php echo sprintf(__('Acesse regularmente a <a href="%1$s">página do plugin</a> para verificar se novas versões foram liberadas e instruções de como atualizar seu plugin.', $domain), "http://oquetananet.com/wordpress/plugins/wp-dynabox/"); ?></p>
        </form>
        <p><?php _e('O autor deste plugin aceita sua doação para manter este plugin. É uma ótima maneira de você demonstrar seu reconhecimento pelo trabalho realizado!', $domain); ?></p>
        <center>
            <!-- INICIO FORMULARIO BOTAO PAGSEGURO -->
            <form target="pagseguro" action="https://pagseguro.uol.com.br/security/webpagamentos/webdoacao.aspx" method="post">
                <input type="hidden" name="email_cobranca" value="ellgrupo@gmail.com">
                <input type="hidden" name="moeda" value="BRL">
                <input type="image" src="https://pagseguro.uol.com.br/Security/Imagens/FacaSuaDoacao.gif" name="submit" alt="Pague com PagSeguro - é rápido, grátis e seguro!">
            </form>
            <!-- FINAL FORMULARIO BOTAO PAGSEGURO -->
        </center>

    </div>
    <?php
}

/* * *************************************************************************************************
 *  Atualiza a opcao de personalizacao do codigo Dynabox para artigo/pagina
 */

function wpdynabox_code_exclusionUpdate($id) {

// authorization
    if (!current_user_can('edit_post', $id))
        return $id;
    // atualiza a exclusao de anuncios por artigo
    if (!wp_verify_nonce($_POST['DynaboxcodeExclusion-key'], 'DynaboxcodeExclusion'))
        return $id;

    // atualiza a exclusao de anuncios por artigo
    $setting = $_POST['DynaboxcodeExclusion'];

    // apaga o metadado se for conteudo vazio
    if (!$setting)
        delete_post_meta($id, 'wp-dynabox');
    else
        $meta_exists = update_post_meta($id, 'wp-dynabox', $setting);
    if ((!$meta_exists) AND ( $setting != '')) {
        add_post_meta($id, 'wp-dynabox', $setting);
    }
    return $id;
}

/* * *************************************************************************************************
 * Atualiza a opcao de cor diferenciada do codigo Dynabox de artigo/pagina
 */

function wpdynabox_custom_colorUpdate($id) {

// authorization
    if (!current_user_can('edit_post', $id))
        return $id;
    // atualizacao da cor personalizada de artigos para artigos
    if (!wp_verify_nonce($_POST['Dynabox_custom_color-key'], 'rgb2'))
        return $id;

    // apaga o metadado se for conteudo vazio
    $setting = $_POST['rgb2'];
    if (!$setting)
        delete_post_meta($id, 'wp-dynabox_custom_color');
    else
        $meta_exists = update_post_meta($id, 'wp-dynabox_custom_color', $setting);
    if ((!$meta_exists) AND ( $setting != '')) {
        add_post_meta($id, 'wp-dynabox_custom_color', $setting);
    }
    return $id;
}

/* * *************************************************************************************************
 * Decode HTTP Request Header
 */

function wpdynabox_decode_header($str) {
    $part = preg_split("/\r?\n/", $str, -1, PREG_SPLIT_NO_EMPTY);
    $out = array();
    for ($h = 0; $h < sizeof($part); $h++) {
        if ($h != 0) {
            $pos = strpos($part[$h], ':');
            $k = strtolower(str_replace(' ', '', substr($part[$h], 0, $pos)));
            $v = trim(substr($part[$h], ( $pos + 1)));
        } else {
            $k = 'status';
            $v = explode(' ', $part[$h]);
            $v = $v[1];
        }

        if ($k == 'set-cookie') {
            $out['cookies'][] = $v;
        } else if ($k == 'content-type') {
            if (( $cs = strpos($v, ';') ) !== false) {
                $out[$k] = substr($v, 0, $cs);
            } else {
                $out[$k] = $v;
            }
        } else {
            $out[$k] = $v;
        }
    }
    return $out;
}

/* * *************************************************************************************************
 * This function has been copyed from Akismet
 *  Returns array with headers in $response[0] and body in $response[1]
 */

function wpdynabox_http_post($request, $host, $path, $port = 80) {

    global $wp_version;
    global $wpdynabox_options;

    $http_request = "POST $path HTTP/1.0\r\n";
    $http_request .= "Host: $host\r\n";
    $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
    $http_request .= "Content-Length: " . strlen($request) . "\r\n";
    $http_request .= "User-Agent: WordPress/$wp_version | wp-dynabox/" . $wpdynabox_options['version'] . "\r\n";
    $http_request .= "\r\n";
    $http_request .= $request;

    $response = '';
    if (false != ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) )) {
        fwrite($fs, $http_request);

        while (!feof($fs))
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);
    }
    return $response;
}
